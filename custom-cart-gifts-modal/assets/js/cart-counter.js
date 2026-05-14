jQuery(document).ready(function ($) {

    var counter_class = product_counter_params.counter_class;
    var triggerElements = ['.quantity-input .minus', '.quantity-input .plus', '.ajax_add_to_cart', '.add_to_cart_button', '.single_add_to_cart_button', '.cart-item-remove', '.add-gift-to-cart'];

    set_cart_count();

    $(document).on('click', $(triggerElements), function (event) {
        clearTimeout(ajaxTimeout);
        var ajaxTimeout = setTimeout(function () {
            set_cart_count();
        }, 2000);
    });

    function set_cart_count() {

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_product_cart_count',
                nonce: ajax_object.nonce
            },
            success: function (response) {
                if (response.success) {
                    if (counter_class) {
                        $('.' + counter_class).text(response.data.count);
                    }
                    if(response.data.total){
                        $('.cart-total-on-cross-sell-page span').html(response.data.total);
                        $('.header_cart_totals .cart-contents-total').html(response.data.total);
                    }
                    notificationHandle();
                }
            },
            error: function () {
                console.error('Błąd podczas pobierania liczby produktów w koszyku');
            }
        });

      

    }

    $(document).ajaxComplete(function () {
        if ($('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-cart')) {
            jQuery('html, body').stop();
        }
    });

    function notificationHandle() {

        var messages = $('.woocommerce-notices-wrapper > *');

        $.each(messages, function (indexInArray, item) {
            setTimeout(function () {
                $(item).remove();
            }, (indexInArray * 300) + 2000);
        });

    }
});