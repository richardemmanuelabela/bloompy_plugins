<?php

namespace Bloompy\Invoices\Types;

use Bloompy\Invoices\Abstract\AbstractInvoice;
use Bloompy\Invoices\Interfaces\InvoiceInterface;
use BookneticApp\Providers\Helpers\Helper;
use WC_Order;

/**
 * WooCommerce Invoice implementation
 * 
 * Handles invoices created from WooCommerce orders and subscriptions.
 * This will be used by the bloompy-woocommerce-bridge plugin.
 */
class WooCommerceInvoice extends AbstractInvoice implements InvoiceInterface
{
    /**
     * Create a new WooCommerce invoice
     * 
     * @param array $data Invoice data
     * @return int|false Invoice ID on success, false on failure
     */
    public function create(array $data)
    {
        if (!$this->validateData($data)) {
            return false;
        }

        $tenantId = $this->getCurrentTenantIdValue();
        $metaInput = $this->prepareWooCommerceInvoiceMeta($tenantId, $data);
        $title = sprintf('WooCommerce Invoice #%s', $data['invoice_number']);

        return $this->createInvoicePost($metaInput, $title);
    }

    /**
     * Get invoices for tenant with pagination
     * 
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array
     */
    public function getForTenant(?int $tenantId = null, int $limit = 20, int $offset = 0, string $search = ''): array
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantIdValue();
        }

        // For super admin context (tenantId = null or 0), get all WooCommerce invoices
        if ($tenantId === null || $tenantId === 0) {
            $args = [
                'post_type' => static::POST_TYPE,
                'posts_per_page' => $limit,
                'offset' => $offset,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => 'source',
                        'value' => 'woocommerce',
                        'compare' => '='
                    ]
                ]
            ];
        } else {
            $args = $this->getBaseQueryArgs($tenantId, $limit, $offset);
            
            // Add source filter for WooCommerce invoices
            $args['meta_query'][] = [
                'key' => 'source',
                'value' => 'woocommerce',
                'compare' => '='
            ];
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $posts = get_posts($args);
        $invoices = [];

        foreach ($posts as $post) {
            $invoices[] = $this->formatInvoiceData($post);
        }

        return $invoices;
    }

    /**
     * Generate invoice number for WooCommerce invoices
     * 
     * @param int|null $tenantId
     * @return string
     */
    public function generateInvoiceNumber(?int $tenantId = null): string
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantIdValue();
        }

        $year = date('Y');
        $prefix = 'WC-' . $year . '-';

        // Get the highest invoice number for this year across ALL tenants (global numbering)
        global $wpdb;
        
        // Get all WooCommerce invoice numbers for this year (not filtered by tenant)
        $query = $wpdb->prepare("
            SELECT pm.meta_value as invoice_number
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_source ON p.ID = pm_source.post_id
            WHERE p.post_type = %s
            AND p.post_status IN ('publish', 'draft')
            AND pm.meta_key = 'invoice_number'
            AND pm_source.meta_key = 'source'
            AND pm_source.meta_value = 'woocommerce'
            AND pm.meta_value LIKE %s
        ", static::POST_TYPE, $prefix . '%');

        $invoice_numbers = $wpdb->get_col($query);
        
        // Find the highest number by extracting and comparing numeric parts
        $highest_number = 0;
        if (!empty($invoice_numbers)) {
            foreach ($invoice_numbers as $invoice_number) {
                $number = intval(str_replace($prefix, '', $invoice_number));
                if ($number > $highest_number) {
                    $highest_number = $number;
                }
            }
            $next_number = $highest_number + 1;
            error_log("WooCommerceInvoice::generateInvoiceNumber - Found " . count($invoice_numbers) . " existing invoices globally. Highest: $highest_number, Next: $next_number (for tenant $tenantId)");
        } else {
            $next_number = 1;
            error_log("WooCommerceInvoice::generateInvoiceNumber - No existing invoices found globally. Starting at: 1 (for tenant $tenantId)");
        }

        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice by invoice number
     * 
     * @param string $invoiceNumber
     * @param int|null $tenantId
     * @return array|null
     */
    public function getByInvoiceNumber(string $invoiceNumber, ?int $tenantId = null): ?array
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantIdValue();
        }
        
        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'invoice_number',
                'value' => $invoiceNumber,
                'compare' => '='
            ],
            [
                'key' => 'source',
                'value' => 'woocommerce',
                'compare' => '='
            ]
        ];
        
        // Add tenant filter if provided
        if ($tenantId) {
            $meta_query[] = [
                'key' => 'tenant_id',
                'value' => $tenantId,
                'compare' => '='
            ];
        }
        
        $args = [
            'post_type' => static::POST_TYPE,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'posts_per_page' => 1
        ];
        
        $posts = get_posts($args);
        
        if (empty($posts)) {
            return null;
        }
        
        return $this->formatInvoiceData($posts[0]);
    }

    /**
     * Get invoice type identifier
     * 
     * @return string
     */
    public function getType(): string
    {
        return 'woocommerce';
    }

    /**
     * Get invoice source identifier
     * 
     * @return string
     */
    public function getSource(): string
    {
        return 'woocommerce';
    }

    /**
     * Get search fields for WooCommerce invoices
     * 
     * @return array
     */
    public function getSearchFields(): array
    {
        return [
            'invoice_number',
            'order_number',
            'customer_name',
            'customer_email',
            'product_name',
            'status'
        ];
    }

    /**
     * Get display columns configuration for DataTable
     * 
     * @return array
     */
    public function getDisplayColumns(): array
    {
        return [
            'checkbox' => function($row) {
                return '<input type="checkbox" class="invoice_checkbox_invoice_number" value="' . $row['invoice_number'] . '"/>';
            },
            'invoice_number' => 'Invoice #',
            'order_number' => 'Order #',
            'customer' => function($row) {
                return '<strong>' . $row['customer_name'] . '</strong><br><small>' . $row['customer_email'] . '</small>';
            },
            'product_name' => 'Product',
            'total_amount' => 'Amount',
            'status' => function($row) {
                $badgeClass = 'badge-secondary';
                if ($row['status'] === 'pending') $badgeClass = 'badge-warning';
                if ($row['status'] === 'paid') $badgeClass = 'badge-success';
                if ($row['status'] === 'cancelled') $badgeClass = 'badge-danger';
                return '<span>' . ucfirst($row['status']) . '</span>';
            },
            'invoice_date' => 'Invoice Date',
            'created_at' => 'Created',
            'actions' => function($row) {
                return '<div class="btn-group" role="group">'
                    . '<button type="button" class="btn btn-sm" onclick="bloompy_invoices.viewInvoice(' . $row['ID'] . ')"><i class="fa fa-eye"></i></button>'
                    . '<button type="button" class="btn btn-sm" onclick="bloompy_invoices.downloadInvoice(' . $row['ID'] . ')"><i class="fa fa-download"></i></button>'
                    . '<button type="button" class="btn btn-sm" onclick="bloompy_invoices.deleteInvoice(' . $row['ID'] . ')"><i class="fa fa-trash"></i></button>'
                    . '</div>';
            }
        ];
    }

    /**
     * Get default data structure for WooCommerce invoices
     * 
     * @return array
     */
    public function getDefaultDataStructure(): array
    {
        return [
            'invoice_number' => '',
            'order_id' => '',
            'order_number' => '',
            'customer_id' => '',
            'customer_email' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'product_id' => '',
            'product_name' => '',
            'product_sku' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'currency' => 'EUR',
            'invoice_date' => '',
            'due_date' => '',
            'status' => 'pending',
            'payment_date' => '',
            'payment_method' => '',
            'billing_address' => [],
            'shipping_address' => [],
            'notes' => '',
            'source' => 'woocommerce',
            'order_items' => [],
            'order_meta' => []
        ];
    }

    /**
     * Validate WooCommerce invoice data
     * 
     * @param array $data
     * @return bool
     */
    public function validateData(array $data): bool
    {
        $requiredFields = [
            'invoice_number',
            'order_id',
            'customer_email',
            'customer_name',
            'product_name',
            'total_amount'
        ];

        return $this->validateRequiredFields($data, $requiredFields);
    }

    /**
     * Format invoice data for display
     * 
     * @param array $invoiceData
     * @return array
     */
    public function formatForDisplay(array $invoiceData): array
    {
        // Format amounts for display
        $invoiceData['unit_price_formatted'] = Helper::price($invoiceData['unit_price'] ?? 0);
        $invoiceData['subtotal_formatted'] = Helper::price($invoiceData['subtotal'] ?? 0);
        $invoiceData['tax_amount_formatted'] = Helper::price($invoiceData['tax_amount'] ?? 0);
        $invoiceData['total_amount_formatted'] = Helper::price($invoiceData['total_amount'] ?? 0);

        // Format dates
        if (!empty($invoiceData['invoice_date'])) {
            $invoiceData['invoice_date_formatted'] = date('d/m/Y', strtotime($invoiceData['invoice_date']));
        }
        if (!empty($invoiceData['due_date'])) {
            $invoiceData['due_date_formatted'] = date('d/m/Y', strtotime($invoiceData['due_date']));
        }

        // Format addresses
        if (!empty($invoiceData['billing_address'])) {
            $invoiceData['billing_address_formatted'] = $this->formatAddress($invoiceData['billing_address']);
        }
        if (!empty($invoiceData['shipping_address'])) {
            $invoiceData['shipping_address_formatted'] = $this->formatAddress($invoiceData['shipping_address']);
        }

        return $invoiceData;
    }

    /**
     * Get PDF data for WooCommerce invoice
     * 
     * @param int $invoiceId
     * @return array
     */
    public function getPdfData(int $invoiceId): array
    {
        $invoice = $this->get($invoiceId);
        if (!$invoice) {
            return [];
        }

        $companyInfo = $this->getTenantCompanyInfo();
        
        return [
            'invoice' => $this->formatForDisplay($invoice),
            'company' => $companyInfo,
            'template' => 'woocommerce-invoice',
            'filename' => 'woocommerce-invoice-' . $invoice['invoice_number'] . '.pdf'
        ];
    }

    /**
     * Get DataTable query adapter for WooCommerce invoices
     * 
     * @param int|null $tenantId
     * @return mixed
     */
    public function getDataTableQuery(?int $tenantId = null)
    {
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantIdValue();
        }

        // Pass parent instance to anonymous class so it can use parent's methods
        $parentInstance = $this;

        // Return anonymous class that implements DataTable query interface
        return new class($tenantId, $parentInstance) {
            private $args;
            private $filters;
            private $orderBy;
            private $order;
            private $limit;
            private $offset;
            private $search;
            private $searchFields;
            private $tenant_id;
            private $parent;
            
            public function __construct($tenant_id, $parent) {
                $this->tenant_id = $tenant_id;
                $this->parent = $parent;
                
                // For super admin context (null or 0), don't filter by tenant
                if ($tenant_id === null || $tenant_id === 0) {
                    $this->args = [
                        'post_type' => 'bloompy_invoice',
                        'post_status' => 'publish',
                        'fields' => 'ids',
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'posts_per_page' => 25,
                        'offset' => 0,
                        'meta_query' => [
                            [
                                'key' => 'source',
                                'value' => 'woocommerce',
                                'compare' => '='
                            ]
                        ],
                        's' => '',
                    ];
                } else {
                    $this->args = [
                        'post_type' => 'bloompy_invoice',
                        'post_status' => 'publish',
                        'fields' => 'ids',
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'posts_per_page' => 25,
                        'offset' => 0,
                        'meta_query' => [
                            [
                                'key' => 'tenant_id',
                                'value' => $this->tenant_id,
                                'compare' => '='
                            ],
                            [
                                'key' => 'source',
                                'value' => 'woocommerce',
                                'compare' => '='
                            ]
                        ],
                        's' => '',
                    ];
                }

                $this->filters = \BookneticApp\Providers\Helpers\Helper::_post('filters', [], 'arr');
                $this->orderBy = 'date';
                $this->order = 'DESC';
                $this->limit = 25;
                $this->offset = 0;
                $this->search = '';
                $this->searchFields = ['invoice_number', 'order_number', 'customer_name', 'customer_email', 'product_name', 'status'];
            }

            public function where($key, $value = null, $operator = '=', $invoice_type = null) {
                if (is_callable($key)) {
                    call_user_func($key, $this);
                    return $this;
                }
                
                if ($value !== null && in_array($key, ['status', 'product_name', 'customer_email', 'customer_name', 'invoice_date', 'order_number'])) {
                    if ($operator === 'like') {
                        $this->args['meta_query'][] = [
                            'key' => $key,
                            'value' => $value,
                            'compare' => 'LIKE'
                        ];
                    } else {
                        $this->args['meta_query'][] = [
                            'key' => $key,
                            'value' => $value,
                            'compare' => $operator
                        ];
                    }
                }
                return $this;
            }

            public function orderBy($orderBy, $order = 'DESC') {
                $this->args['orderby'] = $orderBy;
                $this->args['order'] = $order;
                return $this;
            }

            public function limit($limit) {
                $this->args['posts_per_page'] = $limit;
                return $this;
            }

            public function offset($offset) {
                $this->args['offset'] = $offset;
                return $this;
            }

            public function fetchAll() {
                if (!empty($this->search) || !empty($this->filters)) {
                    $all_args = [
                        'post_type' => 'bloompy_invoice',
                        'post_status' => 'publish',
                        'fields' => 'ids',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'source',
                                'value' => 'woocommerce',
                                'compare' => '='
                            ]
                        ]
                    ];
                    
                    $all_ids = get_posts($all_args);
                    
                    $filtered_ids = [];
                    foreach ($all_ids as $id) {
                        try {
                            $invoice = $this->parent->get($id);
                            if ($this->matchesSearch($invoice, $this->search)) {
                                $filtered_ids[] = $id;
                            }
                        } catch (\Exception $e) {
                            // Skip invalid invoices
                            continue;
                        }
                    }
                    
                    $filtered_ids = array_slice($filtered_ids, $this->args['offset'], $this->args['posts_per_page']);
                    
                    $invoices = [];
                    foreach ($filtered_ids as $id) {
                        try {
                            $invoices[] = $this->parent->get($id);
                        } catch (\Exception $e) {
                            // Skip invalid invoices
                            continue;
                        }
                    }
                    return $invoices;
                }

                $ids = get_posts($this->args);
                $invoices = [];
                foreach ($ids as $id) {
                    try {
                        $invoices[] = $this->parent->get($id);
                    } catch (\Exception $e) {
                        // Skip invalid invoices
                        continue;
                    }
                }
                return $invoices;
            }
            
            private function matchesSearch($invoice, $searchTerm) {
                $searchTerm = strtolower(trim($searchTerm));
                
                foreach ($this->searchFields as $field) {
                    if (isset($invoice[$field])) {
                        $fieldValue = strtolower(trim($invoice[$field]));
                        if (strpos($fieldValue, $searchTerm) !== false) {
                            return true;
                        }
                    }
                }
                return false;
            }

            public function count() {
                if (!empty($this->search) || !empty($this->filters)) {
                    // For super admin context (null or 0), don't filter by tenant
                    if ($this->tenant_id === null || $this->tenant_id === 0) {
                        $all_args = [
                            'post_type' => 'bloompy_invoice',
                            'post_status' => 'publish',
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => 'source',
                                    'value' => 'woocommerce',
                                    'compare' => '='
                                ]
                            ]
                        ];
                    } else {
                        $all_args = [
                            'post_type' => 'bloompy_invoice',
                            'post_status' => 'publish',
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => 'tenant_id',
                                    'value' => $this->tenant_id,
                                    'compare' => '='
                                ],
                                [
                                    'key' => 'source',
                                    'value' => 'woocommerce',
                                    'compare' => '='
                                ]
                            ]
                        ];
                    }
                    
                    $all_ids = get_posts($all_args);

                    $filtered_count = 0;
                    foreach ($all_ids as $id) {
                        try {
                            $invoice = $this->parent->get($id);
                            if ($this->matchesSearch($invoice, $this->search)) {
                                $filtered_count++;
                            }
                        } catch (\Exception $e) {
                            // Skip invalid invoices
                            continue;
                        }
                    }
                    return $filtered_count;
                }
                
                $args = $this->args;
                $args['posts_per_page'] = -1;
                $ids = get_posts($args);
                return count($ids);
            }

            public function isGroupQuery() { return false; }
            public function countGroupBy() { return 0; }
            
            public function like($field, $value, $combinator = 'AND') {
                $this->search = trim($value, '%');
                return $this;
            }
            
            public function orLike($field, $value) {
                $this->search = trim($value, '%');
                return $this;
            }
            
            public function orWhere($field, $operator, $value) {
                if ($operator === 'like') {
                    $this->search = trim($value, '%');
                }
                return $this;
            }
        };
    }

    /**
     * Create invoice from WooCommerce order
     * 
     * @param WC_Order $order
     * @return int|false Invoice ID on success, false on failure
     */
    public function createFromOrder(WC_Order $order)
    {
        $items = $order->get_items();
        if (empty($items)) {
            return false;
        }

        // Get tenant ID from the order's user
        $tenantId = $this->getTenantIdFromOrder($order);
        
        if (!$tenantId) {
            error_log('WooCommerceInvoice::createFromOrder - No tenant found for order ' . $order->get_id());
            return false;
        }

        // Get first item for main product info
        $first_item = reset($items);
        $product = $first_item->get_product();

        $invoiceData = [
            'invoice_number' => $this->generateInvoiceNumber($tenantId),
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'customer_id' => $order->get_user_id(),
            'customer_email' => $order->get_billing_email(),
            'customer_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'customer_phone' => $order->get_billing_phone(),
            'product_id' => $product ? $product->get_id() : '',
            'product_name' => $product ? $product->get_name() : 'WooCommerce Product',
            'product_sku' => $product ? $product->get_sku() : '',
            'quantity' => $first_item->get_quantity(),
            'unit_price' => $first_item->get_total() / $first_item->get_quantity(),
            'subtotal' => $order->get_subtotal(),
            'tax_amount' => $order->get_total_tax(),
            'total_amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'invoice_date' => $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d H:i:s') : current_time('mysql'),
            'due_date' => $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d H:i:s') : current_time('mysql'),
            'status' => $this->mapOrderStatusToInvoiceStatus($order->get_status()),
            'payment_date' => $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d H:i:s') : '',
            'payment_method' => $order->get_payment_method_title(),
            'billing_address' => $this->formatWooCommerceAddress($order->get_address('billing')),
            'shipping_address' => $this->formatWooCommerceAddress($order->get_address('shipping')),
            'notes' => $order->get_customer_note(),
            'source' => 'woocommerce',
            'order_items' => $this->formatOrderItems($items),
            'order_meta' => $this->getOrderMeta($order),
            'tenant_id' => $tenantId // Pass tenant_id in the data
        ];

        return $this->createWithTenantId($invoiceData, $tenantId);
    }

    /**
     * Prepare WooCommerce invoice meta data
     * 
     * @param int $tenantId
     * @param array $data
     * @return array
     */
    protected function prepareWooCommerceInvoiceMeta(int $tenantId, array $data): array
    {
        $companyInfo = $this->getTenantCompanyInfo();
        
        return array_merge([
            'tenant_id' => $tenantId,
            'invoice_number' => $data['invoice_number'],
            'order_id' => $data['order_id'],
            'order_number' => $data['order_number'],
            'customer_id' => $data['customer_id'],
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? '',
            'product_id' => $data['product_id'],
            'product_name' => $data['product_name'],
            'product_sku' => $data['product_sku'] ?? '',
            'quantity' => $data['quantity'] ?? 1,
            'unit_price' => $data['unit_price'] ?? 0,
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'] ?? 0,
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'invoice_date' => $data['invoice_date'] ?? current_time('mysql'),
            'due_date' => $data['due_date'] ?? '',
            'status' => $data['status'] ?? 'pending',
            'payment_date' => $data['payment_date'] ?? '',
            'payment_method' => $data['payment_method'] ?? '',
            'billing_address' => !empty($data['billing_address']) ? json_encode($data['billing_address']) : '',
            'shipping_address' => !empty($data['shipping_address']) ? json_encode($data['shipping_address']) : '',
            'notes' => $data['notes'] ?? '',
            'source' => 'woocommerce',
            'order_items' => !empty($data['order_items']) ? json_encode($data['order_items']) : '',
            'order_meta' => !empty($data['order_meta']) ? json_encode($data['order_meta']) : '',
            'company_logo' => $companyInfo['logo'] ?? '',
            'company_name' => $companyInfo['company_name'] ?? '',
            'company_address' => $companyInfo['address'] ?? '',
            'company_zipcode' => $companyInfo['zipcode'] ?? '',
            'company_city' => $companyInfo['city'] ?? '',
            'company_country' => $companyInfo['country'] ?? '',
            'company_phone' => $companyInfo['phone'] ?? '',
            'company_iban' => $companyInfo['iban'] ?? '',
            'company_kvk_number' => $companyInfo['kvk_number'] ?? '',
            'company_btw_number' => $companyInfo['btw_number'] ?? '',
            'company_footer_text' => $companyInfo['footer_text'] ?? '',
        ], $companyInfo);
    }

    /**
     * Map WooCommerce order status to invoice status
     * 
     * @param string $orderStatus
     * @return string
     */
    protected function mapOrderStatusToInvoiceStatus(string $orderStatus): string
    {
        $statusMap = [
            'completed' => 'paid',
            'processing' => 'paid',
            'on-hold' => 'pending',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'failed' => 'cancelled'
        ];

        return $statusMap[$orderStatus] ?? 'pending';
    }

    /**
     * Format WooCommerce address for storage
     * 
     * @param array $address
     * @return array
     */
    protected function formatWooCommerceAddress(array $address): array
    {
        return [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'company' => $address['company'] ?? '',
            'address_1' => $address['address_1'] ?? '',
            'address_2' => $address['address_2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'postcode' => $address['postcode'] ?? '',
            'country' => $address['country'] ?? '',
            'email' => $address['email'] ?? '',
            'phone' => $address['phone'] ?? ''
        ];
    }

    /**
     * Format address for display
     * 
     * @param array $address
     * @return string
     */
    protected function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['first_name'] ?? '',
            $address['last_name'] ?? '',
            $address['company'] ?? '',
            $address['address_1'] ?? '',
            $address['address_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postcode'] ?? '',
            $address['country'] ?? ''
        ]);

        return implode(', ', $parts);
    }

    /**
     * Format order items for storage
     * 
     * @param array $items
     * @return array
     */
    protected function formatOrderItems(array $items): array
    {
        $formatted = [];
        foreach ($items as $item) {
            $product = $item->get_product();
            $formatted[] = [
                'id' => $item->get_id(),
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'subtotal' => $item->get_subtotal(),
                'sku' => $product ? $product->get_sku() : '',
                'meta' => $item->get_meta_data()
            ];
        }
        return $formatted;
    }

    /**
     * Get relevant order meta data
     * 
     * @param WC_Order $order
     * @return array
     */
    protected function getOrderMeta(WC_Order $order): array
    {
        return [
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'transaction_id' => $order->get_transaction_id(),
            'customer_ip_address' => $order->get_customer_ip_address(),
            'customer_user_agent' => $order->get_customer_user_agent(),
            'created_via' => $order->get_created_via(),
            'version' => $order->get_version()
        ];
    }

    /**
     * Get tenant ID from WooCommerce order
     * 
     * @param WC_Order $order
     * @return int|null
     */
    protected function getTenantIdFromOrder(WC_Order $order): ?int
    {
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            error_log('WooCommerceInvoice::getTenantIdFromOrder - Order has no user_id: ' . $order->get_id());
            return null;
        }

        // Check if we're in SaaS mode and get tenant from user
        if (class_exists('BookneticSaaS\\Models\\Tenant')) {
            $tenant = \BookneticSaaS\Models\Tenant::where('user_id', $user_id)->fetch();
            
            if ($tenant) {
                return (int)$tenant['id'];
            }
            
            error_log('WooCommerceInvoice::getTenantIdFromOrder - No tenant found for user_id: ' . $user_id);
        }

        // Fallback: use the current tenant context if available
        return $this->getCurrentTenantIdValue();
    }

    /**
     * Create invoice with specific tenant context
     * 
     * @param array $data
     * @param int $tenantId
     * @return int|false
     */
    protected function createWithTenantId(array $data, int $tenantId)
    {
        if (!$this->validateData($data)) {
            return false;
        }

        $metaInput = $this->prepareWooCommerceInvoiceMeta($tenantId, $data);
        $title = sprintf('WooCommerce Invoice #%s', $data['invoice_number']);

        return $this->createInvoicePost($metaInput, $title);
    }


    /**
     * Get company information from WordPress options
     * 
     * @return array
     */
    protected function getTenantCompanyInfo(): array
    {
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
