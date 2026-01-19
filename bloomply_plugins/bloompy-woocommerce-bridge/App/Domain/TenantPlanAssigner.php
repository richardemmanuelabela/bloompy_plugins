<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;
use WC_Order;
use WC_Subscription;

/**
 * Handles assignment, upgrade, and downgrade of Booknetic plans for tenants based on WooCommerce events.
 */
class TenantPlanAssigner
{
    private static array $tenantCache = [];
    private static array $processedOrders = [];

    public function registerHooks(): void
    {
        add_filter('woocommerce_order_item_needs_processing', [$this, 'skipProcessingForVirtualAndSubscriptions'], 10, 3);
        // Subscriptions are handled by woocommerce_subscription_status_active hook instead
        // add_action('woocommerce_order_status_completed', [$this, 'assignPlanFromOrder']);
        add_action('woocommerce_subscription_status_active', [$this, 'assignPlanFromOrder']);
        add_action('woocommerce_subscription_renewal_payment_complete', [$this, 'assignPlanFromOrder']);
        add_action('woocommerce_subscription_status_cancelled', [$this, 'removePlanFromSubscription']);
        add_action('woocommerce_subscription_status_expired', [$this, 'removePlanFromSubscription']);
        add_action('woocommerce_subscription_status_on-hold', [$this, 'removePlanFromSubscription']);
        add_action('woocommerce_subscription_switch_completed', [$this, 'handlePlanSwitch'], 10, 2);
        add_action('woocommerce_subscriptions_switch_completed', [$this, 'isFromSwitch']);


		
		add_filter( 'wcs_switch_product_validation', function( $passed, $subscription, $new_product_id, $variation_id ) {
			error_log("wcs_switch_product_validation");
			// Force validation to pass
			$passed = true;

			// Remove the specific warning
			$all_notices = wc_get_notices( 'error' );
			if ( ! empty( $all_notices ) ) {
				foreach ( $all_notices as $notice ) {
					error_log("notice: ".print_r($notice, true));
					if ( strpos( $notice['notice'], 'This subscription product cannot be added to your cart' ) !== false ) {
						wc_remove_notice( $notice['notice'], 'error' );
					}
				}
			}

			return $passed;

		}, 10, 4 );
    }

    /**
     * Tell WooCommerce that virtual products and subscriptions don't need "processing" status.
     * This causes orders with only these items to go straight to "completed" after payment.
     */
    public function skipProcessingForVirtualAndSubscriptions($needs_processing, $product, $order)
    {
        // If the product is virtual or a subscription type, skip processing
        if ($product->is_virtual() || in_array($product->get_type(), ['subscription', 'variable-subscription', 'subscription_variation'])) {
            return false;
        }

        return $needs_processing;
    }

    public function assignPlanFromOrder($orderIdOrObject): void
    {
        $subscription = null;
        
        // Handle different parameter types (int, WC_Order, or WC_Subscription)
        if ($orderIdOrObject instanceof WC_Subscription) {
            // For subscription objects, get the last order (renewal order)
            $subscription = $orderIdOrObject;
            $order = $subscription->get_last_order('all');
            if (!$order) {
                error_log("[Bloompy WooCommerce Bridge] No order found for subscription: " . $subscription->get_id());
                return;
            }
            $order_id = $order->get_id();
        } elseif ($orderIdOrObject instanceof WC_Order) {
            // Already an order object
            $order = $orderIdOrObject;
            $order_id = $order->get_id();
        } else {
            // Assume it's an order ID
            $order_id = (int)$orderIdOrObject;
            $order = wc_get_order($order_id);
        }
        
        if (!$order instanceof WC_Order) {
            error_log("[Bloompy WooCommerce Bridge] Invalid order object for ID: " . ($order_id ?? 'unknown'));
            return;
        }
        
        // Protection 1: Check if already processed in this request (prevents duplicate calls within same request)
        if (isset(self::$processedOrders[$order_id])) {
            error_log("[Bloompy WooCommerce Bridge] Order #$order_id already processed in this request.");
            return;
        }

        // Protection 2: Check if plan was already assigned to this order (persistent across requests)
        if (get_post_meta($order_id, '_bloompy_plan_assigned', true)) {
            error_log("[Bloompy WooCommerce Bridge] Plan already assigned for order #$order_id.");
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("[Bloompy WooCommerce Bridge] No user ID found for order #$order_id.");
            return;
        }

        // Mark as processing in this request
        self::$processedOrders[$order_id] = true;

        foreach ($order->get_items() as $item) {
            $this->processOrderItem($item, $user_id, $order_id, $subscription);
        }

        // Mark as permanently processed
        update_post_meta($order_id, '_bloompy_plan_assigned', current_time('mysql'));
        
        error_log("[Bloompy WooCommerce Bridge] Successfully assigned plan for order #$order_id at " . current_time('mysql'));
    }

