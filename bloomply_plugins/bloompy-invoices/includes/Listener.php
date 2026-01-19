<?php

namespace Bloompy\Invoices;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Service;
use BookneticApp\Models\Customer;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\Invoices\Constants\InvoiceConstants;
use Exception;

/**
 * Event listener class for Bloompy Invoices
 */
class Listener
{
    /**
     * Handle appointment creation for local payments only (cash/pay on arrival)
     */
    public static function onAppointmentCreatedLocalPayment($appointmentData)
    {


        try {
            // Get appointment details
            $appointmentId = $appointmentData->appointmentId ?? $appointmentData->id ?? $appointmentData->appointment_id ?? null;



            if (!$appointmentId) {
                error_log('ERROR: No appointment ID found!');
                return;
            }

            $appointment = Appointment::get($appointmentId);
            if (!$appointment) {
                error_log('ERROR: Appointment not found in database');
                return;
            }

            // Only create invoices for local payments (cash/pay on arrival)
            if ($appointment->payment_method !== 'local') {

                return;
            }



            // Create invoice for local payment (always pending)
            self::createInvoiceForAppointment($appointmentId, 'pending', $appointmentData);

        } catch (Exception $e) {
            error_log("Bloompy Invoices: Error creating invoice for appointment {$appointmentId}: " . $e->getMessage());
        }
    }

    /**
     * Handle payment confirmation - create invoice after successful payment
     */
    public static function onPaymentConfirmed($appointmentId)
    {


        // Safety check: Ensure required Booknetic classes are available
        if (!class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            error_log('Bloompy Invoices: BookneticApp\\Providers\\Core\\Permission class not found, skipping invoice creation');
            return;
        }

        try {
            // Check if invoice already exists for this appointment
            $existingInvoice = self::getInvoiceByAppointmentId($appointmentId);

            if ($existingInvoice) {
                error_log("Bloompy Invoices: Invoice already exists for appointment {$appointmentId}, skipping creation");
                return;
            }

			// Create invoice now that payment is confirmed
			self::createInvoiceForAppointment($appointmentId, 'paid');


        } catch (Exception $e) {
            error_log("Bloompy Invoices: Error creating invoice after payment confirmation for appointment {$appointmentId}: " . $e->getMessage());
        }
    }

