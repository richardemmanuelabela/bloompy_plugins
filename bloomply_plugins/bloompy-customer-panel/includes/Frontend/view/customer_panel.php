<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function Bloompy\CustomerPanel\bkntc__;

/**
 * @var $parameters
 */
$is_valid_customer = $parameters["is_valid_customer"];
$uniqId = uniqid();
?>

<div id="booknetic_progress" class="booknetic_progress_waiting booknetic_progress_done"><dt></dt><dd></dd></div>

<div class="booknetic-body">
    <section id="booknetic-customer-panel">
        <!--header -->
        <div class="booknetic-cp-header">
            <div class="booknetic-cp-header-info">
                <div class="customer-panel-logo">
                    <img src="https://bloompy.nl/wp-content/uploads/2025/05/Laag_cp.png"/>
                </div>
                <div class="header-greetings">
                    <div class="header-greetings-content">
                        Hi, <?php echo htmlspecialchars($parameters['customer']->first_name )?>
                    </div>
                    <div class="header-customer-avatar">
                        <img src="<?php echo Helper::profileImage($parameters['customer']->profile_image, 'Customers')?>" alt="">
                    </div>

                </div>
                <div class="customer-panel-logout">
                    <a href="<?php echo wp_logout_url(); ?>">Uitloggen</a>
                </div>
            </div>

        </div>
        <!-- Section Container -->
        <div class="booknetic-cp-container">
            <!-- Section Content -->

            <div class="booknetic-cp-body">
                <div class="booknetic-cp-tabs">
                    <button class="booknetic-cp-sidebar-toggle" type="button">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                    <button class="booknetic-cp-tab-item active" data-target="#booknetic-tab-appointments" type="button">
                        <i class="fa-solid fa-clock"></i>
                        <div>
                            <span class="booknetic-cp-tab-item-name"><?php echo bkntc__('Appointments')?></span>
                        </div>
                    </button>
                    <button class="booknetic-cp-tab-item" data-target="#booknetic-tab-profile" type="button">
                        <i class="fa-solid fa-user"></i>
                        <div>
                            <span class="booknetic-cp-tab-item-name"><?php echo bkntc__('Profile')?></span>
                        </div>
                    </button>
                    <button class="booknetic-cp-tab-item" data-target="#booknetic-tab-change-password" type="button">
                        <i class="fa-solid fa-key"></i>
                        <div>
                            <span class="booknetic-cp-tab-item-name"><?php echo bkntc__('Change password')?></span>
                        </div>
                    </button>
                </div>
                <div class="booknetic-cp-tab-wrapper">
                    <div class="booknetic-cp-tab show" id="booknetic-tab-appointments">
                        <div class="booknetic-cp-tab-body">
                            <table class="booknetic_data_table booknetic_elegant_table" data-load-appointments="<?php echo $is_valid_customer ?>" id="booknetic_customer_panel_appointments_table">
                                <thead>
                                <tr>

                                    <th class="pl-4 hide-on-mobile "><?php echo bkntc__('ID')?></th>
                                    <th class="cp-appointment-date-mobile cp-appointment-date-mobile-th cp-column-hide-on-desktop" title="<?php echo bkntc__('APPOINTMENT DATE')?>"><?php echo bkntc__('APPOINTMENT DATE')?></th>
                                    <?php if( Helper::isSaaSVersion() ):?>
                                        <th class="cp-appointment-company-mobile"><?php echo bkntc__('Company')?></th>
                                    <?php endif;?>
                                    <th class="hide-on-mobile"><?php echo bkntc__('SERVICE')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('STAFF')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('LOCATIE')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('APPOINTMENT DATE')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('PRICE')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('INVOICE')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('PAYMENT')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('DURATION')?></th>
                                    <th class="hide-on-mobile"><?php echo bkntc__('STATUS')?></th>
                                    <th class="width-100px"><?php echo bkntc__('Link')?></th>
                                    <th class=" cp-column-hide-on-desktop cp-mobile-dropdown-tr"></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="booknetic-cp-tab" id="booknetic-tab-profile">
                        <div class="booknetic-cp-tab-body">
                            <form action="" id="bookentic-cp-user-form">
                                <div class="row">
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_name" class="bookentic-cp-form-label"><?php echo bkntc__('Name')?></label>
                                            <input type="text" class="bookentic-cp-form-control" id="booknetic_input_name" name="name" value="<?php echo htmlspecialchars($parameters['customer']->first_name)?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_surname" class="bookentic-cp-form-label"><?php echo bkntc__('Surname')?></label>
                                            <input type="text" class="bookentic-cp-form-control" id="booknetic_input_surname" name="surname" value="<?php echo htmlspecialchars($parameters['customer']->last_name)?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_email" class="bookentic-cp-form-label"><?php echo bkntc__('Email')?></label>
                                            <input type="email" class="bookentic-cp-form-control" id="booknetic_input_email" name="email" value="<?php echo htmlspecialchars($parameters['customer']->email)?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_phone" class="bookentic-cp-form-label"><?php echo bkntc__('Phone')?></label>
                                            <input type="tel" class="bookentic-cp-form-control" id="booknetic_input_phone" name="phone" value="<?php echo htmlspecialchars($parameters['customer']->phone_number)?>" data-country-code="<?php echo Helper::getOption('default_phone_country_code', '', $parameters['customer']->tenant_id)?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item" id="bookentic-calendar">
                                            <label for="booknetic_input_birthdate" class="bookentic-cp-form-label"><?php echo bkntc__('Date of birth')?></label>
                                            <input type="text" class="bookentic-cp-form-control flatpickr-input date-picker" id="booknetic_input_birthdate"  name="birthdate" value="<?php echo htmlspecialchars($parameters['customer']->birthdate)?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label for="booknetic_input_gender" class="bookentic-cp-form-label"><?php echo bkntc__('Gender')?></label>
                                        <select id="booknetic_input_gender" class="bookentic-cp-form-control" name="gender">
                                            <option value="male"<?php echo $parameters['customer']->gender == 'male' ? ' selected' : ''?>><?php echo bkntc__('Male')?></option>
                                            <option value="female"<?php echo $parameters['customer']->gender == 'female' ? ' selected' : ''?>><?php echo bkntc__('Female')?></option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                            <div class="booknetic-cp-tab-footer">
                                <div class="booknetic-cp-tab-footer-left">
                                        <button class="booknetic-profile-save" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> id="booknetic_profile_save"><?php echo bkntc__('SAVE')?></button>
                                </div>
                                <div class="booknetic-cp-tab-footer-left">
                                    <?php if( Helper::getOption('customer_panel_allow_delete_account', 'on', false ) == 'on' ): ?>
                                        <button type="button" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> class="booknetic-profile-delete" id="booknetic_profile_delete"><?php echo bkntc__('DELETE MY PROFILE')?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                    </div>
                    <div class="booknetic-cp-tab" id="booknetic-tab-change-password">
                        <div class="booknetic-cp-tab-body">
                            <form action="" id="booknetic_tab_change_password">
                                <div class="row">
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_old_password" class="bookentic-cp-form-label"><?php echo bkntc__('Current password')?></label>
                                            <input type="password" class="bookentic-cp-form-control" id="booknetic_input_old_password" name="old_password" placeholder="*****">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_new_password" class="bookentic-cp-form-label"><?php echo bkntc__('New password')?></label>
                                            <input type="password" class="bookentic-cp-form-control" id="booknetic_input_new_password" name="new_password" placeholder="*****">
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="bookentic-cp-user-form-item">
                                            <label for="booknetic_input_repeat_new_password" class="bookentic-cp-form-label"><?php echo bkntc__('Repeat new password')?></label>
                                            <input type="password" class="bookentic-cp-form-control" id="booknetic_input_repeat_new_password" name="repeat_new_password" placeholder="*****">
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>
                        <div class="booknetic-cp-tab-footer">
                            <div class="booknetic-cp-tab-footer-left">
                                <button type="button" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> class="booknetic-profile-save" id="booknetic_change_password_save"><?php echo bkntc__('CHANGE PASSWORD')?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="booknetic_cp_delete_profile_popup" class="booknetic_popup booknetic_hidden">
        <div class="booknetic_popup_body">
            <div class="booknetic_cp_cancel_icon">
                <div><img src="<?php echo Helper::assets( 'icons/trash.svg' )?>"></div>
            </div>
            <div class="booknetic_cancel_popup_body">
                <?php echo bkntc__('Are you sure you want to delete your profile?')?>
            </div>
            <div class="booknetic_reschedule_popup_footer">
                <button class="booknetic_btn_secondary booknetic_cancel_popup_no" type="button" data-dismiss="modal"><?php echo bkntc__('NO')?></button>
                <button class="booknetic_btn_danger booknetic_delete_profile_popup_yes" type="button"><?php echo bkntc__('YES')?></button>
            </div>
        </div>
    </div>

    <div id="booknetic_cp_cancel_popup" class="booknetic_popup booknetic_hidden">
        <div class="booknetic_popup_body">
            <div class="booknetic_cp_cancel_icon">
                <div><img src="<?php echo Helper::assets( 'icons/trash.svg' )?>"></div>
            </div>
            <div class="booknetic_cancel_popup_body">
                <?php echo bkntc__('Are you sure you want to change appointment status to?')?>
            </div>
            <div class="booknetic_reschedule_popup_footer">
                <button class="booknetic_btn_secondary booknetic_cancel_popup_no" type="button" data-dismiss="modal"><?php echo bkntc__('NO')?></button>
                <button class="booknetic_btn_danger booknetic_cancel_popup_yes" type="button"><?php echo bkntc__('YES')?></button>
            </div>
        </div>
    </div>

    <div id="booknetic_cp_reschedule_popup" class="booknetic_popup booknetic_hidden">
        <div class="booknetic_popup_body">
            <div class="booknetic_cp_reschedule_icon">
                <img src="<?php echo Helper::assets('icons/reschedule.svg', 'front-end')?>">
            </div>
            <div class="booknetic_reschedule_popup_body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="booknetic_reschedule_popup_date"><?php echo bkntc__('Date')?></label>
                        <input id="booknetic_reschedule_popup_date" type="text" class="form-control">
                    </div>
                    <div class="form-group col-md-6" id="booknetic_reschedule_popup_time_area">
                        <label for="<?php echo $uniqId . '_1' ?>"><?php echo bkntc__('Time')?></label>
                        <select id="<?php echo $uniqId . '_1' ?>" class="form-control booknetic_reschedule_popup_time"></select>
                    </div>
                </div>
            </div>
            <div class="booknetic_reschedule_popup_footer">
                <button class="booknetic_btn_secondary booknetic_reschedule_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
                <button class="booknetic_btn_danger booknetic_reschedule_popup_confirm" type="button"><?php echo bkntc__('RESCHEDULE')?></button>
            </div>
        </div>
    </div>

    <div id="booknetic_cp_change_status_popup" class="booknetic_popup booknetic_hidden">
        <div class="booknetic_popup_body">
            <div class="booknetic_cp_reschedule_icon">
                <img src="<?php echo Helper::assets('icons/reschedule.svg', 'front-end')?>">
            </div>
            <div class="booknetic_change_status_popup_body">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="<?php echo $uniqId . '_2' ?>"><?php echo bkntc__('Select Status')?></label>
                        <select id="<?php echo $uniqId . '_2' ?>" class="form-control booknetic_change_status_popup_select"></select>
                    </div>
                </div>
            </div>
            <div class="booknetic_reschedule_popup_footer">
                <button class="booknetic_btn_secondary booknetic_reschedule_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
                <button class="booknetic_btn_danger booknetic_change_status_popup_confirm" type="button"><?php echo bkntc__('SAVE')?></button>
            </div>
        </div>
    </div>


    <div id="booknetic_cp_pay_now_popup" class="booknetic_popup booknetic_hidden">
        <div class="booknetic_popup_body">
           <div class="booknetic_pay_now_popup_body">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="<?php echo $uniqId . '_3' ?>"><?php echo bkntc__('Select Payment Gateway')?></label>
                        <select id="<?php echo $uniqId . '_3' ?>" class="booknetic_pay_now_popup_select form-control"></select>
                    </div>
                </div>
            </div>
            <div class="booknetic_reschedule_popup_footer">
                <button class="booknetic_btn_secondary booknetic_pay_now_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
                <button class="booknetic_btn_danger booknetic_pay_now_popup_confirm" type="button"><?php echo bkntc__('Pay')?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .rtl .iti__country-list {
        left:0;
    }

    /*.iti__flag {*/
    /*    background-image: url("*/<?php //echo \Bloompy\CustomerPanel\CustomerPanelAddon::loadAsset('assets/frontend/img/flags.png')?>/*");*/
    /*}*/

    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .iti__flag {
            background-image: url("<?php echo \Bloompy\CustomerPanel\CustomerPanelAddon::loadAsset('assets/frontend/img/flags@2x.png')?>");
        }
    }
</style>
