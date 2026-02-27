<?php

namespace Bloompy\Invoices\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

/**
 * Settings controller for invoice configuration within Booknetic settings
 */
class SettingsController extends BaseController
{
    /**
     * Invoice settings page
     */
    public function bloompy_invoice_settings()
    {
        Capabilities::must('bloompy_invoices_create');

        $tenant_id = 0;
        if (class_exists('\BookneticApp\Providers\Core\Permission')) {
            $tenant_id = \BookneticApp\Providers\Core\Permission::tenantId();
        }
        if (empty($tenant_id) && class_exists('\BookneticSaaS\Models\Tenant')) {
            $user = wp_get_current_user();
            $tenant = \BookneticSaaS\Models\Tenant::where('user_id', $user->ID)->fetch();
            if ($tenant) {
                $tenant_id = $tenant->id;
            }
        }

        $settings = [];
        if ($tenant_id > 0 && class_exists('\BookneticSaaS\Models\Tenant')) {
            $year = date('Y');
            $settings['invoice_starting_number'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, "invoice_starting_number_{$year}");
            $settings['company_name'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_name');
            $settings['company_address'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_address');
            $settings['company_zipcode'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_zipcode');
            $settings['company_city'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_city');
            $settings['company_country'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_country');
            $settings['company_phone'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_phone');
            $settings['company_iban'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_iban');
            $settings['company_kvk_number'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_kvk_number');
            $settings['company_btw_number'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_btw_number');
            $settings['company_footer_text'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_footer_text');
            $settings['company_logo'] = \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_logo');
        } else {
            $year = date('Y');
            $settings['invoice_starting_number'] = get_option("bloompy_invoice_starting_number_{$year}", '');
            $settings['company_name'] = get_option('bloompy_invoice_company_name', '');
            $settings['company_address'] = get_option('bloompy_invoice_company_address', '');
            $settings['company_zipcode'] = get_option('bloompy_invoice_company_zipcode', '');
            $settings['company_city'] = get_option('bloompy_invoice_company_city', '');
            $settings['company_country'] = get_option('bloompy_invoice_company_country', '');
            $settings['company_phone'] = get_option('bloompy_invoice_company_phone', '');
            $settings['company_iban'] = get_option('bloompy_invoice_company_iban', '');
            $settings['company_kvk_number'] = get_option('bloompy_invoice_company_kvk_number', '');
            $settings['company_btw_number'] = get_option('bloompy_invoice_company_btw_number', '');
            $settings['company_footer_text'] = get_option('bloompy_invoice_company_footer_text', '');
            $settings['company_logo'] = get_option('bloompy_invoice_company_logo', '');
        }

        $can_set_starting_number = true;
        if ($tenant_id > 0) {
            $existing_invoices = get_posts([
                'post_type' => 'bloompy_invoice',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'tenant_id',
                        'value' => $tenant_id,
                        'compare' => '='
                    ]
                ]
            ]);
            $can_set_starting_number = empty($existing_invoices);
        } else {
            $existing_invoices = get_posts([
                'post_type' => 'bloompy_invoice',
                'post_status' => 'publish',
                'posts_per_page' => 1
            ]);
            $can_set_starting_number = empty($existing_invoices);
        }

        $this->view('settings_booknetic', [
            'tenant_id' => $tenant_id,
            'settings' => $settings,
            'can_set_starting_number' => $can_set_starting_number
        ]);
    }
}
