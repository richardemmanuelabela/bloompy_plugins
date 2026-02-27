<?php

namespace BookneticAddon\Newsletters\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Helper;
use BookneticAddon\Newsletters\Providers\MailBlueIntegration;
use BookneticAddon\Newsletters\Providers\MailChimpIntegration;
use BookneticApp\Models\Service;

class Controller extends BaseController
{
    public function index()
    {
        $message = '';
        $assignMode = isset($_GET['assign']) ? $_GET['assign'] : '';
        $isMailblueAssign = $assignMode === 'mailblue';
        $isMailchimpAssign = $assignMode === 'mailchimp';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update API fields and default lists if present in POST (configure page)
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
                foreach ($_POST as $key => $val) {
                    if (strpos($key, 'mailblue_service_') === 0) {
                        $serviceId = (int)str_replace('mailblue_service_', '', $key);
                        $listId = sanitize_text_field($val);
                        \BookneticAddon\Newsletters\NewsletterManager::setServiceList($serviceId, 'mailblue', $listId);
                    }
                }
            }
            // Save per-service mapping for MailChimp using Service::setData
            if ($assignMode === 'mailchimp') {
                foreach ($_POST as $key => $val) {
                    if (strpos($key, 'mailchimp_service_') === 0) {
                        $serviceId = (int)str_replace('mailchimp_service_', '', $key);
                        $listId = sanitize_text_field($val);
                        \BookneticAddon\Newsletters\NewsletterManager::setServiceList($serviceId, 'mailchimp', $listId);
                    }
                }
            }
            $message = 'Settings saved!';
        }
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
        $this->view($viewFile, [
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
        ]);
    }
	public static function add_mailblue_row_to_service_view($parameters)
	{
		$sid = $parameters['service']['id'];
		$assignMode = Helper::_post('data-name', '0', 'string');
		$message = '';

		// Always fetch API keys from options for the view
		$apiKey = Helper::getOption('newsletter_mailblue_api_key', '');
		$apiUrl = Helper::getOption('newsletter_mailblue_api_url', '');
		$mailblueLists = \BookneticAddon\Newsletters\NewsletterManager::getLists('mailblue');


		// Load default lists for current tenant
		$mailblueDefault = Helper::getOption('newsletter_mailblue_default', '');

		// Load service-specific lists using Service::getData
		$mailblueMap = [];
		$mailblueActiveListId = \BookneticAddon\Newsletters\NewsletterManager::getServiceList($sid, 'mailblue');
		if (!empty($mailblueListId)) {
			$mailblueMap[$sid] = $mailblueListId;
		}
		$viewFile = $assignMode === '' ? 'configure' : 'assign';
		return ['newsletter' => [
			'service_id' => $sid,
			'mailblue_lists' => $mailblueLists,
			'mailblue_active_list_id' => $mailblueActiveListId,
			'mailblue_map' => $mailblueMap,
			'mailblue_default' => $mailblueDefault,
			'assign_mode' => $assignMode,
			'message' => $message,
			'apiKey' => $apiKey,
			'apiUrl' => $apiUrl
		]];
	}

	public static function mailblue_data_save_service($arr)
	{
		if (! empty($arr[ 'id' ])) {
			$mailblueId = Helper::_post('mailblue', '0', 'int');
			$listId = sanitize_text_field($mailblueId);
			\BookneticAddon\Newsletters\NewsletterManager::setServiceList($arr[ 'id' ], 'mailblue', $listId);
		}
		return $arr;
	}


	public static function add_mailchimp_row_to_service_view($parameters)
	{
		$sid = $parameters['service']['id'];
		$assignMode = Helper::_post('data-name', '0', 'string');
		$message = '';

		// Always fetch API keys from options for the view
		$mailchimpApiKey = Helper::getOption('newsletter_mailchimp_api_key', '');
		$mailchimpLists = \BookneticAddon\Newsletters\NewsletterManager::getLists('mailchimp');


		// Load default lists for current tenant
		$mailchimpDefault = Helper::getOption('newsletter_mailchimp_default', '');

		// Load service-specific lists using Service::getData
		$mailchimpMap = [];
		$mailchimpListId = \BookneticAddon\Newsletters\NewsletterManager::getServiceList($sid, 'mailchimp');
		if (!empty($mailchimpListId)) {
			$mailchimpMap[$sid] = $mailchimpListId;
		}
		$viewFile = $assignMode === '' ? 'configure' : 'assign';
		return ['newsletter' => [
			'service_id' => $sid,
			'mailchimp_api_key' => $mailchimpApiKey,
			'mailchimp_lists' => $mailchimpLists,
			'mailchimp_map' => $mailchimpMap,
			'mailchimp_default' => $mailchimpDefault,
			'assign_mode' => $assignMode,
			'mailchimp_active_list_id' => $mailchimpListId,
			'message' => $message
		]];
	}
	public static function mailchimp_data_save_service($arr)
	{
		if (! empty($arr[ 'id' ])) {
			$mailchimpId = Helper::_post('mailchimp', '0', 'string');
			$listId = sanitize_text_field($mailchimpId);
			error_log("mailchimp: ".$listId);
			error_log("service id: ".$arr[ 'id' ]);
			\BookneticAddon\Newsletters\NewsletterManager::setServiceList($arr[ 'id' ], 'mailchimp', $listId);
		}
		return $arr;
	}
} 