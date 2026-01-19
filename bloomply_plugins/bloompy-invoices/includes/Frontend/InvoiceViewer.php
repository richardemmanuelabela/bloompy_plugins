<?php

namespace Bloompy\Invoices\Frontend;

use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Listener;
use Bloompy\Invoices\Services\PDFService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

/**
 * Frontend invoice viewer for public access
 */
class InvoiceViewer
{
    /**
     * Add rewrite rules for invoice URLs
     */
    public static function addRewriteRules()
    {
        add_rewrite_rule(
            '^bloompy-invoice/([0-9]+)/([a-zA-Z0-9\-_]+)/?$',
            'index.php?bloompy_tenant_id=$matches[1]&bloompy_invoice_number=$matches[2]',
            'top'
        );
        
        // Backward compatibility: support old URLs without tenant ID
        add_rewrite_rule(
            '^bloompy-invoice/([a-zA-Z0-9\-_]+)/?$',
            'index.php?bloompy_invoice_number=$matches[1]',
            'top'
        );
        
        if (get_option('bloompy_invoices_rewrite_rules_flushed') !== BLOOMPY_INVOICES_VERSION) {
            flush_rewrite_rules();
            update_option('bloompy_invoices_rewrite_rules_flushed', BLOOMPY_INVOICES_VERSION);
        }
    }

    /**
     * Add query vars
     */
    public static function addQueryVars($vars)
    {
        $vars[] = 'bloompy_tenant_id';
        $vars[] = 'bloompy_invoice_number';
        $vars[] = 'token';
        $vars[] = 'download';
        return $vars;
    }

    /**
     * Handle invoice view requests
     */
    public static function handleInvoiceView()
    {
        $tenantId = get_query_var('bloompy_tenant_id');
        $invoiceNumber = get_query_var('bloompy_invoice_number');
        
        // Fallback: Check if URL matches invoice pattern manually
        if (!$invoiceNumber) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // Try new format with tenant ID first
            if (preg_match('#^/bloompy-invoice/([0-9]+)/([a-zA-Z0-9\-_]+)/?#', $requestUri, $matches)) {
                $tenantId = $matches[1];
                $invoiceNumber = $matches[2];
            }
            // Fallback to old format
            elseif (preg_match('#^/bloompy-invoice/([a-zA-Z0-9\-_]+)/?#', $requestUri, $matches)) {
                $invoiceNumber = $matches[1];
                $tenantId = null; // Will use current context or search all
            }
        }

        if (!$invoiceNumber) {
            return;
        }

        $token = get_query_var('token') ?: $_GET['token'] ?? '';
        $download = get_query_var('download') ?: $_GET['download'] ?? '';

        // Get invoice with explicit tenant ID if available
        $invoice = InvoiceService::getByInvoiceNumber($invoiceNumber, $tenantId);
        
        if (!$invoice) {
            wp_die(__('Invoice not found', 'bloompy-invoices'), 404);
        }

        // Verify access
        $hasAccess = false;
        
        // Check if user is admin or tenant admin
        if (current_user_can('administrator') || 
            (current_user_can('manage_options') && class_exists('BookneticApp\\Providers\\Core\\Permission') && Permission::tenantId() == $invoice['tenant_id'])) {
            $hasAccess = true;
        }
        // Check token for customer access
        elseif ($token && Listener::verifyInvoiceToken($invoiceNumber, $invoice['customer_email'], $token)) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            wp_die(__('Access denied', 'bloompy-invoices'), 403);
        }

        // Handle download request
        if ($download === 'pdf') {
            self::downloadInvoicePDF($invoice);
            return;
        }

        // Display invoice
        self::displayInvoice($invoice);
    }

    /**
     * Display invoice in browser as PDF
     */
    private static function displayInvoice($invoice)
    {
        try {
            // Get PDF data using the new invoice system
            $pdfData = InvoiceService::getPdfData($invoice['ID']);
            
            if (empty($pdfData)) {
                wp_die(__('Error generating PDF: No PDF data available', 'bloompy-invoices'));
            }

            $pdfService = new PDFService();
            $pdfContent = $pdfService->generateInvoicePDFContent($invoice, $pdfData);
            
            $filename = $pdfData['filename'] ?? 'invoice-' . $invoice['invoice_number'] . '.pdf';
            
            // Set headers for PDF display in browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdfContent;
            exit;
            
        } catch (\Exception $e) {
            wp_die(__('Error generating PDF: ', 'bloompy-invoices') . $e->getMessage());
        }
    }

    /**
     * Download invoice as PDF
     */
    private static function downloadInvoicePDF($invoice)
    {
        try {
            // Get PDF data using the new invoice system
            $pdfData = InvoiceService::getPdfData($invoice['ID']);
            
            if (empty($pdfData)) {
                wp_die(__('Error generating PDF: No PDF data available', 'bloompy-invoices'));
            }

            $pdfService = new PDFService();
            $pdfContent = $pdfService->generateInvoicePDFContent($invoice, $pdfData);
            
            $filename = $pdfData['filename'] ?? 'invoice-' . $invoice['invoice_number'] . '.pdf';
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdfContent;
            exit;
            
        } catch (\Exception $e) {
            wp_die(__('Error generating PDF: ', 'bloompy-invoices') . $e->getMessage());
        }
    }

    /**
     * Generate invoice view URL
     */
    public static function generateInvoiceUrl($invoiceNumber, $customerEmail = null, $download = false, $tenantId = null)
    {
        // Get tenant ID if not provided
        if ($tenantId === null && class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            $tenantId = \BookneticApp\Providers\Core\Permission::tenantId();
        }
        
        // Use new URL format with tenant ID
        if ($tenantId) {
            $url = home_url("/bloompy-invoice/{$tenantId}/{$invoiceNumber}");
        } else {
            // Fallback to old format for backward compatibility
            $url = home_url("/bloompy-invoice/{$invoiceNumber}");
        }
        
        if ($customerEmail) {
            $token = Listener::generateInvoiceToken($invoiceNumber, $customerEmail);
            $url .= "?token={$token}";
            
            if ($download) {
                $url .= "&download=pdf";
            }
        } elseif ($download) {
            $url .= "?download=pdf";
        }
        
        return $url;
    }
} 