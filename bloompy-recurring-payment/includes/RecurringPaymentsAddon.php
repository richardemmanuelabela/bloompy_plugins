<?php
namespace Bloompy\RecurringPayments;

use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\TabUI;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, 'bloompy-recurring-payments' );
}

class RecurringPaymentsAddon extends AddonLoader
{
	public function init()
	{
		Capabilities::register('recurring_payments', bkntc__('Recurring Payments'));
	}
	public function initBackend()
	{
		TabUI::get( 'services_add' )
			->item( 'details' )
			->addView( __DIR__ . '/Backend/view/tab/service_recurring_payment_switch.php', [ \Bloompy\RecurringPayments\Backend\Controller::class, 'add_automatic_recurring_payment_switch_row_to_service_view' ]);
		
		// Hook into service save to save checkbox value
		add_filter('bkntc_after_request_services_save_service', [ \Bloompy\RecurringPayments\Backend\Controller::class, 'save_automatic_recurring_payment_switch' ], 1, 1);
		add_action('bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2);
	}
	public function initFrontend ()
	{
		//add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ]);
		//$this->setFrontendAjaxController( Frontend\Ajax::class );
	}
	public static function enqueueAssets($module, $action)
	{
		if ($module == 'services' && ($action == 'add_new' || $action == 'edit')) {
			echo '<script type="application/javascript" src="' . esc_url(self::loadAsset('assets/backend/js/recurring_payment_service.js')) . '"></script>';
		}
	}




}

