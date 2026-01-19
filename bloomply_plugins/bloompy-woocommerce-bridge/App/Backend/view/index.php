<?php
defined('ABSPATH') or die();

use function \BloompyAddon\WooCommerceBridge\bkntc__;

$title = $parameters['title'] ?? '';
?>
<div style="max-width:800px;margin:40px auto;padding:24px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #0001;">
    <h2 style="margin-bottom:24px;"><?php echo esc_html($title); ?></h2>
    <p><?php echo bkntc__('This plugin integrates WooCommerce with Booknetic SaaS for plan assignment and upgrades.'); ?></p>
    <p><?php echo bkntc__('Features:'); ?></p>
    <ul style="margin-left:20px;">
        <li><?php echo bkntc__('Automatic plan assignment based on WooCommerce orders and subscriptions'); ?></li>
        <li><?php echo bkntc__('Product meta box for associating Booknetic plans'); ?></li>
        <li><?php echo bkntc__('Customized checkout flow for SaaS plans'); ?></li>
        <li><?php echo bkntc__('Post-purchase redirects to Booknetic admin'); ?></li>
    </ul>
</div> 