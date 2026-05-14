jQuery(document).ready(function ($) {
  // Sprawdź czy użytkownik jest zalogowany i czy wishlistData jest dostępne
  if (typeof wishlistData === 'undefined' || !wishlistData || !wishlistData.isLoggedIn) {
    return;
  }

  const svgActive =
    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M2 9.49955C2.00002 8.38675 2.33759 7.30014 2.96813 6.38322C3.59867 5.4663 4.49252 4.76221 5.53161 4.36395C6.5707 3.96569 7.70616 3.892 8.78801 4.1526C9.86987 4.4132 10.8472 4.99583 11.591 5.82355C11.6434 5.87957 11.7067 5.92422 11.7771 5.95475C11.8474 5.98528 11.9233 6.00104 12 6.00104C12.0767 6.00104 12.1526 5.98528 12.2229 5.95475C12.2933 5.92422 12.3566 5.87957 12.409 5.82355C13.1504 4.99045 14.128 4.40292 15.2116 4.13915C16.2952 3.87539 17.4335 3.9479 18.4749 4.34704C19.5163 4.74617 20.4114 5.453 21.0411 6.37345C21.6708 7.2939 22.0053 8.38431 22 9.49955C22 11.7896 20.5 13.4996 19 14.9996L13.508 20.3126C13.3217 20.5266 13.0919 20.6985 12.834 20.8169C12.5762 20.9352 12.296 20.9974 12.0123 20.9992C11.7285 21.001 11.4476 20.9424 11.1883 20.8273C10.9289 20.7122 10.697 20.5432 10.508 20.3316L5 14.9996C3.5 13.4996 2 11.7996 2 9.49955Z" fill="#A2693C"/></svg>';
  const svgInactive =
    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 9.50004C2.00002 8.38724 2.33759 7.30062 2.96813 6.3837C3.59867 5.46678 4.49252 4.7627 5.53161 4.36444C6.5707 3.96618 7.70616 3.89248 8.78801 4.15308C9.86987 4.41368 10.8472 4.99632 11.591 5.82404C11.6434 5.88005 11.7067 5.92471 11.7771 5.95524C11.8474 5.98577 11.9233 6.00152 12 6.00152C12.0767 6.00152 12.1526 5.98577 12.2229 5.95524C12.2933 5.92471 12.3566 5.88005 12.409 5.82404C13.1504 4.99094 14.128 4.40341 15.2116 4.13964C16.2952 3.87588 17.4335 3.94839 18.4749 4.34752C19.5163 4.74666 20.4114 5.45349 21.0411 6.37394C21.6708 7.29439 22.0053 8.3848 22 9.50004C22 11.79 20.5 13.5 19 15L13.508 20.313C13.3217 20.527 13.0919 20.699 12.834 20.8173C12.5762 20.9357 12.296 20.9979 12.0123 20.9997C11.7285 21.0015 11.4476 20.9429 11.1883 20.8278C10.9289 20.7127 10.697 20.5437 10.508 20.332L5 15C3.5 13.5 2 11.8 2 9.50004Z" stroke="#6D6059" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  $('.wishlist-button').on('click', function (e) {
    e.preventDefault();

    const $button = $(this);
    const productId = $button.data('product-id');

    $.ajax({
      url: wishlistData.ajaxurl,
      type: 'POST',
      data: {
        action: 'toggle_wishlist',
        product_id: productId,
        nonce: wishlistData.nonce,
      },
      success: function (response) {
        if (response.success) {
          if (response.data.action === 'added') {
            $button.addClass('active');
            $button.html(svgActive);
          } else {
            $button.removeClass('active');
            $button.html(svgInactive);

            // Jeśli kliknięto serduszko wewnątrz listy życzeń, usuń element z DOM i zaktualizuj widok
            const $productElement = $button.closest('.wishlist-product');
            if ($productElement.length) {
              $productElement.fadeOut(300, function () {
                $(this).remove();
                if ($('.wishlist-product').length === 0) {
                  $('.wishlist-products').html('<p>' + wishlistData.emptyText + '</p>');
                  $('.wishlist-bulk-actions').remove();
                }
              });
            }
          }

          // Aktualizacja licznika
          const $counter = $('.wishlist-counter .count');
          if (response.data && response.data.count !== undefined) {
            const count = response.data.count;
            if (count > 0) {
              if ($counter.length) {
                $counter.text(count).show();
              } else {
                $('.wishlist-counter').append('<span class="count">' + count + '</span>');
              }
            } else {
              $counter.hide();
            }
          }
        }
      },
      error: function (xhr, status, error) {
        console.error('Error toggling wishlist:', error);
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          console.error('Message:', xhr.responseJSON.data.message);
        }
      },
    });
  });

  $('.remove-from-wishlist').on('click', function (e) {
    e.preventDefault();
    const productId = $(this).data('product-id');
    const productElement = $(this).closest('.wishlist-product');
    const button = $(this);

    $.ajax({
      url: wishlistData.ajaxurl,
      type: 'POST',
      data: {
        action: 'remove_from_wishlist',
        product_id: productId,
        nonce: wishlistData.nonce,
      },
      beforeSend: function () {
        button.prop('disabled', true);
      },
      success: function (response) {
        if (response.success) {
          productElement.fadeOut(300, function () {
            $(this).remove();
            // Sprawdź czy lista jest pusta
            if ($('.wishlist-product').length === 0) {
              $('.wishlist-products').html('<p>' + wishlistData.emptyText + '</p>');
              $('.wishlist-bulk-actions').remove();
            }
          });
          // Aktualizuj licznik
          if (response.data && response.data.count !== undefined) {
            const count = response.data.count;
            if (count > 0) {
              $('.wishlist-counter .count').text(count).show();
            } else {
              $('.wishlist-counter .count').hide();
            }
          } else if (response.count !== undefined) {
            if (response.count > 0) {
              $('.wishlist-counter .count').text(response.count).show();
            } else {
              $('.wishlist-counter .count').hide();
            }
          }
        }
      },
      error: function (xhr, status, error) {
        console.error('Error removing from wishlist:', error);
        button.prop('disabled', false);
      },
      complete: function () {
        button.prop('disabled', false);
      },
    });
  });

  // Funkcja do aktualizacji licznika koszyka
  function updateCartCounter(count) {
    $('.cart-counter').text(count);
  }

  // Nasłuchiwanie na zdarzenie odświeżenia fragmentów WooCommerce
  $(document.body).on('added_to_cart', function (event, fragments, cart_hash, button) {
    // Sprawdź czy użytkownik jest zalogowany (wishlist jest tylko dla zalogowanych)
    if (typeof wishlistData === 'undefined' || !wishlistData || !wishlistData.isLoggedIn) {
      return;
    }

    if (fragments && fragments['div.widget_shopping_cart_content']) {
      // Pobierz aktualną ilość produktów z koszyka
      $.ajax({
        url: wishlistData.ajaxurl,
        type: 'POST',
        data: {
          action: 'get_cart_count',
          nonce: wishlistData.nonce,
        },
        success: function (response) {
          if (response.success) {
            updateCartCounter(response.count);
          }
        },
        error: function (xhr, status, error) {
          // Cicho ignoruj błędy dla niezalogowanych użytkowników
          if (xhr.status !== 403) {
            console.error('Error getting cart count:', error);
          }
        },
      });
    }
  });

  // Obsługa dodawania pojedynczego produktu do koszyka
  $('.wishlist-product-actions .add-to-cart').on('click', function (e) {
    e.preventDefault();
    const productId = $(this).data('product-id');
    const button = $(this);

    $.ajax({
      url: wishlistData.ajaxurl,
      type: 'POST',
      data: {
        action: 'add_to_cart_from_wishlist',
        product_id: productId,
        nonce: wishlistData.nonce,
      },
      beforeSend: function () {
        button.prop('disabled', true);
      },
      success: function (response) {
        if (response.success) {
          $(document.body).trigger('wc_fragment_refresh');
          button.text(wishlistData.addedText).addClass('added');
          if (response.cart_count) {
            updateCartCounter(response.cart_count);
          }
        }
      },
      complete: function () {
        button.prop('disabled', false);
      },
    });
  });

  // Obsługa dodawania wszystkich produktów do koszyka
  $('.add-all-to-cart').on('click', function (e) {
    e.preventDefault();
    const button = $(this);
    const products = $('.wishlist-product');
    let processed = 0;

    button.prop('disabled', true);

    products.each(function () {
      const productId = $(this).find('.add-to-cart').data('product-id');
      $.ajax({
        url: wishlistData.ajaxurl,
        type: 'POST',
        data: {
          action: 'add_to_cart_from_wishlist',
          product_id: productId,
          nonce: wishlistData.nonce,
        },
        success: function (response) {
          if (response.success) {
            processed++;
            $(document.body).trigger('wc_fragment_refresh');
            if (processed === products.length) {
              button.text(wishlistData.allProductsAddedText).addClass('added');
            }
            if (response.cart_count) {
              updateCartCounter(response.cart_count);
            }
          }
        },
      });
    });
  });
});
