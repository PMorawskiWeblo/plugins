jQuery(document).ready(function ($) {
  var $availabilityForm = $('#availability-info');
  var $variationIdInput = $('#variation_id');
  var $notifyMeButton = $('#notify_me');

  // Funkcja sprawdzająca dostępność wariantu
  function checkVariationAvailability(variationId) {
    if (typeof notifyAvailabilityData === 'undefined') {
      // Dla produktów simple - formularz jest już pokazany/ukryty przez PHP
      return;
    }

    if (variationId && notifyAvailabilityData[variationId]) {
      var variation = notifyAvailabilityData[variationId];
      var isUnavailable = !variation.in_stock && !variation.on_backorder;

      if (isUnavailable) {
        $availabilityForm.show();
        $variationIdInput.val(variationId);
      } else {
        $availabilityForm.hide();
        $variationIdInput.val('');
      }
    } else {
      // Jeśli wariant nie został wybrany lub nie istnieje w danych
      $availabilityForm.hide();
      $variationIdInput.val('');
    }
  }

  // Nasłuchuj zmian wariantu (WooCommerce event)
  $('form.variations_form').on('found_variation', function (event, variation) {
    var variationId = variation.variation_id;
    checkVariationAvailability(variationId);
  });

  // Nasłuchuj resetowania wariantu
  $('form.variations_form').on('reset_data', function () {
    // Sprawdź domyślny wariant jeśli istnieje
    if (typeof notifyDefaultVariationId !== 'undefined' && notifyDefaultVariationId > 0) {
      checkVariationAvailability(notifyDefaultVariationId);
    } else {
      $availabilityForm.hide();
      $variationIdInput.val('');
    }
  });

  // Nasłuchuj zmian w inputach wariantu (dodatkowa obsługa)
  $('form.variations_form').on('change', 'select, input[type="radio"]', function () {
    // WooCommerce automatycznie wywoła found_variation, ale dodajemy opóźnienie na wypadek
    setTimeout(function () {
      var $variationInput = $('input[name="variation_id"]');
      if ($variationInput.length && $variationInput.val()) {
        var variationId = parseInt($variationInput.val());
        if (variationId) {
          checkVariationAvailability(variationId);
        }
      }
    }, 100);
  });

  // Sprawdź domyślny wariant przy załadowaniu strony (z opóźnieniem, aby WooCommerce zdążył załadować)
  setTimeout(function () {
    if (typeof notifyDefaultVariationId !== 'undefined' && notifyDefaultVariationId > 0) {
      // Sprawdź czy WooCommerce już wybrał wariant
      var $variationInput = $('input[name="variation_id"]');
      if ($variationInput.length && $variationInput.val()) {
        var variationId = parseInt($variationInput.val());
        if (variationId) {
          checkVariationAvailability(variationId);
        } else {
          checkVariationAvailability(notifyDefaultVariationId);
        }
      } else {
        checkVariationAvailability(notifyDefaultVariationId);
      }
    }
  }, 500);

  // Obsługa kliknięcia przycisku powiadomienia
  $notifyMeButton.on('click', function (e) {
    e.preventDefault();
    var email = $('#availability_email').val().trim();
    var initialProductId = $('input[name="prod_id"]').val();
    var variationId = $variationIdInput.val();

    // Użyj variation_id jeśli jest dostępny, w przeciwnym razie użyj product_id
    var productIdToSend = variationId ? variationId : initialProductId;

    if (!email) {
      $('#notification_response').text(notify_availability_i18n.email_required);
      return;
    }

    // Wyślij ajax
    $.ajax({
      type: 'POST',
      url: admin_ajax_url.ajax_url,
      data: {
        action: 'notify_me',
        product_id: productIdToSend,
        email: email,
        name: email, // Używamy email jako name zgodnie z kodem PHP
      },
      success: function (response) {
        $('#availability_email').val('');
        $('#notification_response').text(response);
      },
      error: function () {
        $('#notification_response').text(notify_availability_i18n.error_occurred);
      },
    });
  });
});
