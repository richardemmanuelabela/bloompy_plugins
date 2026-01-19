# Directory Restructure Plan

## Current Structure Analysis

### Issues with Current Structure

1. **`Abstract/` folder** - Only contains one file, could be integrated better
2. **`Models/` folder** - Now empty after deleting Invoice.php
3. **`Types/` folder** - Name could be more descriptive (e.g., "InvoiceTypes")
4. **`Backend/` folder** - Mix of controllers and views, could be better organized
5. **`Frontend/` folder** - Only one file, could be consolidated
6. **Root-level files** - `InvoicesAddon.php` and `Listener.php` in includes/
7. **`Utilities/` folder** - Good start, but could consolidate more utility classes

## Proposed New Structure

### Option A: Domain-Driven Design (Recommended)

```
includes/
├── Core/                          # Core business logic
│   ├── InvoicesAddon.php         # Main addon class (moved from root)
│   ├── Listener.php               # Event listener (moved from root)
│   └── AbstractInvoice.php        # Base invoice class (moved from Abstract/)
│
├── Domain/                        # Domain layer (business entities)
│   ├── Invoice/
│   │   ├── Types/                 # Invoice type implementations
│   │   │   ├── CustomerInvoice.php
│   │   │   └── WooCommerceInvoice.php
│   │   ├── InvoiceInterface.php   # Interface (moved from Interfaces/)
│   │   └── InvoiceFactory.php     # Factory (moved from Factories/)
│   │
│   ├── ValueObjects/              # Value objects
│   │   ├── TenantId.php
│   │   ├── InvoiceNumber.php
│   │   └── Money.php
│   │
│   └── Constants/                 # Domain constants
│       └── InvoiceConstants.php
│
├── Application/                   # Application layer (services)
│   ├── Services/
│   │   ├── InvoiceService.php
│   │   └── PDFService.php
│   │
│   └── Exceptions/                # Application exceptions
│       ├── InvoiceException.php
│       ├── InvoiceNotFoundException.php
│       ├── InvoiceValidationException.php
│       ├── InvoiceCreationException.php
│       └── InvoiceAccessDeniedException.php
│
├── Infrastructure/                # Infrastructure layer
│   ├── Persistence/
│   │   └── PostTypeRegistrar.php  # WordPress CPT registration
│   │
│   └── Integrations/              # External integrations
│       └── WooCommerceHooks.php   # WooCommerce integration
│
├── Presentation/                  # Presentation layer (UI)
│   ├── Admin/                     # Backend admin interface
│   │   ├── Controllers/
│   │   │   ├── InvoiceController.php  # Renamed from Controller.php
│   │   │   ├── SettingsController.php
│   │   │   └── AjaxController.php     # Renamed from Ajax.php
│   │   │
│   │   └── Views/
│   │       ├── invoices.php
│   │       ├── settings.php
│   │       └── settings_booknetic.php
│   │
│   └── Public/                    # Frontend/public interface
│       ├── Controllers/
│       │   └── InvoiceViewController.php  # Renamed from InvoiceViewer.php
│       │
│       └── Templates/
│           └── invoice-view.php   # Moved from templates/
│
└── autoloader.php                 # Class autoloader
```

### Option B: Simplified Clean Architecture

```
includes/
├── Application/                   # Application core
│   ├── InvoicesAddon.php
│   ├── Listener.php
│   └── Services/
│       ├── InvoiceService.php
│       └── PDFService.php
│
├── Domain/                        # Domain models
│   ├── Invoices/
│   │   ├── AbstractInvoice.php
│   │   ├── InvoiceInterface.php
│   │   ├── InvoiceFactory.php
│   │   ├── CustomerInvoice.php
│   │   └── WooCommerceInvoice.php
│   │
│   ├── ValueObjects/
│   │   ├── TenantId.php
│   │   ├── InvoiceNumber.php
│   │   └── Money.php
│   │
│   └── Exceptions/
│       ├── InvoiceException.php
│       ├── InvoiceNotFoundException.php
│       ├── InvoiceValidationException.php
│       ├── InvoiceCreationException.php
│       └── InvoiceAccessDeniedException.php
│
├── Infrastructure/
│   ├── WordPress/
│   │   └── PostTypeRegistrar.php
│   │
│   └── WooCommerce/
│       └── WooCommerceHooks.php
│
├── Presentation/
│   ├── Controllers/
│   │   ├── InvoiceController.php
│   │   ├── AjaxController.php
│   │   ├── SettingsController.php
│   │   └── PublicInvoiceController.php
│   │
│   └── Views/
│       ├── admin/
│       │   ├── invoices.php
│       │   ├── settings.php
│       │   └── settings_booknetic.php
│       │
│       └── public/
│           └── invoice-view.php
│
├── Support/                       # Support utilities
│   └── Constants/
│       └── InvoiceConstants.php
│
└── autoloader.php
```

