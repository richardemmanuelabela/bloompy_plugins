<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Factories\InvoiceFactory;
use Bloompy\Invoices\Constants\InvoiceConstants;
use Bloompy\Invoices\Support\Helpers;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Models\Service;
use BookneticApp\Models\Customer;

/**
 * Backend controller for invoice management
 */
class Controller extends BaseController
{
    /**
     * Default view - show invoices list
     */
    public function index()
    {
        // Build the query for invoices (custom post type)
        $tenant_id = Helpers::getCurrentTenantId();
		
        $invoiceType = $this->determineInvoiceType($tenant_id);
        $query = InvoiceService::getDataTableQuery($invoiceType, $tenant_id);

        $dataTable = new DataTableUI($query);
        $this->dataTable = $dataTable;

        $dataTable->setTitle(\Bloompy\Invoices\bkntc__('Invoices'));
        $dataTable->addNewBtn(\Bloompy\Invoices\bkntc__('Create Invoice'));
		$dataTable->addNewBtn(\Bloompy\Invoices\bkntc__('Export Invoices'));

        // Get display columns for the current invoice type
        $displayColumns = InvoiceService::getDisplayColumns($invoiceType);
        $this->addColumnsToDataTable($dataTable, $displayColumns);


        // Get search fields for the current invoice type
        $searchFields = InvoiceService::getSearchFields($invoiceType);
        $dataTable->searchBy($searchFields);

        // Populate filters with live invoice data if the current page is not a super admin page.
		if ( !empty($tenant_id) ) {
			$this->setFilters($dataTable);
		}

        $table = $dataTable->renderHTML();
        
        // Output the view with table HTML
        ob_start();
        $this->view('invoices', [
            'title' => \Bloompy\Invoices\bkntc__('Invoices'),
            'table' => $table,
            'invoice_type' => $invoiceType,
            'available_types' => InvoiceService::getAvailableTypes()
        ]);
        $viewOutput = ob_get_clean();
        
        // If table variable is missing, inject it manually
        if (strpos($viewOutput, 'DATATABLEUI_TABLE_PLACEHOLDER') !== false) {
            $viewOutput = str_replace('DATATABLEUI_TABLE_PLACEHOLDER', $table, $viewOutput);
        }
        
        echo $viewOutput;
    }

    private function setFilters($dataTable)
    {
        // Get current tenant ID
        $tenant_id = 0;
        if (class_exists('\BookneticApp\Providers\Core\Permission')) {
            $tenant_id = \BookneticApp\Providers\Core\Permission::tenantId();
        }
        
        // Get unique services from invoices for current tenant only
        $services = [];
        $customers = [];
        
        $query_args = [
            'post_type' => 'bloompy_invoice',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        
        // Add tenant filtering if we have a tenant ID
        if ($tenant_id > 0) {
            $query_args['meta_query'] = [
                [
                    'key' => 'tenant_id',
                    'value' => $tenant_id,
                    'compare' => '='
                ]
            ];
        }
        
        $posts = get_posts($query_args);

        foreach ($posts as $post_id) {
            $service_name = get_post_meta($post_id, 'service_name', true);
            $customer_email = get_post_meta($post_id, 'customer_email', true);
            $customer_name = get_post_meta($post_id, 'customer_name', true);

            if ($service_name && !isset($services[$service_name])) {
                $services[$service_name] = $service_name;
            }

            if ($customer_email && !isset($customers[$customer_email])) {
                $customers[$customer_email] = $customer_name . ' (' . $customer_email . ')';
            }
        }

        // Only add the date range filter for now (others causing issues)
		$dataTable->addFilter( Service::getField( 'id' ), 'select', \Bloompy\Invoices\bkntc__( 'Service' ), '=', [ 'model' => new Service() ] );
		$dataTable->addFilter( Customer::getField( 'id' ), 'select', bkntc__( 'Customer' ), '=', [
			'model' => Customer::my(),
			'name_field' => 'CONCAT(`first_name`, \' \', last_name)'
		] );
		$dataTable->addFilter( 'date_start', 'input', bkntc__( 'Date start' ));
		$dataTable->addFilter( 'date_end', 'input', bkntc__( 'Date end' ));
    }

    /**
     * Settings page for invoice configuration
     */
    public function settings()
    {
        if (!\BookneticApp\Providers\Core\Capabilities::userCan('bloompy_invoices_create')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

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


        
        $this->view('settings', [
            'tenant_id' => $tenant_id,
            'settings' => $settings,
            'can_set_starting_number' => $can_set_starting_number
        ]);
    }

    /**
     * Determine invoice type based on context
     * 
     * @param int|null $tenantId
     * @return string
     */
    private function determineInvoiceType(?int $tenantId): string
    {
        // Check if we're in SaaS version and if user is super administrator
        if (Helpers::isSaaSVersion() && Helpers::isSuperAdmin()) {
            // Super admin in SaaS context - show WooCommerce invoices
            return InvoiceConstants::TYPE_WOOCOMMERCE;
        }
        
        // Check if we're on the booknetic-saas page (super admin managing tenants)
        if (class_exists('BookneticApp\\Providers\\Helpers\\Helper')) {
            $currentPage = \BookneticApp\Providers\Helpers\Helper::_get('page', '', 'string');
            if ($currentPage === InvoiceConstants::PAGE_BOOKNETIC_SAAS) {
                // Super admin managing SaaS - show WooCommerce invoices
                return InvoiceConstants::TYPE_WOOCOMMERCE;
            }
        }
        
        // Default to customer invoices for tenant context
        return InvoiceConstants::TYPE_CUSTOMER;
    }

    /**
     * Add columns to DataTable based on invoice type
     * 
     * @param DataTableUI $dataTable
     * @param array $displayColumns
     */
    private function addColumnsToDataTable(DataTableUI $dataTable, array $displayColumns): void
    {
        foreach ($displayColumns as $key => $column) {
            if ($key === 'checkbox' && is_callable($column)) {
                $dataTable->addColumns('', $column, ['is_html' => true]);
            } elseif ($key === 'customer' && is_callable($column)) {
                $dataTable->addColumns(\Bloompy\Invoices\bkntc__('Customer'), $column, ['is_html' => true]);
            } elseif ($key === 'status' && is_callable($column)) {
                $dataTable->addColumns(\Bloompy\Invoices\bkntc__('Status'), $column, ['is_html' => true]);
            } elseif ($key === 'actions' && is_callable($column)) {
                $dataTable->addColumns(\Bloompy\Invoices\bkntc__('Actions'), $column, ['is_html' => true], true);
            } elseif (is_string($column)) {
                $dataTable->addColumns(\Bloompy\Invoices\bkntc__($column), $key);
            }
        }
    }
} 