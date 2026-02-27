<?php
namespace BookneticAddon\BloompyTenants\Backend;

use BookneticApp\Config;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticSaaS\Models\Tenant;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;

class Controller extends \BookneticApp\Providers\Core\Controller
{
	private ShortCodeService $shortCodeService;
	private DataTableUI $dataTable;

	public function __construct()
	{
		$this->shortCodeService = Config::getShortCodeService();
	}

	public function index()
	{
		$user = wp_get_current_user();
		$tenant = Tenant::where('user_id', $user->ID)->fetch();
		$tenantId= $tenant->id;

		if( $tenantId > 0 )
		{
			$tenantInf = [];
			$tenantInf["company_name"] = get_option( 'bkntc_t'.$tenantId.'_company_name' );
			$tenantInf["tenant_company_name"] = Tenant::getData( $tenantId, "tenant_company_name" );
			$tenantInf["footer_first_column"] = Tenant::getData( $tenantId, "footer_first_column" );
			$tenantInf["footer_second_column"] = Tenant::getData( $tenantId, "footer_second_column" );
			$tenantInf["footer_third_column"] = Tenant::getData( $tenantId, "footer_third_column" );
			$tenantInf["footer_fourth_column"] = Tenant::getData( $tenantId, "footer_fourth_column" );
			$tenantInf["privacy_policy_url"] = Tenant::getData( $tenantId, "privacy_policy_url" );
			$tenantInf["terms_conditions_url"] = Tenant::getData( $tenantId, "terms_conditions_url" );
		} else {
			Capabilities::must('tenant_info_add');

			$tenantInf	= [
				'id'		        =>	$tenantId,
				'info'		        =>	[],
			];
		}

		$this->view( 'index', [
			'id'		        =>	$tenantId,
			'info'		        =>	$tenantInf
		] );
	}
}