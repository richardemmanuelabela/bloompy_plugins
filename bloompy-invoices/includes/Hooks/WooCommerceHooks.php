<?php

namespace Bloompy\Invoices\Hooks;

use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Services\PDFService;
use WC_Order;

/**
 * WooCommerce integration hooks
 * 
 * This class handles the integration between WooCommerce orders and the invoice system.
 * It should be used by the bloompy-woocommerce-bridge plugin to create invoices
 * automatically when orders are completed.
 */
class WooCommerceHooks
{
    /**
     * Async action hook used to send completed order emails after invoices have been created.
     */
    private const EMAIL_AS_HOOK = 'bloompy_send_completed_order_email';
    /**
     * Cache to prevent duplicate processing within the same request
     */
    private static array $processedOrders = [];

    /**
     * Register WooCommerce hooks
     */
    public static function registerHooks(): void
    {
        //Remove all automatic email triggers
        self::detachEmailTriggers();

        // Create invoice when order is completed
        add_action('woocommerce_order_status_completed', [self::class, 'createInvoiceFromOrder'], 100, 1);
        
        // Create invoice when subscription is activated
        add_action('woocommerce_subscription_status_active', [self::class, 'createInvoiceFromOrder'], 100, 1);
        
        // Update invoice status when order status changes
        add_action('woocommerce_order_status_changed', [self::class, 'updateInvoiceStatus'], 100, 3);
        
        // Create invoice for subscription renewals
        add_action('woocommerce_subscription_renewal_payment_complete', [self::class, 'createInvoiceFromOrder'], 100, 1);
        
        // Alternative hook for subscription payments (some payment gateways use this)
        add_action('woocommerce_subscription_payment_complete', [self::class, 'createInvoiceFromOrder'], 100, 1);
        add_action('woocommerce_email', [self::class, 'detachEmailTriggers'], 10, 1);
        add_filter('woocommerce_email_attachments', [self::class, 'attachInvoiceToEmail'], 999, 3);

        // Dispatch completed order emails only after invoices exist.
        add_action(self::EMAIL_AS_HOOK, [self::class, 'manuallySendCompletedOrderEmail'], 10, 2);
        add_action('bloompy_woocommerce_invoice_created', [self::class, 'scheduleCompletedOrderEmail'], 20, 3);
    }

    /**
     * Create invoice from WooCommerce order
     * 
     * @param int|WC_Order|WC_Subscription $orderIdOrObject Order ID, Order object, or Subscription object
     * @return void
     */
    public static function createInvoiceFromOrder($orderIdOrObject): void
    {
        try {
            $isSubscriptionObject = false;
            
            // Handle different parameter types
            if ($orderIdOrObject instanceof \WC_Subscription) {
                // For subscription objects, get the last order (renewal order)
                $order = $orderIdOrObject->get_last_order('all');
                if (!$order) {
                    error_log('WooCommerceHooks::createInvoiceFromOrder - No order found for subscription: ' . $orderIdOrObject->get_id());
                    return;
                }
                $orderId = $order->get_id();
                $isSubscriptionObject = true;
            } elseif ($orderIdOrObject instanceof \WC_Order) {
                // Already an order object
                $order = $orderIdOrObject;
                $orderId = $order->get_id();
            } else {
                // Assume it's an order ID
                $orderId = (int)$orderIdOrObject;
                $order = wc_get_order($orderId);
            }
            
            if (!$order instanceof \WC_Order) {
                error_log('WooCommerceHooks::createInvoiceFromOrder - Invalid order object for ID: ' . ($orderId ?? 'unknown'));
                return;
            }
            
            // Skip subscription orders when called from woocommerce_order_status_completed
            // They will be handled by woocommerce_subscription_status_active instead
            if (!$isSubscriptionObject && function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
                error_log('WooCommerceHooks::createInvoiceFromOrder - Skipping subscription order (will be handled by subscription hook): ' . $orderId);
                return;
            }

            // Protection 1: Check if already processed in this request (prevents duplicate calls within same request)
            if (isset(self::$processedOrders[$orderId])) {
                error_log('WooCommerceHooks::createInvoiceFromOrder - Order already processed in this request: ' . $orderId);
                return;
            }

            // Protection 2: Check if invoice already exists in database (persistent across requests)
            if (self::invoiceExistsForOrder($orderId)) {
                error_log('WooCommerceHooks::createInvoiceFromOrder - Invoice already exists for order: ' . $orderId);
                // Mark as processed since invoice exists
                self::$processedOrders[$orderId] = true;
                return;
            }

            // Only create invoices for paid orders
            if (!$order->is_paid()) {
                error_log('WooCommerceHooks::createInvoiceFromOrder - Order not paid, skipping invoice creation: ' . $orderId);
                return;
            }

            // Create the invoice
            $invoiceId = InvoiceService::createFromWooCommerceOrder($order);
            
            if ($invoiceId) {
                // Mark as processed only after successful creation
                self::$processedOrders[$orderId] = true;
                
                error_log('WooCommerceHooks::createInvoiceFromOrder - Invoice created successfully: ' . $invoiceId . ' for order: ' . $orderId);
                
                // Fire action for other plugins to hook into
                do_action('bloompy_woocommerce_invoice_created', $invoiceId, $orderId, $order);
            } else {
                // Don't mark as processed if creation failed - allow retry
                error_log('WooCommerceHooks::createInvoiceFromOrder - Failed to create invoice for order: ' . $orderId);
                return;
            }

        } catch (\Exception $e) {
            error_log('WooCommerceHooks::createInvoiceFromOrder error: ' . $e->getMessage());
        }
    }

