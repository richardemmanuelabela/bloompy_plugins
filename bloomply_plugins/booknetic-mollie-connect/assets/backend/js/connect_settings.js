(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area')
            .on('click', '.mollie_connect_revoke_btn', function()
            {
                if (!confirm('Are you sure you want to revoke the Mollie connection?')) {
                    return;
                }

                booknetic.ajax('mollie_connect_settings.revoke_token', new FormData, function(response)
                {
                    if (response.status === 'ok') {
                        location.reload(); // or update UI with AJAX
                    }
                    else {
                        alert(response.error_msg || 'Something went wrong.');
                    }
                });
            });

    });

})(jQuery);
