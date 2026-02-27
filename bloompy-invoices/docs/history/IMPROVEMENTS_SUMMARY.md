# Invoice System Improvements - Implementation Summary

## Overview

This document summarizes the improvements made to the Bloompy Invoices system based on SOLID principles and best practices analysis.

## Completed Improvements

### 1. ✅ Constants Class (InvoiceConstants.php)

**Problem Solved:** Magic strings and numbers scattered throughout codebase

**Implementation:**
- Created `InvoiceConstants` class with all system constants
- Includes invoice types, sources, statuses, meta keys, and more
- Added helper methods like `isValidType()`, `getPrefixByType()`, etc.
- Uses PHP 8.0 `match` expressions for cleaner code

**Benefits:**
- Single source of truth for all constants
- Easier to maintain and update
- Type-safe constant access
- Better IDE autocomplete

**Usage Example:**
```php
// Before
if ($type === 'customer') { ... }

// After
use Bloompy\Invoices\Constants\InvoiceConstants;
if ($type === InvoiceConstants::TYPE_CUSTOMER) { ... }
```

---

### 2. ✅ Custom Exception Classes

**Problem Solved:** Poor error handling with return false/null without context

**Implementation:**
Created specific exception classes:
- `InvoiceException` - Base exception with context support
- `InvoiceNotFoundException` - When invoice can't be found
- `InvoiceValidationException` - Validation failures with detailed errors
- `InvoiceCreationException` - Creation failures
- `InvoiceAccessDeniedException` - Permission denied

**Benefits:**
- Clear error types for different scenarios
- Rich error context
- Better debugging
- Easier to handle specific error cases

**Usage Example:**
```php
// Before
public function get(int $invoiceId): ?array
{
    $invoice = $this->find($invoiceId);
    if (!$invoice) {
        error_log("Invoice not found: " . $invoiceId);
        return null;
    }
    return $invoice;
}

// After
use Bloompy\Invoices\Exceptions\InvoiceNotFoundException;

public function get(int $invoiceId): array
{
    $invoice = $this->find($invoiceId);
    if (!$invoice) {
        throw InvoiceNotFoundException::forId($invoiceId);
    }
    return $invoice;
}

// In controller
try {
    $invoice = $service->get($invoiceId);
} catch (InvoiceNotFoundException $e) {
    wp_send_json_error(['message' => 'Invoice not found'], 404);
} catch (InvoiceException $e) {
    wp_send_json_error(['message' => $e->getMessage()], 500);
}
```

---

### 3. ✅ Value Objects

**Problem Solved:** Primitive obsession - using int/string for complex concepts

**Implementation:**
Created value objects for:
- `TenantId` - Encapsulates tenant context logic
- `InvoiceNumber` - Invoice number with validation
- `Money` - Monetary values with currency

**TenantId Benefits:**
- Solves confusion between `null`, `0`, and actual tenant IDs
- Provides `isSuperAdmin()`, `isTenant()` methods
- Type-safe tenant operations
- Clear intent in code

**InvoiceNumber Benefits:**
- Validation built-in
- Can extract prefix, year, sequence
- Immutable
- Type-safe

**Money Benefits:**
- Handles currency properly
- Prevents negative amounts
- Math operations (add, subtract, multiply)
- Format for display

**Usage Example:**
```php
// Before
$tenantId = Permission::tenantId();
if (empty($tenantId)) {
    // Super admin...
}

// After
use Bloompy\Invoices\ValueObjects\TenantId;

$tenantId = TenantId::fromValue(Permission::tenantId());
if ($tenantId->isSuperAdmin()) {
    // Super admin...
}

// Invoice Number
$invoiceNumber = InvoiceNumber::generate('INV', 2025, 1);
echo $invoiceNumber; // INV-2025-0001

// Money
$subtotal = Money::from(100.00, 'EUR');
$tax = $subtotal->percentage(21); // 21.00 EUR
$total = $subtotal->add($tax);    // 121.00 EUR
echo $total->format(); // 121.00 EUR
```

---

## Pending Improvements (Recommended)

### 4. ⏳ Interface Segregation

**Current Issue:** `InvoiceInterface` has 15 methods - too many responsibilities

**Recommendation:** Split into focused interfaces:
```php
interface InvoiceDataInterface {
    public function create(array $data): ?int;
    public function get(int $invoiceId): ?array;
    public function update(int $invoiceId, array $data): bool;
    public function delete(int $invoiceId): bool;
}

interface InvoiceQueryInterface {
    public function getForTenant(?int $tenantId, ...): array;
    public function countForTenant(?int $tenantId, ...): int;
}

interface InvoiceDisplayInterface {
    public function getSearchFields(): array;
    public function getDisplayColumns(): array;
    public function formatForDisplay(array $data): array;
}
```

