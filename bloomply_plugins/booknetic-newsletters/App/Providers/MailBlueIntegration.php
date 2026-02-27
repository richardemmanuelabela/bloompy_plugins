<?php
namespace BookneticAddon\Newsletters\Providers;

use BookneticAddon\Newsletters\NewsletterIntegrationInterface;
use BookneticApp\Providers\Helpers\Helper;

class MailBlueIntegration implements NewsletterIntegrationInterface
{
    private $apiKey;
    private $apiUrl;
    private $listId;
    private $tags;

    public function __construct()
    {
        $this->apiKey = Helper::getOption('newsletter_mailblue_api_key', '');
        $this->apiUrl = Helper::getOption('newsletter_mailblue_api_url', '');
    }

    public function configure(array $settings): void
    {
        $this->listId = $settings['list_id'] ?? '';
        $this->tags = $settings['tags'] ?? '';
    }

    public function subscribe(string $email, string $name = '', string $domain = '', string $phone = ''): bool
    {
        if (empty($email) || empty($this->apiKey) || empty($this->apiUrl) || empty($this->listId)) {
            return false;
        }
        [$first_name, $last_name] = $this->splitName($name);
        $url = rtrim($this->apiUrl, '/') . '/api/3/contacts';
        $contact = [
            'email' => $email,
            'firstName' => $first_name,
            'lastName' => $last_name,
            'fieldValues' => [],
        ];
        if (!empty($phone)) {
            $contact['phone'] = $phone;
        }
        $body = [
            'contact' => $contact
        ];
        $args = [
            'headers' => [
                'Api-Token' => $this->apiKey,
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'sslverify' => false,
        ];
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('[Mailblue subscribe] Error: ' . $response->get_error_message());
            return false;
        }
        $respBody = $response['body'] ?? '';
        $data = json_decode($respBody, true);
        $contactId = $data['contact']['id'] ?? null;
        if (!$contactId) {
            error_log('[Mailblue subscribe] No contact ID returned.');
            return false;
        }
        // Now subscribe the contact to the list
        $listUrl = rtrim($this->apiUrl, '/') . '/api/3/contactLists';
        $listBody = [
            'contactList' => [
                'list' => $this->listId,
                'contact' => $contactId,
                'status' => 1 // 1 = subscribe
            ]
        ];
        $listArgs = [
            'headers' => [
                'Api-Token' => $this->apiKey,
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($listBody),
            'sslverify' => false,
        ];
        $listResponse = wp_remote_post($listUrl, $listArgs);
        if (is_wp_error($listResponse)) {
            error_log('[Mailblue subscribe] List Error: ' . $listResponse->get_error_message());
            return false;
        }
        $listRespBody = $listResponse['body'] ?? '';
        $listData = json_decode($listRespBody, true);
        if (isset($listData['contactList']['id'])) {
            return true;
        }
        return false;
    }

    public function getLists(): array
    {
        if (empty($this->apiKey) || empty($this->apiUrl)) {
            return [];
        }
        $url = rtrim($this->apiUrl, '/') . '/api/3/lists';
        $response = wp_remote_get($url, [
            'headers' => [
                'Api-Token' => $this->apiKey,
                'accept' => 'application/json',
            ],
            'sslverify' => false,
        ]);
        if (is_wp_error($response)) {
            error_log('[Mailblue getLists] Error: ' . $response->get_error_message());
            return [];
        }
        $body = $response['body'] ?? '';
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['lists'])) {
            error_log('[Mailblue getLists] Invalid response: ' . $body);
            return [];
        }
        return $data['lists'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiUrl);
    }

    private function splitName($full_name): array
    {
        $parts = explode(' ', trim($full_name));
        $first = array_shift($parts);
        $last = implode(' ', $parts);
        return [$first, $last];
    }
} 