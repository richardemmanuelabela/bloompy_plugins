<?php

declare(strict_types=1);

namespace Bloompy\Invoices\PDF;

/**
 * Interface for invoice PDF templates
 * 
 * Each invoice type implements its own template
 */
interface InvoicePDFTemplate
{
    /**
     * Get the HTML content for the PDF
     * 
     * @param array $invoice Invoice data
     * @return string HTML content
     */
    public function getHTML(array $invoice): string;
    
    /**
     * Get the PDF title
     * 
     * @param array $invoice Invoice data
     * @return string PDF title
     */
    public function getTitle(array $invoice): string;
    
    /**
     * Get the PDF filename
     * 
     * @param array $invoice Invoice data
     * @return string PDF filename
     */
    public function getFilename(array $invoice): string;
    
    /**
     * Prepare invoice data for the template
     * Normalizes field names and adds any computed values
     * 
     * @param array $invoice Raw invoice data
     * @return array Prepared invoice data
     */
    public function prepareData(array $invoice): array;
}

