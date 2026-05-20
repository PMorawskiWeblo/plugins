(function ($) {
	'use strict';

	if (typeof wppData === 'undefined') {
		return;
	}

	function wppLog(message, data) {
		if (window.WppDebug && window.WppDebug.isEnabled()) {
			window.WppDebug.log(message, data);
		}
	}

	function wppWarn(message, data) {
		if (window.WppDebug && window.WppDebug.isEnabled()) {
			window.WppDebug.warn(message, data);
		}
	}

	function wppError(message, data) {
		if (window.WppDebug && window.WppDebug.isEnabled()) {
			window.WppDebug.error(message, data);
		}
	}

	if (typeof Konva === 'undefined') {
		wppError('Konva.js failed to load.');
	}

	var state = {
		textFields: {},
		imageFields: {},
		acceptance: { checked: false },
		valid: false,
		activeSlot: null,
		activeTextField: null
	};

	var stage, textLayer, photoLayer, borderLayer, bgLayer, overlayLayer, previewScale = 1;
	var imageNodes = {};
	var slotBorderNodes = {};
	var textNodes = {};
	var maskCache = {};
	var previewResizeObserver = null;

	function ensureTextFieldState(fieldId) {
		var current = state.textFields[fieldId];

		if (!current || typeof current === 'string') {
			state.textFields[fieldId] = {
				value: typeof current === 'string' ? current : '',
				offsetX: 0,
				offsetY: 0,
				fontSize: null,
				fontFamily: null
			};
		}

		return state.textFields[fieldId];
	}

	function getTextFieldValue(fieldId) {
		var data = state.textFields[fieldId];

		if (!data) {
			return '';
		}

		if (typeof data === 'string') {
			return data;
		}

		return data.value || '';
	}

	function getTextFieldConfig(fieldId) {
		return (wppData.layout.text_fields || []).find(function (f) {
			return f.id === fieldId;
		});
	}

	function getBaseFontSize(field) {
		return (field.style && field.style.fontSize) || 48;
	}

	function getEffectiveFontSize(field, meta) {
		if (meta && meta.fontSize !== null && meta.fontSize !== undefined) {
			return meta.fontSize;
		}

		return getBaseFontSize(field);
	}

	function getEffectiveFontFamily(field, meta) {
		if (window.WppGoogleFonts && typeof window.WppGoogleFonts.getEffectiveFontFamily === 'function') {
			return window.WppGoogleFonts.getEffectiveFontFamily(field, meta);
		}

		return (field && field.style && field.style.fontFamily) || 'Arial';
	}

	function loadLayoutGoogleFonts(callback) {
		if (!window.WppGoogleFonts || typeof window.WppGoogleFonts.loadUrls !== 'function') {
			if (typeof callback === 'function') {
				callback();
			}
			return;
		}

		window.WppGoogleFonts.loadUrls(window.WppGoogleFonts.collectLayoutUrls(wppData.layout), callback);
	}

	function syncFontFamilySelect(field) {
		var $select = $('.wpp-font-family-select');
		var resolved = window.WppGoogleFonts ? window.WppGoogleFonts.resolveFieldFonts(field) : { options: [] };
		var meta = field && field.id ? ensureTextFieldState(field.id) : null;
		var current = getEffectiveFontFamily(field, meta);

		$select.empty();

		resolved.options.forEach(function (opt) {
			$select.append(
				$('<option></option>').attr('value', opt.family).text(opt.family)
			);
		});

		$select.val(current);
	}

	function textFieldHasFontChoice(field) {
		return window.WppGoogleFonts && window.WppGoogleFonts.shouldShowFontSelect(field);
	}

	function getFieldDisplayText(field, meta) {
		if (!field) {
			return '';
		}

		if (meta && String(meta.value || '').length) {
			return String(meta.value);
		}

		if (field.default_value) {
			return String(field.default_value);
		}

		return '';
	}

	function syncTextMetaFromField(field) {
		if (!field || !field.id) {
			return;
		}

		var meta = ensureTextFieldState(field.id);

		if (!String(meta.value || '').length && field.default_value) {
			meta.value = String(field.default_value);
		}
	}

	function getLayerSnapshot() {
		var slots = {};

		Object.keys(imageNodes).forEach(function (id) {
			var node = imageNodes[id];
			if (!node) {
				return;
			}

			var ps = node._photoState;
			slots[id] = {
				className: node.getClassName ? node.getClassName() : 'unknown',
				visible: node.visible ? node.visible() : null,
				x: node.x ? node.x() : null,
				y: node.y ? node.y() : null,
				width: node.width ? node.width() : null,
				height: node.height ? node.height() : null,
				hasMask: !!node._hasMask,
				hasPhotoState: !!ps,
				imgSrc: ps && ps.img ? ps.img.src || '' : '',
				maskSrc: ps && ps.mask ? ps.mask.src || '' : '',
				parent: node.getParent ? !!node.getParent() : false,
				zIndex: node.zIndex ? node.zIndex() : null
			};
		});

		return {
			previewScale: previewScale,
			photoLayerChildren: photoLayer ? photoLayer.getChildren().length : 0,
			imageNodeKeys: Object.keys(imageNodes),
			maskCacheKeys: Object.keys(maskCache),
			slots: slots
		};
	}

	function init() {
		bindEvents();

		if (!$('#wpp-canvas-container').length) {
			return;
		}

		if (typeof Konva === 'undefined') {
			return;
		}

		buildFormFields();
		initCanvas();
		bindPreviewResize();
		moveHiddenFieldsToCartForm();
		validate();

		wppLog('init:personalizer', {
			layoutId: wppData.layoutId,
			slots: (wppData.layout.image_slots || []).map(function (s) {
				return { id: s.id, frame: s.frame, mask: s.mask };
			})
		});
	}

	function moveHiddenFieldsToCartForm() {
		var $form = $('form.cart');
		if (!$form.length || $form.find('input.wpp-project-state').length) {
			return;
		}
		$form.append('<input type="hidden" name="wpp_personalizer_nonce" class="wpp-personalizer-nonce" value="" />');
		$form.append('<input type="hidden" name="wpp_project_state" class="wpp-project-state" value="" />');
		$form.append('<input type="hidden" name="wpp_preview_data" class="wpp-preview-data" value="" />');
		$form.append('<input type="hidden" name="wpp_preview_layers_data" class="wpp-preview-layers-data" value="" />');
	}

	function bindEvents() {
		$(document).on('click', '.wpp-open-personalizer', openModal);
		$(document).on('click', '[data-wpp-close]', closeModal);
		$(document).on('click', '.wpp-save-personalization', saveAndClose);
		$(document).on('input', '.wpp-text-input', onTextInput);
		$(document).on('change', '.wpp-acceptance-checkbox', onAcceptanceChange);
		$(document).on('change', '.wpp-image-upload', onImageUpload);
		$(document).on('click', '.wpp-image-field', onImageFieldActivate);
		$(document).on('click', '.wpp-image-field__remove', onImageRemove);
		$(document).on('click', '.wpp-image-upload-btn', function () {
			setActiveImageField($(this).closest('.wpp-image-field').data('slot-id'));
		});
		$(document).on('click', '.wpp-transform-toolbar [data-action]', onTransformAction);
		$(document).on('change', '.wpp-font-family-select', onFontFamilyChange);
		$(document).on('focus', '.wpp-text-input', onTextFieldFocus);
		$(document).on('click', '.wpp-text-field', onTextFieldActivate);
		$('form.cart').on('submit', onCartSubmit);
	}

	function openModal(e) {
		e.preventDefault();
		e.stopPropagation();

		var $modal = $('#wpp-modal');
		if (!$modal.length) {
			wppWarn('#wpp-modal not found in DOM.');
			return;
		}

		$modal.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('wpp-modal-open');

		requestAnimationFrame(function () {
			resizePreview();
			renderAllText();
		});
	}

	function closeModal() {
		$('#wpp-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('body').removeClass('wpp-modal-open');
	}


	function truncateFilename(name, maxLen) {
		maxLen = maxLen || 15;
		if (!name) {
			return '';
		}
		if (name.length <= maxLen) {
			return name;
		}
		return name.substring(0, maxLen) + '…';
	}

	function filenameFromPath(pathOrUrl) {
		if (!pathOrUrl) {
			return '';
		}
		var parts = String(pathOrUrl).split(/[\\/]/);
		return parts[parts.length - 1] || '';
	}

	function chooseFileLabel() {
		return wppData.i18n.chooseFile || wppData.i18n.uploadImage || 'Choose file';
	}

	function requiredMarkHtml() {
		return '<span class="wpp-required" aria-hidden="true">*</span>';
	}

	function getCheckIconHtml() {
		return (
			'<span class="wpp-btn-icon wpp-btn-icon--check" aria-hidden="true">' +
			'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check">' +
			'<path d="M20 6 9 17l-5-5"/>' +
			'</svg></span>'
		);
	}

	function getDefaultButtonLabel() {
		return wppData.buttonLabel || wppData.i18n.openPersonalizer || 'Personalize product';
	}

	function getCompletedButtonLabel() {
		return wppData.buttonLabelCompleted || wppData.i18n.personalized || 'Personalized';
	}

	function updatePersonalizeButtons() {
		var $buttons = $('.wpp-open-personalizer');

		if (!$buttons.length) {
			return;
		}

		var defaultLabel = getDefaultButtonLabel();
		var completedLabel = getCompletedButtonLabel();

		$buttons.each(function () {
			var $btn = $(this);
			var $inner = $('<span class="wpp-btn-inner" />');

			if (!wppData.validationEnabled) {
				$btn.removeClass('wpp-is-valid wpp-is-required');
				$inner.append($('<span class="wpp-btn-text" />').text(defaultLabel));
				$btn.empty().append($inner);
				return;
			}

			if (state.valid) {
				$btn.removeClass('wpp-is-required').addClass('wpp-is-valid');
				$inner.append($(getCheckIconHtml())).append($('<span class="wpp-btn-text" />').text(completedLabel));
			} else {
				$btn.removeClass('wpp-is-valid').addClass('wpp-is-required');
				$inner.append($('<span class="wpp-btn-text" />').text(defaultLabel)).append($(requiredMarkHtml()));
			}

			$btn.empty().append($inner);
		});
	}

	function setImageUploadLoading(slotId, isLoading) {
		var $field = $('.wpp-image-field[data-slot-id="' + slotId + '"]');

		$field.toggleClass('is-uploading', !!isLoading);
		$field.find('.wpp-image-upload-spinner').prop('hidden', !isLoading);
		$field.find('.wpp-image-upload-btn').prop('disabled', !!isLoading);
	}

	function updateUploadButton(slotId, fileName) {
		var $field = $('.wpp-image-field[data-slot-id="' + slotId + '"]');
		var text = fileName ? truncateFilename(fileName) : chooseFileLabel();
		$field.find('.wpp-image-upload-btn').text(text);
		$field.toggleClass('has-file', !!fileName);
		$field.find('.wpp-image-field__remove').prop('hidden', !fileName);
	}

	function setActiveImageField(slotId) {
		if (!slotId) {
			return;
		}

		state.activeSlot = slotId;
		state.activeTextField = null;
		$('.wpp-image-field').removeClass('is-active');
		$('.wpp-text-field').removeClass('is-active');
		$('.wpp-image-field[data-slot-id="' + slotId + '"]').addClass('is-active');
		updateTransformToolbar();
	}

	function setActiveTextField(fieldId) {
		if (!fieldId) {
			return;
		}

		state.activeTextField = fieldId;
		state.activeSlot = null;
		$('.wpp-text-field').removeClass('is-active');
		$('.wpp-image-field').removeClass('is-active');
		$('.wpp-text-field[data-field-id="' + fieldId + '"]').addClass('is-active');
		updateTransformToolbar();
	}

	function setToolbarGroupVisible($group, visible) {
		if (!$group || !$group.length) {
			return;
		}

		$group.prop('hidden', !visible);
	}

	function textControlEnabled(controls, key) {
		if (!controls || controls[key] === undefined) {
			return key === 'move';
		}

		return !!controls[key];
	}

	function updateTransformToolbar() {
		var $toolbar = $('.wpp-transform-toolbar');
		var $moveGroup = $toolbar.find('.wpp-toolbar-group--move');
		var $imageGroup = $toolbar.find('.wpp-toolbar-group--image');
		var $fontGroup = $toolbar.find('.wpp-toolbar-group--text-font');
		var $fontFamilyGroup = $toolbar.find('.wpp-toolbar-group--text-font-family');

		if (state.activeTextField) {
			var field = getTextFieldConfig(state.activeTextField);
			var controls = (field && field.controls) || {};

			$toolbar.prop('hidden', false);
			setToolbarGroupVisible($imageGroup, false);
			setToolbarGroupVisible($moveGroup, textControlEnabled(controls, 'move'));
			setToolbarGroupVisible($fontGroup, textControlEnabled(controls, 'fontSize'));
			if (field && textFieldHasFontChoice(field)) {
				syncFontFamilySelect(field);
				setToolbarGroupVisible($fontFamilyGroup, true);
			} else {
				setToolbarGroupVisible($fontFamilyGroup, false);
			}
			return;
		}

		if (state.activeSlot && state.imageFields[state.activeSlot] && state.imageFields[state.activeSlot].source) {
			$toolbar.prop('hidden', false);
			setToolbarGroupVisible($moveGroup, true);
			setToolbarGroupVisible($imageGroup, true);
			setToolbarGroupVisible($fontGroup, false);
			setToolbarGroupVisible($fontFamilyGroup, false);
			return;
		}

		$toolbar.prop('hidden', true);
		setToolbarGroupVisible($fontFamilyGroup, false);
	}

	function onTextFieldFocus() {
		setActiveTextField($(this).data('field-id'));
	}

	function onFontFamilyChange() {
		var fieldId = state.activeTextField;
		var field = getTextFieldConfig(fieldId);

		if (!fieldId || !field) {
			return;
		}

		var meta = ensureTextFieldState(fieldId);
		meta.fontFamily = $(this).val() || null;
		renderText(fieldId);
	}

	function onTextFieldActivate(e) {
		if ($(e.target).closest('.wpp-text-input').length) {
			return;
		}

		var fieldId = $(this).data('field-id');
		if (fieldId) {
			setActiveTextField(fieldId);
		}
	}

	function buildFormFields() {
		var $imgWrap = $('.wpp-image-fields').empty();
		var $txtWrap = $('.wpp-text-fields').empty();
		var uploadAccept = (wppData.allowedMimeTypes || []).join(',');

		if (!uploadAccept) {
			uploadAccept = 'image/jpeg,image/png,image/webp';
		}

		(wppData.layout.image_slots || []).forEach(function (slot) {
			var inputId = 'wpp-file-' + String(slot.id).replace(/[^a-z0-9_-]/gi, '_');
			var chooseLabel = wppData.i18n.chooseFile || wppData.i18n.uploadImage || 'Choose file';
			var removeLabel = wppData.i18n.removeImage || 'Remove';
			var html =
				'<div class="wpp-field wpp-image-field" data-slot-id="' + esc(slot.id) + '">' +
				'<span class="wpp-image-field__label"><span class="wpp-field-label-text">' + esc(slot.label || slot.id) + '</span>' + (slot.required ? requiredMarkHtml() : '') + '</span>' +
				'<button type="button" class="wpp-image-field__remove" hidden>' + esc(removeLabel) + '</button>' +
				'<div class="wpp-image-upload-wrap">' +
				'<span class="wpp-image-upload-spinner" aria-hidden="true" hidden></span>' +
				'<input type="file" id="' + esc(inputId) + '" class="wpp-image-upload" accept="' + esc(uploadAccept) + '" data-slot-id="' + esc(slot.id) + '" />' +
				'<label for="' + esc(inputId) + '" class="button wpp-image-upload-btn">' + esc(chooseLabel) + '</label>' +
				'</div>' +
				'<p class="wpp-field-error" role="alert" hidden></p>' +
				'</div>';
			$imgWrap.append(html);
		});

		(wppData.layout.text_fields || []).forEach(function (field) {
			syncTextMetaFromField(field);
			var max = field.max_length || 0;
			var defaultVal = getFieldDisplayText(field, state.textFields[field.id]);
			var requiredMark = field.required ? requiredMarkHtml() : '';
			var html =
				'<div class="wpp-field wpp-text-field" data-field-id="' + esc(field.id) + '">' +
				'<label><span class="wpp-field-label-text">' + esc(field.label || field.id) + '</span>' + requiredMark + '</label>' +
				'<textarea class="wpp-text-input" data-field-id="' + esc(field.id) + '" maxlength="' + (max || 500) + '" placeholder="' + esc(field.placeholder || '') + '">' + esc(defaultVal) + '</textarea>' +
				(max ? '<div class="wpp-char-counter"><span class="wpp-char-current">' + defaultVal.length + '</span>/' + max + '</div>' : '') +
				'<p class="wpp-field-error" role="alert" hidden></p>' +
				'</div>';
			$txtWrap.append(html);
			var meta = ensureTextFieldState(field.id);
			meta.value = defaultVal;
		});
	}

	function getPreviewContainerWidth(container, canvasWidth) {
		if (!container) {
			return canvasWidth || 800;
		}

		var width = container.clientWidth;
		if (width > 0) {
			return width;
		}

		var preview = container.closest('.wpp-editor__preview');
		if (preview && preview.clientWidth > 0) {
			return preview.clientWidth;
		}

		return canvasWidth || 800;
	}

	function resizeLayerBackgrounds() {
		if (!stage) {
			return;
		}

		var w = stage.width();
		var h = stage.height();

		[bgLayer, overlayLayer].forEach(function (targetLayer) {
			if (!targetLayer) {
				return;
			}

			targetLayer.getChildren().forEach(function (child) {
				if (child.getClassName && child.getClassName() === 'Image') {
					child.width(w);
					child.height(h);
				}
			});
			targetLayer.batchDraw();
		});
	}

	function resizePreview() {
		if (!stage) {
			return;
		}

		var canvas = wppData.layout.canvas || {};
		var width = canvas.width || 800;
		var height = canvas.height || 1000;
		var container = document.getElementById('wpp-canvas-container');

		if (!container) {
			return;
		}

		var maxWidth = getPreviewContainerWidth(container, width);
		var newScale = maxWidth / width;

		if (!maxWidth || newScale <= 0) {
			return;
		}

		if (Math.abs(newScale - previewScale) < 0.001) {
			stage.draw();
			return;
		}

		var oldScale = previewScale;
		var scaleRatio = newScale / oldScale;

		previewScale = newScale;
		stage.width(width * previewScale);
		stage.height(height * previewScale);
		resizeLayerBackgrounds();

		Object.keys(imageNodes).forEach(function (slotId) {
			var node = imageNodes[slotId];
			var frame = node._frame;

			if (!frame) {
				return;
			}

			var fw = frame.width * previewScale;
			var fh = frame.height * previewScale;

			if (node._hasMask && node._photoState) {
				var ps = node._photoState;

				ps.frameW = fw;
				ps.frameH = fh;
				ps.offsetX *= scaleRatio;
				ps.offsetY *= scaleRatio;
				ps.baseDrawW *= scaleRatio;
				ps.baseDrawH *= scaleRatio;
				node.position({ x: frame.x * previewScale, y: frame.y * previewScale });
				node.width(fw);
				node.height(fh);
				refreshSlotNode(node);
				return;
			}

			if (node._konvaImage && node._photoState) {
				var groupPs = node._photoState;

				groupPs.offsetX *= scaleRatio;
				groupPs.offsetY *= scaleRatio;
				groupPs.baseDrawW *= scaleRatio;
				groupPs.baseDrawH *= scaleRatio;
				node.position({ x: frame.x * previewScale, y: frame.y * previewScale });
				node.clip({ x: 0, y: 0, width: fw, height: fh });
				applyPhotoState(node);
			}
		});

		renderAllText();
		renderAllSlotBorders();

		if (photoLayer) {
			photoLayer.batchDraw();
		}
		if (borderLayer) {
			borderLayer.batchDraw();
		}
		if (textLayer) {
			textLayer.batchDraw();
		}

		stage.draw();

		wppLog('resizePreview', {
			maxWidth: maxWidth,
			previewScale: previewScale,
			stage: { w: stage.width(), h: stage.height() }
		});
	}

	function bindPreviewResize() {
		var container = document.getElementById('wpp-canvas-container');

		if (!container || previewResizeObserver || typeof ResizeObserver === 'undefined') {
			return;
		}

		previewResizeObserver = new ResizeObserver(function () {
			var $modal = $('#wpp-modal');

			if ($modal.length && !$modal.hasClass('is-open')) {
				return;
			}

			resizePreview();
		});

		previewResizeObserver.observe(container);

		var preview = container.closest('.wpp-editor__preview');
		if (preview && preview !== container) {
			previewResizeObserver.observe(preview);
		}
	}

	function initCanvas() {
		var canvas = wppData.layout.canvas || {};
		var width = canvas.width || 800;
		var height = canvas.height || 1000;
		var container = document.getElementById('wpp-canvas-container');

		if (!container) {
			return;
		}

		var maxWidth = getPreviewContainerWidth(container, width);
		previewScale = maxWidth / width;

		stage = new Konva.Stage({
			container: 'wpp-canvas-container',
			width: width * previewScale,
			height: height * previewScale
		});

		bgLayer = new Konva.Layer();
		photoLayer = new Konva.Layer();
		borderLayer = new Konva.Layer();
		overlayLayer = new Konva.Layer();
		textLayer = new Konva.Layer();
		stage.add(bgLayer);
		stage.add(photoLayer);
		stage.add(borderLayer);
		stage.add(overlayLayer);
		stage.add(textLayer);
		borderLayer.zIndex(2);
		overlayLayer.zIndex(3);
		textLayer.zIndex(4);

		if (canvas.background) {
			loadImage(canvas.background, function (img) {
				bgLayer.add(
					new Konva.Image({
						image: img,
						width: stage.width(),
						height: stage.height()
					})
				);
				bgLayer.draw();
			});
		}

		function finishCanvasInit() {
			loadLayoutGoogleFonts(function () {
				preloadMasks();
				renderAllText();
				if (borderLayer) {
					borderLayer.moveToTop();
				}
				if (textLayer) {
					textLayer.moveToTop();
				}
			});
		}

		if (canvas.overlay && String(canvas.overlay).trim() !== '') {
			loadImage(canvas.overlay, function (img) {
				overlayLayer.add(
					new Konva.Image({
						image: img,
						width: stage.width(),
						height: stage.height(),
						listening: false
					})
				);
				overlayLayer.draw();
				finishCanvasInit();
			});
		} else {
			finishCanvasInit();
		}

		wppLog('initCanvas:done', {
			canvas: canvas,
			previewScale: previewScale
		});
	}

	function preloadMasks() {
		var slots = (wppData.layout.image_slots || []).filter(function (slot) {
			return !!slot.mask;
		});

		if (!slots.length) {
			renderAllSlotBorders();
			return;
		}

		var pending = slots.length;

		slots.forEach(function (slot) {
			loadImage(
				slot.mask,
				function (img) {
					maskCache[slot.id] = img;
					renderSlotBorder(slot.id);
					wppLog('preloadMask:ok', { slotId: slot.id, mask: slot.mask });
					pending -= 1;
					if (pending <= 0 && borderLayer) {
						borderLayer.batchDraw();
					}
				},
				'preload-mask:' + slot.id
			);
		});
	}

	function loadImage(url, cb, label) {
		wppLog('loadImage:start', { url: url, label: label || '' });

		var img = new window.Image();
		img.crossOrigin = 'anonymous';
		img.onload = function () {
			wppLog('loadImage:ok', {
				label: label || '',
				url: url,
				width: img.width,
				height: img.height
			});
			cb(img);
		};
		img.onerror = function () {
			wppError('loadImage:fail', { url: url, label: label || '' });
		};
		img.src = url;
	}

	function getRequiredFieldMessage() {
		return wppData.i18n.requiredField || 'This field is required.';
	}

	function clearFieldError($field) {
		if (!$field || !$field.length) {
			return;
		}

		$field.removeClass('has-error');
		$field.find('.wpp-field-error').attr('hidden', true).text('');
	}

	function setFieldError($field, message) {
		if (!$field || !$field.length) {
			return;
		}

		$field.addClass('has-error');
		$field.find('.wpp-field-error').removeAttr('hidden').text(message || getRequiredFieldMessage());
	}

	function clearAllFieldErrors() {
		$('.wpp-text-field, .wpp-image-field, .wpp-acceptance').each(function () {
			clearFieldError($(this));
		});
	}

	function scrollToField($field) {
		if (!$field || !$field.length) {
			return;
		}

		var $scrollParent = $('.wpp-modal__body');

		if (!$scrollParent.length) {
			$scrollParent = $('.wpp-editor__controls');
		}

		if ($scrollParent.length && $scrollParent[0] !== document.documentElement) {
			var offsetTop = $field.offset().top - $scrollParent.offset().top + $scrollParent.scrollTop() - 16;
			$scrollParent.animate({ scrollTop: Math.max(0, offsetTop) }, 300);
		} else {
			$('html, body').animate({ scrollTop: $field.offset().top - 100 }, 300);
		}

		var $focusable = $field.find('.wpp-text-input, .wpp-image-upload-btn, .wpp-acceptance-checkbox').first();

		if ($focusable.length) {
			$focusable.trigger('focus');
		}
	}

	function onTextInput() {
		var id = $(this).data('field-id');
		var val = $(this).val();
		var meta = ensureTextFieldState(id);

		meta.value = val;
		var $field = $(this).closest('.wpp-text-field');
		$field.find('.wpp-char-current').text(val.length);

		if (String(val).trim()) {
			clearFieldError($field);
		}

		setActiveTextField(id);
		renderText(id);
		validate();
	}

	function onAcceptanceChange() {
		state.acceptance.checked = $(this).is(':checked');

		if (state.acceptance.checked) {
			clearFieldError($(this).closest('.wpp-acceptance'));
		}

		validate();
	}

	function onImageFieldActivate(e) {
		if ($(e.target).closest('.wpp-image-field__remove').length) {
			return;
		}

		var $field = $(this);
		var slotId = $field.data('slot-id');
		var hasFile = $field.hasClass('has-file');
		var clickedUploadWrap = $(e.target).closest('.wpp-image-upload-wrap').length > 0;
		var clickedUploadControl = $(e.target).closest('.wpp-image-upload-btn, .wpp-image-upload').length > 0;

		setActiveImageField(slotId);

		if (clickedUploadControl) {
			return;
		}

		if (!hasFile || clickedUploadWrap) {
			$field.find('.wpp-image-upload').trigger('click');
		}
	}

	function onImageRemove(e) {
		e.preventDefault();
		e.stopPropagation();

		var slotId = $(this).closest('.wpp-image-field').data('slot-id');
		clearSlotImage(slotId);
		validate();
	}

	function clearSlotImage(slotId) {
		var $field = $('.wpp-image-field[data-slot-id="' + slotId + '"]');
		var node = imageNodes[slotId];

		if (node) {
			node.destroy();
			delete imageNodes[slotId];
		}

		delete state.imageFields[slotId];
		$field.find('.wpp-image-upload').val('');
		updateUploadButton(slotId, '');
		clearFieldError($field);

		if (photoLayer) {
			photoLayer.batchDraw();
		}
		if (borderLayer) {
			borderLayer.batchDraw();
		}
	}

	function onImageUpload() {
		var slotId = $(this).data('slot-id');
		var file = this.files[0];
		if (!file) {
			return;
		}

		if (wppData.allowedMimeTypes.indexOf(file.type) === -1) {
			alert(wppData.i18n.invalidFile);
			return;
		}

		if (file.size > wppData.maxUploadMb * 1024 * 1024) {
			alert(wppData.i18n.invalidFile);
			return;
		}

		wppLog('upload:start', { slotId: slotId, fileName: file.name, fileType: file.type, fileSize: file.size });

		var previousSource = state.imageFields[slotId] && state.imageFields[slotId].source;

		updateUploadButton(slotId, file.name);
		setActiveImageField(slotId);
		setImageUploadLoading(slotId, true);

		var formData = new FormData();
		formData.append('action', 'wpp_upload_temp');
		formData.append('nonce', wppData.uploadNonce);
		formData.append('slot_id', slotId);
		formData.append('product_id', wppData.productId);
		formData.append('file', file);

		$.ajax({
			url: wppData.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		})
			.done(function (res) {
				setImageUploadLoading(slotId, false);
				wppLog('upload:response', { slotId: slotId, success: res.success, data: res.data });

				if (!res.success) {
					wppWarn('upload:rejected', { slotId: slotId, data: res.data });
					updateUploadButton(
						slotId,
						previousSource ? filenameFromPath(previousSource) : ''
					);
					alert(res.data && res.data.message ? res.data.message : wppData.i18n.invalidFile);
					return;
				}
				setSlotImage(slotId, res.data.url);
				validate();
			})
			.fail(function (jqXhr) {
				setImageUploadLoading(slotId, false);
				updateUploadButton(
					slotId,
					previousSource ? filenameFromPath(previousSource) : ''
				);
				wppError('upload:ajax_fail', {
					slotId: slotId,
					status: jqXhr.status,
					response: jqXhr.responseText
				});
			});
	}

	function setSlotImage(slotId, url) {
		var slot = getSlotConfig(slotId);
		if (!slot) {
			wppWarn('setSlotImage:no_slot_config', { slotId: slotId });
			return;
		}

		wppLog('setSlotImage:start', {
			slotId: slotId,
			url: url,
			before: getLayerSnapshot()
		});

		state.imageFields[slotId] = {
			source: url,
			transform: defaultTransform(slot)
		};
		updateUploadButton(slotId, filenameFromPath(url));
		setActiveImageField(slotId);
		clearFieldError($('.wpp-image-field[data-slot-id="' + slotId + '"]'));

		loadImage(
			url,
			function (img) {
				if (imageNodes[slotId]) {
					wppLog('setSlotImage:destroy_previous', {
						slotId: slotId,
						className: imageNodes[slotId].getClassName()
					});
					imageNodes[slotId].destroy();
					delete imageNodes[slotId];
				}

				function buildNode(maskImg) {
					var node = createSlotGroup(slotId, slot, img, maskImg);
					photoLayer.add(node);
					node.moveToTop();
					imageNodes[slotId] = node;
					autofitSlot(slotId);
					redrawAllSlots();

					wppLog('setSlotImage:done', {
						slotId: slotId,
						after: getLayerSnapshot()
					});
				}

				var maskImg = slot.mask ? maskCache[slotId] : null;

				if (slot.mask && !maskImg) {
					loadImage(
						slot.mask,
						function (loadedMask) {
							maskCache[slotId] = loadedMask;
							wppLog('mask:cached', { slotId: slotId, maskUrl: slot.mask });
							buildNode(loadedMask);
						},
						'mask:' + slotId
					);
				} else {
					buildNode(maskImg);
				}
			},
			'photo:' + slotId
		);
	}

	function parseSlotBorder(slot) {
		return window.WppMaskBorder ? window.WppMaskBorder.parse(slot) : { width: 0, color: '#ffffff' };
	}

	function renderSlotBorder(slotId) {
		if (!borderLayer) {
			return;
		}

		var slot = getSlotConfig(slotId);
		var maskImg = maskCache[slotId];
		var existing = slotBorderNodes[slotId];

		if (existing) {
			existing.destroy();
			delete slotBorderNodes[slotId];
		}

		if (!slot || !maskImg) {
			borderLayer.batchDraw();
			return;
		}

		var border = parseSlotBorder(slot);
		if (!border.width) {
			borderLayer.batchDraw();
			return;
		}

		var frame = slot.frame || { x: 0, y: 0, width: 200, height: 200 };
		var borderResult = window.WppMaskBorder
			? window.WppMaskBorder.paint(window.WppMaskBorder.buildState(slot, maskImg, previewScale))
			: null;

		if (!borderResult) {
			borderLayer.batchDraw();
			return;
		}

		var node = new Konva.Image({
			x: frame.x * previewScale - borderResult.pad,
			y: frame.y * previewScale - borderResult.pad,
			width: borderResult.width,
			height: borderResult.height,
			image: borderResult.canvas,
			listening: false,
			name: 'wpp_border_' + slotId
		});

		borderLayer.add(node);
		slotBorderNodes[slotId] = node;
		borderLayer.batchDraw();
	}

	function renderAllSlotBorders() {
		(wppData.layout.image_slots || []).forEach(function (slot) {
			if (slot.mask) {
				renderSlotBorder(slot.id);
			}
		});
	}

	function ensureCompositeCanvas(ps) {
		if (!ps.compositeCanvas) {
			ps.compositeCanvas = document.createElement('canvas');
		}
		return ps.compositeCanvas;
	}

	function paintSlotComposite(ps) {
		if (!ps || !ps.img || !ps.mask) {
			wppWarn('paintSlotComposite:skip', { hasPs: !!ps });
			return null;
		}

		var renderScale = Math.max(1, ps.renderScale || 1);
		var fw = Math.max(1, Math.round(ps.frameW * renderScale));
		var fh = Math.max(1, Math.round(ps.frameH * renderScale));
		var canvas = ensureCompositeCanvas(ps);

		if (canvas.width !== fw || canvas.height !== fh) {
			canvas.width = fw;
			canvas.height = fh;
		}

		var ctx = canvas.getContext('2d');
		ctx.clearRect(0, 0, fw, fh);

		if (ps.whiteBg) {
			ctx.fillStyle = '#ffffff';
			ctx.fillRect(0, 0, fw, fh);
		}

		var w = ps.baseDrawW * renderScale;
		var h = ps.baseDrawH * renderScale;

		ctx.save();
		ctx.translate(fw / 2 + ps.offsetX * renderScale, fh / 2 + ps.offsetY * renderScale);
		ctx.rotate((ps.rotation * Math.PI) / 180);
		ctx.scale(ps.scale * ps.flipX, ps.scale * ps.flipY);
		ctx.drawImage(ps.img, -w / 2, -h / 2, w, h);
		ctx.restore();

		ctx.save();
		ctx.globalCompositeOperation = 'destination-in';
		ctx.drawImage(ps.mask, 0, 0, fw, fh);
		ctx.globalCompositeOperation = 'source-over';
		ctx.restore();

		return canvas;
	}

	function refreshSlotNode(node) {
		if (!node || !node._hasMask || !node._photoState) {
			return;
		}

		var canvas = paintSlotComposite(node._photoState);
		if (canvas && typeof node.image === 'function') {
			node.image(canvas);
		}
	}

	/**
	 * Masked slot: offscreen canvas composite + Konva.Image (isolated per letter).
	 */
	function createSlotGroup(slotId, slot, img, maskImg) {
		var frame = slot.frame || { x: 0, y: 0, width: 200, height: 200 };
		var fw = frame.width * previewScale;
		var fh = frame.height * previewScale;
		var ratio = Math.max(fw / img.width, fh / img.height);
		var drawW = img.width * ratio;
		var drawH = img.height * ratio;

		if (maskImg) {
			var photoState = {
				img: img,
				mask: maskImg,
				whiteBg: !!slot.white_bg,
				frameW: fw,
				frameH: fh,
				offsetX: 0,
				offsetY: 0,
				scale: 1,
				rotation: 0,
				flipX: 1,
				flipY: 1,
				baseDrawW: drawW,
				baseDrawH: drawH,
				compositeCanvas: null
			};

			paintSlotComposite(photoState);
			renderSlotBorder(slotId);

			var node = new Konva.Image({
				x: frame.x * previewScale,
				y: frame.y * previewScale,
				width: fw,
				height: fh,
				image: photoState.compositeCanvas,
				name: 'wpp_slot_' + slotId,
				listening: true
			});

			node._photoState = photoState;
			node._hasMask = true;
			node._frame = frame;
			node._slotId = slotId;

			node.on('mousedown touchstart', function () {
				setActiveImageField(slotId);
			});

			wppLog('createSlotGroup:masked', {
				slotId: slotId,
				frame: frame,
				fw: fw,
				fh: fh,
				img: img.width + 'x' + img.height
			});

			return node;
		}

		var group = new Konva.Group({
			x: frame.x * previewScale,
			y: frame.y * previewScale,
			width: fw,
			height: fh,
			name: 'wpp_slot_' + slotId,
			clip: { x: 0, y: 0, width: fw, height: fh }
		});

		if (slot.white_bg) {
			group.add(
				new Konva.Rect({
					x: 0,
					y: 0,
					width: fw,
					height: fh,
					fill: '#ffffff',
					listening: false
				})
			);
		}

		var konvaImage = new Konva.Image({
			image: img,
			x: fw / 2,
			y: fh / 2,
			width: drawW,
			height: drawH,
			offsetX: drawW / 2,
			offsetY: drawH / 2,
			draggable: !!(slot.controls && slot.controls.move)
		});

		group.add(konvaImage);
		group._konvaImage = konvaImage;
		group._photoState = {
			offsetX: 0,
			offsetY: 0,
			scale: 1,
			rotation: 0,
			flipX: 1,
			flipY: 1,
			baseDrawW: drawW,
			baseDrawH: drawH
		};
		group._hasMask = false;
		group._frame = frame;
		group._slotId = slotId;

		konvaImage.on('dragend', function () {
			readPhotoStateFromImage(group);
			syncTransform(slotId, group);
			validate();
		});

		group.on('mousedown touchstart', function () {
			setActiveImageField(slotId);
		});

		return group;
	}

	function readPhotoStateFromImage(group) {
		var ps = group._photoState;
		var img = group._konvaImage;
		var fw = (group._frame.width || 200) * previewScale;
		var fh = (group._frame.height || 200) * previewScale;

		if (!ps || !img) {
			return;
		}

		ps.offsetX = img.x() - fw / 2;
		ps.offsetY = img.y() - fh / 2;
		ps.rotation = img.rotation();
		ps.scale = Math.abs(img.scaleX()) || 1;
		ps.flipX = img.scaleX() < 0 ? -1 : 1;
		ps.flipY = img.scaleY() < 0 ? -1 : 1;
	}

	function applyPhotoState(node) {
		if (node._hasMask) {
			return;
		}

		var ps = node._photoState;
		var img = node._konvaImage;
		var fw = (node._frame.width || 200) * previewScale;
		var fh = (node._frame.height || 200) * previewScale;

		if (!ps || !img) {
			return;
		}

		var w = ps.baseDrawW * ps.scale;
		var h = ps.baseDrawH * ps.scale;

		img.width(w);
		img.height(h);
		img.offsetX(w / 2);
		img.offsetY(h / 2);
		img.position({ x: fw / 2 + ps.offsetX, y: fh / 2 + ps.offsetY });
		img.rotation(ps.rotation);
		img.scaleX(ps.flipX);
		img.scaleY(ps.flipY);
	}

	function redrawAllSlots() {
		wppLog('redrawAllSlots', getLayerSnapshot());

		Object.keys(imageNodes).forEach(function (id) {
			refreshSlotNode(imageNodes[id]);
		});

		if (photoLayer) {
			photoLayer.batchDraw();
		}
		if (borderLayer) {
			borderLayer.batchDraw();
		}
		if (textLayer) {
			textLayer.batchDraw();
		}
	}

	function redrawSlot() {
		redrawAllSlots();
	}

	function defaultTransform(slot) {
		return {
			x: (slot.frame && slot.frame.x) || 0,
			y: (slot.frame && slot.frame.y) || 0,
			scaleX: 1,
			scaleY: 1,
			rotation: 0,
			flipX: false,
			flipY: false,
			fit_mode: 'cover'
		};
	}

	function syncTransform(slotId, group) {
		if (!state.imageFields[slotId]) {
			return;
		}

		if (group._photoState) {
			var ps = group._photoState;
			var frame = group._frame || { x: 0, y: 0 };
			state.imageFields[slotId].transform = {
				x: frame.x || 0,
				y: frame.y || 0,
				offsetX: ps.offsetX / previewScale,
				offsetY: ps.offsetY / previewScale,
				scale: ps.scale,
				rotation: ps.rotation,
				flipX: ps.flipX < 0,
				flipY: ps.flipY < 0,
				fit_mode: 'cover'
			};
		}
	}

	function renderAllText() {
		(wppData.layout.text_fields || []).forEach(function (field) {
			renderText(field.id);
		});
	}

	function renderText(fieldId) {
		var field = getTextFieldConfig(fieldId);

		if (!field || !textLayer) {
			return;
		}

		var key = 'text_' + fieldId;
		var existing = textNodes[fieldId];

		if (existing) {
			existing.destroy();
			delete textNodes[fieldId];
		}

		var meta = ensureTextFieldState(fieldId);
		syncTextMetaFromField(field);
		var displayText = getFieldDisplayText(field, meta);
		var style = field.style || {};

		if (!String(displayText).trim()) {
			textLayer.batchDraw();
			return;
		}

		var fontSize = Math.max(8, getEffectiveFontSize(field, meta) * previewScale);
		var textNode = new Konva.Text({
			name: key,
			text: displayText,
			x: (style.x || 0) * previewScale + (meta.offsetX || 0) * previewScale,
			y: (style.y || 0) * previewScale + (meta.offsetY || 0) * previewScale,
			width: Math.max(20, (style.width || 400) * previewScale),
			height: Math.max(20, (style.height || 80) * previewScale),
			fontSize: fontSize,
			fontFamily: getEffectiveFontFamily(field, meta),
			fill: style.color || '#ffffff',
			align: style.align || 'center',
			verticalAlign: 'middle',
			listening: true,
			shadowColor: 'rgba(0,0,0,0.35)',
			shadowBlur: 2,
			shadowOffset: { x: 1, y: 1 },
			shadowOpacity: 0.8
		});

		textNode._fieldId = fieldId;
		textNode.on('mousedown touchstart', function () {
			setActiveTextField(fieldId);
		});

		textLayer.add(textNode);
		textNodes[fieldId] = textNode;
		textLayer.batchDraw();
	}

	function onTextTransformAction(action) {
		var fieldId = state.activeTextField;
		var field = getTextFieldConfig(fieldId);

		if (!fieldId || !field) {
			return;
		}

		var meta = ensureTextFieldState(fieldId);
		var controls = field.controls || {};
		var step = 2;

		switch (action) {
			case 'left':
				if (!textControlEnabled(controls, 'move')) {
					return;
				}
				meta.offsetX -= step;
				break;
			case 'right':
				if (!textControlEnabled(controls, 'move')) {
					return;
				}
				meta.offsetX += step;
				break;
			case 'up':
				if (!textControlEnabled(controls, 'move')) {
					return;
				}
				meta.offsetY -= step;
				break;
			case 'down':
				if (!textControlEnabled(controls, 'move')) {
					return;
				}
				meta.offsetY += step;
				break;
			case 'font-size-in':
				if (!textControlEnabled(controls, 'fontSize')) {
					return;
				}
				if (meta.fontSize === null || meta.fontSize === undefined) {
					meta.fontSize = getBaseFontSize(field);
				}
				meta.fontSize = Math.min(200, meta.fontSize + 2);
				break;
			case 'font-size-out':
				if (!textControlEnabled(controls, 'fontSize')) {
					return;
				}
				if (meta.fontSize === null || meta.fontSize === undefined) {
					meta.fontSize = getBaseFontSize(field);
				}
				meta.fontSize = Math.max(8, meta.fontSize - 2);
				break;
			default:
				return;
		}

		renderText(fieldId);
		validate();
	}

	function onTransformAction() {
		var action = $(this).data('action');

		if (state.activeTextField) {
			onTextTransformAction(action);
			return;
		}

		if (!state.activeSlot || !imageNodes[state.activeSlot]) {
			return;
		}
		var group = imageNodes[state.activeSlot];
		var slotId = state.activeSlot;
		var step = 10;
		var ps = group._photoState;

		if (!ps) {
			return;
		}

		switch (action) {
			case 'left':
				ps.offsetX -= step;
				break;
			case 'right':
				ps.offsetX += step;
				break;
			case 'up':
				ps.offsetY -= step;
				break;
			case 'down':
				ps.offsetY += step;
				break;
			case 'zoom-in':
				ps.scale *= 1.1;
				break;
			case 'zoom-out':
				ps.scale *= 0.9;
				break;
			case 'rotate':
				ps.rotation += 15;
				break;
			case 'flip-h':
				ps.flipX *= -1;
				break;
			case 'flip-v':
				ps.flipY *= -1;
				break;
			case 'autofit':
				autofitSlot(slotId);
				return;
			case 'reset':
				resetSlot(slotId);
				return;
		}

		if (group._hasMask) {
			refreshSlotNode(group);
		} else {
			applyPhotoState(group);
		}

		redrawSlot();
		syncTransform(slotId, group);
		validate();
	}

	function autofitSlot(slotId) {
		var node = imageNodes[slotId];
		var slot = getSlotConfig(slotId);
		if (!node || !slot || !node._photoState) {
			return;
		}

		var frame = slot.frame || {};
		var fw = frame.width * previewScale;
		var fh = frame.height * previewScale;
		var ps = node._photoState;
		var sourceImg = ps.img || (node._konvaImage && node._konvaImage.image());

		if (!sourceImg) {
			return;
		}

		var ratio = Math.max(fw / sourceImg.width, fh / sourceImg.height);

		ps.offsetX = 0;
		ps.offsetY = 0;
		ps.scale = 1;
		ps.rotation = 0;
		ps.flipX = 1;
		ps.flipY = 1;
		ps.baseDrawW = sourceImg.width * ratio;
		ps.baseDrawH = sourceImg.height * ratio;

		if (node._hasMask) {
			ps.frameW = fw;
			ps.frameH = fh;
			node.position({ x: frame.x * previewScale, y: frame.y * previewScale });
			node.width(fw);
			node.height(fh);
			refreshSlotNode(node);
		} else if (node._konvaImage) {
			node.position({ x: frame.x * previewScale, y: frame.y * previewScale });
			applyPhotoState(node);
		}

		redrawSlot();
		syncTransform(slotId, node);
	}

	function resetSlot(slotId) {
		var data = state.imageFields[slotId];
		if (!data || !data.source) {
			return;
		}
		setSlotImage(slotId, data.source);
	}

	function getSlotConfig(slotId) {
		return (wppData.layout.image_slots || []).find(function (s) {
			return s.id === slotId;
		});
	}

	function validateWithUI(showErrors) {
		if (showErrors) {
			clearAllFieldErrors();
		}

		var ok = true;
		var $firstInvalid = null;
		var requiredMessage = getRequiredFieldMessage();

		(wppData.layout.text_fields || []).forEach(function (field) {
			var val = getTextFieldValue(field.id).trim();
			var $field = $('.wpp-text-field[data-field-id="' + field.id + '"]');

			if (field.required && !val) {
				ok = false;

				if (showErrors) {
					setFieldError($field, requiredMessage);

					if (!$firstInvalid) {
						$firstInvalid = $field;
					}
				}
			}

			if (field.max_length && val.length > field.max_length) {
				ok = false;
			}
		});

		(wppData.layout.image_slots || []).forEach(function (slot) {
			var $field = $('.wpp-image-field[data-slot-id="' + slot.id + '"]');

			if (slot.required && !(state.imageFields[slot.id] && state.imageFields[slot.id].source)) {
				ok = false;

				if (showErrors) {
					setFieldError($field, requiredMessage);

					if (!$firstInvalid) {
						$firstInvalid = $field;
					}
				}
			}
		});

		var $acceptance = $('.wpp-acceptance');

		if (wppData.acceptanceRequired && !state.acceptance.checked) {
			ok = false;

			if (showErrors && $acceptance.length) {
				setFieldError(
					$acceptance,
					wppData.i18n.acceptRequired || requiredMessage
				);

				if (!$firstInvalid) {
					$firstInvalid = $acceptance;
				}
			}
		}

		state.valid = ok;
		updateAddToCartState();

		return {
			valid: ok,
			$firstInvalid: $firstInvalid
		};
	}

	function validate() {
		return validateWithUI(false).valid;
	}

	function handleValidationFailure() {
		var result = validateWithUI(true);

		if (result.$firstInvalid && result.$firstInvalid.length) {
			scrollToField(result.$firstInvalid);
		}

		return result.valid;
	}

	function updateAddToCartState() {
		updatePersonalizeButtons();

		if (!wppData.validationEnabled) {
			$('body').removeClass('wpp-block-atc');
			return;
		}
		if (state.valid) {
			$('body').removeClass('wpp-block-atc');
		} else {
			$('body').addClass('wpp-block-atc');
		}
	}

	function exportTextFieldsForServer() {
		var out = {};

		(wppData.layout.text_fields || []).forEach(function (field) {
			var meta = ensureTextFieldState(field.id);

			syncTextMetaFromField(field);

			out[field.id] = {
				value: getTextFieldValue(field.id),
				offsetX: meta.offsetX || 0,
				offsetY: meta.offsetY || 0,
				fontSize: getEffectiveFontSize(field, meta),
				fontFamily: getEffectiveFontFamily(field, meta)
			};
		});

		return out;
	}

	function exportImageFieldsForServer() {
		var out = {};
		Object.keys(state.imageFields || {}).forEach(function (slotId) {
			var field = state.imageFields[slotId] || {};
			out[slotId] = {
				source: field.source || '',
				transform: field.transform || {}
			};
		});
		return out;
	}

	function exportState() {
		return {
			layout_id: wppData.layoutId,
			product_id: wppData.productId,
			text_fields: exportTextFieldsForServer(),
			image_fields: exportImageFieldsForServer(),
			acceptance: {
				required: wppData.acceptanceRequired,
				checked: state.acceptance.checked
			}
		};
	}

	function exportPreview() {
		if (!stage) {
			return '';
		}
		var pixelRatio = getEffectiveExportPixelRatio();
		setMaskedRenderScale(pixelRatio);
		stage.draw();
		var dataUrl = stage.toDataURL({ pixelRatio: pixelRatio });
		setMaskedRenderScale(1);
		stage.draw();
		return dataUrl;
	}

	function setMaskedRenderScale(scale) {
		Object.keys(imageNodes).forEach(function (slotId) {
			var node = imageNodes[slotId];
			if (!node || !node._hasMask || !node._photoState) {
				return;
			}
			node._photoState.renderScale = Math.max(1, scale || 1);
			refreshSlotNode(node);
		});

		if (photoLayer) {
			photoLayer.batchDraw();
		}
	}

	function getEffectiveExportPixelRatio() {
		var exportScale = Math.max(1, Math.min(6, parseInt(wppData.previewExportScale, 10) || 2));
		var normalized = exportScale / Math.max(0.01, previewScale || 1);
		return Math.max(1, Math.min(24, normalized));
	}

	function exportLayersPreview() {
		if (!stage) {
			return '';
		}

		var hiddenLayers = [];

		[bgLayer, overlayLayer, borderLayer].forEach(function (layer) {
			if (layer && layer.visible()) {
				hiddenLayers.push(layer);
				layer.visible(false);
			}
		});

		var pixelRatio = getEffectiveExportPixelRatio();
		setMaskedRenderScale(pixelRatio);
		stage.draw();
		var dataUrl = stage.toDataURL({ pixelRatio: pixelRatio, mimeType: 'image/png' });

		hiddenLayers.forEach(function (layer) {
			layer.visible(true);
		});
		setMaskedRenderScale(1);
		stage.draw();

		return dataUrl;
	}

	function persistHiddenFields() {
		var json = JSON.stringify(exportState());
		var preview = exportPreview();
		var layersPreview = exportLayersPreview();
		var nonce = $('.wpp-personalizer input[name="wpp_personalizer_nonce"]').val() || wppData.nonce;
		$('form.cart .wpp-project-state, .wpp-personalizer .wpp-project-state').val(json);
		$('form.cart .wpp-preview-data, .wpp-personalizer .wpp-preview-data').val(preview);
		$('form.cart .wpp-preview-layers-data, .wpp-personalizer .wpp-preview-layers-data').val(layersPreview);
		$('form.cart .wpp-personalizer-nonce, .wpp-personalizer input[name="wpp_personalizer_nonce"]').val(nonce);
	}

	function saveAndClose() {
		if (wppData.validationEnabled && !handleValidationFailure()) {
			return;
		}
		persistHiddenFields();
		closeModal();
	}

	function onCartSubmit(e) {
		if (!validate()) {
			if (wppData.validationEnabled) {
				e.preventDefault();
				handleValidationFailure();
			}
			return;
		}
		persistHiddenFields();
	}

	function esc(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	$(init);
})(jQuery);
