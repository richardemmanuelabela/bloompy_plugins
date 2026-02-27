<?php

namespace Bloompy\Invoices\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Capabilities;
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Constants\InvoiceConstants;
use Bloompy\Invoices\Listener;

/**
 * AJAX handler for backend operations
 */
class Ajax extends BaseController
{
    /**
     * Show invoices in AJAX table format
     */
    public function get_invoices()
    {

        $limit = Helper::_post('length', 10, 'int');
        $offset = Helper::_post('start', 0, 'int');
        $search = Helper::_post('search', '', 'string');
        

        if (is_array($search) && isset($search['value'])) {
            $search = $search['value'];
        }

        $invoices = InvoiceService::getForTenant(null, $limit, $offset, $search);
        $totalCount = InvoiceService::countForTenant(null, $search);

        $data = [];
        foreach ($invoices as $invoice) {
            $data[] = [
                'id' => $invoice['ID'],
                'invoice_number' => $invoice['invoice_number'],
                'customer_name' => $invoice['customer_name'],
                'customer_email' => $invoice['customer_email'],
                'service_name' => $invoice['service_name'],
                'total_amount' => Helper::price($invoice['total_amount']),
                'status' => ucfirst($invoice['status']),
                'invoice_date' => date('M j, Y', strtotime($invoice['invoice_date'])),
                'created_at' => date('M j, Y H:i', strtotime($invoice['created_at']))
            ];
        }

        return $this->response(true, [
            'draw' => Helper::_post('draw', 1, 'int'),
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $totalCount,
            'data' => $data
        ]);
    }

