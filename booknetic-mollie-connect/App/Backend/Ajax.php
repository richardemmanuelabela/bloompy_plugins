<?php

namespace BookneticAddon\Bloompy\Mollie\Backend;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private $mollieConnect;

    public function __construct()
    {
        $this->mollieConnect = MollieConnectHelper::getInstance();
    }

    public function generate_register_link()
    {
        $tenantInf = MollieConnectHelper::getTenantInf();
        $authUrl = MollieConnectHelper::getInstance()->getAuthorizationUrl();

        return $this->response(true, ['url' => $authUrl]);
    }

    public function generate_verify_link()
    {
        $tenantInf = MollieConnectHelper::getTenantInf();
        $verifyUrl = MollieConnectHelper::getInstance()->getAuthorizationUrl(); // or whatever verify URL Mollie uses

        return $this->response(true, ['url' => $verifyUrl]);
    }

    public function generate_login_link()
    {

    }

    public function revoke_token()
    {
        try {
            $tenantInf = MollieConnectHelper::getTenantInf();
            MollieConnectHelper::getInstance()->revokeTokens($tenantInf->id);

            return $this->response(true, 'Mollie connection has been revoked.');
        } catch (\Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
}