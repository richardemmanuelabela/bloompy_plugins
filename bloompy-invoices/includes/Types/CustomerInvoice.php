<?php

namespace Bloompy\Invoices\Types;

use Bloompy\Invoices\Abstract\AbstractInvoice;
use Bloompy\Invoices\Interfaces\InvoiceInterface;
use BookneticApp\Providers\Helpers\Helper;

/**
 * Customer Invoice implementation for Booknetic appointments
 * 
 * Handles invoices created from Booknetic appointments and services.
 * This replaces the tightly coupled logic in the original Invoice model.
 */
class CustomerInvoice extends AbstractInvoice implements InvoiceInterface
{
    /**
     * Create a new customer invoice
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
        $metaInput = $this->prepareCustomerInvoiceMeta($tenantId, $data);
        $title = sprintf('Invoice #%s', $data['invoice_number']);

        return $this->createInvoicePost($metaInput, $title);
    }

    /**
     * Override parent method to exclude WooCommerce invoices
     * Customer invoices should only show Booknetic appointment invoices, not subscription invoices
     */
    protected function getTenantMetaQuery(int $tenantId): array
    {
        return [
            'relation' => 'AND',
            [
                'key' => 'tenant_id',
                'value' => $tenantId,
                'compare' => '='
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'source',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'source',
                    'value' => 'woocommerce',
                    'compare' => '!='
                ]
            ]
        ];
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

        $args = $this->getBaseQueryArgs($tenantId, $limit, $offset);

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
     * Generate invoice number for customer invoices
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
        $prefix = $year . '-';

        // Check if there's a starting number set for this year
        $starting_number = null;
        if ($tenantId > 0 && class_exists('\BookneticSaaS\Models\Tenant')) {
            // SaaS installation - get from tenant data
            $starting_number = \BookneticSaaS\Models\Tenant::getData($tenantId, "invoice_starting_number_{$year}");
        } else {
            // Non-SaaS installation - get from WordPress options
            $starting_number = get_option("bloompy_invoice_starting_number_{$year}", '');
        }

        // Get the highest invoice number for this year and tenant
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT pm.meta_value as invoice_number
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_tenant ON p.ID = pm_tenant.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = 'invoice_number'
            AND pm_tenant.meta_key = 'tenant_id'
            AND pm_tenant.meta_value = %s
            AND pm.meta_value LIKE %s
            ORDER BY pm.meta_value DESC
            LIMIT 1
        ", static::POST_TYPE, $tenantId, $prefix . '%');

        $last_invoice = $wpdb->get_var($query);

