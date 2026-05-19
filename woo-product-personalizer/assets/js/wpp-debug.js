/**
 * Woo Product Personalizer – frontend debug helper.
 *
 * @package WooProductPersonalizer
 */
(function (window) {
	'use strict';

	var MAX_ENTRIES = 200;
	var entries = [];

	function isEnabled() {
		return (
			typeof window.wppData !== 'undefined' &&
			(window.wppData.debugEnabled === true || window.wppData.debugEnabled === '1')
		);
	}

	function safeStringify(data) {
		try {
			return JSON.stringify(data);
		} catch (e) {
			return String(data);
		}
	}

	function pushEntry(level, message, data) {
		var entry = {
			ts: new Date().toISOString(),
			level: level,
			message: message,
			data: data || null
		};

		entries.push(entry);
		if (entries.length > MAX_ENTRIES) {
			entries.shift();
		}

		return entry;
	}

	function renderPanel() {
		var $panel = window.jQuery('#wpp-debug-panel');
		if (!$panel.length) {
			return;
		}

		var lines = entries
			.slice(-40)
			.map(function (e) {
				var extra = e.data ? ' ' + safeStringify(e.data) : '';
				return '[' + e.ts.split('T')[1].replace('Z', '') + '] ' + e.level.toUpperCase() + ' ' + e.message + extra;
			})
			.join('\n');

		$panel.find('.wpp-debug-panel__log').text(lines || '(brak logów)');
		$panel[0].scrollTop = $panel[0].scrollHeight;
	}

	function sendToServer(entry) {
		if (!isEnabled() || typeof window.wppData === 'undefined' || !window.jQuery) {
			return;
		}

		window.jQuery.ajax({
			url: window.wppData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpp_client_log',
				nonce: window.wppData.debugLogNonce,
				level: entry.level,
				message: entry.message,
				context: safeStringify(entry.data || {})
			}
		});
	}

	window.WppDebug = {
		isEnabled: isEnabled,
		log: function (message, data) {
			if (!isEnabled()) {
				return;
			}

			pushEntry('log', message, data);
			renderPanel();
		},
		warn: function (message, data) {
			if (!isEnabled()) {
				return;
			}

			pushEntry('warn', message, data);
			renderPanel();
		},
		error: function (message, data) {
			if (!isEnabled()) {
				return;
			}

			var entry = pushEntry('error', message, data);
			renderPanel();
			sendToServer(entry);
		},
		getEntries: function () {
			return entries.slice();
		},
		clear: function () {
			entries = [];
			renderPanel();
		}
	};
})(window);
