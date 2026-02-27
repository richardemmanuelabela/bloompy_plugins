<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Factories;

use Bloompy\Invoices\Interfaces\InvoiceInterface;
use Bloompy\Invoices\Constants\InvoiceConstants;
use Bloompy\Invoices\Types\CustomerInvoice;
use Bloompy\Invoices\Types\WooCommerceInvoice;
use Bloompy\Invoices\Support\Helpers;

/**
 * Factory class for creating and managing different invoice types
 * 
 * This factory provides a centralized way to create and manage different
 * invoice implementations, making it easy to add new invoice types in the future.
 */
class InvoiceFactory
{
    /**
     * Available invoice types and their corresponding classes
     * 
     * @var array
     */
    private static array $invoiceTypes = [
        InvoiceConstants::TYPE_CUSTOMER => CustomerInvoice::class,
        InvoiceConstants::TYPE_WOOCOMMERCE => WooCommerceInvoice::class,
        // Future invoice types can be added here:
        // InvoiceConstants::TYPE_MONEYBIRD => MoneybirdInvoice::class,
    ];

    /**
     * Create an invoice instance by type
     * 
     * @param string $type Invoice type (customer, woocommerce, etc.)
     * @return InvoiceInterface|null
     * @throws \InvalidArgumentException If invoice type is not supported
     */
    public static function create(string $type): ?InvoiceInterface
    {
        if (!self::isValidType($type)) {
            throw new \InvalidArgumentException("Unsupported invoice type: {$type}");
        }

        $className = self::$invoiceTypes[$type];
        
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Invoice class not found: {$className}");
        }

