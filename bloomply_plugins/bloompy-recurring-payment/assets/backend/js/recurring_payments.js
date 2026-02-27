(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        $(".recurring_form_fields").append($("#automatic_recurring_payment").detach());
    });

})(jQuery);