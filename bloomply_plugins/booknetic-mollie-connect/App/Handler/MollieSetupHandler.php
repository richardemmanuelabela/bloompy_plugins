<?php

namespace BookneticAddon\Bloompy\Mollie\Handler;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticAddon\Bloompy\Mollie\Integration\MollieConnect;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class MollieSetupHandler extends MollieConnect
{

    private static $view = 'connect_setup_settings.php';
    private $setupState = false;

    public function handleTenant($tenantInf)
    {
        $mollieConnect = MollieConnectHelper::getInstance();

        $accessToken = \BookneticSaaS\Models\Tenant::getData($tenantInf->id, 'mollie_connect_access_token');

        // ➤ STEP 1: If there's no token yet, tenant needs onboarding
        if (empty($accessToken)) {
            error_log("[MOLLIE DEBUG] No token found for tenant {$tenantInf->id}. Starting onboarding.");
            $this->prepareSetup($tenantInf);
            return false;
        }

        // ➤ STEP 2: Token exists, check if API is accessible
        if (! $mollieConnect->checkApiStatus()) {
            error_log("[MOLLIE ERROR] API check failed for tenant {$tenantInf->id}.");
            MollieConnectHelper::setView('connect_settings_error.php', [
                'error' => 'Could not reach Mollie API with current token.'
            ]);
            return false;
        }

        // ➤ STEP 3: Check if stored orgId exists in Mollie (revalidate)
        $orgId = Tenant::getData($tenantInf->id, 'mollie_connect_org_id');

        if (empty($orgId)) {
            try {
                error_log("[MOLLIE DEBUG] Org ID missing for tenant {$tenantInf->id}. Starting setup.");
                $this->prepareSetup($tenantInf);
                return false;
            } catch (\Exception $e) {
                MollieConnectHelper::setView('connect_settings_error.php', ['error' => $e->getMessage()]);
                return false;
            }
        }

        // ➤ STEP 4: Try to validate/retrieve the account
        try {
            $account = $mollieConnect->retrieveAccount($tenantInf->id);

            if (!empty($account)) {
                $this->setupState = true;

                if (Tenant::getData($tenantInf->id, 'mollie_connect_verified') != 1) {
                    Tenant::setData($tenantInf->id, 'mollie_connect_verified', 1);
                }
            } else {
                error_log("[MOLLIE DEBUG] No account found for tenant {$tenantInf->id}. Restarting setup.");
                $this->prepareSetup($tenantInf);
                $this->setupState = false;
            }
        } catch (\Exception $e) {
            error_log('[MOLLIE ERROR] retrieveAccount failed: ' . $e->getMessage());
            $this->prepareSetup($tenantInf);
            $this->setupState = false;
        }

        if (!$this->setupState) {
            return false;
        }

        return parent::handleTenant($tenantInf);
    }

    private function prepareSetup($tenantInf)
    {
        error_log('[MOLLIE DEBUG] Showing connect setup page for tenant ID: ' . $tenantInf->id);
        if (Tenant::getData($tenantInf->id, 'mollie_connect_verified') == 1)
            Tenant::setData($tenantInf->id, 'mollie_connect_verified', 0);

        $params = [
            'api'     => Helper::getOption('mollie_connect_api', ''),
            'pricing' => MollieConnectHelper::getFeeData(),
            'terms' =>  Helper::getOption('mollie_connect_terms_page', ''),
            'authorization_url' => MollieConnectHelper::getInstance()->getAuthorizationUrl(),
            'tenantSettings' => MollieConnectHelper::getTenantSettings($tenantInf->id)
        ];

        MollieConnectHelper::setView(self::$view, $params);
        error_log('[MOLLIE DEBUG] Using final view: ' . MollieConnectHelper::getView());
    }
}