<?php

namespace BookneticAddon\Newsletters;

use BookneticAddon\Newsletters\Backend\Controller;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\UI\TabUI;
use BookneticAddon\Newsletters\Backend\Ajax;
use BookneticApp\Providers\Core\Route;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, 'booknetic-newsletters' );
}

class NewslettersAddon extends AddonLoader
{
    public function init()
    {
        // Register capability for the newsletter menu (optional, for future use)
        Capabilities::register('newsletters', bkntc__('Newsletters'));
        add_action('bkntc_appointment_created', [self::class, 'subscribeCustomerToNewsletters'], 20, 1);
    }

    public function initBackend()
    {
        if( Capabilities::userCan('newsletters') )
        {
            \BookneticApp\Providers\Core\Route::get('newsletters', new \BookneticAddon\Newsletters\Backend\Controller());
            \BookneticApp\Providers\UI\MenuUI::get('newsletters')
                ->setTitle( bkntc__('Newsletters') )
                ->setIcon('fa fa-envelope-open-text')
                ->setPriority(950);

//            TabUI::get( 'services_add' )
//                ->item( 'details' )
//                ->addView( __DIR__ . '/Backend/view/newsletter_assignment_link.php', [ self::class, 'add_newsletter_assignment_to_service_view' ]);

			TabUI::get( 'services_add' )
				->item( 'details' )
				->addView( __DIR__ . '/Backend/view/tab/newsletter-mailblue-services.php', [ Controller::class, 'add_mailblue_row_to_service_view' ]);
			TabUI::get( 'services_add' )
				->item( 'details' )
				->addView( __DIR__ . '/Backend/view/tab/newsletter-mailchimp-services.php', [ Controller::class, 'add_mailchimp_row_to_service_view' ]);
			Route::post('newsletters', new Ajax() );
			add_action('bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2);
			add_filter('bkntc_after_request_services_save_service', [ Controller::class, 'mailblue_data_save_service' ], 1, 1);
			add_filter('bkntc_after_request_services_save_service', [ Controller::class, 'mailchimp_data_save_service' ], 1, 1);
        }
    }
	public static function enqueueAssets($module, $action)
	{
		if ($module == 'services' && $action == 'add_new') {
			echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/newsletter_service.js') . '"></script>';
		}
	}

    public static function add_newsletter_assignment_to_service_view($parameters)
    {
        $serviceId = $parameters['service']['id'] ?? 0;
        return [ 'current_service_id' => $serviceId ];
    }

    public static function subscribeCustomerToNewsletters($appointmentData)
    {
        $serviceId = $appointmentData->serviceId ?? null;
        $customerEmail = $appointmentData->customerDataObj->email ?? '';
        $customerName = trim(($appointmentData->customerDataObj->first_name ?? '') . ' ' . ($appointmentData->customerDataObj->last_name ?? ''));
        $customerPhone = $appointmentData->customerDataObj->phone ?? '';
        if (!$serviceId || !$customerEmail) return;
        \BookneticAddon\Newsletters\NewsletterManager::subscribeAllForService($serviceId, $customerEmail, $customerName, $customerPhone);
    }
} 