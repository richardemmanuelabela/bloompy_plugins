<?php
namespace Bloompy\RecurringPayments\Backend;
use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends BaseController
{
	public static function add_automatic_recurring_payment_switch_row_to_service_view($parameters)
	{
		$sid = $parameters['service']['id'] ?? 0;
		
		// Load service-specific checkbox value using Service::getData
		$automaticRecurringPaymentSwitch = self::getServiceList($sid, 'automatic_recurring_payment_switch');
		error_log("automaticRecurringPaymentSwitch: ".print_r($automaticRecurringPaymentSwitch, true));
		
		return [
			'service_id' => $sid,
			'automatic_recurring_payment_switch' => $automaticRecurringPaymentSwitch
		];
	}

	/**
	 * Get automatic recurring payment switch value for a specific service.
	 * @param int $serviceId
	 * @param string $key
	 * @return string
	 */
	public static function getServiceList($serviceId, $key)
	{
		if (empty($serviceId)) {
			return '';
		}

		try {
			return \BookneticApp\Models\Service::getData($serviceId, $key, '');
		} catch (\Exception $e) {
			error_log('Failed to get service data for service ' . $serviceId . ': ' . $e->getMessage());
			return '';
		}
	}

	/**
	 * Save automatic recurring payment switch value when service is saved.
	 * @param array $arr Service data array
	 * @return array
	 */
	public static function save_automatic_recurring_payment_switch($arr)
	{
		error_log("save action");
		if (!empty($arr['id'])) {
			$value = Helper::_post('automatic_recurring_payment_switch', '0', 'int');
			// Convert checkbox value: if checked, value is '1', otherwise '0'
			$value = ($value === 1 || $value === 'on') ? '1' : '0';
			try {
				error_log("checkbox save = ".$value);
				\BookneticApp\Models\Service::setData($arr['id'], 'automatic_recurring_payment_switch', $value);
			} catch (\Exception $e) {
				error_log('Failed to save automatic recurring payment switch for service ' . $arr['id'] . ': ' . $e->getMessage());
			}
		}
		return $arr;
	}
}