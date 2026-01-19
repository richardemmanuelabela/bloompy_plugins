<?php

namespace BookneticAddon\Bloompy\Mollie\Helpers;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper as RegularHelper;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;
use Mollie\Api\MollieApiClient;
use Mollie\OAuth2\Client\Provider\Mollie;

class MollieConnectHelper
{

    private static $mollieConnectedAccounts;
    public static $view;
    public static $emptyView = 'connect_settings_error.php';
    public static $params = [];
    private $mollie;
    private $provider;

    private $stateKey;
    private $hasErrors = false;
    private static $instance;

    public function __construct()
    {
        try
        {
            $clientId     = Helper::getOption('mollie_connect_client_id', '');
            $clientSecret = Helper::getOption('mollie_connect_client_secret', '');
            $redirectUri = $this->getRedirectUri();

            // Validate required parameters
            if (empty($clientId) || empty($clientSecret)) {
                error_log('[MOLLIE ERROR] Missing client ID or client secret for Mollie Connect');
                $this->hasErrors = true;
                return;
            }

            $this->provider = new Mollie([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri'  => $redirectUri,
            ]);
        }
        catch ( \Exception $e )
        {
            error_log('[MOLLIE ERROR] Failed to initialize Mollie provider: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    public function getAuthorizationUrl() {
        if ($this->hasErrors || $this->provider === null) {
            error_log('[MOLLIE ERROR] Cannot get authorization URL: provider not initialized');
            throw new \Exception('Mollie Connect is not properly configured. Please check your client ID and client secret.');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            $authorizationUrl = $this->provider->getAuthorizationUrl([
                'scope' => [
                    'organizations.read',
                    'payments.read',
                    'payments.write',
					'customers.read',
                    'customers.write',
                    'orders.write',
                    'orders.read',
                    'orders.write',
                    'profiles.read',
                    'onboarding.read',
                    'invoices.read',
					'subscriptions.read',
					'subscriptions.write',
					'mandates.read',
					'mandates.write'
                ],
            ]);

            // Store the state in the session for later validation
            $state =  $this->provider->getState();
            $_SESSION['oauth2state'] = $state;

            Helper::setOption('mollie_connect_oauth_state', $state, Permission::tenantId());
			error_log("authorizationUrl: ".$authorizationUrl);
            return $authorizationUrl;
        } catch (\Exception $e) {
            error_log('[MOLLIE ERROR] Failed to get authorization URL: ' . $e->getMessage());
            throw new \Exception('Failed to generate Mollie authorization URL: ' . $e->getMessage());
        }
    }

    public function createAccount($tenantInf)
    {
        if ($this->hasErrors || $this->provider === null) {
            error_log('[MOLLIE ERROR] Cannot create account: provider not initialized');
            throw new \Exception('Mollie Connect is not properly configured. Please check your client ID and client secret.');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expectedState = $_SESSION['oauth2state'] ?? Helper::getOption($this->stateKey, Permission::tenantId());

        if ($_GET['state'] !== $expectedState) {
            unset($_SESSION['oauth2state']);
            Helper::deleteOption('mollie_connect_oauth_state', Permission::tenantId());
            throw new \Exception('Invalid state');
        }

        if ($_GET['state'] !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            throw new \Exception('Invalid state');
        }

        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);
        } catch (\Exception $e) {
            error_log('[MOLLIE ERROR] Failed to get access token: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Mollie: ' . $e->getMessage());
        }

        error_log("[MOLLIE DEBUG] OAuth token granted for tenant {$tenantInf->id}. Expires at: {$token->getExpires()}");

        $mollie = new MollieApiClient();
        $mollie->setAccessToken($token->getToken());

        $profile = $mollie->organizations->current();

        // Store credentials
        $apiClient = new MollieApiClient();
        $apiClient->setAccessToken($token->getToken());

        $profiles = $apiClient->profiles->page();
        $profileId = isset($profiles[0]) ? $profiles[0]->id : null;

        $credentials = [
            'access_token'  => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires'       => $token->getExpires(),
            'profile_id'    => $profileId,
            'org_id'        => $profile->id ?? null,
            'verified'      => 1,
        ];

        $this->saveCredentials($tenantInf->id, $credentials);

        return true;
    }

    private function getRedirectUri() {
        // Allow override via WP_ENV constant or $_ENV
        $env = defined('WP_ENV') ? WP_ENV : ($_ENV['WP_ENV'] ?? 'development');

        $fallback_url = site_url() . '/?bkntc_mollie_connect=callback';

        // If not in production, use value from wp_options (e.g. set by ngrok script)
        if ($env !== 'production') {
            return Helper::getOption('mollie_connect_redirect_uri', $fallback_url);
        }

        // Production fallback
        return $fallback_url;
    }

    public function retrieveAccount($accountId)
    {
        $mollie = new MollieApiClient();
        $mollie->setAccessToken($this->getTenantAccessToken($accountId));

        return $mollie->organizations->current();
    }

    public function getProfileId(int $tenantId): ?string
    {
        $credentials = $this->getCredentials($tenantId);

        if (!isset($credentials['profile_id'])) {
            // Lazy-fetch and store the profile ID
            $apiClient = $this->getMollieApiClient($tenantId);
            $profiles = $apiClient->profiles->page();

            if (isset($profiles) && count($profiles) > 0) {
                $profileId = $profiles[0]->id;

                // Persist the profile_id back into your DB or options
                $credentials['profile_id'] = $profileId;
                $this->saveCredentials($tenantId, $credentials);
            } else {
                return null;
            }
        }

        return $credentials['profile_id'] ?? null;
    }

    private function getTenantAccessToken($tenantId)
    {
        return Tenant::getData($tenantId, 'mollie_connect_access_token');
    }


    public function checkApiStatus()
    {
        if ($this->hasErrors) {
            return false;
        }

        try {
            $tenantId = \BookneticApp\Providers\Core\Permission::tenantId();

            $token = Tenant::getData($tenantId, 'mollie_connect_access_token');
            if (empty($token)) {
                error_log('[MOLLIE DEBUG] No access token yet for tenant ' . $tenantId . '. Skipping API check.');
                return false;
            }

            $client = $this->getMollieApiClient($tenantId);
            $org = $client->organizations->current();

            error_log('[MOLLIE] API status OK for tenant ' . $tenantId);
            return !empty($org);
        } catch (\Throwable $e) {
            error_log('[MOLLIE ERROR] checkApiStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllMollieAccounts()
    {

    }

    public static function setView( $view, $params = [] )
    {
        self::$view = $view;

        if ( ! empty( $params ) )
        {
            self::$params = $params;
        }

    }

    public static function getView()
    {
        return empty(self::$view) ? self::$emptyView : self::$view;
    }

    public static function getParams()
    {
        return self::$params;
    }

    public static function getTenantInf()
    {
        $tenantInf = Permission::tenantInf();

        if ( !empty($tenantInf) )
        {
            return $tenantInf;
        }
        else
        {
            throw new \Exception('Something wen\'t wrong');
        }

    }

    public static function getTenantSettings($tenantId)
    {
        return [
            'testmode' => Helper::getOption( 'mollie_connect_test_mode', false, $tenantId) == 'yes'
        ];
    }

    public static function getFeeData($totalPrice = null)
    {
        //since it's a pain to add a custom field for each plan, just use the exisitng bookmark field (ribbon_text) to store mollie platform fee
        $tenantInf = Permission::tenantInf();
        if ( ! $tenantInf ) {
            return [
                'fee' => 0,
                'type' => 'price',
                'raw' => 0,
                'display' => Helper::currencySymbol()
            ];
        }

        if (Date::epoch( Date::dateSQL() ) > Date::epoch( $tenantInf->expires_in ) && ! Tenant::haveEnoughBalanceToPay() ) {
            $plan = Plan::where( 'expire_plan', 1 )->fetch();
        } else {
            $plan = $tenantInf->plan()->fetch();
        }

        $platformFee = $plan['ribbon_text'];

        // Only allow clean numbers or numbers with one '%' at the end
        if (preg_match('/^(\d+|\.\d+|\d+\.\d+)%?$/', $platformFee)) {
            if (str_ends_with($platformFee, '%')) {
                $feeType = 'percentage';
                $rawFee = floatval(rtrim($platformFee, '%'));
            } else {
                $feeType = 'price';
                $rawFee = floatval($platformFee);
            }
        } else {
            // fallback to plugin options if ribbon text is invalid
            $defaultFee = Helper::getOption('mollie_connect_platform_fee', '0');
            $feeType = Helper::getOption('mollie_connect_fee_type', '0') == 'percent' ? 'percentage' : 'price';
            $rawFee = $defaultFee;
        }

        //total price is only supplied when using this method in setting the fee of a payment
        if ($feeType === 'price' || $totalPrice == null) {
            $fee = Math::floor($rawFee);
        } else {
            $fee = Math::floor(Math::div(Math::mul($rawFee, $totalPrice), 100));
        }

        $display = RegularHelper::numberFormat($rawFee) . '%';
        if ($feeType == 'price') {
            $display =  RegularHelper::price($rawFee);
        }

        return [
            'fee'     => $fee,
            'type'    => $feeType,
            'raw'     => $rawFee,
            'display' => $display,
        ];
    }

    public static function canUseMollieConnect()
    {
        $tenantId = Permission::tenantId();

        if ( empty( $tenantId ) )
            return false;

        if ( Tenant::getData( $tenantId, 'mollie_connect_verified') == 1 )
        {
            return true;
        }

        return false;
    }

    public static function getInstance()
    {
        if( is_null(self::$instance ))
        {
            self::$instance = new MollieConnectHelper();
        }
        return self::$instance;
    }

    public function isProperlyConfigured()
    {
        return !$this->hasErrors && $this->provider !== null;
    }

    public function getMollieApiClient($tenantId)
    {
        try {
            $token = Tenant::getData($tenantId, 'mollie_connect_access_token');
            $expires = Tenant::getData($tenantId, 'mollie_connect_expires');

            if (empty($token)) {
                throw new \Exception('Access token is missing.');
            }

            if (!empty($expires) && $expires < time()) {
                $refreshToken = Tenant::getData($tenantId, 'mollie_connect_refresh_token');

                if (empty($refreshToken)) {
                    throw new \Exception('Refresh token is missing. Cannot refresh Mollie access token.');
                }

                error_log("[MOLLIE] Token expired. Attempting refresh for tenant ID: {$tenantId}");

                // Check if provider is available for token refresh
                if ($this->hasErrors || $this->provider === null) {
                    error_log('[MOLLIE ERROR] Cannot refresh token: provider not initialized');
                    throw new \Exception('Mollie Connect is not properly configured. Please check your client ID and client secret.');
                }

                try {
                    $newToken = $this->provider->getAccessToken('refresh_token', [
                        'refresh_token' => $refreshToken
                    ]);

                    $credentials = [
                        'access_token'  => $newToken->getToken(),
                        'refresh_token' => $newToken->getRefreshToken(),
                        'expires'       => $newToken->getExpires(),
                    ];

                    $this->saveCredentials($tenantId, $credentials);

                    $token = $newToken->getToken();
                } catch (\Throwable $e) {
                    error_log("[MOLLIE ERROR] getMollieApiClient for tenant {$tenantId} failed: " . $e->getMessage());

                    // Clean credentials to force re-auth on next interaction
                    $this->revokeTokens($tenantId);

                    throw new \Exception('Your Mollie connection expired. Please reconnect your account.');
                }
            }

            $mollie = new MollieApiClient();
            $mollie->setAccessToken($token);

            return $mollie;
        } catch (\Throwable $e) {
            error_log('[MOLLIE ERROR] getMollieApiClient for tenant ' . $tenantId . ' failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyAccount($tenantId)
    {
        $accessToken = Tenant::getData($tenantId, 'mollie_connect_access_token');

        if (empty($accessToken)) {
            throw new \Exception('No access token found.');
        }

        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($accessToken);

        try {
            $org = $mollie->organizations->current();

            if (!empty($org) && !empty($org->id)) {
                return $org; // success, return org object
            }

            throw new \Exception('Organization not found.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to verify Mollie account: ' . $e->getMessage());
        }
    }

    public function revokeTokens($tenantId)
    {
        $accessToken  = Tenant::getData($tenantId, 'mollie_connect_access_token');
        $refreshToken = Tenant::getData($tenantId, 'mollie_connect_refresh_token');

        $provider = new \Mollie\OAuth2\Client\Provider\Mollie([
            'clientId'     => Helper::getOption('mollie_connect_client_id', '', false),
            'clientSecret' => Helper::getOption('mollie_connect_client_secret', '', false),
            'redirectUri'  => site_url('/?bkntc_mollie_connect=callback'),
        ]);

        if ($accessToken) {
            try {
                $provider->revokeAccessToken($accessToken);
            } catch (\Exception $e) {
                error_log('[Mollie Revoke] Access token error: ' . $e->getMessage());
            }
        }

        if ($refreshToken) {
            try {
                $provider->revokeRefreshToken($refreshToken);
            } catch (\Exception $e) {
                error_log('[Mollie Revoke] Refresh token error: ' . $e->getMessage());
            }
        }

        // Clean up tenant storage
        Tenant::deleteData($tenantId, 'mollie_connect_access_token');
        Tenant::deleteData($tenantId, 'mollie_connect_refresh_token');
        Tenant::deleteData($tenantId, 'mollie_connect_expires');
        Tenant::deleteData($tenantId, 'mollie_connect_org_id');
        Tenant::deleteData($tenantId, 'mollie_connect_verified');

        return true;
    }

    private function getCredentials(int $tenantId): array
    {
        return [
            'access_token'  => Tenant::getData($tenantId, 'mollie_connect_access_token'),
            'refresh_token' => Tenant::getData($tenantId, 'mollie_connect_refresh_token'),
            'expires'       => Tenant::getData($tenantId, 'mollie_connect_expires'),
            'org_id'        => Tenant::getData($tenantId, 'mollie_connect_org_id'),
            'profile_id'    => Tenant::getData($tenantId, 'mollie_connect_profile_id'),
            'verified'      => Tenant::getData($tenantId, 'mollie_connect_verified'),
        ];
    }

    private function saveCredentials(int $tenantId, array $credentials): void
    {
        if (isset($credentials['access_token'])) {
            Tenant::setData($tenantId, 'mollie_connect_access_token', $credentials['access_token']);
        }

        if (isset($credentials['refresh_token'])) {
            Tenant::setData($tenantId, 'mollie_connect_refresh_token', $credentials['refresh_token']);
        }

        if (isset($credentials['expires'])) {
            Tenant::setData($tenantId, 'mollie_connect_expires', $credentials['expires']);
        }

        if (isset($credentials['org_id'])) {
            Tenant::setData($tenantId, 'mollie_connect_org_id', $credentials['org_id']);
        }

        if (isset($credentials['profile_id'])) {
            Tenant::setData($tenantId, 'mollie_connect_profile_id', $credentials['profile_id']);
        }

        if (isset($credentials['verified'])) {
            Tenant::setData($tenantId, 'mollie_connect_verified', $credentials['verified']);
        }

        // Automatically enable mollie_split payment method when credentials are successfully saved
        if (isset($credentials['access_token']) && !empty($credentials['access_token'])) {
            Helper::setOption('mollie_split_payment_enabled', 'on', $tenantId);
        }
    }

    public static function getOrderByPaymentId(int $tenantId, string $paymentId)
    {
        try {
            $orderId = Tenant::getData($tenantId, 'mollie_order_map_' . $paymentId);
            $mode = Tenant::getData($tenantId, 'mollie_order_mode_' . $paymentId);

            if (empty($orderId)) {
                throw new \Exception("Order ID mapping not found for payment ID: $paymentId");
            }

            $client = self::getInstance()->getMollieApiClient($tenantId);

            // Explicitly set testmode flag if stored
            $params = [];
            if ($mode === 'test') {
                $params['testmode'] = true;
            }

            return $client->orders->get($orderId, $params);

        } catch (\Throwable $e) {
            error_log('[MOLLIE CALLBACK] Failed to confirm payment: ' . $e->getMessage());
            return null;
        }
    }
}