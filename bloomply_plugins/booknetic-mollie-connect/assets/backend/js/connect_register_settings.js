(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area').on('click', '.mollie_connect_verify_btn', function()
        {
            $(this).replaceWith(`<i class="fa fa-spinner fa-pulse fa-2x connect_loading"></i>`);

            let w = window.open('about:blank', 'bkntc_mollie_connect_window', 'width=800,height=600');

            booknetic.ajax('mollie_connect_settings.generate_login_link', new FormData(), function(result)
            {
                if (result['url']) {
                    w.location.href = result['url'];
                }
                else {
                    w.close();
                    alert('Failed to generate login link.');
                }
            });
        });
    });

})(jQuery);

// Callback from the popup window
function setupCompletedMollieConnect(status, view)
{
    if (status)
    {
        $('div[data-step="mollie_split"]').replaceWith(booknetic.htmlspecialchars_decode(view));
    }
}