    /**
     * Create invoice for an appointment with specified status
     */
    private static function createInvoiceForAppointment($appointmentId, $status = 'paid', $appointmentData = null)
    {
		try {

			// Get appointment details if not provided
			$appointment = Appointment::get($appointmentId);
			if (!$appointment) {
				error_log("ERROR: Appointment {$appointmentId} not found");
				return false;
			}

			// Get service details
			$service = Service::get($appointment->service_id);
			if (!$service) {
				error_log("ERROR: Service not found for appointment {$appointmentId}");
				return false;
			}

			// Get customer details
			$customer = Customer::get($appointment->customer_id);
			if (!$customer) {
				error_log("ERROR: Customer not found for appointment {$appointmentId}");
				return false;
			}

			// Get detailed pricing information
			$pricingData = self::computeAppointmentPrices($appointmentId);

			// Skip invoice creation if total amount is 0 (free appointments)
			if (empty($pricingData['total_amount']) || $pricingData['total_amount'] <= 0) {
				error_log("Bloompy Invoices: Skipping invoice creation for appointment {$appointmentId} - total amount is {$pricingData['total_amount']} (free appointment)");
				return false;
			}

			// Get complete company information from all available sources
			$companyInfo = self::getCompleteCompanyInfo(null, $appointmentId);

			// Get service extras
			$extrasData = self::getAppointmentExtras($appointmentId);

			// Set payment date if status is paid
			$paymentDate = ($status === 'paid') ? current_time('mysql') : '';

			// Calculate due date (30 days from invoice date for unpaid invoices)
			$dueDate = ($status === 'paid') ? $paymentDate : date('Y-m-d H:i:s', strtotime('+30 days'));

			// Prepare invoice data
			$invoiceData = [
				'invoice_number' => InvoiceService::generateInvoiceNumber('customer'),
				'appointment_id' => $appointmentId,
				'customer_id' => $customer->id,
				'customer_email' => $customer->email,
				'customer_name' => $customer->first_name . ' ' . $customer->last_name,
				'customer_phone' => $customer->phone_number,
				'service_id' => $appointment->service_id,
				'service_name' => $service->name,
				'service_price' => $pricingData['service_price'],
				'service_duration' => $service->duration,
				'service_extras' => $extrasData,
				'appointment_date' => $appointment->starts_at,
				'due_date' => $dueDate,
				'customer_company_name' => $companyInfo['customer_company_name'] ?? '',
				'customer_company_address' => $companyInfo['customer_address'] ?? '',
				'customer_company_zipcode' => $companyInfo['customer_zipcode'] ?? '',
				'customer_company_city' => $companyInfo['customer_city'] ?? '',
				'customer_company_country' => $companyInfo['customer_country'] ?? '',
				'customer_company_iban' => $companyInfo['customer_iban'] ?? '',
				'customer_company_kvk_number' => $companyInfo['customer_kvk_number'] ?? '',
				'customer_company_btw_number' => $companyInfo['customer_btw_number'] ?? '',
				'company_name' => $companyInfo['company_name'] ?? '',
				'company_address' => $companyInfo['address'] ?? '',
				'company_zipcode' => $companyInfo['zipcode'] ?? '',
				'company_city' => $companyInfo['city'] ?? '',
				'company_country' => $companyInfo['country'] ?? '',
				'company_phone' => $companyInfo['phone'] ?? '',
				'company_iban' => $companyInfo['iban'] ?? '',
				'company_kvk_number' => $companyInfo['kvk_number'] ?? '',
				'company_btw_number' => $companyInfo['btw_number'] ?? '',
				'company_footer_text' => $companyInfo['footer_text'] ?? '',
				'company_logo' => $companyInfo['logo'] ?? '',
				'subtotal' => $pricingData['subtotal'],
				'tax_amount' => $pricingData['tax_amount'],
				'total_amount' => $pricingData['total_amount'],
				'currency' => Helper::getOption('currency', 'EUR'),
				'invoice_date' => current_time('mysql'),
				'status' => $status,
				'payment_date' => $paymentDate,
				'source' => 'booknetic',
				'pricing_breakdown' => $pricingData['breakdown'],
				'number_of_appointments' => $pricingData['number_of_appointments']
			];

			// Create the invoice
			$invoiceId = InvoiceService::create($invoiceData);
			if ($invoiceId) {
				return $invoiceId;
			} else {
				error_log("ERROR: Failed to create invoice for appointment {$appointmentId}");
				return false;
			}
        } catch (Exception $e) {
            error_log("Bloompy Invoices: Error creating invoice for appointment {$appointmentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get detailed pricing data from appointment
     */
    private static function getAppointmentPricingPerAppointment($appointmentId, $appointmentData = null)
    {
        // If we have appointment data, use the pricing from there first
        if ($appointmentData && isset($appointmentData->prices)) {

            return self::extractPricingFromAppointmentData($appointmentData);
        }

        // Fallback: Get all appointment prices from database

        $appointmentPrices = AppointmentPrice::where('appointment_id', $appointmentId)->fetchAll();

        $subtotal = 0;
        $taxAmount = 0;
        $servicePrice = 0;
        $breakdown = [];

        foreach ($appointmentPrices as $price) {
            $priceValue = $price->price * $price->negative_or_positive;

            // Store in breakdown for detailed records
            $breakdown[] = [
                'key' => $price->unique_key,
                'price' => $priceValue,
                'type' => self::getPriceType($price->unique_key)
            ];

            // Categorize prices
            if (strpos($price->unique_key, 'tax-') === 0) {
                // This is a tax price
                $taxAmount += $priceValue;
            } elseif ($price->unique_key === 'service' || $price->unique_key === 'service_price') {
                // Main service price
                $servicePrice = $priceValue;
                $subtotal += $priceValue;
            } elseif (strpos($price->unique_key, 'service_extra-') === 0) {
                // Service extra
                $subtotal += $priceValue;
            } elseif (strpos($price->unique_key, 'coupon-') === 0) {
                // Coupon discount (usually negative)
                $subtotal += $priceValue;
            } else {
                // Other fees (staff, location, etc.)
                $subtotal += $priceValue;
            }
        }

        $totalAmount = $subtotal + $taxAmount;

        return [
            'service_price' => $servicePrice,
            'subtotal' => max(0, $subtotal), // Ensure non-negative
            'tax_amount' => max(0, $taxAmount), // Ensure non-negative
            'total_amount' => max(0, $totalAmount), // Ensure non-negative
            'breakdown' => $breakdown
        ];
    }

    /**
     * Extract pricing data from AppointmentRequestData object
     */
    private static function extractPricingFromAppointmentData($appointmentData)
    {
        $subtotal = 0;
        $taxAmount = 0;
        $servicePrice = 0;
        $breakdown = [];

        foreach ($appointmentData->prices as $key => $priceObject) {
            $price = $priceObject->getPrice();
            $negativeOrPositive = $priceObject->getNegativeOrPositive();
            $priceValue = $price * $negativeOrPositive;

            $breakdown[] = [
                'key' => $key,
                'price' => $priceValue,
                'type' => self::getPriceType($key),
                'label' => $priceObject->getLabel()
            ];

            if (strpos($key, 'tax-') === 0) {
                $taxAmount += $priceValue;
            } elseif ($key === 'service_price' || $key === 'service') {
                $servicePrice = $priceValue;
                $subtotal += $priceValue;
            } elseif (strpos($key, 'service_extra-') === 0) {
                $subtotal += $priceValue;
            } elseif (strpos($key, 'coupon-') === 0) {
                $subtotal += $priceValue;
            } elseif ($key !== 'discount') { // Don't add discount to subtotal
                $subtotal += $priceValue;
            }
        }

        $totalAmount = $subtotal + $taxAmount;



        return [
            'service_price' => $servicePrice,
            'subtotal' => max(0, $subtotal),
            'tax_amount' => max(0, $taxAmount),
            'total_amount' => max(0, $totalAmount),
            'breakdown' => $breakdown
        ];
    }

    /**
     * Get appointment extras information
     */
    private static function getAppointmentExtras($appointmentId)
    {
        $extras = AppointmentExtra::where('appointment_id', $appointmentId)->fetchAll();
        $extrasData = [];

        foreach ($extras as $extra) {
            $extrasData[] = [
                'extra_id' => $extra->extra_id,
                'quantity' => $extra->quantity,
                'price' => $extra->price,
                'duration' => $extra->duration
            ];
        }

        return $extrasData;
    }

    /**
     * Determine price type from unique key
     */
    private static function getPriceType($uniqueKey)
    {
        if (strpos($uniqueKey, 'tax-') === 0) {
            return 'tax';
        } elseif ($uniqueKey === 'service') {
            return 'service';
        } elseif (strpos($uniqueKey, 'service_extra-') === 0) {
            return 'extra';
        } elseif (strpos($uniqueKey, 'coupon-') === 0) {
            return 'coupon';
        } elseif (strpos($uniqueKey, 'staff-') === 0) {
            return 'staff';
        } elseif (strpos($uniqueKey, 'location-') === 0) {
            return 'location';
        }
        return 'other';
    }

    /**
     * Get company information from appointment data
     */
    private static function getCompleteCompanyInfo($appointmentData, $appointmentId)
    {
        if (!$appointmentId) {
            return self::getEmptyCompanyInfo();
        }

        // Get tenant form data from appointment meta
        $formData = self::getAppointmentFormData($appointmentId);
		$formDataCompanyInfo = [];
        if (!empty($formData['bedrijf'])) {
			$formDataCompanyInfo =  self::mapFormDataToCompanyInfo($formData);
        }

        // Add tenant settings info
        $tenantSettingsInfo = self::getTenantCompanyInfo();

		return array_merge($formDataCompanyInfo, $tenantSettingsInfo);
    }

    /**
     * Get form data from appointment meta
     */
    private static function getAppointmentFormData($appointmentId)
    {
        return [
            'bedrijf' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_bedrijf'),
            'adres' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_adres'),
            'postcode' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_postcode'),
            'stad' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_stad'),
            'land' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_land'),
            'telefoon' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_telefoon'),
            'iban' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_iban'),
            'kvk' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_kvk'),
            'btw' => \BookneticApp\Models\Appointment::getData($appointmentId, 'bloompy_tenant_form_btw'),
        ];
    }

    /**
     * Map form data to company info structure
     */
    private static function mapFormDataToCompanyInfo($formData)
    {
        return [
            'customer_company_name' => $formData['bedrijf'] ?? '',
            'customer_address' => $formData['adres'] ?? '',
            'customer_zipcode' => $formData['postcode'] ?? '',
            'customer_city' => $formData['stad'] ?? '',
            'customer_country' => $formData['land'] ?? '',
            'customer_phone' => $formData['telefoon'] ?? '',
            'customer_iban' => $formData['iban'] ?? '',
            'customer_kvk_number' => $formData['kvk'] ?? '',
            'customer_btw_number' => $formData['btw'] ?? '',
            //'footer_text' => ''
        ];
    }

    /**
     * Get empty company info structure
     */
    private static function getEmptyCompanyInfo()
    {
        return [
            'customer_company_name' => '',
            'customer_address' => '',
            'customer_zipcode' => '',
            'customer_city' => '',
            'customer_country' => '',
            'customer_phone' => '',
            'customer_iban' => '',
            'customer_kvk_number' => '',
            'customer_btw_number' => '',
			'company_name' => '',
			'address' => '',
			'zipcode' => '',
			'city' => '',
			'country' => '',
			'phone' => '',
			'iban' => '',
			'kvk_number' => '',
			'btw_number' => '',
            'footer_text' => '',
            'logo' => ''
        ];
    }

    /**
     * Get company information from tenant settings or WordPress options
     */
    public static function getTenantCompanyInfo()
    {
        $tenant_id = 0;
        if (class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            $tenant_id = \BookneticApp\Providers\Core\Permission::tenantId();
        }


        if ($tenant_id > 0 && class_exists('\BookneticSaaS\Models\Tenant')) {
            // SaaS installation - get from tenant data
            return [
                'company_name' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_name') ?: '',
                'address' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_address') ?: '',
                'zipcode' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_zipcode') ?: '',
                'city' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_city') ?: '',
                'country' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_country') ?: '',
                'phone' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_phone') ?: '',
                'iban' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_iban') ?: '',
                'kvk_number' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_kvk_number') ?: '',
                'btw_number' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_btw_number') ?: '',
                'footer_text' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_footer_text') ?: '',
                'logo' => \BookneticSaaS\Models\Tenant::getData($tenant_id, 'invoice_company_logo') ?: ''
            ];
        } else {
            // Non-SaaS installation - get from WordPress options
            return [
                'company_name' => get_option('bloompy_invoice_company_name', ''),
                'address' => get_option('bloompy_invoice_company_address', ''),
                'zipcode' => get_option('bloompy_invoice_company_zipcode', ''),
                'city' => get_option('bloompy_invoice_company_city', ''),
                'country' => get_option('bloompy_invoice_company_country', ''),
                'phone' => get_option('bloompy_invoice_company_phone', ''),
                'iban' => get_option('bloompy_invoice_company_iban', ''),
                'kvk_number' => get_option('bloompy_invoice_company_kvk_number', ''),
                'btw_number' => get_option('bloompy_invoice_company_btw_number', ''),
                'footer_text' => get_option('bloompy_invoice_company_footer_text', ''),
                'logo' => get_option('bloompy_invoice_company_logo', '')
            ];
        }
    }



    /**
     * Register shortcodes for email workflows
     */
    public static function registerShortCodes($shortCodeService)
    {
        $shortCodeService->registerCategory('bloompy_invoice', bkntc__('Invoice Info'));

        $shortCodeService->registerShortCode('invoice_link', [
            'name' => bkntc__('Invoice Link'),
            'category' => 'bloompy_invoice',
            'depends' => 'appointment_id'
        ]);

        $shortCodeService->registerShortCode('invoice_number', [
            'name' => bkntc__('Invoice Number'),
            'category' => 'bloompy_invoice',
            'depends' => 'appointment_id'
        ]);

        $shortCodeService->registerShortCode('invoice_amount', [
            'name' => bkntc__('Invoice Amount'),
            'category' => 'bloompy_invoice',
            'depends' => 'appointment_id'
        ]);

        $shortCodeService->registerShortCode('invoice_date', [
            'name' => bkntc__('Invoice Date'),
            'category' => 'bloompy_invoice',
            'depends' => 'appointment_id'
        ]);
    }

    /**
     * Replace shortcodes in email workflows
     */
	public static function replaceShortCodes($text, $eventData, $shortCodeService)
	{
		// Only process if text contains our shortcodes
		if (strpos($text, '{invoice_') === false) {
			return $text;
		}

		$appointmentId = $eventData['appointment_id'] ?? null;
		if (!$appointmentId) {
			// Remove invoice shortcodes if no appointment ID
			$text = str_replace(['{invoice_link}', '{invoice_number}', '{invoice_amount}', '{invoice_date}'], '', $text);
			return $text;
		}

		// Get invoice for this appointment
		$invoice = self::getInvoiceByAppointmentId($appointmentId);
		if (!$invoice) {
			// Remove invoice shortcodes if no invoice exists (e.g., free appointments)
			$text = str_replace(['{invoice_link}', '{invoice_number}', '{invoice_amount}', '{invoice_date}'], '', $text);
			return $text;
		}

		// Replace shortcodes with actual values
		$replacements = [
			'{invoice_link}' => self::generateInvoiceViewUrl($invoice['invoice_number'], $invoice['customer_email']),
			'{invoice_number}' => $invoice['invoice_number'],
			'{invoice_amount}' => Helper::price($invoice['total_amount']),
			'{invoice_date}' => Date::dateSQL($invoice['invoice_date'])
		];

		foreach ($replacements as $shortcode => $replacement) {
			$text = str_replace($shortcode, $replacement, $text);
		}

		return $text;
	}

    /**
     * Get invoice by appointment ID
     */
    private static function getInvoiceByAppointmentId($appointmentId)
    {
        global $wpdb;

        // Get current tenant ID for filtering
        $tenant_id = 0;
        if (class_exists('BookneticApp\\Providers\\Core\\Permission')) {
            $tenant_id = \BookneticApp\Providers\Core\Permission::tenantId();
        }

        $query = $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_tenant ON p.ID = pm_tenant.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = 'appointment_id'
            AND pm.meta_value = %s
            AND pm_tenant.meta_key = 'tenant_id'
            AND pm_tenant.meta_value = %s
            LIMIT 1
        ", InvoiceConstants::POST_TYPE, $appointmentId, $tenant_id);

        $postId = $wpdb->get_var($query);

        if ($postId) {
            return InvoiceService::get($postId);
        }

        return null;
    }

    /**
     * Generate invoice view URL
     */
    private static function generateInvoiceViewUrl($invoiceNumber, $customerEmail)
    {
        return \Bloompy\Invoices\Frontend\InvoiceViewer::generateInvoiceUrl($invoiceNumber, $customerEmail);
    }

    /**
     * Generate secure token for invoice access
     */
    public static function generateInvoiceToken($invoiceNumber, $customerEmail)
    {
        $salt = wp_salt('auth');
        return hash('sha256', $invoiceNumber . $customerEmail . $salt);
    }

    /**
     * Verify invoice access token
     */
    public static function verifyInvoiceToken($invoiceNumber, $customerEmail, $token)
    {
        $expectedToken = self::generateInvoiceToken($invoiceNumber, $customerEmail);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Get recurring appointments for a given appointment
     */
    public static function getRecurringAppointments($appointments, $appointmentId)
    {
        $appointment = Appointment::get($appointmentId);
        $appointmentData = self::getRecurringAppointmentsId($appointment);

        foreach ($appointmentData as $appointment) {
            $appointments[] = $appointment;
        }
        return $appointments;
    }

    /**
     * Get appointments for invoice creation (handles recurring appointments)
     */
    public static function getAppointmentsForInvoice($appointmentId)
    {
        $appointment = Appointment::get($appointmentId);
        $service = Service::get($appointment->service_id);
        $appointmentIds = [];

        if ($service->recurring_payment_type == "full") {
            // Create invoice now that payment is confirmed
            $appointments = self::getRecurringAppointments($appointmentIds, $appointmentId);
        } else {
            $appointments[] = $appointment;
        }
        return $appointments;
    }

    /**
     * Get all recurring appointments by recurring ID
     */
    public static function getRecurringAppointmentsId($appointment)
    {
        return Appointment::where("recurring_id", $appointment->recurring_id)
            ->where("payment_status", "paid")
            ->fetchAll();
    }

    /**
     * Compute appointment prices for invoice creation
     */
    public static function computeAppointmentPrices($appointmentId)
    {
        $appointments = self::getAppointmentsForInvoice($appointmentId);
        $subtotal = 0;
        $taxAmount = 0;
        $totalAmount = 0;
        $breakdown = [];
        $numberOfAppointments = 0;
        $servicePrice = "";

        foreach ($appointments as $appointment) {
            $appointmentPrices = self::getAppointmentPricingPerAppointment($appointment->id, null);
            if (self::isAppointmentExistInPostmeta($appointment->id)) {
                continue;
            }
            if ($appointment->paid_amount == 0) {
                continue;
            }
            $subtotal += $appointmentPrices['subtotal'];
            $taxAmount += $appointmentPrices['tax_amount'];
            $totalAmount += $appointmentPrices['total_amount'];
            $breakdown[] = $appointmentPrices['breakdown'];
            $servicePrice = $appointmentPrices['service_price'];
            $numberOfAppointments++;
        }

        return [
            'service_price' => $servicePrice,
            'subtotal' => max(0, $subtotal),
            'tax_amount' => max(0, $taxAmount),
            'total_amount' => max(0, $totalAmount),
            'breakdown' => $breakdown,
            'number_of_appointments' => $numberOfAppointments
        ];
    }

    /**
     * Check if appointment already exists in postmeta
     */
    public static function isAppointmentExistInPostmeta($appointmentId)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                "appointment_id",
                $appointmentId
            )
        );
    }
} 