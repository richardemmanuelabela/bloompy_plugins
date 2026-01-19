(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area').on('click', '.mollie_connect_register_btn', function()
        {
            $(this).replaceWith(`<i class="fa fa-spinner fa-pulse fa-2x connect_loading"></i>`);

            $('#input_mollie_connect_test_mode').on('change', function () {

            });
            if ($('#input_mollie_connect_test_mode').is(':checked')) {
                localStorage.setItem('input_mollie_connect_test_mode', "checked");
            }

            let w = window.open('about:blank', 'bkntc_mollie_connect_window', 'width=800,height=600');

            booknetic.ajax('mollie_connect_settings.generate_register_link', new FormData(), function(result)
            {
                w.location.href = result['url'];
            });
        });
    });

})(jQuery);

function setupCompletedMollieConnect( status, view )
{
    if ( status )
    {
        var container = $('[data-step="mollie_split"]');
        var lastGroup = container.find('.form-group').last();

        lastGroup.html( booknetic.htmlspecialchars_decode( view ) );

        var mollie_connect_test_mode = localStorage.getItem('input_mollie_connect_test_mode');
        if (mollie_connect_test_mode === "checked") {
            $('#input_mollie_connect_test_mode').prop('checked', true);
        }

        $(".settings-save-btn").click();

    }
}