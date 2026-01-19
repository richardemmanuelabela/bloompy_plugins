<?php
namespace BloompyAddon\WooCommerceBridge\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use WP_Query;
use function \BloompyAddon\WooCommerceBridge\bkntc__;

class PlanUpgradesController extends BaseController
{
    public function index()
    {
        $user_id = get_current_user_id();
        $tenant = Tenant::where('user_id', $user_id)->fetch();
        
        if (!$tenant) {
            wp_die('No tenant profile found. Please contact support.');
        }

        $current_plan_id = $tenant->plan_id;
        $plans = Plan::where('is_active', 1)->fetchAll();

        $wc_subscription = $this->getWCSubscriptionByUserId($user_id);
        $subscription_id = $wc_subscription ? $wc_subscription->get_id() : null;
        $wc_subscription_details = $wc_subscription ? $this->getWCSubscriptionDetails($wc_subscription) : null;
        $item_id = $wc_subscription_details['item_id'] ?? null;
        $current_variation_id = $wc_subscription_details['variation_id'] ?? null;
        $available_plans = [];
        foreach ($plans as $plan) {
            $product_id = $this->getProductIdByPlanId($plan->id);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product && $product->is_purchasable() && !empty($product->get_price())) {
                    $variations = [];
                    
                    // Get variations for variable products
                    if ($product->is_type('variable')) {
                        $variations = $this->getProductVariations($product);
                    }
                    
                    $available_plans[] = [
                        'plan' => $plan,
                        'product' => $product,
                        'product_id' => $product_id,
                        'variations' => $variations,
                        'subscription_id' => $subscription_id,
                        'item_id' => $item_id,
                        'current_variation_id' => $current_variation_id
                    ];
                }
            }
        }

        // Get current plan features and usage
        $current_plan = $current_plan_id ? Plan::get($current_plan_id) : null;
        $plan_limits = [];
        
        if ($current_plan) {
            $plan_limits = $this->getPlanLimits($current_plan, $tenant);
        }

        $this->view('plan_upgrades', [
            'current_plan' => $current_plan,
            'available_plans' => $available_plans,
            'tenant' => $tenant,
            'plan_limits' => $plan_limits,
            'current_variation_id' => $current_variation_id
        ]);
    }

    private function getWCSubscriptionDetails( $subscription ) {
        foreach ( $subscription->get_items() as $item_id => $item ) {
            return [
                'item_id'      => $item_id,
                'product_id'   => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'product'      => $item->get_product(),
            ];
        }

        return null; // no items found
    }

    private function getWCSubscriptionByUserId($user_id) {
        $subscriptions = wcs_get_users_subscriptions($user_id);

        if (empty($subscriptions)) {
            return null;
        }

        // Sort subscriptions by start date descending
        uasort($subscriptions, function($a, $b) {
            return strtotime($b->get_date('start')) - strtotime($a->get_date('start'));
        });

        // Get the first item (latest subscription)
        return reset($subscriptions);
    }

    private function getProductIdByPlanId($plan_id): ?int
    {
        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [[
                'key' => '_bloompy_plan_id',
                'value' => $plan_id
            ]]
        ]);

        return !empty($query->posts) ? $query->posts[0]->ID : null;
    }

    public function getProductVariations($product): array
    {
        $variations = [];
        
        if (!$product->is_type('variable')) {
            return $variations;
        }
        
        $available_variations = $product->get_available_variations();
        
        foreach ($available_variations as $variation) {
            $variation_obj = wc_get_product($variation['variation_id']);
            if ($variation_obj && $variation_obj->is_purchasable()) {
                $variations[] = [
                    'variation_id' => $variation['variation_id'],
                    'attributes' => $variation['attributes'],
                    'price' => $variation_obj->get_price(),
                    'price_html' => $variation_obj->get_price_html(),
                    'name' => $this->formatVariationName($variation['attributes'])
                ];
            }
        }
        
        return $variations;
    }

    public function formatVariationName($attributes): string
    {
        $name_parts = [];
        
        foreach ($attributes as $attribute_name => $attribute_value) {
            $taxonomy = str_replace('attribute_', '', $attribute_name);
            $term = get_term_by('slug', $attribute_value, $taxonomy);
            
            if ($term) {
                $name_parts[] = $term->name;
            } else {
                $name_parts[] = $attribute_value;
            }
        }
        
        return implode(' - ', $name_parts);
    }

    private function getPlanLimits($plan, $tenant): array
    {
        $limits = [];
        $permissions = json_decode($plan->permissions);
        $permissions_limits = $permissions->limits ?? (object)[];
        
        // Staff limit
        $staff_limit = $permissions_limits->staff_allowed_max_number ?? -1;
        $current_staff = \BookneticApp\Models\Staff::where('tenant_id', $tenant->id)->count();
        $limits['staff'] = [
            'title' => bkntc__('Staff Members'),
            'current_usage' => $current_staff,
            'max_usage' => $staff_limit
        ];
        
        // Service limit
        $service_limit = $permissions_limits->services_allowed_max_number ?? -1;
        $current_services = \BookneticApp\Models\Service::where('tenant_id', $tenant->id)->count();
        $limits['service'] = [
            'title' => bkntc__('Services'),
            'current_usage' => $current_services,
            'max_usage' => $service_limit
        ];
        
        // Location limit
        $location_limit = $permissions_limits->locations_allowed_max_number ?? -1;
        $current_locations = \BookneticApp\Models\Location::where('tenant_id', $tenant->id)->count();
        $limits['location'] = [
            'title' => bkntc__('Locations'),
            'current_usage' => $current_locations,
            'max_usage' => $location_limit
        ];
        
        // Appointment limit
        $appointment_limit = $plan->appointment_limit ?? -1;
        $current_appointments = \BookneticApp\Models\Appointment::where('tenant_id', $tenant->id)->count();
        $limits['appointment'] = [
            'title' => bkntc__('Appointments'),
            'current_usage' => $current_appointments,
            'max_usage' => $appointment_limit
        ];
        
        // Customer limit
        $customer_limit = $plan->customer_limit ?? -1;
        $current_customers = \BookneticApp\Models\Customer::where('tenant_id', $tenant->id)->count();
        $limits['customer'] = [
            'title' => bkntc__('Customers'),
            'current_usage' => $current_customers,
            'max_usage' => $customer_limit
        ];
        
        return $limits;
    }
} 