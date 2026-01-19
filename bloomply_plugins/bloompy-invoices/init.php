<?php
/*
 * Plugin Name: Bloompy Invoices for Booknetic
 * Description: Create professional invoices for Booknetic appointments with tenant company information.
 * Version: 1.0.0
 * Author: Bloompy
 * License: GPL v2 or later
 * Text Domain: bloompy-invoices
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;

define('BLOOMPY_INVOICES_VERSION', '1.0.0');
define('BLOOMPY_INVOICES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLOOMPY_INVOICES_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Autoloader
require_once __DIR__ . '/includes/autoloader.php';

// Load translations
add_action('init', function() {
    load_plugin_textdomain('bloompy-invoices', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_filter('bkntc_addons_load', function ($addons)
{
    $addons['bloompy_invoices'] = new \Bloompy\Invoices\InvoicesAddon();
    return $addons;
}); 