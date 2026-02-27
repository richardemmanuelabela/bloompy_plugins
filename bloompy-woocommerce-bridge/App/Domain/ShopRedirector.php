<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use WC_Order;

/**
 * Handles shop and post-purchase redirects.
 */
class ShopRedirector
{
    public function registerHooks(): void
    {
        add_action('template_redirect', [$this, 'redirectAfterPurchase']);
        add_action('template_redirect', [$this, 'redirectShopPage']);
        add_action('init', [$this, 'createThankYouPage']);
    }

    public function redirectAfterPurchase(): void
    {
        if (is_wc_endpoint_url('order-received')) {
            $order_id = absint(get_query_var('order-received'));
            if (!$order_id) {
                return;
            }

            $order = wc_get_order($order_id);
            if (!$order instanceof WC_Order || !$order->is_paid()) {
                return;
            }

            // Store order data in session for thank you page
            if (!session_id()) {
                session_start();
            }
            $_SESSION['bloompy_purchase_order_id'] = $order_id;
            $_SESSION['bloompy_purchase_user_id'] = $order->get_user_id();

            // Redirect to our custom thank you page
            wp_safe_redirect(BLOOMPY_THANKYOU_URL);
            exit;
        }
    }

    public function redirectShopPage(): void
    {
        if (is_shop()) {
            wp_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Create the thank you page if it doesn't exist
     */
    public function createThankYouPage(): void
    {
        $page_slug = 'thank-you-for-your-purchase';
        $existing_page = get_page_by_path($page_slug);
        
        if (!$existing_page) {
            wp_insert_post([
                'post_title' => 'Thank You for Your Purchase',
                'post_content' => '[bloompy_thank_you_page]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $page_slug,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ]);
        }
    }
} 