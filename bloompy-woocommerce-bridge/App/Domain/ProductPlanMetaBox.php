<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BookneticSaaS\Models\Plan;

/**
 * Handles the WooCommerce product meta box for associating Booknetic plans.
 */
class ProductPlanMetaBox
{
    public function registerHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'addPlanIdMetabox']);
        add_action('save_post_product', [$this, 'savePlanIdMetabox']);
    }

    public function addPlanIdMetabox(): void
    {
        add_meta_box(
            'bloompy_plan_id_box',
            'Bloompy Plan',
            [$this, 'renderPlanIdMetabox'],
            'product',
            'side'
        );
    }

    public function renderPlanIdMetabox($post): void
    {
        $selected = get_post_meta($post->ID, '_bloompy_plan_id', true);
        $plans = Plan::fetchAll();

        echo '<label for="bloompy_plan_id">Select Plan:</label>';
        echo '<select id="bloompy_plan_id" name="bloompy_plan_id" style="width:100%;">';
        echo '<option value="">-- None --</option>';

        foreach ($plans as $plan) {
            $isSelected = selected($selected, $plan->id, false);
            echo "<option value=\"{$plan->id}\" {$isSelected}>{$plan->name}</option>";
        }

        echo '</select>';
    }

    public function savePlanIdMetabox($post_id): void
    {
        if (
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
            !isset($_POST['bloompy_plan_id']) ||
            wp_is_post_revision($post_id) ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $this->savePlanIdOnVariations($post_id, $_POST['bloompy_plan_id']); 
        
        update_post_meta($post_id, '_bloompy_plan_id', sanitize_text_field($_POST['bloompy_plan_id']));
    }

    public function savePlanIdOnVariations( $parent_id, $bloompy_plan_id ) {
        $parent_product = wc_get_product( $parent_id );
        if ( ! $parent_product || ! $parent_product->is_type( 'variable' ) ) {
            return; // Exit if not a valid variable product
        }

        foreach ( $parent_product->get_children() as $variation_id ) {
            update_post_meta( $variation_id, '_bloompy_plan_id', sanitize_text_field( $bloompy_plan_id ) );
        }
    }
} 