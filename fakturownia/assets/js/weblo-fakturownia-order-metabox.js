(function ($) {
	'use strict';

	function t(key, fallback) {
		if (
			typeof WebloFakturowniaOrder !== 'undefined' &&
			WebloFakturowniaOrder &&
			WebloFakturowniaOrder.i18n &&
			typeof WebloFakturowniaOrder.i18n[key] !== 'undefined'
		) {
			return WebloFakturowniaOrder.i18n[key];
		}
		return fallback;
	}

	function showResult($box, success, message) {
		var $result = $box.find('.weblo-fakturownia-order-result');
		var cls = success ? 'notice notice-success inline' : 'notice notice-error inline';
		$result.html('<div class="' + cls + '"><p>' + message + '</p></div>');
	}

	$(document).on('click', '.weblo-fakturownia-issue-invoice-now', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var $box = $btn.closest('.weblo-fakturownia-order-box');
		var orderId = parseInt($btn.data('order-id'), 10);

		if (!orderId) {
			showResult($box, false, t('missing_order_id', 'Missing order ID.'));
			return;
		}

		$btn.prop('disabled', true);
		var originalText = $btn.text();
		$btn.text(t('issuing', 'Issuing...'));

		$.ajax({
			url: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'weblo_issue_invoice_now',
				nonce: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.nonce_issue : ''),
				order_id: orderId
			}
		})
			.done(function (res) {
				if (res && res.success) {
					showResult($box, true, res.message || t('ok', 'OK'));
					if (res.html) {
						$('#weblo_fakturownia_order_metabox .inside').html(res.html);
					} else {
						window.location.reload();
					}
				} else {
					showResult($box, false, (res && res.message) ? res.message : t('error', 'Error.'));
					$btn.prop('disabled', false).text(originalText);
				}
			})
			.fail(function () {
				showResult($box, false, t('ajax_error', 'AJAX error.'));
				$btn.prop('disabled', false).text(originalText);
			});
	});

	$(document).on('click', '.weblo-fakturownia-send-invoice-email', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var $box = $btn.closest('.weblo-fakturownia-order-box');
		var orderId = parseInt($btn.data('order-id'), 10);
		var invoiceId = String($btn.data('invoice-id') || '');

		if (!orderId || !invoiceId) {
			showResult($box, false, t('missing_sending_data', 'Missing data for sending.'));
			return;
		}

		$btn.prop('disabled', true);
		var originalText = $btn.text();
		var finalText = originalText;
		$btn.text(t('sending', 'Sending...'));

		$.ajax({
			url: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'weblo_send_invoice_email',
				nonce: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.nonce_send : ''),
				order_id: orderId,
				invoice_id: invoiceId
			}
		})
			.done(function (res) {
				if (res && res.success) {
					showResult($box, true, res.message || t('sent', 'Sent.'));
					// After first successful send, change button label to "Send email again".
					finalText = t('send_email_again', 'Send email again');
					$btn.text(finalText);
				} else {
					showResult($box, false, (res && res.message) ? res.message : t('error', 'Error.'));
				}
			})
			.fail(function () {
				showResult($box, false, t('ajax_error', 'AJAX error.'));
			})
			.always(function () {
				$btn.prop('disabled', false).text(finalText);
			});
	});

	$(document).on('click', '.weblo-fakturownia-issue-correction-now', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var $box = $btn.closest('.weblo-fakturownia-order-box');
		var orderId = parseInt($btn.data('order-id'), 10);

		if (!orderId) {
			showResult($box, false, t('missing_order_id', 'Missing order ID.'));
			return;
		}

		$btn.prop('disabled', true);
		var originalText = $btn.text();
		$btn.text(t('issuing_correction', 'Issuing correction...'));

		$.ajax({
			url: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'weblo_issue_correction_now',
				nonce: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.nonce_issue_correction : ''),
				order_id: orderId
			}
		})
			.done(function (res) {
				if (res && res.success) {
					showResult($box, true, res.message || t('ok', 'OK'));
					if (res.html) {
						$('#weblo_fakturownia_order_metabox .inside').html(res.html);
					} else {
						window.location.reload();
					}
				} else {
					showResult($box, false, (res && res.message) ? res.message : t('error', 'Error.'));
					$btn.prop('disabled', false).text(originalText);
				}
			})
			.fail(function () {
				showResult($box, false, t('ajax_error', 'AJAX error.'));
				$btn.prop('disabled', false).text(originalText);
			});
	});

	$(document).on('click', '.weblo-fakturownia-send-correction-email', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var $box = $btn.closest('.weblo-fakturownia-order-box');
		var orderId = parseInt($btn.data('order-id'), 10);
		var invoiceId = String($btn.data('invoice-id') || '');

		if (!orderId || !invoiceId) {
			showResult($box, false, t('missing_sending_data', 'Missing data for sending.'));
			return;
		}

		$btn.prop('disabled', true);
		var originalText = $btn.text();
		var finalText = originalText;
		$btn.text(t('sending', 'Sending...'));

		$.ajax({
			url: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'weblo_send_correction_email',
				nonce: (typeof WebloFakturowniaOrder !== 'undefined' ? WebloFakturowniaOrder.nonce_send_correction : ''),
				order_id: orderId,
				invoice_id: invoiceId
			}
		})
			.done(function (res) {
				if (res && res.success) {
					showResult($box, true, res.message || t('sent', 'Sent.'));
					finalText = t('send_email_again', 'Send email again');
					$btn.text(finalText);
				} else {
					showResult($box, false, (res && res.message) ? res.message : t('error', 'Error.'));
				}
			})
			.fail(function () {
				showResult($box, false, t('ajax_error', 'AJAX error.'));
			})
			.always(function () {
				$btn.prop('disabled', false).text(finalText);
			});
	});
})(jQuery);

