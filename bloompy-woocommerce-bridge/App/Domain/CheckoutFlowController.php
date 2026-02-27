<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use WC_Product;

/**
 * Handles WooCommerce cart and checkout customizations and redirects for SaaS plans.
 */
class CheckoutFlowController
{
    public function registerHooks(): void
    {
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'customButtonText'], 10, 2);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirectToCheckout']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'forceSingleItemCart'], 10, 3);
        add_action('template_redirect', [$this, 'redirectCartPage']);
        add_filter('wc_add_to_cart_message_html', '__return_empty_string');
    }

    /**
     * Customize button text for subscription products
     */
    public function customButtonText($text, $product): string
    {
        if ($product instanceof WC_Product &&
            ($product->is_type('subscription') || $product->is_type('variable-subscription'))) {
            return __('Subscribe Now', 'woocommerce');
        }

        return $text;
    }

    /**
     * Redirect to checkout after add to cart
     */
    public function redirectToCheckout($url): string
    {
        if (isset($_REQUEST['add-to-cart'])) {
            return wc_get_checkout_url();
        }

        return $url;
    }

    /**
     * Ensure only one product is in cart at a time
     */
    public function forceSingleItemCart(bool $passed, int $product_id, int $quantity): bool
    {
        // Remove all existing cart items before adding
        WC()->cart->empty_cart();
        return $passed;
    }

    /**
     * Redirect customers if they try to access the cart page
     */
    public function redirectCartPage(): void
    {
        if (is_cart()) {
            if (!WC()->cart->is_empty()) {
                $redirect = wc_get_checkout_url();
            } else {
                $redirect = site_url();
            }
            wp_safe_redirect($redirect);
            exit;
        }
    }
} 