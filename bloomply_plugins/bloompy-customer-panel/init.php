<?php
/*
 * Plugin Name: Bloompy Customer Panel
 * Description: Create a customer panel for Bloompy.
 * Version: 1.0.0
 * Author: Levie Company
 * Author URI: https://simonelevie.nl/
 * License: Commercial
 * Text Domain: booknetic-customer-invoices
 */

defined( 'ABSPATH' ) or exit;
define('BLOOMPY_CUSTOMER PANEL_VERSION', '1.0.0');
define('BLOOMPY_CUSTOMER PANEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLOOMPY_CUSTOMER PANEL_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';
// Load translations
add_action('init', function() {
	load_plugin_textdomain('bloompy-customer-panel', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ "bloompy_customer_panel" ] = new \Bloompy\CustomerPanel\CustomerPanelAddon();
    return $addons;
});
