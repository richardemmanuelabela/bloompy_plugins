<?php

declare(strict_types=1);

namespace Bloompy\Invoices\PDF;

use BookneticApp\Providers\Helpers\Helper;

/**
 * PDF template for WooCommerce invoices
 * 
 * Handles data normalization and HTML generation for WooCommerce/SaaS invoices
 */
class WooCommerceInvoicePDFTemplate implements InvoicePDFTemplate
{
    /**
     * Prepare invoice data by normalizing field names
     * 
     * Converts WooCommerce fields (product_name, unit_price) to template fields (service_name, service_price)
     */
    public function prepareData(array $invoice): array
    {
        // Normalize field names for template
        $prepared = $invoice;
        
        // Service/Product name
        if (!isset($prepared['service_name'])) {
            $prepared['service_name'] = $prepared['package_name'] ?? $prepared['product_name'] ?? 'N/A';
        }
        
        // Service/Product price
        if (!isset($prepared['service_price'])) {
            $prepared['service_price'] = $prepared['package_price'] ?? $prepared['unit_price'] ?? 0;
        }
        
        // Invoice number should already be set correctly
        // No fallback needed for new invoices
        
        // Quantity
        if (!isset($prepared['quantity'])) {
            $prepared['quantity'] = 1;
        }
        
        // Ensure required fields
        $prepared['tax_amount'] = $prepared['tax_amount'] ?? 0;
        $prepared['subtotal'] = $prepared['subtotal'] ?? $prepared['service_price'];
        
        return $prepared;
    }
    
    public function getTitle(array $invoice): string
    {
        $invoiceNumber = $invoice['invoice_number'] ?? 'Unknown';
        return 'Invoice #' . $invoiceNumber;
    }
    
    public function getFilename(array $invoice): string
    {
        $invoiceNumber = $invoice['invoice_number'] ?? 'unknown';
        return 'invoice-' . $invoiceNumber . '.pdf';
    }
    
    public function getHTML(array $invoice): string
    {
        // Prepare data first
        $invoice = $this->prepareData($invoice);
        
        // For now, delegate to PDFService's existing generateSaasInvoiceHTML method
        // This can be refactored later to use a separate template file
        $service = new \Bloompy\Invoices\Services\PDFService();
        return $service->generateSaasInvoiceHTML($invoice);
    }
}

