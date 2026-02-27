<?php

declare(strict_types=1);

namespace Bloompy\Invoices\ValueObjects;

use Bloompy\Invoices\Constants\InvoiceConstants;

/**
 * Value Object for Money/Amount
 * 
 * Handles monetary values with proper precision and currency.
 */
final class Money
{
    private float $amount;
    private string $currency;
    
    /**
     * Create a money value
     * 
     * @param float $amount
     * @param string $currency
     */
    private function __construct(float $amount, string $currency)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative");
        }
        
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }
    
    /**
     * Create money from amount and currency
     * 
     * @param float $amount
     * @param string $currency
     * @return self
     */
    public static function from(float $amount, string $currency = InvoiceConstants::DEFAULT_CURRENCY): self
    {
        return new self($amount, $currency);
    }
    
    /**
     * Create zero money
     * 
     * @param string $currency
     * @return self
     */
    public static function zero(string $currency = InvoiceConstants::DEFAULT_CURRENCY): self
    {
        return new self(0.0, $currency);
    }
    
    /**
     * Get the amount
     * 
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    /**
     * Get the currency
     * 
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    /**
     * Add another money value
     * 
     * @param Money $other
     * @return self
     * @throws \InvalidArgumentException
     */
    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }
    
    /**
     * Subtract another money value
     * 
     * @param Money $other
     * @return self
     * @throws \InvalidArgumentException
     */
    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);
        
        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new \InvalidArgumentException("Result cannot be negative");
        }
        
        return new self($result, $this->currency);
    }
    
    /**
     * Multiply by a factor
     * 
     * @param float $factor
     * @return self
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException("Factor cannot be negative");
        }
        
        return new self($this->amount * $factor, $this->currency);
    }
    
    /**
     * Calculate percentage
     * 
     * @param float $percentage
     * @return self
     */
    public function percentage(float $percentage): self
    {
        return $this->multiply($percentage / 100);
    }
    
    /**
     * Check if equals another money value
     * 
     * @param Money $other
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return abs($this->amount - $other->amount) < 0.01 && $this->currency === $other->currency;
    }
    
    /**
     * Check if greater than another money value
     * 
     * @param Money $other
     * @return bool
     */
    public function greaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount > $other->amount;
    }
    
    /**
     * Check if less than another money value
     * 
     * @param Money $other
     * @return bool
     */
    public function lessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount < $other->amount;
    }
    
    /**
     * Check if zero
     * 
     * @return bool
     */
    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }
    
    /**
     * Format for display
     * 
     * @param int $decimals
     * @return string
     */
    public function format(int $decimals = 2): string
    {
        return number_format($this->amount, $decimals, '.', ',') . ' ' . $this->currency;
    }
    
    /**
     * Ensure same currency
     * 
     * @param Money $other
     * @throws \InvalidArgumentException
     */
    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Cannot operate on different currencies");
        }
    }
    
    /**
     * String representation
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }
}