        return new $className();
    }

    /**
     * Get all available invoice types
     * 
     * @return array Array of invoice type identifiers
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(self::$invoiceTypes);
    }

    /**
     * Check if an invoice type is valid/supported
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::$invoiceTypes);
    }

    /**
     * Register a new invoice type
     * 
     * @param string $type Invoice type identifier
     * @param string $className Full class name implementing InvoiceInterface
     * @return void
     */
    public static function registerType(string $type, string $className): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class does not exist: {$className}");
        }

        if (!is_subclass_of($className, InvoiceInterface::class)) {
            throw new \InvalidArgumentException("Class must implement InvoiceInterface: {$className}");
        }

        self::$invoiceTypes[$type] = $className;
    }

    /**
     * Get invoice instance by source
     * 
     * @param string $source Source identifier (booknetic, woocommerce, etc.)
     * @return InvoiceInterface|null
     */
    public static function createBySource(string $source): ?InvoiceInterface
    {
        $type = InvoiceConstants::getTypeBySource($source);
        
        if (!$type) {
            return null;
        }

        return self::create($type);
    }

    /**
     * Get the appropriate invoice instance for a given context
     * 
     * This method automatically determines the correct invoice type based on
     * the provided data or context.
     * 
     * @param array $context Context data to determine invoice type
     * @return InvoiceInterface|null
     */
    public static function createForContext(array $context = []): ?InvoiceInterface
    {
        // Check for WooCommerce context
        if (isset($context['source']) && $context['source'] === InvoiceConstants::SOURCE_WOOCOMMERCE) {
            return self::create(InvoiceConstants::TYPE_WOOCOMMERCE);
        }

        // Check for order_id (WooCommerce indicator)
        if (isset($context['order_id']) && !empty($context['order_id'])) {
            return self::create(InvoiceConstants::TYPE_WOOCOMMERCE);
        }

        // Check for appointment_id (Booknetic indicator)
        if (isset($context['appointment_id']) && !empty($context['appointment_id'])) {
            return self::create(InvoiceConstants::TYPE_CUSTOMER);
        }

        // Default to customer invoice for Booknetic appointments
        if (isset($context['service_id']) && isset($context['customer_id'])) {
            return self::create(InvoiceConstants::TYPE_CUSTOMER);
        }

        // Default fallback
        return self::create(InvoiceConstants::TYPE_CUSTOMER);
    }

    /**
     * Create invoice from WooCommerce order
     * 
     * @param \WC_Order $order
     * @return int|false Invoice ID on success, false on failure
     */
    public static function createFromWooCommerceOrder(\WC_Order $order)
    {
        $invoice = self::create(InvoiceConstants::TYPE_WOOCOMMERCE);
        
        if (!$invoice) {
            error_log('Failed to create WooCommerce invoice instance');
            return false;
        }

        if (method_exists($invoice, 'createFromOrder')) {
            return $invoice->createFromOrder($order);
        }

        error_log('WooCommerce invoice does not support createFromOrder method');
        return false;
    }

    /**
     * Get invoice type information
     * 
     * @param string $type
     * @return array|null
     */
    public static function getTypeInfo(string $type): ?array
    {
        if (!self::isValidType($type)) {
            return null;
        }

        $instance = self::create($type);
        
        if (!$instance) {
            return null;
        }

        return [
            'type' => $instance->getType(),
            'source' => $instance->getSource(),
            'search_fields' => $instance->getSearchFields(),
            'display_columns' => $instance->getDisplayColumns(),
            'default_structure' => $instance->getDefaultDataStructure()
        ];
    }

    /**
     * Get all invoice types with their information
     * 
     * @return array
     */
    public static function getAllTypeInfo(): array
    {
        $info = [];
        
        foreach (self::getAvailableTypes() as $type) {
            $typeInfo = self::getTypeInfo($type);
            if ($typeInfo) {
                $info[$type] = $typeInfo;
            }
        }

        return $info;
    }

    /**
     * Validate invoice data against the appropriate type
     * 
     * @param array $data
     * @param string|null $type If null, will be determined from context
     * @return bool
     */
    public static function validateInvoiceData(array $data, ?string $type = null): bool
    {
        if (!$type) {
            $invoice = self::createForContext($data);
        } else {
            $invoice = self::create($type);
        }

        if (!$invoice) {
            return false;
        }

        return $invoice->validateData($data);
    }

    /**
     * Get invoice instance by existing invoice ID
     * 
     * @param int $invoiceId
     * @return InvoiceInterface|null
     */
    public static function getInstanceByInvoiceId(int $invoiceId): ?InvoiceInterface
    {
        // Get the invoice data to determine its type
        $post = get_post($invoiceId);
        
        if (!$post || $post->post_type !== InvoiceConstants::POST_TYPE) {
            return null;
        }

        $source = get_post_meta($invoiceId, InvoiceConstants::META_SOURCE, true);
        
        if (!$source) {
            // Try to determine from other meta fields
            $invoiceType = get_post_meta($invoiceId, InvoiceConstants::META_INVOICE_TYPE, true);
            if ($invoiceType === InvoiceConstants::TYPE_SAAS) {
                // SaaS invoices are treated as WooCommerce invoices
                $source = InvoiceConstants::SOURCE_WOOCOMMERCE;
            } else {
                $source = InvoiceConstants::SOURCE_BOOKNETIC; // Default fallback
            }
        }

        return self::createBySource($source);
    }

    /**
     * Get invoice type from source
     * 
     * @param string $source
     * @return string|null
     */
    public static function getTypeFromSource(string $source): ?string
    {
        return InvoiceConstants::getTypeBySource($source);
    }

    /**
     * Get source from invoice type
     * 
     * @param string $type
     * @return string|null
     */
    public static function getSourceFromType(string $type): ?string
    {
        return InvoiceConstants::getSourceByType($type);
    }

    /**
     * Check if a plugin/feature is available
     * 
     * @param string $feature Feature name (woocommerce, moneybird, etc.)
     * @return bool
     */
    public static function isFeatureAvailable(string $feature): bool
    {
        switch ($feature) {
            case 'woocommerce':
                return class_exists('WC_Order') && function_exists('wc_get_order');
            
            case 'moneybird':
                // Future implementation
                return false;
            
            default:
                return false;
        }
    }

    /**
     * Get supported invoice types based on available features
     * 
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        // Check if we're in SaaS version and if user is not super administrator
        if (Helpers::isSaaSVersion() && !Helpers::isSuperAdmin()) {
            $supported = ['customer']; 
        }
        
        if (self::isFeatureAvailable('woocommerce')) {
            $supported[] = 'woocommerce';
        }
        
        if (self::isFeatureAvailable('moneybird')) {
            $supported[] = 'moneybird';
        }
        
        return $supported;
    }
}
