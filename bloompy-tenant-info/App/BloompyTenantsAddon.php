<?php
namespace BookneticAddon\BloompyTenants;

use BookneticApp\Config;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticSaaS\Models\Tenant;
use BookneticAddon\BloompyTenants\Frontend;
use BookneticAddon\BloompyTenants\Backend\Ajax;
use BookneticAddon\BloompyTenants\Backend\Controller;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, 'bloompy-tenant-info' );
}

class BloompyTenantsAddon extends AddonLoader
{
	public function init()
	{
		//Templates Addon Hooks
		add_action( 'bkntc_template_base_fields', [ Listener::class, 'setTemplateField' ] );
		add_action( 'bkntc_template_field_labels', [ Listener::class, 'setTemplateFieldLabel' ] );
		Capabilities::register('bloompy_tenants' , bkntc__('Booking Page Info') );

		Config::getShortCodeService()->addReplacer([ Listener::class , 'replaceShortCodes' ]);

		Config::getShortCodeService()->registerShortCodesLazily([ Listener::class, 'registerShortCodes' ]);
	}
	public function initBackend()
	{
		if( ! Capabilities::tenantCan( 'bloompy_tenants' ) )
			return;
		
		// Register settings routes
		Route::post('bloompy_tenant_info_settings', Ajax::class, ['bloompy_tenant_info_settings', 'bloompy_tenant_info_settings_save']);

        $icon = BLOOMPY_TENANT_INFO_PLUGIN_URL . '/assets/backend/images/bookingpage_information_settings.svg';

		// Register with Booknetic settings using SettingsMenuUI
		SettingsMenuUI::get('bloompy_tenant_info_settings', 'bloompy_tenant_info_settings')
			->setTitle(bkntc__('Booking Page Info'))
			->setDescription(bkntc__('Configure booking page information and footer content'))
			->setIcon($icon)
			->setPriority(15);

		SettingsMenuUI::get('bloompy_tenant_info_settings', 'bloompy_tenant_info_settings')
			->subItem( 'bloompy_tenant_info_settings', 'bloompy_tenant_info_settings' )
			->setTitle( bkntc__( 'Basic Info' ) )
			->setPriority( 1 );
	}
	public function initFrontend ()
	{
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ]);
		$this->setFrontendAjaxController( Frontend\Ajax::class );
	}
	function frontend_scripts() {

		wp_enqueue_script('tenant-info', BloompyTenantsAddon::loadAsset('assets/frontend/js/tenant-info.js'), array('jquery'), null, true );
		wp_localize_script( 'tenant-info', 'BloompyDataTenantInfo', [
			'ajax_url'		    => admin_url( 'admin-ajax.php' ),
			'assets_url'	    => Helper::assets('/', 'front-end') ,
			'date_format'	    => Helper::getOption('date_format', 'Y-m-d'),
			'week_starts_on'    => Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday',
			'client_timezone'   => htmlspecialchars(Helper::getOption('client_timezone_enable', 'off')),
			'tz_offset_param'   => htmlspecialchars(Helper::_get('client_time_zone', '-', 'str')),
			'localization'      => [
				// months
				'January'               => bkntc__('January'),
				'February'              => bkntc__('February'),
				'March'                 => bkntc__('March'),
				'April'                 => bkntc__('April'),
				'May'                   => bkntc__('May'),
				'June'                  => bkntc__('June'),
				'July'                  => bkntc__('July'),
				'August'                => bkntc__('August'),
				'September'             => bkntc__('September'),
				'October'               => bkntc__('October'),
				'November'              => bkntc__('November'),
				'December'              => bkntc__('December'),

				//days of week
				'Mon'                   => bkntc__('Mon'),
				'Tue'                   => bkntc__('Tue'),
				'Wed'                   => bkntc__('Wed'),
				'Thu'                   => bkntc__('Thu'),
				'Fri'                   => bkntc__('Fri'),
				'Sat'                   => bkntc__('Sat'),
				'Sun'                   => bkntc__('Sun'),

				// select placeholders
				'select'                => bkntc__('Select...'),
				'searching'				=> bkntc__('Searching...'),
			]
		]);
	}





}