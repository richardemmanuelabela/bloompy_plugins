<?php

namespace BookneticAddon\Bloompy\Mollie\Integration;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class MollieConnect
{
    private static $view = 'connect_settings.php';
    private $next;
    private $chain;


    public function nextChain( $next )
    {
        $this->next = $next;

        return $next;
    }

    public function handleTenant( $tenantInf )
    {
        if ( !$this->next )
        {
            return true;
        }

        return $this->next->handleTenant( $tenantInf );
    }

    public function setChain($chain)
    {
        $this->chain = $chain;
    }

    public function checkTenant($tenantInf)
    {
        $result = $this->chain->handleTenant($tenantInf);

        if ($result === true)
        {
            $verifiedTenant = Tenant::getData($tenantInf->id, 'mollie_connect_verified');

            if ($verifiedTenant != 1) {
                Tenant::setData($tenantInf->id, 'mollie_connect_verified', 1);
            }

            $params = [
                'pricing' => MollieConnectHelper::getFeeData(),
                'tenantSettings' => MollieConnectHelper::getTenantSettings($tenantInf->id),
                'debug' => Tenant::getData($tenantInf->id, 'mollie_connect_refresh_token'),
            ];

            MollieConnectHelper::setView(self::$view, $params);
        }

        return $result;
    }
}