    /**
     * Update invoice status when WooCommerce order status changes
     * 
     * @param int $orderId
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public static function updateInvoiceStatus(int $orderId, string $oldStatus, string $newStatus): void
    {
        try {
            $invoice = self::getInvoiceByOrderId($orderId);
            
            if (!$invoice) {
                return; // No invoice found for this order
            }

            $newInvoiceStatus = self::mapOrderStatusToInvoiceStatus($newStatus);
            
            if ($newInvoiceStatus && $newInvoiceStatus !== $invoice['status']) {
                $success = InvoiceService::update($invoice['ID'], [
                    'status' => $newInvoiceStatus,
                    'payment_date' => $newStatus === 'completed' ? current_time('mysql') : ''
                ]);

                if ($success) {
                    error_log('WooCommerceHooks::updateInvoiceStatus - Updated invoice status to: ' . $newInvoiceStatus . ' for order: ' . $orderId);
                    
                    // Fire action for other plugins to hook into
                    do_action('bloompy_woocommerce_invoice_status_updated', $invoice['ID'], $newInvoiceStatus, $orderId);
                } else {
                    error_log('WooCommerceHooks::updateInvoiceStatus - Failed to update invoice status for order: ' . $orderId);
                }
            }

        } catch (\Exception $e) {
            error_log('WooCommerceHooks::updateInvoiceStatus error: ' . $e->getMessage());
        }
    }

    /**
     * Check if invoice already exists for order
     * 
     * @param int $orderId
     * @return bool
     */
    private static function invoiceExistsForOrder(int $orderId): bool
    {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'bloompy_invoice'
            AND pm.meta_key = 'order_id'
            AND pm.meta_value = %s
            LIMIT 1
        ", $orderId);

        return !empty($wpdb->get_var($query));
    }

    /**
     * Get invoice by WooCommerce order ID
     * 
     * @param int $orderId
     * @return array|null
     */
    private static function getInvoiceByOrderId(int $orderId): ?array
    {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'bloompy_invoice'
            AND pm.meta_key = 'order_id'
            AND pm.meta_value = %s
            LIMIT 1
        ", $orderId);

        $invoiceId = $wpdb->get_var($query);
        
        if ($invoiceId) {
            return InvoiceService::get($invoiceId);
        }

        return null;
    }

    /**
     * Map WooCommerce order status to invoice status
     * 
     * @param string $orderStatus
     * @return string|null
     */
    private static function mapOrderStatusToInvoiceStatus(string $orderStatus): ?string
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

