# Bloompy Invoices - Directory Structure

## Overview

This document explains the directory structure and organization of the Bloompy Invoices plugin.

**Last Updated:** 2025-10-20  
**Version:** 2.0.0

## Root Structure

```
bloompy-invoices/
├── assets/              # Frontend assets (images, future CSS/JS)
├── docs/                # Documentation
├── includes/            # PHP source code
├── languages/           # Translation files
├── resources/           # Non-PHP resources (templates)
├── vendor/              # Composer dependencies
├── .gitignore           # Git ignore rules
├── CHANGELOG.md         # Version history
├── composer.json        # Composer configuration
├── composer.lock        # Composer lock file
├── index.php            # Security file
├── init.php             # Plugin initialization
├── README.md            # Main readme
└── uninstall.php        # Uninstall script
```

## Documentation (`/docs/`)

All documentation organized by category.

```
docs/
├── architecture/        # Architecture decisions and reviews
│   ├── CODE_REVIEW.md
│   ├── SOLID_REVIEW_COMPLETE.md
│   ├── DIRECTORY_RESTRUCTURE_PLAN.md
│   ├── COMPLETE_RESTRUCTURE_PLAN.md
│   └── DIRECTORY_STRUCTURE.md (this file)
│
├── guides/              # Developer guides and how-tos
│   ├── BEST_PRACTICES_GUIDE.md
│   ├── INVOICE_INTERFACE_GUIDE.md
│   └── IMPLEMENTATION_GUIDE.md
│
└── history/             # Historical documentation and changes
    ├── CLEANUP_COMPLETE.md
    ├── CLEANUP_ANALYSIS.md
    ├── IMPROVEMENTS_SUMMARY.md
    ├── IMPLEMENTATION_SUMMARY.md
    ├── MIGRATION_PROGRESS.md
    ├── BUGFIX_TYPE_SAFETY.md
    ├── BOOKNETIC_BUGS.md
    └── CHANGES_SUMMARY.md
```

### Architecture Docs
Documents about system design, architecture decisions, and code reviews.

### Guides
Practical guides for developers working with the plugin.

### History
Historical documentation tracking changes, migrations, and bug fixes.

## Source Code (`/includes/`)

Currently being restructured. Temporary structure:

```
includes/
├── Abstract/
│   └── AbstractInvoice.php
├── Backend/
│   ├── Controllers/
│   │   ├── Ajax.php
│   │   ├── Controller.php
│   │   └── SettingsController.php
│   └── view/
│       ├── invoices.php
│       ├── settings.php
│       └── settings_booknetic.php
├── Constants/
│   └── InvoiceConstants.php
├── Exceptions/
│   ├── InvoiceException.php
│   ├── InvoiceNotFoundException.php
│   ├── InvoiceValidationException.php
│   ├── InvoiceCreationException.php
│   └── InvoiceAccessDeniedException.php
├── Factories/
│   └── InvoiceFactory.php
├── Frontend/
│   └── InvoiceViewer.php
├── Hooks/
│   └── WooCommerceHooks.php
├── Interfaces/
│   └── InvoiceInterface.php
├── Services/
│   ├── InvoiceService.php
│   └── PDFService.php
├── Support/
│   └── Helpers.php
├── Types/
│   ├── CustomerInvoice.php
│   └── WooCommerceInvoice.php
├── Utilities/
│   └── PostTypeRegistrar.php
├── ValueObjects/
│   ├── TenantId.php
│   ├── InvoiceNumber.php
│   └── Money.php
├── autoloader.php
├── InvoicesAddon.php
└── Listener.php
```

## Planned Future Structure

See `COMPLETE_RESTRUCTURE_PLAN.md` for the complete planned restructure to:

```
includes/
├── Core/                # Core logic
├── InvoiceTypes/        # Invoice implementations
├── Services/            # Application services
├── Http/                # HTTP layer (controllers & views)
├── Public/              # Public-facing code
├── Integrations/        # External integrations
└── Support/             # Supporting classes
```

