jQuery(document).ready(function ($) {
  function addCartListeners() {
    // Użyj delegacji zdarzeń na poziomie dokumentu
    $(document)
      .off('click', '.add-gift')
      .on('click', '.add-gift', function () {
        if (!$(this).hasClass('selected_gift')) {
          var giftid = $(this).data('giftid');
          // console.log(giftid)
          var data = {
            action: 'nc_add_to_cart_gift',
            giftid: giftid,
          };

          // console.log(data);

          $.ajax({
            type: 'POST',
            url: cart_refresh_params.ajax_url,
            data: data,
            success: function (response) {
              if (response.success) {
                $("[name='update_cart']").prop('disabled', false);
                $("[name='update_cart']").trigger('click');

                setTimeout(function () {
                  var $noticesWrapper = $('.custom-woocommerce-notices-wrapper .woocommerce-notices-wrapper');
                  var giftMessage =
                    typeof couponMessages !== 'undefined' && couponMessages.giftAdded
                      ? couponMessages.giftAdded
                      : 'WE ADDED A GIFT TO YOUR ORDER';

                  if ($noticesWrapper.length) {
                    $noticesWrapper
                      .stop(true, true)
                      .show()
                      .html('<div class="woocommerce-message" role="alert">' + giftMessage + '</div>');
                    setTimeout(function () {
                      $noticesWrapper.fadeOut(300, function () {
                        $noticesWrapper.empty();
                      });
                    }, 2000);
                  }
                }, 500);
              }
            },
            error: function (xhr, status, error) {
              console.error(error);
              $("[name='update_cart']").trigger('click');
              $("[name='update_cart']").prop('disabled', false);
            },
          });
        }
      });

    // Użyj delegacji zdarzeń na poziomie dokumentu
    $(document)
      .off('click', '.remove-gift')
      .on('click', '.remove-gift', function () {
        var cartkey = $(this).data('itemkey');
        var data = {
          action: 'nc_remove_from_cart_gift',
          cartkey: cartkey,
        };

        // console.log(data);

        $.ajax({
          type: 'POST',
          url: cart_refresh_params.ajax_url,
          data: data,
          success: function (response) {
            if (response.success) {
              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');

              setTimeout(function () {
                if (typeof updateNotices === 'function') {
                  updateNotices();
                }
              }, 500);
            }
          },
          error: function (xhr, status, error) {
            console.error(error);
            $("[name='update_cart']").trigger('click');
            $("[name='update_cart']").prop('disabled', false);
          },
        });
      });
  }

  addCartListeners(); // Call the function once on document ready

  $(document).on('ajaxComplete', addCartListeners); // Re-attach event listeners after every AJAX completion

  // Event WooCommerce po aktualizacji koszyka
  $(document.body).on('updated_wc_div', function () {
    addCartListeners();
  });

  // Event WooCommerce po aktualizacji totali koszyka
  $(document.body).on('updated_cart_totals', function () {
    addCartListeners();
  });
});
