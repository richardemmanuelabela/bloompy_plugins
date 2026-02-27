<?php
namespace BloompyAddon\WooCommerceBridge\Domain\Shortcodes;

use BloompyAddon\WooCommerceBridge\WooCommerceBridgeAddon;
use BloompyAddon\WooCommerceBridge\Domain\Interfaces\ShortcodeInterface;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use function BloompyAddon\WooCommerceBridge\bkntc__;
use WC_Order;

class ThankYouPageShortcode implements ShortcodeInterface
{
    public static function register(): void
    {
        add_shortcode('bloompy_thank_you_page', [self::class, 'render']);
    }

    public static function render($atts): string
    {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }

        // Backfill session from query args if session is empty (handles direct redirects from payment filters)
        if (empty($_SESSION['bloompy_purchase_order_id'])) {
            $order_id_from_query = isset($_GET['order']) ? absint($_GET['order']) : 0;
            $key_from_query = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

            if ($order_id_from_query && $key_from_query) {
                $order = wc_get_order($order_id_from_query);
                if ($order instanceof WC_Order && hash_equals($order->get_order_key(), $key_from_query)) {
                    $_SESSION['bloompy_purchase_order_id'] = $order_id_from_query;
                    $_SESSION['bloompy_purchase_user_id'] = $order->get_user_id();
                }
            }
        }

        $order_id = $_SESSION['bloompy_purchase_order_id'] ?? null;
        $user_id = $_SESSION['bloompy_purchase_user_id'] ?? null;

        if (!$order_id || !$user_id) {
            return '
            <link rel="stylesheet" href="'.WooCommerceBridgeAddon::loadAsset('assets/frontend/css/woocommerce-bridge.css').'">
            <div class="main-wrapper">
                <h2 class="failed-h2">'.bkntc__("Purchase Information Not Found", 'bloompy-woocommerce-bridge').'</h2>
                <p class="thank-you-content">'.bkntc__("Unable to retrieve your purchase details. Please contact support if you need assistance.", 'bloompy-woocommerce-bridge').'</p>
                <div class="action-buttons-container" ><a href="' . admin_url('admin.php?page=bloompy&module=dashboard') . '" class="go-to-dashboard-btn">'.__("Go to Dashboard", 'bloompy-woocommerce-bridge').'</a></div>
            </div>';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return '
            <link rel="stylesheet" href="'.WooCommerceBridgeAddon::loadAsset('assets/frontend/css/woocommerce-bridge.css').'">
            <div class="main-wrapper">
                <h2 class="failed-h2">'.bkntc__("Order Not Found", 'bloompy-woocommerce-bridge').'</h2>
                <p class="thank-you-content">'.bkntc__("Unable to retrieve your order details. Please contact support if you need assistance.", 'bloompy-woocommerce-bridge').'</p>
                <div class="action-buttons-container" ><a href="' . admin_url('admin.php?page=bloompy&module=dashboard') . '" class="go-to-dashboard-btn">'.bkntc__("Go to Dashboard", 'bloompy-woocommerce-bridge').'</a></div>
            </div>';
        }

        // Ensure tenant exists (creates it if needed)
        $tenant = self::ensureTenantExists($user_id);
        if (!$tenant) {
            return '
            <link rel="stylesheet" href="'.WooCommerceBridgeAddon::loadAsset('assets/frontend/css/woocommerce-bridge.css').'">
            <div class="main-wrapper">
                <h2 class="failed-h2">'.bkntc__("Account Setup In Progress", 'bloompy-woocommerce-bridge').'</h2>
                <p class="thank-you-content">'.bkntc__("Your account is being set up. Please refresh the page in a moment.", 'bloompy-woocommerce-bridge').'</p>
                <div class="action-buttons-container" ><a href="javascript:location.reload()" class="go-to-dashboard-btn">'.bkntc__("Go to Dashboard", 'bloompy-woocommerce-bridge').'</a></div>

            </div>';
        }

        $current_plan = $tenant->plan_id ? Plan::get($tenant->plan_id) : null;

        ob_start();
        ?>
        <link rel="stylesheet" href="<?php echo WooCommerceBridgeAddon::loadAsset('assets/frontend/css/woocommerce-bridge.css')?>">
        <div class="main-wrapper" >
            <!-- Success Icon -->
            <div class="success-icon-container" >
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </div>

            <!-- Thank You Message -->
            <h1 class="thank-you-h1"><?php echo bkntc__("Thank You!", 'bloompy-woocommerce-bridge');?></h1>
            <p class="thank-you-content">
                <?php echo bkntc__("Your purchase has been completed successfully. Your new plan is now active!", 'bloompy-woocommerce-bridge');?>
            </p>