**Benefits:**
- Classes only implement what they need
- Easier to test
- Clearer responsibilities
- Easier to add new implementations

---

### 5. ⏳ Data Transfer Objects (DTOs)

**Current Issue:** Using arrays everywhere for invoice data

**Recommendation:** Create DTOs:
```php
final class CreateInvoiceRequest
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly ?int $tenantId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly Money $totalAmount,
        public readonly string $currency = 'EUR',
        // ...
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceNumber: $data['invoice_number'],
            tenantId: $data['tenant_id'] ?? null,
            // ...
        );
    }
}

final class InvoiceData
{
    public function __construct(
        public readonly int $id,
        public readonly string $invoiceNumber,
        public readonly TenantId $tenantId,
        public readonly Money $totalAmount,
        // ...
    ) {}
}
```

**Benefits:**
- Type safety
- IDE autocomplete
- Clear data structure
- Validation in constructor
- Immutability with `readonly`

---

### 6. ⏳ Dependency Injection

**Current Issue:** Static methods everywhere, hard to test

**Recommendation:** Use instance methods with DI:
```php
class InvoiceService
{
    public function __construct(
        private readonly InvoiceFactory $factory,
        private readonly TenantProvider $tenantProvider,
        private readonly InvoiceRepository $repository
    ) {}
    
    public function create(CreateInvoiceRequest $request): InvoiceData
    {
        $invoice = $this->factory->create($request->type);
        $invoiceId = $invoice->create($request->toArray());
        return $this->repository->find($invoiceId);
    }
}
```

**Benefits:**
- Testable (can mock dependencies)
- Flexible (can swap implementations)
- Clear dependencies
- Follows SOLID principles

---

### 7. ⏳ Repository Pattern

**Current Issue:** Data access mixed with business logic

**Recommendation:** Separate repositories:
```php
interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): int;
    public function find(int $id): ?Invoice;
    public function findByNumber(InvoiceNumber $number): ?Invoice;
    public function delete(int $id): bool;
    public function count(TenantId $tenantId): int;
}

class WordPressInvoiceRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): int
    {
        // WordPress-specific implementation
        return wp_insert_post(...);
    }
}
```

**Benefits:**
- Testable (can use in-memory repository for tests)
- Can swap data storage (e.g., to custom tables)
- Decouples business logic from data access
- Easier to optimize queries

---

### 8. ⏳ Strict Types

**Current Issue:** No strict type checking

**Recommendation:** Add to all PHP files:
```php
<?php

declare(strict_types=1);

namespace Bloompy\Invoices\...;
```

**Benefits:**
- Catches type errors early
- More predictable behavior
- Better IDE support
- Prevents subtle bugs

---

### 9. ⏳ Return Type Declarations

**Current Issue:** Inconsistent return types in interface

**Recommendation:** Add explicit return types:
```php
interface InvoiceInterface
{
    public function create(array $data): ?int;  // instead of no type
    public function get(int $invoiceId): ?array;  // explicit nullable
    // ...
}
```

---

## Migration Strategy

### Phase 1: Non-Breaking Additions (COMPLETED ✅)
- ✅ Add Constants class
- ✅ Add Exception classes
- ✅ Add Value Objects
- These can be used alongside existing code

### Phase 2: Gradual Integration (RECOMMENDED)
1. Start using constants in new code
2. Start using exceptions in new methods
3. Start using value objects in new features
4. Gradually refactor existing code when touched

### Phase 3: Major Refactoring (FUTURE)
- Split interfaces
- Add DTOs
- Implement DI
- Add repository pattern
- Add comprehensive tests

---

## How to Use New Improvements

### Using Constants
```php
use Bloompy\Invoices\Constants\InvoiceConstants;

// Instead of strings
$status = InvoiceConstants::STATUS_PAID;
$type = InvoiceConstants::TYPE_CUSTOMER;

// Validation
if (!InvoiceConstants::isValidStatus($status)) {
    throw new \InvalidArgumentException("Invalid status");
}
```