    private function processOrderItem($item, int $user_id, int $order_id, ?WC_Subscription $subscription = null): void
    {
        $variation_id = $item->get_variation_id();
        $product_id = $item->get_product_id();

        $plan_id = get_post_meta($variation_id, '_bloompy_plan_id', true);
        // Fallback to parent product if variation doesn't have it
        if (empty($plan_id)) {
            $plan_id = get_post_meta($product_id, '_bloompy_plan_id', true);
        }

        if (empty($plan_id)) {
            //error_log("[Bloompy WooCommerce Bridge] No _bloompy_plan_id found for product ID $product_id.");
            return;
        }

        $tenant = $this->ensureTenantExists($user_id);
        if (!$tenant) {
            error_log("[Bloompy WooCommerce Bridge] Tenant could not be created or fetched for user ID $user_id.");
            return;
        }

        $plan = Plan::get($plan_id);
        if (!$plan) {
            error_log("[Bloompy WooCommerce Bridge] Plan ID $plan_id not found.");
            return;
        }

        $this->assignPlanToTenant($tenant, $plan, $user_id, $order_id, $subscription);
    }

    private function assignPlanToTenant($tenant, $plan, int $user_id, int $order_id, ?WC_Subscription $subscription = null): void
    {
        // If subscription was not provided, try to retrieve it from the order ID
        if (!$subscription) {
            // Get all subscriptions linked to this order
            $subscriptions = wcs_get_subscriptions_for_order($order_id);

            // Extract the first subscription from the list (orders can have multiple)
            $subscription = !empty($subscriptions) ? array_shift($subscriptions) : null;
        }

        $interval = $subscription ? $subscription->get_billing_interval() : 1;
        $period = $subscription ? $subscription->get_billing_period() : 'month';


		// Determine if this is a free plan
		$isFreePlan = (
			floatval($plan->monthly_price ?? 0) <= 0
			&& floatval($plan->annually_price ?? 0) <= 0
		);

		if ($isFreePlan) {
			$newExpiry = null;
		} else {

			if ( did_action( 'woocommerce_subscription_renewal_payment_complete' ) ) {
				// This is a RENEWAL payment
				$nextPayment = $this->getWooCommerceNextPaymentDate($subscription);
			} else {
				// This is a NEW subscription purchase
				$nextPayment = Date::dateSQL($subscription->get_time('next_payment'));
			}

			// If the WooCommerce subscription next_payment date is empty, assign the default expires_in value.
			$extendBy = "+{$interval} {$period}";
			$newExpiry = !empty($nextPayment)? $nextPayment : Date::dateSQL($tenant->expires_in, $extendBy);

		}

		$result = Tenant::where('id', $tenant->id)->update([
			'plan_id' => $plan->id,
			'expires_in' => $newExpiry,
		]);


        // Respect trial if present
        $trial_end_timestamp = $subscription ? $subscription->get_time('trial_end') : false;

        if ($trial_end_timestamp && time() < $trial_end_timestamp) {
            // Still in trial — assign plan but do not extend expiry
            if ($tenant->plan_id != $plan->id) {
                Tenant::where('id', $tenant->id)->update(['plan_id' => $plan->id]);
                do_action('bloompy_plan_activated_trialing', $tenant->id, $plan->id);
            }
            return;
        }


        if ($result === false) {
            error_log("[Bloompy WooCommerce Bridge] Failed to update tenant #{$tenant->id} with plan {$plan->id}.");
        } else {
            do_action('bloompy_plan_activated', $tenant->id, $plan->id);
        }
    }

	public function getWooCommerceNextPaymentDate($subscription) {
		$base_time = $subscription->get_time( 'next_payment' );

		if ( ! $base_time ) {
			return;
		}

		$next_payment_my_account = wcs_add_time(
			$subscription->get_billing_interval(),
			$subscription->get_billing_period(),
			$base_time
		);

		// This date MATCHES My Account exactly
		$next_payment_date  =  wp_date(
			get_option( 'date_format' ),
			$next_payment_my_account
		);

		return gmdate( 'Y-m-d', $next_payment_my_account );
	}

    public function removePlanFromSubscription($subscription_id): void
    {
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            error_log("[Bloompy WooCommerce Bridge] Subscription not found: $subscription_id.");
            return;
        }

        $user_id = $subscription->get_user_id();
        if (!$user_id) {
            error_log("[Bloompy WooCommerce Bridge] No user associated with subscription ID $subscription_id.");
            return;
        }

        $tenant = Tenant::where('user_id', $user_id)->fetch();
        if (!$tenant) {
            error_log("[Bloompy WooCommerce Bridge] Tenant not found for user ID $user_id.");
            return;
        }

