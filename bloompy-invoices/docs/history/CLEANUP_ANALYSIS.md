# Cleanup Analysis - Unused Files & Code

## Executive Summary

Analysis of the Bloompy Invoices codebase to identify unused files and refactoring opportunities.

## Files Analysis

### ✅ DELETED - Confirmed Unused

1. **NewController.php** ❌ DELETED
   - Was never used in routing
   - Duplicate of Controller.php functionality
   - Used outdated patterns

### ⚠️ KEEP - Still Used (But Needs Refactoring)

#### 1. **Models/Invoice.php** - PARTIALLY USED
**Status:** OLD MODEL - Being gradually phased out

**What's Still Used:**
- ✅ `Invoice::registerPostType()` - Called in InvoicesAddon.php line 56
  - **Purpose:** Registers the `bloompy_invoice` custom post type
  - **Usage:** `add_action('init', [Models\Invoice::class, 'registerPostType']);`
  - **Needed:** YES - This is still required

- ❌ `Invoice::get()` - Called in CustomerInvoice.php and WooCommerceInvoice.php
  - **Purpose:** Fetch invoice data for DataTable display
  - **Usage:** Inside anonymous DataTableQuery classes
  - **Problem:** Should use new interface methods instead
  - **Status:** NEEDS REFACTORING

**What's NOT Used:**
- `Invoice::create()` - Replaced by InvoiceService::create()
- `Invoice::update()` - Replaced by InvoiceService::update()
- `Invoice::delete()` - Replaced by InvoiceService::delete()
- `Invoice::getForTenant()` - Replaced by concrete implementations
- `Invoice::generateInvoiceNumber()` - Replaced by InvoiceService::generateInvoiceNumber()
- All other methods - Replaced by new architecture

**Refactoring Plan:**
1. Extract `registerPostType()` to a separate class (e.g., `PostTypeRegistrar`)
2. Update DataTableQuery classes to use `$this->get()` instead of `Invoice::get()`
3. Delete the entire Invoice.php model

**Files Affected:**
- includes/Models/Invoice.php (856 lines)
- includes/InvoicesAddon.php (line 56)
- includes/Types/CustomerInvoice.php (lines 427, 437, 445, 485)
- includes/Types/WooCommerceInvoice.php (lines 474, 484, 492, 553)

### 🧪 TEST FILES - Should Be Removed in Production

#### 1. **test-invoice.php** - DEVELOPMENT ONLY
**Status:** Test file for old Invoice model

**Purpose:**
- Demonstrates old Invoice::create() usage
- Tests old Invoice model methods
- Creates sample invoices

**Issues:**
- Uses OLD Invoice model (not new interface)
- Outdated examples
- Should be updated or removed

**Recommendation:** 
- DELETE or UPDATE to use new InvoiceService
- Keep in .gitignore for production

#### 2. **test-integration.php** - DEVELOPMENT ONLY
**Status:** Test file for NEW invoice system

**Purpose:**
- Tests InvoiceService and InvoiceFactory
- Validates new architecture
- Good for development

**Recommendation:** 
- KEEP for development
- Should be in .gitignore for production
- Consider moving to proper PHPUnit tests

## Summary of Actions Needed

### Immediate Actions (Breaking Dependencies)

#### 1. Create PostTypeRegistrar Class
```php
// includes/Utilities/PostTypeRegistrar.php
namespace Bloompy\Invoices\Utilities;

class PostTypeRegistrar
{
    public static function register(): void
    {
        register_post_type(InvoiceConstants::POST_TYPE, [
            // ... args ...
        ]);
    }
}
```

#### 2. Update InvoicesAddon.php
```php
// Change line 56 from:
add_action('init', [Models\Invoice::class, 'registerPostType']);

// To:
add_action('init', [Utilities\PostTypeRegistrar::class, 'register']);
```

#### 3. Fix DataTableQuery Classes

**In CustomerInvoice.php and WooCommerceInvoice.php:**

Change from:
```php
$invoice = \Bloompy\Invoices\Models\Invoice::get($id);
```

To:
```php
// Get from parent class context
$post = get_post($id);
if ($post) {
    $invoice = $this->formatInvoiceData($post);
}
```