## Assets (`/assets/`)

```
assets/
├── css/                 # Stylesheets (future)
├── js/                  # JavaScript (future)
└── images/
    └── invoice.svg
```

## Languages (`/languages/`)

Translation files for internationalization.

```
languages/
├── bloompy-invoices-nl_NL.mo
├── bloompy-invoices-nl_NL.po
└── bloompy-invoices.pot
```

## Resources (`/resources/`)

Non-PHP resources like templates.

```
resources/
└── templates/
    └── invoice-template.html
```

## Vendor (`/vendor/`)

Composer-managed dependencies (TCPDF for PDF generation).

## Key Principles

### Organization
- **By Feature:** Group related files together
- **By Layer:** Separate concerns (domain, application, infrastructure, presentation)
- **By Type:** Keep similar files in same directory

### Naming Conventions
- **Folders:** PascalCase for namespaces (`InvoiceTypes/`, `ValueObjects/`)
- **Files:** Match class names exactly (`CustomerInvoice.php`)
- **Docs:** SCREAMING_CASE.md for visibility

### Best Practices
1. **One class per file**
2. **Namespace matches directory structure**
3. **Documentation close to code**
4. **Clear separation of concerns**

## File Location Guide

### Where to find...

**Invoice Types:**
- `includes/Types/CustomerInvoice.php` - Booknetic customer invoices
- `includes/Types/WooCommerceInvoice.php` - WooCommerce order invoices

**Services:**
- `includes/Services/InvoiceService.php` - Main service facade
- `includes/Services/InvoiceFactory.php` - Invoice creation
- `includes/Services/PDFService.php` - PDF generation

**Controllers:**
- `includes/Backend/Controller.php` - Main admin controller
- `includes/Backend/Ajax.php` - AJAX handler
- `includes/Backend/SettingsController.php` - Settings controller

**Views:**
- `includes/Backend/view/` - Admin templates
- `templates/` - Public templates (to be moved to `resources/`)

**Utilities:**
- `includes/Support/Helpers.php` - Helper functions
- `includes/Utilities/PostTypeRegistrar.php` - WordPress CPT registration
- `includes/Constants/InvoiceConstants.php` - Constants

**Domain Objects:**
- `includes/ValueObjects/` - Value objects (TenantId, InvoiceNumber, Money)
- `includes/Exceptions/` - Custom exceptions
- `includes/Interfaces/InvoiceInterface.php` - Invoice contract
- `includes/Abstract/AbstractInvoice.php` - Base invoice class

## Navigation Tips

### For New Developers

1. **Start here:** `README.md` in root
2. **Understand architecture:** `docs/architecture/CODE_REVIEW.md`
3. **Learn patterns:** `docs/guides/BEST_PRACTICES_GUIDE.md`
4. **See examples:** `docs/guides/INVOICE_INTERFACE_GUIDE.md`

### For Maintenance

1. **Adding new invoice type:** `includes/Types/` + update factory
2. **Adding new feature:** Follow SOLID principles in guides
3. **Fixing bugs:** Check `docs/history/BOOKNETIC_BUGS.md` first
4. **Understanding changes:** See `docs/history/CHANGES_SUMMARY.md`

### For Code Review

1. **Architecture decisions:** `docs/architecture/`
2. **Code quality:** `docs/architecture/SOLID_REVIEW_COMPLETE.md`
3. **Best practices:** `docs/guides/BEST_PRACTICES_GUIDE.md`

## Related Documentation

- **Main README:** `/README.md`
- **Changelog:** `/CHANGELOG.md`
- **Architecture Review:** `docs/architecture/CODE_REVIEW.md`
- **Best Practices:** `docs/guides/BEST_PRACTICES_GUIDE.md`
- **Complete Restructure Plan:** `docs/architecture/COMPLETE_RESTRUCTURE_PLAN.md`

## Updates

This document will be updated as the directory structure evolves. Always check the git log for the most recent changes.

---

**Maintained by:** Development Team  
**For questions:** See README.md


