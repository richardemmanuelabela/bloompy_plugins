<?php

defined( 'ABSPATH' ) or die();

use Bloompy\CustomerPanel\CustomerPanelAddon;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function Bloompy\CustomerPanel\bkntc__;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo CustomerPanelAddon::loadAsset('assets/backend/css/customer_panel_settings.css')?>">
	<script type="application/javascript" src="<?php echo CustomerPanelAddon::loadAsset('assets/backend/js/customer_panel_settings.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Front-end panels')?>
			<span class="ms-subtitle"><?php echo bkntc__('Customer Panel')?></span>
		</div>
		<div class="ms-content">

			<form class="position-relative">
                <?php if ( ! Helper::isSaaSVersion() ) : ?>
				<div class="form-row enable_disable_row">

					<div class="form-group col-md-2">
						<input id="input_customer_panel_enable" type="radio" name="input_customer_panel_enable" value="off"<?php echo Helper::getOption('customer_panel_enable', 'off',false)=='off'?' checked':''?>>
						<label for="input_customer_panel_enable"><?php echo bkntc__('Disabled')?></label>
					</div>
					<div class="form-group col-md-2">
						<input id="input_customer_panel_disable" type="radio" name="input_customer_panel_enable" value="on"<?php echo Helper::getOption('customer_panel_enable', 'off',false)=='on'?' checked':''?>>
						<label for="input_customer_panel_disable"><?php echo bkntc__('Enabled')?></label>
					</div>

				</div>
                <?php endif; ?>

				<div id="customer_panel_settings_area">

					<div class="form-row">
                        <?php if ( ! Helper::isSaaSVersion() ) : ?>
                        <div class="form-group col-md-6">
                            <label for="input_customer_panel_page_id"><?php echo bkntc__('Page of Customer Panel')?>:</label>
                            <select class="form-control" id="input_customer_panel_page_id">
                                <?php foreach ( get_pages() AS $page ) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('customer_panel_page_id', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif;?>

                        <div class="form-group col-md-6">
                            <label for="input_timeslot_length"><?php echo bkntc__('Time restriction to change appointments')?>:</label>
                            <select class="form-control" id="input_time_restriction_to_make_changes_on_appointments">
                                <?php $minute = 1; while ( $minute < 34560 ) { ?>
                                    <option value="<?php echo $minute; ?>" <?php echo Helper::getOption( 'time_restriction_to_make_changes_on_appointments', '5' ) == $minute ? 'selected' : ''; ?>><?php echo Helper::secFormat( $minute * 60 ); ?></option>

                                    <?php
                                    if ( $minute >= 1440 )
                                    {
                                        $minute += 1440;
                                    }
                                    else if ( $minute >= 120 )
                                    {
                                        $minute += 60;
                                    }
                                    else if ( $minute >= 60 )
                                    {
                                        $minute += 30;
                                    }
                                    else if ( $minute >= 5 )
                                    {
                                        $minute += 5;
                                    }
                                    else
                                    {
                                        $minute++;
                                    }
                                    ?>
                                <?php } ?>
                            </select>
                        </div>
					</div>

                    <div class="form-row">
                        <?php
                        $allowedStatuses = Helper::getOption( 'customer_panel_allowed_status', '' );
                        $statusesArray = explode( ',', $allowedStatuses );
                        ?>
                        <div class="form-group col-md-6">
                            <label>&nbsp;</label>
                            <div class="form-control-checkbox">
                                <label for="input_allow_customer_to_change_appointment_status"><?php echo bkntc__('Allow customers to change appointment status')?>:</label>
                                <div class="fs_onoffswitch">
                                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_allow_customer_to_change_appointment_status"<?php echo ! empty( $allowedStatuses )?' checked':''?>>
                                    <label class="fs_onoffswitch-label" for="input_allow_customer_to_change_appointment_status"></label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6" data-hide-key="input_customer_panel_allowed_status">
                            <label for="input_customer_panel_allowed_status"><?php echo bkntc__('Customers can change appointment status to')?>:</label>
                            <select class="form-control" id="input_customer_panel_allowed_status" multiple>
                                <?php foreach ( Helper::getAppointmentStatuses() AS $key => $status ) : ?>
                                    <option value="<?php echo $key ?>"<?php echo in_array($key, $statusesArray) ? ' selected' : ''?>><?php echo $status['title'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

					<div class="form-row">
                        <?php
                        $allowedRescheduleStatuses = Helper::getOption( 'customer_panel_reschedule_allowed_status', '' );
                        $rescheduleStatusesArray = explode( ',', $allowedRescheduleStatuses );
                        ?>
						<div class="form-group col-md-6">
                            <label><br>&nbsp;</label>
                            <div class="form-control-checkbox">
								<label for="input_customer_panel_allow_reschedule"><?php echo bkntc__('Allow customers to reschedule their appointments')?>:</label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_customer_panel_allow_reschedule"<?php echo Helper::getOption('customer_panel_allow_reschedule', 'on')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_customer_panel_allow_reschedule"></label>
								</div>
							</div>
						</div>

                        <div class="form-group col-md-6" data-hide-key="input_customer_panel_reschedule_allowed_status">
                            <label for="input_customer_panel_reschedule_allowed_status"><?php echo bkntc__('Customers can reschedule with these appointment statuses')?>:</label>
                            <select class="form-control" id="input_customer_panel_reschedule_allowed_status" multiple>
                                <?php foreach ( Helper::getAppointmentStatuses() AS $key => $status ) : ?>
                                    <option value="<?php echo $key ?>"<?php echo in_array($key, $rescheduleStatusesArray) ? ' selected' : ''?>><?php echo $status['title'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
					</div>

                    <div class="form-row">
                        <?php if ( ! Helper::isSaaSVersion() ): ?>
                            <div class="form-group col-md-6">
                                <div class="form-control-checkbox">
                                    <label for="input_customer_panel_allow_delete_account"><?php echo bkntc__('Allow customers to delete their account')?>:</label>
                                    <div class="fs_onoffswitch">
                                        <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_customer_panel_allow_delete_account"<?php echo Helper::getOption('customer_panel_allow_delete_account', 'on', false )=='on'?' checked':''?>>
                                        <label class="fs_onoffswitch-label" for="input_customer_panel_allow_delete_account"></label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group col-md-6">
                            <div class="form-control-checkbox">
                                <label for="input_hide_pay_now_btn_customer_panel"><?php echo bkntc__('Hide the "Pay now" button on the Customer Panel')?>:</label>
                                <div class="fs_onoffswitch">
                                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_hide_pay_now_btn_customer_panel"<?php echo Helper::getOption('hide_pay_now_btn_customer_panel', 'off')=='on'?' checked':''?>>
                                    <label class="fs_onoffswitch-label" for="input_hide_pay_now_btn_customer_panel"></label>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>

			</form>

		</div>
	</div>
</div>