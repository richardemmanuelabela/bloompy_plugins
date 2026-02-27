<?php
/*
 * Plugin Name: Bloompy WooCommerce Bridge
 * Description: Integrates WooCommerce with Booknetic SaaS for plan assignment and upgrades.
 * Version: 1.0.0
 * Author: Bloompy
 * Author URI: https://www.bloompy.nl
 * License: Commercial
 * Text Domain: bloompy-woocommerce-bridge
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;
require_once __DIR__ . '/autoload.php';

add_filter('bkntc_addons_load', function ($addons) {
    $addons['bloompy_woocommerce_bridge'] = new \BloompyAddon\WooCommerceBridge\WooCommerceBridgeAddon();
    return $addons;
}); 