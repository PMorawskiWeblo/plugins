/**
 * Admin JavaScript for Weblo Search Engine.
 *
 * @package Weblo_Search_Engine
 */

(function($) {
	'use strict';

	/**
	 * Initialize Select2 for multi-select fields with sortable functionality.
	 */
	function initSelect2() {
		// Initialize Select2 for Recommended Products.
		const $productsSelect = $('#weblo_search_recommended_products');
		$productsSelect.select2({
			placeholder: webloSearchAdmin.productsPlaceholder || 'Search products...',
			allowClear: false,
			width: '100%',
			closeOnSelect: false
		});

		// Make products select2 choices sortable.
		initSelect2Sortable($productsSelect);

		// Initialize Select2 for Recommended Categories.
		const $categoriesSelect = $('#weblo_search_recommended_categories');
		$categoriesSelect.select2({
			placeholder: webloSearchAdmin.categoriesPlaceholder || 'Search categories...',
			allowClear: false,
			width: '100%',
			closeOnSelect: false
		});

		// Make categories select2 choices sortable.
		initSelect2Sortable($categoriesSelect);
	}

	/**
	 * Initialize sortable for Select2 choices.
	 */
	function initSelect2Sortable($select) {
		// Wait for Select2 to be fully initialized.
		$select.on('select2:select select2:unselect', function() {
			setTimeout(function() {
				enableSelect2Sortable($select);
			}, 100);
		});

		// Initial setup.
		setTimeout(function() {
			enableSelect2Sortable($select);
		}, 300);
	}

	/**
	 * Enable sortable on Select2 choices container.
	 */
	function enableSelect2Sortable($select) {
		const $container = $select.next('.select2-container').find('.select2-selection__rendered');
		
		// Remove existing sortable if any.
		if ($container.hasClass('ui-sortable')) {
			$container.sortable('destroy');
		}

		// Make choices sortable.
		if ($container.find('.select2-selection__choice').length > 1) {
			$container.sortable({
				items: '.select2-selection__choice',
				tolerance: 'pointer',
				stop: function() {
					// Update the order in the original select element.
					updateSelect2Order($select);
				},
				update: function() {
					// Update the order in the original select element.
					updateSelect2Order($select);
				}
			});
		}
	}

	/**
	 * Update the order of options in select element based on sortable.
	 */
	function updateSelect2Order($select) {
		const $container = $select.next('.select2-container').find('.select2-selection__rendered');
		const selectedValues = [];
		
		// Get order from sorted choices.
		$container.find('.select2-selection__choice').each(function() {
			const $choice = $(this);
			// Get value from data attributes - Select2 stores it in data('data')
			const dataObj = $choice.data('data');
			let value = null;
			
			if (dataObj && dataObj.id !== undefined) {
				value = String(dataObj.id);
			} else {
				// Fallback: find by text content.
				const choiceText = $choice.find('.select2-selection__choice__display').text().trim() || $choice.text().trim();
				$select.find('option').each(function() {
					if ($(this).text().trim() === choiceText) {
						value = $(this).val();
						return false;
					}
				});
			}
			
			if (value) {
				selectedValues.push(value);
			}
		});

		// Get current selected values to preserve selection.
		const currentValues = $select.val() || [];
		
		// Reorder options in the select element.
		const $options = $select.find('option');
		const optionsArray = $options.toArray();
		
		// Separate selected and unselected options.
		const selectedOptions = [];
		const unselectedOptions = [];
		
		optionsArray.forEach(function(opt) {
			const $opt = $(opt);
			if (currentValues.indexOf($opt.val()) !== -1) {
				selectedOptions.push(opt);
			} else {
				unselectedOptions.push(opt);
			}
		});

		// Sort selected options based on selectedValues order.
		const sortedSelectedOptions = selectedValues.map(function(val) {
			return selectedOptions.find(function(opt) {
				return $(opt).val() == val;
			});
		}).filter(function(opt) {
			return opt !== undefined;
		});

		// Combine: sorted selected options + unselected options.
		const sortedOptions = sortedSelectedOptions.concat(unselectedOptions);

		// Update the select element.
		$select.empty().append(sortedOptions);
		
		// Re-select the values maintaining order.
		$select.val(selectedValues).trigger('change.select2');
	}

	/**
	 * Handle custom links.
	 */
	function initCustomLinks() {
		// Add new custom link row.
		$('#weblo-add-custom-link').on('click', function(e) {
			e.preventDefault();
			const $container = $('#weblo-custom-links-container');
			const $newRow = $('<div class="weblo-custom-link-row" style="margin-bottom: 10px;">' +
				'<input type="url" name="weblo_custom_link_url[]" value="" placeholder="' + 
				(webloSearchAdmin.urlPlaceholder || 'URL') + '" class="regular-text" style="width: 45%; margin-right: 10px;" />' +
				'<input type="text" name="weblo_custom_link_text[]" value="" placeholder="' + 
				(webloSearchAdmin.textPlaceholder || 'Link Text') + '" class="regular-text" style="width: 35%; margin-right: 10px;" />' +
				'<button type="button" class="button weblo-remove-link" style="width: 15%;">' + 
				(webloSearchAdmin.removeText || 'Remove') + '</button>' +
				'</div>');
			$container.append($newRow);
		});

		// Remove custom link row.
		$(document).on('click', '.weblo-remove-link', function(e) {
			e.preventDefault();
			$(this).closest('.weblo-custom-link-row').remove();
		});
	}

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function() {
		// Wait for Select2 to be loaded.
		if (typeof $.fn.select2 !== 'undefined') {
			initSelect2();
		} else {
			// Retry if Select2 is not loaded yet.
			setTimeout(function() {
				if (typeof $.fn.select2 !== 'undefined') {
					initSelect2();
				}
			}, 100);
		}

		// Initialize custom links.
		initCustomLinks();
	});

})(jQuery);

