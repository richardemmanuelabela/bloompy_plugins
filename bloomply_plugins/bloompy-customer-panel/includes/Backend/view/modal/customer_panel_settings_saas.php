<?php

defined( 'ABSPATH' ) or die();

use Bloompy\CustomerPanel\CustomerPanelAddon;
use BookneticApp\Providers\Helpers\Helper;
use function Bloompy\CustomerPanel\bkntc__;

?>
<div id="booknetic_settings_area">
    <link rel="stylesheet" href="<?php echo CustomerPanelAddon::loadAsset('assets/backend/css/customer_panel_settings_saas.css')?>">
    <script type="application/javascript" src="<?php echo CustomerPanelAddon::loadAsset('assets/backend/js/customer_panel_settings_saas.js')?>"></script>

    <div class="actions_panel clearfix">
        <button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
    </div>

    <div class="settings-light-portlet">
        <div class="ms-title">
            <?php echo bkntc__('Customer Panel')?>
        </div>
        <div class="ms-content">

            <form class="position-relative">

                <div class="form-row enable_disable_row">

                    <div class="form-group col-md-2">
                        <input id="input_customer_panel_enable" type="radio" name="input_customer_panel_enable" value="off"<?php echo Helper::getOption('customer_panel_enable', 'off')=='off'?' checked':''?>>
                        <label for="input_customer_panel_enable"><?php echo bkntc__('Disabled')?></label>
                    </div>
                    <div class="form-group col-md-2">
                        <input id="input_customer_panel_disable" type="radio" name="input_customer_panel_enable" value="on"<?php echo Helper::getOption('customer_panel_enable', 'off')=='on'?' checked':''?>>
                        <label for="input_customer_panel_disable"><?php echo bkntc__('Enabled')?></label>
                    </div>

                </div>

                <div id="customer_panel_settings_area">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="input_customer_panel_page_id"><?php echo bkntc__('Page of Customer Panel')?>:</label>
                            <select class="form-control" id="input_customer_panel_page_id">
                                <?php foreach ( get_pages() AS $page ) : ?>
                                    <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('customer_panel_page_id', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <div class="form-control-checkbox">
                                <label for="input_customer_panel_allow_delete_account"><?php echo bkntc__('Allow customers to delete their account')?>:</label>
                                <div class="fs_onoffswitch">
                                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_customer_panel_allow_delete_account"<?php echo Helper::getOption('customer_panel_allow_delete_account', 'on', false )=='on'?' checked':''?>>
                                    <label class="fs_onoffswitch-label" for="input_customer_panel_allow_delete_account"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>