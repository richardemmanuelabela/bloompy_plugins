<?php
namespace BloompyAddon\WooCommerceBridge\Domain\Shortcodes;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\ShortcodeInterface;

class BuyNowShortcode implements ShortcodeInterface
{
    public static function register(): void
    {
        add_shortcode('bloompy_buy_now', [self::class, 'render']);
    }

    public static function render($atts): string
    {
        $atts = shortcode_atts([
            'product_id'   => '',
            'variation_id' => '',
            'label'        => 'Buy Now',
        ], $atts);

        if (empty($atts['product_id'])) {
            return '<!-- Missing product_id -->';
        }

        $query = [
            'add-to-cart' => (int) $atts['product_id']
        ];

        if (!empty($atts['variation_id'])) {
            $query['variation_id'] = (int) $atts['variation_id'];

            foreach ($atts as $key => $value) {
                if (strpos($key, 'attribute_') === 0) {
                    $query[$key] = sanitize_title($value);
                }
            }
        }

        $checkout_url = esc_url(add_query_arg($query, wc_get_checkout_url()));
        $button_text = esc_html($atts['label']);

        return '<a class="button bloompy-buy-now" href="' . $checkout_url . '">' . $button_text . '</a>';
    }
} 