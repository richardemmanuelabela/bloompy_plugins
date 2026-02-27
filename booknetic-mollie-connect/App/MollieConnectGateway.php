<?php

namespace BookneticAddon\Bloompy\Mollie;

use BookneticAddon\Bloompy\Mollie\Helpers\MollieConnectHelper;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Common\PaymentData;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\Tenant;
use Mollie\Api\MollieApiClient;
use function Adminer\q;
use function BookneticAddon\Bloompy\Mollie\bkntc__;

class MollieConnectGateway extends PaymentGatewayService
{
    protected $slug = 'mollie_split';

    private $_paymentId;
    private $_type;
    private $_fee;
    private $_successURL;
    private $_cancelURL;
    private $_customer;
    private $_appointmentIds;
    private $_mollieClient;

	private $_appointmentRequests;

    public $createPaymentLink = true;
    protected $_items = [];

    public function __construct()
    {
        $this->setDefaultTitle(bkntc__('iDEAL'));
        $this->setDefaultIcon(MollieAddon::loadAsset('assets/frontend/icons/mollie.svg'));

        //$this->_mollieClient = MollieConnectHelper::getInstance()->getMollieApiClient(Permission::tenantId());
    }

    private function getMollieClient()
    {
        if ($this->_mollieClient === null) {
            $this->_mollieClient = MollieConnectHelper::getInstance()->getMollieApiClient(Permission::tenantId());
        }
        return $this->_mollieClient;
    }

    public function when($status, $appointmentRequests = null)
    {
        if ($status && Helper::getOption('hide_confirm_details_step', 'off') == 'on') {
            return false;
        }

        return $status;
    }

    public function setId($paymentId)
    {
        $this->_paymentId = $paymentId;
        return $this;
    }

    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    public function setAppointmentIds($arr)
    {
        $this->_appointmentIds = $arr;
        return $this;
    }

    public function addItem($price, $currency, $itemName)
    {
        $normalizedPrice = $this->normalizePrice($price, $currency);

        $this->_items[] = [
            'type' => 'digital',
            'name' => $itemName,
            'unitPrice' => [
                'currency' => $currency,
                'value'    => $normalizedPrice,
            ],
            'totalAmount' => [
                'currency' => $currency,
                'value'    => $normalizedPrice,
            ],
            'vatAmount' => [
                'currency' => $currency,
                'value'    => '0.00', // Required field, even if 0
            ],
            'vatRate' => '0.00',
            'quantity' => 1,
        ];
        return $this;
    }

    public function setSuccessURL($url)
    {
        $this->_successURL = $url;
        return $this;
    }

    public function setCancelURL($url)
    {
        $this->_cancelURL = $url;
        return $this;
    }

    public function setCustomer($customerObject)
    {
        $this->_customer = $customerObject;
        return $this;
    }
	public function setAppointmentRequests($appointmentRequests)
	{
		$this->_appointmentRequests = $appointmentRequests;
		return $this;
	}