    /**
     * View single invoice
     */
    public function view_invoice()
    {
        $id = Helper::_post('id', 0, 'int');
        $invoice = InvoiceService::get($id);

        if (!$invoice) {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Invoice not found'));
        }

        return $this->response(true, [
            'invoice' => $invoice
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function download_invoice()
    {
        $id = Helper::_post('id', 0, 'int');
        $invoice = InvoiceService::get($id);
		$invoiceNumber = $invoice['invoice_number'] ?? 'unknown';

		error_log("download invoice: ".print_r($invoice, true));

        if (!$invoice) {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Invoice not found'));
        }

        try {
            $pdfService = new \Bloompy\Invoices\Services\PDFService();
            $pdfPath = $pdfService->generateInvoicePDF($invoice);
            
            if ($pdfPath && file_exists($pdfPath)) {
                $uploadDir = wp_upload_dir();
                $downloadUrl = $uploadDir['baseurl'] . '/bloompy-invoices/' . basename($pdfPath);
                
                return $this->response(true, [
                    'download_url' => $downloadUrl,
                    'invoice_number' => $invoiceNumber,
                    'filename' => basename($pdfPath)
                ]);
            } else {
                return $this->response(false, \Bloompy\Invoices\bkntc__('Failed to generate PDF file'));
            }
            
        } catch (\Exception $e) {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Error generating invoice: ') . $e->getMessage());
        }
    }

    /**
     * Delete invoice
     */
    public function delete_invoice()
    {
        $id = Helper::_post('id', 0, 'int');
        $invoice = InvoiceService::get($id);

        if (!$invoice) {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Invoice not found'));
        }

        if (InvoiceService::delete($id)) {
            return $this->response(true, \Bloompy\Invoices\bkntc__('Invoice deleted successfully'));
        } else {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Failed to delete invoice'));
        }
    }

    /**
     * Create manual invoice
     */
    public function create_invoice()
    {
        try {
            // Get and validate form data
            $customer_email = Helper::_post('customer_email', '', 'string');
            $customer_name = Helper::_post('customer_name', '', 'string');
            $service_name = Helper::_post('service_name', '', 'string');
            $service_price = floatval(Helper::_post('service_price', 0, 'string'));
            $tax_amount = floatval(Helper::_post('tax_amount', 0, 'string'));
            $total_amount = floatval(Helper::_post('total_amount', 0, 'string'));

            // Validate required fields
            if (empty($customer_email) || empty($customer_name) || empty($service_name)) {
                return $this->response(false, \Bloompy\Invoices\bkntc__('Please fill in all required fields'));
            }

            // Validate email format
            if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                return $this->response(false, \Bloompy\Invoices\bkntc__('Please enter a valid email address'));
            }

            // Calculate subtotal if not provided
            $subtotal = $service_price;
            if ($total_amount == 0) {
                $total_amount = $subtotal + $tax_amount;
            }

			// Add tenant settings info
			$tenantSettingsInfo = Listener::getTenantCompanyInfo();
			error_log("Tenant Company Info");
			error_log(print_r($tenantSettingsInfo, true));

            $data = [
                'invoice_number' => InvoiceService::generateInvoiceNumber('customer'),
                'customer_id' => Helper::_post('customer_id', 0, 'int'),
                'customer_email' => $customer_email,
                'customer_name' => $customer_name,
                'customer_phone' => Helper::_post('customer_phone', '', 'string'),
                'service_name' => $service_name,
                'service_price' => $service_price,
                'customer_company_name' => Helper::_post('company_name', '', 'string'),
                'customer_company_address' => Helper::_post('company_address', '', 'string'),
                'customer_company_city' => Helper::_post('company_city', '', 'string'),
				'company_logo' => $tenantSettingsInfo['logo'] ?? '',
				'company_name' => $tenantSettingsInfo['name'] ?? '',
				'company_address' => $tenantSettingsInfo['address'] ?? '',
				'company_zipcode' => $tenantSettingsInfo['zipcode'] ?? '',
				'company_city' => $tenantSettingsInfo['city'] ?? '',
				'company_country' => $tenantSettingsInfo['country'] ?? '',
				'company_phone' => $tenantSettingsInfo['phone'] ?? '',
				'company_iban' => $tenantSettingsInfo['iban'] ?? '',
				'company_kvk_number' => $tenantSettingsInfo['kvk_number'] ?? '',
				'company_btw_number' => $tenantSettingsInfo['btw_number'] ?? '',
				'company_footer_text' => $tenantSettingsInfo['footer_text'] ?? '',
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'currency' => Helper::_post('currency', 'EUR', 'string'),
                'notes' => Helper::_post('notes', '', 'string'),
                'source' => 'manual'
            ];

            // Debug log
            error_log('Creating invoice with data: ' . print_r($data, true));

            $invoiceId = InvoiceService::create($data);

            if ($invoiceId) {
                error_log('Invoice created successfully with ID: ' . $invoiceId);
                return $this->response(true, \Bloompy\Invoices\bkntc__('Invoice created successfully'));
            } else {
                error_log('Failed to create invoice');
                return $this->response(false, \Bloompy\Invoices\bkntc__('Failed to create invoice'));
            }

        } catch (\Exception $e) {
            error_log('Exception in create_invoice: ' . $e->getMessage());
            return $this->response(false, \Bloompy\Invoices\bkntc__('Error: ') . $e->getMessage());
        }
    }

    /**
     * Update invoice status
     */
    public function update_status()
    {
        $id = Helper::_post('id', 0, 'int');
        $status = Helper::_post('status', '', 'string');

        $invoice = InvoiceService::get($id);
        if (!$invoice) {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Invoice not found'));
        }

        if (InvoiceService::update($id, ['status' => $status])) {
            return $this->response(true, \Bloompy\Invoices\bkntc__('Status updated successfully'));
        } else {
            return $this->response(false, \Bloompy\Invoices\bkntc__('Failed to update status'));
        }
    }

    /**
     * Get invoice statistics
     */
    public function get_stats()
    {
        global $wpdb;
        
        $tenantId = class_exists('BookneticApp\\Providers\\Core\\Permission') ? \BookneticApp\Providers\Core\Permission::tenantId() : 0;
        
        // Get total invoices count
        $totalInvoices = InvoiceService::countForTenant($tenantId);
        
        // Get total revenue
        $revenueQuery = $wpdb->prepare("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total_revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_tenant ON p.ID = pm_tenant.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = 'total_amount'
            AND pm_tenant.meta_key = 'tenant_id'
            AND pm_tenant.meta_value = %s
        ", 'bloompy_invoice', $tenantId);
        
        $totalRevenue = $wpdb->get_var($revenueQuery) ?: 0;
        
        // Get pending invoices count
        $pendingQuery = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_tenant ON p.ID = pm_tenant.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = 'status'
            AND pm.meta_value = 'pending'
            AND pm_tenant.meta_key = 'tenant_id'
            AND pm_tenant.meta_value = %s
        ", 'bloompy_invoice', $tenantId);
        
        $pendingInvoices = $wpdb->get_var($pendingQuery) ?: 0;

        $thisMonthInvoices = $this->getThisMonthInvoices($tenantId);

        return $this->response(true, [
            'total_invoices' => $totalInvoices,
            'total_revenue' => Helper::price($totalRevenue),
            'pending_invoices' => $pendingInvoices,
            'this_month_invoices' => $thisMonthInvoices
        ]);
    }

    /**
     * Get this month's invoices count
     */
    private function getThisMonthInvoices($tenantId)
    {
        global $wpdb;
        
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
        
        $query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_tenant ON p.ID = pm_tenant.post_id
            WHERE p.post_type = %s
            AND pm_tenant.meta_key = 'tenant_id'
            AND pm_tenant.meta_value = %s
            AND p.post_date BETWEEN %s AND %s
        ", InvoiceConstants::POST_TYPE, $tenantId, $startDate, $endDate);
        
        return $wpdb->get_var($query) ?: 0;
    }



    /**
     * Display invoice settings view
     */
    public function bloompy_invoice_settings()
    {
		if (! ( Helper::isSaaSVersion() && Permission::isSuperAdministrator() )) {
			Capabilities::must('bloompy_invoices_create');
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

        ob_start();
        include BLOOMPY_INVOICES_PLUGIN_PATH . 'includes/Backend/view/settings_booknetic.php';
        $html = ob_get_clean();

        return Helper::response(true, ['html' => $html] );
    }

    /**
     * Upload company logo
     */
    public function bloompy_invoice_settings_upload_logo()
    {
        Capabilities::must('bloompy_invoices_create');

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            return Helper::response(false, bkntc__('No file uploaded or upload error.'));
        }

        $file = $_FILES['logo'];
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            return Helper::response(false, bkntc__('File size must be less than 2MB.'));
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file_type['type'], $allowed_types)) {
            return Helper::response(false, bkntc__('Only JPEG, PNG, GIF, and BMP images are allowed.'));
        }

        // Upload file using WordPress media library
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            return Helper::response(false, bkntc__('Upload error: ') . $upload['error']);
        }

        // Also save to Booknetic company logo system for unified storage
        $this->syncLogoToBooknetic($upload['url']);

        return Helper::response(true, [
            'logo_url' => $upload['url']
        ]);
    }

