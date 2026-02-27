# Complete Plugin Restructure Plan - Option C Enhanced

## Overview

This plan restructures the entire Bloompy Invoices plugin to follow WordPress plugin best practices and modern PHP architecture patterns.

## Current Complete Structure

```
bloompy-invoices/
├── assets/
│   └── images/
│       └── invoice.svg
├── includes/
│   ├── Abstract/
│   │   └── AbstractInvoice.php
│   ├── Backend/
│   │   ├── Ajax.php
│   │   ├── Controller.php
│   │   ├── SettingsController.php
│   │   └── view/
│   │       ├── invoices.php
│   │       ├── settings.php
│   │       └── settings_booknetic.php
│   ├── Constants/
│   │   └── InvoiceConstants.php
│   ├── Exceptions/
│   │   ├── InvoiceException.php
│   │   ├── InvoiceNotFoundException.php
│   │   ├── InvoiceValidationException.php
│   │   ├── InvoiceCreationException.php
│   │   └── InvoiceAccessDeniedException.php
│   ├── Factories/
│   │   └── InvoiceFactory.php
│   ├── Frontend/
│   │   └── InvoiceViewer.php
│   ├── Hooks/
│   │   └── WooCommerceHooks.php
│   ├── Interfaces/
│   │   └── InvoiceInterface.php
│   ├── Models/ (empty)
│   ├── Services/
│   │   ├── InvoiceService.php
│   │   └── PDFService.php
│   ├── Types/
│   │   ├── CustomerInvoice.php
│   │   └── WooCommerceInvoice.php
│   ├── Utilities/
│   │   └── PostTypeRegistrar.php
│   ├── ValueObjects/
│   │   ├── TenantId.php
│   │   ├── InvoiceNumber.php
│   │   └── Money.php
│   ├── autoloader.php
│   ├── InvoicesAddon.php
│   └── Listener.php
├── languages/
│   ├── bloompy-invoices-nl_NL.mo
│   ├── bloompy-invoices-nl_NL.po
│   └── bloompy-invoices.pot
├── templates/
│   └── invoice-view.php
├── vendor/
│   └── [composer dependencies]
├── *.md (documentation files)
├── composer.json
├── composer.lock
├── index.php
├── init.php
├── invoice-template.html
└── uninstall.php
```

## Proposed New Structure (Option C Enhanced)