    private function createPaymentRequest($customData = [])
    {
        $tenantId = Permission::tenantId();
        $tenantSettings = MollieConnectHelper::getTenantSettings($tenantId);
        try {
            $description = '';
            if (!empty($this->_items)) {
                $itemNames = array_map(function($item) {
                    return $item['name'];
                }, $this->_items);
                $description = implode(', ', $itemNames);
            }

			$tenant = \BookneticSaaS\Models\Tenant::where('id', $tenantId)->fetch();

			$customerId = $this->getCustomerId();


			$customerMollie = $this->getCustomerMollie($customerId, $tenantId, $tenantSettings);

			$customerMollieId = $customerMollie->id;



            
            $payload = [
                'amount' => [
                    'currency' => Helper::getOption('currency', 'EUR'),
                    'value'    => $this->getTotalAmount(),
                ],
                'description' => $description ?: 'Payment',
                'redirectUrl' => $this->_successURL,
                'cancelUrl'   => $this->_cancelURL,
                'metadata'    => !empty($customData) ? $customData : [
                    'payment_id' => $this->_paymentId,
                    'type'       => $this->_type,
                    'appointment_ids' => $this->_appointmentIds,
                ],
            ];


			$isRecurringPresent = $this->isRecurringServicePresent();
			if ( $isRecurringPresent ) {
				$payload['sequenceType'] = \Mollie\Api\Types\SequenceType::SEQUENCETYPE_FIRST;
			}

            // Add application fee if split payment is configured
            if (!empty($this->_fee['fee'])) {
                $totalAmount = (float)$this->getTotalAmount();
                $feeAmount = (float)$this->_fee['fee'];
                
                // Application fee cannot exceed the total payment amount
                // Mollie requires the fee to be less than the total amount
                if ($feeAmount >= $totalAmount) {
                    // Cap the fee at 99.9% of the total amount to ensure it's always less
                    $feeAmount = $totalAmount * 0.999;
                    error_log('[MOLLIE WARNING]: Application fee (' . $this->_fee['fee'] . ') was higher than total amount (' . $totalAmount . '). Capped to ' . $feeAmount);
                }
                
                $payload['applicationFee'] = [
                    'amount' => [
                        'currency' => Helper::getOption('currency', 'EUR'),
                        'value'    => $this->normalizePrice($feeAmount, Helper::getOption('currency', 'EUR')),
                    ],
                    'description' => 'Bloompy platform fee',
                ];
            }


            // Add profileId for split payments
            $profileId = MollieConnectHelper::getInstance()->getProfileId($tenantId);
            if ($profileId) {
                $payload['profileId'] = $profileId;
            }


            // Set testmode if needed
            if ($tenantSettings['testmode']) {
                $payload['testmode'] = true;
            }

            $payment = $customerMollie->createPayment($payload);
            $molliePaymentId = $payment->id;

			if ( $isRecurringPresent ) {
				//process subscription
				Customer::setData( $customerId, 'recurring_data_'.$molliePaymentId, json_encode($this->_appointmentRequests) );
				//process subscription
				Customer::setData( $customerId, 'recurring_appointmentIds_'.$molliePaymentId, json_encode($this->getAppointmentIds()) );
				Customer::setData( $customerId, 'recurring_total_amount_'.$molliePaymentId, json_encode($this->getTotalAmount()) );
			}
            
            // Update redirectUrl to include the Mollie payment ID
            $separator = (strpos($this->_successURL, '?') !== false) ? '&' : '?';
            $updatedRedirectUrl = $this->_successURL . $separator . 'payment_id=' . $molliePaymentId;
            
            // Prepare update payload - must include testmode if payment was created in test mode
            $updatePayload = [
                'redirectUrl' => $updatedRedirectUrl
            ];
            
            // Include testmode in update if it was used in creation
            // This ensures the API uses the same mode for both create and update
            if (isset($payload['testmode']) && $payload['testmode']) {
                $updatePayload['testmode'] = true;
            }

			$payment = $this->getMollieClient()->payments->update($molliePaymentId, $updatePayload);

            Tenant::setData($tenantId, 'mollie_payment_id_' . $this->_paymentId, $molliePaymentId);
            Tenant::setData($tenantId, 'mollie_payment_mode_' . $this->_paymentId, $tenantSettings['testmode'] ? 'test' : 'live');

			return  $payment->getCheckoutUrl();
        } catch (\Exception $ex) {
            error_log('[MOLLIE ERROR]: ' . $ex->getMessage() . print_r($payload, true));
            error_log('[MOLLIE PAYLOAD]' . print_r($payload, true));

            return 0;
        }
    }

