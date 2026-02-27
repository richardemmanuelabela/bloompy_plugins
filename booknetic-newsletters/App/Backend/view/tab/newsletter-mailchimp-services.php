<?php

use function \BookneticAddon\Newsletters\bkntc__;
use BookneticAddon\Newsletters\NewslettersAddon;
$parameters = $parameters['newsletter'];
$services = $parameters['services'] ?? [];
$mailchimpLists = $parameters['mailchimp_lists'] ?? [];
$mailchimpActiveListId = $parameters['mailchimp_active_list_id'] ?? [];
$mailchimpMap = $parameters['mailchimp_map'] ?? [];
$mailchimpDefault = $parameters['mailchimp_default'] ?? '';
$message = $parameters['message'] ?? '';

/**
 * @var mixed $parameters
 */
?>
<script type="text/javascript" src="<?php echo NewslettersAddon::loadAsset('assets/backend/js/lists.js')?>" id="add_new_JS" ></script>
<div class="form-row">
	<div class="form-group col-md-6 input-container">
		<label for="mailchimp_service_select"><?php echo bkntc__( 'Mailchimp Assigning Lists' );?></label>
		<select name="mailchimp_service_select" id="mailchimp_service_select" class="list_select form-control" >
			<option value=""  class="no-value" ><?php echo bkntc__('-- No list --'); ?></option>
			<?php foreach ($mailchimpLists as $list): ?>
				<option value="<?php echo esc_attr($list['id']); ?>" <?php if (($mailchimpActiveListId ?? '') == $list['id']) echo 'selected'; ?>>
					<?php echo esc_html($list['name'] ?? $list['id']); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>