<?php
/*
 * Plugin Name: Tenant Info by Bloompy
 * Description: Enable tenants to set a company name to be displayed at the top of the booking page, and to define custom footer text for display at the bottom of the page.
 * Version: 1.0.0
 * Author: Levie Company
 * Author URI: https://simonelevie.nl/
 * License: Commercial
 * Text Domain: bloompy-tenant-info
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

define('BLOOMPY_TENANT_INFO_VERSION', '1.0.0');
define('BLOOMPY_TENANT_INFO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Translations are loaded automatically by Booknetic's AddonLoader system

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\BloompyTenants\BloompyTenantsAddon::getAddonSlug() ] = new \BookneticAddon\BloompyTenants\BloompyTenantsAddon();
    return $addons;
});
