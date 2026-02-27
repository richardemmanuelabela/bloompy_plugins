(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'ajax_services.save_service', function ( params )
        {
            var automaticRecurringPaymentSwitch = $('.fs-modal #automatic_recurring_payment_switch').is(':checked') ? '1' : '0';
            params.append('automatic_recurring_payment_switch', automaticRecurringPaymentSwitch);
            return params;
        });
    });
})(jQuery);
