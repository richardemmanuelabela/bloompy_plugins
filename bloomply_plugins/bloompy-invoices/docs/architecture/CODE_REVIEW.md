# Code Review: Bloompy Invoices - SOLID Principles & Best Practices

## Executive Summary

Overall, the invoice system demonstrates good architectural design with clear separation of concerns and adherence to most SOLID principles. However, there are several areas where improvements can be made for better maintainability, testability, and extensibility.

## SOLID Principles Analysis

### ✅ Single Responsibility Principle (SRP) - **GOOD**

**Strengths:**
- Each class has a clear, focused responsibility
- `InvoiceFactory` handles creation logic only
- `InvoiceService` acts as a facade/orchestrator
- Individual invoice types (`CustomerInvoice`, `WooCommerceInvoice`) handle type-specific logic

**Areas for Improvement:**
- `AbstractInvoice` has mixed responsibilities (data access, validation, formatting, company info retrieval)
- `InvoiceService` contains static methods that could be instance methods for better testability

### ✅ Open/Closed Principle (OCP) - **EXCELLENT**

**Strengths:**
- New invoice types can be added without modifying existing code
- Interface-based design allows extensions
- Factory pattern enables easy registration of new types

### ⚠️ Liskov Substitution Principle (LSP) - **NEEDS IMPROVEMENT**

**Issues:**
1. **Return type inconsistency in `create()` method**
   - Interface declares: `public function create(array $data);` (no return type)
   - Should be: `public function create(array $data): ?int;`

2. **Tenant ID handling inconsistency**
   - `getCurrentTenantId()` returns `int` but uses `0` for super admin
   - Should return `?int` to properly represent "no tenant"
   - Methods expecting `?int $tenantId` then convert null to 0, creating confusion

### ⚠️ Interface Segregation Principle (ISP) - **NEEDS IMPROVEMENT**

**Issues:**
1. **`InvoiceInterface` is too large (God Interface)**
   - Contains 15 different methods
   - Mixes data operations, query building, UI formatting, validation, and PDF generation
   - Not all implementations may need all methods

**Recommended split:**
```php
interface InvoiceDataInterface {
    public function create(array $data): ?int;
    public function get(int $invoiceId): ?array;
    public function update(int $invoiceId, array $data): bool;
    public function delete(int $invoiceId): bool;
}

interface InvoiceQueryInterface {
    public function getForTenant(?int $tenantId, int $limit, int $offset, string $search): array;
    public function countForTenant(?int $tenantId, string $search): int;
    public function getDataTableQuery(?int $tenantId);
}

interface InvoiceDisplayInterface {
    public function getSearchFields(): array;
    public function getDisplayColumns(): array;
    public function formatForDisplay(array $invoiceData): array;
}

interface InvoiceValidationInterface {
    public function validateData(array $data): bool;
    public function getDefaultDataStructure(): array;
}

interface InvoicePdfInterface {
    public function getPdfData(int $invoiceId): array;
}
```

### ✅ Dependency Inversion Principle (DIP) - **GOOD**

**Strengths:**
- Depends on `InvoiceInterface` abstraction, not concrete classes
- Factory pattern provides proper dependency injection

**Areas for Improvement:**
- Direct dependency on WordPress functions (`get_post`, `wp_insert_post`)
- Direct dependency on Booknetic classes (`Permission`, `Helper`)
- Should use adapters/wrappers for external dependencies

---

## Additional Best Practices

### 1. ⚠️ Static Method Overuse

**Issue:** `InvoiceService` uses all static methods

