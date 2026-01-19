<?php

namespace Bloompy\Invoices\Services;

use Bloompy\Invoices\Factories\InvoiceFactory;
use Bloompy\Invoices\Interfaces\InvoiceInterface;

/**
 * Invoice Service
 * 
 * This service acts as a facade for the invoice system, providing a clean
 * interface that replaces the tightly coupled Invoice model. It automatically
 * determines the appropriate invoice type and delegates to the correct implementation.
 */
class InvoiceService
{
    /**
     * Create a new invoice
     * 
     * @param array $data Invoice data
     * @param string|null $type Specific invoice type (optional, will be auto-detected)
     * @return int|false Invoice ID on success, false on failure
     */
    public static function create(array $data, ?string $type = null)
    {
        try {
            if ($type) {
                $invoice = InvoiceFactory::create($type);
            } else {
                $invoice = InvoiceFactory::createForContext($data);
            }

            if (!$invoice) {
                error_log('InvoiceService::create - No suitable invoice type found for data: ' . json_encode($data));
                return false;
            }

            // Ensure source is set correctly
            if (!isset($data['source'])) {
                $data['source'] = $invoice->getSource();
            }

            return $invoice->create($data);

        } catch (\Exception $e) {
            error_log('InvoiceService::create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice by ID
     * 
     * @param int $invoiceId
     * @return array|null
     */
    public static function get(int $invoiceId): ?array
    {
        try {
            $invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
            
            if (!$invoice) {
                return null;
            }

            return $invoice->get($invoiceId);

        } catch (\Exception $e) {
            error_log('InvoiceService::get error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update invoice
     * 
     * @param int $invoiceId
     * @param array $data
     * @return bool
     */
    public static function update(int $invoiceId, array $data): bool
    {
        try {
            $invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
            
            if (!$invoice) {
                return false;
            }

            return $invoice->update($invoiceId, $data);

        } catch (\Exception $e) {
            error_log('InvoiceService::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete invoice
     * 
     * @param int $invoiceId
     * @return bool
     */
    public static function delete(int $invoiceId): bool
    {
        try {
            $invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
            
            if (!$invoice) {
                return false;
            }

            return $invoice->delete($invoiceId);

        } catch (\Exception $e) {
            error_log('InvoiceService::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoices for tenant with pagination
     * 
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @param string|null $type Filter by specific invoice type
     * @return array
     */
    public static function getForTenant(?int $tenantId = null, int $limit = 20, int $offset = 0, string $search = '', ?string $type = null): array
    {
        try {
            if ($type) {
                $invoice = InvoiceFactory::create($type);
                return $invoice ? $invoice->getForTenant($tenantId, $limit, $offset, $search) : [];
            }

            // Get all invoice types and combine results
            $allInvoices = [];
            $supportedTypes = InvoiceFactory::getSupportedTypes();

            foreach ($supportedTypes as $invoiceType) {
                $invoice = InvoiceFactory::create($invoiceType);
                if ($invoice) {
                    $typeInvoices = $invoice->getForTenant($tenantId, $limit, $offset, $search);
                    $allInvoices = array_merge($allInvoices, $typeInvoices);
                }
            }

            // Sort by creation date (newest first)
            usort($allInvoices, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Apply pagination to combined results
            return array_slice($allInvoices, $offset, $limit);

        } catch (\Exception $e) {
            error_log('InvoiceService::getForTenant error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count invoices for tenant
     * 
     * @param int|null $tenantId
     * @param string $search
     * @param string|null $type Filter by specific invoice type
     * @return int
     */
    public static function countForTenant(?int $tenantId = null, string $search = '', ?string $type = null): int
    {
        try {
            if ($type) {
                $invoice = InvoiceFactory::create($type);
                return $invoice ? $invoice->countForTenant($tenantId, $search) : 0;
            }

            // Count all invoice types
            $totalCount = 0;
            $supportedTypes = InvoiceFactory::getSupportedTypes();

            foreach ($supportedTypes as $invoiceType) {
                $invoice = InvoiceFactory::create($invoiceType);
                if ($invoice) {
                    $totalCount += $invoice->countForTenant($tenantId, $search);
                }
            }

            return $totalCount;

        } catch (\Exception $e) {
            error_log('InvoiceService::countForTenant error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate invoice number
     * 
     * @param string $type Invoice type
     * @param int|null $tenantId
     * @return string
     */
    public static function generateInvoiceNumber(string $type, ?int $tenantId = null): string
    {
        try {
            $invoice = InvoiceFactory::create($type);
            
            if (!$invoice) {
                error_log('InvoiceService::generateInvoiceNumber - Invalid invoice type: ' . $type);
                return '';
            }

            return $invoice->generateInvoiceNumber($tenantId);

        } catch (\Exception $e) {
            error_log('InvoiceService::generateInvoiceNumber error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Check if user can access invoice
     * 
     * @param int $invoiceId
     * @param string|null $customerEmail
     * @return bool
     */
    public static function canUserAccess(int $invoiceId, ?string $customerEmail = null): bool
    {
        try {
            $invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
            
            if (!$invoice) {
                return false;
            }

            return $invoice->canUserAccess($invoiceId, $customerEmail);

        } catch (\Exception $e) {
            error_log('InvoiceService::canUserAccess error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get DataTable query adapter
     * 
     * @param string|null $type Specific invoice type (optional)
     * @param int|null $tenantId
     * @return mixed
     */
    public static function getDataTableQuery(?string $type = null, ?int $tenantId = null)
    {
        try {
            if ($type) {
                $invoice = InvoiceFactory::create($type);
                return $invoice ? $invoice->getDataTableQuery($tenantId) : null;
            }

            // For backward compatibility, return customer invoice query as default
            $invoice = InvoiceFactory::create('customer');
            return $invoice ? $invoice->getDataTableQuery($tenantId) : null;

        } catch (\Exception $e) {
            error_log('InvoiceService::getDataTableQuery error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get search fields for invoice type
     * 
     * @param string $type
     * @return array
     */
    public static function getSearchFields(string $type): array
    {
        try {
            $invoice = InvoiceFactory::create($type);
            return $invoice ? $invoice->getSearchFields() : [];

        } catch (\Exception $e) {
            error_log('InvoiceService::getSearchFields error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get display columns for invoice type
     * 
     * @param string $type
     * @return array
     */
    public static function getDisplayColumns(string $type): array
    {
        try {
            $invoice = InvoiceFactory::create($type);
            return $invoice ? $invoice->getDisplayColumns() : [];

        } catch (\Exception $e) {
            error_log('InvoiceService::getDisplayColumns error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate invoice data
     * 
     * @param array $data
     * @param string|null $type
     * @return bool
     */
    public static function validateData(array $data, ?string $type = null): bool
    {
        return InvoiceFactory::validateInvoiceData($data, $type);
    }

    /**
     * Format invoice for display
     * 
     * @param array $invoiceData
     * @param string|null $type
     * @return array
     */
    public static function formatForDisplay(array $invoiceData, ?string $type = null): array
    {
        try {
            if (!$type) {
                $type = self::determineInvoiceType($invoiceData);
            }

            $invoice = InvoiceFactory::create($type);
            
            if (!$invoice) {
                return $invoiceData; // Return original data if no formatter found
            }

            return $invoice->formatForDisplay($invoiceData);

        } catch (\Exception $e) {
            error_log('InvoiceService::formatForDisplay error: ' . $e->getMessage());
            return $invoiceData;
        }
    }

    /**
     * Get PDF data for invoice
     * 
     * @param int $invoiceId
     * @return array
     */
    public static function getPdfData(int $invoiceId): array
    {
        try {
            $invoice = InvoiceFactory::getInstanceByInvoiceId($invoiceId);
            
            if (!$invoice) {
                return [];
            }

            return $invoice->getPdfData($invoiceId);

        } catch (\Exception $e) {
            error_log('InvoiceService::getPdfData error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create invoice from WooCommerce order
     * 
     * @param \WC_Order $order
     * @return int|false
     */
    public static function createFromWooCommerceOrder(\WC_Order $order)
    {
        return InvoiceFactory::createFromWooCommerceOrder($order);
    }

    /**
     * Get invoice by invoice number
     * 
     * @param string $invoiceNumber
     * @param int|null $tenantId
     * @return array|null
     */
    public static function getByInvoiceNumber(string $invoiceNumber, ?int $tenantId = null): ?array
    {
        try {
            // Search across all supported invoice types
            $supportedTypes = InvoiceFactory::getSupportedTypes();

            foreach ($supportedTypes as $type) {
                $invoice = InvoiceFactory::create($type);
                if ($invoice) {
                    $invoiceData = $invoice->getByInvoiceNumber($invoiceNumber, $tenantId);
                    if ($invoiceData) {
                        return $invoiceData;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            error_log('InvoiceService::getByInvoiceNumber error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all available invoice types
     * 
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return InvoiceFactory::getAvailableTypes();
    }

    /**
     * Get supported invoice types
     * 
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return InvoiceFactory::getSupportedTypes();
    }

    /**
     * Determine invoice type from data
     * 
     * @param array $data
     * @return string
     */
    private static function determineInvoiceType(array $data): string
    {
        if (isset($data['source'])) {
            $type = InvoiceFactory::getTypeFromSource($data['source']);
            if ($type) {
                return $type;
            }
        }

        if (isset($data['order_id'])) {
            return 'woocommerce';
        }

        return 'customer'; // Default fallback
    }
}


