<?php
defined('ABSPATH') or die();
use BloompyAddon\WooCommerceBridge\WooCommerceBridgeAddon;
use function \BloompyAddon\WooCommerceBridge\bkntc__;

$current_plan = $parameters['current_plan'] ?? null;
$available_plans = $parameters['available_plans'] ?? [];
$tenant = $parameters['tenant'] ?? null;
$plan_limits = $parameters['plan_limits'] ?? null;

//get the pay period.
$attributes = [];
if ( ! empty( $parameters['current_variation_id'] ) ) {
	$current_variation = wc_get_product( $parameters['current_variation_id'] );
	$attributes        = $current_variation ? $current_variation->get_attributes() : [];
}
$period = reset( $attributes );
?>

<link rel="stylesheet" href="<?php echo WooCommerceBridgeAddon::loadAsset('assets/backend/css/woocommerce-bridge.css')?>">

<div class="upgrade-plan-main">
    <div class="m_header clearfix">
        <div class="m_head_title float-left">
            <span class="name"><?php echo bkntc__('Plan Upgrades'); ?></span>
        </div>
    </div>
    <div class="fs_separator"></div>
    <div class="d-flex flex-column justify-content-center align-items-center">
        <div class="upgrade_plan_card">
            <div class="upgrade_plan_card--label" for="workflow_name">
                <div class="form-row mt-4 plan-table-form-row">
                    <div class="form-group col-md-12 plan-table-form-group">
                        <div class="fs_data_table_wrapper">
                            <?php if ($current_plan): ?>
                                <table class="table-gray-2 dashed-border">
                                    <thead>
                                    <tr>
                                        <th><?php echo bkntc__('Current Plan'); ?></th>
                                        <th><?php echo bkntc__('Pay Period'); ?></th>
                                        <th><?php echo bkntc__('Expiration Date'); ?></th>
                                        <th><?php echo bkntc__('Status'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($current_plan->name); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo esc_html($period); ?>
                                            </td>
                                            <td>
                                                <?php if ($tenant->expires_in): ?>
                                                    <?php echo bkntc__('Expires:'); ?> <?php echo esc_html(date('F j, Y', strtotime($tenant->expires_in))); ?>
                                                <?php else: ?>
                                                    <?php echo bkntc__('No expiration date'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="active"><?php echo bkntc__('ACTIVE'); ?></span>
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p style="margin:0;color:#666;"><?php echo bkntc__('No plan currently assigned.'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($plan_limits)): ?>
            <div class="upgrade_plan_card">
                <div class="ms-title">
                    <?php echo bkntc__('Plan Features & Usage'); ?>
                </div>
                <div class="ms-content ms-content-plans">
                    <!-- Plan Features and Usage -->
                    <div class="plan-features-and-usage-section">
                        <?php foreach ($plan_limits as $key => $limit): ?>
                            <div class="plan-features-and-usage-container">
                                <div class="plan-feature">
                                    <?php echo esc_html($limit['title']); ?>
                                </div>
                                <div class="plan-usage" >
                                    <?php echo esc_html($limit['current_usage']); ?> / <?php echo ($limit['max_usage'] == -1 ? '∞' : esc_html($limit['max_usage'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <div class="upgrade_plan_card">
            <div class="ms-title">
                <?php echo bkntc__('Available Upgrades'); ?>
            </div>
            <div class="ms-content ms-content-plans">
                <?php if (!empty($available_plans)): ?>
                    <div class="plan-container">
                        <?php foreach ($available_plans as $plan_data): ?>
							<?php
                                $plan = $plan_data['plan'];
                                $product_id = $plan_data['product_id'];
                                $subscription_id = $plan_data['subscription_id'];
                                $item_id = $plan_data['item_id'];
                                $nonce = wp_create_nonce('wcs_switch_request');
                                $product = wc_get_product($product_id);
                                $current_variation_id = $plan_data['current_variation_id'];

                                //get the parent product id and generate the URL
                                $parent_id = $product->get_parent_id();
                                $product_url = get_permalink($product_id);
                                $variations = [];

                                // Get variations for variable products
                                if ($product && $product->is_type('variable')) {
                                    $variations = $this->getProductVariations($product);
                                }
							?>
                            <div class="plan-card">
                                <h4 class="plan-title"><?php echo esc_html($plan->name); ?></h4>

                                <!-- Plan Features -->
                                <?php if (!empty($plan->description)): ?>
                                    <p class="plan-description">
                                        <?php echo $plan->description; ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Upgrade Options -->
                                <div class="upgrade-option-container">
                                    <?php if ($product && $product->is_type('variable') && !empty($variations)): ?>
                                        <!-- Variable Product with Variations -->
                                        <div class="form-row">
                                            <div class="form-group col-md-12">
                                                <label for="variation_<?php echo $product_id; ?>" style="display:block;margin-bottom:6px;font-weight:500;color:#333;">
                                                    <?php echo bkntc__('Select Plan Option:'); ?>
                                                </label>
                                                <select id="variation_<?php echo $product_id; ?>" class="select-plan list_select form-control">
                                                    <option value=""><?php echo bkntc__('-- Choose an option --'); ?></option>
                                                    <?php foreach ($variations as $variation): ?>
														<?php if($current_variation_id == $variation['variation_id'] ){ continue; }?>
                                                        <option value="<?php echo $variation['variation_id']; ?>"
                                                                data-price="<?php echo esc_attr($variation['price_html']); ?>">
                                                            <?php echo esc_html($variation['name']); ?> - <?php echo $variation['price_html']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="button"
                                                data-product-id="<?php echo $product_id; ?>"
                                                data-plan-name="<?php echo esc_attr($plan->name); ?>"
                                                data-subscription-id="<?php echo $subscription_id; ?>"
                                                data-item-id="<?php echo $item_id; ?>"
                                                data-nonce="<?php echo $nonce; ?>"
                                                data-product-url="<?php echo $product_url; ?>"
                                                class="subscribe-btn btn btn-lg btn-primary float-right ml-1"
                                                disabled>
                                            <?php echo bkntc__('Subscribe'); ?>
                                        </button>
                                    <?php else: ?>
                                        <!-- Simple Product -->
                                        <a href="<?php echo esc_url(add_query_arg('add-to-cart', $product_id, wc_get_checkout_url())); ?>"
                                        class="btn btn-lg btn-primary float-right ml-1">
                                            <?php echo bkntc__('Subscribe Now'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-upgrade-container" >
                        <p class="no-upgrade-option"><?php echo bkntc__('No upgrade options available at the moment.'); ?></p>
                        <p class="contact-support" ><?php echo bkntc__('Please contact support for more information.'); ?></p>
                    </div>
                <?php endif;?>
                <!-- Information Section -->
                <div class="how_it_works_container">
                    <h4 ><?php echo bkntc__('How it works'); ?></h4>
                    <ul>
                        <li><?php echo bkntc__('Select a plan option above to upgrade your current subscription'); ?></li>
                        <li><?php echo bkntc__('You\'ll be redirected to WooCommerce to complete the purchase'); ?></li>
                        <li><?php echo bkntc__('Your new plan will be activated immediately after payment'); ?></li>
                        <li><?php echo bkntc__('For plans with multiple options, choose the variation that best fits your needs'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle variation selection
    $('select[id^="variation_"]').on('change', function() {
        var productId = $(this).attr('id').replace('variation_', '');
        var variationId = $(this).val();
        var subscribeBtn = $('.subscribe-btn[data-product-id="' + productId + '"]');
        
        if (variationId) {
            subscribeBtn.prop('disabled', false);
            subscribeBtn.css('background', '#007cba');
        } else {
            subscribeBtn.prop('disabled', true);
            subscribeBtn.css('background', '#ccc');
        }
    });
    
    // Handle subscribe button clicks
    $('.subscribe-btn').on('click', function() {
        var productId = $(this).data('product-id');
        var planName = $(this).data('plan-name');
        var subscription_id = $(this).data('subscription-id');
        var item_id = $(this).data('item-id');
        var nonce = $(this).data('nonce');
        var product_url = $(this).data('product-url');
        var variationSelect = $('#variation_' + productId);
        var variationId = variationSelect.val();
        
        if (!variationId) {
            alert('<?php echo esc_js(bkntc__('Please select a plan option first.')); ?>');
            return;
        }
        
        // Confirm subscription
        if (confirm('<?php echo esc_js(bkntc__('Are you sure you want to subscribe to')); ?> "' + planName + '"?')) {
            // Redirect to checkout with the selected variation
            //var checkoutUrl = '<?php echo esc_url(wc_get_checkout_url()); ?>?add-to-cart=' + productId + '&variation_id=' + variationId;
           // var checkoutUrl = product_url+'?switch-subscription='+subscription_id+'&item='+item_id+'&_wcsnonce=' + nonce;
            var checkoutUrl = product_url+'?switch-subscription='+subscription_id+'&item='+item_id+'&variation_id='+variationId+'&add-to-cart='+productId+'&auto-switch=1&_wcsnonce=' + nonce;
            window.location.href = checkoutUrl;
        }
    });
    $(".select-plan").select2({
        theme:			'bootstrap',
        placeholder:	booknetic.__('select'),
        allowClear:		true
    });
});
</script> 