<?php

namespace BookneticAddon\BloompyTenants\Frontend;

use BookneticAddon\Customerpanel\CustomerPanelHelper;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;
use BookneticApp\Models\Service;

class Ajax extends FrontendAjax
{

    public function getdata()
    {
        $tenantId	=	Helper::_post('tenant_id', '', 'int');
		$serviceId	=	Helper::_post('service', '', 'int');
		$data	=	Helper::_post('data', '', 'str');
		$tenantCompanyName = Tenant::getData( $tenantId, "tenant_company_name" );
		$companyName = (empty($tenantCompanyName)) ? get_option( 'bkntc_t'.$tenantId.'_company_name' ): $tenantCompanyName;
		$footerFirstColumn = Tenant::getData( $tenantId, "footer_first_column" );
		$footerSecondColumn = Tenant::getData( $tenantId, "footer_second_column" );
		$footerThirdColumn = Tenant::getData( $tenantId, "footer_third_column" );
		$footerFourthColumn = Tenant::getData( $tenantId, "footer_fourth_column" );

		$service = Service::get($serviceId);
		$service_name = $service->name ?? '';

        if( empty( $tenantId ) )
        {
            return false;
        }

		return $this->response(true, [
			'id'	=>	$tenantId,
			'company_name' => $companyName,
			'footer_first_column' => $footerFirstColumn,
			'footer_second_column' => $footerSecondColumn,
			'footer_third_column' => $footerThirdColumn,
			'footer_fourth_column' => $footerFourthColumn,
			'service_name' => $service_name
			//'footer_text' => Tenant::getData( $tenantId, "tenant_footer_text" )
		]);
    }
}