### Option C: Minimal Restructure (Least Disruptive)

```
includes/
├── Core/                          # Core functionality
│   ├── InvoicesAddon.php         # Main addon (moved up)
│   ├── Listener.php               # Event listener (moved up)
│   ├── AbstractInvoice.php        # Base class (from Abstract/)
│   └── InvoiceInterface.php       # Interface (from Interfaces/)
│
├── InvoiceTypes/                  # Invoice implementations (renamed from Types/)
│   ├── CustomerInvoice.php
│   └── WooCommerceInvoice.php
│
├── Services/                      # Application services
│   ├── InvoiceService.php
│   ├── InvoiceFactory.php         # Moved from Factories/
│   └── PDFService.php
│
├── Http/                          # HTTP layer (renamed from Backend/)
│   ├── Controllers/
│   │   ├── InvoiceController.php  # Renamed from Controller.php
│   │   ├── AjaxController.php     # Renamed from Ajax.php
│   │   └── SettingsController.php
│   │
│   └── Views/                     # Renamed from view/
│       ├── invoices.php
│       ├── settings.php
│       └── settings_booknetic.php
│
├── Public/                        # Public facing (renamed from Frontend/)
│   ├── InvoiceViewController.php  # Renamed from InvoiceViewer.php
│   └── Templates/
│       └── invoice-view.php
│
├── Support/                       # Support classes
│   ├── Constants/
│   │   └── InvoiceConstants.php
│   │
│   ├── Exceptions/
│   │   ├── InvoiceException.php
│   │   ├── InvoiceNotFoundException.php
│   │   ├── InvoiceValidationException.php
│   │   ├── InvoiceCreationException.php
│   │   └── InvoiceAccessDeniedException.php
│   │
│   ├── ValueObjects/
│   │   ├── TenantId.php
│   │   ├── InvoiceNumber.php
│   │   └── Money.php
│   │
│   └── WordPress/
│       └── PostTypeRegistrar.php
│
├── Integrations/                  # External integrations (renamed from Hooks/)
│   └── WooCommerceHooks.php
│
└── autoloader.php
```

## Comparison Matrix

| Aspect | Option A (DDD) | Option B (Clean) | Option C (Minimal) |
|--------|---------------|------------------|-------------------|
| **Complexity** | High | Medium | Low |
| **Learning Curve** | Steep | Moderate | Easy |
| **Scalability** | Excellent | Good | Good |
| **Maintainability** | Excellent | Very Good | Good |
| **Migration Effort** | High | Medium | Low |
| **Team Size** | Large teams | Medium teams | Small teams |
| **Best For** | Enterprise apps | Growing apps | Small/medium plugins |

## Recommendations

### For This Project: **Option C - Minimal Restructure**

**Why:**
1. ✅ **Lower risk** - Less moving parts
2. ✅ **Faster migration** - Can be done incrementally
3. ✅ **Easier to understand** - Clear folder names
4. ✅ **Good enough** - Solves current issues
5. ✅ **WordPress-friendly** - Follows plugin conventions
6. ✅ **Team-friendly** - Easy for new developers

**What It Solves:**
- ❌ Removes empty `Models/` folder
- ❌ Removes single-file `Abstract/` folder
- ❌ Removes single-file `Interfaces/` folder
- ❌ Removes single-file `Factories/` folder
- ✅ Groups related files logically
- ✅ Clearer naming conventions
- ✅ Better separation of concerns

## Implementation Plan

### Phase 1: Consolidation (Low Risk)
1. Move `AbstractInvoice.php` and `InvoiceInterface.php` to `Core/`
2. Move `InvoicesAddon.php` and `Listener.php` to `Core/`
3. Delete empty `Models/`, `Abstract/`, `Interfaces/` folders
4. Rename `Types/` to `InvoiceTypes/`

### Phase 2: Services Consolidation (Low Risk)
1. Move `InvoiceFactory.php` to `Services/`
2. Keep other services in `Services/`

