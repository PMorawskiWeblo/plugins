(function (global) {
	'use strict';

	var loadedUrls = {};

	function isGoogleFontsCssUrl(url) {
		if (!url || typeof url !== 'string') {
			return false;
		}

		return /^https:\/\/fonts\.googleapis\.com\/css/i.test(url.trim());
	}

	function parseLines(text) {
		var urls = [];

		String(text || '')
			.split(/\r?\n/)
			.forEach(function (line) {
				var trimmed = line.trim();

				if (!trimmed) {
					return;
				}

				var match = trimmed.match(/https:\/\/fonts\.googleapis\.com\/css[^\s"'<>]*/i);

				if (match && isGoogleFontsCssUrl(match[0])) {
					urls.push(match[0]);
				}
			});

		return urls.filter(function (url, index, list) {
			return list.indexOf(url) === index;
		});
	}

	function decodeFamilyName(raw) {
		return String(raw || '')
			.replace(/\+/g, ' ')
			.trim();
	}

	function parseFamiliesFromUrl(url) {
		var families = [];
		var family;

		try {
			var parsed = new URL(url);
			var params = parsed.searchParams;

			params.getAll('family').forEach(function (value) {
				family = decodeFamilyName(value.split(':')[0]);

				if (family && families.indexOf(family) === -1) {
					families.push(family);
				}
			});

			if (!families.length) {
				parsed.search
					.replace(/^\?/, '')
					.split('&')
					.forEach(function (part) {
						if (part.indexOf('family=') !== 0) {
							return;
						}

						family = decodeFamilyName(decodeURIComponent(part.slice(7).split(':')[0]));

						if (family && families.indexOf(family) === -1) {
							families.push(family);
						}
					});
			}
		} catch (e) {
			var regex = /family=([^&]+)/gi;

			while ((family = regex.exec(url))) {
				var name = decodeFamilyName(decodeURIComponent(family[1].split(':')[0]));

				if (name && families.indexOf(name) === -1) {
					families.push(name);
				}
			}
		}

		return families;
	}

	function resolveFieldFonts(field) {
		var links = Array.isArray(field && field.google_fonts) ? field.google_fonts.slice() : [];
		var options = [];
		var seen = {};

		links.forEach(function (url) {
			if (!isGoogleFontsCssUrl(url)) {
				return;
			}

			var families = parseFamiliesFromUrl(url);
			var family = families[0] || '';

			if (!family || seen[family]) {
				return;
			}

			seen[family] = true;
			options.push({
				family: family,
				url: url
			});
		});

		return {
			links: links.filter(isGoogleFontsCssUrl),
			options: options
		};
	}

	function shouldShowFontSelect(field) {
		return resolveFieldFonts(field).links.length > 1;
	}

	function getDefaultFontFamily(field) {
		var resolved = resolveFieldFonts(field);

		if (resolved.options.length) {
			return resolved.options[0].family;
		}

		return (field && field.style && field.style.fontFamily) || 'Arial';
	}

	function getEffectiveFontFamily(field, meta) {
		var resolved = resolveFieldFonts(field);

		if (!resolved.options.length) {
			return (field && field.style && field.style.fontFamily) || 'Arial';
		}

		if (resolved.links.length === 1) {
			return resolved.options[0].family;
		}

		if (meta && meta.fontFamily) {
			var allowed = resolved.options.some(function (opt) {
				return opt.family === meta.fontFamily;
			});

			if (allowed) {
				return meta.fontFamily;
			}
		}

		return resolved.options[0].family;
	}

	function loadUrls(urls, callback) {
		var pending = [];
		var unique = (urls || []).filter(function (url, index, list) {
			return isGoogleFontsCssUrl(url) && list.indexOf(url) === index;
		});

		unique.forEach(function (url) {
			if (loadedUrls[url]) {
				return;
			}

			loadedUrls[url] = true;
			pending.push(url);

			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = url;
			document.head.appendChild(link);
		});

		if (!pending.length) {
			if (typeof callback === 'function') {
				callback();
			}
			return;
		}

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				if (typeof callback === 'function') {
					callback();
				}
			});
			return;
		}

		window.setTimeout(function () {
			if (typeof callback === 'function') {
				callback();
			}
		}, 400);
	}

	function collectLayoutUrls(layout) {
		var urls = [];

		(layout && layout.text_fields ? layout.text_fields : []).forEach(function (field) {
			resolveFieldFonts(field).links.forEach(function (url) {
				if (urls.indexOf(url) === -1) {
					urls.push(url);
				}
			});
		});

		return urls;
	}

	function collectUsedFieldUrls(layout, textState, hasTextFn) {
		var urls = [];
		var checkText = typeof hasTextFn === 'function' ? hasTextFn : function () {
			return true;
		};

		(layout && layout.text_fields ? layout.text_fields : []).forEach(function (field) {
			if (!field || !field.id || !checkText(field, textState)) {
				return;
			}

			resolveFieldFonts(field).links.forEach(function (url) {
				if (urls.indexOf(url) === -1) {
					urls.push(url);
				}
			});
		});

		return urls;
	}

	function buildSvgFontStyleBlock(urls) {
		if (!urls || !urls.length) {
			return '';
		}

		var imports = urls
			.map(function (url) {
				return '@import url("' + String(url).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '");';
			})
			.join('\n');

		return '<defs><style type="text/css"><![CDATA[\n' + imports + '\n]]></style></defs>\n';
	}

	global.WppGoogleFonts = {
		isGoogleFontsCssUrl: isGoogleFontsCssUrl,
		parseLines: parseLines,
		parseFamiliesFromUrl: parseFamiliesFromUrl,
		resolveFieldFonts: resolveFieldFonts,
		shouldShowFontSelect: shouldShowFontSelect,
		getDefaultFontFamily: getDefaultFontFamily,
		getEffectiveFontFamily: getEffectiveFontFamily,
		loadUrls: loadUrls,
		collectLayoutUrls: collectLayoutUrls,
		collectUsedFieldUrls: collectUsedFieldUrls,
		buildSvgFontStyleBlock: buildSvgFontStyleBlock
	};
})(typeof window !== 'undefined' ? window : this);