    public function doPayment($appointmentRequests)
    {
        try {
            $this->getMollieClient();

            $this->resetItems();

            $tenantIdParam = (Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '');
            $totalPrice = 0;

            $this->setId($appointmentRequests->paymentId);

			$this->setAppointmentRequests($appointmentRequests);

			$appointmentIds = [];

            foreach ($appointmentRequests->appointments as $appointmentObj) {
                $this->addItem(
                    $appointmentObj->getPayableToday(true),
                    Helper::getOption('currency', 'EUR'),
                    $appointmentObj->serviceInf->name
                );

                $totalPrice += $appointmentObj->getPayableToday(true);

                $customer = $appointmentObj->customerDataObj;
				$appointmentIds[] =  $appointmentObj->appointmentId;
            }
			$encodedAppointmentIds = base64_encode(implode(',', $appointmentIds));
			$this->setAppointmentIds($encodedAppointmentIds);

            $this->setCustomer($customer);

            $this->setSuccessURL(site_url() . '/?bkntc_mollie_split_status=success'  . $tenantIdParam);
            $this->setCancelURL(site_url() . '/?bkntc_mollie_split_status=cancel'  . $tenantIdParam);
            
            $this->setFee(
                Permission::tenantId(),
                $totalPrice,
                Helper::getOption('mollie_connect_platform_fee', '0', false),
                Helper::getOption('mollie_connect_fee_type', 'percent', false)
            );

            $checkoutUrl = $this->createPaymentRequest();

            if ($checkoutUrl === 0) {
                return (object)[
                    'status' => false,
                    'data'   => ['error_msg' => bkntc__("Couldn't create a payment!")],
                ];
            }

            return (object)[
                'status' => true,
                'data'   => ['url' => $checkoutUrl],
            ];
        } catch (\Throwable $e) {
        return (object)[
            'status' => false,
            'data'   => ['error_msg' => bkntc__("Couldn't create a payment!")],
        ];
        }
    }

    public function createPaymentLink($appointments)
    {
        $this->resetItems();

        $tenantIdParam = (Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '');
        $appointmentIds = array_map(fn($appointment) => $appointment->id, $appointments);
        $encodedAppointmentIds = base64_encode(implode(',', $appointmentIds));
        $this->setId($encodedAppointmentIds);
        
        $this->setType('create_payment_link');
        $this->setAppointmentIds($encodedAppointmentIds);

        $totalPrice = 0;
        $paymentId = null;
        $customer  = new \stdClass();
        foreach ($appointments as $appointmentObj) {
            if ( $appointmentObj->payment_status == "pending" ) {
                $this->addItem(
                    $appointmentObj->total_price,
                    Helper::getOption('currency', 'EUR'),
                    $appointmentObj->service_name
                );
            } else {
                $this->addItem(
                    Math::sub($appointmentObj->total_price, $appointmentObj->paid_amount),
                    Helper::getOption('currency', 'EUR'),
                    $appointmentObj->service_name
                );
            }
            if ($appointmentObj->payment_status == "pending") {
                $totalPrice += $appointmentObj->total_price;
            } else {
                $totalPrice += Math::sub($appointmentObj->total_price, $appointmentObj->paid_amount);
            }
            
            $customerObj = $appointmentObj->customerDataObj;
            
            $customer->first_name = $appointmentObj->customer_first_name;
            $customer->last_name = $appointmentObj->customer_first_name;
            $customer->email = $appointmentObj->customer_email;
            $paymentId = $appointmentObj->payment_id;
        }
        $this->setCustomer($customer);

        $this->setSuccessURL(site_url() . '/?bkntc_mollie_split_status=success&type=create_payment_link' . $tenantIdParam );
        $this->setCancelURL(site_url() . '/?bkntc_mollie_split_status=cancel&type=create_payment_link' . $tenantIdParam);
        $this->setFee(
            Permission::tenantId(),
            $totalPrice,
            Helper::getOption('mollie_connect_platform_fee', '0', false),
            Helper::getOption('mollie_connect_fee_type', 'percent', false)
        );

        $checkoutUrl = $this->createPaymentRequest();

        if ($checkoutUrl === 0) {
            return (object)[
                'status' => false,
                'data'   => ['error_msg' => bkntc__("Couldn't create a payment!")],
            ];
        }

        return (object)[
            'status' => true,
            'data'   => ['url' => $checkoutUrl],
        ];
    }

