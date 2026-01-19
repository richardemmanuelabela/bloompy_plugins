<?php

declare(strict_types=1);

namespace Bloompy\Invoices\ValueObjects;

use Bloompy\Invoices\Constants\InvoiceConstants;

/**
 * Value Object for Tenant ID
 * 
 * Encapsulates tenant ID logic and provides a type-safe way to work with tenant context.
 * This solves the confusion between null, 0, and actual tenant IDs.
 */
final class TenantId
{
    private ?int $value;
    
    /**
     * Create a tenant ID
     * 
     * @param int|null $value Null represents super admin context
     */
    private function __construct(?int $value)
    {
        if ($value !== null && $value < 0) {
            throw new \InvalidArgumentException("Tenant ID must be null or a non-negative integer");
        }
        
        $this->value = $value;
    }
    
    /**
     * Create tenant ID from value (handles both int and string from Booknetic)
     * 
     * @param int|string|null|false $value
     * @return self
     */
    public static function fromValue($value): self
    {
        // Handle various return types from Permission::tenantId()
        if ($value === null || $value === false || $value === '') {
            return new self(null);
        }
        
        // Convert string to int if necessary
        $intValue = is_numeric($value) ? (int)$value : null;
        
        return new self($intValue);
    }
    
    /**
     * Create super admin tenant context
     * 
     * @return self
     */
    public static function superAdmin(): self
    {
        return new self(null);
    }
    
    /**
     * Create from tenant value
     * 
     * @param int $value
     * @return self
     */
    public static function fromTenant(int $value): self
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("Tenant ID must be positive");
        }
        
        return new self($value);
    }
    
    /**
     * Get the value
     * 
     * @return int|null
     */
    public function getValue(): ?int
    {
        return $this->value;
    }
    
    /**
     * Get value as integer (0 for super admin)
     * For compatibility with old code that uses 0 for super admin
     * 
     * @return int
     */
    public function getValueAsInt(): int
    {
        return $this->value ?? InvoiceConstants::TENANT_SUPER_ADMIN;
    }
    
    /**
     * Check if this is super admin context
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->value === null;
    }
    
    /**
     * Check if this is a tenant context
     * 
     * @return bool
     */
    public function isTenant(): bool
    {
        return $this->value !== null && $this->value > 0;
    }
    
    /**
     * Check if equals another tenant ID
     * 
     * @param TenantId $other
     * @return bool
     */
    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function toString(): string
    {
        return $this->value !== null ? (string)$this->value : 'super_admin';
    }
    
    /**
     * String representation
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}


