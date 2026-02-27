<?php
namespace BookneticAddon\Newsletters\Providers;

use BookneticAddon\Newsletters\NewsletterIntegrationInterface;
use BookneticApp\Providers\Helpers\Helper;

class MailChimpIntegration implements NewsletterIntegrationInterface
{
    private $apiKey;
    private $dataCenter;
    private $listId;

    public function __construct()
    {
        $this->apiKey = Helper::getOption('newsletter_mailchimp_api_key', '');
        // Extract data center from API key
        $this->dataCenter = '';
        if (strpos($this->apiKey, '-') !== false) {
            $parts = explode('-', $this->apiKey);
            $this->dataCenter = array_pop($parts);
        }
    }

    public function configure(array $settings): void
    {
        $this->listId = $settings['list_id'] ?? '';
    }

    public function getLists(): array
    {
        if (empty($this->apiKey) || empty($this->dataCenter)) {
            return [];
        }
        $url = 'https://' . $this->dataCenter . '.api.mailchimp.com/3.0/lists';
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'apikey ' . $this->apiKey,
                'accept' => 'application/json',
            ],
            'sslverify' => false,
        ]);
        if (is_wp_error($response)) {
            error_log('[MailChimp getLists] Error: ' . $response->get_error_message());
            return [];
        }
        $body = $response['body'] ?? '';
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['lists'])) {
            error_log('[MailChimp getLists] Invalid response: ' . $body);
            return [];
        }
        // Normalize to id/name
        $lists = [];
        foreach ($data['lists'] as $list) {
            $lists[] = [
                'id' => $list['id'],
                'name' => $list['name']
            ];
        }
        return $lists;
    }

    public function subscribe(string $email, string $name = '', string $domain = '', string $phone = ''): bool
    {
        if (empty($email) || empty($this->apiKey) || empty($this->dataCenter) || empty($this->listId)) {
            return false;
        }
        [$first_name, $last_name] = $this->splitName($name);
        $url = 'https://' . $this->dataCenter . '.api.mailchimp.com/3.0/lists/' . $this->listId . '/members';
        $mergeFields = [
            'FNAME' => $first_name,
            'LNAME' => $last_name
        ];
        if (!empty($phone)) {
            $mergeFields['PHONE'] = $phone;
        }
        $body = [
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => $mergeFields
        ];
        $args = [
            'headers' => [
                'Authorization' => 'apikey ' . $this->apiKey,
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'sslverify' => false,
        ];
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('[MailChimp subscribe] Error: ' . $response->get_error_message());
            return false;
        }
        $respBody = $response['body'] ?? '';
        $data = json_decode($respBody, true);
        if (isset($data['id'])) {
            return true;
        }
        return false;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->dataCenter);
    }

    private function splitName($full_name): array
    {
        $parts = explode(' ', trim($full_name));
        $first = array_shift($parts);
        $last = implode(' ', $parts);
        return [$first, $last];
    }
} 