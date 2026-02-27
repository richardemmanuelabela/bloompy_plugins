var bookneticmolliePaymentCompletedFn;

(function($)
{
    "use strict";

    $(document).ready(function()
    {
        let paymentWindow;
        let paymentWindowController;
        let paymentLinkExpirationController;
        let paymentData;

        const mollieLoading = function ( status )
        {
            if( status )
            {
                let loadingEl = document.createElement('div');
                loadingEl.className = 'booknetic_mollie_loading';
                loadingEl.style.position = 'fixed';
                loadingEl.style.top = 0;
                loadingEl.style.left = 0;
                loadingEl.style.width = '100%';
                loadingEl.style.height = '100%';
                loadingEl.style.background = 'rgba(0,0,0,0.7)';
                loadingEl.style.color = '#FFF';
                loadingEl.style.display = 'flex';
                loadingEl.style.alignItems = 'center';
                loadingEl.style.justifyContent = 'center';
                loadingEl.style.zIndex = 9999;

                // doit texti duzeltmek ve tercumeoluna bilen olsun.
                loadingEl.innerHTML = 'Please process the payment...';

                $('body').append( $(loadingEl) );
            }
            else
            {
                $('.booknetic_mollie_loading').remove();
            }
        };

        const waitPaymentFinish = function()
        {
            if( paymentWindow.closed )
            {
                paymentCompleted( false );
                return;
            }

            paymentWindowController = setTimeout( waitPaymentFinish, 1000 );
        };

        const paymentCompleted = function ( status )
        {
            mollieLoading(false);

            if( ! paymentWindow.closed )
                paymentWindow.close();

            clearTimeout( paymentWindowController );

            bookneticHooks.doAction('payment_completed', status, paymentData);
        };

        bookneticHooks.addAction( 'before_processing_payment', function ( payment_method, data )
        {
            if( payment_method !== 'mollie' )
                return;

            paymentData = data;

            paymentLinkExpirationController && clearTimeout( paymentLinkExpirationController );

            paymentWindow = window.open( '', 'booknetic_payment_window', 'width=1000,height=700' );
            waitPaymentFinish();

            bookneticmolliePaymentCompletedFn = paymentCompleted;
        });

        bookneticHooks.addAction( 'after_processing_payment', function( payment_method, process_status, data )
        {
            if( payment_method !== 'mollie' )
                return;

            if( ! process_status )
            {
                paymentWindow.close();
                return;
            }

            if( ! paymentWindow.closed )
            {
                mollieLoading( true );

                paymentWindow.location.href = data['url'];

                if ( typeof data["payment_link_expiration_time"] !== undefined )
                {
                    paymentLinkExpirationController = setTimeout(() =>
                    {
                        paymentWindow.close()
                    }, data["payment_link_expiration_time"] * 1000)
                }

                return;
            }
        });



        // old version
        bookneticHooks.addAction( 'ajax_before_confirm', function( params, booknetic )
        {
            if( params.get('payment_method') == 'mollie' )
            {
                bookneticPaymentStatus = booknetic.paymentFinished;
                booknetic.paymentWindow = window.open( '', 'booknetic_payment_window', 'width=1000,height=700' );
                booknetic.waitPaymentFinish();
            }
        });


        bookneticHooks.addAction( 'ajax_after_confirm_success', function( booknetic, data, result )
        {
            if( data.get('payment_method') == 'mollie' )
            {
                if( result['status'] == 'error' )
                {
                    booknetic.toast( result['error_msg'], 'unsuccess' );
                    booknetic.paymentWindow.close();
                    return;
                }

                if( !booknetic.paymentWindow.closed )
                {
                    booknetic.loading(1);
                    booknetic.paymentWindow.location.href = result['url'];
                    return;
                }

            }

        });


        bookneticHooks.addAction( 'ajax_after_confirm_error', function( booknetic, data, result )
        {
            if( data.get('payment_method') == 'mollie' )
            {
                booknetic.paymentWindow.close();
            }
        });


    });

})(jQuery);