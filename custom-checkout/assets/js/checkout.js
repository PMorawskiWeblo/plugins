jQuery(function ($) {
  var validationEnabled = false;

  $('.go_to_delivery_and_payment, .go_to_delivery_and_payment_step').on('click', function () {
    validationEnabled = true;
    var $button = $('.go_to_delivery_and_payment');
    var $form = $('form.checkout.woocommerce-checkout');
    var isValid = true;
    var messages = window.WebloCheckoutI18n || {};

    var $allFields = $('.customer_details').find('input, select, textarea');
    var $fields = $allFields.filter(function () {
      var $el = $(this);
      var fieldId = $el.attr('id') || $el.attr('name') || '';
      var fieldValue = ($el.val() || '').trim();
      var fieldNameAttr = $el.attr('name') || '';

      var required =
        $el.prop('required') ||
        $el.attr('aria-required') === 'true' ||
        $el.closest('.form-row').hasClass('validate-required');

      var hidden = !$el.is(':visible') || $el.closest('.form-row').is(':hidden');

      var isPhoneOrPostcode =
        fieldId.includes('phone') ||
        fieldId.includes('postcode') ||
        fieldNameAttr.includes('phone') ||
        fieldNameAttr.includes('postcode');

      return !hidden && (required || (fieldValue && isPhoneOrPostcode));
    });

    var invalidFields = [];

    $fields.each(function () {
      var el = this;
      var $el = $(el);
      var fieldId = $el.attr('id') || $el.attr('name') || 'unknown';
      var fieldValue = $el.val() || '';
      var fieldName = $el.attr('name') || '';

      var fieldValid = true;
      var validationMessage = '';
      var trimmedValue = fieldValue.toString().trim();

      var isRequired =
        $el.prop('required') ||
        $el.attr('aria-required') === 'true' ||
        $el.closest('.form-row').hasClass('validate-required');

      if (isRequired && !trimmedValue) {
        fieldValid = false;
        validationMessage = messages.field_required || 'This field is required.';
      }

      if (
        fieldValid &&
        trimmedValue &&
        (fieldId === 'billing_phone' ||
          fieldName === 'billing_phone' ||
          fieldId === 'shipping_phone' ||
          fieldName === 'shipping_phone')
      ) {
        var digitsOnly = trimmedValue.replace(/\D/g, '');
        if (digitsOnly.length < 9) {
          fieldValid = false;
          validationMessage = messages.phone_min_length || 'Phone number must contain at least 9 digits.';
        }
      }

      if (
        fieldValid &&
        trimmedValue &&
        (fieldId === 'billing_postcode' ||
          fieldName === 'billing_postcode' ||
          fieldId === 'shipping_postcode' ||
          fieldName === 'shipping_postcode')
      ) {
        var postcodeClean = trimmedValue.replace(/\s/g, '');
        var postcodePattern = /^(\d{2}-\d{3}|\d{5})$/;

        if (!postcodePattern.test(postcodeClean)) {
          fieldValid = false;
          validationMessage = messages.postcode_format || 'Postcode must be in format XX-XXX or XXXXX.';
        }
      }

      if (fieldValid && trimmedValue && typeof el.checkValidity === 'function') {
        var html5Valid = el.checkValidity();
        if (!html5Valid) {
          fieldValid = false;
          validationMessage = el.validationMessage || validationMessage || messages.field_invalid || 'Field is invalid';
        }
      }

      if (!fieldValid) {
        isValid = false;
        invalidFields.push({
          id: fieldId,
          element: el,
          message: validationMessage || messages.field_required || 'This field is required.',
        });

        var $row = $el.closest('.form-row');
        $row.addClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
        $row.append('<span class="weblo-field-error">' + validationMessage + '</span>');

        if (typeof el.reportValidity === 'function') {
          el.reportValidity();
        }
        $(el).focus();
        return false;
      } else {
        var $row = $el.closest('.form-row');
        $row.removeClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
      }
    });

    if (!isValid) {
      if (invalidFields.length > 0 && invalidFields[0].element) {
        var $firstError = $(invalidFields[0].element);
        $('html, body').animate(
          {
            scrollTop: $firstError.offset().top - 100,
          },
          300
        );
      }

      return false;
    }

    if ($form.length) {
      $form.addClass('step_one_validate');
    }

    $button.hide();

    $('.customer_details').addClass('customer_details_inactive');
    $('.shippings_payments_wrapper').addClass('shippings_payments_wrapper_active');
    $('.shippings_payments_wrapper').removeClass('shippings_payments_wrapper_inactive');
    $('.order_review_wrapper').removeClass('order_review_wrapper_hidden');
    $('.order_review_wrapper').addClass('order_review_wrapper_active');
    $('.go_to_delivery_and_payment_step').addClass('step_active');
    $('.go_to_buyer_details_step').removeClass('step_active');
  });

  $('.go_to_buyer_details_step').on('click', function () {
    validationEnabled = false;
    $('.go_to_buyer_details_step').addClass('step_active');
    $('.go_to_delivery_and_payment_step').removeClass('step_active');
    $('.go_to_delivery_and_payment').show();
    $('.customer_details').removeClass('customer_details_inactive');
    $('.shippings_payments_wrapper').addClass('shippings_payments_wrapper_active');
    $('.shippings_payments_wrapper').removeClass('shippings_payments_wrapper_inactive');
    $('.order_review_wrapper').addClass('order_review_wrapper_hidden');
    $('.order_review_wrapper').removeClass('order_review_wrapper_active');
    $('.customer_details .form-row').removeClass('woocommerce-invalid');
    $('.customer_details .weblo-field-error').remove();

    $('.shippings_payments_wrapper').removeClass('shippings_payments_wrapper_active');
    $('.shippings_payments_wrapper').addClass('shippings_payments_wrapper_inactive');
  });

  function updateShippingRequired() {
    var shipToDifferent = $('#ship-to-different-address-checkbox').is(':checked');
    var requiredShippingIds = [
      'shipping_first_name',
      'shipping_last_name',
      'shipping_address_1',
      'shipping_postcode',
      'shipping_city',
    ];

    requiredShippingIds.forEach(function (id) {
      var $field = $('#' + id);
      if (!$field.length) {
        return;
      }

      var $row = $field.closest('.form-row');

      if (shipToDifferent) {
        $field.prop('required', true).attr('aria-required', 'true');
        $row.addClass('validate-required');
      } else {
        $field.prop('required', false).removeAttr('aria-required');
      }
    });
  }

  function checkPaymentMethodCheckbox() {
    var $selectedPayment = $('input[name="payment_method"]:checked');

    if ($selectedPayment.length) {
      var paymentMethodId = $selectedPayment.val();
      var $paymentBox = $('.payment_box.payment_method_' + paymentMethodId);

      if ($paymentBox.length) {
        var $checkbox = $paymentBox.find('input[type="checkbox"]');

        if ($checkbox.length && !$checkbox.prop('checked')) {
          $checkbox.prop('checked', true);
        }
      }
    }
  }

  $(document.body).on('change', 'input[name="payment_method"]', function () {
    checkPaymentMethodCheckbox();
  });

  $(document.body).on('updated_checkout', function () {
    checkPaymentMethodCheckbox();
    updateShippingRequired();
  });

  checkPaymentMethodCheckbox();
  updateShippingRequired();

  function toggleOrderNotesField() {
    var $checkbox = $('#weblo_has_order_note');
    var $orderCommentsField = $('#order_comments_field');

    if ($checkbox.length && $orderCommentsField.length) {
      if ($checkbox.prop('checked')) {
        $orderCommentsField.show();
      } else {
        $orderCommentsField.hide();
      }
    }
  }

  $(document.body).on('change', '#weblo_has_order_note', function () {
    toggleOrderNotesField();
  });

  function toggleVatInvoiceFields() {
    var $checkbox = $('#weblo_vat_invoice');
    var $checkboxWrapper = $('.weblo-vat-invoice-checkbox-wrapper');
    var $vatFieldsWrapper = $('.weblo-vat-invoice-fields-wrapper');
    var $vatFields = $('.weblo-vat-invoice-field');

    if ($checkbox.length && $vatFields.length) {
      if ($checkbox.prop('checked')) {
        $checkboxWrapper.addClass('weblo-vat-invoice-checkbox-wrapper_active');
        $vatFields.show();
        $vatFields.find('input, select, textarea').prop('required', true);
        $vatFields.find('label .required').remove();
        $vatFields.find('label').each(function () {
          if ($(this).find('.required').length === 0) {
            $(this).append(' <abbr class="required" title="required">*</abbr>');
          }
        });
      } else {
        $checkboxWrapper.removeClass('weblo-vat-invoice-checkbox-wrapper_active');
        $vatFields.hide();
        $vatFields.find('input, select, textarea').prop('required', false);
        $vatFields.find('label .required').remove();
      }
    }
  }

  $(document.body).on('change', '#weblo_vat_invoice', function () {
    toggleVatInvoiceFields();
  });

  $(document.body).on('blur', '.weblo-vat-invoice-field input', function () {
    var $field = $(this);
    var $wrapper = $field.closest('.form-row');
    var fieldName = $field.attr('name') || '';
    var fieldId = $field.attr('id') || '';
    var messages = window.WebloCheckoutI18n || {};

    if (fieldName.toLowerCase().includes('nip') || fieldId.toLowerCase().includes('nip')) {
      var value = $field.val().replace(/\D/g, '');

      if (value && value.length !== 10) {
        $wrapper.addClass('woocommerce-invalid');
        $wrapper.find('.woocommerce-error').remove();
        $wrapper.append(
          '<span class="woocommerce-error">' +
            (messages.nip_exact_digits || 'Tax Identification Number must be exactly 10 digits.') +
            '</span>'
        );
      } else {
        $wrapper.removeClass('woocommerce-invalid');
        $wrapper.find('.woocommerce-error').remove();
        if (value) {
          $field.val(value);
        }
      }
    }
  });

  $(document.body).on('updated_checkout', function () {
    toggleOrderNotesField();
    toggleVatInvoiceFields();
  });

  toggleOrderNotesField();

  $('.weblo-vat-invoice-field').hide();

  toggleVatInvoiceFields();

  $('.customer_details .form-row').removeClass('woocommerce-invalid');
  $('.customer_details .weblo-field-error').remove();

  $(document.body).on('change', '#ship-to-different-address-checkbox', function () {
    updateShippingRequired();
  });

  function logFieldValidation($field) {
    if (!validationEnabled) {
      return;
    }

    var fieldId = $field.attr('id') || $field.attr('name') || 'unknown';
    var fieldValue = $field.val() || '';
    var fieldName = $field.attr('name') || '';
    var trimmedValue = fieldValue.toString().trim();
    var messages = window.WebloCheckoutI18n || {};
    var isRequired =
      $field.prop('required') ||
      $field.attr('aria-required') === 'true' ||
      $field.closest('.form-row').hasClass('validate-required');
    var isVisible = $field.is(':visible') && !$field.closest('.form-row').is(':hidden');

    if (isRequired && isVisible) {
      var isValid = true;
      var validationMessage = '';

      if (!trimmedValue) {
        isValid = false;
        validationMessage = messages.field_required || 'This field is required.';
      }

      if (
        isValid &&
        trimmedValue &&
        (fieldId === 'billing_phone' ||
          fieldName === 'billing_phone' ||
          fieldId === 'shipping_phone' ||
          fieldName === 'shipping_phone')
      ) {
        var digitsOnly = trimmedValue.replace(/\D/g, '');
        if (digitsOnly.length < 9) {
          isValid = false;
          validationMessage = messages.phone_min_length || 'Phone number must contain at least 9 digits.';
        }
      }

      if (
        isValid &&
        trimmedValue &&
        (fieldId === 'billing_postcode' ||
          fieldName === 'billing_postcode' ||
          fieldId === 'shipping_postcode' ||
          fieldName === 'shipping_postcode')
      ) {
        var postcodeClean = trimmedValue.replace(/\s/g, '');
        var postcodePattern = /^(\d{2}-\d{3}|\d{5})$/;

        if (!postcodePattern.test(postcodeClean)) {
          isValid = false;
          validationMessage = messages.postcode_format || 'Postcode must be in format XX-XXX or XXXXX.';
        }
      }

      if (isValid && trimmedValue && typeof $field[0].checkValidity === 'function') {
        var html5Valid = $field[0].checkValidity();
        if (!html5Valid) {
          isValid = false;
          validationMessage =
            $field[0].validationMessage || validationMessage || messages.field_invalid || 'Field is invalid';
        }
      }

      var $row = $field.closest('.form-row');
      if (!isValid) {
        $row.addClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
        $row.append('<span class="weblo-field-error" >' + validationMessage + '</span>');
      } else {
        $row.removeClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
      }
    }
  }

  $(document.body).on(
    'input change blur',
    '.customer_details input, .customer_details select, .customer_details textarea',
    function () {
      logFieldValidation($(this));
    }
  );

  $(document.body).on(
    'blur',
    'input[name="billing_phone"], input[name="shipping_phone"], #billing_phone, #shipping_phone',
    function () {
      if (!validationEnabled) {
        return;
      }
      var $field = $(this);
      var fieldValue = ($field.val() || '').trim();
      var digitsOnly = fieldValue.replace(/\D/g, '');
      var $row = $field.closest('.form-row');
      var messages = window.WebloCheckoutI18n || {};

      if (fieldValue && digitsOnly.length < 9) {
        $row.addClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
        $row.append(
          '<span class="weblo-field-error" >' +
            (messages.phone_min_length || 'Phone number must contain at least 9 digits.') +
            '</span>'
        );
      } else if (fieldValue) {
        $row.removeClass('woocommerce-invalid');
        $row.find('.weblo-field-error').remove();
      }
    }
  );

  $(document.body).on(
    'blur',
    'input[name="billing_postcode"], input[name="shipping_postcode"], #billing_postcode, #shipping_postcode',
    function () {
      if (!validationEnabled) {
        return;
      }
      var $field = $(this);
      var fieldValue = ($field.val() || '').trim();
      var $row = $field.closest('.form-row');
      var messages = window.WebloCheckoutI18n || {};

      if (fieldValue) {
        var postcodeClean = fieldValue.replace(/\s/g, '');
        var postcodePattern = /^(\d{2}-\d{3}|\d{5})$/;

        if (!postcodePattern.test(postcodeClean)) {
          $row.addClass('woocommerce-invalid');
          $row.find('.weblo-field-error').remove();
          $row.append(
            '<span class="weblo-field-error" >' +
              (messages.postcode_format || 'Postcode must be in format XX-XXX or XXXXX.') +
              '</span>'
          );
        } else {
          $row.removeClass('woocommerce-invalid');
          $row.find('.weblo-field-error').remove();
        }
      }
    }
  );

  $(document.body).on('updated_checkout', function () {
    if (validationEnabled) {
      $('.customer_details')
        .find('input, select, textarea')
        .filter(function () {
          var $el = $(this);
          var required =
            $el.prop('required') ||
            $el.attr('aria-required') === 'true' ||
            $el.closest('.form-row').hasClass('validate-required');
          var hidden = !$el.is(':visible') || $el.closest('.form-row').is(':hidden');
          return required && !hidden;
        })
        .each(function () {
          logFieldValidation($(this));
        });
    }
    updateShippingDetailsDisplay();
  });

  function updateShippingDetailsDisplay() {
    var $detailsContent = $('.checkout_section_shipping_details_content');
    if (!$detailsContent.length) {
      return;
    }

    var shipToDifferent = $('#ship-to-different-address-checkbox').is(':checked');
    var address1, postcode, city;

    if (shipToDifferent) {
      // Użyj danych z shipping
      address1 = $('#shipping_address_1').val() || '';
      postcode = $('#shipping_postcode').val() || '';
      city = $('#shipping_city').val() || '';
    } else {
      // Użyj danych z billing
      address1 = $('#billing_address_1').val() || '';
      postcode = $('#billing_postcode').val() || '';
      city = $('#billing_city').val() || '';
    }

    // Formatuj adres
    var messages = window.WebloCheckoutI18n || {};
    var streetLabel = messages.street_label || 'st.';
    var formattedAddress = '';
    if (address1) {
      formattedAddress += streetLabel + ' ' + address1;
    }
    if (postcode || city) {
      if (formattedAddress) {
        formattedAddress += '<br>';
      }
      var cityLine = '';
      if (postcode) {
        cityLine += postcode;
      }
      if (city) {
        if (cityLine) {
          cityLine += ' ';
        }
        cityLine += city;
      }
      formattedAddress += cityLine;
    }

    $detailsContent.html(formattedAddress);
  }

  // Aktualizuj przy zmianie pól adresowych
  $(document.body).on(
    'change input blur',
    '#shipping_address_1, #shipping_postcode, #shipping_city, #billing_address_1, #billing_postcode, #billing_city',
    function () {
      updateShippingDetailsDisplay();
    }
  );

  // Aktualizuj przy zmianie checkboxa "ship to different address"
  $(document.body).on('change', '#ship-to-different-address-checkbox', function () {
    updateShippingDetailsDisplay();
  });

  // Inicjalizacja przy załadowaniu strony
  updateShippingDetailsDisplay();
});
