(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        alert();
        console.log("test");
        const serviceId = $("#add_new_JS").data('service-id');
        $('.fs-modal').on('change', '#automatic_recurring_payment_switch', function ()
        {

            if( $(this).is(':checked') )
            {
                $(".fs-modal [data-for='automatic_recurring_payment']").slideDown( $(this).data('slideSpeed') || 0 );
            }
            else
            {
                $(".fs-modal [data-for='automatic_recurring_payment']").slideUp( $(this).data('slideSpeed') || 0 );
            }

            $(this).data('slideSpeed', 200);
            var test = $("#automatic_recurring_payment_switch").val();
            console.log(test);

        });
    });

})(jQuery);