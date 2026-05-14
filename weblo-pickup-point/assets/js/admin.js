/**
 * Admin JavaScript for Weblo Pick-up Point
 */
(function ($) {
  'use strict';

  var initialized = false;

  /**
   * Initialize pickup points table
   */
  function initPickupPointsTable() {
    var $wrapper = $('.weblo-pickup-points-table-wrapper');

    if ($wrapper.length === 0) {
      return false;
    }

    // Check if already initialized for this wrapper
    if ($wrapper.data('weblo-initialized')) {
      return true;
    }

    var $hiddenInput = $wrapper.find('input[type="hidden"]');
    var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
    var $addButton = $wrapper.find('.weblo-add-point');

    if ($tbody.length === 0 || $addButton.length === 0) {
      return false;
    }

    // Mark as initialized
    $wrapper.data('weblo-initialized', true);

    // Initialize sortable
    if ($tbody.length > 0 && !$tbody.hasClass('ui-sortable')) {
      $tbody.sortable({
        handle: '.sort-handle',
        axis: 'y',
        update: function () {
          updateHiddenInput($wrapper);
        },
      });
    }

    // Add new point - direct event handler
    $addButton.off('click.weblo').on('click.weblo', function (e) {
      e.preventDefault();
      e.stopPropagation();
      addNewRow($wrapper);
      return false;
    });

    // Remove point - use delegation on tbody
    $tbody.off('click', '.weblo-remove-point').on('click', '.weblo-remove-point', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).closest('tr').remove();
      updateHiddenInput($wrapper);
      checkEmptyState($wrapper);
      return false;
    });

    // Update hidden input on change
    $tbody
      .off('input', '.weblo-point-name, .weblo-point-price')
      .on('input', '.weblo-point-name, .weblo-point-price', function () {
        updateHiddenInput($wrapper);
      });

    // Initial update
    updateHiddenInput($wrapper);

    return true;
  }

  /**
   * Add new row to table
   */
  function addNewRow($wrapper) {
    var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
    var index = $tbody.find('tr.weblo-pickup-point-row').length;
    var pointNamePlaceholder =
      typeof webloPickupPointAdmin !== 'undefined' ? webloPickupPointAdmin.pointNamePlaceholder : 'Point name';
    var removeButton = typeof webloPickupPointAdmin !== 'undefined' ? webloPickupPointAdmin.removeButton : 'Remove';

    var newRow =
      '<tr class="weblo-pickup-point-row" data-index="' +
      index +
      '">' +
      '<td class="sort-handle"><span class="dashicons dashicons-menu-alt"></span></td>' +
      '<td><input type="text" class="weblo-point-name" value="" placeholder="' +
      pointNamePlaceholder +
      '" /></td>' +
      '<td><input type="number" class="weblo-point-price" step="0.01" min="0" value="0" placeholder="0.00" /></td>' +
      '<td><button type="button" class="button weblo-remove-point">' +
      removeButton +
      '</button></td>' +
      '</tr>';

    $tbody.find('.weblo-no-points').remove();
    $tbody.append(newRow);

    // Reinitialize sortable
    if ($tbody.hasClass('ui-sortable')) {
      $tbody.sortable('destroy');
    }
    $tbody.sortable({
      handle: '.sort-handle',
      axis: 'y',
      update: function () {
        updateHiddenInput($wrapper);
      },
    });

    updateHiddenInput($wrapper);
  }

  /**
   * Update hidden input with JSON data
   */
  function updateHiddenInput($wrapper) {
    var $hiddenInput = $wrapper.find('input[type="hidden"]');
    var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
    var points = [];

    $tbody.find('tr.weblo-pickup-point-row').each(function () {
      var $row = $(this);
      var name = $row.find('.weblo-point-name').val().trim();
      var price = parseFloat($row.find('.weblo-point-price').val()) || 0;

      if (name) {
        points.push({
          name: name,
          price: price,
        });
      }
    });

    $hiddenInput.val(JSON.stringify(points));
  }

  /**
   * Check if table is empty and show message
   */
  function checkEmptyState($wrapper) {
    var $tbody = $wrapper.find('.weblo-pickup-points-tbody');
    var noPointsMessage =
      typeof webloPickupPointAdmin !== 'undefined'
        ? webloPickupPointAdmin.noPointsMessage
        : 'No pickup points added yet.';

    if ($tbody.find('tr.weblo-pickup-point-row').length === 0) {
      $tbody.append(
        '<tr class="weblo-no-points"><td colspan="4" style="text-align: center; padding: 20px;">' +
          noPointsMessage +
          '</td></tr>'
      );
    }
  }

  /**
   * Try to initialize with multiple methods
   */
  function tryInit() {
    if (initPickupPointsTable()) {
      initialized = true;
    }
  }

  // Initialize on document ready
  $(document).ready(function () {
    tryInit();
  });

  // Reinitialize when WooCommerce modal is opened
  $(document).on('wc_backbone_modal_loaded', function () {
    setTimeout(function () {
      tryInit();
    }, 200);
  });

  // Also listen for when modal content is loaded
  $(document).on('wc_backbone_modal_before_remove', function () {
    // Reset initialization flag when modal closes
    $('.weblo-pickup-points-table-wrapper').removeData('weblo-initialized');
  });

  // Use MutationObserver but with debouncing
  var initTimeout;
  if (typeof MutationObserver !== 'undefined') {
    var observer = new MutationObserver(function (mutations) {
      clearTimeout(initTimeout);
      initTimeout = setTimeout(function () {
        if ($('.weblo-pickup-points-table-wrapper').length > 0) {
          tryInit();
        }
      }, 300);
    });

    $(document).ready(function () {
      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    });
  }

  // Fallback: try initialization periodically for first few seconds
  var attempts = 0;
  var maxAttempts = 20;
  var initInterval = setInterval(function () {
    attempts++;
    if (initPickupPointsTable() || attempts >= maxAttempts) {
      clearInterval(initInterval);
    }
  }, 500);
})(jQuery);