### Using Exceptions
```php
use Bloompy\Invoices\Exceptions\{
    InvoiceNotFoundException,
    InvoiceValidationException,
    InvoiceCreationException
};

// Throw specific exceptions
throw InvoiceNotFoundException::forId($invoiceId);
throw InvoiceValidationException::withErrors($errors);
throw InvoiceCreationException::because('Invalid data', $context);

// Catch and handle
try {
    $invoice = $service->create($data);
} catch (InvoiceValidationException $e) {
    // Handle validation errors
    $errors = $e->getValidationErrors();
} catch (InvoiceNotFoundException $e) {
    // Handle not found
} catch (InvoiceException $e) {
    // Handle general invoice errors
}
```

### Using Value Objects
```php
use Bloompy\Invoices\ValueObjects\{TenantId, InvoiceNumber, Money};

// Tenant ID
$tenantId = TenantId::fromValue(Permission::tenantId());
if ($tenantId->isSuperAdmin()) {
    // Show all invoices
} else {
    // Filter by tenant
    $query->where('tenant_id', $tenantId->getValue());
}

// Invoice Number
$invoiceNumber = InvoiceNumber::generate('INV', 2025, 1);
$year = $invoiceNumber->extractYear(); // 2025
$prefix = $invoiceNumber->extractPrefix(); // INV

// Money calculations
$price = Money::from(100.00);
$tax = $price->percentage(21); // Calculate 21% tax
$total = $price->add($tax);
echo $total->format(); // "121.00 EUR"
```

---

## Testing Examples

With the new improvements, testing becomes easier:

```php
class InvoiceNumberTest extends TestCase
{
    public function test_generates_correct_format()
    {
        $number = InvoiceNumber::generate('INV', 2025, 1);
        $this->assertEquals('INV-2025-0001', $number->getValue());
    }
    
    public function test_extracts_year_correctly()
    {
        $number = InvoiceNumber::fromString('INV-2025-0001');
        $this->assertEquals(2025, $number->extractYear());
    }
    
    public function test_throws_on_empty_number()
    {
        $this->expectException(\InvalidArgumentException::class);
        InvoiceNumber::fromString('');
    }
}

class TenantIdTest extends TestCase
{
    public function test_super_admin_detection()
    {
        $tenantId = TenantId::superAdmin();
        $this->assertTrue($tenantId->isSuperAdmin());
        $this->assertFalse($tenantId->isTenant());
    }
    
    public function test_tenant_detection()
    {
        $tenantId = TenantId::fromTenant(5);
        $this->assertFalse($tenantId->isSuperAdmin());
        $this->assertTrue($tenantId->isTenant());
    }
}

class MoneyTest extends TestCase
{
    public function test_addition()
    {
        $a = Money::from(100.00);
        $b = Money::from(50.00);
        $result = $a->add($b);
        
        $this->assertEquals(150.00, $result->getAmount());
    }
    
    public function test_percentage_calculation()
    {
        $price = Money::from(100.00);
        $tax = $price->percentage(21);
        
        $this->assertEquals(21.00, $tax->getAmount());
    }
}
```

---

## Performance Improvements

### Before
```php
// Multiple queries in loop
foreach ($invoiceIds as $id) {
    $invoice = Invoice::get($id); // N+1 query problem
}
```

### After (with Repository)
```php
// Single query
$invoices = $repository->findByIds($invoiceIds);
```

---

## Security Improvements

### Exception Handling Prevents Information Leakage
```php
// Before - might expose sensitive info
catch (\Exception $e) {
    echo $e->getMessage(); // Could show SQL errors, file paths, etc.
}

// After - controlled error messages
catch (InvoiceNotFoundException $e) {
    echo "Invoice not found"; // Safe message
} catch (InvoiceException $e) {
    error_log($e->getMessage()); // Log full details
    echo "An error occurred"; // Generic message to user
}
```

---

## Summary

### What's Been Added

1. **Constants Class** - Centralized constants
2. **Exception Classes** - Proper error handling
3. **Value Objects** - Type-safe domain concepts

### Benefits Achieved

- ✅ Better code organization
- ✅ Type safety
- ✅ Clearer intent
- ✅ Easier to test
- ✅ Better error handling
- ✅ More maintainable

### Next Steps (Optional but Recommended)

1. Gradually adopt constants in existing code
2. Start using exceptions instead of returning false/null
3. Use value objects in new features
4. Consider interface segregation for future types
5. Add DTOs for type-safe data transfer
6. Implement repository pattern for better testability

---

## Backward Compatibility

All improvements are **backward compatible**:
- New classes don't break existing code
- Can be adopted gradually
- Existing functionality continues to work
- No database changes required

The old code can coexist with the new patterns while you migrate incrementally.


