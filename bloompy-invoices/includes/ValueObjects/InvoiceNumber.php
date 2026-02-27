<?php

declare(strict_types=1);

namespace Bloompy\Invoices\ValueObjects;

use Bloompy\Invoices\Constants\InvoiceConstants;

/**
 * Value Object for Invoice Number
 * 
 * Encapsulates invoice number logic and validation.
 */
final class InvoiceNumber
{
    private string $value;
    
    /**
     * Create an invoice number
     * 
     * @param string $value
     */
    private function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException("Invoice number cannot be empty");
        }
        
        if (strlen($value) > InvoiceConstants::MAX_INVOICE_NUMBER_LENGTH) {
            throw new \InvalidArgumentException(
                "Invoice number cannot exceed " . InvoiceConstants::MAX_INVOICE_NUMBER_LENGTH . " characters"
            );
        }
        
        $this->value = $value;
    }
    
    /**
     * Create invoice number from string
     * 
     * @param string $value
     * @return self
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }
    
    /**
     * Generate new invoice number
     * 
     * @param string $prefix
     * @param int $year
     * @param int $sequence
     * @return self
     */
    public static function generate(string $prefix, int $year, int $sequence): self
    {
        $value = sprintf('%s-%d-%04d', $prefix, $year, $sequence);
        return new self($value);
    }
    
    /**
     * Get the value
     * 
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    
    /**
     * Check if equals another invoice number
     * 
     * @param InvoiceNumber $other
     * @return bool
     */
    public function equals(InvoiceNumber $other): bool
    {
        return $this->value === $other->value;
    }
    
    /**
     * Extract year from invoice number if formatted as PREFIX-YEAR-SEQUENCE
     * 
     * @return int|null
     */
    public function extractYear(): ?int
    {
        $parts = explode('-', $this->value);
        
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            return (int)$parts[1];
        }
        
        return null;
    }
    
    /**
     * Extract sequence from invoice number if formatted as PREFIX-YEAR-SEQUENCE
     * 
     * @return int|null
     */
    public function extractSequence(): ?int
    {
        $parts = explode('-', $this->value);
        
        if (count($parts) >= 3 && is_numeric($parts[2])) {
            return (int)$parts[2];
        }
        
        return null;
    }
    
    /**
     * Extract prefix from invoice number if formatted as PREFIX-YEAR-SEQUENCE
     * 
     * @return string|null
     */
    public function extractPrefix(): ?string
    {
        $parts = explode('-', $this->value);
        
        return $parts[0] ?? null;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }
    
    /**
     * String representation
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}


