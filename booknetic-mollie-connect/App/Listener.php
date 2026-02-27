<?php

namespace BookneticAddon\Bloompy\Mollie;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticSaaS\Models\Tenant;
use Mollie\Api\Exceptions\ApiException;

class Listener
{
    public static function saveSettings( $response )
    {
        $testMode = Helper::_post('mollie_connect_test_mode', 'no', 'str');

        Helper::setOption('mollie_connect_test_mode', $testMode);
        Helper::setOption('local_payment_enabled', 'off');
        return $response;
    }

    public static function saveSplitSettings( $response )
    {
        $clientId     = Helper::_post( 'mollie_connect_client_id', '', 'str' );
        $clientSecret = Helper::_post( 'mollie_connect_client_secret', '', 'str' );
        $platformFee  = Helper::_post( 'mollie_connect_platform_fee', '0', 'float' );
        $feeType      = Helper::_post( 'mollie_connect_fee_type', 'price', [ 'price', 'percent' ] );
        $termsPage    = Helper::_post( 'mollie_connect_terms_page', '', 'str' );

        if ( $platformFee < 0 )
            return Helper::response( false, bkntc__( 'Fee cannot be less than 0' ), false );

        if ( $feeType === 'percent' && $platformFee > 100 )
            return Helper::response( false, bkntc__( 'Fee cannot be higher than 100%' ), false );

        if ( PaymentGatewayService::find( 'mollie_split' )->isEnabled() && ( empty($clientId) || empty($clientSecret) || empty($platformFee) || empty($feeType) ) )
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Mollie Connect payment gateway.' ), false );

        Helper::setOption( 'mollie_connect_client_id', $clientId, false );
        Helper::setOption( 'mollie_connect_client_secret', $clientSecret, false );
        Helper::setOption( 'mollie_connect_platform_fee', $platformFee, false );
        Helper::setOption( 'mollie_connect_fee_type', $feeType, false );
        Helper::setOption( 'mollie_connect_terms_page', $termsPage, false );

        return $response;
    }

    public static function disableLocalpayment() {
        Helper::setOption('local_payment_enabled', 'off');
        Helper::setOption('mollie_split_payment_enabled', 'on');
    }

    public static function checkMollieConnectSetupCallback()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $callbackFlag = Helper::_get('bkntc_mollie_connect', '', 'string');
        $authCode     = Helper::_get('code', '', 'string');
        $state        = Helper::_get('state', '', 'string');

        if ($callbackFlag !== 'callback' || empty($authCode) || empty($state)) {
            return;
        }

        if (!isset($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state. Possible CSRF attack.');
        }

        try {
            $tenantInf = MollieConnectHelper::getTenantInf();
            MollieConnectHelper::getInstance()->createAccount($tenantInf);

            // Fetch updated onboarding requirements
            $organization = MollieConnectHelper::getInstance()->retrieveAccount($tenantInf->id);

            $params = [
                'organization_name' => $organization->name ?? '',
                'status' => $organization->status ?? '',
                'pricing' => MollieConnectHelper::getFeeData(),
                'tenantSettings' => MollieConnectHelper::getTenantSettings($tenantInf->id)
            ];

            $successHTML = htmlspecialchars(Helper::renderView(__DIR__ . '/Backend/view/connect/connect_settings.php', $params));

            echo '<script type="text/javascript">
                if(window.opener!==null){ window.opener.setupCompletedMollieConnect(true, `' . $successHTML . '`); window.close(); }
            </script>';
            exit;

        } catch (\Exception $e) {
            error_log('[Mollie Connect Setup] Error: ' . $e->getMessage());
            echo '<script>alert("Mollie Connect setup failed: ' . htmlspecialchars($e->getMessage()) . '"); window.close();</script>';
            exit;
        }
    }

