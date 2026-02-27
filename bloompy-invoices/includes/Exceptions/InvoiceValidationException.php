<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Exceptions;

/**
 * Exception thrown when invoice validation fails
 */
class InvoiceValidationException extends InvoiceException
{
    private array $validationErrors = [];
    
    /**
     * Create exception with validation errors
     * 
     * @param array $errors Array of validation error messages
     * @param \Throwable|null $previous
     */
    public static function withErrors(array $errors, ?\Throwable $previous = null): self
    {
        $message = "Invoice validation failed";
        $exception = new self($message, ['errors' => $errors], 422, $previous);
        $exception->validationErrors = $errors;
        
        return $exception;
    }
    
    /**
     * Create exception for missing required field
     * 
     * @param string $field
     * @param \Throwable|null $previous
     */
    public static function missingRequiredField(string $field, ?\Throwable $previous = null): self
    {
        return self::withErrors(
            [$field => "The {$field} field is required"],
            $previous
        );
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}



