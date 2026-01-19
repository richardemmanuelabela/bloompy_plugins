<?php

namespace BookneticAddon\Bloompy\Mollie;

use BookneticAddon\Bloompy\Mollie\Backend\Ajax;
use BookneticAddon\Bloompy\Mollie\Handler\MollieRegisterHandler;
use BookneticAddon\Bloompy\Mollie\Handler\MollieSetupHandler;
use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticAddon\Bloompy\Mollie\Integration\MollieConnect;
use BookneticAddon\Bloompy\Mollie\Listener;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\UI\TabUI;
use BookneticSaaS\Providers\Core\Permission as SaaSPermission;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticSaaS\Providers\UI\TabUI as SaaSTabUI;

function bkntc__($text, $params = [], $esc = true)
{
    return \bkntc__($text, $params, $esc, MollieAddon::getAddonTextDomain());
}

class MollieAddon extends AddonLoader
{
    public function init()
    {
        Capabilities::registerLimit( 'plan_mollie_connect_platform_fee', bkntc__('Platform Fee - Mollie Connect') );
        Capabilities::registerLimit( 'plan_mollie_connect_platform_fee_type', bkntc__('Use fixed amount? Enter 1 for yes.') );
        Capabilities::registerTenantCapability('mollie_connect', bkntc__('Mollie Connect integration'));

        //delete local payment method
        TabUI::get('payment_gateways_settings')
            ->removeSubItem('local');


        if (!Capabilities::tenantCan('mollie_connect'))
            return;

        Capabilities::register('mollie_connect_settings', bkntc__('Mollie Connect settings'), 'settings');

        Mollie::load();
    }

    public function initBackend()
    {
        Route::post('mollie_connect_settings', Ajax::class, ['generate_register_link', 'generate_verify_link', 'generate_login_link', 'revoke_token']);

        if (!Capabilities::tenantCan('mollie_connect'))
            return;

        add_action('bkntc_before_request_settings_payment_gateways_settings', function () {
            $chain = new MollieSetupHandler();
            $chain->nextChain(new MollieRegisterHandler());

            $mollieConnect = new MollieConnect();
            $mollieConnect->setChain($chain);

            $mollieConnect->checkTenant(MollieConnectHelper::getTenantInf());

            TabUI::get('payment_gateways_settings')
                ->item('mollie_split')
                ->setTitle('Mollie Connect')
                ->addView(__DIR__ . '/Backend/view/connect/' . MollieConnectHelper::getView(), MollieConnectHelper::getParams());
        });

        add_action('bkntc_enqueue_assets', [self::class, 'enqueueAssets'], 10, 2);
        add_filter('bkntc_after_request_settings_save_payment_gateways_settings', [Listener::class, 'saveSettings']);
    }

    public static function enqueueAssets($module, $action)
    {
        if ($module == 'settings' && $action == 'payment_gateways_settings') {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/mollie-settings.js') . '"></script>';
        }
    }

    public function initFrontend()
    {
        if ( Capabilities::tenantCan('mollie_connect') )
        {
            Listener::checkMollieConnectCallback();
        }

        // doit deprecated... 1 ay sonra else sherti silinecek. core update olmagini gozleyirik. bkntc_booking_panel_assets ile evezlenib. class_exists ile guya versiyani check edirem burda, o class yeni versiyada yaradilib. varsa demeli yeni versiyadadai Core. muveqqeti shertdi zaten, 1 ay sonra silincek.
        if( class_exists( 'BookneticApp\Providers\Core\BookingPanelService' ) )
        {
            add_filter('bkntc_booking_panel_assets', function ( $assets )
            {
                $assets[] = [
                    'id'    => 'booknetic-mollie-init',
                    'type'  => 'js',
                    'src'   => self::loadAsset('assets/frontend/js/init.js' ),
                    'deps'  => ['booknetic']
                ];

                return $assets;
            });
        }
        else
        {
            add_action('bkntc_after_booking_panel_shortcode', function ()
            {
                wp_enqueue_script( 'booknetic-mollie-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic' ] );
            });
            add_filter( 'bkntc_add_files_through_ajax', function ( $result )
            {
                $result[ 'files' ][] = [
                    'type' => 'js',
                    'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
                    'id'   => 'booknetic-mollie-init',
                ];

                return $result;
            } );
        }

        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-mollie-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic-cp' ] );
        });
    }

    public function initSaaS()
    {
        SaaSPermission::enableSplitPayments();

        Capabilities::registerTenantCapability('mollie_connect', bkntc__('Mollie Connect integration'));

        if (!Capabilities::tenantCan('mollie_connect'))
            return;

        Capabilities::register('mollie_connect_settings', bkntc__('Mollie Connect settings'), 'settings');

        add_action( 'bkntcsaas_tenant_created', [ Listener::class, 'disableLocalpayment' ] );

        MollieConnectGateway::load();
    }

    public function initSaaSBackend()
    {
        SaaSRoute::post('mollie_connect_settings', Ajax::class, ['connected_tenants_saas', 'delete_connected_tenant_account']);

        SaaSTabUI::get('payment_split_payments_settings')
            ->item('mollie_split')
            ->setTitle('Mollie Connect')
            ->addView(__DIR__ . '/Backend/view/modal/connect_settings_saas.php');

        add_filter('bkntcsaas_after_request_settings_save_payment_split_payments_settings', [Listener::class, 'saveSplitSettings']);
    }

    public function initSaaSFrontend()
    {
        if (!Capabilities::tenantCan('mollie_connect'))
            return;

        Listener::checkMollieConnectSetupCallback();
        $bookingPanelService = class_exists('BookneticApp\Providers\Core\BookingPanelService');

        if ($bookingPanelService) {
            add_filter('bkntc_booking_panel_assets', function ($assets) {
                $assets[] = [
                    'id'    => 'booknetic-mollie-init-split',
                    'type'  => 'js',
                    'src'   => self::loadAsset('assets/frontend/js/init-mollie-connect.js'),
                    'deps'  => ['booknetic']
                ];

                return $assets;
            });
        }

        // Fallback: ensure script is enqueued via wp_footer if filter didn't fire
        add_action('wp_footer', function () {
            if (!is_page()) {
                return;
            }

            global $post;

            if (
                isset($post->post_content)
                && strpos($post->post_content, '[booknetic]') !== false
                && Capabilities::tenantCan('mollie_connect')
            ) {
                echo '<script src="' . MollieAddon::loadAsset('assets/frontend/js/init-mollie-connect.js') . '"></script>';
            }
        });

        // Ensure callback checker runs
        //add_action('template_redirect', [Listener::class, 'checkMollieConnectCallback']);
    }
}