(function ($) {
	'use strict';

	var $builder, config;
	var collapseState = { slots: {}, texts: {} };
	var allBuilderCardsCollapsed = false;

	function initLayoutBuilder() {
		$builder = $('.wpp-layout-builder');
		if (!$builder.length) {
			return;
		}

		var raw = $builder.attr('data-config');
		try {
			config = JSON.parse(raw);
		} catch (e) {
			config = { canvas: {}, image_slots: [], text_fields: [], limits: {} };
		}

		syncCanvasInputs();
		renderSlots();
		renderTextFields();
		syncJson();
		updateToggleAllButton();
		initLayoutPreview();

		$builder.on('click', '.wpp-add-image-slot', addImageSlot);
		$builder.on('click', '.wpp-add-text-field', addTextField);
		$builder.on('click', '.wpp-select-media', selectMedia);
		$builder.on('click', '.wpp-remove-media', removeMedia);
		$builder.on('input change', 'input, textarea, select', onFieldChange);
		$builder.on('click', '.wpp-remove-slot', removeSlot);
		$builder.on('click', '.wpp-clone-slot', cloneSlot);
		$builder.on('click', '.wpp-move-slot-up, .wpp-move-slot-down', moveSlotByArrow);
		$builder.on('click', '.wpp-remove-text', removeText);
		$builder.on('click', '.wpp-clone-text', cloneText);
		$builder.on('click', '.wpp-builder-card__toggle', toggleBuilderCard);
		$builder.on('click', '.wpp-json-copy', copyLayoutJson);
		$builder.on('click mousedown', '.wpp-card-header-label', function (e) {
			e.stopPropagation();
		});
		$builder.on('click', '.wpp-builder-card__actions button, .wpp-builder-card__reorder button', function (e) {
			e.stopPropagation();
		});
		$builder.on('click', '.wpp-preview-zoom-toggle', togglePreviewZoom);
		$builder.on('click', '.wpp-toggle-all-cards', toggleAllBuilderCards);
		$builder.on('click', '.wpp-open-media', openMediaFromPreview);
	}

	function layoutI18n(key, fallback) {
		if (window.wppLayoutBuilder && window.wppLayoutBuilder.i18n && window.wppLayoutBuilder.i18n[key]) {
			return window.wppLayoutBuilder.i18n[key];
		}

		return fallback;
	}

	function formatSlotTitle(index) {
		var template = layoutI18n('slotTitle', 'Slot #%d');

		return template.replace('%d', String(index + 1));
	}

	function formatTextFieldTitle(index) {
		var template = layoutI18n('textFieldTitle', 'Text field #%d');

		return template.replace('%d', String(index + 1));
	}

	function captureCollapseState() {
		collapseState.slots = {};
		collapseState.texts = {};

		$builder.find('.wpp-slot-card').each(function () {
			var id = $(this).attr('data-card-id') || '';

			if (id) {
				collapseState.slots[id] = $(this).hasClass('is-collapsed');
			}
		});

		$builder.find('.wpp-text-card').each(function () {
			var id = $(this).attr('data-card-id') || '';

			if (id) {
				collapseState.texts[id] = $(this).hasClass('is-collapsed');
			}
		});
	}

	function applyCollapseState() {
		var slotsStateEmpty = !Object.keys(collapseState.slots).length;

		$builder.find('.wpp-slot-card').each(function () {
			var $card = $(this);
			var id = $card.attr('data-card-id') || '';
			var collapsed;

			if (id && Object.prototype.hasOwnProperty.call(collapseState.slots, id)) {
				collapsed = !!collapseState.slots[id];
			} else if (slotsStateEmpty) {
				collapsed = true;
			} else {
				collapsed = true;
			}

			setCardCollapsed($card, collapsed);
		});

		$builder.find('.wpp-text-card').each(function () {
			var id = $(this).attr('data-card-id') || '';

			if (id && collapseState.texts[id]) {
				setCardCollapsed($(this), true);
			}
		});
	}

	function setCardCollapsed($card, collapsed) {
		$card.toggleClass('is-collapsed', collapsed);

		var $toggle = $card.find('.wpp-builder-card__toggle').first();

		$toggle.attr('aria-expanded', collapsed ? 'false' : 'true');
		$toggle.attr(
			'aria-label',
			collapsed ? layoutI18n('expandItem', 'Expand settings') : layoutI18n('collapseItem', 'Collapse settings')
		);
	}

	function getBuilderCards() {
		return $builder.find('.wpp-slot-card.wpp-builder-card, .wpp-text-card.wpp-builder-card');
	}

	function syncCollapseStateFromCard($card, collapsed) {
		var id = $card.attr('data-card-id') || '';

		if (!id) {
			return;
		}

		if ($card.hasClass('wpp-slot-card')) {
			collapseState.slots[id] = collapsed;
		} else if ($card.hasClass('wpp-text-card')) {
			collapseState.texts[id] = collapsed;
		}
	}

	function updateToggleAllButton() {
		var $btn = $builder.find('.wpp-toggle-all-cards');

		if (!$btn.length) {
			return;
		}

		var $cards = getBuilderCards();
		var collapsedCount = $cards.filter('.is-collapsed').length;

		allBuilderCardsCollapsed = $cards.length > 0 && collapsedCount === $cards.length;

		$btn.toggleClass('is-active', allBuilderCardsCollapsed);
		$btn.attr('aria-pressed', allBuilderCardsCollapsed ? 'true' : 'false');
		$btn.attr(
			'title',
			allBuilderCardsCollapsed
				? layoutI18n('expandAllCards', 'Expand all slots and text fields')
				: layoutI18n('collapseAllCards', 'Collapse all slots and text fields')
		);
		$btn.find('.wpp-toggle-all-cards__label').text(
			allBuilderCardsCollapsed
				? layoutI18n('expandAllCards', 'Expand all')
				: layoutI18n('collapseAllCards', 'Collapse all')
		);
	}

	function toggleAllBuilderCards(e) {
		e.preventDefault();

		var $cards = getBuilderCards();
		var shouldCollapse = !allBuilderCardsCollapsed;

		$cards.each(function () {
			var $card = $(this);

			setCardCollapsed($card, shouldCollapse);
			syncCollapseStateFromCard($card, shouldCollapse);
		});

		allBuilderCardsCollapsed = shouldCollapse;
		updateToggleAllButton();
	}


	function mediaPickerHtml(options) {
		var selectLabel = esc(options.selectLabel || layoutI18n('selectImage', 'Select image'));
		var removeLabel = esc(options.removeLabel || layoutI18n('removeImage', 'Remove image'));
		var openLabel = esc(options.openLabel || layoutI18n('openMediaLibrary', 'Open media library'));
		var emptyLabel = esc(options.emptyLabel || '');
		var target = esc(options.target);
		var previewClass = options.previewClass || 'wpp-media-preview';

		return (
			'<div class="wpp-media-picker" data-target="' + target + '">' +
			'<div class="wpp-media-picker__toolbar">' +
			'<button type="button" class="wpp-builder-card__icon wpp-select-media" data-target="' + target + '" aria-label="' + selectLabel + '" title="' + selectLabel + '">' +
			'<span class="dashicons dashicons-format-image" aria-hidden="true"></span>' +
			'</button>' +
			'<button type="button" class="wpp-builder-card__icon wpp-builder-card__icon--danger wpp-remove-media" data-target="' + target + '" aria-label="' + removeLabel + '" title="' + removeLabel + '">' +
			'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' +
			'</button>' +
			'</div>' +
			'<button type="button" class="wpp-media-picker__preview wpp-open-media ' + previewClass + '" data-target="' + target + '" aria-label="' + openLabel + '" data-empty-label="' + emptyLabel + '"></button>' +
			'</div>'
		);
	}

	function builderReorderControls(options) {
		if (!options.showReorder) {
			return '';
		}

		var moveUpLabel = esc(layoutI18n('moveUp', 'Move up'));
		var moveDownLabel = esc(layoutI18n('moveDown', 'Move down'));
		var dragLabel = esc(layoutI18n('dragToReorder', 'Drag to reorder'));
		var upClass = esc(options.moveUpClass || 'wpp-move-item-up');
		var downClass = esc(options.moveDownClass || 'wpp-move-item-down');

		return (
			'<div class="wpp-builder-card__reorder">' +
			'<button type="button" class="wpp-builder-card__icon ' + upClass + '" aria-label="' + moveUpLabel + '" title="' + moveUpLabel + '">' +
			'<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>' +
			'</button>' +
			'<button type="button" class="wpp-builder-card__icon ' + downClass + '" aria-label="' + moveDownLabel + '" title="' + moveDownLabel + '">' +
			'<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>' +
			'</button>' +
			'<span class="wpp-card-reorder-handle" role="button" tabindex="0" aria-label="' + dragLabel + '" title="' + dragLabel + '">' +
			'<span class="dashicons dashicons-menu" aria-hidden="true"></span>' +
			'</span>' +
			'</div>'
		);
	}

	function builderCardHeader(options) {
		var toggleLabel = esc(layoutI18n('toggleItem', 'Toggle settings'));
		var cloneLabel = esc(layoutI18n('cloneItem', 'Clone'));
		var removeLabel = esc(layoutI18n('removeItem', 'Remove'));
		var labelPlaceholder = esc(layoutI18n('cardLabel', 'Label'));
		var expanded = options.collapsed ? 'false' : 'true';

		return (
			'<div class="wpp-builder-card__header">' +
			builderReorderControls(options) +
			'<button type="button" class="wpp-builder-card__toggle" aria-expanded="' + expanded + '" aria-label="' + toggleLabel + '">' +
			'<span class="wpp-builder-card__title">' + esc(options.title) + '</span>' +
			'<span class="dashicons dashicons-arrow-up-alt2 wpp-builder-card__arrow" aria-hidden="true"></span>' +
			'</button>' +
			'<input type="text" class="wpp-card-header-label ' + options.labelClass + '" value="' + esc(options.labelValue || '') + '" placeholder="' + labelPlaceholder + '" />' +
			'<div class="wpp-builder-card__actions">' +
			'<button type="button" class="wpp-builder-card__icon ' + options.cloneClass + '" aria-label="' + cloneLabel + '" title="' + cloneLabel + '">' +
			'<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>' +
			'</button>' +
			'<button type="button" class="wpp-builder-card__icon wpp-builder-card__icon--danger ' + options.removeClass + '" aria-label="' + removeLabel + '" title="' + removeLabel + '">' +
			'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' +
			'</button>' +
			'</div>' +
			'</div>'
		);
	}

	function syncCanvasInputs() {
		$builder.find('.wpp-canvas-width').val(config.canvas.width || 2000);
		$builder.find('.wpp-canvas-height').val(config.canvas.height || 2400);
		$builder.find('.wpp-canvas-background').val(config.canvas.background || '');
		updatePreview('.wpp-bg-preview', config.canvas.background);
	}

	function renderSlots() {
		captureCollapseState();

		var $wrap = $builder.find('.wpp-layout-builder__slots').empty();
		(config.image_slots || []).forEach(function (slot, index) {
			var $card = $(slotCardHtml(slot, index));
			updateSlotMaskPreview($card, slot.mask || '');
			$wrap.append($card);
		});

		applyCollapseState();
		updateSlotCardIndices();
		initSlotsSortable();
		updateToggleAllButton();
		refreshLayoutPreview();
	}

	function renderTextFields() {
		captureCollapseState();

		var $wrap = $builder.find('.wpp-layout-builder__text-fields').empty();
		(config.text_fields || []).forEach(function (field, index) {
			$wrap.append(textCardHtml(field, index));
		});

		applyCollapseState();
		updateToggleAllButton();
		refreshLayoutPreview();
	}

	function slotCardHtml(slot, index) {
		var cardId = esc(slot.id || '');

		return (
			'<div class="wpp-slot-card wpp-builder-card is-collapsed" data-index="' + index + '" data-card-id="' + cardId + '">' +
			builderCardHeader({
				title: formatSlotTitle(index),
				labelValue: slot.label || '',
				labelClass: 'slot-label',
				cloneClass: 'wpp-clone-slot',
				removeClass: 'wpp-remove-slot',
				showReorder: true,
				moveUpClass: 'wpp-move-slot-up',
				moveDownClass: 'wpp-move-slot-down',
				collapsed: true
			}) +
			'<div class="wpp-builder-card__body wpp-slot-card__body">' +
			'<div class="wpp-builder-section">' +
			'<div class="wpp-builder-section__grid wpp-builder-section__grid--id">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">ID</label><input type="text" class="wpp-canvas-field__input slot-id" value="' + cardId + '" /></div>' +
			'<div class="wpp-canvas-field wpp-canvas-field--checkbox">' +
			'<label class="wpp-slot-required-row"><input type="checkbox" class="slot-required" ' + (slot.required ? 'checked' : '') + ' /> Wymagane</label>' +
			'<span class="wpp-canvas-media__hint">Klient musi dodać zdjęcie przed złożeniem zamówienia.</span></div>' +
			'</div></div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Frame</span>' +
			'<div class="wpp-canvas-panel__grid wpp-canvas-panel__grid--frame">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">X</label><input type="number" class="wpp-canvas-field__input slot-x" value="' + (slot.frame && slot.frame.x) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Y</label><input type="number" class="wpp-canvas-field__input slot-y" value="' + (slot.frame && slot.frame.y) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">W</label><input type="number" class="wpp-canvas-field__input slot-w" value="' + (slot.frame && slot.frame.width) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">H</label><input type="number" class="wpp-canvas-field__input slot-h" value="' + (slot.frame && slot.frame.height) + '" /></div>' +
			'</div></div>' +
			'<div class="wpp-builder-section">' +
			'<div class="wpp-canvas-panel__body wpp-canvas-panel__body--split wpp-canvas-panel__body--compact">' +
			'<div class="wpp-canvas-panel__col wpp-canvas-panel__col--meta">' +
			'<span class="wpp-canvas-media__label">Mask image</span>' +
			'<p class="wpp-canvas-media__hint">Shape used to clip customer photos (PNG with transparency).</p>' +
			'<div class="wpp-canvas-field">' +
			'<label class="wpp-canvas-field__label">' + esc(layoutI18n('imageUrl', 'Image URL')) + '</label>' +
			'<input type="text" class="wpp-canvas-field__input slot-mask" value="' + esc(slot.mask || '') + '" placeholder="https://…" />' +
			'</div>' +
			'</div>' +
			'<div class="wpp-canvas-panel__col wpp-canvas-panel__col--media">' +
			mediaPickerHtml({
				target: 'mask',
				previewClass: 'wpp-media-preview slot-mask-preview',
				emptyLabel: layoutI18n('clickToSelectMask', 'Click to select mask image'),
				selectLabel: layoutI18n('selectMask', 'Select mask'),
				removeLabel: layoutI18n('removeMask', 'Remove mask')
			}) +
			'</div></div></div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Border</span>' +
			'<div class="wpp-canvas-panel__grid wpp-canvas-panel__grid--border">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Width</label><input type="number" class="wpp-canvas-field__input slot-border-width" min="0" step="1" value="' + ((slot.border && slot.border.width) || 0) + '" /></div>' +
			'<div class="wpp-canvas-field wpp-canvas-field--color"><label class="wpp-canvas-field__label">Color</label><input type="color" class="wpp-color-input slot-border-color" value="' + esc((slot.border && slot.border.color) || '#ffffff') + '" /></div>' +
			'</div>' +
			'<p class="wpp-canvas-media__hint">Outline follows mask shape. Set width to 0 to disable.</p>' +
			'</div>' +
			'</div></div>'
		);
	}

	function textControls(field, key) {
		var controls = field.controls || {};

		if (controls[key] === undefined) {
			return key === 'fontSize' ? false : true;
		}

		return !!controls[key];
	}

		function textCardHtml(field, index) {
		var style = field.style || {};
		var cardId = esc(field.id || '');
		var googleFontsHelp = layoutI18n('googleFontsHelpHtml', '');

		return (
			'<div class="wpp-text-card wpp-builder-card" data-index="' + index + '" data-card-id="' + cardId + '">' +
			builderCardHeader({
				title: formatTextFieldTitle(index),
				labelValue: field.label || '',
				labelClass: 'text-label',
				cloneClass: 'wpp-clone-text',
				removeClass: 'wpp-remove-text'
			}) +
			'<div class="wpp-builder-card__body wpp-text-card__body">' +
			'<div class="wpp-builder-section">' +
			'<div class="wpp-builder-section__grid wpp-builder-section__grid--id">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">ID</label><input type="text" class="wpp-canvas-field__input text-id" value="' + cardId + '" /></div>' +
			'<div class="wpp-canvas-field wpp-canvas-field--checkbox">' +
			'<label class="wpp-slot-required-row"><input type="checkbox" class="text-required" ' + (field.required ? 'checked' : '') + ' /> Required</label></div>' +
			'</div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Default text</label><textarea class="wpp-canvas-field__input text-default" rows="3">' + esc(field.default_value || '') + '</textarea></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Max length</label><input type="number" class="wpp-canvas-field__input text-max" value="' + (field.max_length || 20) + '" /></div>' +
			'</div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Position & size</span>' +
			'<div class="wpp-canvas-panel__grid wpp-canvas-panel__grid--frame">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">X</label><input type="number" class="wpp-canvas-field__input text-x" value="' + (style.x || 0) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Y</label><input type="number" class="wpp-canvas-field__input text-y" value="' + (style.y || 0) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">W</label><input type="number" class="wpp-canvas-field__input text-width" min="1" value="' + (style.width || 400) + '" /></div>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">H</label><input type="number" class="wpp-canvas-field__input text-height" min="1" value="' + (style.height || 80) + '" /></div>' +
			'</div></div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Typography</span>' +
			'<div class="wpp-canvas-panel__grid wpp-canvas-panel__grid--border">' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">Font size</label><input type="number" class="wpp-canvas-field__input text-size" min="8" value="' + (style.fontSize || 48) + '" /></div>' +
			'<div class="wpp-canvas-field wpp-canvas-field--color"><label class="wpp-canvas-field__label">Color</label><input type="color" class="wpp-color-input text-color" value="' + esc(style.color || '#ffffff') + '" /></div>' +
			'</div></div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Google Fonts</span>' +
			'<div class="wpp-canvas-field"><label class="wpp-canvas-field__label">CSS links</label><textarea class="wpp-canvas-field__input text-google-fonts" rows="3" placeholder="https://fonts.googleapis.com/css2?family=...">' +
			esc((field.google_fonts || []).join('\n')) +
			'</textarea></div>' +
			'<span class="wpp-canvas-media__hint wpp-google-fonts-help">' + googleFontsHelp + '</span></div>' +
			'<div class="wpp-builder-section">' +
			'<span class="wpp-builder-section__title">Customer controls</span>' +
			'<div class="wpp-canvas-field wpp-canvas-field--checkbox">' +
			'<label><input type="checkbox" class="text-ctrl-move" ' + (textControls(field, 'move') ? 'checked' : '') + ' /> Move (arrows in editor)</label> ' +
			'<label><input type="checkbox" class="text-ctrl-font-size" ' + (textControls(field, 'fontSize') ? 'checked' : '') + ' /> Font size (+/− in editor)</label>' +
			'</div></div>' +
			'</div></div>'
		);
	}
	function addImageSlot(e) {
		e.preventDefault();
		config.image_slots = config.image_slots || [];
		config.image_slots.push({
			id: 'photo_' + (config.image_slots.length + 1),
			label: 'Upload image',
			required: false,
			frame: { x: 100, y: 100, width: 400, height: 500 },
			controls: { move: true, scale: true, rotate: true, flip: true, autofit: true, reset: true }
		});
		renderSlots();
		syncJson();
		refreshLayoutPreview();
	}

	function addTextField(e) {
		e.preventDefault();
		config.text_fields = config.text_fields || [];
		config.text_fields.push({
			id: 'caption_' + (config.text_fields.length + 1),
			label: 'Text',
			required: true,
			max_length: 20,
			default_value: '',
			style: { x: 100, y: 200, width: 400, height: 80, fontSize: 48, color: '#ffffff', align: 'center' },
			controls: { move: true, fontSize: false }
		});
		renderTextFields();
		syncJson();
		refreshLayoutPreview();
	}

	function removeSlot(e) {
		e.preventDefault();
		e.stopPropagation();
		readFromDom();
		var index = $(this).closest('.wpp-slot-card').data('index');
		config.image_slots.splice(index, 1);
		renderSlots();
		syncJson();
		refreshLayoutPreview();
	}

	function initSlotsSortable() {
		var $wrap = $builder.find('.wpp-layout-builder__slots');

		if (!$wrap.length || typeof $.fn.sortable !== 'function') {
			return;
		}

		if ($wrap.hasClass('ui-sortable')) {
			$wrap.sortable('destroy');
		}

		if (!$wrap.children('.wpp-slot-card').length) {
			return;
		}

		$wrap.sortable({
			items: '> .wpp-slot-card',
			handle: '.wpp-card-reorder-handle',
			axis: 'y',
			containment: 'parent',
			tolerance: 'pointer',
			cursor: 'grabbing',
			distance: 5,
			placeholder: 'wpp-slot-card-placeholder',
			forcePlaceholderSize: true,
			stop: function () {
				readFromDom();
				updateSlotCardIndices();
				syncJson();
				refreshLayoutPreview();
			}
		});
	}

	function updateSlotCardIndices() {
		$builder.find('.wpp-slot-card').each(function (index) {
			var $card = $(this);

			$card.attr('data-index', index);
			$card.find('.wpp-builder-card__title').first().text(formatSlotTitle(index));
		});

		updateSlotMoveButtons();
	}

	function updateSlotMoveButtons() {
		var $cards = $builder.find('.wpp-slot-card');

		$cards.find('.wpp-move-slot-up, .wpp-move-slot-down').prop('disabled', false);
		$cards.first().find('.wpp-move-slot-up').prop('disabled', true);
		$cards.last().find('.wpp-move-slot-down').prop('disabled', true);
	}

	function moveSlotByArrow(e) {
		e.preventDefault();
		e.stopPropagation();

		if ($(this).prop('disabled')) {
			return;
		}

		readFromDom();

		var $card = $(this).closest('.wpp-slot-card');
		var index = parseInt($card.attr('data-index'), 10);
		var direction = $(this).hasClass('wpp-move-slot-up') ? -1 : 1;
		var newIndex = index + direction;

		if (isNaN(index) || newIndex < 0 || newIndex >= config.image_slots.length) {
			return;
		}

		var moved = config.image_slots.splice(index, 1)[0];
		config.image_slots.splice(newIndex, 0, moved);
		renderSlots();
		syncJson();
		refreshLayoutPreview();
	}

	function cloneSlot(e) {
		e.preventDefault();
		e.stopPropagation();

		readFromDom();
		captureCollapseState();

		var index = $(this).closest('.wpp-slot-card').data('index');
		var source = config.image_slots[index];

		if (!source) {
			return;
		}

		var clone = JSON.parse(JSON.stringify(source));
		var nextId = 1;

		(config.image_slots || []).forEach(function (slot) {
			var match = String(slot.id || '').match(/^photo_(\d+)$/);

			if (match) {
				nextId = Math.max(nextId, parseInt(match[1], 10) + 1);
			}
		});

		clone.id = 'photo_' + nextId;
		config.image_slots.splice(index + 1, 0, clone);
		renderSlots();
		syncJson();
		refreshLayoutPreview();
	}

	function toggleBuilderCard(e) {
		e.preventDefault();
		e.stopPropagation();

		var $card = $(this).closest('.wpp-builder-card');

		if (!$card.length) {
			return;
		}

		var collapsed = !$card.hasClass('is-collapsed');

		setCardCollapsed($card, collapsed);
		syncCollapseStateFromCard($card, collapsed);
		updateToggleAllButton();
	}

	function copyLayoutJson(e) {
		e.preventDefault();
		e.stopPropagation();

		readFromDom();
		syncJson();

		var text = $builder.find('.wpp-layout-json').val() || '';

		function showCopied() {
			var $btn = $builder.find('.wpp-json-copy').first();
			var originalTitle = $btn.attr('title') || layoutI18n('copyJson', 'Copy JSON');

			$btn.addClass('is-copied');
			$btn.attr('title', layoutI18n('copyJsonSuccess', 'JSON copied to clipboard.'));

			window.setTimeout(function () {
				$btn.removeClass('is-copied');
				$btn.attr('title', originalTitle);
			}, 2000);
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(showCopied).catch(function () {
				window.prompt(layoutI18n('copyJson', 'Copy JSON'), text);
			});
			return;
		}

		window.prompt(layoutI18n('copyJson', 'Copy JSON'), text);
		showCopied();
	}

	function removeText(e) {
		e.preventDefault();
		e.stopPropagation();
		readFromDom();
		var index = $(this).closest('.wpp-text-card').data('index');
		config.text_fields.splice(index, 1);
		renderTextFields();
		syncJson();
		refreshLayoutPreview();
	}

	function cloneText(e) {
		e.preventDefault();
		e.stopPropagation();

		readFromDom();
		captureCollapseState();

		var index = $(this).closest('.wpp-text-card').data('index');
		var source = config.text_fields[index];

		if (!source) {
			return;
		}

		var clone = JSON.parse(JSON.stringify(source));
		var nextId = 1;

		(config.text_fields || []).forEach(function (field) {
			var match = String(field.id || '').match(/^caption_(\d+)$/);

			if (match) {
				nextId = Math.max(nextId, parseInt(match[1], 10) + 1);
			}
		});

		clone.id = 'caption_' + nextId;
		config.text_fields.splice(index + 1, 0, clone);
		renderTextFields();
		syncJson();
		refreshLayoutPreview();
	}


	function openMediaFromPreview(e) {
		e.preventDefault();
		e.stopPropagation();

		var target = $(this).data('target');
		var $trigger = $(this).closest('.wpp-media-picker').find('.wpp-select-media[data-target="' + target + '"]').first();

		if (!$trigger.length) {
			$trigger = $builder.find('.wpp-select-media[data-target="' + target + '"]').first();
		}

		if ($trigger.length) {
			selectMedia.call($trigger[0], e);
		}
	}

	function selectMedia(e) {
		e.preventDefault();
		var $btn = $(this);
		var target = $btn.data('target');
		var $slotCard = $btn.closest('.wpp-slot-card');
		var frame = wp.media({
			title: target === 'mask' ? 'Select mask image' : 'Select image',
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();

			if (target === 'mask' && $slotCard.length) {
				setSlotMask($slotCard, attachment.url);
				return;
			}

			setCanvasMedia(target, attachment.url);
		});
		frame.open();
	}

	function removeMedia(e) {
		e.preventDefault();
		var $btn = $(this);
		var target = $btn.data('target');
		var $slotCard = $btn.closest('.wpp-slot-card');

		if (target === 'mask' && $slotCard.length) {
			setSlotMask($slotCard, '');
			return;
		}

		setCanvasMedia(target, '');
	}

	function setSlotMask($card, url) {
		$card.find('.slot-mask').val(url || '');
		updateSlotMaskPreview($card, url || '');
		readFromDom();
		syncJson();
	}

	function setCanvasMedia(target, url) {
		if (target === 'background') {
			config.canvas.background = url || '';
			$builder.find('.wpp-canvas-background').val(url || '');
			updatePreview('.wpp-bg-preview', url);
		}
		syncJson();
		refreshLayoutPreview();
	}

	function onFieldChange(e) {
		if (e && e.target) {
			var $target = $(e.target);

			if ($target.hasClass('slot-mask')) {
				updateSlotMaskPreview($target.closest('.wpp-slot-card'), $target.val());
			}

			if ($target.hasClass('wpp-canvas-background')) {
				updatePreview('.wpp-bg-preview', $target.val());
			}

			if ($target.hasClass('slot-id')) {
				$target.closest('.wpp-slot-card').attr('data-card-id', $target.val());
			}

			if ($target.hasClass('text-id')) {
				$target.closest('.wpp-text-card').attr('data-card-id', $target.val());
			}
		}
		readFromDom();
		syncJson();
		refreshLayoutPreview();
	}

	function readFromDom() {
		config.canvas = {
			width: parseInt($builder.find('.wpp-canvas-width').val(), 10) || 2000,
			height: parseInt($builder.find('.wpp-canvas-height').val(), 10) || 2400,
			background: $builder.find('.wpp-canvas-background').val(),
			overlay: config.canvas && config.canvas.overlay ? config.canvas.overlay : ''
		};

		config.image_slots = [];
		$builder.find('.wpp-slot-card').each(function () {
			var $c = $(this);
			config.image_slots.push({
				id: $c.find('.slot-id').val(),
				label: $c.find('.slot-label').val(),
				required: $c.find('.slot-required').is(':checked'),
				frame: {
					x: parseInt($c.find('.slot-x').val(), 10) || 0,
					y: parseInt($c.find('.slot-y').val(), 10) || 0,
					width: parseInt($c.find('.slot-w').val(), 10) || 100,
					height: parseInt($c.find('.slot-h').val(), 10) || 100
				},
				mask: $c.find('.slot-mask').val(),
				border: {
					width: parseInt($c.find('.slot-border-width').val(), 10) || 0,
					color: $c.find('.slot-border-color').val() || '#ffffff'
				},
				controls: { move: true, scale: true, rotate: true, flip: true, autofit: true, reset: true }
			});
		});

		config.text_fields = [];
		$builder.find('.wpp-text-card').each(function () {
			var $c = $(this);
			config.text_fields.push({
				id: $c.find('.text-id').val(),
				label: $c.find('.text-label').val(),
				default_value: $c.find('.text-default').val() || '',
				required: $c.find('.text-required').is(':checked'),
				max_length: parseInt($c.find('.text-max').val(), 10) || 20,
				google_fonts: parseGoogleFontLines($c.find('.text-google-fonts').val()),
				type: 'textarea',
				style: {
					x: parseInt($c.find('.text-x').val(), 10) || 0,
					y: parseInt($c.find('.text-y').val(), 10) || 0,
					width: parseInt($c.find('.text-width').val(), 10) || 400,
					height: parseInt($c.find('.text-height').val(), 10) || 80,
					fontSize: parseInt($c.find('.text-size').val(), 10) || 48,
					color: $c.find('.text-color').val() || '#ffffff',
					align: 'center',
					fontFamily: 'Arial'
				},
				controls: {
					move: $c.find('.text-ctrl-move').is(':checked'),
					fontSize: $c.find('.text-ctrl-font-size').is(':checked')
				}
			});
		});
	}

	function syncJson() {
		$builder.find('.wpp-layout-json').val(JSON.stringify(config, null, 2));
	}

	function updatePreview(selector, url) {
		var $p = $builder.find(selector);
		if (url) {
			$p.html('<img src="' + esc(url) + '" alt="" />').addClass('has-image');
		} else {
			$p.empty().removeClass('has-image');
		}
	}

	function updateSlotMaskPreview($card, url) {
		var $p = $card.find('.slot-mask-preview');

		if (url) {
			$p.html('<img src="' + esc(url) + '" alt="" />');
		} else {
			$p.empty();
		}
	}

	function esc(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function parseGoogleFontLines(text) {
		if (window.WppGoogleFonts && typeof window.WppGoogleFonts.parseLines === 'function') {
			return window.WppGoogleFonts.parseLines(text);
		}

		return String(text || '')
			.split(/\r?\n/)
			.map(function (line) {
				return line.trim();
			})
			.filter(function (line) {
				return /^https:\/\/fonts\.googleapis\.com\/css/i.test(line);
			});
	}

	function refreshLayoutPreview() {
		if (window.WppLayoutPreview && typeof window.WppLayoutPreview.refresh === 'function') {
			window.WppLayoutPreview.refresh();
		}
	}

	function togglePreviewZoom(e) {
		e.preventDefault();

		var $workspace = $builder.find('.wpp-layout-builder__workspace');
		var $btn = $(e.currentTarget);
		var zoomed = !$workspace.hasClass('is-preview-zoomed');

		$workspace.toggleClass('is-preview-zoomed', zoomed);
		$btn.toggleClass('is-active', zoomed);
		$btn.attr('aria-pressed', zoomed ? 'true' : 'false');
		$btn.attr(
			'title',
			zoomed ? 'Przywróć dopasowanie do panelu' : 'Powiększ podgląd do 100%'
		);
		$btn.attr(
			'aria-label',
			zoomed ? 'Przywróć dopasowanie podglądu' : 'Powiększ podgląd do 100%'
		);

		if (window.WppLayoutPreview && typeof window.WppLayoutPreview.setZoomMode === 'function') {
			window.WppLayoutPreview.setZoomMode(zoomed);
			window.requestAnimationFrame(function () {
				if (window.WppLayoutPreview && typeof window.WppLayoutPreview.refresh === 'function') {
					window.WppLayoutPreview.refresh();
				}
			});
		}
	}

	function initLayoutPreview() {
		if (!window.WppLayoutPreview || typeof window.WppLayoutPreview.init !== 'function') {
			return;
		}

		window.WppLayoutPreview.init({
			$builder: $builder,
			getConfig: function () {
				return config;
			},
			onSlotUpdate: function (index, x, y, w, h) {
				var $card = $builder.find('.wpp-slot-card').eq(index);
				$card.find('.slot-x').val(x);
				$card.find('.slot-y').val(y);
				$card.find('.slot-w').val(w);
				$card.find('.slot-h').val(h);
				readFromDom();
				syncJson();
			},
			onTextUpdate: function (index, x, y, w, h) {
				var $card = $builder.find('.wpp-text-card').eq(index);
				$card.find('.text-x').val(x);
				$card.find('.text-y').val(y);
				$card.find('.text-width').val(w);
				$card.find('.text-height').val(h);
				readFromDom();
				syncJson();
			}
		});
	}

	$(initLayoutBuilder);
})(jQuery);