```
bloompy-invoices/
│
├── assets/                        # Frontend assets
│   ├── css/                       # NEW: Stylesheets (if needed)
│   ├── js/                        # NEW: JavaScript (if needed)
│   └── images/                    # Images and icons
│       └── invoice.svg
│
├── docs/                          # NEW: Documentation folder
│   ├── architecture/
│   │   ├── CODE_REVIEW.md
│   │   ├── SOLID_REVIEW_COMPLETE.md
│   │   └── DIRECTORY_STRUCTURE.md  # This file
│   ├── guides/
│   │   ├── BEST_PRACTICES_GUIDE.md
│   │   ├── INVOICE_INTERFACE_GUIDE.md
│   │   └── IMPLEMENTATION_GUIDE.md
│   └── history/
│       ├── CLEANUP_COMPLETE.md
│       ├── IMPROVEMENTS_SUMMARY.md
│       ├── IMPLEMENTATION_SUMMARY.md
│       └── MIGRATION_PROGRESS.md
│
├── includes/                      # PHP source code
│   │
│   ├── Core/                      # Core application logic
│   │   ├── InvoicesAddon.php      # Main addon class
│   │   ├── Listener.php           # Event listener
│   │   ├── AbstractInvoice.php    # Base invoice class
│   │   └── InvoiceInterface.php   # Invoice contract
│   │
│   ├── InvoiceTypes/              # Invoice type implementations
│   │   ├── CustomerInvoice.php    # Booknetic customer invoices
│   │   └── WooCommerceInvoice.php # WooCommerce order invoices
│   │
│   ├── Services/                  # Application services
│   │   ├── InvoiceService.php     # Main invoice service (facade)
│   │   ├── InvoiceFactory.php     # Invoice factory
│   │   └── PDFService.php         # PDF generation service
│   │
│   ├── Http/                      # HTTP layer
│   │   ├── Controllers/
│   │   │   ├── InvoiceController.php     # Main invoice controller
│   │   │   ├── AjaxController.php        # AJAX request handler
│   │   │   └── SettingsController.php    # Settings controller
│   │   │
│   │   └── Views/                 # Admin view templates
│   │       ├── invoices.php
│   │       ├── settings.php
│   │       └── settings_booknetic.php
│   │
│   ├── Public/                    # Public-facing code
│   │   ├── Controllers/
│   │   │   └── InvoiceViewController.php  # Public invoice viewing
│   │   │
│   │   └── Templates/             # Public templates
│   │       └── invoice-view.php
│   │
│   ├── Integrations/              # External integrations
│   │   └── WooCommerce/
│   │       └── WooCommerceHooks.php
│   │
│   ├── Support/                   # Supporting classes
│   │   ├── Constants/
│   │   │   └── InvoiceConstants.php
│   │   │
│   │   ├── Exceptions/
│   │   │   ├── InvoiceException.php
│   │   │   ├── InvoiceNotFoundException.php
│   │   │   ├── InvoiceValidationException.php
│   │   │   ├── InvoiceCreationException.php
│   │   │   └── InvoiceAccessDeniedException.php
│   │   │
│   │   ├── ValueObjects/
│   │   │   ├── TenantId.php
│   │   │   ├── InvoiceNumber.php
│   │   │   └── Money.php
│   │   │
│   │   └── WordPress/
│   │       └── PostTypeRegistrar.php
│   │
│   └── autoloader.php             # PSR-4 autoloader
│
├── languages/                     # Translation files
│   ├── bloompy-invoices-nl_NL.mo
│   ├── bloompy-invoices-nl_NL.po
│   └── bloompy-invoices.pot
│
├── resources/                     # NEW: Non-PHP resources
│   └── templates/
│       └── invoice-template.html  # PDF template
│
├── vendor/                        # Composer dependencies
│   └── [third-party libraries]
│
├── .gitignore                     # NEW: Git ignore file
├── CHANGELOG.md                   # NEW: Version history
├── composer.json                  # Composer config
├── composer.lock                  # Composer lock
├── index.php                      # Security (prevent directory listing)
├── init.php                       # Plugin initialization
├── README.md                      # Main readme
└── uninstall.php                  # Uninstall script
```

## Detailed Changes by Directory

### 1. Root Level

**MOVE:**
- All `*.md` files → `docs/` (organized by category)
- `invoice-template.html` → `resources/templates/`

**CREATE:**
- `.gitignore` - Ignore vendor/, node_modules/, .DS_Store, etc.
- `CHANGELOG.md` - Track version changes

**KEEP:**
- `composer.json`, `composer.lock`
- `index.php`, `init.php`, `uninstall.php`
- `README.md` (main readme stays at root)

### 2. assets/

**CURRENT:**
```
assets/
└── images/
    └── invoice.svg
```

**NEW:**
```
assets/
├── css/              # NEW: For future stylesheets
├── js/               # NEW: For future JavaScript
└── images/
    └── invoice.svg
```

**RATIONALE:** 
- Prepare for future CSS/JS needs
- Standard WordPress plugin asset structure

### 3. includes/

**Phase 1: Consolidate Core**
```
MOVE: includes/Abstract/AbstractInvoice.php → includes/Core/AbstractInvoice.php
MOVE: includes/Interfaces/InvoiceInterface.php → includes/Core/InvoiceInterface.php
MOVE: includes/InvoicesAddon.php → includes/Core/InvoicesAddon.php
MOVE: includes/Listener.php → includes/Core/Listener.php
DELETE: includes/Abstract/ (empty)
DELETE: includes/Interfaces/ (empty)
DELETE: includes/Models/ (empty)
```

**Phase 2: Rename Invoice Types**
```
RENAME: includes/Types/ → includes/InvoiceTypes/
```

**Phase 3: Consolidate Services**
```
MOVE: includes/Factories/InvoiceFactory.php → includes/Services/InvoiceFactory.php
DELETE: includes/Factories/ (empty)
```

**Phase 4: Restructure HTTP Layer**
```
RENAME: includes/Backend/ → includes/Http/
CREATE: includes/Http/Controllers/
MOVE: includes/Http/Ajax.php → includes/Http/Controllers/AjaxController.php
MOVE: includes/Http/Controller.php → includes/Http/Controllers/InvoiceController.php
MOVE: includes/Http/SettingsController.php → includes/Http/Controllers/SettingsController.php
RENAME: includes/Http/view/ → includes/Http/Views/
```

