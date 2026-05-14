jQuery(document).ready(function ($) {
  var $rightColCart = $('.right-col-cart');
  var stickyClass = 'right-col-cart-sticky';

  function reloadCouponForm(savedMessage) {
    var $couponForm = $('.custom_cart_coupon_form');
    var $message = $('.coupon-code-message');

    if (!savedMessage) {
      savedMessage = $message.html();
    }

    if ($couponForm.length) {
      $couponForm.load(window.location.href + ' .custom_cart_coupon_form>*', function () {
        if (savedMessage && savedMessage.trim() !== '') {
          $('.coupon-code-message').html(savedMessage).show();
        }
        addCartListeners();
      });
    }
  }

  function checkOffset() {
    var windowHeight = $(window).height();
    var sectionHeight = $('.right-col-cart').height();
    var offset = windowHeight - sectionHeight + 600;
    $(window).scroll(function () {
      var windowTop = $(window).scrollTop();

      if (windowTop > offset) {
        $rightColCart.removeClass(stickyClass);
      } else {
        $rightColCart.addClass(stickyClass);
      }
    });
  }
  checkOffset();

  function updateCartCounters() {
    var cartCount = $('#cart-counter').text();
    if (cartCount && !isNaN(cartCount)) {
      $('.cart-counter').text(cartCount);
      $('#mini-cart-count').text(cartCount);
    } else {
      var currentCount = $('.cart-counter').first().text();
      if (currentCount && !isNaN(currentCount)) {
        $('.cart-counter').text(currentCount);
        $('#mini-cart-count').text(currentCount);
      }
    }
  }

  function addCartListeners() {
    $('.splide-gift').each(function () {
      var splide = new Splide(this, {
        perPage: 3,
        perMove: 1,
        focus: 'center',
        gap: '12px',

        breakpoints: {
          768: {
            perPage: 2.3,
            rewind: false,
          },
          2000: {
            perPage: 3,
            perMove: 1,
            rewind: false,
          },
        },

        pagination: false,
        arrows: false,
        loop: false,
        rewind: false,
      });

      splide.mount();
    });

    $('#cross-products-splide').each(function () {
      var splideCross = new Splide(this, {
        perPage: 4,
        perMove: 1,
        gap: '20px',

        breakpoints: {
          768: {
            perPage: 2.1,
            rewind: false,
            gap: '12px',
          },
          2000: {
            perPage: 4,
            perMove: 1,
            rewind: false,
          },
        },

        pagination: false,
        arrows: false,
        loop: false,
        rewind: false,
      });

      splideCross.mount();
    });

    $(document)
      .off(
        'click',
        '.woocommerce-cart-form__cart-item span.quantity-plus, .woocommerce-cart-form__cart-item span.quantity-minus'
      )
      .on(
        'click',
        '.woocommerce-cart-form__cart-item span.quantity-plus, .woocommerce-cart-form__cart-item span.quantity-minus',
        function (e) {
          e.preventDefault();
          var qty = $(this).parent().find('input');
          var val = parseFloat(qty.val());
          var max = parseFloat(qty.attr('max'));
          var min = parseFloat(qty.attr('min'));
          var step = parseFloat(qty.attr('step'));

          if ($(this).is('.quantity-plus')) {
            if (max && max <= val) {
              qty.val(max);
            } else {
              qty.val(val + step);
            }
          } else {
            if (min && min >= val) {
              qty.val(min);
            } else if (val > 1) {
              qty.val(val - step);
            }
          }

          qty.trigger('change');
        }
      );

    $(document)
      .off('change', '.woocommerce-cart-form__cart-item .quantity')
      .on('change', '.woocommerce-cart-form__cart-item .quantity', function () {
        var data = {
          action: 'nc_update_cart_quantity',
          product_key: $(this).attr('name'),
          quantity: $(this).val(),
          security: cart_refresh_params.nonce,
        };

        $.ajax({
          type: 'POST',
          url: cart_refresh_params.ajax_url,
          data: data,
          success: function (response) {
            if (response.success && response.data && response.data.cart_contents_count !== undefined) {
              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');
              var cartCount = response.data.cart_contents_count;
              $('#mini-cart-count').text(cartCount);
              $('.cart-counter').text(cartCount);

              if (cartCount === 0) {
                setTimeout(function () {
                  window.location.reload();
                }, 500);
                return;
              }

              reloadCouponForm(savedCouponMessage);

              setTimeout(function () {
                updateNotices();
              }, 500);
            }
          },
          complete: function () {
            setTimeout(function () {
              updateCartCounters();
            }, 500);
          },
          error: function (xhr, status, error) {
            $("[name='update_cart']").trigger('click');
            $("[name='update_cart']").prop('disabled', false);
          },
        });
      });

    $(document)
      .off('click', '.add-sample-to-cart')
      .on('click', '.add-sample-to-cart', function (e) {
        e.preventDefault();
        var $button = $(this);
        var productId = $button.data('product-id');
        var sampleName = $button.data('sample-name');
        var samplePrice = $button.data('sample-price');
        var sampleSlogan = $button.data('sample-slogan');

        // Konwertuj productId na liczbę jeśli jest stringiem
        productId = parseInt(productId, 10);

        if (!productId || isNaN(productId) || productId <= 0) {
          console.error('Product ID is missing or invalid:', productId);
          alert('Błąd: Nieprawidłowe ID produktu');
          return;
        }

        $.ajax({
          type: 'POST',
          url: cart_refresh_params.ajax_url,
          data: {
            action: 'nc_add_to_cart_sample',
            product_id: productId,
            sample_name: sampleName,
            sample_price: samplePrice,
            sample_slogan: sampleSlogan,
          },
          success: function (response) {
            if (response.success) {
              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');

              if (response.data && response.data.fragments) {
                $.each(response.data.fragments, function (key, value) {
                  $(key).replaceWith(value);
                });
              }

              if (response.data && response.data.counter !== undefined) {
                var cartCount = response.data.counter;
                $('.cart-counter').text(cartCount);
                $('#mini-cart-count').text(cartCount);
              }

              setTimeout(function () {
                if (typeof window.updateNotices === 'function') {
                  window.updateNotices();
                }
              }, 500);
            } else {
              var errorMessage =
                response.data && response.data.message
                  ? response.data.message
                  : 'Failed to add sample to cart';
              alert(errorMessage);
            }
          },
          error: function (xhr, status, error) {
            console.error('Error adding sample to cart:', error);
            alert('Error adding sample to cart');
          },
        });
      });

    $('.coupon-form button')
      .off('click')
      .on('click', function () {
        var couponCode = $(this).parent().find('input').val();
        var $message = $('.coupon-code-message');

        $message.removeClass('correct incorrect').empty();

        $.ajax({
          type: 'POST',
          url: cart_refresh_params.ajax_url,
          data: {
            action: 'nc_apply_coupon_code',
            coupon_code: couponCode,
          },
          success: function (response) {
            if (response.success) {
              var successMessage =
                '<div class="correct">' + (couponMessages.codeValid || 'The entered code is correct.') + '</div>';
              $message.html(successMessage).show();

              savedCouponMessage = successMessage;

              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');
              $('#coupon_code').val('');

              setTimeout(function () {
                updateNotices();
              }, 500);
            } else {
              var errorMessage =
                response.data && response.data.message
                  ? response.data.message
                  : couponMessages.codeInvalid || 'The entered code is incorrect.';

              var errorMessageHtml = '<div class="incorrect">' + errorMessage + '</div>';
              $message.html(errorMessageHtml).show();

              savedCouponMessage = errorMessageHtml;

              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');
            }
          },
          error: function (xhr, status, error) {
            var errorMessageHtml =
              '<div class="incorrect">' + (couponMessages.codeInvalid || 'The entered code is incorrect.') + '</div>';
            $message.html(errorMessageHtml).show();

            savedCouponMessage = errorMessageHtml;

            $("[name='update_cart']").trigger('click');
            $("[name='update_cart']").prop('disabled', false);
          },
        });
      });

    $('.cart-discount .delete-coupon')
      .off('click')
      .on('click', function () {
        var couponCode = $(this).data('coupon-code');

        savedCouponMessage = null;

        $('.coupon-code-message').empty().hide();

        $.ajax({
          type: 'POST',
          url: cart_refresh_params.ajax_url,
          data: {
            action: 'nc_delete_coupon_code',
            coupon_code: couponCode,
          },
          success: function (response) {
            if (response.success) {
              $("[name='update_cart']").prop('disabled', false);
              $("[name='update_cart']").trigger('click');

              setTimeout(function () {
                updateNotices();
              }, 500);
            }
          },
          error: function (xhr, status, error) {
            $("[name='update_cart']").trigger('click');
            $("[name='update_cart']").prop('disabled', false);
          },
        });
      });

    $('.giftlevel-name')
      .off('click')
      .on('click', function () {
        $(this).parent().toggleClass('hide');
      });

    $('.custom_cart_coupon_form_wrapper_button')
      .off('click')
      .on('click', function () {
        $('.coupon-inputs').toggleClass('coupon-inputs-hidden');
        $('.coupon-inputs').toggleClass('coupon-inputs-shown');
      });
  }

  addCartListeners();

  $(document).on('ajaxComplete', addCartListeners);

  $(document).on('click', '.product-remove a', function (e) {
    var $link = $(this);
    var href = $link.attr('href');

    var cartItemsCount = $('.woocommerce-cart-form__cart-item').not('.cart-item-gift').length;
    var currentCartCount = parseInt($('#cart-counter').text()) || 0;

    if (href && href.indexOf('remove_item') !== -1 && (cartItemsCount === 1 || currentCartCount === 1)) {
      e.preventDefault();
      window.location.href = href;
      return false;
    } else {
      setTimeout(function () {
        updateNotices();
      }, 500);
    }
  });

  $(document).on('click', '.woocommerce-remove-coupon', function (e) {
    e.preventDefault();

    var $link = $(this);
    var href = $link.attr('href');
    var couponCode = $link.data('coupon') || $link.attr('data-coupon');

    savedCouponMessage = null;

    $('.coupon-code-message').empty().hide();

    if (couponCode) {
      $.ajax({
        type: 'POST',
        url: cart_refresh_params.ajax_url,
        data: {
          action: 'nc_delete_coupon_code',
          coupon_code: couponCode,
        },
        success: function (response) {
          if (response.success) {
            $("[name='update_cart']").prop('disabled', false);
            $("[name='update_cart']").trigger('click');

            setTimeout(function () {
              updateNotices();
            }, 500);
          }
        },
        error: function (xhr, status, error) {
          window.location.href = href;
        },
      });
    } else {
      window.location.href = href;
    }

    return false;
  });

  function checkAndReloadIfEmpty() {
    setTimeout(function () {
      var cartCount = parseInt($('#cart-counter').text()) || 0;
      var cartItems = $('.woocommerce-cart-form__cart-item').not('.cart-item-gift').length;
      var cartContents = $('.woocommerce-cart-form__contents .woocommerce-cart-form__cart-item').not(
        '.cart-item-gift'
      ).length;

      if (cartCount === 0 || (cartItems === 0 && cartContents === 0)) {
        window.location.reload();
        return;
      }

      setTimeout(function () {
        cartCount = parseInt($('#cart-counter').text()) || 0;
        cartItems = $('.woocommerce-cart-form__cart-item').not('.cart-item-gift').length;
        cartContents = $('.woocommerce-cart-form__contents .woocommerce-cart-form__cart-item').not(
          '.cart-item-gift'
        ).length;

        if (cartCount === 0 || (cartItems === 0 && cartContents === 0)) {
          window.location.reload();
        }
      }, 800);
    }, 300);
  }

  var savedCouponMessage = null;

  window.updateNotices = function updateNotices() {
    var wcAjaxUrl = window.location.origin + window.location.pathname + '?wc-ajax=get_refreshed_fragments';

    $.ajax({
      type: 'POST',
      url: wcAjaxUrl,
      data: {
        time: Date.now(),
      },
      success: function (response) {
        var $noticesWrapper = $('.custom-woocommerce-notices-wrapper .woocommerce-notices-wrapper');
        var cartUpdatedMessage =
          typeof couponMessages !== 'undefined' && couponMessages.cartUpdated
            ? couponMessages.cartUpdated
            : 'CART UPDATED.';

        if (response && response.fragments && response.fragments['.custom-woocommerce-notices-wrapper']) {
          var $fragmentWrapper = $(response.fragments['.custom-woocommerce-notices-wrapper']);
          var $fragmentNotices = $fragmentWrapper.find('.woocommerce-notices-wrapper');

          if ($fragmentNotices.length) {
            var noticesHtml = $fragmentNotices.html().trim();
            if (
              noticesHtml !== '' &&
              (noticesHtml.indexOf('woocommerce-error') !== -1 || noticesHtml.indexOf('woocommerce-info') !== -1)
            ) {
              $('.custom-woocommerce-notices-wrapper').replaceWith(
                response.fragments['.custom-woocommerce-notices-wrapper']
              );
            } else {
              if ($noticesWrapper.length) {
                $noticesWrapper
                  .stop(true, true)
                  .show()
                  .html('<div class="woocommerce-message" role="alert">' + cartUpdatedMessage + '</div>');
                setTimeout(function () {
                  $noticesWrapper.fadeOut(300, function () {
                    $noticesWrapper.empty();
                  });
                }, 1000);
              }
            }
          } else {
            if ($noticesWrapper.length) {
              $noticesWrapper
                .stop(true, true)
                .show()
                .html('<div class="woocommerce-message" role="alert">' + cartUpdatedMessage + '</div>');
              setTimeout(function () {
                $noticesWrapper.fadeOut(300, function () {
                  $noticesWrapper.empty();
                });
              }, 1000);
            }
          }
        } else {
          if ($noticesWrapper.length) {
            $noticesWrapper
              .stop(true, true)
              .show()
              .html('<div class="woocommerce-message" role="alert">' + cartUpdatedMessage + '</div>');
            setTimeout(function () {
              $noticesWrapper.fadeOut(300, function () {
                $noticesWrapper.empty();
              });
            }, 1000);
          }
        }
      },
    });
  };

  $(document.body).on('updated_wc_div', function (event, fragments, cart_hash) {
    updateCartCounters();
    addCartListeners();

    if (fragments && fragments['.custom-woocommerce-notices-wrapper']) {
      var $noticesWrapper = $('.custom-woocommerce-notices-wrapper');
      if ($noticesWrapper.length) {
        $noticesWrapper.replaceWith(fragments['.custom-woocommerce-notices-wrapper']);
      }
    } else {
      setTimeout(function () {
        updateNotices();
      }, 200);
    }

    setTimeout(function () {
      updateNotices();
    }, 300);

    if (savedCouponMessage) {
      setTimeout(function () {
        $('.coupon-code-message').html(savedCouponMessage).show();
      }, 200);
    } else {
      $('.coupon-code-message').empty().hide();
    }

    checkAndReloadIfEmpty();
  });

  $(document.body).on('updated_cart_totals', function (event, fragments, cart_hash) {
    updateCartCounters();
    addCartListeners();

    if (fragments && fragments['.custom-woocommerce-notices-wrapper']) {
      var $noticesWrapper = $('.custom-woocommerce-notices-wrapper');
      if ($noticesWrapper.length) {
        $noticesWrapper.replaceWith(fragments['.custom-woocommerce-notices-wrapper']);
      }
    } else {
      setTimeout(function () {
        updateNotices();
      }, 200);
    }

    setTimeout(function () {
      updateNotices();
    }, 300);

    if (savedCouponMessage) {
      setTimeout(function () {
        $('.coupon-code-message').html(savedCouponMessage).show();
      }, 200);
    } else {
      $('.coupon-code-message').empty().hide();
    }

    checkAndReloadIfEmpty();
  });

  $(document).ajaxComplete(function (event, xhr, settings) {
    if (
      settings.url &&
      (settings.url.indexOf('wc-ajax') !== -1 ||
        settings.url.indexOf('remove_item') !== -1 ||
        settings.url.indexOf('nc_update_cart_quantity') !== -1 ||
        settings.url.indexOf('nc_remove_from_cart_gift') !== -1)
    ) {
      checkAndReloadIfEmpty();

      if (
        settings.url &&
        (settings.url.indexOf('update_cart') !== -1 ||
          settings.url.indexOf('nc_update_cart_quantity') !== -1 ||
          settings.url.indexOf('remove_item') !== -1 ||
          settings.url.indexOf('nc_remove_from_cart_gift') !== -1)
      ) {
        setTimeout(function () {
          updateNotices();
        }, 400);
      }
    }
  });

  $(document.body).on('removed_coupon', function () {
    reloadCouponForm();
  });
});
