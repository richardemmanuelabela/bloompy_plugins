<?php
/*
 * Plugin Name: Newsletters for Booknetic
 * Description: Integrate newsletters to Booknetic
 * Version: 1.0.0
 * Author: Bloompy
 * Author URI: https://www.bloompy.nl
 * License: Commercial
 * Text Domain: booknetic-newsletters
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/autoload.php';

// Load translations
add_action('init', function() {
    load_plugin_textdomain('booknetic-newsletters', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ 'newsletters' ] = new \BookneticAddon\Newsletters\NewslettersAddon();
    return $addons;
});
