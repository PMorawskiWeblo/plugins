(function ($, Konva) {
	'use strict';

	var api = {
		$builder: null,
		getConfig: null,
		onSlotUpdate: null,
		onTextUpdate: null,
		stage: null,
		previewScale: 1,
		layers: {},
		selected: null,
		resizeObserver: null,
		transformer: null,
		previewNodes: { slots: [], texts: [] },
		maskCache: {},
		zoomMode: false
	};

	function esc(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function getContainerWidth(container, canvasWidth) {
		var width = container.clientWidth;
		return width > 0 ? width : canvasWidth || 600;
	}

	function computePreviewScale(canvasWidth, canvasHeight, container) {
		var cw = getContainerWidth(container, canvasWidth);

		if (!api.zoomMode) {
			return cw / canvasWidth;
		}

		var maxH = Math.min(Math.max(320, window.innerHeight * 0.85), 1400);
		var scaleW = cw / canvasWidth;
		var scaleH = maxH / canvasHeight;

		return Math.min(scaleW, scaleH);
	}

	function syncContainerSize(container, canvasWidth, canvasHeight, scale) {
		if (!container) {
			return;
		}

		if (api.zoomMode) {
			container.style.height = Math.round(canvasHeight * scale) + 'px';
		} else {
			container.style.height = '';
		}
	}

	function loadImage(url, cb) {
		if (!url) {
			cb(null);
			return;
		}

		var img = new window.Image();
		img.crossOrigin = 'anonymous';
		img.onload = function () {
			cb(img);
		};
		img.onerror = function () {
			cb(null);
		};
		img.src = url;
	}

	function clearLayer(layer) {
		if (!layer) {
			return;
		}

		layer.destroyChildren();
	}

	function getTransformer() {
		if (!api.transformer) {
			api.transformer = new Konva.Transformer({
				rotateEnabled: false,
				keepRatio: false,
				enabledAnchors: [
					'top-left',
					'top-center',
					'top-right',
					'middle-left',
					'middle-right',
					'bottom-left',
					'bottom-center',
					'bottom-right'
				],
				boundBoxFunc: function (oldBox, newBox) {
					if (newBox.width < 16 || newBox.height < 16) {
						return oldBox;
					}
					return newBox;
				}
			});
			api.layers.ui.add(api.transformer);
		}

		return api.transformer;
	}

	function detachTransformer() {
		if (api.transformer) {
			api.transformer.nodes([]);
		}
	}

	function getSelectedNode() {
		if (!api.selected) {
			return null;
		}

		if (api.selected.type === 'slot') {
			return api.previewNodes.slots[api.selected.index] || null;
		}

		return api.previewNodes.texts[api.selected.index] || null;
	}

	function attachTransformer(node) {
		if (!node) {
			detachTransformer();
			api.layers.ui.batchDraw();
			return;
		}

		getTransformer().nodes([node]);
		api.layers.ui.moveToTop();
		api.layers.ui.batchDraw();
	}

	function normalizeGroupBox(group) {
		var scaleX = group.scaleX();
		var scaleY = group.scaleY();
		var box = group.findOne('.wpp-box');

		if (!box) {
			return null;
		}

		var w = Math.max(16, box.width() * scaleX);
		var h = Math.max(16, box.height() * scaleY);

		group.scaleX(1);
		group.scaleY(1);
		box.width(w);
		box.height(h);

		var label = group.findOne('.wpp-slot-label');
		if (label) {
			label.width(w);
		}

		var text = group.findOne('.wpp-text-content');
		if (text) {
			text.width(w);
			text.height(h);
		}

		return {
			x: Math.round(group.x() / api.previewScale),
			y: Math.round(group.y() / api.previewScale),
			width: Math.max(1, Math.round(w / api.previewScale)),
			height: Math.max(1, Math.round(h / api.previewScale))
		};
	}

	function emitSlotUpdate(index, group) {
		var data = normalizeGroupBox(group);

		if (!data || typeof api.onSlotUpdate !== 'function') {
			return;
		}

		api.onSlotUpdate(index, data.x, data.y, data.width, data.height);
	}

	function emitTextUpdate(index, group) {
		var data = normalizeGroupBox(group);

		if (!data || typeof api.onTextUpdate !== 'function') {
			return;
		}

		api.onTextUpdate(index, data.x, data.y, data.width, data.height);
	}

	function selectPreviewItem(type, index) {
		api.selected = { type: type, index: index };

		api.$builder.find('.wpp-slot-card, .wpp-text-card').removeClass('is-preview-active');

		if (type === 'slot') {
			api.$builder.find('.wpp-slot-card[data-index="' + index + '"]').addClass('is-preview-active');
		} else if (type === 'text') {
			api.$builder.find('.wpp-text-card[data-index="' + index + '"]').addClass('is-preview-active');
		}

		attachTransformer(getSelectedNode());
	}

	function bindCardClicks() {
		api.$builder.on('click.wppPreview', '.wpp-slot-card', function (e) {
			if ($(e.target).closest('.wpp-builder-card__header').length) {
				return;
			}

			selectPreviewItem('slot', $(this).data('index'));
		});

		api.$builder.on('click.wppPreview', '.wpp-text-card', function (e) {
			if ($(e.target).closest('.wpp-builder-card__header').length) {
				return;
			}

			selectPreviewItem('text', $(this).data('index'));
		});
	}

	function bindStageDeselect() {
		api.stage.on('click.wppPreview tap.wppPreview', function (e) {
			if (e.target === api.stage) {
				api.selected = null;
				detachTransformer();
				api.$builder.find('.wpp-slot-card, .wpp-text-card').removeClass('is-preview-active');
			}
		});
	}

	function ensureStage() {
		var container = document.getElementById('wpp-admin-canvas-container');

		if (!container || typeof Konva === 'undefined') {
			return false;
		}

		if (api.stage) {
			return true;
		}

		api.stage = new Konva.Stage({
			container: 'wpp-admin-canvas-container',
			width: 400,
			height: 300
		});

		api.layers.bg = new Konva.Layer();
		api.layers.slots = new Konva.Layer();
		api.layers.text = new Konva.Layer();
		api.layers.overlay = new Konva.Layer();
		api.layers.ui = new Konva.Layer();

		api.stage.add(api.layers.bg);
		api.stage.add(api.layers.slots);
		api.stage.add(api.layers.overlay);
		api.stage.add(api.layers.text);
		api.stage.add(api.layers.ui);
		api.layers.text.zIndex(3);
		api.layers.ui.zIndex(4);

		bindStageDeselect();

		if (!api.resizeObserver && typeof ResizeObserver !== 'undefined') {
			api.resizeObserver = new ResizeObserver(function () {
				api.refresh();
			});
			api.resizeObserver.observe(container);
		}

		return true;
	}

	function loadSlotMask(url, cb) {
		if (!url) {
			cb(null);
			return;
		}

		if (api.maskCache[url]) {
			cb(api.maskCache[url]);
			return;
		}

		loadImage(url, function (img) {
			if (img) {
				api.maskCache[url] = img;
			}
			cb(img);
		});
	}

	function addSlotMaskBorder(group, slot) {
		if (!window.WppMaskBorder || !slot.mask) {
			return;
		}

		var border = window.WppMaskBorder.parse(slot);
		if (!border.width) {
			return;
		}

		loadSlotMask(slot.mask, function (maskImg) {
			if (!maskImg || !group.getParent()) {
				return;
			}

			var existing = group.find('.wpp-slot-mask-border');
			if (existing && existing.length) {
				existing[0].destroy();
			}

			var result = window.WppMaskBorder.paint(
				window.WppMaskBorder.buildState(slot, maskImg, api.previewScale)
			);

			if (!result) {
				return;
			}

			group.add(
				new Konva.Image({
					name: 'wpp-slot-mask-border',
					x: -result.pad,
					y: -result.pad,
					width: result.width,
					height: result.height,
					image: result.canvas,
					listening: false
				})
			);

			api.layers.slots.batchDraw();
		});
	}

	function createSlotGroup(slot, index) {
		var frame = slot.frame || { x: 0, y: 0, width: 100, height: 100 };
		var w = (frame.width || 100) * api.previewScale;
		var h = (frame.height || 100) * api.previewScale;

		var group = new Konva.Group({
			x: (frame.x || 0) * api.previewScale,
			y: (frame.y || 0) * api.previewScale,
			draggable: true,
			name: 'slot_' + index
		});

		group.add(
			new Konva.Rect({
				name: 'wpp-box',
				width: w,
				height: h,
				fill: 'rgba(127, 84, 179, 0.25)',
				stroke: '#7f54b3',
				strokeWidth: 1
			})
		);

		group.add(
			new Konva.Text({
				name: 'wpp-slot-label',
				text: slot.label || slot.id || 'Slot',
				fontSize: Math.max(10, 12 * api.previewScale),
				fill: '#1e1e1e',
				padding: 4,
				width: w,
				ellipsis: true
			})
		);

		addSlotMaskBorder(group, slot);

		group.on('mousedown touchstart', function (e) {
			e.cancelBubble = true;
			selectPreviewItem('slot', index);
		});

		group.on('dragend', function () {
			emitSlotUpdate(index, group);
			selectPreviewItem('slot', index);
		});

		group.on('transformend', function () {
			emitSlotUpdate(index, group);
			selectPreviewItem('slot', index);
		});

		return group;
	}

	function createTextGroup(field, index) {
		var style = field.style || {};
		var textValue = String(field.default_value || field.label || field.id || 'Text').trim();

		if (!textValue) {
			return null;
		}

		var w = Math.max(20, (style.width || 400) * api.previewScale);
		var h = Math.max(20, (style.height || 80) * api.previewScale);
		var fontSize = Math.max(8, (style.fontSize || 48) * api.previewScale);
		var fontFamily =
			window.WppGoogleFonts && typeof window.WppGoogleFonts.getDefaultFontFamily === 'function'
				? window.WppGoogleFonts.getDefaultFontFamily(field)
				: style.fontFamily || 'Arial';

		var group = new Konva.Group({
			x: (style.x || 0) * api.previewScale,
			y: (style.y || 0) * api.previewScale,
			draggable: true,
			name: 'text_' + index
		});

		group.add(
			new Konva.Rect({
				name: 'wpp-box',
				width: w,
				height: h,
				fill: 'rgba(255, 255, 255, 0.06)',
				stroke: 'rgba(255, 255, 255, 0.45)',
				strokeWidth: 1,
				dash: [4, 4]
			})
		);

		var textProps = {
			name: 'wpp-text-content',
			width: w,
			height: h,
			text: textValue,
			fontSize: fontSize,
			fontFamily: fontFamily,
			fill: style.color || '#ffffff',
			align: style.align || 'center',
			verticalAlign: 'middle'
		};

		if (style.textShadow) {
			textProps.shadowColor = 'rgba(0,0,0,0.35)';
			textProps.shadowBlur = 2;
			textProps.shadowOffset = { x: 1, y: 1 };
			textProps.shadowOpacity = 0.8;
		}

		group.add(new Konva.Text(textProps));

		group.on('mousedown touchstart', function (e) {
			e.cancelBubble = true;
			selectPreviewItem('text', index);
		});

		group.on('dragend', function () {
			emitTextUpdate(index, group);
			selectPreviewItem('text', index);
		});

		group.on('transformend', function () {
			emitTextUpdate(index, group);
			selectPreviewItem('text', index);
		});

		return group;
	}

	function loadPreviewGoogleFonts(cfg, done) {
		if (!window.WppGoogleFonts || typeof window.WppGoogleFonts.loadUrls !== 'function') {
			done();
			return;
		}

		window.WppGoogleFonts.loadUrls(window.WppGoogleFonts.collectLayoutUrls(cfg), done);
	}

	function refresh() {
		if (!api.getConfig || !ensureStage()) {
			return;
		}

		var cfg = api.getConfig();
		var canvas = cfg.canvas || {};
		var width = parseInt(canvas.width, 10) || 800;
		var height = parseInt(canvas.height, 10) || 600;
		var container = document.getElementById('wpp-admin-canvas-container');
		var selectedSnapshot = api.selected ? { type: api.selected.type, index: api.selected.index } : null;

		api.previewScale = computePreviewScale(width, height, container);
		syncContainerSize(container, width, height, api.previewScale);
		api.stage.width(width * api.previewScale);
		api.stage.height(height * api.previewScale);

		detachTransformer();
		clearLayer(api.layers.bg);
		clearLayer(api.layers.slots);
		clearLayer(api.layers.text);
		clearLayer(api.layers.overlay);

		api.previewNodes = { slots: [], texts: [] };

		var stageW = api.stage.width();
		var stageH = api.stage.height();

		loadImage(canvas.background, function (bgImg) {
			if (bgImg) {
				api.layers.bg.add(
					new Konva.Image({
						image: bgImg,
						width: stageW,
						height: stageH,
						listening: false
					})
				);
			} else {
				api.layers.bg.add(
					new Konva.Rect({
						width: stageW,
						height: stageH,
						fill: '#f0f0f1',
						listening: false
					})
				);
			}
			api.layers.bg.batchDraw();
		});

		loadPreviewGoogleFonts(cfg, function () {
			(cfg.image_slots || []).forEach(function (slot, index) {
				var group = createSlotGroup(slot, index);
				api.previewNodes.slots[index] = group;
				api.layers.slots.add(group);
			});

			(cfg.text_fields || []).forEach(function (field, index) {
				var group = createTextGroup(field, index);
				if (group) {
					api.previewNodes.texts[index] = group;
					api.layers.text.add(group);
				}
			});

			api.layers.slots.batchDraw();
			api.layers.text.batchDraw();

			if (selectedSnapshot) {
				selectPreviewItem(selectedSnapshot.type, selectedSnapshot.index);
			}
		});

		loadImage(canvas.overlay, function (overlayImg) {
			if (overlayImg) {
				api.layers.overlay.add(
					new Konva.Image({
						image: overlayImg,
						width: stageW,
						height: stageH,
						listening: false
					})
				);
				api.layers.overlay.batchDraw();
			}

			api.layers.text.moveToTop();
			api.layers.ui.moveToTop();
			api.layers.text.batchDraw();
			api.layers.ui.batchDraw();
		});

		api.layers.slots.batchDraw();
		api.layers.text.batchDraw();

		if (selectedSnapshot) {
			selectPreviewItem(selectedSnapshot.type, selectedSnapshot.index);
		}
	}

	api.init = function (options) {
		api.$builder = options.$builder;
		api.getConfig = options.getConfig;
		api.onSlotUpdate = options.onSlotUpdate;
		api.onTextUpdate = options.onTextUpdate;

		if (!api.$builder || !api.$builder.length) {
			return;
		}

		bindCardClicks();
		refresh();
	};

	api.refresh = refresh;
	api.setZoomMode = function (on) {
		api.zoomMode = !!on;
		refresh();
	};
	api.selectSlot = function (index) {
		selectPreviewItem('slot', index);
	};
	api.selectText = function (index) {
		selectPreviewItem('text', index);
	};

	window.WppLayoutPreview = api;
})(jQuery, typeof Konva !== 'undefined' ? Konva : null);
