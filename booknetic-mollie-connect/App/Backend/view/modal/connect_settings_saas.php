<?php

defined( 'ABSPATH' ) or die();
use \BookneticAddon\Bloompy\Mollie\MollieAddon;
use BookneticApp\Providers\Helpers\Helper;
use function \BookneticAddon\Bloompy\Mollie\bkntc__;

?>


<script type="application/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connect_settings_saas.js')?>"></script>


<div class="form-row">

    <div class="form-group col-md-12">
        <label for="input_mollie_connect_client_id"><?php echo bkntc__('Application ID')?>:</label>
        <input class="form-control" id="input_mollie_connect_client_id" value="<?php echo htmlspecialchars( Helper::getOption('mollie_connect_client_id', '') )?>">
    </div>

    <div class="form-group col-md-12">
        <label for="input_mollie_connect_client_secret"><?php echo bkntc__('Secret Token')?>:</label>
        <input class="form-control" id="input_mollie_connect_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('mollie_connect_client_secret', '') )?>">
    </div>

    <div class="form-group col-md-6">
        <label for="input_mollie_connect_platform_fee"><?php echo bkntc__('Platform Fee')?>:</label>
        <input class="form-control" id="input_mollie_connect_platform_fee" value="<?php echo Helper::getOption('mollie_connect_platform_fee', '0', false) ?>">
    </div>

    <div class="form-group col-md-6">
        <label for="input_mollie_connect_fee_type"><?php echo bkntc__('Charge as')?>:</label>
        <select class="form-control" id="input_mollie_connect_fee_type">
            <option value="percent"<?php echo Helper::getOption('mollie_connect_fee_type', 'price', false)=='percent' ? ' selected':''?>>%</option>
            <option value="price"<?php echo Helper::getOption('mollie_connect_fee_type', 'price', false)=='price' ? ' selected' : ''?>><?php echo htmlspecialchars( Helper::currencySymbol() )?></option>
        </select>
    </div>

    <div class="form-group col-md-12">
        <label for="input_mollie_connect_terms_page"><?php echo bkntc__('Terms and Conditions Page URL')?>:</label>
        <input class="form-control" id="input_mollie_connect_terms_page" value="<?php echo htmlspecialchars( Helper::getOption('mollie_connect_terms_page', '', false) )?>">
    </div>

    <button style="display: none;" type="button" id="manage_connected_tenants" class="btn btn-lg btn-info btn-block"><i class="fa fa-user pr-2"></i> MANAGE CONNECTED TENANTS</button>

</div>