    public function createPayment(array $items, array $customData)
    {
        $this->resetItems();

        foreach ($items as $item) {
            $this->addItem(
                $item['price'] ?? 0,
                Helper::getOption('currency', 'EUR'),
                $item['name'] ?? 'Product'
            );
        }

        $this->setSuccessURL(site_url() . '/?bkntc_mollie_split_status=success');
        $this->setCancelURL(site_url() . '/?bkntc_mollie_split_status=cancel');

        $checkoutUrl = $this->createPaymentRequest($customData);

        if ($checkoutUrl === 0) {
            return (object)[
                'status' => false,
                'data'   => ['error_msg' => bkntc__("Couldn't create a payment!")],
            ];
        }

        return (object)[
            'status' => true,
            'data'   => ['url' => $checkoutUrl],
        ];
    }

    private function getTotalAmount()
    {
        $total = 0;
        foreach ($this->_items as $item) {
            $total += (float)$item['totalAmount']['value'];
        }
        return number_format($total, 2, '.', '');
    }

    protected function resetItems()
    {
        $this->_items = [];
    }

    private function normalizePrice($price, $currency)
    {
        $currencies = [
            'BIF' => 0, 'DJF' => 0, 'JPY' => 0, 'KRW' => 0, 'PYG' => 0, 'VND' => 0,
            'XAF' => 0, 'XPF' => 0, 'CLP' => 0, 'GNF' => 0, 'KMF' => 0, 'MGA' => 0,
            'RWF' => 0, 'VUV' => 0, 'XOF' => 0, 'ISK' => 0, 'UGX' => 0, 'UYI' => 0,
            'BHD' => 3, 'IQD' => 3, 'JOD' => 3, 'KWD' => 3, 'LYD' => 3, 'OMR' => 3, 'TND' => 3,
        ];

        $decimals = $currencies[$currency] ?? 2;
        return number_format((float)$price, $decimals, '.', '');
    }

    public function setFee($tenantId, $totalPrice, $rawFee, $feeType = 'percent')
    {
        $feeData = MollieConnectHelper::getFeeData($totalPrice);

        $numericFee = (float)$feeData['fee'];
        $currency = Helper::getOption('currency', 'EUR');
        if ($numericFee < 0.01) {
            $this->_fee = null;
            return;
        }

        $fee = $this->normalizePrice($numericFee, $currency);
        $this->_fee = [
            'fee' => $fee,
            'destination' => Tenant::getData($tenantId, 'mollie_connect_org_id'),
        ];
    }
    public function check( $paymentId, $testmode = null )
    {
        try
        {
            $params = [];
            if ( $testmode !== null ) {
                $params['testmode'] = (bool) $testmode;
            }
            
            if ( Helper::getOption( 'mollie_use_selected_payment_methods' ) ) {
                return $this->getMollieClient()->orders->get( $paymentId, $params );
            } else {
                return $this->getMollieClient()->payments->get( $paymentId, $params );
            }
        }
        catch ( \Exception $ex )
        {
            error_log('[MOLLIE CHECK ERROR]: ' . $ex->getMessage());
            return null;
        }
    }

    public static function shipOrder( $paymentOrOrder )
    {
        // Only create shipment for Order objects, not Payment objects
        // Payments don't have shipments in Mollie API
        if ( method_exists( $paymentOrOrder, 'createShipment' ) ) {
            $paymentOrOrder->createShipment();
        } else {
            // Payments don't need shipments, so we can skip this
            error_log('[MOLLIE] Skipping shipment creation - method not available for: ' . get_class($paymentOrOrder));
        }
    }

	private function getCustomerMollie($customerId, $tenantId, $tenantSettings)
	{
		// Check if customer already exists for this tenant and mode
		$customer = Customer::where('id', $customerId)->fetch();
		$modeKey = $tenantSettings['testmode'] ? 'test' : 'live';

		$mollieClient = $this->getMollieClient();
		$customerData = [
			"name"  => $customer->full_name,
			"email" => $customer->email
		];

		// Include testmode when creating customer
		if ($tenantSettings['testmode']) {
			$customerData['testmode'] = true;
		}

		$customerMollie = $mollieClient->customers->create($customerData);
		$customerMollieId = $customerMollie->id;
		return $customerMollie;
	}

