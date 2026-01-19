<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Exceptions;

/**
 * Exception thrown when access to an invoice is denied
 */
class InvoiceAccessDeniedException extends InvoiceException
{
    /**
     * Create exception for access denied
     * 
     * @param int $invoiceId
     * @param string|null $userId
     * @param \Throwable|null $previous
     */
    public static function forInvoice(
        int $invoiceId,
        ?string $userId = null,
        ?\Throwable $previous = null
    ): self {
        $context = ['invoice_id' => $invoiceId];
        
        if ($userId) {
            $context['user_id'] = $userId;
        }
        
        return new self(
            "Access denied to invoice",
            $context,
            403,
            $previous
        );
    }
}



