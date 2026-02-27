<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Exceptions;

/**
 * Exception thrown when an invoice cannot be found
 */
class InvoiceNotFoundException extends InvoiceException
{
    /**
     * Create exception for missing invoice
     * 
     * @param int $invoiceId
     * @param \Throwable|null $previous
     */
    public static function forId(int $invoiceId, ?\Throwable $previous = null): self
    {
        return new self(
            "Invoice not found",
            ['invoice_id' => $invoiceId],
            404,
            $previous
        );
    }
    
    /**
     * Create exception for missing invoice by number
     * 
     * @param string $invoiceNumber
     * @param \Throwable|null $previous
     */
    public static function forNumber(string $invoiceNumber, ?\Throwable $previous = null): self
    {
        return new self(
            "Invoice not found",
            ['invoice_number' => $invoiceNumber],
            404,
            $previous
        );
    }
}