            <div class="plan-section">
                <!-- Order Details -->
                <div class="order-details-section">
                    <h3 class="order-details-h3"><?php echo bkntc__("Order Details", 'bloompy-woocommerce-bridge');?></h3>
                    <div class="order-details">
                        <div class="order-details-item">
                            <span class="field-name"><?php echo bkntc__("Order Number:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="item-value" >#<?php echo $order->get_order_number(); ?></span>
                        </div>
                        <div class="order-details-item">
                            <span class="field"><?php echo bkntc__("Order Date:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="item-value"><?php echo date_i18n(get_option('date_format'), $order->get_date_created()->getTimestamp()); ?></span>
                        </div>
                        <div class="order-details-item">
                            <span class="field"><?php echo bkntc__("Total Amount:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="item-value" ><?php echo $order->get_formatted_order_total(); ?></span>
                        </div>
                        <div class="order-details-item-payment-status">
                            <span class="field"><?php echo bkntc__("Payment Status:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="item-value"><?php echo ucfirst($order->get_status()); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Plan Information -->
                <?php if ($current_plan): ?>
                <div class="plan-information-section">
                    <h3 class="plan-information-h3"><?php echo bkntc__("Your New Plan", 'bloompy-woocommerce-bridge');?></h3>
                    <div class="plan-information" >
                        <div class="plan-container">
                            <span class="plan-details-label"><?php echo bkntc__("Plan Name:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="plan-name"><?php echo $current_plan->name; ?></span>
                        </div>
                        <?php if (!empty($current_plan->description)): ?>
                        <div class="plan-container-description">
                            <span class="plan-details-description-label"><?php echo bkntc__("Description:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="plan-description"><?php echo strip_tags($current_plan->description); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="plan-expiration-container" >
                            <span  class="plan-details-label"><?php echo bkntc__("Plan Expires:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="plan-expiration">
                                <?php echo $tenant->expires_in ? date_i18n(get_option('date_format'), strtotime($tenant->expires_in)) : __( 'No expiration', 'bloompy-woocommerce-bridge' ); ?>
                            </span>
                        </div>
                        <div class="plan-status-container">
                            <span  class="plan-details-label"><?php echo bkntc__("Status:", 'bloompy-woocommerce-bridge');?></span>
                            <span class="plan-status"><?php echo bkntc__("Active", 'bloompy-woocommerce-bridge');?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Next Steps -->
            <div class="next-steps-wrapper" >
                <h3 class="next-steps-h3"><?php echo bkntc__("What's Next?", 'bloompy-woocommerce-bridge');?></h3>
                <ul class="next-steps-ul">
                    <li><?php echo bkntc__("Your new plan features are now available in your Booknetic dashboard", 'bloompy-woocommerce-bridge');?></li>
                    <li><?php echo bkntc__("You can start using all the features included in your plan immediately", 'bloompy-woocommerce-bridge');?></li>
                    <li><?php echo bkntc__("Check your email for a confirmation of your purchase", 'bloompy-woocommerce-bridge');?></li>
                    <li><?php echo bkntc__("If you have any questions, please contact our support team", 'bloompy-woocommerce-bridge'); ?></li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons-container" >
                <a href="<?php echo admin_url('admin.php?page=bloompy&module=dashboard'); ?>" class="go-to-dashboard-btn">
                    <?php echo bkntc__("Go to Dashboard", 'bloompy-woocommerce-bridge');?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bloompy&module=bloompy_plan_upgrades'); ?>" class="manage-plan-btn">
                    <?php echo bkntc__("Manage Plan", 'bloompy-woocommerce-bridge');?>
                </a>
            </div>

            <!-- Support Information -->
            <div class="support-information-container">
                <p class="support-information-label"><?php echo bkntc__("Need help? Contact our support team", 'bloompy-woocommerce-bridge');?></p>
                <p class="support-information-details">
                    Email: <a href="mailto:support@bloompy.nl">support@bloompy.nl</a> | 
                    Phone: <a href="tel:+31123456789">+31 123 456 789</a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Ensure tenant exists for the given user, creating it if necessary
     * 
     * @param int $user_id WordPress user ID
     * @return object|null Tenant object or null on failure
     */
    private static function ensureTenantExists(int $user_id)
    {
        // Try to fetch existing tenant
        $tenant = Tenant::where('user_id', $user_id)->fetch();
        if ($tenant) {
            return $tenant;
        }

        // Tenant doesn't exist, create it
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) {
            error_log("[Bloompy Thank You Page] Failed to fetch WP user for tenant creation (user ID: $user_id)");
            return null;
        }

        // Add booknetic_saas_tenant role if not present
        if (!in_array('booknetic_saas_tenant', $user->roles)) {
            $user->add_role('booknetic_saas_tenant');
            error_log("[Bloompy Thank You Page] Added 'booknetic_saas_tenant' role to user ID: $user_id");
        }

        // Get default plan
        $defaultPlan = \BookneticSaaS\Models\Plan::where('is_default', 1)->fetch();
        
        // Insert new tenant
        $inserted = Tenant::insert([
            'user_id' => $user_id,
            'full_name' => $user->display_name ?: $user->user_login,
            'email' => $user->user_email,
            'domain' => sanitize_title($user->user_login),
            'plan_id' => $defaultPlan ? $defaultPlan->id : null,
            'expires_in' => \BookneticSaaS\Providers\Helpers\Date::dateSQL('+' . \BookneticSaaS\Providers\Helpers\Helper::getOption('trial_period', 30) . ' days'),
            'inserted_at' => \BookneticSaaS\Providers\Helpers\Date::dateTimeSQL(),
            'verified_at' => \BookneticSaaS\Providers\Helpers\Date::dateTimeSQL(),
        ]);

        if (!$inserted) {
            error_log("[Bloompy Thank You Page] Failed to insert new tenant for user ID $user_id");
            return null;
        }

        $tenant_id = Tenant::lastId();

        // Create initial tenant data if method exists
        if (method_exists(Tenant::class, 'createInitialData')) {
            Tenant::createInitialData($tenant_id);
        }

        // Trigger hooks
        do_action('bkntcsaas_tenant_sign_up_confirm', $tenant_id);
        do_action('bloompy_tenant_created_from_checkout', $tenant_id, $user_id);

        error_log("[Bloompy Thank You Page] Successfully created tenant ID $tenant_id for user ID $user_id");

        // Fetch and return the newly created tenant
        return Tenant::where('id', $tenant_id)->fetch();
    }
} 