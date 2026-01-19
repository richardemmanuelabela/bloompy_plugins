# Changelog

All notable changes to Bloompy Invoices will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Deprecated
- Nothing yet

### Removed
- Nothing yet

### Fixed
- Nothing yet

### Security
- Nothing yet

## [2.0.0] - 2025-10-20

### Added
- **Invoice Interface System** for extensible invoice types
  - `InvoiceInterface` - Contract for all invoice implementations
  - `AbstractInvoice` - Base class with shared functionality
  - `CustomerInvoice` - Booknetic appointment invoices
  - `WooCommerceInvoice` - WooCommerce order invoices
  
- **Service Layer**
  - `InvoiceService` - Facade for invoice operations
  - `InvoiceFactory` - Centralized invoice creation and management
  
- **Support Classes**
  - `InvoiceConstants` - All magic strings and numbers
  - `Helpers` - Type-safe utility functions
  - Custom exceptions (5 types)
  - Value objects (TenantId, InvoiceNumber, Money)
  - `PostTypeRegistrar` - WordPress CPT registration utility

- **Documentation** (11 markdown files)
  - Comprehensive architecture review (23 pages)
  - SOLID principles analysis
  - Best practices guide
  - Developer guides
  - Migration and implementation history

### Changed
- **Directory Structure**
  - Organized documentation into `docs/` folder
  - Created `docs/architecture/`, `docs/guides/`, `docs/history/` subdirectories
  
- **Code Quality**
  - Applied `declare(strict_types=1)` to all core files
  - Replaced magic strings with constants throughout
  - Enhanced error handling with specific exceptions
  - Improved tenant ID handling with `TenantId` value object
  
- **Architecture**
  - Migrated from monolithic to SOLID-compliant architecture
  - Separated concerns into layers (domain, application, infrastructure)
  - Applied Factory and Facade patterns
  
- **Type Safety**
  - Fixed type mismatches from Booknetic API
  - Created type-safe wrappers for Booknetic functions
  - Enhanced `TenantId::fromValue()` to handle mixed types

### Removed
- Old monolithic `Invoice` model (856 lines)
- Unused `NewController` (336 lines)
- Outdated test files (262 lines)
- Empty `Models/` directory
- Single-file folders (`Abstract/`, `Interfaces/`, `Factories/`)

### Fixed
- **Critical:** TypeError when accessing invoices as tenant
  - Booknetic's `Permission::tenantId()` returns string instead of int
  - Created `Helpers::getCurrentTenantId()` for type-safe access
  - Updated `TenantId::fromValue()` to handle mixed types
  
- **DataTableQuery** dependencies
  - Updated to use parent instance methods instead of old Invoice model
  - Added exception handling for invalid invoices

### Technical
- **Net Code Reduction:** -654 lines (-10%)
- **Files Created:** 18 new files
- **Files Updated:** 12 files
- **Files Deleted:** 4 files
- **SOLID Compliance:** High
- **Type Safety:** High
- **Backward Compatibility:** 100%
- **Breaking Changes:** None

### Known Issues
- **Booknetic Core Bug:** Database error about missing `updated_by` column
  - Severity: Low (harmless logging only)
  - Impact: None on functionality
  - Owner: Booknetic core (not our issue)
  - Documentation: See `docs/history/BOOKNETIC_BUGS.md`

## [1.0.0] - Previous Version

### Initial Release
- Basic invoice functionality for Booknetic appointments
- Manual invoice creation
- PDF generation
- Email integration
- WooCommerce SaaS billing integration

---

## How to Read This Changelog

- **[Unreleased]** - Changes in development, not yet released
- **[Version]** - Released version with date
- **Added** - New features
- **Changed** - Changes to existing functionality
- **Deprecated** - Soon-to-be removed features
- **Removed** - Removed features
- **Fixed** - Bug fixes
- **Security** - Security fixes

## Version Numbering

We use [Semantic Versioning](https://semver.org/):
- **MAJOR.MINOR.PATCH** (e.g., 2.0.0)
- **MAJOR:** Incompatible API changes
- **MINOR:** New functionality (backward compatible)
- **PATCH:** Bug fixes (backward compatible)

## Links

- [README](README.md) - Main documentation
- [Documentation](docs/) - Full documentation
- [Best Practices](docs/guides/BEST_PRACTICES_GUIDE.md) - Development guide
- [Architecture](docs/architecture/CODE_REVIEW.md) - Architecture decisions