        return $statusMap[$orderStatus] ?? null;
    }

    /**
     * Manually create invoice for order (for admin use)
     * 
     * @param int $orderId
     * @return int|false Invoice ID on success, false on failure
     */
    public static function manualCreateInvoice(int $orderId)
    {
        try {
            $order = wc_get_order($orderId);
            
            if (!$order instanceof WC_Order) {
                return false;
            }

            return InvoiceService::createFromWooCommerceOrder($order);

        } catch (\Exception $e) {
            error_log('WooCommerceHooks::manualCreateInvoice error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice for order
     * 
     * @param int $orderId
     * @return array|null
     */
    public static function getInvoiceForOrder(int $orderId): ?array
    {
        return self::getInvoiceByOrderId($orderId);
    }

    /**
     * Check if order has an invoice
     * 
     * @param int $orderId
     * @return bool
     */
    public static function orderHasInvoice(int $orderId): bool
    {
        return self::invoiceExistsForOrder($orderId);
    }

    /**
     * Attach generated invoice PDF to outgoing WooCommerce emails.
     *
     * @param array       $attachments
     * @param string      $emailId
     * @param \WC_Order   $order
     * @return array
     */
    public static function attachInvoiceToEmail( array $attachments, string $emailId = '', $order = null ): array
    {
        try {
            $targetEmails = [
                'customer_completed_order',
                'customer_completed_renewal_order',
                'customer_processing_renewal_order',
                'customer_renewal_invoice',
                'new_renewal_order',
            ];

            if ( ! in_array( $emailId, $targetEmails, true ) ) {
                return $attachments;
            }

            if ( ! $order instanceof \WC_Order ) {
                return $attachments;
            }

            $orderId = $order->get_id();
            $invoice = self::getInvoiceByOrderId( $orderId );

            if ( ! $invoice ) {
            }

            $pdfService = new PDFService();
            $pdfPath    = $pdfService->generateInvoicePDF( $invoice );

            if ( $pdfPath && file_exists( $pdfPath ) ) {
                $attachments[] = $pdfPath;
            }

        } catch ( \Throwable $e ) {
            error_log( 'WooCommerceHooks::attachInvoiceToEmail error: ' . $e->getMessage() );
        }

        return $attachments;
    }

    /**
     * Remove default WooCommerce triggers for completed order emails so we can send them after invoices exist.
     *
     * @param \WC_Email $email
     * @return void
     */
    public static function detachEmailTriggers(): void
    {
        $mailer = WC()->mailer();

        if ( ! $mailer ) {
            return;
        }

        foreach ( $mailer->get_emails() as $email ) {
            if ( $email instanceof \WC_Email_Customer_Completed_Order ) {
                remove_action( 'woocommerce_order_status_completed', [ $email, 'trigger' ], 10 );
                remove_action( 'woocommerce_order_status_completed_notification', [ $email, 'trigger' ], 10 );
            }

            if ( class_exists( 'WCS_Email_Completed_Renewal_Order' ) && $email instanceof \WCS_Email_Completed_Renewal_Order ) {
                remove_action( 'woocommerce_subscription_payment_complete', [ $email, 'trigger' ], 10 );
                remove_action( 'woocommerce_order_status_completed_renewal_notification', [ $email, 'trigger' ], 10 );
                remove_action( 'woocommerce_order_status_processing_renewal_notification', [ $email, 'trigger' ], 10 );
                remove_action( 'woocommerce_order_status_pending_to_processing_renewal_notification', [ $email, 'trigger' ], 10 );
            }
        }
    }

    /**
     * Queue the completed order email once the invoice exists.
     *
     * @param int $invoiceId
     * @param int $orderId
     * @param \WC_Order $order
     * @return void
     */
    public static function scheduleCompletedOrderEmail( int $invoiceId, int $orderId, $order ): void
    {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $isRenewal = false;
        if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order( $orderId );
            if ( ! empty( $subscriptions ) ) {
                $subscription = array_shift( $subscriptions );
                if ( $subscription instanceof \WC_Subscription ) {
                    $isRenewal = self::isPaymentRenewal( $subscription );
                }
            }
        }

        $args = [
            'order_id'   => $orderId,
            'is_renewal' => $isRenewal ? 1 : 0,
        ];

        if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::EMAIL_AS_HOOK, $args ) ) {
            return;
        }

        if ( function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( self::EMAIL_AS_HOOK, $args, 'bloompy-invoices' );
            return;
        }

        self::manuallySendCompletedOrderEmail( $orderId, $isRenewal );
    }

    /**
     * Manually send the completed order email (used for async callback/fallback).
     *
     * @param int  $orderId
     * @param bool $isPaymentRenewal
     * @return void
     */
    public static function manuallySendCompletedOrderEmail( int $orderId, $isPaymentRenewal ): void
    {
        if ( ! $orderId ) {
            return;
        }

        $order  = wc_get_order( $orderId );
        $mailer = WC()->mailer();

        if ( ! $order || ! $mailer ) {
            return;
        }

        $emails           = $mailer->get_emails();
        $isPaymentRenewal = (bool) $isPaymentRenewal;

        if ( $isPaymentRenewal ) {
            if ( isset( $emails['WCS_Email_Completed_Renewal_Order'] ) ) {
                $emails['WCS_Email_Completed_Renewal_Order']->trigger( $orderId );
            }
            return;
        }

        if ( isset( $emails['WC_Email_Customer_Completed_Order'] ) ) {
            $emails['WC_Email_Customer_Completed_Order']->trigger( $orderId );
        }
    }

    /**
     * Determine whether the given subscription represents a renewal payment.
     *
     * @param \WC_Subscription|null $subscription
     * @return bool
     */
    public static function isPaymentRenewal( $subscription ): bool
    {
        if ( ! $subscription instanceof \WC_Subscription ) {
            return false;
        }

        $lastOrder = $subscription->get_last_order( 'all' );
        $orderId   = 0;

        if ( is_array( $lastOrder ) ) {
            $maybe   = end( $lastOrder );
            $orderId = is_object( $maybe ) && method_exists( $maybe, 'get_id' )
                ? (int) $maybe->get_id()
                : (int) $maybe;
        } elseif ( is_object( $lastOrder ) && method_exists( $lastOrder, 'get_id' ) ) {
            $orderId = (int) $lastOrder->get_id();
        } elseif ( is_scalar( $lastOrder ) ) {
            $orderId = (int) $lastOrder;
        }

        if ( ! $orderId ) {
            return false;
        }

        if ( function_exists( 'wcs_order_contains_subscription' ) ) {
            return (bool) wcs_order_contains_subscription(
                $orderId,
                [ 'renewal', 'switch', 'resubscribe' ]
            );
        }

        if ( function_exists( 'wcs_order_contains_renewal' ) ) {
            return (bool) wcs_order_contains_renewal( $orderId );
        }

        return false;
    }
}


