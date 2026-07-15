(function ($) {
	'use strict';

	$(function () {
		$('.qr-color-picker').wpColorPicker();

		$('#qr-add-reason').on('click', function () {
			var name = $('input[name*="[return_reasons]"]').first().attr('name');
			if (!name) return;
			$('#qr-reasons-list').append(
				'<p><input type="text" name="' + name + '" value="" class="regular-text" /></p>'
			);
		});
	});
})(jQuery);
