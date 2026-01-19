(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.summernote(
			$('.editor_tenant_info'),
			[
				//['style', ['style']],
				['style', ['bold', 'italic', 'underline', 'clear']],
				//['fontsize', ['fontsize']],
				//['color', ['color']],
				//['para', ['ul', 'ol', 'paragraph']],
				//['table', ['table']],
				//['insert', ['link', 'picture']],
				//['view', ['codeview']],
				//['height', ['height']]
			],
			[],
			350
		);


	});

})(jQuery);
