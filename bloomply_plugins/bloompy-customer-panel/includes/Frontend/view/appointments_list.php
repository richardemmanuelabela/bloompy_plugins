<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use Bloompy\CustomerPanel\CustomerPanelHelper;
use Bloompy\CustomerPanel\CustomerPanelAddon;
use Bloompy\Invoices\Services\InvoiceService;
use Bloompy\CustomerPanel\Frontend\Controller;
use function Bloompy\CustomerPanel\bkntc__;

/**
 * @var $parameters array
 */
$appointmentCount = 0;
foreach ( $parameters[ 'appointments' ] as $appointment ):
	Permission::setTenantId( $appointment->tenant_id );
    $clientTimeZoneIsOpen = Helper::getOption('client_timezone_enable', 'off') == 'on';

    $duration   = (int)$appointment->ends_at - (int)$appointment->starts_at;
    $dateFormat = Helper::isSaaSVersion() ? get_option( 'date_format', 'Y-m-d' ) : Helper::getOption( 'date_format', 'Y-m-d' );

    $clientDate = Helper::isSaaSVersion() ? Date::format( get_option( 'date_format', 'Y-m-d' ), $appointment->starts_at, false, $clientTimeZoneIsOpen ) : Date::datee( $appointment->starts_at, false, $clientTimeZoneIsOpen );
    $clientTime = $duration >= 24 * 60 * 60 ? '' : ( Helper::isSaaSVersion() ? Date::timeSQL( $appointment->starts_at ,false,$clientTimeZoneIsOpen ) : Date::time( $appointment->starts_at,false,$clientTimeZoneIsOpen ) );

    $originalDate = Helper::isSaaSVersion() ? Date::format( get_option( 'date_format', 'Y-m-d' ), $appointment->starts_at ) : Date::datee( $appointment->starts_at );
    $originalTime = $duration >= 24 * 60 * 60 ? '' : ( Helper::isSaaSVersion() ? Date::timeSQL( $appointment->starts_at ) : Date::time( $appointment->starts_at ) );
	$date = new DateTime($clientDate);

    $invoiceId = Controller::get_post_id_by_postmeta( "appointment_id", $appointment->id );
    $invoice = InvoiceService::get($invoiceId);

    switch ( $appointment->payment_status_text ) {
		case 'Paid': $status = bkntc__( 'Paid' ); break;
		case 'Pending': $status = bkntc__( 'Pending' ); break;
		case 'Not paid': $status = bkntc__( 'Not paid' ); break;
		case 'Paid (deposit)': $status = bkntc__( 'Not paid' ); break;
		case 'Cancelled': $status = bkntc__( 'Cancelled' ); break;
		case 'Canceled': $status = bkntc__( 'Canceled' ); break;
		default: $status = $appointment->payment_status_text;
	}
    $companyName = Helper::getOption( 'company_name', Permission::tenantInf()->domain);
    $mobileCompanyName = (strlen($companyName) > 20) ? substr($companyName, 0, 20) .  "..." : $companyName;

	$nextAppointment = $parameters['appointments'][$appointmentCount + 1] ?? null;
	$currentAppointment = $parameters['appointments'][$appointmentCount];

	$isParentRecurringNotPaid = ($nextAppointment && $nextAppointment->payment_status !== 'paid' && $appointment->payment_id == $nextAppointment->payment_id)?"disabled":"";



	?>
	<tr data-id="<?php echo $appointment->id; ?>" data-tenant-id="<?php echo $appointment->tenant_id; ?>" data-date="<?php echo $clientDate; ?>" data-date-original="<?php echo $originalDate; ?>" data-time="<?php echo $clientTime; ?>" data-time-original="<?php echo $originalTime; ?>" data-date-format="<?php echo $dateFormat; ?>" data-datebased="<?php echo (int) ( $appointment->duration >= 24 * 60 ); ?>">
		<td class="pl-4 hide-on-mobile"><?php echo htmlspecialchars( $appointment->id ); ?></td>

		<td class="td_datetime mobile-view cp-appointment-date-mobile">
			<div class="cp-appointment-date-mobile-wrapper"><?php echo $date->format( 'd-m-Y' ) . ' ' . $clientTime; ?></div>
			<span class="date-icon"></span>
			<?php if ( CustomerPanelHelper::canRescheduleAppointment( $appointment ) ) : ?>
				<button class="booknetic_reschedule_btn" type="button" title="<?php echo bkntc__( 'Reschedule' ); ?>"></button>
			<?php endif; ?>
		</td>

		<?php if ( Helper::isSaaSVersion() ) : ?>
            <td class="cp-appointment-company-mobile" title="<?php echo htmlspecialchars( $companyName ); ?>"><a class="booknetic_company_link" target="_blank" href="<?php echo CustomerPanelHelper::getCompanyLink() ?>"><?php echo htmlspecialchars( $mobileCompanyName ); ?></a></td>
		<?php endif; ?>

		<td class="hide-on-mobile">
            <?php echo htmlspecialchars( $appointment->service_name ); ?><br/>
        </td>
		<td class="hide-on-mobile"><?php echo htmlspecialchars( $appointment->staff_name ); ?></td>
		<td class="hide-on-mobile"><?php echo htmlspecialchars( $appointment->location_name ); ?></td>

		<td class="td_datetime hide-on-mobile cp-appointment-date">
			<?php echo $date->format( 'd-m-Y' ) . ' ' . $clientTime; ?>
			<?php if ( CustomerPanelHelper::canRescheduleAppointment( $appointment ) ) : ?>
				<button class="booknetic_reschedule_btn" type="button" title="<?php echo bkntc__( 'Reschedule' ); ?>"></button>
			<?php endif; ?>
		</td>

		<td class="hide-on-mobile">
			<div class="cp-total-price"><?php echo Helper::price( $appointment->total_price ); ?></div>
		</td>

		<td class="hide-on-mobile">
			<?php if ( ! empty( $invoice ) ) : ?>
				<a href="#" class="invoice-download-link" appointment_id="<?php echo $appointment->id; ?>">
					<img src="<?php echo CustomerPanelAddon::loadAsset( 'assets/icons/cp-invoice.png' ); ?>">
				</a>
			<?php endif; ?>
		</td>

		<td class="hide-on-mobile">
			<?php if ( Helper::getOption( 'hide_pay_now_btn_customer_panel', 'off' ) === 'off' && ( $appointment->total_price != $appointment->paid_amount || $appointment->payment_status === 'pending' ) && ! in_array( $appointment->status, [ 'canceled', 'rejected' ] ) ) : ?>
				<button class="booknetic_pay_now_btn" type="button" title="<?php echo bkntc__( 'Pay Now' ); ?>" data-tenant-id="<?php echo $appointment->tenant_id; ?>" <?php echo $isParentRecurringNotPaid;?>><?php echo bkntc__( 'Pay now' ); ?></button>
			<?php elseif ( in_array( $appointment->status, [ 'canceled', 'rejected' ] ) ) : ?>
				<span class="booknetic_appointment_status_all gray-disc"><?php echo bkntc__( 'Pay' ); ?></span>
			<?php else : ?>
				<span class="booknetic_appointment_status_all green-disc"><?php echo bkntc__( 'Paid' ); ?></span>
			<?php endif; ?>
		</td>

		<td class="hide-on-mobile"><?php echo Helper::secFormat( $duration ); ?></td>

		<td class="booknetic_appointment_status_td hide-on-mobile">
			<span class="booknetic_appointment_status_all"><?php echo htmlspecialchars( $appointment->status_text ) ?></span>
		</td>

		<td class="cp-link">
			<?php do_action( 'bkntc_customer_panel_appointment_actions', $appointment->id ); ?>
			<?php if ( CustomerPanelHelper::canChangeAppointmentStatus( $appointment ) ) : ?>
				<button class="booknetic_change_status_btn" type="button" title="<?php echo bkntc__( 'Change Status' ); ?>"><i class="fa fa-exchange-alt"></i></button>
			<?php endif; ?>
		</td>

		<td class="cp-column-hide-on-desktop cp-mobile-dropdown-tr">
			<a href="#" class="cp-mobile-dropdown" appointment_id="<?php echo htmlspecialchars( $appointment->id ); ?>" action="cp-dropdown-down"></a>
		</td>
	</tr>

	<tr class="cp-mobile-dropdown-tr" id="cp-mobile-dropdown-tr-<?php echo htmlspecialchars( $appointment->id ); ?>" data-id="<?php echo $appointment->id; ?>" data-tenant-id="<?php echo $appointment->tenant_id; ?>" data-date="<?php echo $clientDate; ?>" data-date-original="<?php echo $originalDate; ?>" data-time="<?php echo $clientTime; ?>" data-time-original="<?php echo $originalTime; ?>" data-date-format="<?php echo $dateFormat; ?>" data-datebased="<?php echo (int) ( $appointment->duration >= 24 * 60 ); ?>">
		<td colspan="10" class="cp-dropdown">

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Service' ); ?></div>
				<div class="cp-dropdown-value"><?php echo htmlspecialchars( $appointment->service_name ); ?></div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Personeel' ); ?></div>
				<div class="cp-dropdown-value"><?php echo htmlspecialchars( $appointment->staff_name ); ?></div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Locatie' ); ?></div>
				<div class="cp-dropdown-value"><?php echo htmlspecialchars( $appointment->location_name ); ?></div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Prijs' ); ?></div>
				<div class="cp-dropdown-value"><div class="cp-total-price"><?php echo Helper::price( $appointment->total_price ); ?></div></div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Invoice' ); ?></div>
				<div class="cp-dropdown-value">
					<?php if ( ! empty( $invoice ) ) : ?>
						<a href="#" class="invoice-download-link" appointment_id="<?php echo $appointment->id; ?>"><img src="<?php echo CustomerPanelAddon::loadAsset( 'assets/icons/cp-invoice.png' ); ?>"></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Duur' ); ?></div>
				<div class="cp-dropdown-value"><?php echo Helper::secFormat( $duration ); ?></div>
			</div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( 'Payment' ); ?></div>
				<div class="cp-dropdown-value">
					<?php if ( Helper::getOption( 'hide_pay_now_btn_customer_panel', 'off' ) === 'off' && $appointment->total_price != $appointment->paid_amount && ! in_array( $appointment->status, [ 'canceled', 'rejected' ] ) ) : ?>
						<button class="booknetic_pay_now_btn" type="button" title="<?php echo bkntc__( 'Pay Now' ); ?>" data-tenant-id="<?php echo $appointment->tenant_id; ?>" <?php echo $isParentRecurringNotPaid;?>><?php echo bkntc__( 'Pay now' ); ?></button>
					<?php elseif ( in_array( $appointment->status, [ 'canceled', 'rejected' ] ) ) : ?>
						<span class="booknetic_appointment_status_all gray-disc"><?php echo bkntc__( 'Pay' ); ?></span>
					<?php else : ?>
						<span class="booknetic_appointment_status_all green-disc"><?php echo bkntc__( 'Paid' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
            <div class="cp-mobile-dropdown-wrapper">
                <div class="cp-dropdown-field"><?php echo bkntc__( 'Status' ); ?></div>
                <div class="cp-dropdown-value"><?php echo htmlspecialchars( $appointment->status_text ) ?></div>
            </div>

			<div class="cp-mobile-dropdown-wrapper">
				<div class="cp-dropdown-field"><?php echo bkntc__( '#' ); ?></div>
				<div class="cp-dropdown-value"><?php echo htmlspecialchars( $appointment->id ); ?></div>
			</div>

		</td>
	</tr>
<?php $appointmentCount++;?>
<?php endforeach; ?>
