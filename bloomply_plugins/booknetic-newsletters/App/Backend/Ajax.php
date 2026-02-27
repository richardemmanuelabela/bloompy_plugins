<?php

namespace BookneticAddon\Newsletters\Backend;

use BookneticApp\Models\Service;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use function \BookneticAddon\Newsletters\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
	public function assign()
	{
		$assignMode = Helper::_post('data-name', '0', 'string');
		$message = '';
		//$assignMode = isset($_GET['assign']) ? $_GET['assign'] : '';
		$isMailblueAssign = $assignMode === 'mailblue';
		$isMailchimpAssign = $assignMode === 'mailchimp';

		// Always fetch API keys from options for the view
		$apiKey = Helper::getOption('newsletter_mailblue_api_key', '');
		$apiUrl = Helper::getOption('newsletter_mailblue_api_url', '');
		$mailchimpApiKey = Helper::getOption('newsletter_mailchimp_api_key', '');
		$mailblueLists = \BookneticAddon\Newsletters\NewsletterManager::getLists('mailblue');
		$mailchimpLists = \BookneticAddon\Newsletters\NewsletterManager::getLists('mailchimp');
		$services = Service::select(['id', 'name'])->fetchAll();
		
		// Load default lists for current tenant
		$mailblueDefault = Helper::getOption('newsletter_mailblue_default', '');
		$mailchimpDefault = Helper::getOption('newsletter_mailchimp_default', '');
		
		// Load service-specific lists using Service::getData
		$mailblueMap = [];
		$mailchimpMap = [];
		foreach ($services as $service) {
			$mailblueListId = \BookneticAddon\Newsletters\NewsletterManager::getServiceList($service['id'], 'mailblue');
			if (!empty($mailblueListId)) {
				$mailblueMap[$service['id']] = $mailblueListId;
			}
			
			$mailchimpListId = \BookneticAddon\Newsletters\NewsletterManager::getServiceList($service['id'], 'mailchimp');
			if (!empty($mailchimpListId)) {
				$mailchimpMap[$service['id']] = $mailchimpListId;
			}
		}
		$viewFile = $assignMode === '' ? 'configure' : 'assign';


		return $this->modalView('assign', ['newsletter' => [
			'mailblue_api_key' => $apiKey,
			'mailblue_api_url' => $apiUrl,
			'mailblue_lists' => $mailblueLists,
			'mailblue_map' => $mailblueMap,
			'mailblue_default' => $mailblueDefault,
			'mailchimp_api_key' => $mailchimpApiKey,
			'mailchimp_lists' => $mailchimpLists,
			'mailchimp_map' => $mailchimpMap,
			'mailchimp_default' => $mailchimpDefault,
			'services' => $services,
			'assign_mode' => $assignMode,
			'message' => $message
		]]);
	}
	public function save_list() {
		$assignMode	= Helper::_post('assign_mode', '', 'string');
		$listData	= Helper::_post('list_data', '', 'json');

		// Only update API fields and default lists if present in POST (configure page)
		if ($assignMode === '') {
			$apiKey = sanitize_text_field($_POST['mailblue_api_key'] ?? '');
			$apiUrl = sanitize_text_field($_POST['mailblue_api_url'] ?? '');
			Helper::setOption('newsletter_mailblue_api_key', $apiKey);
			Helper::setOption('newsletter_mailblue_api_url', $apiUrl);
			$mcApiKey = sanitize_text_field($_POST['mailchimp_api_key'] ?? '');
			Helper::setOption('newsletter_mailchimp_api_key', $mcApiKey);
			
			// Save default lists for current tenant
			if (isset($_POST['mailblue_default_list'])) {
				Helper::setOption('newsletter_mailblue_default', sanitize_text_field($_POST['mailblue_default_list']));
			}
			if (isset($_POST['mailchimp_default_list'])) {
				Helper::setOption('newsletter_mailchimp_default', sanitize_text_field($_POST['mailchimp_default_list']));
			}
		}

		// Save per-service mapping for MailBlue using Service::setData
		if ($assignMode === 'mailblue') {
			foreach ($listData as $key => $val) {
				if (strpos($key, 'mailblue_service_') === 0) {
					$serviceId = (int)str_replace('mailblue_service_', '', $key);
					$listId = sanitize_text_field($val);
					\BookneticAddon\Newsletters\NewsletterManager::setServiceList($serviceId, 'mailblue', $listId);
				}
			}
		}
		// Save per-service mapping for MailChimp using Service::setData
		if ($assignMode === 'mailchimp') {
			foreach ($listData as $key => $val) {
				if (strpos($key, 'mailchimp_service_') === 0) {
					$serviceId = (int)str_replace('mailchimp_service_', '', $key);
					$listId = sanitize_text_field($val);
					\BookneticAddon\Newsletters\NewsletterManager::setServiceList($serviceId, 'mailchimp', $listId);
				}
			}
		}
		return $this->response( true );
	}
}