    /**
     * Save invoice settings
     */
    public function bloompy_invoice_settings_save_settings()
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

        $invoice_starting_number = Helper::_post('invoice_starting_number', '', 'string');
        $company_name = Helper::_post('company_name', '', 'string');
        $company_address = Helper::_post('company_address', '', 'string');
        $company_zipcode = Helper::_post('company_zipcode', '', 'string');
        $company_city = Helper::_post('company_city', '', 'string');
        $company_country = Helper::_post('company_country', '', 'string');
        $company_phone = Helper::_post('company_phone', '', 'string');
        $company_iban = Helper::_post('company_iban', '', 'string');
        $company_kvk_number = Helper::_post('company_kvk_number', '', 'string');
        $company_btw_number = Helper::_post('company_btw_number', '', 'string');
        $company_footer_text = Helper::_post('company_footer_text', '', 'string');
        $company_logo = Helper::_post('company_logo', '', 'string');
		$display_logo_on_booking_panel = Helper::_post( 'display_logo_on_booking_panel', 'off', 'string', [ 'on', 'off' ] );

        try {
            if ($tenant_id > 0 && class_exists('\BookneticSaaS\Models\Tenant')) {
                // SaaS installation - save to tenant data
                $year = date('Y');
                \BookneticSaaS\Models\Tenant::setData($tenant_id, "invoice_starting_number_{$year}", $invoice_starting_number);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_name', $company_name);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_address', $company_address);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_zipcode', $company_zipcode);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_city', $company_city);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_country', $company_country);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_phone', $company_phone);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_iban', $company_iban);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_kvk_number', $company_kvk_number);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_btw_number', $company_btw_number);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_footer_text', $company_footer_text);
                \BookneticSaaS\Models\Tenant::setData($tenant_id, 'invoice_company_logo', $company_logo);
				Helper::setOption( 'display_logo_on_booking_panel', $display_logo_on_booking_panel );

                // Sync company details to Booknetic company settings
                $this->syncCompanyDetailsToBooknetic($company_name, $company_address, $company_phone, $company_logo);
            } else {
                // Non-SaaS installation - save to WordPress options
                $year = date('Y');
                update_option("bloompy_invoice_starting_number_{$year}", $invoice_starting_number);
                update_option('bloompy_invoice_company_name', $company_name);
                update_option('bloompy_invoice_company_address', $company_address);
                update_option('bloompy_invoice_company_zipcode', $company_zipcode);
                update_option('bloompy_invoice_company_city', $company_city);
                update_option('bloompy_invoice_company_country', $company_country);
                update_option('bloompy_invoice_company_phone', $company_phone);
                update_option('bloompy_invoice_company_iban', $company_iban);
                update_option('bloompy_invoice_company_kvk_number', $company_kvk_number);
                update_option('bloompy_invoice_company_btw_number', $company_btw_number);
                update_option('bloompy_invoice_company_footer_text', $company_footer_text);
                update_option('bloompy_invoice_company_logo', $company_logo);
				update_option('display_logo_on_booking_panel', $display_logo_on_booking_panel);
                
                // Sync company details to Booknetic company settings
                $this->syncCompanyDetailsToBooknetic($company_name, $company_address, $company_phone, $company_logo);
            }

            return Helper::response(true, bkntc__('Settings saved successfully!'));
        } catch (\Exception $e) {
            return Helper::response(false, bkntc__('Error saving settings: ') . $e->getMessage());
        }
    }

    /**
     * Preview invoice
     */
    public function bloompy_invoice_settings_preview_invoice()
    {
        Capabilities::must('bloompy_invoices_create');

        $company_name = Helper::_post('company_name', '', 'string');
        $company_phone = Helper::_post('company_phone', '', 'string');
        $company_logo = Helper::_post('company_logo', '', 'string');
        $company_address = Helper::_post('company_address', '', 'string');
        $company_zipcode = Helper::_post('company_zipcode', '', 'string');
        $company_city = Helper::_post('company_city', '', 'string');
        $company_country = Helper::_post('company_country', '', 'string');
        $company_iban = Helper::_post('company_iban', '', 'string');
        $company_kvk_number = Helper::_post('company_kvk_number', '', 'string');
        $company_btw_number = Helper::_post('company_btw_number', '', 'string');
        $company_footer_text = Helper::_post('company_footer_text', '', 'string');

        try {
            // Create a preview invoice using the PDF service
            $pdfService = new \Bloompy\Invoices\Services\PDFService();
            
            // Generate preview data
            $previewData = [
                'invoice_number' => 'PREVIEW-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
				'customer_phone' => '+31 20 123 4567',
				'service_id' => '100',
                'service_name' => 'Sample Service',
                'service_price' => 100.00,
                'subtotal' => 100.00,
                'tax_amount' => 21.00,
                'total_amount' => 121.00,
                'currency' => 'EUR',
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'paid', // Add status field for PDF generation
                'service_extras' => [], // Empty array for service extras
                'customer_company_name' => 'Sample Company B.V.',
                'customer_company_address' => 'Sample Street 123',
                'customer_company_zipcode' => '1234 AB',
                'customer_company_city' => 'Amsterdam',
                'customer_company_country' => 'Netherlands',
                'customer_company_iban' => 'NL91ABNA0417164300',
                'customer_company_kvk_number' => '12345678',
                'customer_company_btw_number' => 'NL123456789B01',
                'company_name' => $company_name,
                'company_address' => $company_address,
                'company_zipcode' => $company_zipcode,
                'company_city' => $company_city,
                'company_country' => $company_country,
                'company_phone' => $company_phone,
                'company_iban' => $company_iban,
                'company_kvk_number' => $company_kvk_number,
                'company_btw_number' => $company_btw_number,
                'company_footer_text' => $company_footer_text,
                'company_logo' => $company_logo,
            ];

            $pdfContent = $pdfService->generateInvoicePDFContent($previewData);

            if (!$pdfContent) {
                return Helper::response(false, bkntc__('Failed to generate preview PDF'));
            }

            // For AJAX requests, we need to return a response with download URL
            // Create a temporary file and return the URL
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['path'] . '/invoice-preview-' . time() . '.pdf';
            file_put_contents($temp_file, $pdfContent);
            
            $download_url = $upload_dir['url'] . '/invoice-preview-' . time() . '.pdf';
            
            return Helper::response(true, [
                'download_url' => $download_url,
                'filename' => 'invoice-preview.pdf'
            ]);
        } catch (\Exception $e) {
            return Helper::response(false, bkntc__('Error generating preview: ') . $e->getMessage());
        }
    }

    /**
     * Sync company details to Booknetic company settings
     * This ensures the invoice company details are also used in Booknetic
     */
    private function syncCompanyDetailsToBooknetic($companyName, $companyAddress, $companyPhone, $logoUrl)
    {
        try {
            // Check if Booknetic Helper class exists
            if (!class_exists('\BookneticApp\Providers\Helpers\Helper')) {
                return;
            }

            // Sync company name
            if (!empty($companyName)) {
                \BookneticApp\Providers\Helpers\Helper::setOption('company_name', $companyName);
            }

            // Sync company address
            if (!empty($companyAddress)) {
                \BookneticApp\Providers\Helpers\Helper::setOption('company_address', $companyAddress);
            }

            // Sync company phone
            if (!empty($companyPhone)) {
                \BookneticApp\Providers\Helpers\Helper::setOption('company_phone', $companyPhone);
            }

            // Sync logo
            if (!empty($logoUrl)) {
                $this->syncLogoToBooknetic($logoUrl);
            }
        } catch (\Exception $e) {
            // Silently fail - don't break invoice functionality if Booknetic sync fails
            error_log('Bloompy Invoices: Failed to sync company details to Booknetic: ' . $e->getMessage());
        }
    }

    /**
     * Sync logo to Booknetic company settings
     * This ensures the invoice logo is also used as the Booknetic company logo
     */
    private function syncLogoToBooknetic($logoUrl)
    {
        if (empty($logoUrl)) {
            return;
        }

        try {
            // Check if Booknetic Helper class exists
            if (!class_exists('\BookneticApp\Providers\Helpers\Helper')) {
                return;
            }

            // Convert URL to filename for Booknetic storage
            $logoFilename = $this->convertUrlToBookneticFilename($logoUrl);
            
            if ($logoFilename) {
                // Save to Booknetic company_image option
                \BookneticApp\Providers\Helpers\Helper::setOption('company_image', $logoFilename);
            }
        } catch (\Exception $e) {
            // Silently fail - don't break invoice functionality if Booknetic sync fails
            error_log('Bloompy Invoices: Failed to sync logo to Booknetic: ' . $e->getMessage());
        }
    }

    /**
     * Convert WordPress media URL to Booknetic filename format
     */
    private function convertUrlToBookneticFilename($url)
    {
        if (empty($url)) {
            return '';
        }

        // Extract filename from URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $filename = basename($path);
        
        // Generate unique filename similar to Booknetic's format
        $pathInfo = pathinfo($filename);
        $extension = $pathInfo['extension'] ?? '';
        $uniqueFilename = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        
        // Copy file to Booknetic uploads directory
        $uploadDir = wp_upload_dir();
        $sourceFile = $uploadDir['basedir'] . str_replace($uploadDir['baseurl'], '', $url);
        
        if (file_exists($sourceFile)) {
            $bookneticUploadDir = $uploadDir['basedir'] . '/booknetic/settings/';
            
            // Create directory if it doesn't exist
            if (!is_dir($bookneticUploadDir)) {
                wp_mkdir_p($bookneticUploadDir);
            }
            
            $destinationFile = $bookneticUploadDir . $uniqueFilename;
            
            if (copy($sourceFile, $destinationFile)) {
                return $uniqueFilename;
            }
        }
        
        return '';
    }

	/**
	 * @return string[]
	 * Invoices fields.
	 */
	public function invoice_fields() {
		return ['invoice_number',
			'appointment_id',
			'customer_id',
			'customer_email',
			'customer_name',
			'customer_phone',
			'service_id',
			'service_name',
			'service_price',
			'service_duration',
			'appointment_date',
			'customer_company_name',
			'customer_company_address',
			'customer_company_zipcode',
			'customer_company_city',
			'customer_company_country',
			'customer_company_iban',
			'customer_company_kvk_number',
			'customer_company_btw_number',
			'company_logo',
			'company_name',
			'company_address',
			'company_zipcode',
			'company_city',
			'company_country',
			'company_phone',
			'company_iban',
			'company_kvk_number',
			'company_btw_number',
			'company_footer_text',
			'subtotal',
			'tax_amount',
			'total_amount',
			'currency',
			'invoice_date',
			'due_date',
			'status',
			'payment_date',
			'notes',
			'source',
			'service_extras',
			'number_of_appointments'
			/*'pricing_breakdown'*/];
	}


	/**
	 * @return mixed|void|null
	 * Export Invoice in PDF format.
	 */
	public function exportInvoicePdf() {
		$invoiceNumbers = Helper::_post('invoice_numbers', '', 'json');
		$invoices = $this->getInvoicesForExport($invoiceNumbers);

		$uploadDir = wp_upload_dir();
		$zipFilesDirectory = $uploadDir['basedir'] . '/bloompy-invoices/zip-files/';
		$zipFilename = 'bloompy-invoices-zip-'.time().".zip";
		$zipInvoiceDir = $zipFilesDirectory.$zipFilename;
		if (!is_dir($zipFilesDirectory)) {
			if (wp_mkdir_p($zipFilesDirectory)) {
				error_log("Directory created: " . $zipFilesDirectory);
			} else {
				return Helper::response(false, bkntc__('Failed to create directory.'));
			}
		}
		$zip = new \ZipArchive();
		if ($zip->open($zipInvoiceDir, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
			foreach($invoices as $invoice) {
				$pdfService = new \Bloompy\Invoices\Services\PDFService();
				$pdfPath = $pdfService->generateInvoicePDF($invoice);
				if ($pdfPath && file_exists($pdfPath)) {
					$uploadDir = wp_upload_dir();
					$invoiceUrl = $uploadDir['baseurl'] . '/bloompy-invoices/' . basename($pdfPath);
					$fileContent = file_get_contents($invoiceUrl);
					if ($fileContent !== false) {
						// Extract filename from URL
						$filename = basename(parse_url($invoiceUrl, PHP_URL_PATH));
						// Add file to zip
						$zip->addFromString($filename, $fileContent);
					}
				} else {
					error_log('Invoice ID: '.$invoice->ID.' Failed to generate PDF file');
				}
			}
			$zip->close();
			$downloadUrl = $uploadDir['baseurl'] . '/bloompy-invoices/zip-files/'.$zipFilename;
			return Helper::response(true, [
				'download_url' => $downloadUrl,
				'filename' => $zipFilename
			]);
		}
	}

	/**
	 * @return mixed|null
	 * Export Invoice in XML format.
	 */
	public function exportInvoiceXml() {
		$invoiceNumbers = Helper::_post('invoice_numbers', '', 'json');
		$invoices = $this->getInvoicesForExport($invoiceNumbers);

		if ( !$invoices ) return Helper::response(false, bkntc__('No invoice was found.'));
		$host = 'http://' . $_SERVER['HTTP_HOST'] . '/';
		$invoices_xml = new \SimpleXMLElement('<source></source>');
		$invoices_xml->addChild('publisher', $_SERVER['HTTP_HOST']);
		$invoices_xml->addChild('publisherurl', $host);
		$invoices_xml->addChild('lastBuildDate', date('r'));

		$fields = $this->invoice_fields();

		foreach ( $invoices as $invoice ) {
			$invoice_xml = $invoices_xml->addChild('invoice');
			foreach ( $fields as $field ) {
				if (!empty($invoice[$field])) {
					$value = (string)$invoice[$field];
					$invoice_xml->addChild( $field, $value );
				}

			}
		}

		ob_end_clean();
		header_remove();
		$filename = 'bloompy-invoices-xml-'.time().".xml";
		return Helper::response(true, [
			'xml' => $invoices_xml->asXML(),
			'filename' => $filename
		]);
	}

	/**
	 * @return mixed|null
	 * Export Invoice in CSV format.
	 */
	public function exportInvoiceCsv() {

		$invoiceNumbers = Helper::_post('invoice_numbers', '', 'json');
		$invoices = $this->getInvoicesForExport($invoiceNumbers);

		if ( !$invoices ) return Helper::response(false, bkntc__('No invoice was found.'));

		$fields = $this->invoice_fields();

		$fp = fopen('php://temp', 'w');

		//assign headers
		fputcsv($fp, $fields, ',');

		foreach ( $invoices as $invoice ) {
			$invoiceData = [];
			foreach ( $fields as $field ) {
				array_push( $invoiceData, $invoice[$field] );;
			}
			fputcsv($fp, $invoiceData, ',');
		}
		rewind($fp);
		$csv = stream_get_contents($fp);
		fclose($fp);

		$filename = "invoices.csv";
		return Helper::response(true, [
			'csv' => $csv,
			'filename' => $filename
		]);


	}

	/**
	 * @param $invoiceNumbers
	 * @param $tenantId
	 * @return array
	 * Get invoices by invoice numbers and tenant ID.
	 */
	public function getInvoicesByInvoiceNumber($invoiceNumbers, $tenantId) {
		$invoices = [];
		foreach($invoiceNumbers as $invoiceNumber) {
			$invoices[] = InvoiceService::getByInvoiceNumber($invoiceNumber, $tenantId);
		}
		return $invoices;
	}

	/**
	 * @param $invoiceNumbers
	 * @return array
	 * Get invoices to export.
	 */
	public function getInvoicesForExport($invoiceNumbers) {
		$tenantId = class_exists('BookneticApp\\Providers\\Core\\Permission') ? \BookneticApp\Providers\Core\Permission::tenantId() : 0;
		if (!empty($invoiceNumbers)) {
			return $this->getInvoicesByInvoiceNumber($invoiceNumbers, $tenantId);
		} else {
			return InvoiceService::getForTenant($tenantId, -1, 0, '');
		}
	}
} 