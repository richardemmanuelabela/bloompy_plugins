(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'ajax_services.save_service', function ( params )
        {
            var mailblue =  $('.fs-modal #mailblue_service_select').val();
            var mailchimp =  $('.fs-modal #mailchimp_service_select').val();
            params.append('mailblue', mailblue);
            params.append('mailchimp', mailchimp);
            return params;
        });
    });
})(jQuery);
