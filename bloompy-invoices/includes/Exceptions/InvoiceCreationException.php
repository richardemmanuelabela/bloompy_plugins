<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Exceptions;

/**
 * Exception thrown when invoice creation fails
 */
class InvoiceCreationException extends InvoiceException
{
    /**
     * Create exception for creation failure
     * 
     * @param string $reason
     * @param array $context
     * @param \Throwable|null $previous
     */
    public static function because(
        string $reason,
        array $context = [],
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Failed to create invoice: {$reason}",
            $context,
            500,
            $previous
        );
    }
}