	public function getCustomerId(): int
	{
		$appointmentIds = $this->getAppointmentIds();
		$firstAppointment = Appointment::where("id", $appointmentIds[0])->fetch();

		return $firstAppointment->customer_id;
	}

	public function getAppointmentIds()
	{
		$decodedAppointmentId = base64_decode($this->_appointmentIds);
		return explode(',', $decodedAppointmentId);
	}

	public function saveCustomerMollieIdOnAppointments($modeKey, $customerMollieId)
	{
		$appointmentIds = $this->getAppointmentIds();
		foreach ($appointmentIds as $appointmentId) {
			\BookneticApp\Models\Appointment::setData($appointmentId, 'mollie_customer_id_' . $modeKey, $customerMollieId);
		}

	}


	public static function processMollieSubscription($paymentInf)
	{
		$mainAppointment = Appointment::where('payment_id', $paymentInf->metadata->payment_id)->fetch();

		if (!self::validateRecurringSubscription($mainAppointment)) {
			return;
		}

		$paymentData = self::getPaymentData($paymentInf, $mainAppointment);
		if (!$paymentData) {
			return;
		}

		$mollieConnect = new MollieConnectGateway();
		$mollieClient = $mollieConnect->getMollieClient();
		$customer = self::getMollieCustomer($mollieClient, $paymentData['customerMollieId'], $paymentData['testmode']);

		if ($customer === null) {
			error_log('[MOLLIE ERROR] Cannot create subscriptions: Customer object is null');
			return;
		}

		self::processAppointmentSubscriptions(
			$paymentData['appointments'],
			$paymentData['priceWithTax'],
			$paymentData['tenantId'],
			$paymentData['testmode'],
			$paymentData['modeKey'],
			$paymentData['customerMollieId'],
			$mollieClient,
			$mollieConnect
		);

		self::markRecurringAsProcessed($mainAppointment);
	}

	private static function validateRecurringSubscription($mainAppointment)
	{
		// Check if the service has an automatic payment recurring
		$isRecurring = \BookneticApp\Models\Service::getData($mainAppointment->service_id, 'automatic_recurring_payment_switch');

		// Check if there is already an automatic payment recurring registered
		$isRecurringExist = Customer::getData($mainAppointment->customer_id, "mollie_recurring_automatic_payment_" . $mainAppointment->recurring_id);

		return $isRecurring && !$isRecurringExist;
	}

	private static function getPaymentData($paymentInf, $mainAppointment)
	{
		$molliePaymentId = $paymentInf->id;
		$customerId = $mainAppointment->customer_id;
		$customerMollieId = $paymentInf->customerId;
		$tenantId = $mainAppointment->tenant_id;

		$appointmentRequests = json_decode(Customer::getData($customerId, 'recurring_data_' . $molliePaymentId));
		$priceWithTax = json_decode(Customer::getData($customerId, 'recurring_total_amount_' . $molliePaymentId));

		if (!$appointmentRequests || !isset($appointmentRequests->appointments)) {
			error_log('[MOLLIE ERROR] Cannot retrieve appointment requests data');
			return null;
		}

		$modeKey = $paymentInf->mode;
		$testmode = ($modeKey == "test");

		return [
			'molliePaymentId' => $molliePaymentId,
			'customerId' => $customerId,
			'customerMollieId' => $customerMollieId,
			'tenantId' => $tenantId,
			'appointments' => $appointmentRequests->appointments,
			'priceWithTax' => $priceWithTax,
			'modeKey' => $modeKey,
			'testmode' => $testmode,
		];
	}

