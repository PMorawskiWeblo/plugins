(function ($) {
	'use strict';

	var $popup;
	var $canvas;

	function getPopup() {
		$popup = $('#wpp-cart-preview-popup');

		if ($popup.length) {
			$canvas = $popup.find('.wpp-cart-preview-popup__canvas');
		}

		return $popup;
	}

	function openPreview(src) {
		var $el = getPopup();

		if (!$el.length || !src) {
			return;
		}

		$canvas = $el.find('.wpp-cart-preview-popup__canvas');
		$canvas.html('<img src="' + src + '" alt="" />');
		$el.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('wpp-cart-preview-open');
	}

	function closePreview() {
		var $el = getPopup();

		$el.removeClass('is-open').attr('aria-hidden', 'true');
		$('body').removeClass('wpp-cart-preview-open');
		$canvas.empty();
	}

	function bindEvents() {
		$(document).on('click', '.wpp-cart-preview-link', function (e) {
			e.preventDefault();
			openPreview($(this).attr('data-wpp-preview-full') || $(this).attr('data-wpp-preview') || '');
		});

		$(document).on('click', '[data-wpp-cart-preview-close]', function (e) {
			e.preventDefault();
			closePreview();
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape') {
				closePreview();
			}
		});
	}

	$(bindEvents);

	if (document.body) {
		document.body.addEventListener('xoo_wsc_cart_updated', bindEvents);
	}
})(jQuery);
