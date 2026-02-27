<?php
namespace BookneticAddon\Newsletters;

use Exception;

class NewsletterManager
{
    /** @var NewsletterIntegrationInterface[] */
    private $integrations = [];

    public function addIntegration(string $name, NewsletterIntegrationInterface $integration): void
    {
        $this->integrations[$name] = $integration;
    }

    public function getIntegration(string $name): ?NewsletterIntegrationInterface
    {
        return $this->integrations[$name] ?? null;
    }

    public function subscribe(string $integration, string $email, string $name = '', string $domain = ''): bool
    {
        $int = $this->getIntegration($integration);
        if ($int) {
            return $int->subscribe($email, $name, $domain);
        }
        return false;
    }

    /**
     * Get a provider instance by slug (e.g., 'mailblue', 'mailchimp').
     */
    public static function getProvider($slug)
    {
        switch ($slug) {
            case 'mailblue':
                return new \BookneticAddon\Newsletters\Providers\MailBlueIntegration();
            case 'mailchimp':
                return new \BookneticAddon\Newsletters\Providers\MailChimpIntegration();
        }
        return null;
    }

    /**
     * Get lists for a provider by slug, only if the provider is configured.
     */
    public static function getLists($slug)
    {
        $provider = self::getProvider($slug);
        if ($provider && $provider->isConfigured()) {
            return $provider->getLists();
        }
        return [];
    }

    /**
     * Set newsletter list for a specific service and provider.
     * @param int $serviceId
     * @param string $providerSlug (e.g., 'mailblue', 'mailchimp')
     * @param string $listId
     * @return bool
     */
    public static function setServiceList($serviceId, $providerSlug, $listId)
    {
        $providerKeys = [
            'mailblue' => 'mailblue_list',
            'mailchimp' => 'mailchimp_list',
        ];
        
        if (!isset($providerKeys[$providerSlug])) {
            return false;
        }
        
        try {
            \BookneticApp\Models\Service::setData($serviceId, $providerKeys[$providerSlug], $listId);
            return true;
        } catch (Exception $e) {
            error_log('NewsletterManager: Failed to set service list for service ' . $serviceId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get newsletter list for a specific service and provider.
     * @param int $serviceId
     * @param string $providerSlug (e.g., 'mailblue', 'mailchimp')
     * @return string
     */
    public static function getServiceList($serviceId, $providerSlug)
    {
        $providerKeys = [
            'mailblue' => 'mailblue_list',
            'mailchimp' => 'mailchimp_list',
        ];
        
        if (!isset($providerKeys[$providerSlug])) {
            return '';
        }
        
        try {
            return \BookneticApp\Models\Service::getData($serviceId, $providerKeys[$providerSlug], '');
        } catch (Exception $e) {
            error_log('NewsletterManager: Failed to get service list for service ' . $serviceId . ': ' . $e->getMessage());
            return '';
        }
    }


    /**
     * Subscribe a customer to all enabled providers for a given service.
     * @param int $serviceId
     * @param string $email
     * @param string $name
     */
    public static function subscribeAllForService($serviceId, $email, $name, $phone = '')
    {
        $providers = [
            [
                'slug' => 'mailblue',
                'service_data_key' => 'mailblue_list',
                'default_option_key' => 'newsletter_mailblue_default',
            ],
            [
                'slug' => 'mailchimp',
                'service_data_key' => 'mailchimp_list',
                'default_option_key' => 'newsletter_mailchimp_default',
            ],
            // Add more providers here as needed
        ];

        foreach ($providers as $providerInfo) {
            $provider = self::getProvider($providerInfo['slug']);
            if (!$provider || !$provider->isConfigured()) {
                continue;
            }

            // Get service-specific list from service data
            $listId = '';
            try {
                $listId = \BookneticApp\Models\Service::getData($serviceId, $providerInfo['service_data_key'], '');
            } catch (Exception $e) {
                // Log the error for debugging
                error_log('NewsletterManager: Failed to get service data for service ' . $serviceId . ': ' . $e->getMessage());
                $listId = '';
            }

            // If no service-specific list, use default list for current tenant
            if (empty($listId)) {
                $listId = \BookneticApp\Providers\Helpers\Helper::getOption($providerInfo['default_option_key'], '');
            }

            if ($listId) {
                $provider->configure(['list_id' => $listId]);
                $provider->subscribe($email, $name, '', $phone);
            }
        }
    }
} 