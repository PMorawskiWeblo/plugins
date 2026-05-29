(function ($, window) {
	'use strict';

	var cropper = null;
	var pending = null;
	var callbacks = { onSelect: null, onCancel: null };
	var $modal;

	function getCropperConstructor() {
		if (window.WppCropperLib && typeof window.WppCropperLib === 'function') {
			return window.WppCropperLib;
		}

		if (
			typeof window.Cropper === 'function' &&
			window.Cropper.prototype &&
			typeof window.Cropper.prototype.getCroppedCanvas === 'function'
		) {
			return window.Cropper;
		}

		return null;
	}

	function init(options) {
		callbacks.onSelect = options && options.onSelect ? options.onSelect : null;
		callbacks.onCancel = options && options.onCancel ? options.onCancel : null;
		ensureModal();
		bindModalEvents();
	}

	function ensureModal() {
		$modal = $('#wpp-crop-modal');
		if ($modal.length) {
			return;
		}

		$('body').append(
			'<div id="wpp-crop-modal" class="wpp-crop-modal" aria-hidden="true" role="dialog" aria-modal="true">' +
				'<div class="wpp-crop-modal__backdrop" data-wpp-crop-close></div>' +
				'<div class="wpp-crop-modal__dialog">' +
				'<button type="button" class="wpp-crop-modal__close" data-wpp-crop-close aria-label="Close">&times;</button>' +
				'<div class="wpp-crop-modal__stage">' +
				'<div class="wpp-crop-modal__crop-area"><img id="wpp-crop-image" class="wpp-crop-modal__image" src="" alt="" /></div>' +
				'</div>' +
				'<div class="wpp-crop-modal__footer">' +
				'<div class="wpp-crop-modal__zoom">' +
				'<button type="button" class="wpp-crop-modal__zoom-btn" data-wpp-crop-zoom="out" aria-label="Zoom out"><span aria-hidden="true">−</span></button>' +
				'<button type="button" class="wpp-crop-modal__zoom-btn" data-wpp-crop-zoom="in" aria-label="Zoom in"><span aria-hidden="true">+</span></button>' +
				'</div>' +
				'<div class="wpp-crop-modal__actions">' +
				'<button type="button" class="btn wpp-crop-modal__cancel">Cancel</button>' +
				'<button type="button" class="btn wpp-crop-modal__select">Select</button>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>'
		);
		$modal = $('#wpp-crop-modal');
	}

	function bindModalEvents() {
		$modal.on('click', '[data-wpp-crop-close], .wpp-crop-modal__cancel', function (e) {
			e.preventDefault();
			close(true);
		});

		$modal.on('click', '.wpp-crop-modal__select', function (e) {
			e.preventDefault();
			confirmCrop();
		});

		$modal.on('click', '[data-wpp-crop-zoom]', function (e) {
			e.preventDefault();
			if (!cropper) {
				return;
			}
			var dir = $(this).data('wpp-crop-zoom');
			cropper.zoom(dir === 'in' ? 0.1 : -0.1);
		});
	}

	function destroyCropper() {
		if (cropper) {
			cropper.destroy();
			cropper = null;
		}
	}

	function applyMaskShapeUi(active, maskUrl) {
		var $cropArea = $modal.find('.wpp-crop-modal__crop-area');

		if (active && maskUrl) {
			$cropArea
				.addClass('wpp-crop--mask-shape')
				.css('--wpp-crop-mask-url', 'url("' + String(maskUrl).replace(/"/g, '\\"') + '")');
			return;
		}

		$cropArea.removeClass('wpp-crop--mask-shape').css('--wpp-crop-mask-url', '');
	}

	function applyMaskToCanvas(sourceCanvas, maskUrl) {
		return new Promise(function (resolve, reject) {
			var maskImg = new Image();
			maskImg.crossOrigin = 'anonymous';
			maskImg.onload = function () {
				var out = document.createElement('canvas');
				out.width = sourceCanvas.width;
				out.height = sourceCanvas.height;
				var ctx = out.getContext('2d');

				ctx.drawImage(sourceCanvas, 0, 0);
				ctx.globalCompositeOperation = 'destination-in';
				ctx.drawImage(maskImg, 0, 0, out.width, out.height);
				resolve(out);
			};
			maskImg.onerror = reject;
			maskImg.src = maskUrl;
		});
	}

	function exportCanvas(canvas) {
		var mime = pending.outputMime || 'image/jpeg';
		var quality = mime === 'image/png' ? undefined : 1;
		var fileName =
			pending.fileName ||
			(mime === 'image/png' ? 'cropped.png' : 'cropped.jpg');

		canvas.toBlob(
			function (blob) {
				if (!blob || !pending) {
					return;
				}
				if (callbacks.onSelect) {
					callbacks.onSelect({
						slotId: pending.slotId,
						blob: blob,
						fileName: fileName
					});
				}
				close(false);
			},
			mime,
			quality
		);
	}

	function close(isCancel) {
		destroyCropper();
		applyMaskShapeUi(false);
		$modal.removeClass('is-open').attr('aria-hidden', 'true');
		$('body').removeClass('wpp-crop-modal-open');
		$('#wpp-crop-image').attr('src', '');

		if (isCancel && pending && callbacks.onCancel) {
			callbacks.onCancel(pending);
		}

		pending = null;
	}

	function confirmCrop() {
		if (!cropper || !pending) {
			return;
		}

		var cropData = cropper.getData(true) || {};
		var outW = Math.max(1, Math.round(cropData.width || 0));
		var outH = Math.max(1, Math.round(cropData.height || 0));

		if (!outW || !outH) {
			var frame = pending.frame || { width: 1, height: 1 };
			var maxEdge = Math.max(frame.width || 1, frame.height || 1, 1);
			var exportScale = Math.min(4, Math.max(1, 2048 / maxEdge));
			outW = Math.max(1, Math.round((frame.width || 1) * exportScale));
			outH = Math.max(1, Math.round((frame.height || 1) * exportScale));
		}

		var canvas = cropper.getCroppedCanvas({
			width: outW,
			height: outH,
			imageSmoothingEnabled: true,
			imageSmoothingQuality: 'high'
		});

		if (!canvas) {
			return;
		}

		if (pending.useMaskShape && pending.maskUrl) {
			applyMaskToCanvas(canvas, pending.maskUrl)
				.then(exportCanvas)
				.catch(function () {
					exportCanvas(canvas);
				});
			return;
		}

		exportCanvas(canvas);
	}

	function fitImageToModal(instance, aspectRatio) {
		if (!instance) {
			return;
		}

		aspectRatio = aspectRatio > 0 ? aspectRatio : 1;

		var containerData = instance.getContainerData();
		var imageData = instance.getImageData();

		if (!containerData.width || !containerData.height || !imageData.naturalWidth || !imageData.naturalHeight) {
			return;
		}

		var scale = Math.min(
			(containerData.width * 0.96) / imageData.naturalWidth,
			(containerData.height * 0.96) / imageData.naturalHeight
		);

		if (!isFinite(scale) || scale <= 0) {
			return;
		}

		instance.zoomTo(scale);

		var maxCropW = containerData.width * 0.88;
		var maxCropH = containerData.height * 0.88;
		var cropWidth = maxCropW;
		var cropHeight = cropWidth / aspectRatio;

		if (cropHeight > maxCropH) {
			cropHeight = maxCropH;
			cropWidth = cropHeight * aspectRatio;
		}

		instance.setCropBoxData({
			width: cropWidth,
			height: cropHeight,
			left: (containerData.width - cropWidth) / 2,
			top: (containerData.height - cropHeight) / 2
		});
	}

	function initCropperInstance(CropperCtor, imgEl, aspectRatio) {
		cropper = new CropperCtor(imgEl, {
			aspectRatio: aspectRatio,
			viewMode: 2,
			dragMode: 'move',
			autoCropArea: 0.9,
			responsive: true,
			background: false,
			guides: true,
			center: true,
			highlight: true,
			cropBoxMovable: true,
			cropBoxResizable: true,
			toggleDragModeOnDblclick: false,
			checkOrientation: true,
			ready: function () {
				if (!cropper) {
					return;
				}

				cropper.crop();
				fitImageToModal(cropper, aspectRatio);
				if (pending && pending.useMaskShape && pending.maskUrl) {
					$(cropper.cropper).addClass('wpp-crop--mask-shape');
				}
				window.requestAnimationFrame(function () {
					if (!cropper) {
						return;
					}
					cropper.resize();
					fitImageToModal(cropper, aspectRatio);
				});
			}
		});
	}

	function applyLabels(i18n) {
		if (!i18n) {
			return;
		}
		$modal.find('.wpp-crop-modal__cancel').text(i18n.cancel || 'Cancel');
		$modal.find('.wpp-crop-modal__select').text(i18n.select || 'Select');
		$modal.find('[data-wpp-crop-zoom="in"]').attr('aria-label', i18n.zoomIn || 'Zoom in');
		$modal.find('[data-wpp-crop-zoom="out"]').attr('aria-label', i18n.zoomOut || 'Zoom out');
	}

	function open(options) {
		options = options || {};

		var CropperCtor = getCropperConstructor();

		if (!CropperCtor) {
			if (options.onFallback) {
				options.onFallback(options);
			}
			return;
		}

		ensureModal();
		applyLabels(options.i18n);

		pending = options;
		var aspectRatio = options.aspectRatio > 0 ? options.aspectRatio : 1;
		var $img = $('#wpp-crop-image');

		destroyCropper();
		applyMaskShapeUi(options.useMaskShape && options.maskUrl, options.maskUrl);
		$modal.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('wpp-crop-modal-open');

		$img.off('load.wppCrop').on('load.wppCrop', function () {
			destroyCropper();
			window.requestAnimationFrame(function () {
				initCropperInstance(CropperCtor, $img[0], aspectRatio);
			});
		});

		$img.attr('src', options.url || '');
	}

	window.WppImageCrop = {
		init: init,
		open: open,
		close: function () {
			close(true);
		}
	};
})(jQuery, window);
