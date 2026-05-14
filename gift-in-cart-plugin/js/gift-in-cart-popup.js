jQuery(document).ready(function ($) {
  $('#gift-in-cart-wrapper').hide();

  function handleAddToCart($element) {
    if ($element.hasClass('add-gift')) {
      return;
    }

    var product_id, quantity, variation_id;
    var $button, $form;

    if ($element.is('form')) {
      $form = $element;
      $button = $form.find('button[type="submit"]');
      // Sprawdź najpierw input[name="product_id"], potem input[name="add-to-cart"], na końcu wartość przycisku
      product_id =
        $form.find('input[name="product_id"]').val() || $form.find('input[name="add-to-cart"]').val() || $button.val();
      // Dla produktów z wariantami użyj variation_id
      variation_id = $form.find('input[name="variation_id"]').val();
      quantity = $form.find('input[name="quantity"]').val() || 1;
    } else if ($element.hasClass('single_add_to_cart_button')) {
      $button = $element;
      $form = $button.closest('form');
      // Sprawdź najpierw input[name="product_id"], potem input[name="add-to-cart"], na końcu wartość przycisku
      product_id =
        $form.find('input[name="product_id"]').val() || $form.find('input[name="add-to-cart"]').val() || $button.val();
      // Dla produktów z wariantami użyj variation_id
      variation_id = $form.find('input[name="variation_id"]').val();
      quantity = $form.find('input[name="quantity"]').val() || 1;
    } else {
      $button = $element;
      $form = $button.closest('div');
      product_id = $button.data('product_id') || $button.data('product-id');
      variation_id = $button.data('variation_id') || $button.data('variation-id');
      quantity = $button.data('quantity') || 1;
    }

    // Konwersja product_id na liczbę
    product_id = parseInt(product_id);
    if (variation_id) {
      variation_id = parseInt(variation_id);
    }
    quantity = parseInt(quantity) || 1;

    if (!product_id || isNaN(product_id) || product_id <= 0) {
      console.error('Nie znaleziono product_id lub jest nieprawidłowy:', product_id);
      console.error('Form:', $form);
      console.error('Button:', $button);
      console.error('Element:', $element);
      return;
    }

    $form.addClass('add_to_cart_in_progress');
    $button.append('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    // Pobierz atrybuty wariantu z formularza
    var variation = {};
    if ($form.length) {
      $form.find('select[name^="attribute_"], input[name^="attribute_"]').each(function () {
        var attrName = $(this).attr('name');
        var attrValue = $(this).val();
        if (attrName && attrValue) {
          variation[attrName] = attrValue;
        }
      });
    }

    var ajaxData = {
      action: 'new_ajax_add_to_cart',
      product_id: product_id,
      quantity: quantity,
    };

    // Dodaj variation_id i variation jeśli istnieją
    if (variation_id && variation_id !== '0' && variation_id !== '') {
      ajaxData.variation_id = variation_id;
      if (Object.keys(variation).length > 0) {
        ajaxData.variation = variation;
      }
    }

    $.ajax({
      type: 'POST',
      url: ajax_object.ajax_url,
      data: ajaxData,
      success: function (response) {
        if (response.success) {
          if (ajax_object.is_cart) {
            $("[name='update_cart']").prop('disabled', false);
            $("[name='update_cart']").trigger('click');

            setTimeout(function () {
              if (typeof updateNotices === 'function') {
                updateNotices();
              }
            }, 500);
          } else {
            if (response.data && response.data.show) {
              //   console.log('Show popup');

              $('#gift-in-cart-popup').html(response.data.html);
              $('.gift-in-cart-wrapper').show();
              $('body').addClass('gift-in-cart-popup-overlay');

              // Inicjalizuj Splide dla produktów w popupie
              if (typeof Splide !== 'undefined' && $('#gift-in-cart-products-splide').length) {
                var splide = new Splide('#gift-in-cart-products-splide', {
                  fixedWidth: '327px',
                  gap: '24px',
                  perMove: 1,
                  arrows: false,
                  pagination: false,
                  loop: true,
                  // rewind: false,
                });
                splide.mount();
              }

              setTimeout(function () {
                $('#gift-in-cart-go-to-cart').focus();
                // console.log('focus');
              }, 500);
            }
            if (response.data && response.data.fragments) {
              $.each(response.data.fragments, function (key, value) {
                $(key).replaceWith(value);
              });
            }
            // Aktualizuj licznik koszyka jeśli jest dostępny
            if (response.data && response.data.counter !== undefined) {
              var cartCount = response.data.counter;
              $('.cart-counter').text(cartCount);
              $('#mini-cart-count').text(cartCount);
            }
          }
        } else {
          var errorMessage =
            response.data && response.data.message ? response.data.message : 'Nie udało się dodać produktu do koszyka';
          console.error('Nie udało się dodać produktu do koszyka:', errorMessage);
          console.error('Response:', response);
          console.error('AjaxData:', ajaxData);

          // Wyświetl komunikat błędu użytkownikowi
          // Usuń HTML entities z komunikatu (zachowaj podstawowe formatowanie)
          var cleanMessage = errorMessage.replace(/&mdash;/g, '—').replace(/&nbsp;/g, ' ');

          // Sprawdź czy istnieje kontener na powiadomienia WooCommerce
          var $noticesWrapper = $('.woocommerce-notices-wrapper');
          if (!$noticesWrapper.length) {
            // Jeśli nie ma kontenera, utwórz go przed formularzem dodawania do koszyka
            var $form = $('form.cart');
            if ($form.length) {
              $noticesWrapper = $('<div class="woocommerce-notices-wrapper"></div>');
              $form.before($noticesWrapper);
            }
          }

          if ($noticesWrapper.length) {
            // Wyświetl komunikat w kontenerze WooCommerce
            $noticesWrapper.html('<div class="woocommerce-error" role="alert">' + cleanMessage + '</div>');
            $noticesWrapper.show();

            // Przewiń do góry strony, aby pokazać komunikat
            $('html, body').animate(
              {
                scrollTop: $noticesWrapper.offset().top - 100,
              },
              500
            );
          } else {
            // Jeśli nie ma kontenera, użyj alertu
            alert(cleanMessage);
          }
        }
      },
      error: function (xhr, status, error) {
        console.error('Błąd Ajax:', error);
        console.error('Status:', status);
        console.error('Response:', xhr.responseText);
        console.error('AjaxData:', ajaxData);
      },
      complete: function () {
        $form.removeClass('add_to_cart_in_progress');
        $button.find('.spinner-border').remove();
      },
    });
  }

  function addAddToCartListeners() {
    $('form.cart')
      .off('submit')
      .on('submit', function (event) {
        event.preventDefault();
        handleAddToCart($(this));
      });
    $('.single_add_to_cart_button')
      .off('click')
      .on('click', function (event) {
        event.preventDefault();
        handleAddToCart($(this));
      });
    $('div[data-action="add-to-cart"], .ajax_add_to_cart')
      .off('click')
      .on('click', function (e) {
        if ($(this).hasClass('add-gift')) {
          return;
        }
        e.preventDefault();
        handleAddToCart($(this));
      });
    $('#gift-in-cart-continue-shopping, #gift-in-cart-popup-close')
      .off('click')
      .on('click', function (e) {
        e.preventDefault();
        $('.gift-in-cart-wrapper').hide();
        $('body').removeClass('gift-in-cart-popup-overlay');
      });
    $('.gift-in-cart-wrapper')
      .off('click')
      .on('click', function (e) {
        if (e.target !== this) return;
        e.preventDefault();
        $(this).hide();
        $('body').removeClass('gift-in-cart-popup-overlay');
      });
    $('#gift-in-cart-popup')
      .off('click')
      .on('click', function (e) {});
  }

  addAddToCartListeners();
  $(document).on('ajaxComplete', addAddToCartListeners);
});
