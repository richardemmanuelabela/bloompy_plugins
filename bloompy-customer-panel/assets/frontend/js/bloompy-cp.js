(function($)
{
	"use strict";
	function __( key )
	{
		return key in BookneticDataCP.localization ? BookneticDataCP.localization[ key ] : key;
	}
	$(document).ready( function()
	{
		let initCustomerPanelPage = function( value )
		{
			var customer_panel_js = $( value );
			var booknetic = {

				options: {
					'templates': {
						'loader': '<div class="booknetic_loading_layout"></div>',
						'toast': '<div id="booknetic-toastr"><div class="booknetic-toast-img"><img></div><div class="booknetic-toast-details"><span class="booknetic-toast-description"></span></div><div class="booknetic-toast-remove"><i class="fa fa-times"></i></div></div>'
					}
				},

				localization: {
					month_names: [ __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December') ],
					day_of_week: [ __('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun') ] ,
				},

				panel_js: customer_panel_js,

				loadAppointmentsList: function()
				{
					let tableBody = customer_panel_js.find( "#booknetic_customer_panel_appointments_table" );
					if ( ! tableBody.attr( "data-load-appointments" ) ) {
						return
					}
					let data  = new FormData();
					data.append('client_time_zone' ,booknetic.timeZoneOffset());
					booknetic.ajax( 'get_appointments_list', data, function ( result )
					{
						tableBody.find('tbody').html( result['list_html'] );
					});
				},


				loadAvailableDate: function(instance , appointmentId )
				{
					instance.set('enable',[]);

					let data  = new FormData();
					data.append('appointment_id' , appointmentId);
					data.append('client_time_zone' ,booknetic.timeZoneOffset());
					data.append('current_month' , (instance.currentMonth + 1).toString().padStart(2,'0') ) ;
					data.append('current_year' , instance.currentYear ) ;
					booknetic.ajax( 'get_available_dates', data, function ( result )
					{
						instance.set('enable',result['available_dates']);
					});
				},


				parseHTML: function ( html )
				{
					var range = document.createRange();
					var documentFragment = range.createContextualFragment( html );
					return documentFragment;
				},

				loading: function ( onOff )
				{
					if( typeof onOff === 'undefined' || onOff )
					{
						$('#booknetic_progress').removeClass('booknetic_progress_done').show();
						$({property: 0}).animate({property: 100}, {
							duration: 1000,
							step: function()
							{
								var _percent = Math.round(this.property);
								if( !$('#booknetic_progress').hasClass('booknetic_progress_done') )
								{
									$('#booknetic_progress').css('width',  _percent+"%");
								}
							}
						});

						$('body').append( this.options.templates.loader );
					}
					else if( ! $('#booknetic_progress').hasClass('booknetic_progress_done') )
					{
						$('#booknetic_progress').addClass('booknetic_progress_done').css('width', 0);

						// IOS bug...
						setTimeout(function ()
						{
							$('.booknetic_loading_layout').remove();
						}, 0);
					}
				},

				htmlspecialchars_decode: function (string, quote_style)
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined')
					{
						quote_style = 2;
					}
					string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
					var OPTS ={
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++){
							if(OPTS[quote_style[i]]===0){
								noquotes = true;
							} else if(OPTS[quote_style[i]]){
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/&#0*39;/g, "'");
					}
					if(!noquotes){
						string = string.replace(/&quot;/g, '"');
					}
					string = string.replace(/&amp;/g, '&');
					return string;
				},

				htmlspecialchars: function ( string, quote_style, charset, double_encode )
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined' || quote_style===null)
					{
						quote_style = 2;
					}
					string = typeof string != 'string' ? '' : string;

					string = string.toString();
					if(double_encode !== false){
						string = string.replace(/&/g, '&amp;');
					}
					string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
					var OPTS = {
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++)
						{
							if(OPTS[quote_style[i]]===0)
							{
								noquotes = true;
							}
							else if(OPTS[quote_style[i]])
							{
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/'/g, '&#039;');
					}
					if(!noquotes)
					{
						string = string.replace(/"/g, '&quot;');
					}
					return string;
				},

				ajaxResultCheck: function ( res )
				{

					if( typeof res != 'object' )
					{
						try
						{
							res = JSON.parse(res);
						}
						catch(e)
						{
							this.toast( 'Error!', 'unsuccess' );
							return false;
						}
					}

					if( typeof res['status'] == 'undefined' )
					{
						this.toast( 'Error!', 'unsuccess' );
						return false;
					}

					if( res['status'] == 'error' )
					{
						this.toast( typeof res['error_msg'] == 'undefined' ? 'Error!' : res['error_msg'], 'unsuccess' );
						return false;
					}

					if( res['status'] == 'ok' )
						return true;

					// else

					this.toast( 'Error!', 'unsuccess' );
					return false;
				},

				ajax: function ( action , params , func , loading, fnOnError )
				{
					loading = loading === false ? false : true;

					if( loading )
					{
						booknetic.loading(true);
					}

					if( params instanceof FormData)
					{
						params.append('action', 'bkntc_' + action);
					}
					else
					{
						params['action'] = 'bkntc_' + action;
					}

					params = bookneticHooks.doFilter( 'ajax_' + action, params );

					var ajaxObject =
						{
							url: BookneticDataCP.ajax_url,
							method: 'POST',
							data: params,
							success: function ( result )
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								if( booknetic.ajaxResultCheck( result, fnOnError ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}
									if( typeof func == 'function' )
										func( result );
								}
								else if( typeof fnOnError == 'function' )
								{
									fnOnError();
								}
							},
							error: function (jqXHR, exception)
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								booknetic.toast( jqXHR.status + ' error!' );

								if( typeof fnOnError == 'function' )
								{
									fnOnError();
								}
							}
						};

					if( params instanceof FormData)
					{
						ajaxObject['processData'] = false;
						ajaxObject['contentType'] = false;
					}

					$.ajax( ajaxObject );

				},

				select2Ajax: function ( select, action, parameters )
				{
					var params = {};
					params['action'] = 'bkntc_' + action;

					select.select2({
						theme: 'bootstrap',
						placeholder: __('select'),
						language: {
							searching: function() {
								return __('searching');
							}
						},
						allowClear: true,
						ajax: {
							url: BookneticDataCP.ajax_url,
							dataType: 'json',
							type: "POST",
							data: function ( q )
							{
								var sendParams = params;
								sendParams['q'] = q['term'];

								if( typeof parameters == 'function' )
								{
									var additionalParameters = parameters( $(this) );

									for (var key in additionalParameters)
									{
										sendParams[key] = additionalParameters[key];
									}
								}
								else if( typeof parameters == 'object' )
								{
									for (var key in parameters)
									{
										sendParams[key] = parameters[key];
									}
								}

								return sendParams;
							},
							processResults: function ( result )
							{
								if( booknetic.ajaxResultCheck( result ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}

									return result;
								}
							}
						}
					});
				},

				zeroPad: function(n, p)
				{
					p = p > 0 ? p : 2;
					n = String(n);
					return n.padStart(p, '0');
				},

				toastTimer: 0,

				toast: function(title , type , duration )
				{
					$("#booknetic-toastr").remove();

					if( this.toastTimer )
						clearTimeout(this.toastTimer);

					$("body").append(this.options.templates.toast);

					$("#booknetic-toastr").hide().fadeIn(300);

					type = type === 'unsuccess' ? 'unsuccess' : 'success';

					$("#booknetic-toastr .booknetic-toast-img > img").attr('src', BookneticDataCP.assets_url + 'icons/' + type + '.svg');

					$("#booknetic-toastr .booknetic-toast-description").text(title);

					duration = typeof duration != 'undefined' ? duration : 1000 * ( title.length > 48 ? parseInt(title.length / 12) : 4 );

					this.toastTimer = setTimeout(function()
					{
						$("#booknetic-toastr").fadeOut(200 , function()
						{
							$(this).remove();
						});
					} , typeof duration != 'undefined' ? duration : 4000);
				},

				timeZoneOffset: function()
				{
					if( BookneticDataCP.client_time_zone == 'off' )
						return  '-';

					if ( window.Intl && typeof window.Intl === 'object' )
					{
						return Intl.DateTimeFormat().resolvedOptions().timeZone;
					}
					else
					{
						return new Date().getTimezoneOffset();
					}
				},

				reformatTimeFromCustomFormat: function ( time )
				{
					let parts = time.match( /^([0-9]{1,2}):([0-9]{1,2})\s(am|pm)$/i );

					if ( parts )
					{
						let hours = parseInt( parts[ 1 ] );
						let minutes = parseInt( parts[ 2 ] );
						let ampm = parts[ 3 ].toLowerCase();

						if ( ampm === 'pm' && hours < 12 ) hours += 12;
						if ( ampm === 'am' && hours === 12 ) hours = 0;

						if ( hours < 10 ) hours = '0' + hours.toString();
						if ( minutes < 10 ) minutes = '0' + minutes.toString();

						return hours + ':' + minutes;
					}

					return time;
				},

			waitPaymentFinish: function()
			{
				if( booknetic.paymentWindow && booknetic.paymentWindow.closed )
				{
					if ( booknetic.paymentStatusListener )
						clearInterval( booknetic.paymentStatusListener );

					// Payment window closed - handle payment completion
					if( booknetic.paymentAppointmentId )
					{
						var appointmentId = booknetic.paymentAppointmentId;
						booknetic.paymentAppointmentId = null;
						
						// Small delay to allow payment webhook to process
						setTimeout(function() {
							// Reload appointments list to get updated payment status
							booknetic.loadAppointmentsList();
							
							// After reload, check if payment was successful
							setTimeout(function() {
								var appointmentRow = customer_panel_js.find('tr[data-id="' + appointmentId + '"]');
								var isPaid = false;
								
								if( appointmentRow.length > 0 )
								{
									// Check for "Paid" status indicator (green disc with "Paid" text)
									var paidIndicator = appointmentRow.find('.booknetic_appointment_status_all.green-disc');
									if( paidIndicator.length > 0 )
									{
										isPaid = true;
									}
									// Also check if pay now button disappeared (means payment completed)
									else if( appointmentRow.find('.booknetic_pay_now_btn').length === 0 )
									{
										// No pay button means payment is likely complete
										isPaid = true;
									}
								}
								
								// Show success message and close popup
								if( isPaid )
								{
									booknetic.toast( __('Payment completed successfully!'), 'success' );
								}
								
								// Always close the payment popup
								$("html, body").css({ overflow: "auto" });
								customer_panel_js.find('#booknetic_cp_pay_now_popup').fadeOut(200);
							}, 1000);
						}, 2000);
					}
					else
					{
						// Just reload if no appointment ID was set
						setTimeout(function() {
							booknetic.loadAppointmentsList();
						}, 1000);
					}

					return;
				}

				setTimeout( booknetic.waitPaymentFinish, 1000 );
			},

			paymentFinished: function ( status )
			{
				booknetic.paymentStatus = status;
				if( booknetic.paymentWindow && !booknetic.paymentWindow.closed )
				{
					if ( booknetic.paymentStatusListener )
						clearInterval( booknetic.paymentStatusListener );

					booknetic.paymentWindow.close();
				}
			},
			};

			if( BookneticDataCP.client_timezone != 'off' && BookneticDataCP.tz_offset_param === '-' && typeof bkntc_preview !== 'undefined' && !bkntc_preview )
			{
				location.href = location.href + (location.href.indexOf('?') === -1 ? '?' : '&') + 'client_time_zone=' + booknetic.timeZoneOffset();
			}

			if( 'datepicker' in $.fn && $.fn.datepicker?.dates )
			{
				$.fn.datepicker.dates['en']['months'] = [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')];
				$.fn.datepicker.dates['en']['days'] = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
				$.fn.datepicker.dates['en']['daysShort'] = $.fn.datepicker.dates['en']['days'];
				$.fn.datepicker.dates['en']['daysMin'] = $.fn.datepicker.dates['en']['days'];
			}

			customer_panel_js.on('click', '#booknetic-toaster .booknetic-toast-remove', function ()
			{
				$(this).closest('#booknetic-toaster').fadeOut(200, function()
				{
					$(this).remove();
					this.toastTimer = 0;
				});
			}).on('click', '.booknetic_cp_header_menu_item', function()
			{
				if( $(this).hasClass('booknetic_cp_header_menu_active') )
				{
					return;
				}

				var tabid = $(this).data('tabid');

				customer_panel_js.find('.booknetic_cp_header_menu_active').removeClass('booknetic_cp_header_menu_active');
				$(this).addClass('booknetic_cp_header_menu_active');

				customer_panel_js.find('.booknetic_cp_tab').hide();
				customer_panel_js.find('#booknetic_tab_' + tabid).show();
			}).on('click', '.invoice-download-link', function ()
			{
				let appointment_id = $(this).attr("appointment_id");
				booknetic.ajax('download_invoice', {
					id: appointment_id
				}, function(response) {
					if (response && response.status) {
						var data = response.data || response;
						if (data.download_url) {
							// PDF download - create a proper download link
							var link = document.createElement('a');
							link.href = data.download_url;
							link.download = data.filename || 'invoice-' + data.invoice_number + '.pdf';
							link.target = '_blank';
							document.body.appendChild(link);
							link.click();
							document.body.removeChild(link);

							booknetic.toast('PDF generated successfully!', 'success');
						} else if (data.html_content) {
							// Fallback HTML preview
							var newWindow = window.open('', '_blank');
							newWindow.document.write(data.html_content);
							newWindow.document.close();

							if (data.message) {
								booknetic.toast(data.message, 'warning');
							}
						} else {
							booknetic.toast('No download content available', 'unsuccess');
						}
					} else {
						var errorMsg = (response && response.data) ? response.data : 'Download failed';
						booknetic.toast(errorMsg, 'unsuccess');
					}
				});
			}).on('click', '#booknetic_profile_save', function ()
			{
				var name		= customer_panel_js.find('#booknetic_input_name').val(),
					surname 	= customer_panel_js.find('#booknetic_input_surname').val(),
					email		= customer_panel_js.find('#booknetic_input_email').val(),
					phone_input = customer_panel_js.find('#booknetic_input_phone'),
					phone		= phone_input.data('iti') ? (typeof intlTelInputUtils !== 'undefined' ? phone_input.data('iti').getNumber(intlTelInputUtils.numberFormat.E164) : phone_input.data('iti').getNumber()) : phone_input.val(),
					birthdate	= customer_panel_js.find('#booknetic_input_birthdate').val(),
					gender		= customer_panel_js.find('#booknetic_input_gender').val();

				booknetic.ajax('save_profile', {
					name: name,
					surname: surname,
					email: email,
					phone: phone,
					birthdate: birthdate,
					gender: gender
				}, function ( result )
				{
					booknetic.toast( result['message'] );
				});
			}).on('click', '#booknetic_change_password_save', function ()
			{
				var old_password		= customer_panel_js.find('#booknetic_input_old_password').val(),
					new_password		= customer_panel_js.find('#booknetic_input_new_password').val(),
					repeat_new_password	= customer_panel_js.find('#booknetic_input_repeat_new_password').val();

				booknetic.ajax('change_password', {
					old_password: old_password,
					new_password: new_password,
					repeat_new_password: repeat_new_password
				}, function ( result )
				{
					booknetic.toast( result['message'] );
				});
			}).on('click', '.booknetic_cp_header_logout_btn', function ()
			{
				location.href = $(this).data('href');
			}).on('click', '.booknetic_reschedule_popup_cancel,.booknetic_pay_now_popup_cancel, .booknetic_cancel_popup_no', function ()
			{
				$(this).closest('.booknetic_popup').fadeOut(200);
				$("html, body").css({ overflow: "auto" });
			}).on('click', '.booknetic_reschedule_btn', function ()
			{
				var tr				= $( this ).closest( 'tr' ),
					id				= tr.attr( 'data-id' ),
					date			= tr.attr( 'data-date' ),
					time			= tr.attr( 'data-time' ),
					date_format		= tr.attr( 'data-date-format' ),
					datebased		= tr.data( 'datebased' );

				if ( datebased == 1 )
				{
					customer_panel_js.find( '#booknetic_reschedule_popup_time_area' ).hide();
				}
				else
				{
					customer_panel_js.find( '#booknetic_reschedule_popup_time_area' ).show();
				}

				customer_panel_js.find( '#booknetic_reschedule_popup_date' ).attr( 'o_date', date );

				customer_panel_js.find( '#booknetic_reschedule_popup_date' ).flatpickr(
					{
						altInput: true,
						altFormat: date_format,
						dateFormat: date_format,
						monthSelectorType: 'dropdown',
						locale: {
							firstDayOfWeek: BookneticDataCP.week_starts_on === 'sunday' ? 0 : 1
						},
						defaultDate: date,
						onMonthChange :  (selectedDates, dateStr, instance)=>{
							booknetic.loadAvailableDate(instance , id );
						},
						onOpen : (selectedDates, dateStr, instance)=>{
							booknetic.loadAvailableDate(instance , id );
						},
					} );

				customer_panel_js.find( '.booknetic_reschedule_popup_time' ).html( '<option value="' + time + '">' + time + '</option>' );
				$("html, body").css({ overflow: "hidden" });
				customer_panel_js.find( '#booknetic_cp_reschedule_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );
			}).on('click', '.booknetic_pay_now_btn', function ()
			{
				let tr = $( this ).closest( 'tr' ),
					id = tr.attr( 'data-id' ),
					tenant_id = tr.attr('data-tenant-id');

				customer_panel_js.find( '#booknetic_cp_pay_now_popup' )
					.attr( 'data-appointment-id', id )
					.attr('data-tenant-id', tenant_id)
					.removeClass('booknetic_hidden').hide().fadeIn( 200 );

			}).on('click', '.booknetic_change_status_btn', function ()
			{
				var tr				= $( this ).closest( 'tr' ),
					id				= tr.attr( 'data-id' );

				customer_panel_js.find( '#booknetic_cp_change_status_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );

			}).on('click', '.booknetic_reschedule_popup_confirm', function ()
			{
				var dataid	= customer_panel_js.find('#booknetic_cp_reschedule_popup').attr( 'data-appointment-id'),
					date	= customer_panel_js.find('#booknetic_reschedule_popup_date').val(),
					time	= customer_panel_js.find('.booknetic_reschedule_popup_time').val();

				booknetic.ajax('reschedule_appointment', {
					id: dataid,
					date: date,
					time: booknetic.reformatTimeFromCustomFormat( time ),
					client_time_zone: booknetic.timeZoneOffset(),
				}, function ( result )
				{
					booknetic.loadAppointmentsList();

					booknetic.toast( result['message'] );
					$("html, body").css({ overflow: "auto" });
					customer_panel_js.find('#booknetic_cp_reschedule_popup').fadeOut(200);
				});
			}).on('click', '.booknetic_change_status_popup_confirm', function ()
			{
				var appointment_id	= customer_panel_js.find('#booknetic_cp_change_status_popup').attr( 'data-appointment-id'),
					status_key	= customer_panel_js.find('.booknetic_change_status_popup_select').val();

				booknetic.ajax('change_appointment_status', {
					id: appointment_id,
					status: status_key
				}, function ( result )
				{
					booknetic.loadAppointmentsList();

					booknetic.toast( result['message'] );

					customer_panel_js.find('#booknetic_cp_change_status_popup').fadeOut(200);
				});
			}).on('click', '.booknetic_pay_now_popup_confirm', function ()
			{
				var appointment_id	= customer_panel_js.find('#booknetic_cp_pay_now_popup').attr( 'data-appointment-id'),
					tenant_id = customer_panel_js.find("#booknetic_cp_pay_now_popup").attr('data-tenant-id'),
					payment_method	= customer_panel_js.find('.booknetic_pay_now_popup_select').val();
					// tenant_id = customer_panel_js.find()

				let data = new FormData()
				data.append('id',appointment_id)
				data.append('payment_method',payment_method)
				data.append('tenant_id', tenant_id);

				bookneticHooks.doAction('ajax_before_confirm' , data , booknetic);
				
				// Store appointment ID to check payment status later
				booknetic.paymentAppointmentId = appointment_id;
				
				// Set up interval to check if payment window closes (backup mechanism)
				if( booknetic.paymentWindowCheckInterval )
				{
					clearInterval( booknetic.paymentWindowCheckInterval );
				}
				
				booknetic.paymentWindowCheckInterval = setInterval(function() {
					if( booknetic.paymentWindow && booknetic.paymentWindow.closed && booknetic.paymentAppointmentId )
					{
						clearInterval( booknetic.paymentWindowCheckInterval );
						booknetic.paymentWindowCheckInterval = null;
						
						// Trigger the payment completion handler
						var appointmentId = booknetic.paymentAppointmentId;
						booknetic.paymentAppointmentId = null;
						
						// Small delay to allow payment webhook to process
						setTimeout(function() {
							// Reload appointments list to get updated payment status
							booknetic.loadAppointmentsList();
							
							// After reload, check if payment was successful
							setTimeout(function() {
								var appointmentRow = customer_panel_js.find('tr[data-id="' + appointmentId + '"]');
								var isPaid = false;
								
								if( appointmentRow.length > 0 )
								{
									// Check for "Paid" status indicator (green disc with "Paid" text)
									var paidIndicator = appointmentRow.find('.booknetic_appointment_status_all.green-disc');
									if( paidIndicator.length > 0 )
									{
										isPaid = true;
									}
									// Also check if pay now button disappeared (means payment completed)
									else if( appointmentRow.find('.booknetic_pay_now_btn').length === 0 )
									{
										// No pay button means payment is likely complete
										isPaid = true;
									}
								}
								
								// Show success message and close popup
								if( isPaid )
								{
									booknetic.toast( __('Payment completed successfully!'), 'success' );
								}
								
								// Always close the payment popup
								$("html, body").css({ overflow: "auto" });
								customer_panel_js.find('#booknetic_cp_pay_now_popup').fadeOut(200);
							}, 1000);
						}, 2000);
					}
				}, 500);
				
				booknetic.ajax('create_payment_link', data , function ( result )
				{
					bookneticHooks.doAction('ajax_after_confirm_success' , booknetic,data,result)

					// bookneticHooks.doAction('ajax_after_confirm_success', function( booknetic,data,result )
					// {
					// 		console.log("Testestestes");
					// 		if( result['status'] == 'error' )
					// 		{
					// 			booknetic.toast( result['error_msg'], 'unsuccess' );
					// 			booknetic.paymentWindow.close();
					// 			return;
					// 		}

					// 		if( !booknetic.paymentWindow.closed )
					// 		{
					// 			booknetic.loading(1);
					// 			booknetic.paymentWindow.location.href = result['url'];
					// 			return;
					// 		}

					// });
					
				},true,function ()
				{
					bookneticHooks.doAction('ajax_after_confirm_error' , booknetic,data,null)
					
					// Clear appointment ID on error
					booknetic.paymentAppointmentId = null;
					
					// Clear interval check on error
					if( booknetic.paymentWindowCheckInterval )
					{
						clearInterval( booknetic.paymentWindowCheckInterval );
						booknetic.paymentWindowCheckInterval = null;
					}
				});
			}).on('click', '#booknetic_profile_delete', function ()
			{
				customer_panel_js.find('#booknetic_cp_delete_profile_popup').removeClass('booknetic_hidden').hide().fadeIn(200);
			}).on('click', '.booknetic_delete_profile_popup_yes', function ()
			{
				booknetic.ajax('delete_profile', {}, function ( result )
				{
					booknetic.loading(1);
					location.href = result['redirect_url'];
				});
			}).on('change', '#booknetic_reschedule_popup_date', function ()
			{
				customer_panel_js.find("#booknetic_reschedule_popup_date").attr('o_date', $("#booknetic_reschedule_popup_date").val());
				customer_panel_js.find('.booknetic_reschedule_popup_time').val('').trigger('change');
			}).on('click', '.cp-mobile-dropdown', function ()
			{
					var appointment_id = $(this).attr("appointment_id");
					var action = $(this).attr("action");
					if(action == "cp-dropdown-down") {
						$("#cp-mobile-dropdown-tr-"+appointment_id).slideDown();
						$(this).attr("action", "cp-dropdown-up");
					} else {
						$("#cp-mobile-dropdown-tr-"+appointment_id).slideUp();
						$(this).attr("action", "cp-dropdown-down");
					}
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_change_status_popup_select"), 'get_allowed_statuses', function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_change_status_popup').attr( 'data-appointment-id'),
				}
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_pay_now_popup_select"), 'get_allowed_payment_gateways', function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_pay_now_popup').attr( 'data-appointment-id'),
				}
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_reschedule_popup_time"), 'get_available_times_of_appointment',function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_reschedule_popup').attr( 'data-appointment-id'),
					date:  customer_panel_js.find("#booknetic_reschedule_popup_date").attr('o_date'),
					client_time_zone:booknetic.timeZoneOffset()
				}
			});

			var phone_input = customer_panel_js.find('#booknetic_input_phone');
			if (phone_input.length > 0) {
				var intlTelInputFn = window.intlTelInput || (typeof intlTelInput !== 'undefined' ? intlTelInput : null);
				if (typeof intlTelInputFn === 'function') {
					try {
						phone_input.data('iti', intlTelInputFn( phone_input[0], {
							utilsScript: BookneticDataCP.assets_url + "js/utilsIntlTelInput.js",
							initialCountry: phone_input.data('country-code')
						}));
					} catch (e) {
						console.error('Error initializing intlTelInput:', e);
					}
				}
			}

			customer_panel_js.find('.td_datetime').each(function (){
				let tenant_timezone = $( this ).data('appointment-timezone');
				let tenant_offset = 0;
				let offset_diff;

				if( /^[a-zA-Z_-]+\/[a-zA-Z_-]+\/*[a-zA-Z_-]*$/.test( tenant_timezone.trim() ) )
				{
					const str = new Date().toLocaleString('en-us', { timeZone: tenant_timezone.trim(), hour12: false });
					offset_diff = ( new Date( str ).getTime() - new Date().getTime() );
				}
				else if( !isNaN(parseFloat( tenant_timezone.replace("UTC", '') )) )
				{
					tenant_offset = parseFloat(tenant_timezone.replace("UTC", ''))*60*60*1000;
				}

				let client_offset = new Date().getTimezoneOffset()*60*1000*(-1);
				let datetime = $( this ).parent('tr').data('date');
				datetime += ' ';
				datetime += $( this ).parent('tr').data('time');

				datetime = new Date( datetime ) ;

				if ( typeof offset_diff != "undefined" && offset_diff != null )
				{
					datetime = new Date( ( datetime.getTime() - offset_diff ) );
				}
				else
				{
					datetime = new Date( ( datetime.getTime() - tenant_offset + client_offset ) );
				}


				let dateYear = datetime.getFullYear();
				let dateMonth = ("0" + (datetime.getMonth()+1)).slice(-2);
				let dateDay = ("0" + datetime.getDate()).slice(-2);
				let dateString = '';
				let date_format = $( this ).data('date-format');
				switch(date_format) {
					case 'Y-m-d':
						dateString = dateYear + '-' + dateMonth + '-' + dateDay;
						break;
					case 'm/d/Y':
						dateString = dateMonth + '/' + dateDay + '/' + dateYear;
						break;
					case 'd-m-Y':
						dateString = dateDay + '-' + dateMonth + '-' + dateYear;
						break;
					case 'd/m/Y':
						dateString = dateDay + '/' + dateMonth + '/' + dateYear;
						break;
					case 'd.m.Y':
						dateString = dateDay + '.' + dateMonth + '.' + dateYear;
						break;
					default:
						dateString = dateYear + '-' + dateMonth + '-' + dateDay;
				}

				let timeHour = ("0" + datetime.getHours()).slice(-2);
				let timeMinute = ("0" + datetime.getMinutes()).slice(-2);
				let timeString = "";
				let is12Hour = $( this ).data('time-format') == 'g:i A';
				if( parseInt(timeHour) >= 12 && is12Hour )
				{
					timeHour = parseInt( timeHour - 12 );
					if( timeHour === 0 )
					{
						timeHour = 12;
					}
					timeString = timeHour + ":" + timeMinute + " PM";
				}
				else if( parseInt(timeHour) < 12 && is12Hour)
				{
					timeString = timeHour + ":" + timeMinute + " AM";
				}

				else
				{
					timeString = timeHour + ":" + timeMinute;
				}

				$( this ).text( dateString + " " + timeString);
				$( this ).parent('tr').attr('data-date', dateString);
				$( this ).parent('tr').data('time-show', timeString);
			});

			booknetic.loadAppointmentsList();

			// Listen for payment completion events
			bookneticHooks.addAction( 'payment_completed', function( status, paymentData )
			{
				// Only handle if payment was successful
				if( status )
				{
					// Reload appointments list to show updated payment status
					booknetic.loadAppointmentsList();
					
					// Show success message
					booknetic.toast( __('Payment completed successfully!'), 'success' );
					
					// Close the payment popup
					$("html, body").css({ overflow: "auto" });
					customer_panel_js.find('#booknetic_cp_pay_now_popup').fadeOut(200);
				}
			});

			bookneticHooks.doAction( 'customer_panel_loaded', booknetic )
		}


		$('.booknetic-body').each( ( i, v ) =>
		{
			initCustomerPanelPage( v )
		})
		$.urlParam = function(name){
			var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
			return results[1] || 0;
		}
		if ($.urlParam('status') == "new_register_customer") {
			$('[data-target=#booknetic-tab-change-password]').click();
		}

	});

})(jQuery);

