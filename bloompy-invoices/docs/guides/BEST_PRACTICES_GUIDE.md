# Invoice System - Best Practices Guide

## Quick Reference for Developers

This guide provides practical examples of how to use the improved invoice system following SOLID principles and best practices.

---

## Table of Contents

1. [Using Constants](#using-constants)
2. [Error Handling with Exceptions](#error-handling-with-exceptions)
3. [Working with Value Objects](#working-with-value-objects)
4. [Creating Invoices](#creating-invoices)
5. [Querying Invoices](#querying-invoices)
6. [Common Patterns](#common-patterns)
7. [Testing](#testing)
8. [Migration from Old Code](#migration-from-old-code)

---

## Using Constants

### ✅ DO: Use constants from InvoiceConstants
```php
use Bloompy\Invoices\Constants\InvoiceConstants;

// Invoice types
$type = InvoiceConstants::TYPE_CUSTOMER;
$type = InvoiceConstants::TYPE_WOOCOMMERCE;

// Status
$status = InvoiceConstants::STATUS_PAID;
$status = InvoiceConstants::STATUS_PENDING;

// Validation
if (InvoiceConstants::isValidStatus($status)) {
    // Process...
}

// Get prefix by type
$prefix = InvoiceConstants::getPrefixByType($type);
```

### ❌ DON'T: Use magic strings
```php
// Bad
$type = 'customer';
$status = 'paid';

// Bad
if ($type === 'woocommerce') { ... }
```

---

## Error Handling with Exceptions

### ✅ DO: Throw specific exceptions
```php
use Bloompy\Invoices\Exceptions\{
    InvoiceNotFoundException,
    InvoiceValidationException,
    InvoiceCreationException
};

public function getInvoice(int $id): array
{
    $invoice = $this->repository->find($id);
    
    if (!$invoice) {
        throw InvoiceNotFoundException::forId($id);
    }
    
    return $invoice;
}

public function validateInvoiceData(array $data): void
{
    $errors = [];
    
    if (empty($data['customer_email'])) {
        $errors['customer_email'] = 'Email is required';
    }
    
    if (!empty($errors)) {
        throw InvoiceValidationException::withErrors($errors);
    }
}
```

### ✅ DO: Handle exceptions properly
```php
try {
    $invoice = $service->getInvoice($id);
    // Success
} catch (InvoiceNotFoundException $e) {
    // Handle not found - show 404
    wp_send_json_error(['message' => 'Invoice not found'], 404);
} catch (InvoiceValidationException $e) {
    // Handle validation errors - show 422
    wp_send_json_error([
        'message' => 'Validation failed',
        'errors' => $e->getValidationErrors()
    ], 422);
} catch (InvoiceException $e) {
    // Handle general errors - log and show 500
    error_log('Invoice error: ' . $e->getMessage());
    wp_send_json_error(['message' => 'Internal error'], 500);
}
```

### ❌ DON'T: Return false/null without context
```php
// Bad
public function getInvoice(int $id): ?array
{
    $invoice = $this->find($id);
    if (!$invoice) {
        error_log("Invoice not found");
        return null; // Lost context of why it failed
    }
    return $invoice;
}
```

---

## Working with Value Objects

### TenantId

#### ✅ DO: Use TenantId value object
```php
use Bloompy\Invoices\ValueObjects\TenantId;

// Create from nullable value
$tenantId = TenantId::fromValue(Permission::tenantId());

// Check context
if ($tenantId->isSuperAdmin()) {
    // Get all invoices
    $invoices = $service->getAllInvoices();
} else {
    // Get tenant invoices
    $invoices = $service->getInvoicesForTenant($tenantId);
}

// Use in queries
$query->where('tenant_id', $tenantId->getValue());

// For compatibility with old code expecting int
$legacyId = $tenantId->getValueAsInt(); // Returns 0 for super admin
```

#### ❌ DON'T: Use raw int with confusing null/0 logic
```php
// Bad
$tenantId = Permission::tenantId();
if (empty($tenantId)) { // Confusing: null or 0?
    // ...
}
```

### InvoiceNumber

#### ✅ DO: Use InvoiceNumber value object
```php
use Bloompy\Invoices\ValueObjects\InvoiceNumber;

// Generate new invoice number
$invoiceNumber = InvoiceNumber::generate('INV', 2025, 1);
// Result: INV-2025-0001

// Create from string
$invoiceNumber = InvoiceNumber::fromString('INV-2025-0001');

// Extract components
$year = $invoiceNumber->extractYear(); // 2025
$sequence = $invoiceNumber->extractSequence(); // 1
$prefix = $invoiceNumber->extractPrefix(); // 'INV'

// Use as string
echo $invoiceNumber; // INV-2025-0001
$data['invoice_number'] = $invoiceNumber->getValue();
```

### Money

#### ✅ DO: Use Money value object for calculations
```php
use Bloompy\Invoices\ValueObjects\Money;

// Create money
$subtotal = Money::from(100.00, 'EUR');

// Calculate tax
$taxRate = 21; // 21%
$tax = $subtotal->percentage($taxRate);

// Calculate total
$total = $subtotal->add($tax);

// Display
echo $total->format(); // "121.00 EUR"
echo $total->format(0); // "121 EUR"

// Comparisons
if ($total->greaterThan(Money::from(100.00))) {
    // ...
}

// Check if paid
$amountPaid = Money::from(121.00);
if ($total->equals($amountPaid)) {
    // Fully paid
}
```

#### ❌ DON'T: Use raw floats for money
```php
// Bad
$subtotal = 100.00;
$tax = $subtotal * 0.21; // Precision issues
$total = $subtotal + $tax; // No currency tracking
```

---

## Creating Invoices

### ✅ DO: Use Factory and Service
```php
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Constants\InvoiceConstants;

// Let the service determine the type
$invoiceId = InvoiceService::create([
    'invoice_number' => $invoiceNumber->getValue(),
    'tenant_id' => $tenantId->getValue(),
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'total_amount' => $total->getAmount(),
    'currency' => $total->getCurrency(),
    'status' => InvoiceConstants::STATUS_PAID,
    'source' => InvoiceConstants::SOURCE_BOOKNETIC,
]);

// Or specify the type explicitly
$invoiceId = InvoiceService::create($data, InvoiceConstants::TYPE_CUSTOMER);
```

### ✅ DO: Validate before creating
```php
use Bloompy\Invoices\Exceptions\InvoiceValidationException;

public function createInvoice(array $data): int
{
    // Validate
    $this->validateInvoiceData($data);
    
    // Create
    try {
        return InvoiceService::create($data);
    } catch (InvoiceCreationException $e) {
        error_log('Failed to create invoice: ' . $e->getMessage());
        throw $e;
    }
}

private function validateInvoiceData(array $data): void
{
    $errors = [];
    
    $required = ['invoice_number', 'customer_email', 'total_amount'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = "The {$field} field is required";
        }
    }
    
    if (!empty($errors)) {
        throw InvoiceValidationException::withErrors($errors);
    }
}
```

---

## Querying Invoices

### ✅ DO: Use Service methods
```php
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\ValueObjects\TenantId;

// Get single invoice
try {
    $invoice = InvoiceService::get($invoiceId);
} catch (InvoiceNotFoundException $e) {
    // Handle not found
}

// Get invoices for tenant
$tenantId = TenantId::fromValue(Permission::tenantId());
$invoices = InvoiceService::getForTenant(
    $tenantId->getValue(),
    $limit = 20,
    $offset = 0,
    $search = ''
);

// Count invoices
$count = InvoiceService::countForTenant($tenantId->getValue());

// Get by invoice number
$invoice = InvoiceService::getByInvoiceNumber($invoiceNumber->getValue());
```

---

## Common Patterns

### Pattern 1: Determining Invoice Type
```php
use Bloompy\Invoices\Factories\InvoiceFactory;
use Bloompy\Invoices\Constants\InvoiceConstants;

// By context
$type = InvoiceConstants::getTypeBySource($source);
$invoice = InvoiceFactory::create($type);

// By existing invoice
$invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
```

### Pattern 2: Permission Checking
```php
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Exceptions\InvoiceAccessDeniedException;

public function viewInvoice(int $invoiceId, ?string $customerEmail = null): array
{
    $invoice = InvoiceService::get($invoiceId);
    
    // Get invoice instance to check permissions
    $invoiceInstance = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
    
    if (!$invoiceInstance->canUserAccess($invoiceId, $customerEmail)) {
        throw InvoiceAccessDeniedException::forInvoice($invoiceId);
    }
    
    return $invoice;
}
```

### Pattern 3: Tenant-Aware Queries
```php
use Bloompy\Invoices\ValueObjects\TenantId;

public function getInvoices(): array
{
    $tenantId = TenantId::fromValue(Permission::tenantId());
    
    if ($tenantId->isSuperAdmin()) {
        // Get all invoices across all tenants
        return InvoiceService::getForTenant(null);
    }
    
    // Get only tenant's invoices
    return InvoiceService::getForTenant($tenantId->getValue());
}
```

### Pattern 4: Invoice Status Management
```php
use Bloompy\Invoices\Constants\InvoiceConstants;

public function markAsPaid(int $invoiceId): bool
{
    return InvoiceService::update($invoiceId, [
        InvoiceConstants::META_STATUS => InvoiceConstants::STATUS_PAID,
        InvoiceConstants::META_PAYMENT_DATE => current_time('mysql'),
    ]);
}

public function markAsOverdue(int $invoiceId): bool
{
    return InvoiceService::update($invoiceId, [
        InvoiceConstants::META_STATUS => InvoiceConstants::STATUS_OVERDUE,
    ]);
}
```

---

## Testing

### Unit Test Example
```php
use PHPUnit\Framework\TestCase;
use Bloompy\Invoices\ValueObjects\{TenantId, InvoiceNumber, Money};

class InvoiceCreationTest extends TestCase
{
    public function test_tenant_id_identifies_super_admin()
    {
        $tenantId = TenantId::superAdmin();
        
        $this->assertTrue($tenantId->isSuperAdmin());
        $this->assertNull($tenantId->getValue());
        $this->assertEquals(0, $tenantId->getValueAsInt());
    }
    
    public function test_invoice_number_generation()
    {
        $number = InvoiceNumber::generate('INV', 2025, 42);
        
        $this->assertEquals('INV-2025-0042', $number->getValue());
        $this->assertEquals(2025, $number->extractYear());
        $this->assertEquals(42, $number->extractSequence());
    }
    
    public function test_money_calculation()
    {
        $price = Money::from(100.00, 'EUR');
        $tax = $price->percentage(21);
        $total = $price->add($tax);
        
        $this->assertEquals(121.00, $total->getAmount());
        $this->assertEquals('EUR', $total->getCurrency());
    }
}
```

---

## Migration from Old Code

### Before (Old Code)
```php
// Using magic strings
$invoiceData = [
    'invoice_type' => 'customer',
    'status' => 'paid',
    'source' => 'booknetic',
];

// Confusing tenant ID handling
$tenantId = Permission::tenantId();
if (empty($tenantId)) {
    // Super admin
}

// Returning false on error
$invoice = Invoice::get($id);
if (!$invoice) {
    return false; // Why did it fail?
}

// Primitive calculations
$tax = $subtotal * 0.21;
$total = $subtotal + $tax;
```

### After (New Code)
```php
use Bloompy\Invoices\Constants\InvoiceConstants;
use Bloompy\Invoices\ValueObjects\{TenantId, Money};
use Bloompy\Invoices\Exceptions\InvoiceNotFoundException;

// Using constants
$invoiceData = [
    'invoice_type' => InvoiceConstants::TYPE_CUSTOMER,
    'status' => InvoiceConstants::STATUS_PAID,
    'source' => InvoiceConstants::SOURCE_BOOKNETIC,
];

// Clear tenant ID handling
$tenantId = TenantId::fromValue(Permission::tenantId());
if ($tenantId->isSuperAdmin()) {
    // Super admin
}

// Throwing exceptions
try {
    $invoice = InvoiceService::get($id);
} catch (InvoiceNotFoundException $e) {
    // Clear why it failed
}

// Type-safe calculations
$subtotal = Money::from($price, 'EUR');
$tax = $subtotal->percentage(21);
$total = $subtotal->add($tax);
```

---

## Checklist for New Code

When writing new code, ensure:

- [ ] Use `InvoiceConstants` instead of magic strings
- [ ] Use `TenantId` value object for tenant context
- [ ] Use `InvoiceNumber` for invoice numbers
- [ ] Use `Money` for monetary calculations
- [ ] Throw exceptions instead of returning false/null
- [ ] Catch specific exceptions and handle appropriately
- [ ] Use `InvoiceService` for all invoice operations
- [ ] Validate data before creating/updating
- [ ] Check permissions before showing data
- [ ] Log errors appropriately

---

## Code Review Checklist

When reviewing PRs:

- [ ] No magic strings (should use constants)
- [ ] No raw `null/0` for tenant IDs (should use TenantId)
- [ ] No `return false` without throwing exceptions
- [ ] Proper exception handling with specific catch blocks
- [ ] Money calculations use Money value object
- [ ] Invoice numbers use InvoiceNumber value object
- [ ] PHPDoc comments are present and accurate
- [ ] No hardcoded currencies, statuses, or types

---

## Performance Tips

### DO: Use bulk operations
```php
// Good: Single query
$invoiceIds = [1, 2, 3, 4, 5];
$invoices = InvoiceRepository::findByIds($invoiceIds);

// Bad: N+1 queries
foreach ($invoiceIds as $id) {
    $invoices[] = InvoiceService::get($id);
}
```

### DO: Cache expensive operations
```php
// Cache tenant info
$cacheKey = 'tenant_info_' . $tenantId->getValue();
$tenantInfo = wp_cache_get($cacheKey, InvoiceConstants::CACHE_GROUP);

if ($tenantInfo === false) {
    $tenantInfo = $this->getTenantInfo($tenantId);
    wp_cache_set(
        $cacheKey,
        $tenantInfo,
        InvoiceConstants::CACHE_GROUP,
        InvoiceConstants::CACHE_TTL
    );
}
```

---

## Security Best Practices

### DO: Always check permissions
```php
if (!$invoice->canUserAccess($invoiceId, $customerEmail)) {
    throw InvoiceAccessDeniedException::forInvoice($invoiceId);
}
```

### DO: Sanitize user input
```php
$search = sanitize_text_field($_GET['search'] ?? '');
$invoiceId = absint($_GET['invoice_id'] ?? 0);
```

### DO: Use nonces for actions
```php
if (!wp_verify_nonce($_POST['nonce'], 'delete_invoice')) {
    wp_die('Security check failed');
}
```

### DON'T: Expose internal errors to users
```php
// Bad
catch (\Exception $e) {
    echo $e->getMessage(); // Might expose SQL, file paths, etc.
}

// Good
catch (InvoiceException $e) {
    error_log($e->getMessage()); // Log full details
    wp_send_json_error(['message' => 'An error occurred']); // Generic message
}
```

---

## Quick Reference

| Task | Use This |
|------|----------|
| Invoice types/statuses | `InvoiceConstants::TYPE_*`, `::STATUS_*` |
| Tenant context | `TenantId::fromValue()` |
| Invoice numbers | `InvoiceNumber::generate()` |
| Money calculations | `Money::from()` |
| Create invoice | `InvoiceService::create()` |
| Get invoice | `InvoiceService::get()` |
| Handle errors | Catch specific exceptions |
| Validate data | Throw `InvoiceValidationException` |

---

## Getting Help

- See `CODE_REVIEW.md` for architectural overview
- See `IMPROVEMENTS_SUMMARY.md` for detailed explanations
- See `INVOICE_INTERFACE_GUIDE.md` for original implementation guide
- Check existing tests for examples

---

**Remember:** These are improvements to make the codebase more maintainable, testable, and robust. Adopt them gradually and consistently!


