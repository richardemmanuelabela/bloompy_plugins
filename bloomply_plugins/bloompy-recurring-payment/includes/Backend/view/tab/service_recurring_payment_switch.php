<?php

use function \Bloompy\RecurringPayments\bkntc__;
use Bloompy\RecurringPayments\RecurringPaymentsAddon;

$serviceId = $parameters['service_id'] ?? 0;
$automaticRecurringPaymentSwitch = $parameters['automatic_recurring_payment_switch'] ?? '';
$isChecked = !empty($automaticRecurringPaymentSwitch) && $automaticRecurringPaymentSwitch !== '0';

/**
 * @var mixed $parameters
 */
?>
<script type="text/javascript" src="<?php echo RecurringPaymentsAddon::loadAsset('assets/backend/js/recurring_payments.js')?>" id="add_new_JS" ></script>
<div class="form-row" data-for="automatic_recurring_payment" id="automatic_recurring_payment">
	<div class="form-group col-md-12">
        <label for="automatic_recurring_payment_switch"><span class="required-star"><?php echo '&nbsp;'; ?></span></label>
        <div class="form-control-checkbox">
            <span class="enable_deposit_text">
                <i class="fa fa-info-circle help-icon do_tooltip" data-content="<?php echo esc_attr(bkntc__( 'Enable automatic recurring payment for this service' )); ?>"></i>
                <label for="automatic_recurring_payment_switch"><?php echo esc_html(bkntc__('Automatic recurring payment')); ?></label>
            </span>

            <div class="fs_onoffswitch">
                <input type="checkbox" id="automatic_recurring_payment_switch" name="automatic_recurring_payment_switch" class="fs_onoffswitch-checkbox" value="1" <?php echo $isChecked ? 'checked' : ''; ?>>
                <label for="automatic_recurring_payment_switch" class="fs_onoffswitch-label"></label>
            </div>
        </div>
	</div>
</div>