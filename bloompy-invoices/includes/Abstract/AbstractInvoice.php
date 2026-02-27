<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Abstract;

use Bloompy\Invoices\Interfaces\InvoiceInterface;
use Bloompy\Invoices\Constants\InvoiceConstants;
use Bloompy\Invoices\ValueObjects\TenantId;
use Bloompy\Invoices\Exceptions\{InvoiceNotFoundException, InvoiceCreationException};
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

/**
 * Abstract base class for invoice implementations
 * 
 * Provides common functionality that can be shared across different invoice types
 * while allowing each type to implement its specific logic.
 */
abstract class AbstractInvoice implements InvoiceInterface
{
    protected const POST_TYPE = InvoiceConstants::POST_TYPE;
    
    /**
     * Get current tenant ID as TenantId value object
     * 
     * @return TenantId
     */
    protected function getCurrentTenantId(): TenantId
    {
        if (!class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            return TenantId::superAdmin(); // fallback for non-tenant installations
        }
        
        return TenantId::fromValue(Permission::tenantId());
    }
    
    /**
     * Get current tenant ID as nullable int (for backward compatibility)
     * 
     * @return int|null
     */
    protected function getCurrentTenantIdValue(): ?int
    {
        return $this->getCurrentTenantId()->getValue();
    }

    /**
     * Create a new invoice post
     * 
     * @param array $metaInput
     * @param string $title
     * @return int|false Post ID on success, false on failure
     */
    protected function createInvoicePost(array $metaInput, string $title)
    {
        try {
            $post_data = [
                'post_type' => static::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $title,
                'meta_input' => $metaInput
            ];

            error_log('Creating invoice post with data: ' . print_r($post_data, true));

            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                error_log('wp_insert_post error: ' . $post_id->get_error_message());
                return false;
            }

            error_log('Invoice post created with ID: ' . $post_id);
            return $post_id;
            
        } catch (\Exception $e) {
            error_log('Exception in AbstractInvoice::createInvoicePost: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice by ID
     * 
     * @param int $invoiceId
     * @return array|null
     * @throws InvoiceNotFoundException
     */
    public function get(int $invoiceId): ?array
    {
        $post = get_post($invoiceId);
        
        if (!$post || $post->post_type !== static::POST_TYPE) {
            throw InvoiceNotFoundException::forId($invoiceId);
        }

        return $this->formatInvoiceData($post);
    }

    /**
     * Update invoice
     * 
     * @param int $invoiceId
     * @param array $data
     * @return bool
     */
    public function update(int $invoiceId, array $data): bool
    {
        $post = get_post($invoiceId);
        
        if (!$post || $post->post_type !== static::POST_TYPE) {
            return false;
        }

        // Update post meta
        foreach ($data as $key => $value) {
            if ($key !== 'ID') {
                update_post_meta($invoiceId, $key, $value);
            }
        }

        return true;
    }

    /**
     * Delete invoice
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function delete(int $invoiceId): bool
    {
        $post = get_post($invoiceId);
        
        if (!$post || $post->post_type !== static::POST_TYPE) {
            return false;
        }

		$result = wp_delete_post($invoiceId, true);
		return $result !== false && $result !== null;
    }

    /**
     * Count invoices for tenant
     * 
     * @param int|null $tenantId
     * @param string $search
     * @return int
     */
    public function countForTenant(?int $tenantId = null, string $search = ''): int
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantId();
        }

        $args = [
            'post_type' => static::POST_TYPE,
            'meta_query' => $this->getTenantMetaQuery($tenantId),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $posts = get_posts($args);
        return count($posts);
    }

    /**
     * Check if user can access invoice
     * 
     * @param int $invoiceId
     * @param string|null $customerEmail
     * @return bool
     */
    public function canUserAccess(int $invoiceId, ?string $customerEmail = null): bool
    {
        $invoice = $this->get($invoiceId);
        
        if (!$invoice) {
            return false;
        }

        // Admin can always access
        if (current_user_can('administrator')) {
            return true;
        }

        // Tenant admin can access their own invoices
        if (class_exists('BookneticApp\\Providers\\Core\\Permission') && 
            Permission::tenantId() == $invoice['tenant_id'] && current_user_can('manage_options')) {
            return true;
        }

        // Customer can access their own invoice
        if ($customerEmail && $this->canCustomerAccessInvoice($invoice, $customerEmail)) {
            return true;
        }

        return false;
    }

    /**
     * Check if customer can access specific invoice
     * 
     * @param array $invoice
     * @param string $customerEmail
     * @return bool
     */
    protected function canCustomerAccessInvoice(array $invoice, string $customerEmail): bool
    {
        return isset($invoice['customer_email']) && $customerEmail === $invoice['customer_email'];
    }

    /**
     * Format invoice data from post object
     * 
     * @param \WP_Post $post
     * @return array
     */
    protected function formatInvoiceData(\WP_Post $post): array
    {
        $meta = get_post_meta($post->ID);
        $data = ['ID' => $post->ID];
        $data['id'] = $post->ID;

        // Convert meta array to simple key-value pairs
        foreach ($meta as $key => $value) {
            $data[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
        }

        $data['created_at'] = $post->post_date;
        $data['updated_at'] = $post->post_modified;

        // Decode JSON fields
        if (!empty($data['service_extras'])) {
            $data['service_extras'] = json_decode($data['service_extras'], true);
        }
        if (!empty($data['pricing_breakdown'])) {
            $data['pricing_breakdown'] = json_decode($data['pricing_breakdown'], true);
        }

        return $data;
    }

    /**
     * Get tenant meta query for filtering
     * 
     * @param int $tenantId
     * @return array
     */
    protected function getTenantMetaQuery(int $tenantId): array
    {
        return [
            [
                'key' => 'tenant_id',
                'value' => $tenantId,
                'compare' => '='
            ]
        ];
    }

    /**
     * Get base query args for invoice retrieval
     * 
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function getBaseQueryArgs(?int $tenantId = null, int $limit = 20, int $offset = 0): array
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantId();
        }

        return [
            'post_type' => static::POST_TYPE,
            'meta_query' => $this->getTenantMetaQuery($tenantId),
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
    }

    /**
     * Validate required fields for invoice creation
     * 
     * @param array $data
     * @param array $requiredFields
     * @return bool
     */
    protected function validateRequiredFields(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                error_log("Missing required field: {$field}");
                return false;
            }
        }
        return true;
    }

