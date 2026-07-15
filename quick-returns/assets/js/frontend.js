(function () {
	'use strict';

	if (typeof quickReturns === 'undefined') {
		return;
	}

	const cfg = quickReturns;
	const i18n = cfg.i18n;

	function esc(str) {
		const el = document.createElement('span');
		el.textContent = str || '';
		return el.innerHTML;
	}

	function formatPrice(amount, currency) {
		const c = currency || cfg.currency || {};
		const decimals = typeof c.decimals === 'number' ? c.decimals : 2;
		const fixed = (amount || 0).toFixed(decimals);
		const parts = fixed.split('.');
		const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, c.thousandSeparator || ',');
		const formatted = parts.length > 1
			? intPart + (c.decimalSeparator || '.') + parts[1]
			: intPart;
		const priceFormat = c.format || '%1$s%2$s';
		return priceFormat
			.replace('%1$s', c.symbol || '')
			.replace('%2$s', formatted);
	}

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.from((ctx || document).querySelectorAll(sel));
	}

	function ajax(action, data) {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', cfg.nonce);
		Object.keys(data).forEach(function (key) {
			const val = data[key];
			if (typeof val === 'object') {
				body.append(key, JSON.stringify(val));
			} else {
				body.append(key, val);
			}
		});
		return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (res) { return res.json(); });
	}

	function resolveOrderContext(orderId, mode, email) {
		const ctx = cfg.orderContext || null;
		const resolved = {
			orderId: orderId || (ctx && ctx.order_id) || 0,
			mode: mode || (ctx && ctx.mode) || 'manual_select',
			email: email || (ctx && ctx.email) || '',
		};

		if (cfg.isLoggedIn && resolved.orderId && !resolved.email && ctx && ctx.email) {
			resolved.email = ctx.email;
		}

		return resolved;
	}

	function applyFormContext(form, orderId, mode, email) {
		const resolved = resolveOrderContext(orderId, mode, email);
		const loggedIn = cfg.isLoggedIn || document.body.classList.contains('logged-in');
		const canSkip = loggedIn && resolved.orderId;

		form.dataset.qrOrderId = String(resolved.orderId || 0);
		form.dataset.qrMode = resolved.mode;
		if (resolved.email) {
			form.dataset.qrEmail = resolved.email;
		}

		if (canSkip) {
			form.dataset.qrInitialStep = '2';
		} else {
			form.dataset.qrInitialStep = form.dataset.qrContext === 'modal' ? '0' : '1';
		}

		return resolved;
	}

	function renderStepper(step) {
		const steps = [
			{ num: 1, label: i18n.stepOrder },
			{ num: 2, label: i18n.stepProducts },
			{ num: 3, label: i18n.stepConfirmation },
		];
		let html = '<div class="qr-stepper">';
		steps.forEach(function (s) {
			let cls = '';
			if (s.num < step) cls = 'is-done';
			else if (s.num === step) cls = 'is-active';
			const content = s.num < step ? '✓' : s.num;
			html += '<div class="qr-stepper__item ' + cls + '">';
			html += '<div class="qr-stepper__circle">' + content + '</div>';
			html += '<span class="qr-stepper__label">' + esc(s.label) + '</span>';
			html += '</div>';
		});
		html += '</div>';
		return html;
	}

	function renderLoader() {
		return '<div class="qr-loader"><div class="qr-loader__spinner"></div></div>';
	}

	class ReturnForm {
		constructor(root) {
			this.root = root;
			this.app = qs('.qr-app', root);
			this.context = root.dataset.qrContext || 'inline';
			this.step = parseInt(root.dataset.qrInitialStep, 10) || 0;
			this.orderId = parseInt(root.dataset.qrOrderId, 10) || 0;
			this.mode = root.dataset.qrMode || 'manual_select';
			this.orderData = null;
			this.email = root.dataset.qrEmail || '';
			this.currency = null;
			this.items = [];
			this.loading = false;

			this.init();
		}

		init() {
			const loggedIn = cfg.isLoggedIn || document.body.classList.contains('logged-in');

			if (loggedIn && this.orderId && this.step !== 2) {
				this.step = 2;
			}

			if (this.step === 0) {
				this.renderIntro();
			} else if (this.step === 2 && this.orderId) {
				this.loadOrderItems();
			} else {
				this.step = 1;
				this.renderOrderStep();
			}
		}

		setLoading(on) {
			this.loading = on;
		}

		renderIntro() {
			const desc = cfg.settings.introDescription || '';
			this.app.innerHTML =
				'<p class="qr-intro__text">' + esc(desc) + '</p>' +
				'<button type="button" class="qr-btn qr-btn--primary" data-action="start">' +
				esc(cfg.settings.triggerText || 'Start request') +
				'</button>';
			this.bindActions();
		}

		renderOrderStep() {
			this.app.innerHTML =
				renderStepper(1) +
				'<div class="qr-field">' +
				'<label for="qr-order-number">' + esc(i18n.labelOrderNumber) + ' <span class="required">*</span></label>' +
				'<input type="text" id="qr-order-number" class="qr-input" placeholder="' + esc(i18n.placeholderOrderNumber) + '" autocomplete="off">' +
				'</div>' +
				'<div class="qr-field">' +
				'<label for="qr-email">' + esc(i18n.labelEmail) + ' <span class="required">*</span></label>' +
				'<input type="email" id="qr-email" class="qr-input" placeholder="' + esc(i18n.placeholderEmail) + '" autocomplete="email">' +
				'</div>' +
				'<p class="qr-hint">' + esc(i18n.hintOrderLookup) + '</p>' +
				'<div class="qr-error-box" data-error style="display:none"></div>' +
				'<button type="button" class="qr-btn qr-btn--primary" data-action="lookup">' + esc(i18n.buttonSearchOrder) + '</button>' +
				(this.context === 'modal' ? '<button type="button" class="qr-link" data-action="back-intro">' + esc(i18n.buttonBack) + '</button>' : '');
			this.bindActions();
		}

		loadOrderItems() {
			this.app.innerHTML = renderStepper(2) + renderLoader();
			this.setLoading(true);
			ajax('quick_returns_get_items', { order_id: this.orderId, mode: this.mode })
				.then(this.handleItemsResponse.bind(this))
				.catch(this.handleError.bind(this));
		}

		handleItemsResponse(res) {
			this.setLoading(false);
			const loggedIn = cfg.isLoggedIn || document.body.classList.contains('logged-in');
			if (!res.success) {
				if (loggedIn && this.orderId) {
					this.app.innerHTML = renderStepper(2) +
						'<div class="qr-error-box" data-error>' + esc(res.data && res.data.message ? res.data.message : i18n.errorGeneric) + '</div>';
					return;
				}
				this.step = 1;
				this.renderOrderStep();
				this.showError(res.data && res.data.message ? res.data.message : i18n.errorGeneric);
				return;
			}
			this.orderData = res.data;
			this.orderId = res.data.order_id;
			this.email = res.data.customer_email;
			this.currency = res.data.currency || null;
			this.initItems(res.data.items);
			this.step = 2;
			this.renderProductsStep();
		}

		initItems(serverItems) {
			this.items = serverItems.map(function (item) {
				return {
					order_item_id: item.order_item_id,
					product_id: item.product_id,
					name: item.name,
					sku: item.sku,
					ean: item.ean,
					price: item.price,
					price_formatted: item.price_formatted,
					purchased_qty: item.purchased_qty,
					returned_qty: item.returned_qty,
					available_qty: item.available_qty,
					eligible: item.eligible,
					ineligible_msg: item.ineligible_msg,
					thumbnail: item.thumbnail,
					selected: !!item.selected,
					quantity: item.selected ? 1 : 0,
					reason: '',
					comment: '',
				};
			});
		}

		renderProductsStep() {
			const od = this.orderData;
			let html = renderStepper(2);
			html += '<div class="qr-order-info">' + esc(i18n.labelOrder) + ' <strong>' + esc(od.order_number) + '</strong>';
			if (od.customer_name) {
				html += ' · ' + esc(od.customer_name);
			}
			html += '</div>';

			this.items.forEach(function (item, idx) {
				const disabled = !item.eligible;
				const selected = item.selected && item.eligible;
				html += '<div class="qr-product' + (selected ? ' is-selected' : '') + (disabled ? ' is-disabled' : '') + '" data-idx="' + idx + '">';
				html += '<div class="qr-product__check">';
				html += '<input type="checkbox" data-field="selected" ' + (selected ? 'checked' : '') + (disabled ? ' disabled' : '') + ' aria-label="' + esc(item.name) + '">';
				html += '</div>';
				html += '<div class="qr-product__thumb">' + (item.thumbnail || '<span class="qr-product-placeholder"></span>') + '</div>';
				html += '<div class="qr-product__body">';
				html += '<h3 class="qr-product__name">' + esc(item.name) + '</h3>';
				html += '<p class="qr-product__meta">';
				if (item.sku) html += 'SKU: ' + esc(item.sku) + ' ';
				if (item.ean) html += 'EAN: ' + esc(item.ean);
				html += '</p>';
				html += '<p class="qr-product__qty-info"><strong>' + item.purchased_qty + ' ' + esc(i18n.pcs) + '</strong>';
				if (item.returned_qty > 0) {
					html += ' <span class="qr-product__returned">(' + item.returned_qty + ' ' + esc(i18n.pcs) + ' ' + esc(i18n.alreadyReturned) + ')</span>';
				}
				html += '</p>';

				if (!disabled) {
					html += '<div class="qr-product__fields" data-fields style="' + (selected ? '' : 'display:none') + '">';
					html += '<div class="qr-field"><label>' + esc(i18n.labelQuantity) + '</label><select class="qr-select" data-field="quantity">';
					for (let q = 1; q <= item.available_qty; q++) {
						html += '<option value="' + q + '"' + (item.quantity === q ? ' selected' : '') + '>' + q + ' ' + esc(i18n.pcs) + '</option>';
					}
					html += '</select></div>';
					html += '<div class="qr-field"><label>' + esc(i18n.labelReason) + ' <span class="required">*</span></label>';
					html += '<select class="qr-select" data-field="reason"><option value="">' + esc(i18n.selectPlaceholder) + '</option>';
					(cfg.settings.returnReasons || []).forEach(function (r) {
						html += '<option value="' + esc(r) + '"' + (item.reason === r ? ' selected' : '') + '>' + esc(r) + '</option>';
					});
					html += '</select></div>';
					html += '<div class="qr-field"><label>' + esc(i18n.labelComment) + '</label>';
					html += '<textarea class="qr-textarea" data-field="comment" placeholder="' + esc(i18n.placeholderComment) + '">' + esc(item.comment) + '</textarea>';
					html += '</div></div>';
				} else if (item.ineligible_msg) {
					html += '<p class="qr-error">' + esc(item.ineligible_msg) + '</p>';
				}

				html += '</div></div>';
			});

			const summary = this.calcSummary();
			html += '<div class="qr-summary" data-summary style="' + (summary.count ? '' : 'display:none') + '">';
			html += '<div class="qr-summary__row"><span>' + esc(i18n.selectedProducts) + '</span><span data-sum-count>' + summary.count + '</span></div>';
			html += '<div class="qr-summary__row"><span>' + esc(i18n.totalQuantity) + '</span><span data-sum-qty>' + summary.qty + '</span></div>';
			html += '<div class="qr-summary__row"><span>' + esc(i18n.estimatedValue) + '</span><span data-sum-value></span></div>';
			html += '<p class="qr-summary__disclaimer">' + esc(i18n.valueDisclaimer) + '</p></div>';

			html += '<div class="qr-error-box" data-error style="display:none"></div>';
			html += '<button type="button" class="qr-btn qr-btn--primary" data-action="submit">' + esc(i18n.buttonSubmit) + '</button>';
			html += '<button type="button" class="qr-link" data-action="back-order">' + esc(i18n.buttonBack) + '</button>';

			this.app.innerHTML = html;
			const sumValue = qs('[data-sum-value]', this.app);
			if (sumValue) {
				sumValue.textContent = summary.valueFormatted;
			}
			this.bindProductEvents();
			this.bindActions();
		}

		calcSummary() {
			let count = 0, qty = 0, value = 0;
			this.items.forEach(function (item) {
				if (item.selected && item.eligible) {
					count++;
					qty += item.quantity || 0;
					value += (item.price || 0) * (item.quantity || 0);
				}
			});
			return { count: count, qty: qty, value: value, valueFormatted: formatPrice(value, this.currency) };
		}

		updateSummary() {
			const summary = this.calcSummary();
			const box = qs('[data-summary]', this.app);
			if (!box) return;
			box.style.display = summary.count ? '' : 'none';
			const c = qs('[data-sum-count]', box);
			const q = qs('[data-sum-qty]', box);
			const v = qs('[data-sum-value]', box);
			if (c) c.textContent = summary.count;
			if (q) q.textContent = summary.qty;
			if (v) v.textContent = summary.valueFormatted;
		}

		bindProductEvents() {
			const self = this;

			function syncProductSelection(card, idx, checkbox, fields) {
				self.items[idx].selected = checkbox.checked;
				if (checkbox.checked && !self.items[idx].quantity) {
					self.items[idx].quantity = 1;
				}
				card.classList.toggle('is-selected', checkbox.checked);
				if (fields) fields.style.display = checkbox.checked ? '' : 'none';
				self.updateSummary();
			}

			qsa('.qr-product', this.app).forEach(function (card) {
				const idx = parseInt(card.dataset.idx, 10);
				const checkbox = qs('[data-field="selected"]', card);
				const fields = qs('[data-fields]', card);

				if (checkbox) {
					checkbox.addEventListener('change', function () {
						syncProductSelection(card, idx, checkbox, fields);
					});
				}

				card.addEventListener('click', function (e) {
					if (!checkbox || checkbox.disabled || card.classList.contains('is-disabled')) {
						return;
					}
					if (e.target === checkbox || e.target.closest('select, textarea, button, a, label')) {
						return;
					}
					checkbox.checked = !checkbox.checked;
					checkbox.dispatchEvent(new Event('change', { bubbles: true }));
				});

				qsa('[data-field]', card).forEach(function (el) {
					if (el === checkbox) return;
					el.addEventListener('change', function () {
						const field = el.dataset.field;
						if (field === 'quantity') self.items[idx].quantity = parseInt(el.value, 10);
						else if (field === 'reason') self.items[idx].reason = el.value;
						else if (field === 'comment') self.items[idx].comment = el.value;
						self.updateSummary();
					});
					if (el.tagName === 'TEXTAREA') {
						el.addEventListener('input', function () {
							self.items[idx].comment = el.value;
						});
					}
				});
			});
		}

		renderConfirmation(data) {
			this.step = 3;
			this.app.innerHTML =
				renderStepper(3) +
				'<div class="qr-confirmation">' +
				'<div class="qr-confirmation__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg></div>' +
				'<h3 class="qr-confirmation__title">' + esc(i18n.confirmationTitle) + '</h3>' +
				'<p class="qr-confirmation__text">' + esc(data.message) + '</p>' +
				(data.ship_notice ? '<p class="qr-confirmation__notice">' + esc(data.ship_notice) + '</p>' : '') +
				(data.refund_notice ? '<p class="qr-confirmation__notice">' + esc(data.refund_notice) + '</p>' : '') +
				(data.return_address ? '<p class="qr-confirmation__notice"><strong>' + esc(i18n.labelReturnAddress) + '</strong><br>' + esc(data.return_address) + '</p>' : '') +
				'<a href="' + esc(cfg.shopUrl || '/') + '" class="qr-btn qr-btn--primary">' + esc(i18n.buttonBackToShop) + '</a>' +
				'</div>';
		}

		showError(msg) {
			const box = qs('[data-error]', this.app);
			if (box) {
				box.textContent = msg;
				box.style.display = msg ? '' : 'none';
			}
		}

		handleError() {
			this.setLoading(false);
			this.showError(i18n.errorGeneric);
		}

		bindActions() {
			const self = this;
			qsa('[data-action]', this.app).forEach(function (btn) {
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					const action = btn.dataset.action;
					if (action === 'start') {
						self.step = 1;
						self.renderOrderStep();
					} else if (action === 'back-intro') {
						self.step = 0;
						self.renderIntro();
					} else if (action === 'back-order') {
						const loggedIn = cfg.isLoggedIn || document.body.classList.contains('logged-in');
						if (loggedIn && self.orderId) {
							if (self.context === 'modal') {
								closeModal();
							}
							return;
						}
						if (self.orderId && self.context === 'inline') {
							self.step = 1;
							self.renderOrderStep();
						} else if (self.context === 'modal') {
							self.step = 0;
							self.renderIntro();
						} else {
							self.step = 1;
							self.renderOrderStep();
						}
					} else if (action === 'lookup') {
						self.doLookup();
					} else if (action === 'submit') {
						self.doSubmit();
					}
				});
			});
		}

		doLookup() {
			const self = this;
			const orderNumber = (qs('#qr-order-number', this.app) || {}).value || '';
			const email = (qs('#qr-email', this.app) || {}).value || '';
			if (!orderNumber.trim() || !email.trim()) {
				this.showError(i18n.errorRequiredFields);
				return;
			}
			this.email = email;
			this.showError('');
			const btn = qs('[data-action="lookup"]', this.app);
			if (btn) btn.disabled = true;
			this.app.insertAdjacentHTML('beforeend', '<div class="qr-loader" data-temp-loader>' + renderLoader() + '</div>');

			ajax('quick_returns_lookup_order', { order_number: orderNumber, email: email, mode: this.mode })
				.then(function (res) {
					const loader = qs('[data-temp-loader]', self.app);
					if (loader) loader.remove();
					if (btn) btn.disabled = false;
					if (!res.success) {
						self.showError(res.data && res.data.message ? res.data.message : i18n.errorOrderNotFound);
						return;
					}
					self.orderData = res.data;
					self.orderId = res.data.order_id;
					self.initItems(res.data.items);
					self.step = 2;
					self.renderProductsStep();
				})
				.catch(function () {
					const loader = qs('[data-temp-loader]', self.app);
					if (loader) loader.remove();
					if (btn) btn.disabled = false;
					self.showError(i18n.errorGeneric);
				});
		}

		doSubmit() {
			const selected = this.items.filter(function (i) { return i.selected && i.eligible; });
			if (!selected.length) {
				this.showError(i18n.errorSelectProduct);
				return;
			}
			for (let i = 0; i < selected.length; i++) {
				if (!selected[i].reason) {
					this.showError(i18n.errorSelectReason);
					return;
				}
			}

			const payload = selected.map(function (item) {
				return {
					order_item_id: item.order_item_id,
					quantity: item.quantity,
					reason: item.reason,
					comment: item.comment,
				};
			});

			const btn = qs('[data-action="submit"]', this.app);
			if (btn) btn.disabled = true;
			this.showError('');

			const self = this;
			ajax('quick_returns_submit', {
				order_id: this.orderId,
				email: this.email,
				items: payload,
				source: this.context,
			})
				.then(function (res) {
					if (btn) btn.disabled = false;
					if (!res.success) {
						self.showError(res.data && res.data.message ? res.data.message : i18n.errorGeneric);
						return;
					}
					self.renderConfirmation(res.data);
				})
				.catch(function () {
					if (btn) btn.disabled = false;
					self.showError(i18n.errorGeneric);
				});
		}
	}

	/* Modal */
	const modal = document.getElementById('qr-return-modal');

	function openModal(orderId, mode, email) {
		if (!modal) return;
		const form = qs('[data-qr-form]', modal);
		if (form) {
			const resolved = applyFormContext(form, orderId, mode, email);
			const app = qs('.qr-app', form);
			if (app) app.innerHTML = '';
			new ReturnForm(form);
		}
		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		if (!modal) return;
		modal.hidden = true;
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}

	if (modal) {
		qsa('[data-qr-close]', modal).forEach(function (el) {
			el.addEventListener('click', closeModal);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !modal.hidden) closeModal();
		});
	}

	/* Triggers */
	document.addEventListener('click', function (e) {
		const trigger = e.target.closest('[data-qr-trigger]');
		if (trigger) {
			e.preventDefault();
			openModal(
				parseInt(trigger.dataset.qrOrderId, 10) || 0,
				trigger.dataset.qrMode || 'manual_select',
				trigger.dataset.qrEmail || ''
			);
			return;
		}

		const selectors = (cfg.settings.triggerSelectors || '').split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
		selectors.forEach(function (sel) {
			try {
				if (e.target.closest(sel)) {
					e.preventDefault();
					openModal(0, 'manual_select', '');
				}
			} catch (err) { /* invalid selector */ }
		});
	});

	/* Init inline forms */
	qsa('[data-qr-form]').forEach(function (form) {
		if (form.closest('#qr-return-modal')) return;
		new ReturnForm(form);
	});
})();