### Phase 3: HTTP Layer (Medium Risk)
1. Rename `Backend/` to `Http/`
2. Create `Http/Controllers/` subfolder
3. Move controllers to `Http/Controllers/`
4. Rename controllers for clarity
5. Rename `view/` to `Views/`

### Phase 4: Support Layer (Low Risk)
1. Create `Support/` folder
2. Move `Constants/`, `Exceptions/`, `ValueObjects/` to `Support/`
3. Create `Support/WordPress/` for WP-specific utilities
4. Move `PostTypeRegistrar.php` to `Support/WordPress/`

### Phase 5: Cleanup (Low Risk)
1. Rename `Frontend/` to `Public/`
2. Rename `Hooks/` to `Integrations/`
3. Update all namespace references
4. Update autoloader
5. Test everything

## Namespace Changes Required

### Before:
```php
namespace Bloompy\Invoices\Abstract;
namespace Bloompy\Invoices\Backend;
namespace Bloompy\Invoices\Factories;
namespace Bloompy\Invoices\Frontend;
namespace Bloompy\Invoices\Hooks;
namespace Bloompy\Invoices\Interfaces;
namespace Bloompy\Invoices\Types;
namespace Bloompy\Invoices\Utilities;
```

### After (Option C):
```php
namespace Bloompy\Invoices\Core;
namespace Bloompy\Invoices\Http\Controllers;
namespace Bloompy\Invoices\InvoiceTypes;
namespace Bloompy\Invoices\Integrations;
namespace Bloompy\Invoices\Public;
namespace Bloompy\Invoices\Services;
namespace Bloompy\Invoices\Support\Constants;
namespace Bloompy\Invoices\Support\Exceptions;
namespace Bloompy\Invoices\Support\ValueObjects;
namespace Bloompy\Invoices\Support\WordPress;
```

## Files to Update

All files with `use` statements will need namespace updates:
- InvoicesAddon.php
- All Controllers
- InvoiceService.php
- InvoiceFactory.php
- All concrete invoice types
- Listener.php
- All tests (when created)

## Benefits After Restructure

### Clarity
- ✅ `Core/` - Essential app logic
- ✅ `InvoiceTypes/` - Clear what's inside
- ✅ `Http/Controllers/` - Standard MVC pattern
- ✅ `Support/` - Helper classes
- ✅ `Integrations/` - External systems

### Organization
- ✅ Related files grouped together
- ✅ No single-file folders
- ✅ No empty folders
- ✅ Consistent naming

### Scalability
- ✅ Easy to add new invoice types
- ✅ Easy to add new integrations
- ✅ Easy to add new value objects
- ✅ Clear where new files go

### Developer Experience
- ✅ New developers find files easily
- ✅ Clear separation of concerns
- ✅ Follows common patterns
- ✅ Self-documenting structure

## Risk Mitigation

### Low Risk Changes (Do First)
- Creating new folders
- Moving files within same layer
- Renaming folders (with namespace updates)

### Medium Risk Changes (Test Thoroughly)
- Updating namespaces
- Moving files between layers
- Renaming files

### High Risk Changes (Do Last, Test Extensively)
- Changing public APIs
- Modifying autoloader
- Refactoring class dependencies

## Testing Strategy

After each phase:
1. ✅ Run PHP syntax checks
2. ✅ Test invoice list view
3. ✅ Test invoice creation
4. ✅ Test PDF generation
5. ✅ Check error logs
6. ✅ Test in both SaaS and standalone
7. ✅ Test WooCommerce integration

## Timeline Estimate

- **Phase 1:** 30 minutes
- **Phase 2:** 15 minutes
- **Phase 3:** 45 minutes
- **Phase 4:** 30 minutes
- **Phase 5:** 30 minutes
- **Testing:** 60 minutes
- **Total:** ~3.5 hours

## Rollback Strategy

1. Keep git commits small (one phase per commit)
2. Test after each phase
3. If issues arise, roll back the last commit
4. Fix issues before proceeding

## Conclusion

**Recommendation: Proceed with Option C - Minimal Restructure**

It provides the best balance of:
- ✅ Improved organization
- ✅ Minimal disruption
- ✅ Clear structure
- ✅ Future-proof design
- ✅ Easy to implement

The restructure will make the codebase more professional, easier to navigate, and better organized without introducing unnecessary complexity.

---

**Decision:** Ready to implement Option C?
**Estimated Time:** 3-4 hours
**Risk Level:** Low to Medium
**Impact:** High positive

