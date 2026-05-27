/**
 * Admin JavaScript for Loyalty Program
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

(function ($) {
  'use strict';

  /**
   * SweetAlert2 Helper - Custom configuration
   */
  var SwalConfig = {
    // Default configuration
    defaultConfig: {
      color: '#000000',
      confirmButtonColor: '#b02e66',
      cancelButtonColor: '#aac096',
      denyButtonColor: '#aac096',
      buttonsStyling: true,
      customClass: {
        popup: 'loyalty-swal-popup',
        title: 'loyalty-swal-title',
        content: 'loyalty-swal-content',
        confirmButton: 'loyalty-swal-confirm',
        cancelButton: 'loyalty-swal-cancel'
      }
    },
    
    // Alert wrapper
    alert: function(message, title, icon) {
      return Swal.fire({
        title: title || '',
        text: message,
        icon: icon || 'info',
        confirmButtonText: 'OK',
        confirmButtonColor: '#b02e66',
        color: '#000000',
        buttonsStyling: true
      });
    },
    
    // Confirm wrapper
    confirm: function(message, title, icon) {
      return Swal.fire({
        title: title || '',
        text: message,
        icon: icon || 'question',
        showCancelButton: true,
        confirmButtonText: 'Tak',
        cancelButtonText: 'Anuluj',
        confirmButtonColor: '#b02e66',
        cancelButtonColor: '#aac096',
        color: '#000000',
        buttonsStyling: true
      });
    },
    
    // Success alert
    success: function(message, title) {
      return this.alert(message, title || 'Sukces', 'success');
    },
    
    // Error alert
    error: function(message, title) {
      return this.alert(message, title || 'Błąd', 'error');
    },
    
    // Warning alert
    warning: function(message, title) {
      return this.alert(message, title || 'Ostrzeżenie', 'warning');
    },
    
    // Info alert
    info: function(message, title) {
      return this.alert(message, title || 'Informacja', 'info');
    }
  };

  /**
   * Loyalty Program Admin Object
   */
  var LoyaltyProgramAdmin = {
    /**
     * Initialize
     */
    init: function () {
      this.bindEvents();
      this.initComponents();
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      // Handle form submissions with AJAX
      $('.loyalty-program-form').on('submit', this.handleFormSubmit);

      // Handle delete confirmations
      $('.loyalty-delete-item').on('click', this.handleDeleteConfirm);

      // Handle toggle switches
      $('.loyalty-toggle input').on('change', this.handleToggleChange);
    },

    /**
     * Initialize components
     */
    initComponents: function () {
      // Initialize tooltips if needed
      if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
      }

      // Initialize date pickers if needed
      if (typeof $.fn.datepicker === 'function') {
        $('.loyalty-datepicker').datepicker({
          dateFormat: 'yy-mm-dd',
        });
      }
    },

    /**
     * Handle form submit with AJAX
     */
    handleFormSubmit: function (e) {
      var $form = $(this);
      var $button = $form.find('button[type="submit"]');
      var originalText = $button.text();

      // Don't prevent default for now, let the form submit normally
      // We can add AJAX handling later if needed
    },

    /**
     * Handle delete confirmation
     */
    handleDeleteConfirm: function (e) {
      e.preventDefault();
      var $link = $(e.currentTarget);
      var originalHref = $link.attr('href');
      
      SwalConfig.confirm(loyaltyProgramAdmin.i18n.confirm_delete).then(function(result) {
        if (result.isConfirmed) {
          if (originalHref) {
            window.location.href = originalHref;
          }
        }
      });
      
      return false;
    },

    /**
     * Handle toggle change
     */
    handleToggleChange: function () {
      var $toggle = $(this);
      var isEnabled = $toggle.is(':checked');
      var targetSelector = $toggle.data('target');

      if (targetSelector) {
        var $target = $(targetSelector);
        if (isEnabled) {
          $target.removeClass('disabled');
          $target.find('input, select, textarea').prop('disabled', false);
        } else {
          $target.addClass('disabled');
          $target.find('input, select, textarea').prop('disabled', true);
        }
      }
    },

    /**
     * Show notification
     */
    showNotification: function (message, type) {
      type = type || 'success';

      var $notification = $('<div>')
        .addClass('notice notice-' + type + ' is-dismissible')
        .html('<p>' + message + '</p>');

      $('.wrap > h1').after($notification);

      // Auto dismiss after 5 seconds
      setTimeout(function () {
        $notification.fadeOut(function () {
          $(this).remove();
        });
      }, 5000);
    },

    /**
     * AJAX request helper
     */
    ajaxRequest: function (action, data, successCallback, errorCallback) {
      data = data || {};
      data.action = action;
      data.nonce = loyaltyProgramAdmin.nonce;

      $.ajax({
        url: loyaltyProgramAdmin.ajax_url,
        type: 'POST',
        data: data,
        success: function (response) {
          if (response.success && typeof successCallback === 'function') {
            successCallback(response.data);
          } else if (!response.success && typeof errorCallback === 'function') {
            errorCallback(response.data);
          }
        },
        error: function (xhr, status, error) {
          if (typeof errorCallback === 'function') {
            errorCallback({
              message: loyaltyProgramAdmin.i18n.error_occurred,
            });
          }
        },
      });
    },
  };

  /**
   * Document ready
   */
  $(document).ready(function () {
    if (typeof loyaltyProgramAdmin !== 'undefined') {
      LoyaltyProgramAdmin.init();
    }
  });
})(jQuery);