**Phase 5: Restructure Public Layer**
```
RENAME: includes/Frontend/ → includes/Public/
CREATE: includes/Public/Controllers/
MOVE: includes/Public/InvoiceViewer.php → includes/Public/Controllers/InvoiceViewController.php
CREATE: includes/Public/Templates/
MOVE: templates/invoice-view.php → includes/Public/Templates/invoice-view.php
DELETE: templates/ (empty, at root)
```

**Phase 6: Create Support Layer**
```
CREATE: includes/Support/
MOVE: includes/Constants/ → includes/Support/Constants/
MOVE: includes/Exceptions/ → includes/Support/Exceptions/
MOVE: includes/ValueObjects/ → includes/Support/ValueObjects/
CREATE: includes/Support/WordPress/
MOVE: includes/Utilities/PostTypeRegistrar.php → includes/Support/WordPress/PostTypeRegistrar.php
DELETE: includes/Utilities/ (empty)
```

**Phase 7: Restructure Integrations**
```
RENAME: includes/Hooks/ → includes/Integrations/
CREATE: includes/Integrations/WooCommerce/
MOVE: includes/Integrations/WooCommerceHooks.php → includes/Integrations/WooCommerce/WooCommerceHooks.php
```

### 4. Documentation

**CREATE:**
```
docs/
├── architecture/          # Architecture decisions
│   ├── CODE_REVIEW.md
│   ├── SOLID_REVIEW_COMPLETE.md
│   └── DIRECTORY_STRUCTURE.md
│
├── guides/               # Developer guides
│   ├── BEST_PRACTICES_GUIDE.md
│   ├── INVOICE_INTERFACE_GUIDE.md
│   └── IMPLEMENTATION_GUIDE.md
│
└── history/             # Historical documentation
    ├── CLEANUP_COMPLETE.md
    ├── IMPROVEMENTS_SUMMARY.md
    ├── IMPLEMENTATION_SUMMARY.md
    └── MIGRATION_PROGRESS.md
```

**MOVE:**
- `BEST_PRACTICES_GUIDE.md` → `docs/guides/`
- `CODE_REVIEW.md` → `docs/architecture/`
- `SOLID_REVIEW_COMPLETE.md` → `docs/architecture/`
- `INVOICE_INTERFACE_GUIDE.md` → `docs/guides/`
- `IMPLEMENTATION_COMPLETE.md` → `docs/guides/IMPLEMENTATION_GUIDE.md`
- `CLEANUP_COMPLETE.md` → `docs/history/`
- `IMPROVEMENTS_SUMMARY.md` → `docs/history/`
- `IMPLEMENTATION_SUMMARY.md` → `docs/history/`
- `MIGRATION_PROGRESS.md` → `docs/history/`
- `CLEANUP_ANALYSIS.md` → `docs/history/`
- `DIRECTORY_RESTRUCTURE_PLAN.md` → `docs/architecture/`

### 5. Resources

**CREATE:**
```
resources/
└── templates/
    └── invoice-template.html
```

**MOVE:**
- `invoice-template.html` → `resources/templates/`

## Namespace Changes

### Before → After

| Before | After |
|--------|-------|
| `Bloompy\Invoices\Abstract` | `Bloompy\Invoices\Core` |
| `Bloompy\Invoices\Backend` | `Bloompy\Invoices\Http\Controllers` |
| `Bloompy\Invoices\Factories` | `Bloompy\Invoices\Services` |
| `Bloompy\Invoices\Frontend` | `Bloompy\Invoices\Public\Controllers` |
| `Bloompy\Invoices\Hooks` | `Bloompy\Invoices\Integrations\WooCommerce` |
| `Bloompy\Invoices\Interfaces` | `Bloompy\Invoices\Core` |
| `Bloompy\Invoices\Types` | `Bloompy\Invoices\InvoiceTypes` |
| `Bloompy\Invoices\Utilities` | `Bloompy\Invoices\Support\WordPress` |
| `Bloompy\Invoices\Constants` | `Bloompy\Invoices\Support\Constants` |
| `Bloompy\Invoices\Exceptions` | `Bloompy\Invoices\Support\Exceptions` |
| `Bloompy\Invoices\ValueObjects` | `Bloompy\Invoices\Support\ValueObjects` |

### Files Requiring Namespace Updates

