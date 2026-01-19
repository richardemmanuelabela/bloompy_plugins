<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Exceptions;

/**
 * Base exception for all invoice-related errors
 */
class InvoiceException extends \Exception
{
    /**
     * Create exception with context
     * 
     * @param string $message
     * @param array $context Additional context information
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (!empty($context)) {
            $contextStr = json_encode($context);
            $message .= " | Context: {$contextStr}";
        }
        
        parent::__construct($message, $code, $previous);
    }
}