**Problems:**
- Hard to test (can't mock)
- Hard to extend
- Tight coupling
- No dependency injection

**Recommendation:**
```php
class InvoiceService
{
    private InvoiceFactory $factory;
    
    public function __construct(InvoiceFactory $factory)
    {
        $this->factory = $factory;
    }
    
    public function create(array $data, ?string $type = null): ?int
    {
        // ... implementation
    }
}
```

### 2. ⚠️ Error Handling

**Issues:**
- Errors are logged but not properly propagated
- Methods return `false` or `null` without context
- No custom exceptions

**Recommendation:**
```php
// Create custom exceptions
class InvoiceNotFoundException extends \Exception {}
class InvoiceValidationException extends \Exception {}
class InvoiceCreationException extends \Exception {}

// Use them in code
public function get(int $invoiceId): array
{
    $invoice = $this->repository->find($invoiceId);
    
    if (!$invoice) {
        throw new InvoiceNotFoundException("Invoice #{$invoiceId} not found");
    }
    
    return $invoice;
}
```

### 3. ⚠️ Type Safety

**Issues:**
- Missing return type declarations in interface
- Mixed use of `int`, `?int`, and `0` for tenant IDs
- Array return types without type hints (PHP 7.4+ supports this)

**Recommendations:**
```php
// Add strict types
declare(strict_types=1);

// Use value objects for complex data
class TenantId
{
    private ?int $value;
    
    public function __construct(?int $value)
    {
        $this->value = $value;
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->value === null;
    }
    
    public function getValue(): ?int
    {
        return $this->value;
    }
}
```

### 4. ⚠️ Separation of Concerns

**Issues:**
- `AbstractInvoice` does too much (data access, validation, formatting, company info)
- Mix of business logic and data access

**Recommendation:**
```php
// Separate repository pattern
interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): int;
    public function find(int $id): ?Invoice;
    public function findByNumber(string $number): ?Invoice;
    public function delete(int $id): bool;
}

// Separate validator
class InvoiceValidator
{
    public function validate(array $data, array $rules): ValidationResult
    {
        // validation logic
    }
}

// Separate formatter
class InvoiceFormatter
{
    public function formatForDisplay(Invoice $invoice): array
    {
        // formatting logic
    }
}
```

### 5. ⚠️ Hardcoded Dependencies

**Issues:**
- Direct calls to WordPress functions
- Direct calls to Booknetic classes
- Hard to test in isolation

**Recommendation:**
```php
// Create adapters
interface TenantProviderInterface
{
    public function getCurrentTenantId(): ?int;
    public function getTenantInfo(int $tenantId): ?array;
}

class BookneticTenantProvider implements TenantProviderInterface
{
    public function getCurrentTenantId(): ?int
    {
        if (!class_exists('BookneticApp\Providers\Core\Permission')) {
            return null;
        }
        
        return Permission::tenantId();
    }
}
```

### 6. ⚠️ Data Transfer Objects (DTOs)

**Issue:** Using raw arrays everywhere

**Recommendation:**
```php
class CreateInvoiceRequest
{
    public string $invoiceNumber;
    public ?int $tenantId;
    public string $customerEmail;
    public string $customerName;
    public float $totalAmount;
    // ... other properties
    
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->invoiceNumber = $data['invoice_number'] ?? '';
        // ... populate other fields
        return $request;
    }
}
```

### 7. ⚠️ Testing Support

**Issues:**
- Static methods make testing difficult
- No dependency injection
- Tight coupling to WordPress/Booknetic

**Recommendations:**
- Use dependency injection
- Create interfaces for external dependencies
- Add unit tests with PHPUnit

### 8. ⚠️ Magic Strings and Numbers

**Issues:**
- Hardcoded strings like `'bloompy_invoice'`, `'customer'`, `'woocommerce'`
- Magic number `0` used for super admin

**Recommendation:**
```php
class InvoiceType
{
    public const CUSTOMER = 'customer';
    public const WOOCOMMERCE = 'woocommerce';
    public const MONEYBIRD = 'moneybird';
}

class PostType
{
    public const INVOICE = 'bloompy_invoice';
}

class TenantContext
{
    public const SUPER_ADMIN = null;
}
```

### 9. ✅ Documentation - **GOOD**

**Strengths:**
- Good PHPDoc comments
- Clear method descriptions
- Implementation guides created

**Minor improvements:**
- Add `@throws` annotations
- Add examples in docblocks

---

## Priority Improvements

### HIGH PRIORITY

1. **Split Interface** - Break `InvoiceInterface` into smaller, focused interfaces
2. **Type Safety** - Add strict types and consistent return type declarations
3. **Error Handling** - Implement custom exceptions instead of returning false/null
4. **Tenant ID Handling** - Use `?int` consistently instead of mixing with `0`

### MEDIUM PRIORITY

5. **Dependency Injection** - Convert static methods to instance methods in `InvoiceService`
6. **Repository Pattern** - Separate data access from business logic
7. **Value Objects** - Create DTOs for invoice data
8. **Constants** - Replace magic strings with class constants

### LOW PRIORITY

9. **Adapters** - Create adapters for WordPress and Booknetic dependencies
10. **Unit Tests** - Add comprehensive test coverage
11. **Domain Events** - Consider adding event system for invoice lifecycle

---

## Code Smell Detection

### 1. **Long Parameter Lists**
- Some methods have many optional parameters
- Consider using parameter objects or builder pattern

### 2. **Feature Envy**
- `AbstractInvoice` accesses too much external data (Tenant data, WordPress options)
- Consider injecting these dependencies

### 3. **Shotgun Surgery**
- Changing tenant ID handling requires changes in multiple places
- Solution: Use value objects and consistent handling

### 4. **Primitive Obsession**
- Heavy use of arrays and primitive types
- Solution: Use DTOs and value objects

---

## Security Considerations

### ✅ Good Practices

1. Token generation using `wp_salt('auth')`
2. `hash_equals` for token comparison (timing-attack safe)
3. Permission checks in `canUserAccess`

### ⚠️ Areas for Improvement

1. **SQL Injection Prevention**
   - Uses WordPress functions (good)
   - But custom queries in some places should use prepared statements

2. **Input Validation**
   - Validation exists but could be more comprehensive
   - Consider using a validation library

3. **Access Control**
   - Good permission checks
   - Could be centralized in a dedicated authorization service

---

## Performance Considerations

### Issues

1. **N+1 Query Problem**
   - `formatInvoiceData` called in loops
   - Consider eager loading or caching

2. **Inefficient Counting**
   - `countForTenant` gets all posts then counts
   - Should use SQL COUNT query

3. **No Caching**
   - Frequently accessed data (company info, tenant data) not cached

### Recommendations

```php
// Add caching
class CachedInvoiceRepository implements InvoiceRepositoryInterface
{
    private InvoiceRepositoryInterface $repository;
    private CacheInterface $cache;
    
    public function find(int $id): ?Invoice
    {
        $cacheKey = "invoice:{$id}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $invoice = $this->repository->find($id);
        $this->cache->set($cacheKey, $invoice, 3600);
        
        return $invoice;
    }
}
```

---

## Architectural Recommendations

### Current Architecture (Layered)

```
Controller → Service → Factory → Concrete Types → WordPress
```

### Recommended Architecture (Clean/Hexagonal)

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (Controllers, Views, Ajax Handlers)    │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│        Application Layer                │
│  (Use Cases, Services, DTOs)            │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│          Domain Layer                   │
│  (Entities, Value Objects, Interfaces)  │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│      Infrastructure Layer               │
│  (Repositories, External Services)      │
└─────────────────────────────────────────┘
```

---

## Conclusion

The current implementation is **solid** (pun intended) with good separation of concerns and extensibility. The main areas for improvement are:

1. Interface segregation
2. Dependency injection over static methods
3. Consistent type handling
4. Better error handling with exceptions
5. Use of DTOs instead of arrays

These improvements would make the codebase more maintainable, testable, and easier to extend for future requirements like Moneybird integration.

### Estimated Refactoring Effort

- **Small refactorings** (constants, type hints): 4-6 hours
- **Medium refactorings** (interface split, DTOs): 8-12 hours
- **Large refactorings** (DI, repository pattern): 16-24 hours
- **Testing**: 8-16 hours

**Total**: 36-58 hours for complete refactoring with tests

### Immediate Action Items (Can be done incrementally)

1. Add `declare(strict_types=1);` to all PHP files
2. Create constants class for magic strings
3. Fix return type declarations in interface
4. Add custom exception classes
5. Document with `@throws` annotations


