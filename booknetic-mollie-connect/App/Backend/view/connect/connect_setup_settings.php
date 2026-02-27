<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters;
 */

use BookneticAddon\Bloompy\Mollie\MollieAddon;

use function BookneticAddon\Bloompy\Mollie\bkntc__;
?>

<script type="text/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connect_setup_settings.js' )?>"></script>
<div class="form-group col-md-12 text-center p-0">

    <div class="card-body mollie_connect_setup_container">
        <label>Test mode? <input type="checkbox" value="yes" id="input_mollie_connect_test_mode" name="input_mollie_connect_test_mode" <?php checked($parameters['tenantSettings']['testmode'])?> /></label>
        <div class="alert alert-dark"><?php echo bkntc__('Not activated') ?></div>
        <h5 class="card-title"><?php echo bkntc__('Connect your Mollie Account'); ?></h5>
        <p class="card p-2"><?php echo bkntc__('Bloompy fee per payment') . ': ' . $parameters['pricing']['display'] ?></p>
        <p><?php echo bkntc__('Click the button to start the registration process for Mollie Connect'); ?></p>
        <h6 style="color: rebeccapurple;"><?php echo bkntc__('By clicking the register button you agree to our'); ?> <a href="<?php echo $parameters['terms'] ?>"> <?php echo bkntc__('terms and services'); ?></a></h6>
        <a href="#" class="btn btn-primary mollie_connect_register_btn"><?php echo bkntc__('Register'); ?></a>
    </div>
</div>
