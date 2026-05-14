/**
 * Frontend JavaScript for Weblo Pick-up Point
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		var $body = $('body');

		// Update checkout when pickup point radio button is selected
		$body.on('change', 'input[name="weblo_pickup_point"]', function() {
			var selectedValue = $(this).val();
			var $radio = $(this);
			
			// Update label classes for styling
			$('.weblo-pickup-point-radio-label').removeClass('weblo-selected');
			$radio.closest('.weblo-pickup-point-radio-label').addClass('weblo-selected');
			
			// Ensure the value is in the form data by adding a hidden input if needed
			var $hiddenInput = $('#weblo_pickup_point_hidden');
			if ($hiddenInput.length === 0) {
				$hiddenInput = $('<input>', {
					type: 'hidden',
					id: 'weblo_pickup_point_hidden',
					name: 'weblo_pickup_point'
				});
				$('form.checkout').append($hiddenInput);
			}
			$hiddenInput.val(selectedValue);
			
			// Save to session via AJAX before updating checkout
			var ajaxData = {
				action: 'weblo_update_pickup_point',
				pickup_point: selectedValue
			};
			
			// Add nonce if available
			if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.update_order_review_nonce) {
				ajaxData.security = wc_checkout_params.update_order_review_nonce;
			}
			
			// Update price in shipping method label immediately
			updateShippingMethodPrice(selectedValue);
			
			if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url) {
				$.ajax({
					type: 'POST',
					url: wc_checkout_params.ajax_url,
					data: ajaxData,
					success: function(response) {
						// Update price again after AJAX (in case checkout was updated)
						updateShippingMethodPrice(selectedValue);
						// Trigger update_checkout to recalculate shipping
						// WooCommerce will send form data including weblo_pickup_point
						$body.trigger('update_checkout');
					},
					error: function() {
						// Update price again after AJAX error
						updateShippingMethodPrice(selectedValue);
						// Fallback: trigger update_checkout anyway (will use woocommerce_checkout_update_order_review hook)
						$body.trigger('update_checkout');
					}
				});
			} else {
				// Fallback: trigger update_checkout directly (will use woocommerce_checkout_update_order_review hook)
				$body.trigger('update_checkout');
			}
		});
		
		/**
		 * Update shipping method price in label
		 */
		function updateShippingMethodPrice(selectedPointName) {
			if (!selectedPointName || typeof webloPickupPoint === 'undefined' || !webloPickupPoint.pickupPoints) {
				return;
			}
			
			// Find the selected point price
			var selectedPrice = 0;
			for (var i = 0; i < webloPickupPoint.pickupPoints.length; i++) {
				if (webloPickupPoint.pickupPoints[i].name === selectedPointName) {
					selectedPrice = parseFloat(webloPickupPoint.pickupPoints[i].price) || 0;
					break;
				}
			}
			
			// Find all shipping method labels for weblo_pickup_point
			$('input[name^="shipping_method"]').each(function() {
				var $input = $(this);
				var methodValue = $input.val();
				
				if (methodValue && methodValue.indexOf('weblo_pickup_point') !== -1) {
					var $label = $input.closest('.shipping_method_col').find('label[for="' + $input.attr('id') + '"]');
					
					if ($label.length > 0) {
						// Find the price span
						var $priceSpan = $label.find('.woocommerce-Price-amount');
						
						if ($priceSpan.length > 0) {
							// Update the price value
							// Extract currency symbol
							var currencySymbol = $priceSpan.find('.woocommerce-Price-currencySymbol').text() || 'zł';
							
							// Format price based on WooCommerce format
							var priceText = parseFloat(selectedPrice).toFixed(2).replace('.', ',');
							
							// Update bdi content
							var $bdi = $priceSpan.find('bdi');
							if ($bdi.length > 0) {
								$bdi.html(priceText + '&nbsp;<span class="woocommerce-Price-currencySymbol">' + currencySymbol + '</span>');
							}
						} else {
							// Price span doesn't exist, add it
							var priceHtml = ': <span class="woocommerce-Price-amount amount"><bdi>' + parseFloat(selectedPrice).toFixed(2).replace('.', ',') + '&nbsp;<span class="woocommerce-Price-currencySymbol">zł</span></bdi></span>';
							$label.append(priceHtml);
						}
					}
				}
			});
		}
		
		// Update selected state on page load
		function updateSelectedRadioState() {
			$('.weblo-pickup-point-radio-label').removeClass('weblo-selected');
			$('input[name="weblo_pickup_point"]:checked').closest('.weblo-pickup-point-radio-label').addClass('weblo-selected');
		}

		// Initial check - ensure selector exists first
		ensurePickupPointSelectorExists();
		updatePickupPointSelectorVisibility();
		updateSelectedRadioState();
		
		// Update shipping method price on page load
		var $selectedRadio = $('input[name="weblo_pickup_point"]:checked');
		if ($selectedRadio.length > 0) {
			updateShippingMethodPrice($selectedRadio.val());
		}
		
		// Update pickup point selector visibility when checkout is updated
		$body.on('updated_checkout', function() {
			ensurePickupPointSelectorExists();
			updatePickupPointSelectorVisibility();
			updateSelectedRadioState();
			
			// Update shipping method price after checkout update
			var $selectedRadio = $('input[name="weblo_pickup_point"]:checked');
			if ($selectedRadio.length > 0) {
				updateShippingMethodPrice($selectedRadio.val());
			}
		});

		/**
		 * Format price using WooCommerce format
		 */
		function formatPrice(price) {
			// Try to use WooCommerce accounting format if available
			if (typeof accounting !== 'undefined' && typeof wc_checkout_params !== 'undefined') {
				var format = wc_checkout_params.currency_format_symbol || 'zł';
				return accounting.formatMoney(price, {
					symbol: format,
					format: wc_checkout_params.currency_format_symbol_pos === 'right' ? '%v %s' : '%s %v',
					decimal: wc_checkout_params.currency_format_decimal_sep || ',',
					thousand: wc_checkout_params.currency_format_thousand_sep || ' ',
					precision: parseInt(wc_checkout_params.currency_format_num_decimals, 10) || 2
				});
			}
			// Fallback - simple format
			return parseFloat(price).toFixed(2).replace('.', ',') + ' zł';
		}

		/**
		 * Ensure pickup point selector exists in DOM
		 */
		function ensurePickupPointSelectorExists() {
			var $selector = $('.weblo-pickup-point-selector');
			if ($selector.length === 0 && typeof webloPickupPoint !== 'undefined' && webloPickupPoint.pickupPoints && webloPickupPoint.pickupPoints.length > 0) {
				// Selector doesn't exist, create it dynamically
				// Try to find shipping table or review order table
				var $targetTable = $('table.shop_table.shipping tbody');
				if ($targetTable.length === 0) {
					$targetTable = $('.woocommerce-checkout-review-order-table tbody');
				}
				if ($targetTable.length === 0) {
					$targetTable = $('table.woocommerce-checkout-review-order-table tbody');
				}
				if ($targetTable.length === 0) {
					// Try to find any table with shipping methods
					$targetTable = $('table tbody').has('input[name^="shipping_method"]').first();
				}
				
				if ($targetTable.length > 0) {
					var html = '<tr class="weblo-pickup-point-selector" style="display:none;">';
					html += '<th colspan="2">';
					html += '<label>Select a pick-up point</label>';
					html += '<ul class="weblo-pickup-point-radio-list" style="display:none;">';
					
					webloPickupPoint.pickupPoints.forEach(function(point, index) {
						html += '<li>';
						html += '<label class="weblo-pickup-point-radio-label">';
						html += '<input type="radio" name="weblo_pickup_point" value="' + $('<div>').text(point.name).html() + '" class="weblo-pickup-point-radio"' + (index === 0 ? ' checked="checked"' : '') + ' />';
						html += '<span class="weblo-pickup-point-name">' + $('<div>').text(point.name).html() + '</span>';
						html += '<span class="weblo-pickup-point-price">' + formatPrice(point.price) + '</span>';
						html += '</label>';
						html += '</li>';
					});
					
					html += '</ul>';
					html += '</th>';
					html += '</tr>';
					
					$targetTable.append(html);
				}
			}
		}

		/**
		 * Update pickup point selector visibility
		 */
		function updatePickupPointSelectorVisibility() {
			// Ensure selector exists in DOM
			ensurePickupPointSelectorExists();
			
			var isPickupPointSelected = false;
			
			// Check if our shipping method is selected (checked)
			$('input[name^="shipping_method"]:checked').each(function() {
				var methodValue = $(this).val();
				// Check if value contains 'weblo_pickup_point' (format: 'weblo_pickup_point:49')
				if (methodValue && methodValue.indexOf('weblo_pickup_point') !== -1) {
					isPickupPointSelected = true;
					return false; // break
				}
			});

			if (isPickupPointSelected) {
				// Show selector if it exists
				var $selector = $('.weblo-pickup-point-selector');
				if ($selector.length > 0) {
					$selector.show();
					$('.weblo-pickup-point-radio-list').show();
					
					// Auto-select first radio button if none is selected
					var $selectedRadio = $('input[name="weblo_pickup_point"]:checked');
					if ($selectedRadio.length === 0) {
						var $firstRadio = $('input[name="weblo_pickup_point"]').first();
						if ($firstRadio.length > 0) {
							$firstRadio.prop('checked', true);
							updateSelectedRadioState();
							$body.trigger('update_checkout');
						}
					} else {
						updateSelectedRadioState();
					}
				}
			} else {
				// Hide selector if it exists
				$('.weblo-pickup-point-selector').hide();
				$('.weblo-pickup-point-radio-list').hide();
			}
		}

		// Watch for shipping method changes
		$body.on('change', 'input[name^="shipping_method"]', function() {
			// Update visibility immediately
			updatePickupPointSelectorVisibility();
			
			// Also update after checkout update (in case selector is loaded via AJAX)
			setTimeout(function() {
				updatePickupPointSelectorVisibility();
			}, 100);
		});
		
		// Also watch for checkout updates to ensure selector is shown/hidden correctly
		$body.on('updated_checkout', function() {
			updatePickupPointSelectorVisibility();
		});
	});

})(jQuery);

