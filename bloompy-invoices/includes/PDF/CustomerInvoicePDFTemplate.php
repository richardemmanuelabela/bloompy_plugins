<?php

declare(strict_types=1);

namespace Bloompy\Invoices\PDF;

/**
 * PDF template for Booknetic customer invoices (appointments)
 * 
 * Wraps the existing customer invoice template
 */
class CustomerInvoicePDFTemplate implements InvoicePDFTemplate
{
    public function prepareData(array $invoice): array
    {
        // Customer invoices already have the correct field names
        return $invoice;
    }
    
    public function getTitle(array $invoice): string
    {
        return 'Invoice #' . ($invoice['invoice_number'] ?? 'Unknown');
    }
    
    public function getFilename(array $invoice): string
    {
        return 'invoice-' . ($invoice['invoice_number'] ?? 'unknown') . '.pdf';
    }
    
    public function getHTML(array $invoice): string
    {
        // For now, delegate to PDFService's existing method
        // This can be refactored later to use a separate template file
        $service = new \Bloompy\Invoices\Services\PDFService();
        return $service->generateInvoiceHTML($invoice);
    }
}

