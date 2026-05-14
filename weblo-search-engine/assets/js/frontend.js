/**
 * Frontend JavaScript for Weblo Search Engine.
 *
 * @package Weblo_Search_Engine
 */

(function ($) {
  'use strict';

  /**
   * Main search engine object.
   */
  const WebloSearchEngine = {
    /**
     * Initialize the search engine.
     */
    init: function () {
      this.bindToggleTrigger();
      this.bindSearchInput();
      this.bindCloseButtons();
    },

    /**
     * Get container by instance ID or closest container.
     */
    getContainer: function ($element) {
      const instanceId = this.getInstanceId($element);
      // Try to find container by ID first.
      const $containerById = $('#' + instanceId + '-container');
      if ($containerById.length) {
        return $containerById;
      }
      // If no container with ID, find by data-instance-id or closest wrapper.
      const $containerByData = $('[data-instance-id="' + instanceId + '"]').closest('.weblo-search-engine');
      if ($containerByData.length) {
        return $containerByData;
      }
      // Fallback: find any element with matching instance ID.
      return $element.closest('[data-instance-id="' + instanceId + '"]').length
        ? $element.closest('[data-instance-id="' + instanceId + '"]')
        : $element.closest('.weblo-search-engine');
    },

    /**
     * Get instance ID from element.
     */
    getInstanceId: function ($element) {
      // First try to get from element's data attribute.
      if ($element.data('instance-id')) {
        return $element.data('instance-id');
      }
      // Try to get from element's ID.
      const elementId = $element.attr('id');
      if (elementId && elementId.includes('-')) {
        const parts = elementId.split('-');
        if (parts.length >= 3) {
          return parts[0] + '-' + parts[1] + '-' + parts[2];
        }
      }
      // Try to get from closest parent with data-instance-id.
      const $parent = $element.closest('[data-instance-id]');
      if ($parent.length) {
        return $parent.data('instance-id');
      }
      // Fallback.
      return 'weblo-search-1';
    },

    /**
     * Bind toggle trigger.
     */
    bindToggleTrigger: function () {
      const triggerClass = webloSearchEngine.triggerClass || 'search_engine_icon';
      const $header = $('#header');
      const $body = $('body');
      // Delegate click event to trigger class elements.
      $(document).on('click', '.' + triggerClass, function (e) {
        e.preventDefault();
        const $trigger = $(this);
        // Find associated container by class or use first one.
        const containerClass = $trigger.data('search-container');
        let $container;
        if (containerClass) {
          $container = $('.' + containerClass).first();
        } else {
          $container = $('.weblo-search-engine').first();
        }
        if ($container.length) {
          $container.toggleClass('weblo-search-hidden');
          $header.toggleClass('search_engine_open');
          $body.toggleClass('search_engine_active');
        }
      });
    },

    /**
     * Bind search input events.
     */
    bindSearchInput: function () {
      // Use event delegation for dynamically added inputs.
      $(document).on('input', '.weblo-search-input', function () {
        const $input = $(this);
        const instanceId = WebloSearchEngine.getInstanceId($input);

        // Find elements by instance ID directly (works for separated elements).
        const $loading = $input.closest('.weblo-search-input-wrapper').find('.weblo-search-loading');
        const $clearButton = $('#' + instanceId + '-clear-input');
        const $resultsContainer = $('#' + instanceId + '-results');
        const $wrapperResults = $('.weblo-search-wrapper-results[data-instance-id="' + instanceId + '"]');
        const $categoriesContainer = $('#' + instanceId + '-categories-results');
        const $noResults = $('#' + instanceId + '-no-results');
        const $recommendedProducts = $('#' + instanceId + '-recommended-products');
        const $recommendedCategories = $('#' + instanceId + '-recommended-categories');
        const $productsTitle = $('#' + instanceId + '-products-title');
        const $categoriesTitle = $('#' + instanceId + '-categories-title');

        const searchTerm = $input.val().trim();

        // Show/hide clear button.
        if (searchTerm.length > 0) {
          $clearButton.show();
        } else {
          $clearButton.hide();
        }
        $wrapperResults.hide();
        // Clear previous timer for this instance.
        if (!$input.data('debounce-timer')) {
          $input.data('debounce-timer', null);
        }
        clearTimeout($input.data('debounce-timer'));

        // Hide previous results.
        $resultsContainer.empty().hide();
        $categoriesContainer.empty().hide();
        $noResults.hide();

        // Get products column container.
        let $productsColumn = $resultsContainer.closest('.weblo-search-column.weblo-search-products');
        if (!$productsColumn.length) {
          // If not found by closest, try to find by instance ID.
          $productsColumn = $('.weblo-search-column.weblo-search-products[data-instance-id="' + instanceId + '"]');
        }
        const $body = $('body');

        // Check minimum length.
        if (searchTerm.length < webloSearchEngine.minLength) {
          // Show recommended, hide search results.
          // Remove result classes when showing recommended, set to no-results.
          $productsColumn.removeClass('has-results').addClass('no-results');
          $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');

          WebloSearchEngine.showRecommended(
            $recommendedProducts,
            $recommendedCategories,
            $productsTitle,
            $categoriesTitle
          );
          return;
        }

        // Hide recommended, show search results containers.
        WebloSearchEngine.showSearchResults(
          $recommendedProducts,
          $recommendedCategories,
          $productsTitle,
          $categoriesTitle
        );
        $resultsContainer.show();
        $categoriesContainer.show();

        // Show loading indicator, hide clear button.
        $loading.show();
        $clearButton.hide();

        // Debounce the search.
        const timer = setTimeout(function () {
          WebloSearchEngine.performSearch(
            searchTerm,
            $loading,
            $resultsContainer,
            $categoriesContainer,
            $noResults,
            instanceId
          );
        }, webloSearchEngine.debounceTime);
        $input.data('debounce-timer', timer);
      });

      // Handle Enter key - redirect to search page.
      $(document).on('keypress', '.weblo-search-input', function (e) {
        if (e.which === 13 || e.keyCode === 13) {
          // Enter key
          e.preventDefault();
          const $input = $(this);
          const searchTerm = $input.val().trim();

          if (searchTerm.length > 0) {
            // Redirect to shop page with search parameters.
            const shopUrl = webloSearchEngine.shopUrl || '/sklep/';
            const searchUrl = shopUrl + '?s=' + encodeURIComponent(searchTerm) + '&post_type=product';
            window.location.href = searchUrl;
          }
        }
      });
    },

    /**
     * Bind close buttons.
     */
    bindCloseButtons: function () {
      const $header = $('#header');
      const $body = $('body');

      // Clear input button - use event delegation.
      $(document).on('click', '.weblo-search-clear-input', function (e) {
        e.preventDefault();
        const $button = $(this);
        const instanceId = WebloSearchEngine.getInstanceId($button);

        // Find input directly by instance ID (works for separated elements).
        const $input = $('#' + instanceId + '-input');

        if ($input.length) {
          $input.val('').trigger('input');
          $input.focus();
          $button.hide();

          // Reset to recommended view.
          const $recommendedProducts = $('#' + instanceId + '-recommended-products');
          const $recommendedCategories = $('#' + instanceId + '-recommended-categories');
          const $productsTitle = $('#' + instanceId + '-products-title');
          const $categoriesTitle = $('#' + instanceId + '-categories-title');
          const $resultsContainer = $('#' + instanceId + '-results');
          const $categoriesResults = $('#' + instanceId + '-categories-results');
          const $noResults = $('#' + instanceId + '-no-results');

          // Hide search results and show recommended.
          if ($resultsContainer.length) {
            $resultsContainer.hide();
          }
          if ($categoriesResults.length) {
            $categoriesResults.hide();
          }
          if ($noResults.length) {
            $noResults.hide();
          }

          // Show recommended products and categories.
          if (
            $recommendedProducts.length &&
            $recommendedCategories.length &&
            $productsTitle.length &&
            $categoriesTitle.length
          ) {
            WebloSearchEngine.showRecommended(
              $recommendedProducts,
              $recommendedCategories,
              $productsTitle,
              $categoriesTitle
            );
          }

          // Update classes.
          let $productsColumn = $('.weblo-search-column.weblo-search-products[data-instance-id="' + instanceId + '"]');
          if (!$productsColumn.length) {
            $productsColumn = $resultsContainer.closest('.weblo-search-column.weblo-search-products');
          }
          const $body = $('body');

          if ($productsColumn.length) {
            $productsColumn.removeClass('has-results').addClass('no-results');
          }
          $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');
        }
      });

      // Close container button - use event delegation.
      $(document).on('click', '.weblo-search-close-container', function (e) {
        e.preventDefault();
        const $button = $(this);
        const $container = WebloSearchEngine.getContainer($button);
        $container.addClass('weblo-search-hidden');
        $header.removeClass('search_engine_open');
        $body.removeClass('search_engine_active');
      });
    },

    /**
     * Show recommended products and categories.
     */
    showRecommended: function ($recommendedProducts, $recommendedCategories, $productsTitle, $categoriesTitle) {
      $recommendedProducts.show();
      $recommendedCategories.show();

      // Update titles.
      $productsTitle.text($productsTitle.data('recommended-text') || 'Recommended Products');
      $categoriesTitle.text($categoriesTitle.data('recommended-text') || 'Recommended Categories');
    },

    /**
     * Show search results mode.
     */
    showSearchResults: function ($recommendedProducts, $recommendedCategories, $productsTitle, $categoriesTitle) {
      $recommendedProducts.hide();
      $recommendedCategories.hide();

      // Update titles.
      $productsTitle.text($productsTitle.data('search-text') || 'Search Results');
      $categoriesTitle.text($categoriesTitle.data('search-text') || 'Categories');
    },

    /**
     * Perform AJAX search.
     */
    performSearch: function (term, $loading, $resultsContainer, $categoriesContainer, $noResults, instanceId) {
      // Find clear button by instance ID (works for separated elements).
      const $clearButton = $('#' + instanceId + '-clear-input');

      $.ajax({
        url: webloSearchEngine.ajaxurl,
        type: 'POST',
        data: {
          action: 'weblo_search',
          nonce: webloSearchEngine.nonce,
          term: term,
        },
        success: function (response) {
          $loading.hide();
          if ($clearButton.length) {
            $clearButton.show();
          }

          if (response.success && response.data) {
            const data = response.data;

            // Get references to elements by instance ID directly (works for separated elements).
            const $recommendedProducts = $('#' + instanceId + '-recommended-products');
            const $recommendedCategories = $('#' + instanceId + '-recommended-categories');
            const $productsTitle = $('#' + instanceId + '-products-title');
            const $categoriesTitle = $('#' + instanceId + '-categories-title');

            // Get products column container.
            let $productsColumn = $resultsContainer.closest('.weblo-search-column.weblo-search-products');
            if (!$productsColumn.length) {
              // If not found by closest, try to find by instance ID.
              $productsColumn = $('.weblo-search-column.weblo-search-products[data-instance-id="' + instanceId + '"]');
            }
            const $body = $('body');

            if (data.products && data.products.length > 0) {
              // We have results - show search results mode.
              WebloSearchEngine.showSearchResults(
                $recommendedProducts,
                $recommendedCategories,
                $productsTitle,
                $categoriesTitle
              );
              $resultsContainer.show();

              // Add class for has results.
              $productsColumn.removeClass('no-results').addClass('has-results');
              $body.removeClass('weblo-search-no-results').addClass('weblo-search-has-results');

              WebloSearchEngine.renderProducts(data.products, $resultsContainer);
              $noResults.hide();
            } else {
              // No products found - show recommended again.
              // Add class for no results.
              $productsColumn.removeClass('has-results').addClass('no-results');
              $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');

              WebloSearchEngine.showNoResults(term, $noResults, instanceId);
              return;
            }

            if (data.categories && Object.keys(data.categories).length > 0) {
              $categoriesContainer.show();
              WebloSearchEngine.renderCategories(data.categories, $categoriesContainer);
            } else {
              $categoriesContainer.empty();
            }
          } else {
            $loading.hide();
            $clearButton.show();
          }
        },
        error: function () {
          $loading.hide();
          $clearButton.show();
          console.error('Search error occurred');
        },
      });
    },

    /**
     * Render products.
     */
    renderProducts: function (productIds, $container) {
      $container.empty();

      // Get products column container and body.
      let $productsColumn = $container.closest('.weblo-search-column.weblo-search-products');
      if (!$productsColumn.length) {
        // Try to find by data-instance-id.
        const instanceId = $container.attr('id')?.replace('-results', '') || '';
        if (instanceId) {
          $productsColumn = $('.weblo-search-column.weblo-search-products[data-instance-id="' + instanceId + '"]');
        }
      }
      const $body = $('body');

      // Load products via AJAX.
      $.ajax({
        url: webloSearchEngine.ajaxurl,
        type: 'POST',
        data: {
          action: 'weblo_get_products_html',
          nonce: webloSearchEngine.nonce,
          product_ids: productIds,
        },
        success: function (response) {
          if (response.success && response.data && response.data.html) {
            $container.html(response.data.html);

            // Ensure has-results class is set after rendering.
            if (productIds && productIds.length > 0) {
              $productsColumn.removeClass('no-results').addClass('has-results');
              $body.removeClass('weblo-search-no-results').addClass('weblo-search-has-results');
            } else {
              $productsColumn.removeClass('has-results').addClass('no-results');
              $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');
            }
          }
        },
        error: function () {
          console.error('Error loading products');
          // On error, mark as no results.
          $productsColumn.removeClass('has-results').addClass('no-results');
          $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');
        },
      });
    },

    /**
     * Render categories.
     */
    renderCategories: function (categories, $container) {
      $container.empty();

      let html = '';

      for (const categoryId in categories) {
        if (categories.hasOwnProperty(categoryId)) {
          const category = categories[categoryId];
          html += WebloSearchEngine.renderCategoryItem(category);
        }
      }

      $container.html(html);
    },

    /**
     * Render single category item with hierarchy.
     */
    renderCategoryItem: function (category) {
      const categoryUrl = category.url || WebloSearchEngine.getCategoryUrl(category.slug);

      let html = '<div class="search-category search-category-parent">';
      html +=
        '<a href="' +
        WebloSearchEngine.escapeHtml(categoryUrl) +
        '">' +
        WebloSearchEngine.escapeHtml(category.name) +
        '</a>';
      html += '</div>';

      // Render children.
      if (category.children && Object.keys(category.children).length > 0) {
        for (const childId in category.children) {
          if (category.children.hasOwnProperty(childId)) {
            const child = category.children[childId];
            const childUrl = child.url || WebloSearchEngine.getCategoryUrl(child.slug);
            html += '<div class="search-category search-category-child"> ';
            html +=
              '<a href="' +
              WebloSearchEngine.escapeHtml(childUrl) +
              '"><span>—</span>' +
              WebloSearchEngine.escapeHtml(child.name) +
              '</a>';
            html += '</div>';
          }
        }
      }

      return html;
    },

    /**
     * Get category URL (fallback).
     */
    getCategoryUrl: function (slug) {
      // Fallback method if URL is not provided in AJAX response.
      if (typeof webloSearchEngine.categoryBase !== 'undefined') {
        return webloSearchEngine.categoryBase.replace('%product_cat%', slug);
      }
      // Fallback to standard WooCommerce structure.
      return '/product-category/' + slug + '/';
    },

    /**
     * Show no results message.
     */
    showNoResults: function (term, $noResultsContainer, instanceId) {
      const message = webloSearchEngine.noResultsText.replace('%s', WebloSearchEngine.escapeHtml(term));
      $noResultsContainer.html('<p>' + message + '</p>').show();

      // Get products column container and add no-results class.
      let $productsColumn = $noResultsContainer.closest('.weblo-search-column.weblo-search-products');
      if (!$productsColumn.length) {
        // If not found by closest, try to find by instance ID.
        $productsColumn = $('.weblo-search-column.weblo-search-products[data-instance-id="' + instanceId + '"]');
      }
      const $body = $('body');

      if ($productsColumn.length) {
        $productsColumn.removeClass('has-results').addClass('no-results');
      }
      $body.removeClass('weblo-search-has-results').addClass('weblo-search-no-results');

      // Show recommended products and categories again when no results.
      // Find elements by instance ID directly (works for separated elements).
      const $recommendedProducts = $('#' + instanceId + '-recommended-products');
      const $recommendedCategories = $('#' + instanceId + '-recommended-categories');
      const $productsTitle = $('#' + instanceId + '-products-title');
      const $categoriesTitle = $('#' + instanceId + '-categories-title');

      WebloSearchEngine.showRecommended($recommendedProducts, $recommendedCategories, $productsTitle, $categoriesTitle);
    },

    /**
     * Escape HTML.
     */
    escapeHtml: function (text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      };
      return text.replace(/[&<>"']/g, function (m) {
        return map[m];
      });
    },
  };

  /**
   * Initialize on document ready.
   */
  $(document).ready(function () {
    WebloSearchEngine.init();

    // Set initial state - no results (before any search).
    const $body = $('body');
    if (!$body.hasClass('weblo-search-has-results') && !$body.hasClass('weblo-search-no-results')) {
      $body.addClass('weblo-search-no-results');
    }
  });
})(jQuery);
