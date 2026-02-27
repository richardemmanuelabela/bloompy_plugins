<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Bloompy\Mollie\MollieAddon;
use function BookneticAddon\Bloompy\Mollie\bkntc__;

/**
 * @var mixed $parameters
 */

?>

<link rel="stylesheet" href="<?php echo MollieAddon::loadAsset('assets/backend/css/connect_settings_saas.css' )?>">
<script type="application/javascript" src="<?php echo MollieAddon::loadAsset('assets/backend/js/connected_tenants_saas.js')?>"></script>


<div id="connected_tenants_modal">

    <div class="connected_tenants">
        <div class="connected_tenants_title confirm_modal_icon_div">
            <div class="connected_tenants_title_text confirm_modal_title">List of connected tenants</div>
        </div>

        <div class="card connected_tenants_body">
            <div class="card-header text-center connected_tenants_header">
                <div>Tenant</div>
                <div>Status</div>
            </div>

            <ul class="list-group list-group-flush">

                <?php foreach( $parameters[ 'tenants' ] AS $key => $tenant ): ?>
                    <li class="list-group-item" data-tenant-id="<?php echo $key ?>" data-account-id="<?php echo $tenant['mollie_connect_account_id'] ?>">
                        <div class="connected_tenants_list_wrapper">
                            <div class="connected_tenants_list_inner">
                                <input type="checkbox" class="connected_tenants_checkbox">
                                <?php echo $tenant['email'] ?>
                            </div>

                            <div class="connected_tenants_status <?php echo $tenant['status'] ?> ml-2">
                                <?php echo $tenant['status'] ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>

            </ul>
        </div>
    </div>

    <div class="confirm_modal_actions">
        <button class="btn btn-lg btn-secondary cancel_accounts_btn ml-3" data-dismiss="modal" type="button">CLOSE</button>
        <button class="btn btn-lg btn-danger delete_btn ml-3" type="button">DELETE</button>
    </div>

</div>