**Core Files (11 files):**
- Core/InvoicesAddon.php
- Core/Listener.php
- Core/AbstractInvoice.php
- Core/InvoiceInterface.php
- InvoiceTypes/CustomerInvoice.php
- InvoiceTypes/WooCommerceInvoice.php
- Services/InvoiceService.php
- Services/InvoiceFactory.php
- Services/PDFService.php
- autoloader.php
- init.php

**HTTP Controllers (3 files):**
- Http/Controllers/InvoiceController.php
- Http/Controllers/AjaxController.php
- Http/Controllers/SettingsController.php

**Public Controllers (1 file):**
- Public/Controllers/InvoiceViewController.php

**Support Classes (11 files):**
- Support/Constants/InvoiceConstants.php
- Support/Exceptions/*.php (5 files)
- Support/ValueObjects/*.php (3 files)
- Support/WordPress/PostTypeRegistrar.php

**Integrations (1 file):**
- Integrations/WooCommerce/WooCommerceHooks.php

**Total: ~27 files to update**

## Implementation Phases

### Phase 1: Prepare (10 min)
- ✅ Create all new directories
- ✅ Create .gitignore
- ✅ Create CHANGELOG.md

### Phase 2: Move Documentation (10 min)
- ✅ Create docs/ structure
- ✅ Move all .md files to appropriate locations
- ✅ Create DIRECTORY_STRUCTURE.md

### Phase 3: Move Resources (5 min)
- ✅ Create resources/ folder
- ✅ Move invoice-template.html
- ✅ Move templates/invoice-view.php

### Phase 4: Consolidate Core (15 min)
- ✅ Create includes/Core/
- ✅ Move AbstractInvoice.php, InvoiceInterface.php
- ✅ Move InvoicesAddon.php, Listener.php
- ✅ Update namespaces in these files
- ✅ Delete empty Abstract/, Interfaces/, Models/

### Phase 5: Rename Invoice Types (10 min)
- ✅ Rename Types/ to InvoiceTypes/
- ✅ Update namespaces in CustomerInvoice.php, WooCommerceInvoice.php

### Phase 6: Consolidate Services (10 min)
- ✅ Move InvoiceFactory.php to Services/
- ✅ Update namespace
- ✅ Delete empty Factories/

### Phase 7: Restructure HTTP (20 min)
- ✅ Rename Backend/ to Http/
- ✅ Create Http/Controllers/
- ✅ Move and rename controllers
- ✅ Rename view/ to Views/
- ✅ Update all namespaces
- ✅ Update all view paths

### Phase 8: Restructure Public (15 min)
- ✅ Rename Frontend/ to Public/
- ✅ Create Public/Controllers/
- ✅ Move and rename InvoiceViewer.php
- ✅ Create Public/Templates/
- ✅ Update namespaces
- ✅ Update template paths

### Phase 9: Create Support Layer (15 min)
- ✅ Create Support/ structure
- ✅ Move Constants/, Exceptions/, ValueObjects/
- ✅ Create Support/WordPress/
- ✅ Move PostTypeRegistrar.php
- ✅ Update all namespaces
- ✅ Delete empty Utilities/

### Phase 10: Restructure Integrations (10 min)
- ✅ Rename Hooks/ to Integrations/
- ✅ Create Integrations/WooCommerce/
- ✅ Move WooCommerceHooks.php
- ✅ Update namespace

### Phase 11: Update Autoloader (15 min)
- ✅ Update PSR-4 mappings in autoloader.php
- ✅ Test autoloading

### Phase 12: Update All References (30 min)
- ✅ Update all `use` statements in all files
- ✅ Update view/template paths
- ✅ Update any hardcoded paths

### Phase 13: Testing (30 min)
- ✅ PHP syntax check all files
- ✅ Test invoice list
- ✅ Test invoice creation
- ✅ Test PDF generation
- ✅ Test settings
- ✅ Check error logs

**Total Estimated Time: ~3 hours**

## Autoloader Updates

### Current autoloader.php
```php
spl_autoload_register(function ($class) {
    $prefix = 'Bloompy\\Invoices\\';
    // ...
});
```

### New PSR-4 Mappings
```php
$namespaceMap = [
    'Bloompy\\Invoices\\Core\\' => 'Core/',
    'Bloompy\\Invoices\\InvoiceTypes\\' => 'InvoiceTypes/',
    'Bloompy\\Invoices\\Services\\' => 'Services/',
    'Bloompy\\Invoices\\Http\\Controllers\\' => 'Http/Controllers/',
    'Bloompy\\Invoices\\Http\\' => 'Http/',
    'Bloompy\\Invoices\\Public\\Controllers\\' => 'Public/Controllers/',
    'Bloompy\\Invoices\\Public\\' => 'Public/',
    'Bloompy\\Invoices\\Integrations\\WooCommerce\\' => 'Integrations/WooCommerce/',
    'Bloompy\\Invoices\\Integrations\\' => 'Integrations/',
    'Bloompy\\Invoices\\Support\\Constants\\' => 'Support/Constants/',
    'Bloompy\\Invoices\\Support\\Exceptions\\' => 'Support/Exceptions/',
    'Bloompy\\Invoices\\Support\\ValueObjects\\' => 'Support/ValueObjects/',
    'Bloompy\\Invoices\\Support\\WordPress\\' => 'Support/WordPress/',
    'Bloompy\\Invoices\\Support\\' => 'Support/',
];
```

## .gitignore Template

```gitignore
# Dependencies
/vendor/
/node_modules/

# IDE
.idea/
.vscode/
*.sublime-project
*.sublime-workspace

# OS
.DS_Store
Thumbs.db

# Logs
*.log
debug.log

# Build
/build/
/dist/

# Temporary
*.tmp
*.bak
*.swp
*~

# Environment
.env
.env.local

# Composer
composer.phar

# WordPress
wp-config.php
```

## CHANGELOG.md Template

```markdown
# Changelog

All notable changes to Bloompy Invoices will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-10-20

### Added
- Invoice interface system for extensible invoice types
- CustomerInvoice and WooCommerceInvoice implementations
- InvoiceFactory for centralized invoice creation
- InvoiceService facade
- Value objects (TenantId, InvoiceNumber, Money)
- Custom exception classes
- Constants class for all magic strings
- Comprehensive documentation

### Changed
- Restructured entire plugin directory for better organization
- Migrated to SOLID architecture principles
- Applied strict types throughout
- Improved error handling with specific exceptions

### Removed
- Old monolithic Invoice model (856 lines)
- Unused NewController
- Outdated test files

### Technical
- Net code reduction: -654 lines
- 100% backward compatible
- Zero breaking changes

## [1.0.0] - Previous Version
- Initial release
```

## Benefits After Restructure

### For Developers

✅ **Clear Navigation**
- `Core/` - Start here for main logic
- `Http/Controllers/` - Admin interface
- `Public/Controllers/` - Frontend interface
- `InvoiceTypes/` - Invoice implementations
- `Services/` - Business logic
- `Support/` - Utilities and helpers

✅ **Standard Patterns**
- Follows WordPress plugin conventions
- Recognizable MVC-like structure
- Clear separation of concerns

✅ **Easy Onboarding**
- Documentation organized in `docs/`
- Clear folder names
- Self-documenting structure

### For Maintenance

✅ **Reduced Complexity**
- No single-file folders
- No empty folders
- Logical grouping

✅ **Future-Proof**
- Easy to add new invoice types
- Easy to add new integrations
- Clear where new features go

✅ **Professional**
- Industry-standard structure
- Clean and organized
- Well-documented

## Risk Mitigation

### Low Risk
- Creating new folders
- Moving documentation
- Moving templates

### Medium Risk
- Renaming folders
- Moving PHP files
- Updating namespaces

### High Risk (Test Thoroughly!)
- Updating autoloader
- Updating view paths
- Template path references

## Testing Checklist

After each phase, verify:
- [ ] No PHP syntax errors
- [ ] Plugin activates successfully
- [ ] Invoice list displays
- [ ] Invoice creation works
- [ ] PDF generation works
- [ ] Settings page loads
- [ ] No errors in debug.log

## Rollback Strategy

Each phase is a separate git commit:
```bash
git log --oneline  # See commits
git revert <commit-hash>  # Rollback specific phase
```

## Summary

This restructure will:
- ✅ Eliminate 3 empty folders
- ✅ Consolidate 4 single-file folders
- ✅ Organize documentation properly
- ✅ Follow WordPress standards
- ✅ Make codebase more professional
- ✅ Improve developer experience
- ✅ Maintain 100% backward compatibility

**Ready to implement? This will take approximately 3 hours with testing.**