	private static function getMollieCustomer($mollieClient, $customerMollieId, $testmode)
	{
		$params = [];
		if ($testmode) {
			$params['testmode'] = true;
		}

		try {
			return $mollieClient->customers->get($customerMollieId, $params);
		} catch (\Exception $e) {
			// If we get a 403 Forbidden error, it means the token doesn't have customers.read permission
			// This can happen if the user authorized before we added customers.read to the scope
			if (strpos($e->getMessage(), '403') !== false || strpos($e->getMessage(), 'Forbidden') !== false) {
				error_log('[MOLLIE WARNING] Cannot retrieve customer: Missing customers.read permission. User needs to re-authorize the Mollie connection to grant this permission. Continuing with customer ID: ' . $customerMollieId);
				return null;
			} else {
				// Re-throw other exceptions
				throw $e;
			}
		}
	}

	private static function processAppointmentSubscriptions($appointments, $priceWithTax, $tenantId, $testmode, $modeKey, $customerMollieId, $mollieClient, $mollieConnect)
	{
		foreach ($appointments as $appointment) {

			if (!self::isServiceRecurringEnabled($appointment)) {
				continue;
			}

			$appointmentIds = self::getRecurringAppointmentPaymentIds($appointment->appointmentId);
			$appointmentNumber = 0;

			foreach ($appointment->recurringAppointmentsList as $index => $recurringAppointment) {

				$date = $recurringAppointment[0];
				$encodedAppointmentIds = base64_encode(implode(',', $appointmentIds[$appointmentNumber]));

				if ($index != 0) {
					Appointment::setData($appointmentIds[$appointmentNumber][0], "is_recurring_appointment_automatic_payment_".$tenantId, true);
				}

				$appointmentNumber++;

				// Skip the first appointment since it is already paid
				if ($index == 0) {
					continue;
				}

				self::createSubscriptionForAppointment(
					$appointment,
					$date,
					$encodedAppointmentIds,
					$priceWithTax,
					$tenantId,
					$testmode,
					$customerMollieId,
					$mollieClient,
					$mollieConnect
				);
			}
		}
	}

	private static function isServiceRecurringEnabled($appointment)
	{
		$serviceInf = $appointment->serviceInf;
		$serviceId = $serviceInf->id;
		$isRecurring = \BookneticApp\Models\Service::getData($serviceId, 'automatic_recurring_payment_switch');

		if (!$isRecurring) {
			error_log('[MOLLIE] Service does not have automatic recurring payment enabled');
			return false;
		}

		return true;
	}

	private static function createSubscriptionForAppointment($appointment, $date, $encodedAppointmentIds, $priceWithTax, $tenantId, $testmode, $customerMollieId, $mollieClient, $mollieConnect)
	{
		$serviceInf = $appointment->serviceInf;
		$serviceName = $serviceInf->name;
		$currency = Helper::getOption('currency', 'EUR');

		$subscriptionData = self::prepareSubscriptionData(
			$serviceName,
			$date,
			$encodedAppointmentIds,
			$priceWithTax,
			$currency,
			$tenantId,
			$testmode,
			$mollieConnect
		);

		$subscriptionData = self::createMollieSubscription($mollieClient, $customerMollieId, $subscriptionData);

		$decodedAppointmentId = explode(',', base64_decode($encodedAppointmentIds));
		$appointmentId =  reset($decodedAppointmentId);
		Appointment::setData($appointmentId, "recurring_automatic_payment_data_".$tenantId, json_encode($subscriptionData));
	}

	private static function prepareSubscriptionData($serviceName, $date, $encodedAppointmentIds, $priceWithTax, $currency, $tenantId, $testmode, $mollieConnect)
	{
		$customData = [
			'payment_id' => $encodedAppointmentIds,
			'type' => "create_payment_link",
			'appointment_ids' => $encodedAppointmentIds,
		];

		$subscriptionData = [
			"amount" => [
				"currency" => $currency,
				"value" => $mollieConnect->normalizePrice($priceWithTax, $currency)
			],
			'metadata' => $customData,
			"interval" => "1 day",
			"description" => $serviceName . " - " . $date . " - " . time(),
			"startDate" => $date,
			"times" => 1,
			"testmode" => $testmode,
			"webhookUrl" => site_url() . '/?bkntc_mollie_split_status=success&type=create_payment_link&tenant_id=' . $tenantId,
		];

		// Add profileId for split payments (required for subscriptions)
		$profileId = MollieConnectHelper::getInstance()->getProfileId($tenantId);
		if ($profileId) {
			$subscriptionData['profileId'] = $profileId;
		}

		return $subscriptionData;
	}

