<?php

declare(strict_types=1);

namespace Bloompy\Invoices\PDF;

/**
 * Factory for creating the appropriate PDF template based on invoice type
 */
class InvoicePDFTemplateFactory
{
    /**
     * Get the appropriate PDF template for an invoice
     * 
     * @param array $invoice Invoice data
     * @return InvoicePDFTemplate
     */
    public static function create(array $invoice): InvoicePDFTemplate
    {
        // Determine invoice type based on source field
        $source = $invoice['source'] ?? '';
        
        // Check if this is a WooCommerce invoice
        if ($source === 'woocommerce') {
            return new WooCommerceInvoicePDFTemplate();
        }
        
        // Default to customer invoice template (Booknetic appointments)
        return new CustomerInvoicePDFTemplate();
    }
}

