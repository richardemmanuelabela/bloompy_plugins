(function($)
{
    "use strict";
    function __( key )
    {
        return key in BloompyDataTenantInfo.localization ? BloompyDataTenantInfo.localization[ key ] : key;
    }

    $(document).ready( function()
    {

        const tenantId = window.BookneticData.tenant_id;

        let initTenantInfoPage = function( value )
        {
            var tenant_info_js = $( value );

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

                panel_js: tenant_info_js,

                loadAvailableDate: function(tenantId )
                {
                    const params = new URLSearchParams(window.location.search);
                    const service = params.get('service'); // ?id=123

                    let data  = new FormData();
                    data.append('tenant_id' , tenantId);
                    data.append('service' , service);
                    booknetic.ajax( 'getdata', data, function ( result )
                    {
                        result = JSON.parse(JSON.stringify(result));
                        if ( result.service_name != null && result.service_name != "" && result.service_name != false ) {
                            let company_name = "<h3 class='booking_form_service_name'>"+result.service_name+"</h3>";
                            $(".booknetic_appointment_container_header").prepend(company_name);
                        }
                        if ( result.company_name != null && result.company_name != "" && result.company_name != false ) {
                            let company_name = "<h2 class='booking_form_company_name'>"+result.company_name+"</h2>";
                            $(".booknetic_appointment_container_header").prepend(company_name);
                        }

                        if ($(window).width() <= 768 && ( result.service_name != null && result.service_name != "" && result.service_name != false )) {
                            $('.booking_form_company_name').addClass('booking_form_company_name_mobile');
                        } else {
                            $('.booking_form_company_name').removeClass('booking_form_company_name_mobile');
                        }

                        let footer_first_column = "";
                        let footer_second_column = "";
                        let footer_third_column = "";
                        let footer_fourth_column = "";

                        if ( result.footer_first_column != null && result.footer_first_column != "" ) {
                            footer_first_column = result.footer_first_column;
                        }
                        if ( result.footer_second_column != null && result.footer_second_column != "" ) {
                            footer_second_column = result.footer_second_column;
                        }
                        if ( result.footer_third_column != null && result.footer_third_column != "" ) {
                            footer_third_column = result.footer_third_column;
                        }
                        if ( result.footer_fourth_column != null && result.footer_fourth_column != "" ) {
                            footer_fourth_column = result.footer_fourth_column;
                        }


                        var footer_text = '<div class="et_pb_row et_pb_row_0 et_pb_row_4col bloompy_booking_footer_wrapper">';
                        footer_text += '<div class="et_pb_column et_pb_column_1_3 et_pb_column_0  et_pb_css_mix_blend_mode_passthrough">';
                        footer_text += '<div class="et_pb_module et_pb_text et_pb_text_0  et_pb_text_align_left et_pb_bg_layout_light">';
                        footer_text += '<div class="et_pb_text_inner bloompy_booking_footer footer_first_column_text">'+footer_first_column+'</div>';
                        footer_text += '</div>';
                        footer_text += '</div>';
                        footer_text += '<div class="et_pb_column et_pb_column_1_3 et_pb_column_1  et_pb_css_mix_blend_mode_passthrough">';
                        footer_text += '<div class="et_pb_module et_pb_text et_pb_text_1  et_pb_text_align_left et_pb_bg_layout_light">';
                        footer_text += '<div class="et_pb_text_inner bloompy_booking_footer footer_second_column_text">';
                        footer_text += footer_second_column;
                        footer_text += '</div>';
                        footer_text += '</div>';
                        footer_text += '</div>';
                        footer_text += '<div class="et_pb_column et_pb_column_1_3 et_pb_column_2  et_pb_css_mix_blend_mode_passthrough">';
                        footer_text += '<div class="et_pb_module et_pb_text et_pb_text_2  et_pb_text_align_left et_pb_bg_layout_light">';
                        footer_text += '<div class="et_pb_text_inner bloompy_booking_footer footer_third_column_text">';
                        footer_text += footer_third_column;
                        footer_text += '</div>';
                        footer_text += '</div>';
                        footer_text += '</div>';
                        // footer_text += '<div class="et_pb_column et_pb_column_1_4 et_pb_column_3  et_pb_css_mix_blend_mode_passthrough et-last-child">';
                        // footer_text += '<div class="et_pb_module et_pb_text et_pb_text_3  et_pb_text_align_left et_pb_bg_layout_light">';
                        // footer_text += '<div class="et_pb_text_inner footer_fourth_column_text">';
                        // footer_text += footer_fourth_column;
                        // footer_text += '</div>';
                        // footer_text += '</div>';
                        // footer_text += '</div>';
                        footer_text += '</div>';
                        $("#bloompy-booking-page").append(footer_text);
                        //instance.set('enable',result['available_dates']);
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
                            url: BloompyDataTenantInfo.ajax_url,
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
                            url: BloompyDataTenantInfo.ajax_url,
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

                    $("#booknetic-toastr .booknetic-toast-img > img").attr('src', BloompyDataTenantInfo.assets_url + 'icons/' + type + '.svg');

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
                    if( BloompyDataTenantInfo.client_time_zone == 'off' )
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
                    if( booknetic.paymentWindow.closed )
                    {
                        if ( booknetic.paymentStatusListener )
                            clearInterval( booknetic.paymentStatusListener );

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
            booknetic.loadAvailableDate( tenantId );

        }
        if ( $('.booknetic_appointment_container').length ) {

            initTenantInfoPage(  );
        }

    });

})(jQuery);

