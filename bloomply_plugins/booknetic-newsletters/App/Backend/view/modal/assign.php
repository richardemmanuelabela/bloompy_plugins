<?php

defined('ABSPATH') or die();

use function \BookneticAddon\Newsletters\bkntc__;
use BookneticAddon\Newsletters\NewslettersAddon;
$parameters = $parameters['newsletter'];
$assignMode = $parameters['assign_mode'] ?? '';
$services = $parameters['services'] ?? [];
$mailblueLists = $parameters['mailblue_lists'] ?? [];
$mailblueMap = $parameters['mailblue_map'] ?? [];
$mailblueDefault = $parameters['mailblue_default'] ?? '';
$mailchimpLists = $parameters['mailchimp_lists'] ?? [];
$mailchimpMap = $parameters['mailchimp_map'] ?? [];
$mailchimpDefault = $parameters['mailchimp_default'] ?? '';
$message = $parameters['message'] ?? '';

/**
 * @var mixed $parameters
 */

?>


<script type="text/javascript" src="<?php echo NewslettersAddon::loadAsset('assets/backend/js/lists.js')?>" id="add_new_JS" ></script>
<link rel="stylesheet" href="<?php echo NewslettersAddon::loadAsset('assets/backend/css/assign.css')?>">

<div class="fs-modal-title">
	<div class="title-text newsletter-title-text"><?php echo bkntc__( 'MailBlue Assigning Lists to Services' );?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>
<div class="fs-modal-body">
	<div class="fs-modal-body-inner newsletter-modal-body-inner">
        <form method="post">
            <div class="tab-pane active" id="tab_newsletter_list">
                <input type="hidden" id="assign_mode" value="<?php echo $assignMode;?>"/>
                <?php if ($assignMode === 'mailblue'): ?>
                    <div class="form-row field-name-container">
                        <div class="form-group col-md-6 field-name">
                            <?php echo bkntc__('Service'); ?>
                        </div>
                        <div class="form-group col-md-6 field-name">
                            <?php echo bkntc__('MailBlue List'); ?>
                        </div>
                    </div>
                    <?php foreach ($services as $service): ?>
                        <div class="form-row newsletter-select-fow">
                            <div class="form-group col-md-6 input-label">
                                <?php echo esc_html($service['name']); ?>
                            </div>
                            <div class="form-group col-md-6 input-container">
                                <select name="mailblue_service_<?php echo (int)$service['id']; ?>" id="mailblue_service_<?php echo (int)$service['id']; ?>" class="list_select form-control" >
                                    <option value=""  class="no-value" ><?php echo bkntc__('-- No list --'); ?></option>
                                    <?php foreach ($mailblueLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if (($mailblueMap[$service['id']] ?? '') == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($assignMode === 'mailchimp'): ?>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <?php echo bkntc__('Service'); ?>
                        </div>
                        <div class="form-group col-md-6">
                            <?php echo bkntc__('MailChimp List'); ?>
                        </div>
                    </div>
                    <?php foreach ($services as $service): ?>
                        <div class="form-row">
                            <div class="form-group col-md-6 input-label">
                                <?php echo esc_html($service['name']); ?>
                            </div>
                            <div class="form-group col-md-6">
                                <select name="mailchimp_service_<?php echo (int)$service['id']; ?>" id="mailchimp_service_<?php echo (int)$service['id']; ?>"  class="list_select form-control">
                                    <option value="" class="no-value" ><?php echo bkntc__('-- No list --'); ?></option>
                                    <?php foreach ($mailchimpLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if (($mailchimpMap[$service['id']] ?? '') == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </form>

    </div>
</div>
<div class="fs-modal-footer">
	<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
	<button type="button" class="btn btn-lg btn-primary" id="addListSave"><?php echo bkntc__('SAVE' );?></button>
</div>

