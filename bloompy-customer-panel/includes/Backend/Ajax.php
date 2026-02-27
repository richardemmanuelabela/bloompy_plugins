<?php

namespace Bloompy\CustomerPanel\Backend;


use BookneticApp\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function customer_panel_settings()
	{
		return $this->modalView( 'customer_panel_settings', [] );
	}

    public function save_customer_panel_settings()
    {
        if( ! Helper::isSaaSVersion() )
        {
            $customer_panel_enable               = Helper::_post('customer_panel_enable', 'off', 'string', ['on', 'off']);
            $customer_panel_page_id	             = Helper::_post('customer_panel_page_id', '', 'int');
            $customer_panel_allow_delete_account = Helper::_post('customer_panel_allow_delete_account', 'on', 'string', ['on', 'off']);

            Helper::setOption( 'customer_panel_enable', $customer_panel_enable );
            Helper::setOption( 'customer_panel_page_id', $customer_panel_page_id );
            Helper::setOption( 'customer_panel_allow_delete_account', $customer_panel_allow_delete_account, false );
        }

        $customer_panel_allow_reschedule		                = Helper::_post('customer_panel_allow_reschedule', 'on', 'string', ['on', 'off']);
        $hide_pay_now_btn_customer_panel		                = Helper::_post('hide_pay_now_btn_customer_panel', 'off', 'string', ['on', 'off']);
        $customer_panel_allowed_status			                = Helper::_post('customer_panel_allowed_status', '', 'string');
        $time_restriction_to_make_changes_on_appointments		= Helper::_post('time_restriction_to_make_changes_on_appointments', '5', 'int');
        $customer_panel_reschedule_allowed_status               = Helper::_post('customer_panel_reschedule_allowed_status', '', 'string');

        if ( Helper::_post('allow_customer_to_change_appointment_status', 'on', 'string', ['on', 'off'] ) === 'off' )
        {
            $customer_panel_allowed_status = '';
        }

        if ( $customer_panel_allow_reschedule === 'off' )
        {
            $customer_panel_reschedule_allowed_status = '';
        }

        Helper::setOption('customer_panel_allow_reschedule', $customer_panel_allow_reschedule);
        Helper::setOption('hide_pay_now_btn_customer_panel', $hide_pay_now_btn_customer_panel);
        Helper::setOption('customer_panel_allowed_status', $customer_panel_allowed_status);
        Helper::setOption('time_restriction_to_make_changes_on_appointments', $time_restriction_to_make_changes_on_appointments);
        Helper::setOption('customer_panel_reschedule_allowed_status', $customer_panel_reschedule_allowed_status);

        return $this->response(true);
    }

	public function customer_panel_settings_saas()
	{
		return $this->modalView( 'customer_panel_settings_saas', [] );
	}

    public function save_customer_panel_settings_saas()
    {
        $customer_panel_enable				 = Helper::_post('customer_panel_enable', 'off', 'string', ['on', 'off']);
        $customer_panel_page_id				 = Helper::_post('customer_panel_page_id', '', 'int');
        $customer_panel_allow_delete_account = Helper::_post('customer_panel_allow_delete_account', 'on', 'string', ['on', 'off']);

        Helper::setOption('customer_panel_enable', $customer_panel_enable, false );
        Helper::setOption('customer_panel_page_id', $customer_panel_page_id, false );
        Helper::setOption('customer_panel_allow_delete_account', $customer_panel_allow_delete_account, false );

        return $this->response(true);
    }

}