        $this->downgradeToDefaultPlan($tenant);
    }

    private function downgradeToDefaultPlan($tenant): void
    {
        $defaultPlan = Plan::where('is_default', 1)->fetch();
        $result = false;

        if ($defaultPlan) {
            $result = Tenant::where('id', $tenant->id)->update(['plan_id' => $defaultPlan->id]);
            do_action('bloompy_plan_downgraded_to_default', $tenant->id, $defaultPlan->id);
        } else {
            $result = Tenant::where('id', $tenant->id)->update(['plan_id' => null]);
            do_action('bloompy_plan_removed', $tenant->id);
        }

        if ($result === false) {
            error_log("[Bloompy WooCommerce Bridge] Failed to update plan on cancellation for tenant #{$tenant->id}.");
        }
    }

    public function isFromSwitch($order_id) {
        if ( !wcs_order_contains_switch( $order_id ) ) {
            return;
        }

        // Get subscription created/modified by switch
        $subscriptions = wcs_get_subscriptions_for_switch_order( $order_id );
        $subscription  = array_shift( $subscriptions );

        error_log("Switch completed for subscription: " . $subscription->get_id());
        error_log("Switch order: " . $order_id);
        $this->handlePlanSwitch($subscription, []);
    }
    public function handlePlanSwitch($subscription, $args): void
    {
        $user_id = $subscription->get_user_id();
        if (!$user_id) {
            error_log('[Bloompy WooCommerce Bridge] Subscription switch failed: missing user ID.');
            return;
        }

        $expires_in_timestamp = $subscription->get_time( 'next_payment' );

        $expires_in = $expires_in_timestamp ? date( 'Y-m-d H:i:s', $expires_in_timestamp ) : null;
        $items = $subscription->get_items();
        foreach ($items as $item) {
            $variation_id = $item->get_variation_id();
            $product_id = $variation_id ?: $item->get_product_id();
            $plan_id = get_post_meta($product_id, '_bloompy_plan_id', true);
            if (!$plan_id) {
                error_log("[Bloompy WooCommerce Bridge] No _bloompy_plan_id found for product $product_id during switch.");
                continue;
            }

            $tenant = Tenant::where('user_id', $user_id)->fetch();
            if (!$tenant) {
                error_log("[Bloompy WooCommerce Bridge] No tenant found for user ID $user_id during switch.");
                continue;
            }

            $result = Tenant::where('id', $tenant->id)->update([
                'plan_id' => $plan_id,
                'expires_in' => $expires_in
            ]);

            if ($result === false) {
                error_log("[Bloompy WooCommerce Bridge] Failed to update plan during switch for tenant ID {$tenant->id}.");
                // Notify admin about failed plan switch
                global $bloompy_woocommerce_bridge_notification_service;
                if (isset($bloompy_woocommerce_bridge_notification_service)) {
                    $bloompy_woocommerce_bridge_notification_service->notifyFailedPlanSwitch($user_id, $plan_id);
                }
            } else {
                error_log('bloompy_plan_switched '.$plan_id." => ".$product_id." => ".$variation_id);
                do_action('bloompy_plan_switched', $tenant->id, $plan_id);
            }
        }
    }

    private function ensureTenantExists(int $user_id)
    {
        if (isset(self::$tenantCache[$user_id])) {
            return self::$tenantCache[$user_id];
        }

        $tenant = Tenant::where('user_id', $user_id)->fetch();
        if ($tenant) {
            self::$tenantCache[$user_id] = $tenant;
            return $tenant;
        }

        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) {
            error_log("[Bloompy WooCommerce Bridge] Failed to fetch WP user for tenant creation (user ID: $user_id)");
            return null;
        }

        if (!in_array('booknetic_saas_tenant', $user->roles)) {
            $user->add_role('booknetic_saas_tenant'); 
            error_log('"booknetic_saas_tenant role" role has been added to tenant.');
        }
        error_log('List of Roles: '.print_r($user->roles, true));

        $defaultPlan = Plan::where('is_default', 1)->fetch();
        $inserted = Tenant::insert([
            'user_id' => $user_id,
            'full_name' => $user->display_name ?: $user->user_login,
            'email' => $user->user_email,
            'domain' => sanitize_title($user->user_login),
            'plan_id' => $defaultPlan ? $defaultPlan->id : null,
            'expires_in' => Date::dateSQL('+' . Helper::getOption('trial_period', 30) . ' days'),
            'inserted_at' => Date::dateTimeSQL(),
            'verified_at' => Date::dateTimeSQL(),
        ]);

        if (!$inserted) {
            error_log("[Bloompy WooCommerce Bridge] Failed to insert new tenant for user ID $user_id");
            return null;
        }

        $tenant_id = Tenant::lastId();

        if (method_exists(Tenant::class, 'createInitialData')) {
            Tenant::createInitialData($tenant_id);
        }

        do_action('bkntcsaas_tenant_sign_up_confirm', $tenant_id);
        do_action('bloompy_tenant_created_from_checkout', $tenant_id, $user_id);

        $tenant = Tenant::where('id', $tenant_id)->fetch();
        self::$tenantCache[$user_id] = $tenant;

        return $tenant;
    }
} 