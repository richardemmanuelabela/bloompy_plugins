<?php

defined('ABSPATH') or die();

use \BookneticAddon\Bloompy\Mollie\MollieAddon;
use function \BookneticAddon\Bloompy\Mollie\bkntc__;

?>

<script type="text/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connect_setup_settings.js') ?>"></script>

<div class="form-group col-md-12 text-center p-0">

    <div class="card-body mollie_connect_setup_container">
        <img class="card-img-top" src="<?php echo MollieAddon::loadAsset('assets/backend/icons/broken-link.svg') ?>" alt="mollie_connect_error" style="height: 4rem;">
        <div class="alert alert-danger mt-3"><?php echo bkntc__('Your Mollie connection is broken or expired.') ?></div>
        <h5 class="card-title"><?php echo bkntc__('Reconnect your Mollie Account'); ?></h5>
        <p><?php echo bkntc__('Click the button below to reconnect and restore your Mollie account access.') ?></p>
        <a href="#" class="btn btn-primary mollie_connect_register_btn"><?php echo bkntc__('Reconnect'); ?></a>
    </div>
</div>