    public static function checkMollieConnectCallback()
    {
        $status    = Helper::_get('bkntc_mollie_split_status', '', 'string');
        $paymentId = Helper::_get('payment_id', '', 'string');
        $tenantId  = Helper::_get('tenant_id', '', 'string');
        $type      = Helper::_get( 'type', '', 'string' );

		//This is for the webhook of recurring automatic payment;
		if (empty($paymentId)) {
			$paymentId = $_POST['id'];
			error_log("mollie auto payment = ".date("Y-m-d h:i:s A").$paymentId);
		}

        if (empty($status) || empty($paymentId) || empty($tenantId)) {
            return;
        }
        error_log('[MOLLIE CALLBACK] Starting 2...');
        
        // Try to get payment with test mode first, then live mode if that fails
        $mollie = new MollieConnectGateway();
        $paymentInf = null;
        
        // Get tenant settings to determine test mode
        $tenantSettings = MollieConnectHelper::getTenantSettings($tenantId);
        $testmode = isset($tenantSettings['testmode']) ? $tenantSettings['testmode'] : false;
        
        // Try with the configured mode first
        $paymentInf = $mollie->check( $paymentId, $testmode );

        // If that fails, try the opposite mode
        if ( empty( $paymentInf ) ) {
            $paymentInf = $mollie->check( $paymentId, !$testmode );
        }
        
        if ( empty( $paymentInf ) || !isset( $paymentInf->metadata ) || !isset( $paymentInf->metadata->payment_id ) ) {
            error_log('[MOLLIE CALLBACK] Payment not found or missing metadata');
            return;
        }
        if ( in_array( $paymentInf->status, [ 'paid', 'authorized' ] ) )
        {
            if ( $paymentInf->status === 'authorized' )
                MollieConnectGateway::shipOrder( $paymentInf );
            if ( $type === 'create_payment_link' )
            {
                $appointmentIds = explode( ',', base64_decode( $paymentInf->metadata->payment_id ) );
                $amountTotal    = $paymentInf->amount->value;
                foreach ( $appointmentIds as $appointmentId )
                {
					$appointment  = Appointment::get($appointmentId);
					$totalAmount  = self::getTotalAmount($appointmentId)->total_amount;

					$isFullyPaid = $totalAmount == $appointment->paid_amount;
					$isPaymentPending   = $appointment->payment_status === 'pending';

					if ($isFullyPaid && $isPaymentPending) {
						// Process failed payments.
						self::resetAmountOneTimePaymentRecurringAppointment($appointment);
						PaymentGatewayService::confirmPayment($appointment->payment_id);
						continue;
					}
					PaymentGatewayService::confirmPaymentLink($appointmentId, $amountTotal, 'mollie');
                }
                $thanksYouPage = Helper::getOption( 'redirect_url_after_booking', '' );
                $redirectUrl   = empty( $thanksYouPage ) ? site_url() : $thanksYouPage;
                echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( true )}window.location.href = "' . $redirectUrl . '"</script>';
            }
            else
            {
				MollieConnectGateway::processMollieSubscription($paymentInf);

                PaymentGatewayService::confirmPayment( $paymentInf->metadata->payment_id );
                echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
            }

        }
        else
        {
            if ( $type !== 'create_payment_link' )
            {
                PaymentGatewayService::cancelPayment( $paymentInf->metadata->payment_id );
            }
            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( false )}window.location.href = "' . site_url() . '"</script>';
        }

        exit;
    }

    public static function checkMolliePaymentCallback()
    {
        $status    = Helper::_get('bkntc_mollie_split_status', '', 'string');
        $paymentId = Helper::_get('payment_id', '', 'string');
        $tenantId  = Helper::_get('tenant_id', '', 'string');
        error_log('[MOLLIE CALLBACK] Starting 1...');
        if (empty($status) || empty($paymentId) || empty($tenantId)) {
            return;
        }
        error_log('[MOLLIE CALLBACK] Starting 2...');
        
        try {
            // Get tenant settings to determine test mode
            $tenantSettings = MollieConnectHelper::getTenantSettings($tenantId);
            $testmode = isset($tenantSettings['testmode']) ? $tenantSettings['testmode'] : false;
            
            $mollie = new MollieConnectGateway();
            $paymentInf = $mollie->check( $paymentId, $testmode );
            
            // If that fails, try the opposite mode
            if ( empty( $paymentInf ) ) {
                $paymentInf = $mollie->check( $paymentId, !$testmode );
            }
            
            if ( empty( $paymentInf ) ) {
                throw new \Exception("Payment not found");
            }
            
            if ( in_array( $paymentInf->status, [ 'paid', 'authorized' ] ) ){
                if ( $paymentInf->status === 'authorized' )
                    MollieConnectGateway::shipOrder( $paymentInf );
                PaymentGatewayService::confirmPayment( $paymentInf->metadata->payment_id );
                echo '<script>if(window.opener){window.opener.bookneticPaymentStatus(true);} window.close();</script>';
            } else {
                error_log('[MOLLIE CALLBACK] Order not paid!');
                if ( isset( $paymentInf->metadata ) && isset( $paymentInf->metadata->payment_id ) ) {
                    PaymentGatewayService::cancelPayment( $paymentInf->metadata->payment_id );
                }
                echo '<script>if(window.opener){window.opener.bookneticPaymentStatus(false);} window.close();</script>';
            }
        } catch (\Throwable $e) {
            error_log('[MOLLIE CALLBACK] Payment verification failed: ' . $e->getMessage());
            echo '<script>alert("Could not verify payment. Please contact support."); window.close();</script>';
        }
        exit;
    }

	public static function getTotalAmount($appointmentId) {
		return AppointmentPrice::where('appointment_id', $appointmentId)
			->select('sum(price * negative_or_positive) as total_amount', true)
			->fetch();
	}
	public static function resetAmountOneTimePaymentRecurringAppointment($appointment)
	{
		if (empty($appointment->recurring_id)) {
			return;
		}
		Appointment::where( 'id', '<>', $appointment->id)
		->where( 'recurring_id', $appointment->recurring_id)
		->where( 'payment_status', 'pending')
		->update(['paid_amount' => 0 ]);
	}
}
