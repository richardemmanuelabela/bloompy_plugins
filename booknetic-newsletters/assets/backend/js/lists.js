(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		$('.fs-modal').on('click', '#addListSave', function () {

			var data = new FormData();
			var assign_mode = $("#assign_mode").val();

			let list = {};
			$(".list_select").each(function() {
				let id = $(this).attr("id");      // get ID
				let value = $(this).val();        // get selected value
				if (value != "") {
					list[id] = value;
				}
			});
			let jsonData = JSON.stringify(list);
			data.append('assign_mode', assign_mode);
			data.append('list_data', jsonData);

			booknetic.ajax( 'newsletters.save_list', data, function()
			{
				booknetic.toast(booknetic.__('Changes saved'), 'success');
				booknetic.modalHide($(".fs-modal"));

				// if( $("#fs_data_table_div").length > 0 ) {
				//     booknetic.dataTable.reload($("#fs_data_table_div"));
				// }
			});
		});
		$(".fs-modal .list_select").select2({
			theme:			'bootstrap',
			placeholder:	booknetic.__('select'),
			allowClear:		true
		});
	});

})(jQuery);
