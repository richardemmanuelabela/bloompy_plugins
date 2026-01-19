<?php

namespace BookneticAddon\BloompyTenants\Backend;

use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	/**
	 * Display tenant info settings view
	 */
	public function bloompy_tenant_info_settings()
	{
		$user = wp_get_current_user();
		$tenant = Tenant::where('user_id', $user->ID)->fetch();
		$tenantId = $tenant->id;

		$tenantInf = [];
		if( $tenantId > 0 ) {
			$tenantInf["company_name"] = get_option( 'bkntc_t'.$tenantId.'_company_name' );
			$tenantInf["tenant_company_name"] = Tenant::getData( $tenantId, "tenant_company_name" );
			$tenantInf["footer_first_column"] = Tenant::getData( $tenantId, "footer_first_column" );
			$tenantInf["footer_second_column"] = Tenant::getData( $tenantId, "footer_second_column" );
			$tenantInf["footer_third_column"] = Tenant::getData( $tenantId, "footer_third_column" );
			$tenantInf["footer_fourth_column"] = Tenant::getData( $tenantId, "footer_fourth_column" );
			$tenantInf["privacy_policy_url"] = Tenant::getData( $tenantId, "privacy_policy_url" );
			$tenantInf["terms_conditions_url"] = Tenant::getData( $tenantId, "terms_conditions_url" );
		} else {
			$tenantInf = [
				'id' => $tenantId,
				'info' => [],
			];
		}

		$companyName = ( empty($tenantInf['tenant_company_name']) ) ? $tenantInf['company_name'] : $tenantInf['tenant_company_name'];

		// Prepare parameters for the view
		$parameters = [
			'id' => $tenantId,
			'info' => $tenantInf,
			'company_name' => $companyName
		];

		// Use output buffering to capture the view content
		ob_start();
		include __DIR__ . '/view/tenant_info_settings.php';
		$html = ob_get_clean();

		return Helper::response(true, ['html' => $html]);
	}

	/**
	 * Save tenant info settings
	 */
	public function bloompy_tenant_info_settings_save()
	{
		$tenantId = Helper::_post('tenant_id', '0', 'integer');

		$tenant_company_name = Helper::_post('tenant_company_name', '', 'string');
		Tenant::setData($tenantId, "tenant_company_name", $tenant_company_name);

		$footer_first_column = Helper::_post('footer_first_column', '', 'string');
		Tenant::setData($tenantId, "footer_first_column", $footer_first_column);

		$footer_second_column = Helper::_post('footer_second_column', '', 'string');
		Tenant::setData($tenantId, "footer_second_column", $footer_second_column);

		$footer_third_column = Helper::_post('footer_third_column', '', 'string');
		Tenant::setData($tenantId, "footer_third_column", $footer_third_column);

		$footer_fourth_column = Helper::_post('footer_fourth_column', '', 'string');
		Tenant::setData($tenantId, "footer_fourth_column", $footer_fourth_column);

		$privacy_policy_url = Helper::_post('privacy_policy_url', '', 'string');
		Tenant::setData($tenantId, "privacy_policy_url", $privacy_policy_url);

		$terms_conditions_url = Helper::_post('terms_conditions_url', '', 'string');
		Tenant::setData($tenantId, "terms_conditions_url", $terms_conditions_url);

		return $this->response(true, [
			'id' => $tenantId
		]);
	}

	public function save()
	{
		$tenantId			=	Helper::_post('tenant_id', '0', 'integer');

		$tenant_company_name =	Helper::_post('tenant_company_name', '', 'string');
		Tenant::setData($tenantId, "tenant_company_name", $tenant_company_name);

		$footer_first_column =	Helper::_post('footer_first_column', '', 'string');
		Tenant::setData($tenantId, "footer_first_column", $footer_first_column);

		$footer_second_column =	Helper::_post('footer_second_column', '', 'string');
		Tenant::setData($tenantId, "footer_second_column", $footer_second_column);

		$footer_third_column =	Helper::_post('footer_third_column', '', 'string');
		Tenant::setData($tenantId, "footer_third_column", $footer_third_column);

		$footer_fourth_column =	Helper::_post('footer_fourth_column', '', 'string');
		Tenant::setData($tenantId, "footer_fourth_column", $footer_fourth_column);

		$privacy_policy_url =	Helper::_post('privacy_policy_url', '', 'string');
		Tenant::setData($tenantId, "privacy_policy_url", $privacy_policy_url);

		$terms_conditions_url =	Helper::_post('terms_conditions_url', '', 'string');
		Tenant::setData($tenantId, "terms_conditions_url", $terms_conditions_url);

//		$tenant_footer_text		=	Helper::_post('tenant_footer_text', '', 'string');
//		Tenant::setData($tenantId, "tenant_footer_text", $tenant_footer_text);


		return $this->response(true, [
			'id'	=>	$tenantId
		]);
	}

}
