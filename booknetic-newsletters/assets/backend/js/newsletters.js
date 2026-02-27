(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(document).on('click', '#newsletter-modal', function(ids)
		{
			var id = $(this).attr("data-name");
			booknetic.loadModal('assign', {'data-name': id});
		});
		$(".newsletter-wrapper select").select2({
			theme:			'bootstrap',
			placeholder:	booknetic.__('select'),
			allowClear:		true
		});

	});

})(jQuery);