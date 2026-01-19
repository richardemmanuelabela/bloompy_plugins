<?php
/*
 * Plugin Name: Mollie Connect for Booknetic
 * Description: Make payments with Mollie Connect
 * Version: 1.0
 * Author: Levie Company
 * Author URI: https://www.simonelevie.nl
 * License: Commercial
 * Text Domain: booknetic-mollie-connect
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Bloompy\Mollie\MollieAddon::getAddonSlug() ] = new \BookneticAddon\Bloompy\Mollie\MollieAddon();
    return $addons;
});
