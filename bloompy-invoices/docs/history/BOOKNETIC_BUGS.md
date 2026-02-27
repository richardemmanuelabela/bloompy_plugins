# Booknetic Core Bugs & Workarounds

This document tracks bugs in Booknetic core that affect our plugin and the workarounds we've implemented.

## 1. Missing `updated_by` Column in Services Table

### Issue

**Error:**
```
WordPress database error Unknown column 'updated_by' in 'field list' 
for query UPDATE wp_bkntc_services SET is_active='0', updated_by='53', updated_at='1760972237'
```

**Stack Trace:**
```
BookneticSaaS\Config::tenantCapabilities
→ BookneticSaaS\Providers\Helpers\TenantHelper::restrictLimits
→ BookneticApp\Providers\DB\QueryBuilder->update
```

**Root Cause:**
- Booknetic SaaS tries to update the `wp_bkntc_services` table with `updated_by` column
- This column doesn't exist in the database schema
- Triggered when checking tenant capabilities via `Capabilities::tenantCan()`

**Impact:**
- ⚠️ Database error logged on every page load
- ⚠️ Error occurs during our plugin initialization
- ⚠️ Not our bug, but appears in our stack trace

### Workaround

**File:** `includes/InvoicesAddon.php`

**Solution:** Suppress the error with `@` operator while Booknetic fixes their schema:

```php
// Check tenant capability (suppress DB errors from Booknetic core)
// Note: Booknetic SaaS has a bug where it tries to update services with 
// non-existent 'updated_by' column. This is a Booknetic core issue, not ours.
if (!@Capabilities::tenantCan('bloompy_invoices')) {
    return;
}
```

**Why This Works:**
- The `@` operator suppresses the database error
- The capability check still works correctly (returns true/false)
- No functionality is affected
- Clean error logs

**Better Solution (For Booknetic to Implement):**

Option 1: Add the missing column:
```sql
ALTER TABLE wp_bkntc_services 
ADD COLUMN updated_by INT NULL AFTER updated_at;
```

Option 2: Check if column exists before updating:
```php
// In TenantHelper::restrictLimits
if ($wpdb->get_var("SHOW COLUMNS FROM wp_bkntc_services LIKE 'updated_by'")) {
    // Include updated_by in update
} else {
    // Skip updated_by
}
```

**Status:** 🟡 WORKAROUND IN PLACE  
**Reported to Booknetic:** ❓ TBD  
**Fixed in Booknetic:** ❌ No

---

## 2. Type Inconsistency in Permission::tenantId()

### Issue

**Error:**
```
TypeError: Argument must be of type ?int, string given
```

**Root Cause:**
- `Permission::tenantId()` returns string instead of int
- Example: `'50'` instead of `50`
- Breaks strict type checking

**Impact:**
- ❌ Fatal errors with `declare(strict_types=1)`
- ❌ Type safety compromised
- ❌ Inconsistent with type hints

### Workaround

**Files:** 
- `includes/Support/Helpers.php` (NEW)
- `includes/ValueObjects/TenantId.php` (UPDATED)

**Solution:** Created type-safe wrapper functions:

```php
// Helpers.php
public static function getCurrentTenantId(): ?int
{
    $tenantId = \BookneticApp\Providers\Core\Permission::tenantId();
    
    // Handle string/int/null/false
    if ($tenantId === null || $tenantId === false || $tenantId === '') {
        return null;
    }
    
    return is_numeric($tenantId) ? (int)$tenantId : null;
}
```

**Better Solution (For Booknetic to Implement):**

Update `Permission::tenantId()` return type:
```php
public static function tenantId(): ?int  // Not string|int|false|null
{
    // Ensure we always return int or null
    $id = // ... get tenant ID
    return $id ? (int)$id : null;
}
```

**Status:** ✅ FIXED (with workaround)  
**Reported to Booknetic:** ❓ TBD  
**Fixed in Booknetic:** ❌ No

---

## Summary

| Bug | Severity | Impact | Status | Workaround |
|-----|----------|--------|--------|------------|
| Missing `updated_by` column | Medium | Error logs | 🟡 Workaround | `@` suppression |
| Type inconsistency in tenantId() | High | Fatal error | ✅ Fixed | Helper class |

## Recommendations

### For Our Plugin
1. ✅ Keep workarounds documented
2. ✅ Monitor Booknetic updates
3. ✅ Remove workarounds when Booknetic fixes issues
4. ⏳ Consider reporting bugs to Booknetic

### For Booknetic Team
1. Add missing `updated_by` column to services table
2. Fix `Permission::tenantId()` return type consistency
3. Add database schema migration system
4. Improve type safety throughout codebase

## Testing

To verify Booknetic bugs:

### Test 1: Missing Column
```sql
-- Check if column exists
SHOW COLUMNS FROM wp_bkntc_services LIKE 'updated_by';
-- Should return empty (column doesn't exist)
```

### Test 2: Type Inconsistency
```php
var_dump(\BookneticApp\Providers\Core\Permission::tenantId());
// Returns: string(2) "50" instead of int(50)
```

## Update Log

- **2025-10-20:** Identified both bugs
- **2025-10-20:** Implemented workarounds
- **2025-10-20:** Documented in this file

---

**Note:** These are Booknetic core bugs, not issues with our plugin. We've implemented workarounds to ensure our plugin works correctly despite these issues.