	private static function createMollieSubscription($mollieClient, $customerMollieId, $subscriptionData)
	{
		try {
			$subscription = $mollieClient->subscriptions->createForId($customerMollieId, $subscriptionData);
			error_log('[MOLLIE] Subscription created successfully: ' . $subscription->id);
			return [
				"subscription_id" =>  $subscription->id,
				"customer_id" => $customerMollieId
			];
		} catch (\Exception $e) {
			self::handleSubscriptionCreationError($e, $customerMollieId);
		}
	}

	private static function handleSubscriptionCreationError($e, $customerMollieId)
	{
		// Check if it's a 403 Forbidden error due to missing subscriptions.write permission
		if (strpos($e->getMessage(), '403') !== false ||
			strpos($e->getMessage(), 'Forbidden') !== false ||
			strpos($e->getMessage(), 'subscriptions.write') !== false) {
			error_log('[MOLLIE ERROR] Cannot create subscription: Missing subscriptions.write permission. User needs to re-authorize the Mollie connection to grant this permission.');
			error_log('[MOLLIE ERROR] Subscription creation failed for customer: ' . $customerMollieId);
			error_log('[MOLLIE ERROR] Payment will continue, but recurring subscription was not created. Please reconnect Mollie account in settings.');
		} else {
			// Log other errors but also don't break the payment flow
			error_log('[MOLLIE ERROR] Subscription creation failed: ' . $e->getMessage());
			error_log('[MOLLIE ERROR] Payment will continue, but recurring subscription was not created.');
		}
	}

	private static function markRecurringAsProcessed($mainAppointment)
	{
		// Mark that the recurring series has already been processed and the subscription has been added
		Customer::setData($mainAppointment->customer_id, "mollie_recurring_automatic_payment_" . $mainAppointment->recurring_id, true);
	}

	public function isRecurringServicePresent()
	{
		$appointmentRequests = $this->_appointmentRequests;
		$appointments = $appointmentRequests->appointments;

		foreach ($appointments as $appointment) {
			$timeslot = reset($appointment->timeslots); // Retrieve only the first item, as all entries are identical
			$serviceInf = $timeslot->getServiceInf();
			$serviceInf = $serviceInf->toArray();
			$serviceId = $serviceInf['id'];
			$isRecurring = \BookneticApp\Models\Service::getData($serviceId, 'automatic_recurring_payment_switch');

			if ($isRecurring) {
				// Check if the automatic recurring payment is on
				return true;
			}
		}
	}

	public static function getRecurringAppointmentPaymentIds($parentId)
	{
		$parent = Appointment::where("id", $parentId)->fetch();
		$appointments = Appointment::where("recurring_id", $parent->recurring_id)->fetchAll();
		$ids = [];
		foreach ($appointments as $appointment) {
			$ids[] = array($appointment->id);
		}
		return $ids;
	}

	public static function cancelSubscription($appointmentId, $tenantId)
	{
		$subscriptionData = json_decode(Appointment::getData($appointmentId, "recurring_automatic_payment_data_".$tenantId));

		$mollieConnect = new MollieConnectGateway();
		$mollieClient = $mollieConnect->getMollieClient();

		$tenantSettings = MollieConnectHelper::getTenantSettings($tenantId);

		error_log("appointment id: ".$appointmentId);
		error_log("tenant id: ".$tenantId);
		error_log("subscriptionData: ".print_r($subscriptionData, true));
		try {
			$mollieClient->subscriptions->cancelFor($subscriptionData->customer_id, $subscriptionData->subscription_id);
			Appointment::setData($appointmentId, "is_recurring_appointment_automatic_payment_".$tenantId, false);
			return true;
		} catch (\Exception $e) {
			error_log("Cancel mollie error: ".print_r($e, true));
			self::handleSubscriptionCreationError($e, $subscriptionData->customer_id);
		}

	}
}