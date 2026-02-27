<?php

defined('ABSPATH') or die();
use BookneticAddon\Newsletters\NewslettersAddon;


use function \BookneticAddon\Newsletters\bkntc__;

$apiKey = $parameters['mailblue_api_key'] ?? '';
$apiUrl = $parameters['mailblue_api_url'] ?? '';
$message = $parameters['message'] ?? '';
$mailblueLists = $parameters['mailblue_lists'] ?? [];
$services = $parameters['services'] ?? [];
$mailblueDefault = $parameters['mailblue_default'] ?? '';
$mailchimpApiKey = $parameters['mailchimp_api_key'] ?? '';
$mailchimpLists = $parameters['mailchimp_lists'] ?? [];
$mailchimpDefault = $parameters['mailchimp_default'] ?? '';
?>
<link rel="stylesheet" href="<?php echo NewslettersAddon::loadAsset('assets/backend/css/edit.css')?>" type="text/css">
<script type="text/javascript" src="<?php echo NewslettersAddon::loadAsset('assets/backend/js/newsletters.js', 'newsletters')?>"></script>
<div class="newsletter-wrapper">
    <form method="post">
        <div class="newsletter-header">
            <div class="newsletter-title-page">
                <h2 class="title-page"><?php echo bkntc__('Newsletter Integrations'); ?></h2>
            </div>
        </div>
		<?php if ($message): ?>
            <div class="success-message">✔ <?php echo esc_html($message); ?></div>
		<?php endif; ?>
       

        <div class="accordion">
            <div class="accordion-item">
                <div class="accordion-header active"><h2><?php echo bkntc__('MailBlue'); ?></h2><i class="arrow downIcon"></i></div>
                <div class="accordion-content" style="display: block;">
<!--                        <legend style="font-weight:bold;">--><?php //echo bkntc__('MailBlue'); ?><!--</legend>-->
                    <div class="form-row">
                        <div class="form-control-container form-group col-md-6">
                            <label for="mailblue_api_key"><?php echo bkntc__('API Key'); ?></label>
                            <input type="text" id="mailblue_api_key" class="form-control" name="mailblue_api_key" value="<?php echo esc_attr($apiKey); ?>" style="width:100%;padding:8px;">
                        </div>
                        <div class="form-control-container form-group col-md-6">
                            <label for="mailblue_api_url"><?php echo bkntc__('API URL'); ?></label>
                            <input type="text" id="mailblue_api_url" class="form-control" name="mailblue_api_url" value="<?php echo esc_attr($apiUrl); ?>" style="width:100%;padding:8px;">
                        </div>
                    </div>
                    <div class="form-row">
<!--                        --><?php //if (!empty($mailblueLists)): ?>
                        <div class="form-control-container form-group col-md-6">
                            <div class="active-list">
                                <h3><?php echo bkntc__('Default MailBlue List'); ?></h3>
                                <select name="mailblue_default_list" class="form-control">
                                    <option value=""><?php echo bkntc__('-- No default list --'); ?></option>
                                    <?php foreach ($mailblueLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if ($mailblueDefault == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="assign-list-container">
                                    <a href="#<?php //echo admin_url('admin.php?page=bloompy&module=newsletters&assign=mailblue') ?>" id="newsletter-modal" data-name="mailblue" style="color:#3b82f6;text-decoration:underline;cursor:pointer;"><?php echo bkntc__('Assign lists to individual services'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
<!--                        --><?php //endif; ?>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header"><h2><?php echo bkntc__('MailChimp'); ?></h2><i class="arrow downIcon"></i></div>
                <div class="accordion-content">
<!--                        <legend style="font-weight:bold;">--><?php //echo bkntc__('MailChimp'); ?><!--</legend>-->
                    <div class="form-row">
                        <div class="form-control-container  form-group col-md-6">
                            <label for="mailchimp_api_key" style="display:block;font-weight:500;"><?php echo bkntc__('API Key'); ?></label>
                            <input type="text" id="mailchimp_api_key" name="mailchimp_api_key" class="form-control" value="<?php echo esc_attr($mailchimpApiKey); ?>" style="width:100%;padding:8px;">
                        </div>
                    </div>
<!--                    --><?php //if (!empty($mailchimpLists)): ?>
                    <div class="form-row">
                        <div class="form-control-container  form-group col-md-6">
                            <div class="active-list">
                                <h3><?php echo bkntc__('Default MailChimp List'); ?></h3>
                                <select name="mailchimp_default_list" class="form-control">
                                    <option value=""><?php echo bkntc__('-- No default list --'); ?></option>
                                    <?php foreach ($mailchimpLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if ($mailchimpDefault == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="assign-list-container">
                                    <a href="#<?php //echo admin_url('admin.php?page=bloompy&module=newsletters&assign=mailchimp') ?>" class="assign-list-link" id="newsletter-modal" data-name="mailchimp"><?php echo bkntc__('Assign lists to individual services'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
<!--                    --><?php //endif; ?>
                </div>
            </div>
        </div>
        <div class="newsletter-buttons form-row">
            <button type="submit" class="btn btn-lg btn-primary float-right ml-1" id="invoice_save_btn"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('Save'); ?></button>
        </div>
    </form>
</div>
<script>
    $(document).ready(function() {
        $('.accordion-header').click(function() {
            $('.accordion-content').not($(this).next()).slideUp();

            $(".accordion-header").not(this).removeClass("active");
            $(this).toggleClass("active");
            $(this).next().slideToggle();
        });
    });
</script>