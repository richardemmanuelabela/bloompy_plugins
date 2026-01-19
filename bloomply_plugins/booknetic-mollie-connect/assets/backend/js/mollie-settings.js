(function ( $ )
{
    'use strict';

    $( document ).ready( function ()
    {
        booknetic.addFilter( 'ajax_settings.save_payment_gateways_settings', function ( params )
        {
            params.append('mollie_client_id', $( '#input_mollie_client_id' ).val())
            params.append('mollie_client_secret', $( '#input_mollie_client_secret' ).val())

            let testMode = $('input[name="input_mollie_connect_test_mode"]:checked').val() || 'no';
            params.append('mollie_connect_test_mode', testMode);


            return params;
        } );
    } );
} )( jQuery );