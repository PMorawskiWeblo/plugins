(function ($, window) {
	'use strict';

	if (typeof wppData === 'undefined' || !wppData.lazyLoad) {
		return;
	}

	var loadPromise = null;
	var loaded = false;

	function i18n(key, fallback) {
		return (wppData.i18n && wppData.i18n[key]) || fallback;
	}

	function showLoader() {
		var $loader = $('#wpp-lazy-loader');
		if ($loader.length) {
			$loader.addClass('is-active').attr('aria-hidden', 'false');
		}
		$('body').addClass('wpp-personalizer-loading');
	}

	function hideLoader() {
		var $loader = $('#wpp-lazy-loader');
		if ($loader.length) {
			$loader.removeClass('is-active').attr('aria-hidden', 'true');
		}
		$('body').removeClass('wpp-personalizer-loading');
	}

	function loadStyle(entry) {
		return new Promise(function (resolve, reject) {
			if (!entry || !entry.src) {
				resolve();
				return;
			}

			if (document.querySelector('link[data-wpp-lazy-style="' + entry.handle + '"]')) {
				resolve();
				return;
			}

			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = entry.src;
			link.setAttribute('data-wpp-lazy-style', entry.handle);
			link.onload = function () {
				resolve();
			};
			link.onerror = function () {
				reject(new Error('style:' + entry.handle));
			};
			document.head.appendChild(link);
		});
	}

	function loadScript(entry) {
		return new Promise(function (resolve, reject) {
			if (!entry || !entry.src) {
				resolve();
				return;
			}

			if (document.querySelector('script[data-wpp-lazy-script="' + entry.handle + '"]')) {
				resolve();
				return;
			}

			var script = document.createElement('script');
			script.src = entry.src;
			script.async = false;
			script.setAttribute('data-wpp-lazy-script', entry.handle);
			script.onload = function () {
				if (entry.handle === 'wpp-cropper-lib') {
					window.WppCropperLib =
						typeof window.Cropper === 'function' &&
						window.Cropper.prototype &&
						typeof window.Cropper.prototype.getCroppedCanvas === 'function'
							? window.Cropper
							: null;
				}
				resolve();
			};
			script.onerror = function () {
				reject(new Error('script:' + entry.handle));
			};
			document.body.appendChild(script);
		});
	}

	function loadAssets() {
		var manifest = wppData.lazyAssets || { scripts: [], styles: [] };
		var chain = Promise.resolve();

		(manifest.styles || []).forEach(function (entry) {
			chain = chain.then(function () {
				return loadStyle(entry);
			});
		});

		(manifest.scripts || []).forEach(function (entry) {
			chain = chain.then(function () {
				return loadScript(entry);
			});
		});

		return chain;
	}

	function ensurePersonalizerLoaded() {
		if (loaded && window.WppPersonalizer && window.WppPersonalizer.isReady()) {
			return Promise.resolve(window.WppPersonalizer);
		}

		if (loadPromise) {
			return loadPromise;
		}

		loadPromise = loadAssets()
			.then(function () {
				if (!window.WppPersonalizer || typeof window.WppPersonalizer.init !== 'function') {
					throw new Error('personalizer_missing');
				}

				window.WppPersonalizer.init();
				loaded = true;
				unbindLazyTriggers();
				return window.WppPersonalizer;
			})
			.catch(function (err) {
				loadPromise = null;
				throw err;
			});

		return loadPromise;
	}

	function runWithLoader(run) {
		showLoader();

		return ensurePersonalizerLoaded()
			.then(function (api) {
				hideLoader();
				return run(api);
			})
			.catch(function () {
				hideLoader();
				window.alert(i18n('loadFailed', 'Could not load the personalizer. Please refresh the page and try again.'));
			});
	}

	function unbindLazyTriggers() {
		$(document).off('click.wppLazyOpen');
		$(document).off('click.wppLazyAtc');
	}

	function bindLazyTriggers() {
		if (wppData.replaceAddToCart) {
			$('form.cart .single_add_to_cart_button').addClass('wpp-atc-replaced');
		}

		$(document).on('click.wppLazyOpen', '.wpp-open-personalizer', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			runWithLoader(function (api) {
				api.openModal(e);
			});
		});

		$(document).on('click.wppLazyAtc', 'form.cart .single_add_to_cart_button.wpp-atc-replaced', function (e) {
			var nativeEvent = e;

			runWithLoader(function (api) {
				var action = api.handleReplacedAddToCart(nativeEvent);

				if (action === 'submit') {
					var $form = $('form.cart');
					$form.off('click.wppLazyAtc');
					$form.trigger('submit');
				}
			});

			e.preventDefault();
			e.stopImmediatePropagation();
		});
	}

	$(function () {
		bindLazyTriggers();
	});
})(jQuery, window);
