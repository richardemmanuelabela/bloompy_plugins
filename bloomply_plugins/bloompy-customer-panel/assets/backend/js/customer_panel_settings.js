(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        var fadeSpeed = 0;

        $('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
        {
            var customer_panel_page_id					            = $("#input_customer_panel_page_id").select2('val'),
                customer_panel_allow_reschedule			            = $("#input_customer_panel_allow_reschedule").is(':checked') ? 'on' : 'off',
                customer_panel_allow_delete_account		            = $("#input_customer_panel_allow_delete_account").is(':checked') ? 'on' : 'off',
                customer_panel_enable					            = $('input[name="input_customer_panel_enable"]:checked').val(),
                time_restriction_to_make_changes_on_appointments 	= $("#input_time_restriction_to_make_changes_on_appointments").val(),
                allow_customer_to_change_appointment_status 	    = $("#input_allow_customer_to_change_appointment_status").is(':checked') ? 'on' : 'off',
                hide_pay_now_btn_customer_panel 	                = $("#input_hide_pay_now_btn_customer_panel").is(':checked') ? 'on' : 'off',
                customer_panel_allowed_status                       = $("#input_customer_panel_allowed_status").select2('val'),
                customer_panel_reschedule_allowed_status            = $("#input_customer_panel_reschedule_allowed_status").select2('val');


            var data = new FormData();

            data.append('customer_panel_enable' , customer_panel_enable);
            data.append('customer_panel_page_id' , customer_panel_page_id);
            data.append('customer_panel_allow_reschedule' , customer_panel_allow_reschedule);
            data.append('customer_panel_allow_delete_account' , customer_panel_allow_delete_account);
            data.append('time_restriction_to_make_changes_on_appointments' , time_restriction_to_make_changes_on_appointments);
            data.append('allow_customer_to_change_appointment_status', allow_customer_to_change_appointment_status);
            data.append('hide_pay_now_btn_customer_panel', hide_pay_now_btn_customer_panel);
            data.append('customer_panel_allowed_status' , customer_panel_allowed_status);
            data.append('customer_panel_reschedule_allowed_status' , customer_panel_reschedule_allowed_status);

            booknetic.ajax('customerpanel.save_customer_panel_settings', data, function ()
            {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        }).on('change', 'input[name="input_customer_panel_enable"]', function()
        {
            if( $('input[name="input_customer_panel_enable"]:checked').val() == 'on' )
            {
                $('#customer_panel_settings_area').slideDown(fadeSpeed);
            }
            else
            {
                $('#customer_panel_settings_area').slideUp(fadeSpeed);
            }
        });

        $('input[name="input_customer_panel_enable"]').trigger('change');

        fadeSpeed = 400;

        $("#input_customer_panel_page_id").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select')
        });

        $("#input_customer_panel_allowed_status").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: true
        });

        $("#input_customer_panel_reschedule_allowed_status").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: true
        });

        $('#input_allow_customer_to_change_appointment_status').on('change', function ()
        {
            if( $(this).is(':checked') )
            {
                $('div[data-hide-key="input_customer_panel_allowed_status"]').fadeIn(200);
            }
            else
            {
                $('div[data-hide-key="input_customer_panel_allowed_status"]').fadeOut(200);
            }
        }).trigger('change');

        $('#input_customer_panel_allow_reschedule').on('change', function ()
        {
            if( $(this).is(':checked') )
            {
                $('div[data-hide-key="input_customer_panel_reschedule_allowed_status"]').fadeIn(200);
            }
            else
            {
                $('div[data-hide-key="input_customer_panel_reschedule_allowed_status"]').fadeOut(200);
            }
        }).trigger('change');

    });

})(jQuery);