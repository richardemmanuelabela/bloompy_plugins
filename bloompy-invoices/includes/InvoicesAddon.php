<?php

namespace Bloompy\Invoices;

use Bloompy\Invoices\Utilities\PostTypeRegistrar;
use BookneticAddon\Templates\Backend\Helpers\Helper;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Config;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticSaaS\Providers\UI\MenuUI as SaaSMenuUI;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticSaaS\Providers\UI\SettingsMenuUI as SaaSSettingsMenuUI;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, 'bloompy-invoices' );
}

/**
 * Main addon class for Bloompy Invoices
 */
class InvoicesAddon extends AddonLoader
{
    /**
     * Initialize the addon
     */
    public function init()
    {
        // Register capabilities
        Capabilities::registerTenantCapability('bloompy_invoices', bkntc__('Invoices'));
        
        // Check tenant capability (suppress DB errors from Booknetic core)
        // Note: Booknetic SaaS has a bug where it tries to update services with non-existent 'updated_by' column
        // This is a Booknetic core issue, not ours
        if (!@Capabilities::tenantCan('bloompy_invoices')) {
            return;
        }

        Capabilities::register('bloompy_invoices', bkntc__('Invoices'));
        Capabilities::register('bloompy_invoices_view', bkntc__('View Invoices'), 'bloompy_invoices');
        Capabilities::register('bloompy_invoices_create', bkntc__('Create Invoices'), 'bloompy_invoices');
        Capabilities::register('bloompy_invoices_delete', bkntc__('Delete Invoices'), 'bloompy_invoices');

        // Hook into payment confirmation to create invoices (only after successful payment)
        add_action('bkntc_payment_confirmed', [Listener::class, 'onPaymentConfirmed'], 20, 1);
        add_action('bkntc_payment_confirmed_backend', [Listener::class, 'onPaymentConfirmed'], 20, 1);
        
        // Hook into appointment creation only for local payments (cash/pay on arrival)
        add_action('bkntc_appointment_created', [Listener::class, 'onAppointmentCreatedLocalPayment'], 20, 1);
        
        // Register shortcodes for email workflows
        Config::getShortCodeService()->addReplacer([Listener::class, 'replaceShortCodes']);
        Config::getShortCodeService()->registerShortCodesLazily([Listener::class, 'registerShortCodes']);
        
        // Register custom post type
        add_action('init', [PostTypeRegistrar::class, 'register']);
        
        // Add public query vars and rewrite rules for invoice viewing
        add_action('init', [Frontend\InvoiceViewer::class, 'addRewriteRules']);
        add_filter('query_vars', [Frontend\InvoiceViewer::class, 'addQueryVars']);
        add_action('template_redirect', [Frontend\InvoiceViewer::class, 'handleInvoiceView']);
        
        // Register WordPress AJAX actions globally
        add_action('wp_ajax_bloompy_invoices_save_settings', [$this, 'handleAjaxSaveSettings']);
        add_action('wp_ajax_bloompy_invoices_upload_logo', [$this, 'handleAjaxUploadLogo']);
        add_action('wp_ajax_bloompy_invoices_preview_invoice', [$this, 'handleAjaxPreviewInvoice']);


        // Register WooCommerce hooks if WooCommerce is available
        if (class_exists('WC_Order')) {
            \Bloompy\Invoices\Hooks\WooCommerceHooks::registerHooks();
        }
    }

    /**
     * Initialize backend functionality
     */
    public function initBackend()
    {
        if (!Capabilities::tenantCan('bloompy_invoices')) {
            return;
        }

        if (Capabilities::userCan('bloompy_invoices')) {
            // Register routes - use the correct route name
            Route::get('bloompy_invoices', new Backend\Controller());
            Route::post('bloompy_invoices', Backend\Ajax::class);

            // Add menu - use the same route name as the module
            MenuUI::get('bloompy_invoices')
                ->setTitle(bkntc__('Invoices'))
                ->setIcon('fa fa-file-invoice')
                ->setPriority(920);
            
            // Enqueue DataTables for our invoices page
            add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

            // Register settings routes
            Route::post('bloompy_invoice_settings', Backend\Ajax::class, ['bloompy_invoice_settings', 'bloompy_invoice_settings_save_settings', 'bloompy_invoice_settings_upload_logo', 'bloompy_invoice_settings_preview_invoice']);

            $invoiceIcon = BLOOMPY_INVOICES_PLUGIN_URL . '/assets/images/invoice.svg';

            SettingsMenuUI::get('bloompy_invoice_settings', 'bloompy_invoice_settings')
                ->setTitle(bkntc__('Company Details'))
                ->setDescription(bkntc__('Configure Company and Invoice settings'))
                ->setIcon($invoiceIcon)
                ->setPriority(2);

			SettingsMenuUI::get('bloompy_invoice_settings', 'bloompy_invoice_settings')
				->subItem( 'bloompy_invoice_settings', 'bloompy_invoice_settings' )
				->setTitle( bkntc__( 'Business Information' ) )
				->setPriority( 1 );


        }
    }

