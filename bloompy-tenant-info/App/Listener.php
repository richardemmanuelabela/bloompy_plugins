<?php

namespace BookneticAddon\BloompyTenants;

use BookneticApp\Providers\Core\Templates\Applier;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\BloompyTenants\bkntc__;
use BookneticAddon\Customerpanel\CustomerPanelHelper;
class Listener
{
	public static function registerShortCodes($shortCodeService)
	{
		$shortCodeService->registerShortCode( 'bloompy_tenant_company_name', [
			'name'      =>  bkntc__('Bloompy company name'),
			'category'  =>  'appointment_info',
			'depends'   =>  'appointment_id'
		] );

		$shortCodeService->registerShortCode( 'bloompy_tenant_footer_text', [
			'name'      =>  bkntc__('Bloompy footer text'),
			'category'  =>  'appointment_info',
			'depends'   =>  'appointment_id'
		] );

		$shortCodeService->registerShortCode( 'bloompy_tenant_privacy_policy_url', [
			'name'      =>  bkntc__('Bloompy privacy policy URL'),
			'category'  =>  'appointment_info',
			'depends'   =>  'appointment_id'
		] );

		$shortCodeService->registerShortCode( 'bloompy_tenant_terms_conditions_url', [
			'name'      =>  bkntc__('Bloompy terms & conditions URL'),
			'category'  =>  'appointment_info',
			'depends'   =>  'appointment_id'
		] );
	}
	public static function replaceShortCodes( $text, $data, $shortCodeService )
	{
		// Handle privacy policy URL shortcode
		if (strpos($text, '{bloompy_tenant_privacy_policy_url}') !== false) {
			$privacyUrl = self::getTenantUrl('privacy_policy_url', 'https://bloompy.nl/privacybeleid/');
			$text = str_replace('{bloompy_tenant_privacy_policy_url}', $privacyUrl, $text);
		}

		// Handle terms & conditions URL shortcode
		if (strpos($text, '{bloompy_tenant_terms_conditions_url}') !== false) {
			$termsUrl = self::getTenantUrl('terms_conditions_url', 'https://bloompy.nl/algemene-voorwaarden/');
			$text = str_replace('{bloompy_tenant_terms_conditions_url}', $termsUrl, $text);
		}

		return $text;
	}

	/**
	 * Get a tenant URL setting with fallback
	 */
	private static function getTenantUrl($urlKey, $defaultUrl)
	{
		try {
			if (class_exists('\BookneticSaaS\Models\Tenant') && class_exists('\BookneticApp\Providers\Core\Permission')) {
				$tenantId = \BookneticApp\Providers\Core\Permission::tenantId();
				if ($tenantId > 0) {
					$url = \BookneticSaaS\Models\Tenant::getData($tenantId, $urlKey);
					if (!empty($url)) {
						return $url;
					}
				}
			}
		} catch (\Exception $e) {
			// Log error and fall back to default
			error_log('Bloompy Tenant Info: Error getting tenant URL - ' . $e->getMessage());
		}

		return $defaultUrl;
	}

	public static function setTemplateField( $fields )
	{
		$fields[ 'bloompy_tenants' ] = true;

		return $fields;
	}

	public static function setTemplateFieldLabel( $labels )
	{
		$labels[ 'bloompy_tenants' ] = bkntc__( 'Booking Page Info' );

		return $labels;
	}



	public static function customerPanelReplaceShortCode( string $text, $data )
	{
		$shortCodeReplacers = [
			'customer_panel_url' => CustomerPanelHelper::customerPanelURL(),
			'customer_panel_restriction_time' => Helper::getOption( 'time_restriction_to_make_changes_on_appointments', '5' )
		];

		foreach ( $shortCodeReplacers as $shortcode => $replacer )
		{
			$text = str_replace( '{' . $shortcode . '}', $replacer, $text );
		}

		return $text;
	}

}