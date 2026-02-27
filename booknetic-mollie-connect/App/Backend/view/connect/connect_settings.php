<?php

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use \BookneticAddon\Bloompy\Mollie\MollieAddon;

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */

use function BookneticAddon\Bloompy\Mollie\bkntc__;
?>

<script type="text/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connect_settings.js' )?>"></script>

<div class="form-group text-center p-0 col-md-12" style="">
    <div class="card-body mollie_connect_container">
        <label>Test mode? <input type="checkbox" value="yes" id="input_mollie_connect_test_mode" name="input_mollie_connect_test_mode" <?php checked($parameters['tenantSettings']['testmode'])?> /></label>

        <div class="alert alert-success"><?php echo bkntc__( 'Verified' ) ?></div>

        <p class="card p-2"><?php echo bkntc__('Bloompy fee per payment') . ': ' . $parameters['pricing']['display'] ?></p>

        <a href="#" class="btn btn-danger mollie_connect_revoke_btn mt-3">
            <?php echo bkntc__( 'Revoke Connection'); ?>
        </a>
    </div>
</div>

<input type="file" class="payment-method-settings-icon-input" style="display: none;">
