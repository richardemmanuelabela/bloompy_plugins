<?php

namespace Bloompy\Invoices\Interfaces;

/**
 * Interface for different invoice types
 * 
 * This interface defines the contract that all invoice implementations must follow,
 * allowing for easy extension with new invoice types like Moneybird, WooCommerce, etc.
 */
interface InvoiceInterface
{
    /**
     * Create a new invoice
     * 
     * @param array $data Invoice data
     * @return int|false Invoice ID on success, false on failure
     */
    public function create(array $data);

    /**
     * Get invoice by ID
     * 
     * @param int $invoiceId
     * @return array|null Invoice data or null if not found
     */
    public function get(int $invoiceId);

    /**
     * Update invoice
     * 
     * @param int $invoiceId
     * @param array $data
     * @return bool Success status
     */
    public function update(int $invoiceId, array $data): bool;

    /**
     * Delete invoice
     * 
     * @param int $invoiceId
     * @return bool Success status
     */
    public function delete(int $invoiceId): bool;

    /**
     * Get invoices for tenant/pagination
     * 
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array List of invoices
     */
    public function getForTenant(?int $tenantId = null, int $limit = 20, int $offset = 0, string $search = ''): array;

    /**
     * Count invoices for tenant
     * 
     * @param int|null $tenantId
     * @param string $search
     * @return int Count of invoices
     */
    public function countForTenant(?int $tenantId = null, string $search = ''): int;

    /**
     * Generate invoice number
     * 
     * @param int|null $tenantId
     * @return string Generated invoice number
     */
    public function generateInvoiceNumber(?int $tenantId = null): string;

    /**
     * Get invoice by invoice number
     * 
     * @param string $invoiceNumber
     * @param int|null $tenantId
     * @return array|null Invoice data or null if not found
     */
    public function getByInvoiceNumber(string $invoiceNumber, ?int $tenantId = null): ?array;

    /**
     * Get invoice type identifier
     * 
     * @return string Invoice type (e.g., 'customer', 'woocommerce', 'moneybird')
     */
    public function getType(): string;

    /**
     * Get invoice source identifier
     * 
     * @return string Source identifier (e.g., 'booknetic', 'woocommerce', 'manual')
     */
    public function getSource(): string;

    /**
     * Check if user can access invoice
     * 
     * @param int $invoiceId
     * @param string|null $customerEmail
     * @return bool
     */
    public function canUserAccess(int $invoiceId, ?string $customerEmail = null): bool;

    /**
     * Get DataTable query adapter for this invoice type
     * 
     * @param int|null $tenantId
     * @return mixed DataTable query object
     */
    public function getDataTableQuery(?int $tenantId = null);

    /**
     * Get search fields for this invoice type
     * 
     * @return array Array of searchable field names
     */
    public function getSearchFields(): array;

    /**
     * Get display columns configuration for DataTable
     * 
     * @return array Column configuration array
     */
    public function getDisplayColumns(): array;

    /**
     * Validate invoice data before creation
     * 
     * @param array $data
     * @return bool Validation result
     */
    public function validateData(array $data): bool;

    /**
     * Get default invoice data structure
     * 
     * @return array Default data structure
     */
    public function getDefaultDataStructure(): array;

    /**
     * Format invoice data for display
     * 
     * @param array $invoiceData
     * @return array Formatted data
     */
    public function formatForDisplay(array $invoiceData): array;

    /**
     * Get invoice PDF data
     * 
     * @param int $invoiceId
     * @return array PDF generation data
     */
    public function getPdfData(int $invoiceId): array;
}


