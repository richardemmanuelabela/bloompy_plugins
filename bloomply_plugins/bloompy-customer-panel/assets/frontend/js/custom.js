(function ($) {

    $(function () {


        bookneticHooks.addAction('customer_panel_loaded', ( booknetic ) =>
        {
            booknetic.panel_js.find('.booknetic-cp-tab-item').click(function () {
                $dataID = $(this).data('target');

                if (!$(this).hasClass('active')) {
                    booknetic.panel_js.find('.booknetic-cp-tab-item').removeClass('active');
                    $(this).addClass('active');

                    booknetic.panel_js.find('.booknetic-cp-tab').stop(true, false, true).fadeOut(0).removeClass('show');
                    booknetic.panel_js.find($dataID).stop(true, false, true).fadeIn().addClass('show');
                    booknetic.panel_js.find('.booknetic-cp-tabs').removeClass('expand');
                    booknetic.panel_js.find('.booknetic-cp-sidebar-toggle').removeClass('active');
                }
            });

            booknetic.panel_js.find('.booknetic-cp-sidebar-toggle').click(function () {
                $(this).toggleClass('active');
                booknetic.panel_js.find('.booknetic-cp-tabs').toggleClass('expand');
            });

            // Flat date picker
            booknetic.panel_js.find("#booknetic_input_birthdate").flatpickr(
                {
                    altInput: true,
                    altFormat: "Y-m-d",
                    dateFormat: "Y-m-d",
                    monthSelectorType: 'dropdown',
                    locale: {
                        firstDayOfWeek: BookneticDataCP.week_starts_on === 'sunday' ? 0 : 1
                    },
                    onOpen: function (selectedDates, dateStr, instance) {
                        booknetic.panel_js.find('.flatpickr-calendar').css("max-width", $(instance.input).next().outerWidth());
                    }
                }
            );

            // Responsive JS - GRID
            let customerPanelWidth = $('.booknetic-body').width();

            if (customerPanelWidth >= 576) {
                $('.booknetic-body').addClass('device-min-sm');
            }
            if (customerPanelWidth >= 768) {
                $('.booknetic-body').addClass('device-min-md');
            }
            if (customerPanelWidth >= 992) {
                $('.booknetic-body').addClass('device-min-lg');
            }
            if (customerPanelWidth >= 1200) {
                $('.booknetic-body').addClass('device-min-xl');
            }
            if (customerPanelWidth >= 1400) {
                $('.booknetic-body').addClass('device-min-xxl');
            }

            // Responsive JS - MOBİLE
            if (customerPanelWidth < 576) {
                $('.booknetic-body').addClass('device-max-sm');
            }
            if (customerPanelWidth < 768) {
                $('.booknetic-body').addClass('device-max-md');
            }
            if (customerPanelWidth < 992) {
                $('.booknetic-body').addClass('device-max-lg');
            }
            if (customerPanelWidth < 1200) {
                $('.booknetic-body').addClass('device-max-xl');
            }
            if (customerPanelWidth < 1400) {
                $('.booknetic-body').addClass('device-max-xxl');
            }
        })
    });

})(jQuery);