        if ($last_invoice) {
            // Extract number and increment
            $number = intval(str_replace($prefix, '', $last_invoice));
            $next_number = $number + 1;
        } else {
            // If no existing invoices and starting number is set, use it
            if ($starting_number && !empty($starting_number)) {
                $next_number = intval($starting_number);
            } else {
                $next_number = 1;
            }
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
        global $wpdb;
        
        if ($tenantId === null) {
            $tenantId = $this->getCurrentTenantIdValue();
        }
        
        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'invoice_number',
                'value' => $invoiceNumber,
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
        
        // Exclude WooCommerce invoices
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => 'source',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key' => 'source',
                'value' => 'woocommerce',
                'compare' => '!='
            ]
        ];
        
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
        return 'customer';
    }

    /**
     * Get invoice source identifier
     * 
     * @return string
     */
    public function getSource(): string
    {
        return 'booknetic';
    }

    /**
     * Get search fields for customer invoices
     * 
     * @return array
     */
    public function getSearchFields(): array
    {
        return [
            'invoice_number',
            'customer_name',
            'customer_email',
            'service_name',
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
            'customer' => function($row) {
                return '<strong>' . $row['customer_name'] . '</strong><br><small>' . $row['customer_email'] . '</small>';
            },
            'service_name' => 'Service',
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
     * Get default data structure for customer invoices
     * 
     * @return array
     */
    public function getDefaultDataStructure(): array
    {
        return [
            'invoice_number' => '',
            'appointment_id' => null,
            'customer_id' => '',
            'customer_email' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'service_id' => '',
            'service_name' => '',
            'service_price' => 0,
            'service_duration' => '',
            'appointment_date' => '',
            'customer_company_name' => '',
            'customer_company_address' => '',
            'customer_company_zipcode' => '',
            'customer_company_city' => '',
            'customer_company_country' => '',
            'customer_company_iban' => '',
            'customer_company_kvk_number' => '',
            'customer_company_btw_number' => '',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'currency' => 'EUR',
            'invoice_date' => '',
            'due_date' => '',
            'status' => 'pending',
            'payment_date' => '',
            'notes' => '',
            'source' => 'booknetic',
            'service_extras' => [],
            'pricing_breakdown' => [],
			'number_of_appointments' => 0,
        ];
    }

    /**
     * Validate customer invoice data
     * 
     * @param array $data
     * @return bool
     */
    public function validateData(array $data): bool
    {
        $requiredFields = [
            'invoice_number',
            'customer_id',
            'customer_email',
            'customer_name',
            'service_id',
            'service_name',
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
        $invoiceData['service_price_formatted'] = Helper::price($invoiceData['service_price'] ?? 0);
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
        if (!empty($invoiceData['appointment_date'])) {
            $invoiceData['appointment_date_formatted'] = date('d/m/Y H:i', strtotime($invoiceData['appointment_date']));
        }

        return $invoiceData;
    }

    /**
     * Get PDF data for customer invoice
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
            'template' => 'customer-invoice',
            'filename' => 'invoice-' . $invoice['invoice_number'] . '.pdf'
        ];
    }

    /**
     * Get DataTable query adapter for customer invoices
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
                $this->args = [
                    'post_type' => 'bloompy_invoice',
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'posts_per_page' => 25,
                    'offset' => 0,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'tenant_id',
                            'value' => $this->tenant_id,
                            'compare' => '='
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'source',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'source',
                                'value' => 'woocommerce',
                                'compare' => '!='
                            ]
                        ]
                    ],
                    's' => '',
                ];

                $this->filters = \BookneticApp\Providers\Helpers\Helper::_post('filters', [], 'arr');
                $this->orderBy = 'date';
                $this->order = 'DESC';
                $this->limit = 25;
                $this->offset = 0;
                $this->search = '';
                $this->searchFields = ['invoice_number', 'customer_name', 'customer_email', 'service_name', 'status'];
            }

            public function where($key, $value = null, $operator = '=', $invoice_type = null) {
                if (is_callable($key)) {
                    call_user_func($key, $this);
                    return $this;
                }
                
                if ($value !== null && ($key === 'status' || $key === 'service_name' || $key === 'customer_email' || $key === 'customer_name' || $key === 'invoice_date')) {
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
                            'relation' => 'AND',
                            [
                                'key' => 'tenant_id',
                                'value' => $this->tenant_id,
                                'compare' => '='
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'source',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'source',
                                    'value' => 'woocommerce',
                                    'compare' => '!='
                                ]
                            ]
                        ]
                    ];
                    
                    $all_args = $this->setFilterConditions($all_args);
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
                    $all_args = [
                        'post_type' => 'bloompy_invoice',
                        'post_status' => 'publish',
                        'fields' => 'ids',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            'relation' => 'AND',
                            [
                                'key' => 'tenant_id',
                                'value' => $this->tenant_id,
                                'compare' => '='
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'source',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'source',
                                    'value' => 'woocommerce',
                                    'compare' => '!='
                                ]
                            ]
                        ]
                    ];

                    $all_args = $this->setFilterConditions($all_args);
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

            private function setFilterConditions($all_args) {
                if (!empty($this->filters)) {
                    $all_args['meta_query'] = ['relation' => 'AND'];
                    foreach ($this->filters as $filter) {
                        if ($filter[0] == 0) {
                            $all_args['meta_query'][] = [
                                'key' => 'service_id',
                                'value' => $filter[1],
                                'compare' => '='
                            ];
                        }
                        if ($filter[0] == 1) {
                            $all_args['meta_query'][] = [
                                'key' => 'customer_id',
                                'value' => $filter[1],
                                'compare' => '='
                            ];
                        }
                        if ($filter[0] == 2) {
                            $startDate = date("Y-m-d", strtotime($filter[1]));
                        }
                        if ($filter[0] == 3) {
                            $endDate = date("Y-m-d", strtotime($filter[1]));
                        }
                    }
                    if (!empty($startDate) || !empty($endDate)) {
                        $dateRange = [];
                        if (isset($startDate)) {
                            $dateRange['after'] = $startDate;
                        }
                        if (isset($endDate)) {
                            $dateRange['before'] = $endDate;
                        }
                        $dateRange['inclusive'] = true;
                        $all_args['date_query'] = [[$dateRange]];
                    }
                }
                return $all_args;
            }
        };
    }

    /**
     * Prepare customer invoice meta data
     * 
     * @param int $tenantId
     * @param array $data
     * @return array
     */
    protected function prepareCustomerInvoiceMeta(int $tenantId, array $data): array
    {
        $companyInfo = $this->getTenantCompanyInfo();
        
        return array_merge([
            'tenant_id' => $tenantId,
            'invoice_number' => $data['invoice_number'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? '',
            'service_id' => $data['service_id'],
            'service_name' => $data['service_name'],
            'service_price' => $data['service_price'],
            'service_duration' => $data['service_duration'] ?? '',
            'appointment_date' => $data['appointment_date'] ?? '',
            'customer_company_name' => $data['customer_company_name'] ?? '',
            'customer_company_address' => $data['customer_company_address'] ?? '',
            'customer_company_zipcode' => $data['customer_company_zipcode'] ?? '',
            'customer_company_city' => $data['customer_company_city'] ?? '',
            'customer_company_country' => $data['customer_company_country'] ?? '',
            'customer_company_iban' => $data['customer_company_iban'] ?? '',
            'customer_company_kvk_number' => $data['customer_company_kvk_number'] ?? '',
            'customer_company_btw_number' => $data['customer_company_btw_number'] ?? '',
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
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'] ?? 0,
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'invoice_date' => $data['invoice_date'] ?? current_time('mysql'),
            'due_date' => $data['due_date'] ?? '',
            'status' => $data['status'] ?? 'pending',
            'payment_date' => $data['payment_date'] ?? '',
            'notes' => $data['notes'] ?? '',
            'source' => $data['source'] ?? 'booknetic',
            'service_extras' => !empty($data['service_extras']) ? json_encode($data['service_extras']) : '',
            'pricing_breakdown' => !empty($data['pricing_breakdown']) ? json_encode($data['pricing_breakdown']) : '',
			'number_of_appointments' => $data['number_of_appointments'] ?? '1',
        ], $companyInfo);
    }
}

