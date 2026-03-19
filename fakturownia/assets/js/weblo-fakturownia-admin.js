(function ($) {

	'use strict';
	var TAB_STORAGE_KEY = 'weblo_fakturownia_active_tab';

	function t(key, fallback) {
		if (
			typeof WebloFakturowniaSettings !== 'undefined' &&
			WebloFakturowniaSettings &&
			WebloFakturowniaSettings.i18n &&
			typeof WebloFakturowniaSettings.i18n[key] !== 'undefined'
		) {
			return WebloFakturowniaSettings.i18n[key];
		}
		return fallback;
	}

	function switchTab(tabKey) {
		var $targetTab = $('.weblo-fakturownia-tab[data-tab="' + tabKey + '"]');
		if (!$targetTab.length) {
			tabKey = 'connection';
			$targetTab = $('.weblo-fakturownia-tab[data-tab="connection"]');
		}

		$('.weblo-fakturownia-tab').removeClass('is-active').attr('aria-selected', 'false');
		$targetTab.addClass('is-active').attr('aria-selected', 'true');

		$('.weblo-fakturownia-tab-panel').hide();
		$('.weblo-fakturownia-tab-panel[data-tab="' + tabKey + '"]').show();

		try {
			window.localStorage.setItem(TAB_STORAGE_KEY, tabKey);
		} catch (e) {}

		if (tabKey === 'logs') {
			fetchLogs();
		}
	}

	function updateConditionalFields() {
		var $codCheckbox = $('[id$="_weblo_fakturownia_cod_invoices"]');
		var $codStatuses = $('[id$="_weblo_fakturownia_cod_statuses"]');

		if ($codCheckbox.length && $codStatuses.length) {
			$codStatuses.closest('tr').toggle(!!$codCheckbox.prop('checked'));
		}

		var $shippingMode = $('[id$="_weblo_fakturownia_correction_shipping_mode"]');
		var $shippingAmount = $('[id$="_weblo_fakturownia_correction_shipping_amount"]');

		if ($shippingMode.length && $shippingAmount.length) {
			$shippingAmount.closest('tr').toggle($shippingMode.val() === 'custom_amount');
		}
	}

	function fetchLogs() {
		var $wrap = $('#weblo-fakturownia-logs');
		if (!$wrap.length) return;

		var nonce = (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.nonce_fetch_logs : $wrap.data('nonce'));
		var type = $('#weblo-fakturownia-logs-type').val() || '';
		var limit = parseInt($('#weblo-fakturownia-logs-limit').val(), 10) || 100;
		var $out = $('#weblo-fakturownia-logs-result');

		$out.html('<p>' + t('loading_logs', 'Loading logs...') + '</p>');

		$.ajax({
			url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'weblo_fakturownia_fetch_logs',
				nonce: nonce,
				type: type,
				limit: limit
			}
		})
			.done(function (res) {
				if (!res || !res.success) {
					$out.html('<div class="notice notice-error"><p>' + ((res && res.message) ? res.message : t('error_loading_logs', 'Error while loading logs.')) + '</p></div>');
					return;
				}

				var rows = res.rows || [];
				if (!rows.length) {
					$out.html('<div class="notice notice-info"><p>' + t('no_logs', 'No logs to display.') + '</p></div>');
					return;
				}

				var html = '';
				html += '<table class="widefat striped">';
				html += '<thead><tr>';
				html += '<th>ID</th><th>Order</th><th>Type</th><th>Date</th><th>Error</th>';
				html += '</tr></thead><tbody>';

				for (var i = 0; i < rows.length; i++) {
					var r = rows[i] || {};
					var orderId = r.order_id || '';
					var editUrl = orderId ? (window.location.origin + '/wp-admin/admin.php?page=wc-orders&action=edit&id=' + encodeURIComponent(orderId)) : '';
					var err = (r.error || '');
					if (err.length > 300) err = err.slice(0, 300) + '…';

					html += '<tr>';
					html += '<td>' + (r.id || '') + '</td>';
					html += '<td>' + (orderId ? ('<a href="' + editUrl + '">#' + orderId + '</a>') : '') + '</td>';
					html += '<td><code>' + (r.type || '') + '</code></td>';
					html += '<td>' + (r.created_at || '') + '</td>';
					html += '<td><code style="white-space:pre-wrap;display:block;">' + $('<div>').text(err).html() + '</code></td>';
					html += '</tr>';
				}

				html += '</tbody></table>';
				$out.html(html);
			})
			.fail(function () {
				$out.html('<div class="notice notice-error"><p>' + t('ajax_error_loading_logs', 'AJAX error while loading logs.') + '</p></div>');
			});
	}

	function enableSaveButton() {
		var $saveButton = $('.woocommerce-save-button, button[name="save"], input[name="save"]');
		if (!$saveButton.length) return;
		$saveButton.prop('disabled', false).removeClass('disabled');
	}

	$(document).ready(function () {
		var $button = $('#weblo-fakturownia-test-connection');
		var $result = $('#weblo-fakturownia-test-result');
		var $bulkStart = $('#weblo-fakturownia-bulk-start');
		var $bulkStop = $('#weblo-fakturownia-bulk-stop');
		var $bulkStatus = $('#weblo-fakturownia-bulk-status');
		var $bulkProgressWrap = $('#weblo-fakturownia-bulk-progress');
		var $bulkBar = $('#weblo-fakturownia-bulk-progress-bar');
		var $bulkText = $('#weblo-fakturownia-bulk-progress-text');

		var $bulkCorrStart = $('#weblo-fakturownia-bulk-corr-start');
		var $bulkCorrStop = $('#weblo-fakturownia-bulk-corr-stop');
		var $bulkCorrStatus = $('#weblo-fakturownia-bulk-corr-status');
		var $bulkCorrProgressWrap = $('#weblo-fakturownia-bulk-corr-progress');
		var $bulkCorrBar = $('#weblo-fakturownia-bulk-corr-progress-bar');
		var $bulkCorrText = $('#weblo-fakturownia-bulk-corr-progress-text');

		var bulkRunning = false;
		var bulkNonce = $bulkStart.data('nonce');

		var bulkCorrRunning = false;
		var bulkCorrNonce = $bulkCorrStart.data('nonce');

		// Tabs.
		$(document).on('click', '.weblo-fakturownia-tab', function (e) {
			e.preventDefault();
			var tabKey = $(this).data('tab');
			switchTab(tabKey);
		});

		// Restore last active tab after reload/save.
		var initialTab = 'connection';
		try {
			var savedTab = window.localStorage.getItem(TAB_STORAGE_KEY);
			if (savedTab) {
				initialTab = savedTab;
			}
		} catch (e) {}
		switchTab(initialTab);

		// Logs: manual refresh.
		$(document).on('click', '#weblo-fakturownia-logs-refresh', function (e) {
			e.preventDefault();
			fetchLogs();
		});

		// Conditional fields.
		updateConditionalFields();
		$(document).on('change', '[id$="_weblo_fakturownia_cod_invoices"], [id$="_weblo_fakturownia_correction_shipping_mode"]', updateConditionalFields);

		// Keep WooCommerce "Save changes" button active on this screen.
		enableSaveButton();
		$(document).on('input change keyup paste', '.weblo-fakturownia-admin :input, .weblo-fakturownia-admin textarea', enableSaveButton);
		$(document).on('click', '.weblo-fakturownia-tab, .weblo-fakturownia-restore-default-template', function () {
			setTimeout(enableSaveButton, 0);
		});
		if (window.tinyMCE && window.tinyMCE.editors) {
			window.tinyMCE.editors.forEach(function (editor) {
				if (!editor) return;
				editor.on('keyup change input SetContent', function () {
					enableSaveButton();
				});
			});
		}

		if (!$button.length) {
			// Do not return – bulk operations might still be on this page.
		}

		$button.on('click', function (e) {
			e.preventDefault();

			var nonce = $button.data('nonce');

			// Disable button and show test-in-progress message.
			$button.prop('disabled', true);
			var originalText = $button.text();
			$button.text(t('testing_connection', 'Testing connection...'));

			$result
				.removeClass('updated error notice notice-success notice-error')
				.empty();

			$.ajax({
				url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'weblo_fakturownia_test_connection',
					nonce: nonce
				}
			})
				.done(function (response) {
					var success = !!response.success;
					var message = response.message || '';

					var cssClass = success ? 'notice notice-success' : 'notice notice-error';

					$result
						.addClass(cssClass)
						.html('<p>' + message + '</p>');

					// Po sukcesie odśwież stronę, żeby odsłonić zakładki (connection_ok zapisuje się po teście).
					if (success) {
						setTimeout(function () {
							window.location.reload();
						}, 500);
					}
				})
				.fail(function () {
					$result
						.addClass('notice notice-error')
						.html('<p>' + t('unexpected_connection_error', 'An unexpected error occurred while testing the connection.') + '</p>');
				})
				.always(function () {
					$button.prop('disabled', false);
					$button.text(originalText);
				});
		});

		function bulkRenderProgress(processed, total, okCount, failCount) {
			$bulkProgressWrap.show();
			var pct = 0;
			if (total > 0) {
				pct = Math.min(100, Math.round((processed / total) * 100));
			}
			$bulkBar.css('width', pct + '%');
			var progressTpl = t('progress_format', 'Progress: {processed}/{total} | OK: {ok} | Errors: {errors}');
			$bulkText.text(
				progressTpl
					.replace('{processed}', processed)
					.replace('{total}', total)
					.replace('{ok}', okCount)
					.replace('{errors}', failCount)
			);
		}

		function bulkCorrRenderProgress(processed, total, okCount, failCount) {
			$bulkCorrProgressWrap.show();
			var pct = 0;
			if (total > 0) {
				pct = Math.min(100, Math.round((processed / total) * 100));
			}
			$bulkCorrBar.css('width', pct + '%');
			var progressTpl = t('progress_format', 'Progress: {processed}/{total} | OK: {ok} | Errors: {errors}');
			$bulkCorrText.text(
				progressTpl
					.replace('{processed}', processed)
					.replace('{total}', total)
					.replace('{ok}', okCount)
					.replace('{errors}', failCount)
			);
		}

		function bulkStep(step) {
			if (!bulkRunning) return;

			$.ajax({
				url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'weblo_fakturownia_bulk_issue_invoices',
					nonce: bulkNonce,
					step: step
				}
			})
				.done(function (res) {
					if (!res || !res.success) {
						bulkRunning = false;
						$bulkStart.prop('disabled', false);
						$bulkStop.prop('disabled', true);
						$bulkStatus.addClass('notice notice-error').html('<p>' + ((res && res.message) ? res.message : t('generic_error', 'Error.')) + '</p>');
						return;
					}

					var processed = res.processed || 0;
					var total = res.total || 0;
					var okCount = res.success_count || 0;
					var failCount = res.failed_count || 0;
					bulkRenderProgress(processed, total, okCount, failCount);

					if (res.done) {
						bulkRunning = false;
						$bulkStart.prop('disabled', false);
						$bulkStop.prop('disabled', true);
						$bulkStatus.removeClass('notice notice-error').addClass('notice notice-success').html('<p>' + (res.message || t('finished', 'Finished.')) + '</p>');
						return;
					}

					// Next batch: short delay to avoid overloading the server.
					setTimeout(function () {
						bulkStep('run');
					}, 300);
				})
				.fail(function () {
					bulkRunning = false;
					$bulkStart.prop('disabled', false);
					$bulkStop.prop('disabled', true);
					$bulkStatus.addClass('notice notice-error').html('<p>' + t('ajax_error', 'AJAX error.') + '</p>');
				});
		}

		function bulkCorrStep(step) {
			if (!bulkCorrRunning) return;

			$.ajax({
				url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'weblo_fakturownia_bulk_issue_corrections',
					nonce: bulkCorrNonce,
					step: step
				}
			})
				.done(function (res) {
					if (!res || !res.success) {
						bulkCorrRunning = false;
						$bulkCorrStart.prop('disabled', false);
						$bulkCorrStop.prop('disabled', true);
						$bulkCorrStatus.addClass('notice notice-error').html('<p>' + ((res && res.message) ? res.message : t('generic_error', 'Error.')) + '</p>');
						return;
					}

					var processed = res.processed || 0;
					var total = res.total || 0;
					var okCount = res.success_count || 0;
					var failCount = res.failed_count || 0;
					bulkCorrRenderProgress(processed, total, okCount, failCount);

					if (res.done) {
						bulkCorrRunning = false;
						$bulkCorrStart.prop('disabled', false);
						$bulkCorrStop.prop('disabled', true);
						$bulkCorrStatus.removeClass('notice notice-error').addClass('notice notice-success').html('<p>' + (res.message || t('finished', 'Finished.')) + '</p>');
						return;
					}

					setTimeout(function () {
						bulkCorrStep('run');
					}, 300);
				})
				.fail(function () {
					bulkCorrRunning = false;
					$bulkCorrStart.prop('disabled', false);
					$bulkCorrStop.prop('disabled', true);
					$bulkCorrStatus.addClass('notice notice-error').html('<p>' + t('ajax_error', 'AJAX error.') + '</p>');
				});
		}

		// Masowe operacje – faktury.
		if ($bulkStart.length) {
			$bulkStart.on('click', function (e) {
				e.preventDefault();
				if (bulkRunning) return;

				bulkNonce = $bulkStart.data('nonce');
				bulkRunning = true;
				$bulkStart.prop('disabled', true);
				$bulkStop.prop('disabled', false);
				$bulkStatus.removeClass('notice notice-error notice-success').empty();
				$bulkProgressWrap.hide();
				bulkRenderProgress(0, 0, 0, 0);

				bulkStep('start');
			});
		}

		if ($bulkStop.length) {
			$bulkStop.on('click', function (e) {
				e.preventDefault();
				if (!bulkRunning) return;
				bulkRunning = false;
				$bulkStart.prop('disabled', false);
				$bulkStop.prop('disabled', true);
				$bulkStatus.removeClass('notice notice-success').addClass('notice notice-warning').html('<p>' + t('stopping', 'Stopping...') + '</p>');

				$.ajax({
					url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'weblo_fakturownia_bulk_issue_invoices',
						nonce: bulkNonce,
						step: 'stop'
					}
				});
			});
		}

		// Masowe operacje – korekty.
		if ($bulkCorrStart.length) {
			$bulkCorrStart.on('click', function (e) {
				e.preventDefault();
				if (bulkCorrRunning) return;

				bulkCorrNonce = $bulkCorrStart.data('nonce');
				bulkCorrRunning = true;
				$bulkCorrStart.prop('disabled', true);
				$bulkCorrStop.prop('disabled', false);
				$bulkCorrStatus.removeClass('notice notice-error notice-success').empty();
				$bulkCorrProgressWrap.hide();
				bulkCorrRenderProgress(0, 0, 0, 0);

				bulkCorrStep('start');
			});
		}

		if ($bulkCorrStop.length) {
			$bulkCorrStop.on('click', function (e) {
				e.preventDefault();
				if (!bulkCorrRunning) return;
				bulkCorrRunning = false;
				$bulkCorrStart.prop('disabled', false);
				$bulkCorrStop.prop('disabled', true);
				$bulkCorrStatus.removeClass('notice notice-success').addClass('notice notice-warning').html('<p>' + t('stopping', 'Stopping...') + '</p>');

				$.ajax({
					url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'weblo_fakturownia_bulk_issue_corrections',
						nonce: bulkCorrNonce,
						step: 'stop'
					}
				});
			});
		}

		// Clear debug.log file.
		$(document).on('click', '#weblo-fakturownia-debug-clear', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var nonce = $btn.data('nonce');
			if (!nonce) return;

			if (!window.confirm(t('confirm_clear_debug', 'Are you sure you want to remove the debug.log file? This action cannot be undone.'))) {
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'weblo_fakturownia_clear_debug_log',
					nonce: nonce
				}
			})
				.done(function (res) {
					var msg = (res && res.message) ? res.message : t('operation_finished', 'Operation finished.');
					alert(msg);
				})
				.fail(function () {
					alert(t('ajax_error_clearing_debug', 'AJAX error while clearing debug.log file.'));
				})
				.always(function () {
					$btn.prop('disabled', false);
				});
		});

		// Clear integration logs from database table.
		$(document).on('click', '#weblo-fakturownia-logs-clear', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var nonce = $btn.data('nonce');
			if (!nonce) return;

			if (!window.confirm(t('confirm_clear_db_logs', 'Are you sure you want to remove all integration logs from the database?'))) {
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: (typeof WebloFakturowniaSettings !== 'undefined' ? WebloFakturowniaSettings.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '')),
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'weblo_fakturownia_clear_db_logs',
					nonce: nonce
				}
			})
				.done(function (res) {
					var msg = (res && res.message) ? res.message : t('operation_finished', 'Operation finished.');
					alert(msg);
					if (res && res.success) {
						fetchLogs();
					}
				})
				.fail(function () {
					alert(t('ajax_error_clearing_db_logs', 'AJAX error while clearing database logs.'));
				})
				.always(function () {
					$btn.prop('disabled', false);
				});
		});
	});
})(jQuery);

