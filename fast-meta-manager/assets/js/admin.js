(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var toggleButton = event.target.closest('.ffmm-toggle-edit');
		if (toggleButton) {
			var cell = toggleButton.closest('.ffmm-actions-cell');
			if (!cell) {
				return;
			}

			var panel = cell.querySelector('.ffmm-edit-panel');
			var row = cell.querySelector('.ffmm-save-delete-row');
			if (!panel || !row) {
				return;
			}

			var isHidden = panel.hasAttribute('hidden');
			if (isHidden) {
				panel.removeAttribute('hidden');
				row.removeAttribute('hidden');
				toggleButton.textContent = toggleButton.getAttribute('data-hide-label') || 'Cancel';
			} else {
				panel.setAttribute('hidden', 'hidden');
				row.setAttribute('hidden', 'hidden');
				toggleButton.textContent = toggleButton.getAttribute('data-show-label') || 'Edit';
			}

			return;
		}

		var submitButton = event.target.closest('.ffmm-js-submit-button');
		if (!submitButton) {
			return;
		}

		var container = submitButton.closest('.ffmm-edit-form');
		if (!container) {
			return;
		}

		var mode = submitButton.getAttribute('data-mode');
		var target = container.getAttribute('data-ffmm-target');
		var objectId = container.getAttribute('data-ffmm-object-id');
		var metaKey = container.getAttribute('data-ffmm-meta-key');
		var prevValue = container.getAttribute('data-ffmm-prev-value') || '';
		var returnUrl = container.getAttribute('data-ffmm-return-url') || '';
		var nonce = mode === 'delete'
			? container.getAttribute('data-ffmm-delete-nonce')
			: container.getAttribute('data-ffmm-save-nonce');

		if (!mode || !target || !objectId || !metaKey || !nonce || !window.FFMMAdmin) {
			return;
		}

		if (mode === 'delete' && !confirm(window.FFMMAdmin.confirmDelete || 'Delete this metadata key?')) {
			return;
		}

		var metaValue = '';
		if (mode === 'save') {
			var textarea = container.querySelector('.ffmm-meta-input');
			if (textarea) {
				metaValue = textarea.value;
			}
		}

		var form = document.createElement('form');
		form.method = 'post';
		form.action = window.FFMMAdmin.adminPostUrl;
		form.style.display = 'none';

		var addField = function (name, value) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = value;
			form.appendChild(input);
		};

		addField('action', 'ffmm_' + mode + '_meta');
		addField('target', target);
		addField('object_id', objectId);
		addField('meta_key', metaKey);
		addField('prev_value', prevValue);
		addField('ffmm_nonce', nonce);

		if (mode === 'save') {
			addField('meta_value', metaValue);
		}

		if (returnUrl) {
			addField('return_url', returnUrl);
		}

		document.body.appendChild(form);
		form.submit();
	});
})();
