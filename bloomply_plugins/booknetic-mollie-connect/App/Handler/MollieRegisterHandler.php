<?php

namespace BookneticAddon\Bloompy\Mollie\Handler;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticAddon\Bloompy\Mollie\Integration\MollieConnect;
use BookneticSaaS\Models\Tenant;

class MollieRegisterHandler extends MollieConnect
{
    private static $view = 'connect_register_settings.php';

    public function handleTenant($tenantInf)
    {
        /*$tenantOrgId = Tenant::getData($tenantInf->id, 'mollie_connect_org_id');

        $organization = MollieConnectHelper::getInstance()->retrieveAccount($tenantInf->id);

        if (empty($organization) || !$organization->status == 'verified')
        {
            if (Tenant::getData($tenantInf->id, 'mollie_connect_verified') == 1)
                Tenant::setData($tenantInf->id, 'mollie_connect_verified', 0);

            MollieConnectHelper::setView(self::$view, [
                'status'     => false,
                'reason'     => '', // Mollie doesn't have disabled_reason like Stripe
                'requirments' => [], // Mollie API doesn't give verification requirements through Organization directly
            ]);

            return false;
        }*/

        return parent::handleTenant($tenantInf);
    }
}