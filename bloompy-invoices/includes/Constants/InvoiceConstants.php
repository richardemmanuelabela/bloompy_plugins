<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Constants;

/**
 * Invoice system constants
 * 
 * Centralized location for all magic strings and numbers used throughout the invoice system.
 * This follows the "Replace Magic Number with Symbolic Constant" refactoring pattern.
 */
final class InvoiceConstants
{
    /**
     * Invoice Types
     */
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_WOOCOMMERCE = 'woocommerce';
    public const TYPE_MONEYBIRD = 'moneybird';
    public const TYPE_SAAS = 'saas_invoice'; // Legacy support
    
    /**
     * Invoice Sources
     */
    public const SOURCE_BOOKNETIC = 'booknetic';
    public const SOURCE_WOOCOMMERCE = 'woocommerce';
    public const SOURCE_MANUAL = 'manual';
    
    /**
     * Post Type
     */
    public const POST_TYPE = 'bloompy_invoice';
    
    /**
     * Invoice Status
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_OVERDUE = 'overdue';
    
    /**
     * Meta Keys
     */
    public const META_INVOICE_NUMBER = 'invoice_number';
    public const META_TENANT_ID = 'tenant_id';
    public const META_INVOICE_TYPE = 'invoice_type';
    public const META_SOURCE = 'source';
    public const META_CUSTOMER_EMAIL = 'customer_email';
    public const META_CUSTOMER_NAME = 'customer_name';
    public const META_TOTAL_AMOUNT = 'total_amount';
    public const META_STATUS = 'status';
    public const META_INVOICE_DATE = 'invoice_date';
    public const META_DUE_DATE = 'due_date';
    public const META_PAYMENT_DATE = 'payment_date';
    
    /**
     * Currency
     */
    public const DEFAULT_CURRENCY = 'EUR';
    
    /**
     * Pagination
     */
    public const DEFAULT_LIMIT = 20;
    public const DEFAULT_OFFSET = 0;
    
    /**
     * WordPress Page Slugs
     */
    public const PAGE_BOOKNETIC_SAAS = 'booknetic-saas';
    public const PAGE_BOOKNETIC = 'booknetic';
    
    /**
     * Tenant Context
     */
    public const TENANT_SUPER_ADMIN = 0; // 0 represents super admin context
    
    /**
     * Invoice Number Prefixes
     */
    public const PREFIX_CUSTOMER = 'INV';
    public const PREFIX_WOOCOMMERCE = 'WC';
    public const PREFIX_MONEYBIRD = 'MB';
    
    /**
     * Date Formats
     */
    public const DATE_FORMAT_INVOICE = 'Y-m-d';
    public const DATE_FORMAT_DISPLAY = 'd-m-Y';
    
    /**
     * Validation
     */
    public const MAX_INVOICE_NUMBER_LENGTH = 50;
    public const MAX_EMAIL_LENGTH = 255;
    public const MAX_NAME_LENGTH = 255;
    
    /**
     * Cache
     */
    public const CACHE_TTL = 3600; // 1 hour
    public const CACHE_GROUP = 'bloompy_invoices';
    
    /**
     * PDF
     */
    public const PDF_ORIENTATION = 'portrait';
    public const PDF_UNIT = 'mm';
    public const PDF_FORMAT = 'A4';
    
    /**
     * Get all available invoice types
     * 
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_CUSTOMER,
            self::TYPE_WOOCOMMERCE,
            self::TYPE_MONEYBIRD,
        ];
    }
    
    /**
     * Get all available invoice statuses
     * 
     * @return array
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
            self::STATUS_OVERDUE,
        ];
    }
    
    /**
     * Get all available sources
     * 
     * @return array
     */
    public static function getAvailableSources(): array
    {
        return [
            self::SOURCE_BOOKNETIC,
            self::SOURCE_WOOCOMMERCE,
            self::SOURCE_MANUAL,
        ];
    }
    
    /**
     * Check if invoice type is valid
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAvailableTypes(), true);
    }
    
    /**
     * Check if invoice status is valid
     * 
     * @param string $status
     * @return bool
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAvailableStatuses(), true);
    }
    
    /**
     * Check if invoice source is valid
     * 
     * @param string $source
     * @return bool
     */
    public static function isValidSource(string $source): bool
    {
        return in_array($source, self::getAvailableSources(), true);
    }
    
    /**
     * Get invoice prefix by type
     * 
     * @param string $type
     * @return string
     */
    public static function getPrefixByType(string $type): string
    {
        return match($type) {
            self::TYPE_CUSTOMER => self::PREFIX_CUSTOMER,
            self::TYPE_WOOCOMMERCE => self::PREFIX_WOOCOMMERCE,
            self::TYPE_MONEYBIRD => self::PREFIX_MONEYBIRD,
            default => self::PREFIX_CUSTOMER,
        };
    }
    
    /**
     * Get type by source
     * 
     * @param string $source
     * @return string
     */
    public static function getTypeBySource(string $source): string
    {
        return match($source) {
            self::SOURCE_WOOCOMMERCE => self::TYPE_WOOCOMMERCE,
            self::SOURCE_BOOKNETIC => self::TYPE_CUSTOMER,
            self::SOURCE_MANUAL => self::TYPE_CUSTOMER,
            default => self::TYPE_CUSTOMER,
        };
    }
    
    /**
     * Get source by type
     * 
     * @param string $type
     * @return string
     */
    public static function getSourceByType(string $type): string
    {
        return match($type) {
            self::TYPE_WOOCOMMERCE => self::SOURCE_WOOCOMMERCE,
            self::TYPE_CUSTOMER => self::SOURCE_BOOKNETIC,
            default => self::SOURCE_BOOKNETIC,
        };
    }
    
    /**
     * Private constructor to prevent instantiation
     */
    private function __construct()
    {
        // Utility class should not be instantiated
    }
}