    /**
     * Initialize frontend functionality
     */
    public function initFrontend()
    {
        // Frontend functionality is handled in init() method
    }
    
    /**
     * Enqueue assets for the admin area
     */
    public function enqueueAssets()
    {
        error_log('enqueueAssets called - page: ' . ($_GET['page'] ?? 'none') . ', module: ' . ($_GET['module'] ?? 'none'));
        
        // Only enqueue on our invoices page
        if (isset($_GET['page']) && $_GET['page'] === 'bloompy' && 
            isset($_GET['module']) && $_GET['module'] === 'bloompy_invoices') {
            
            error_log('Enqueuing DataTables assets');
            
            // Enqueue DataTables CSS and JS
            wp_enqueue_style(
                'datatables-css',
                'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                [],
                '1.13.6'
            );
            
            wp_enqueue_script(
                'datatables-js',
                'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                ['jquery'],
                '1.13.6',
                true
            );
        }
    }

    /**
     * Handle AJAX save settings request
     */
    public function handleAjaxSaveSettings()
    {
        // Check if user has permission
        if (!\BookneticApp\Providers\Core\Capabilities::userCan('bloompy_invoices_create')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to access this page.']);
            return;
        }

        try {
            // Create AJAX controller instance and call save_settings method
            $ajaxController = new Backend\Ajax();
            $response = $ajaxController->save_settings();
            
            // Check if the response is successful and send appropriate WordPress AJAX response
            if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
                wp_send_json_success($response['data'] ?? 'Settings saved successfully');
            } else {
                $errorMessage = is_array($response) && isset($response['error_msg']) ? $response['error_msg'] : 'Error saving settings';
                wp_send_json_error(['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Internal error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX logo upload request
     */
    public function handleAjaxUploadLogo()
    {
        // Check if user has permission
        if (!\BookneticApp\Providers\Core\Capabilities::userCan('bloompy_invoices_create')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to access this page.']);
            return;
        }

        try {
            // Create AJAX controller instance and call upload_logo method
            $ajaxController = new Backend\Ajax();
            $response = $ajaxController->upload_logo();
            
            // Check if the response is successful and send appropriate WordPress AJAX response
            if (is_array($response) && isset($response['status']) && $response['status'] === 'ok') {
                wp_send_json_success($response ?? 'Logo uploaded successfully');
            } else {
                $errorMessage = is_array($response) && isset($response['error_msg']) ? $response['error_msg'] : 'Error uploading logo';
                wp_send_json_error(['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Internal error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX preview invoice request
     */
    public function handleAjaxPreviewInvoice()
    {
        // Check if user has permission
        if (!\BookneticApp\Providers\Core\Capabilities::userCan('bloompy_invoices_create')) {
            wp_send_json_error(['message' => 'You do not have sufficient permissions to access this page.']);
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bloompy_invoices_preview')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        try {
            // Create AJAX controller instance and call preview_invoice method
            $ajaxController = new Backend\Ajax();
            $response = $ajaxController->preview_invoice();
            
            // Check if the response is successful and send appropriate WordPress AJAX response
            if (is_array($response) && isset($response['status']) && $response['status'] === 'ok') {
                wp_send_json_success($response['data'] ?? 'Preview invoice generated successfully');
            } else {
                $errorMessage = is_array($response) && isset($response['error_msg']) ? $response['error_msg'] : 'Error generating preview invoice';
                wp_send_json_error(['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Internal error: ' . $e->getMessage()]);
        }
    }
	public function initSaaSBackend()
	{
		SaaSRoute::get( 'bloompy_invoices', new Backend\Controller() );
		SaaSRoute::post( 'bloompy_invoices', Backend\Ajax::class);
		SaaSMenuUI::get( 'bloompy_invoices' )
			->setTitle( bkntc__( 'Invoices' ) )
			->setIcon('fa fa-file-invoice')
			->setPriority( 650 );

		$invoiceIcon = BLOOMPY_INVOICES_PLUGIN_URL . '/assets/images/invoice.svg';
		// Register settings routes
		SaaSRoute::post('bloompy_invoice_settings', Backend\Ajax::class, ['bloompy_invoice_settings', 'bloompy_invoice_settings', 'bloompy_invoice_settings_save_settings', 'bloompy_invoice_settings_upload_logo', 'bloompy_invoice_settings_preview_invoice']);
		SaaSSettingsMenuUI::get( 'bloompy_invoice_settings', 'bloompy_invoice_settings' )
			->setPriority( 2 )
			->setTitle( bkntcsaas__('Company Details') )
			->setDescription( bkntcsaas__('Configure Company and Invoice settings') )
			->setIcon( $invoiceIcon );

	}



}