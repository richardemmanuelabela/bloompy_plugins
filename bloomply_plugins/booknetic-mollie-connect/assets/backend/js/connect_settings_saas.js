(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        booknetic.addFilter( 'ajax_settings.save_payment_split_payments_settings', function ( params )
        {
            params[ 'mollie_connect_client_id' ]         = $("#input_mollie_connect_client_id").val()
            params[ 'mollie_connect_client_secret' ]     = $("#input_mollie_connect_client_secret").val()
            params[ 'mollie_connect_platform_fee' ]      = $("#input_mollie_connect_platform_fee").val()
            params[ 'mollie_connect_fee_type' ]          = $("#input_mollie_connect_fee_type").val()
            params[ 'mollie_connect_terms_page' ]        = $("#input_mollie_connect_terms_page").val()

            return params;
        } );

        $("#input_mollie_connect_fee_type").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: false,
        });

        $('#manage_connected_tenants').on( 'click', function()
        {
            booknetic.ajax( 'mollie_connect_settings.connected_tenants_saas', {}, function(res) {
                booknetic.modal(booknetic.htmlspecialchars_decode(res.html), { 'type' : 'center', 'width': '650px' })
            })
        })

    });

})(jQuery);