    /**
     * Generate secure token for invoice access
     * 
     * @param string $invoiceNumber
     * @param string $customerEmail
     * @return string
     */
    public function generateInvoiceToken(string $invoiceNumber, string $customerEmail): string
    {
        $salt = wp_salt('auth');
        return hash('sha256', $invoiceNumber . $customerEmail . $salt);
    }

    /**
     * Verify invoice access token
     * 
     * @param string $invoiceNumber
     * @param string $customerEmail
     * @param string $token
     * @return bool
     */
    public function verifyInvoiceToken(string $invoiceNumber, string $customerEmail, string $token): bool
    {
        $expectedToken = $this->generateInvoiceToken($invoiceNumber, $customerEmail);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Get company information from tenant settings or WordPress options
     * 
     * @return array
     */
    protected function getTenantCompanyInfo(): array
    {
        $tenant_id = $this->getCurrentTenantIdValue();

        if ($tenant_id > 0 && class_exists('\BookneticSaaS\Models\Tenant')) {
            // SaaS installation - get from tenant data
            return [
                'company_name' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_name') ?: '',
                'address' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_address') ?: '',
                'zipcode' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_zipcode') ?: '',
                'city' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_city') ?: '',
                'country' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_country') ?: '',
                'phone' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_phone') ?: '',
                'iban' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_iban') ?: '',
                'kvk_number' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_kvk_number') ?: '',
                'btw_number' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_btw_number') ?: '',
                'footer_text' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_footer_text') ?: '',
                'logo' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_logo') ?: ''
            ];
        } else {
            // Non-SaaS installation - get from WordPress options
            return [
                'company_name' => get_option('bloompy_invoice_company_name', ''),
                'address' => get_option('bloompy_invoice_company_address', ''),
                'zipcode' => get_option('bloompy_invoice_company_zipcode', ''),
                'city' => get_option('bloompy_invoice_company_city', ''),
                'country' => get_option('bloompy_invoice_company_country', ''),
                'phone' => get_option('bloompy_invoice_company_phone', ''),
                'iban' => get_option('bloompy_invoice_company_iban', ''),
                'kvk_number' => get_option('bloompy_invoice_company_kvk_number', ''),
                'btw_number' => get_option('bloompy_invoice_company_btw_number', ''),
                'footer_text' => get_option('bloompy_invoice_company_footer_text', ''),
                'logo' => get_option('bloompy_invoice_company_logo', '')
            ];
        }
    }

    /**
     * Abstract methods that must be implemented by concrete classes
     */
    abstract public function getType(): string;
    abstract public function getSource(): string;
    abstract public function getSearchFields(): array;
    abstract public function getDisplayColumns(): array;
    abstract public function getDefaultDataStructure(): array;
    abstract public function validateData(array $data): bool;
    abstract public function formatForDisplay(array $invoiceData): array;
    abstract public function getPdfData(int $invoiceId): array;
}
