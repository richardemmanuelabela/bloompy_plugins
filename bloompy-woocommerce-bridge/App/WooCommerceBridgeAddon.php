<?php
namespace BloompyAddon\WooCommerceBridge;

use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\MenuUI;

function bkntc__( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, 'bloompy-woocommerce-bridge' );
}

class WooCommerceBridgeAddon extends AddonLoader
{
    private HookRegistrar $hookRegistrar;

    public function init()
    {
        // Define global thank you URL constant once
        if (!defined('BLOOMPY_THANKYOU_URL')) {
            define('BLOOMPY_THANKYOU_URL', home_url('/thank-you-for-your-purchase/'));
        }

        // Load text domain for translations
        load_plugin_textdomain('bloompy-woocommerce-bridge', false, dirname(plugin_basename(__FILE__)) . '/languages');
        Capabilities::register('bloompy_woocommerce_bridge', bkntc__('WooCommerce Bridge'));

        // Register all hooks for the plugin's services
        $this->hookRegistrar = new HookRegistrar();
        $this->hookRegistrar->registerHooks();

        // Clean up old plan-upgrades page if it exists
        add_action('init', [$this, 'cleanupOldPlanUpgradesPage']);

        add_filter('woocommerce_payment_successful_result', [$this, 'forcePaymentSuccessRedirect'], 10, 2);
        add_filter('woocommerce_checkout_order_received_url', [$this, 'overrideOrderReceivedUrl'], 10, 2);
        add_action('woocommerce_payment_complete', [$this, 'maybeAutoCompletePaidVirtualOrSubscriptionOrder'], 10, 1);
    }

    public function initBackend()
    {
        // Add Plan Upgrades menu item
        if (Capabilities::userCan('bloompy_woocommerce_bridge')) {
            \BookneticApp\Providers\Core\Route::get('bloompy_plan_upgrades', new \BloompyAddon\WooCommerceBridge\Backend\PlanUpgradesController());
            MenuUI::get('bloompy_plan_upgrades')
                ->setTitle(bkntc__('Plan Upgrades'))
                ->setIcon('fa fa-arrow-up')
                ->setPriority(960);
        }
    }

    /**
     * Clean up the old plan-upgrades page that was created automatically
     */
    public function cleanupOldPlanUpgradesPage(): void
    {
        $page = get_page_by_path('plan-upgrades');
        if ($page) {
            wp_delete_post($page->ID, true);
        }
    }

    /**
     * Ensure gateways that return immediately use our custom thank you page when order is paid
     */
    public function forcePaymentSuccessRedirect(array $result, int $order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof \WC_Order) {
            return $result;
        }

        // Only redirect when order is actually paid to avoid premature redirects
        if ($order->has_status(['processing', 'completed'])) {
            $result['redirect'] = add_query_arg([
                'order' => $order->get_id(),
                'key'   => $order->get_order_key(),
            ], BLOOMPY_THANKYOU_URL);
        }

        return $result;
    }

    /**
     * Strong fallback: when WC renders order-received, send user to our page if order is paid
     */
    public function overrideOrderReceivedUrl(string $url, $order): string
    {
        if ($order instanceof \WC_Order && $order->has_status(['processing', 'completed'])) {
            return add_query_arg([
                'order' => $order->get_id(),
                'key'   => $order->get_order_key(),
            ], BLOOMPY_THANKYOU_URL);
        }
        return $url;
    }

    /**
     * If the order is paid and contains only virtual or subscription items, mark it completed.
     */
    public function maybeAutoCompletePaidVirtualOrSubscriptionOrder(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof \WC_Order) {
            return;
        }

        if (!$order->is_paid()) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                return;
            }
            $type = $product->get_type();
            $isVirtual = $product->is_virtual();
            $isSubscription = in_array($type, ['subscription', 'variable-subscription', 'subscription_variation'], true);
            if (!$isVirtual && !$isSubscription) {
                return;
            }
        }

        if (!$order->has_status(['completed'])) {
            $order->update_status('completed', 'Auto-completed: paid and only virtual/subscription items.');
        }
    }
}

// Register deactivation hook to clean up
register_deactivation_hook(__FILE__, function() {
    $page = get_page_by_path('plan-upgrades');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}); 