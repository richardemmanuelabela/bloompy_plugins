<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Support;

/**
 * Helper functions for the Bloompy Invoices plugin
 */
class Helpers
{
    /**
     * Get current tenant ID as integer
     * 
     * Handles the type inconsistency from Permission::tenantId() which can return:
     * - int (expected)
     * - string (actual, e.g., '50')
     * - null (no tenant)
     * - false (no permission class)
     * 
     * @return int|null Integer tenant ID or null for super admin
     */
    public static function getCurrentTenantId(): ?int
    {
        if (!class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            return null;
        }
        
        $tenantId = \BookneticApp\Providers\Core\Permission::tenantId();
        
        // Handle various return types
        if ($tenantId === null || $tenantId === false || $tenantId === '') {
            return null;
        }
        
        // Convert to int if it's a numeric string
        return is_numeric($tenantId) ? (int)$tenantId : null;
    }
    
    /**
     * Check if current user is super administrator
     * 
     * @return bool
     */
    public static function isSuperAdmin(): bool
    {
        if (!class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            return false;
        }
        
        return \BookneticApp\Providers\Core\Permission::isSuperAdministrator();
    }
    
    /**
     * Check if we're in SaaS version
     * 
     * @return bool
     */
    public static function isSaaSVersion(): bool
    {
        if (!class_exists('BookneticApp\\Providers\\Helpers\\Helper')) {
            return false;
        }
        
        return \BookneticApp\Providers\Helpers\Helper::isSaaSVersion();
    }
}


