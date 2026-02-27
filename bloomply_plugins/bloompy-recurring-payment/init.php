<?php
/*
 * Plugin Name: Bloompy Recurring Payments
 * Description: Bloompy Recurring payments
 * Version: 1.0.0
 * Author: Bloompy
 * Author URI: https://www.bloompy.nl
 * License: Commercial
 * Text Domain: booknetic-newsletters
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;

// Autoloader
require_once __DIR__ . '/includes/autoloader.php';

// Load translations
add_action('init', function() {
    load_plugin_textdomain('bloompy-recurring-payments', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ 'bloompy_recurring_payments' ] = new \Bloompy\RecurringPayments\RecurringPaymentsAddon();
    return $addons;
});
