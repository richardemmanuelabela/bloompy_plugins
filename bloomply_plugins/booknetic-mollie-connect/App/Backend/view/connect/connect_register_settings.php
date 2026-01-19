<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */

use BookneticAddon\Bloompy\Mollie\MollieAddon;
use function BookneticAddon\Bloompy\Mollie\bkntc__;

?>

<script type="text/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connect_register_settings.js'); ?>"></script>
    <div class="form-group text-center p-0 col-md-12">
        <div class="card-body mollie_connect_register_container">
            <label>Test mode? <input type="checkbox" value="yes" id="input_mollie_connect_test_mode" name="input_mollie_connect_test_mode" <?php checked($parameters['tenantSettings']['testmode'])?> /></label>
            <?php if (!empty($parameters['status']) && $parameters['status'] !== 'verified'): ?>
                <div class="alert alert-warning"><?php echo bkntc__('Activation pending'); ?></div>
                <h5 class="card-title"><?php echo bkntc__('Your Mollie account is under review'); ?></h5>
                <div class="form-group">
                    <p class="card-title"><?php echo bkntc__('Your current verification status is') . ': ' . htmlspecialchars($parameters['status']); ?></p>
                </div>
                <a href="#" class="btn btn-primary mollie_connect_verify_btn"><?php echo bkntc__('Go to Mollie Dashboard'); ?></a>
            <?php else: ?>
                <div class="alert alert-success"><?php echo bkntc__('Your Mollie account is fully verified!'); ?></div>
                <h5 class="card-title"><?php echo bkntc__('No further action is needed.'); ?></h5>
            <?php endif; ?>

        </div>
    </div>


