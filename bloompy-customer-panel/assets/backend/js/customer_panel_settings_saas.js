(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        var fadeSpeed = 0;

        $('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
        {
            var customer_panel_page_id					= $("#input_customer_panel_page_id").select2('val'),
                customer_panel_enable					= $('input[name="input_customer_panel_enable"]:checked').val(),
                customer_panel_allow_delete_account		= $("#input_customer_panel_allow_delete_account").is(':checked') ? 'on' : 'off';

            booknetic.ajax('customerpanel.save_customer_panel_settings_saas', {
                customer_panel_enable: customer_panel_enable,
                customer_panel_page_id: customer_panel_page_id,
                customer_panel_allow_delete_account: customer_panel_allow_delete_account
            }, function ()
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

    });

})(jQuery);