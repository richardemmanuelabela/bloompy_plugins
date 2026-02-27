<?php

namespace Bloompy\CustomerPanel;

use BookneticApp\Backend\Customers\Helpers\CustomerService;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class CustomerPanelHelper
{

	private static $myCustomer;

	public static function canUseCustomerPanel()
	{
        if( ! ( Permission::userId() > 0 ) )
            return false;

		if( Helper::getOption('customer_panel_enable', 'off', false) != 'on' )
			return false;

		if( ! self::myCustomer() )
			return false;

		return true;
	}

	public static function myCustomer()
	{
		if( is_null( self::$myCustomer ) )
		{
			$userId = Permission::userId();
			self::$myCustomer = Customer::where('user_id', $userId)->noTenant()->fetch();
		}

		return self::$myCustomer;
	}

	public static function myCustomersIDs()
	{
		$customers = CustomerService::getCustomersOfLoggedInUser();

		return array_column( $customers, 'id' );
	}

	public static function canRescheduleAppointment( $appointment )
	{

        $allowedRescheduleStatuses = Helper::getOption('customer_panel_reschedule_allowed_status', '');
        $allowedRescheduleStatuses = explode(',', $allowedRescheduleStatuses);

		if ( Helper::getOption('customer_panel_allow_reschedule', 'on') != 'on' )
			return false;

        if ( !in_array($appointment->status, $allowedRescheduleStatuses ) && !empty($allowedRescheduleStatuses) )
            return false;

		if(Date::epoch() >= Date::epoch($appointment->starts_at))
			return false;

		$minute = Helper::getOption('time_restriction_to_make_changes_on_appointments', '5');

		if(Date::epoch('+'.$minute.' minutes') > Date::epoch($appointment->starts_at))
			return false;

		return true;
	}

	public static function canChangeAppointmentStatus( $appointment )
	{
		$allStatuses    = Helper::getAppointmentStatuses();
		$statuses       = Helper::getOption( 'customer_panel_allowed_status', '' );
		$statusesArray  = explode(',', $statuses);
		$minute = Helper::getOption('time_restriction_to_make_changes_on_appointments', '0');

		$statusesArray  = array_filter($statusesArray, function ($item) use ($allStatuses, $appointment)
		{
			if (empty($item))
				return false;

			if (!array_key_exists($item, $allStatuses))
				return false;

			if ($item == $appointment->status)
				return false;

			return true;
		});

		if ( Date::epoch('+'. $minute . ' minutes') >= Date::epoch($appointment->starts_at) )
		{
			$statusesArray = [];
		}

		return count( $statusesArray ) > 0;
	}

    public static function customerPanelURL()
    {
        $customerPanelPageID = Helper::getOption('customer_panel_page_id', '', false);

        if( empty( $customerPanelPageID ) )
            return '';

        return get_page_link( (int)$customerPanelPageID );
    }

    public static function getCompanyLink()
    {
        $companyLink = Helper::getOption( 'company_website', '' );

        if ( ! empty( $companyLink ) )
            return htmlspecialchars( $companyLink );

        return site_url() . '/' . htmlspecialchars( Permission::tenantInf()->domain );
    }
}