OR create a helper method in the parent scope:
```php
// In CustomerInvoice::getDataTableQuery()
$parentInstance = $this;

return new class($tenant_id, $parentInstance) implements ... {
    private $parent;
    
    public function __construct($tenant_id, $parent) {
        $this->parent = $parent;
        // ...
    }
    
    public function getResults() {
        foreach ($ids as $id) {
            $invoices[] = $this->parent->get($id); // Use parent's method
        }
    }
};
```

#### 4. Delete Old Invoice Model
Once steps 1-3 are complete:
```bash
rm includes/Models/Invoice.php
```

### Test Files

#### Option A: Update test-invoice.php
```php
// Update to use new InvoiceService
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Constants\InvoiceConstants;

$invoiceData = [
    'invoice_number' => InvoiceService::generateInvoiceNumber(InvoiceConstants::TYPE_CUSTOMER),
    // ... other data
];

$invoiceId = InvoiceService::create($invoiceData, InvoiceConstants::TYPE_CUSTOMER);
```

#### Option B: Delete Test Files
```bash
rm test-invoice.php
rm test-integration.php
```

Add to .gitignore:
```
test-*.php
```

## Impact Analysis

### Files to Delete
- ✅ **NewController.php** (DONE)
- ⏳ **Models/Invoice.php** (After refactoring)
- 🤔 **test-invoice.php** (Optional - update or delete)
- 🤔 **test-integration.php** (Optional - keep for dev)

### Files to Create
- 📝 **Utilities/PostTypeRegistrar.php** (Extract from Invoice model)

### Files to Modify
- 📝 **InvoicesAddon.php** (Change registerPostType call)
- 📝 **Types/CustomerInvoice.php** (Fix DataTableQuery Invoice::get calls)
- 📝 **Types/WooCommerceInvoice.php** (Fix DataTableQuery Invoice::get calls)

### LOC (Lines of Code) Impact
- **Delete:** ~900 lines (Invoice.php: 856 + NewController.php: 336 already deleted)
- **Add:** ~50 lines (PostTypeRegistrar.php)
- **Modify:** ~20 lines (various fixes)
- **Net Reduction:** ~850 lines 📉

## Priority

### High Priority (Blocking)
1. ✅ Remove NewController.php (DONE)
2. ⏳ Create PostTypeRegistrar
3. ⏳ Update InvoicesAddon.php
4. ⏳ Fix DataTableQuery Invoice::get() calls
5. ⏳ Delete Models/Invoice.php

### Medium Priority
6. Update or delete test files
7. Add proper PHPUnit tests

### Low Priority
8. Clean up documentation files (consider consolidating)

## Risk Assessment

| Action | Risk Level | Impact | Mitigation |
|--------|-----------|--------|------------|
| Delete NewController.php | ✅ None | None | Already unused |
| Create PostTypeRegistrar | 🟡 Low | Low | Simple extraction |
| Fix DataTableQuery | 🟡 Low | Medium | Test invoice listing |
| Delete Invoice.php | 🟠 Medium | High | Thorough testing required |
| Update test files | 🟢 None | Low | Development only |

## Testing Checklist

Before deleting Invoice.php, verify:
- [ ] Invoice list displays correctly (Customer invoices)
- [ ] Invoice list displays correctly (WooCommerce invoices)
- [ ] Search functionality works
- [ ] Filters work
- [ ] Invoice creation works
- [ ] Invoice viewing works
- [ ] Invoice deletion works
- [ ] PDF generation works
- [ ] Custom post type is registered
- [ ] No PHP errors in logs

## Estimated Effort

- **PostTypeRegistrar Creation:** 15 minutes
- **DataTableQuery Fixes:** 45 minutes
- **Testing:** 30 minutes
- **Cleanup:** 15 minutes
- **Total:** ~2 hours

## Conclusion

The old `Models/Invoice.php` is the main technical debt. After extracting `registerPostType()` and fixing DataTableQuery dependencies, we can safely delete 856 lines of legacy code.

This will:
- ✅ Reduce codebase size
- ✅ Eliminate confusion about which Invoice class to use
- ✅ Improve maintainability
- ✅ Complete the migration to new architecture

---

**Last Updated:** 2025-10-20
**Status:** Analysis Complete - Ready for Implementation

