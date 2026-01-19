<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use WP_Query;

/**
 * Handles plan upgrade UI and logic.
 */
class UpgradeFlowController
{
    public function registerHooks(): void
    {
        add_shortcode('bloompy_upgrade_plans', [$this, 'renderUpgradeUi']);
    }

    public function renderUpgradeUi(): string
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please log in to manage your plan.</p>';
        }

        $tenant = Tenant::where('user_id', $user_id)->fetch();
        if (!$tenant) {
            return '<p>No tenant profile found.</p>';
        }

        // Redirect to the Booknetic admin plan upgrades page
        $admin_url = admin_url('admin.php?page=bloompy&module=bloompy_plan_upgrades');
        return '<p>Please <a href="' . esc_url($admin_url) . '">click here</a> to manage your plan upgrades in the Booknetic admin.</p>';
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
} 