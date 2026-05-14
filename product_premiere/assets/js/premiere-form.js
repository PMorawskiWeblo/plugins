jQuery(document).ready(function ($) {
  // Funkcja do aktualizacji licznika
  function updateCountdown($countdown) {
    const premiereDate = new Date($countdown.data('premiere-date')).getTime();
    const now = new Date().getTime();
    const distance = premiereDate - now;

    // Obliczanie czasu
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Aktualizacja elementów
    $countdown.find('.ppwc-days').text(String(days).padStart(2, '0'));
    $countdown.find('.ppwc-hours').text(String(hours).padStart(2, '0'));
    $countdown.find('.ppwc-minutes').text(String(minutes).padStart(2, '0'));
    $countdown.find('.ppwc-seconds').text(String(seconds).padStart(2, '0'));

    if (distance < 0) {
      clearInterval($countdown.data('interval'));
      $countdown.find('.ppwc-days, .ppwc-hours, .ppwc-minutes, .ppwc-seconds').text('00');
      location.reload();
    }
  }

  // Inicjalizacja liczników
  $('.ppwc-countdown').each(function () {
    const $countdown = $(this);
    // Pierwsze uruchomienie
    updateCountdown($countdown);
    // Aktualizacja co sekundę
    const interval = setInterval(() => updateCountdown($countdown), 1000);
    $countdown.data('interval', interval);
  });

  // Obsługa formularza
  $('#ppwc-premiere-signup-form').on('submit', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $msg = $form.find('.ppwc-premiere-message');
    var product_id = $form.data('product');
    var name = $form.find('[name="ppwc_name"]').val();
    var email = $form.find('[name="ppwc_email"]').val();

    // Zbierz wszystkie zaznaczone zgody
    var consents = {};
    $form.find('input[name^="ppwc_consent["]').each(function () {
      var consentId = $(this)
        .attr('name')
        .match(/ppwc_consent\[(.*?)\]/)[1];
      consents[consentId] = $(this).is(':checked') ? 1 : 0;
    });

    // Sprawdź wymagane zgody
    var hasAllRequired = true;
    $form.find('input[name^="ppwc_consent["][required]').each(function () {
      if (!$(this).is(':checked')) {
        hasAllRequired = false;
        return false; // break the loop
      }
    });

    if (!hasAllRequired) {
      $msg.text('Proszę zaakceptować wszystkie wymagane zgody').css('color', 'red').show();
      return;
    }

    $.post(
      ppwcPremiereAjax.ajaxUrl,
      {
        action: 'ppwc_save_premiere_signup',
        nonce: ppwcPremiereAjax.nonce,
        product_id: product_id,
        name: name,
        email: email,
        consent: consents,
      },
      function (response) {
        if (response.success) {
          $msg.text(ppwcPremiereAjax.successMsg).css('color', 'green').show();
          $form[0].reset();
        } else {
          $msg
            .text(response.data || ppwcPremiereAjax.errorMsg)
            .css('color', 'red')
            .show();
        }
      }
    ).fail(function (jqXHR, textStatus, errorThrown) {
      $msg
        .text('Wystąpił błąd podczas wysyłania formularza: ' + errorThrown)
        .css('color', 'red')
        .show();
    });
  });
});
