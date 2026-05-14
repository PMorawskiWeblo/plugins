jQuery(document).ready(function ($) {

    var updateInterval;


    
    $(document).on('click', '.quantity-input .minus, .quantity-input .plus', function (event) {

        event.preventDefault();

        clearTimeout(updateInterval);
        $('button[name="update_cart"]').attr('disabled', false);

        var cart_item_key = $(this).data('cart-item');
        var quantity = $(this).closest('.custom-cart-item').find('input.quantity').val();
        var quantityInput = $(this).closest('.custom-cart-item').find('input.quantity');
        var min_quantity = quantityInput.attr('min');
        var max_quantity = quantityInput.attr('max');

        if ($(this).hasClass('plus')) {
            quantity++;
            if (quantity > max_quantity) {
                quantity = max_quantity;
            }

            if (quantity == max_quantity) {
                $(this).attr('disabled', true);
            } else {
                $(this).closest('.quantity-input').find('.minus').attr('disabled', false);
            }
        } else {
            quantity--;
            if (quantity < min_quantity) {
                quantity = min_quantity;
            }

            if (quantity == min_quantity) {
                $(this).attr('disabled', true);
            } else {
                $(this).closest('.quantity-input').find('.plus').attr('disabled', false);
            }
        }

        $(this).closest('.custom-cart-item').find('input.quantity').val(quantity);

        updateInterval = setTimeout(function () {

            $('.quantity-input .minus, .quantity-input .plus').css('pointer-events', 'none');

            $.ajax({
                url: custom_cart_params.ajax_url,
                method: 'POST',
                data: {
                    action: 'update_cart_quantity',
                    cart_item_key: cart_item_key,
                    quantity: quantity,
                    nonce: custom_cart_params.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('button[name="update_cart"]').click();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('Wystąpił błąd.');
                },
                complete: function () {
                    $('.quantity-input .minus, .quantity-input .plus').css('pointer-events', 'auto');
                }
            });

        }, 500);

    });

    $(document).on('click', '.add-gift-to-cart', function (event) {
        event.preventDefault();

        var product_id = $(this).data('product-id');
        var level_index = $(this).data('level');

        $.ajax({
            url: custom_cart_params.ajax_url,
            method: 'POST',
            data: {
                action: 'add_gift_to_cart',
                product_id: product_id,
                level_index: level_index,
                nonce: custom_cart_params.nonce
            },
            success: function (response) {
                if (response.success) {

                    $('button[name="update_cart"]').removeAttr('disabled');
                    $('button[name="update_cart"]').click();
                } else {
                    location.reload();
                }
            }
        });
    });

    $(document).on('click', '.gift-view.remove-gift-from-cart', function (event) {

        event.preventDefault();

        var product_id = $(this).data('product-id');
        var level_index = $(this).data('level');

        $.ajax({
            url: custom_cart_params.ajax_url,
            method: 'POST',
            data: {
                action: 'remove_gift_from_cart',
                product_id: product_id,
                level_index: level_index,
                nonce: custom_cart_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('button[name="update_cart"]').removeAttr('disabled');
                    $('button[name="update_cart"]').click();
                } else {
                    location.reload();
                }
            } 
        });
    });


    
    var giftsLevelItemsConfig = {
        type: 'slider',
        perPage: 3,
        perMove: 1,
        pagination: false,
        arrows: false,
        gap:10,
        breakpoints: {
            992: {
                perPage: 2,
                fixedWidth: '40%',
            },
            576: {
                perPage: 1,
                gap: 10,
                fixedWidth: '55%',
            },
        }
    };

    function initGiftsSliders() {
        
        var giftsLevelItemsSplide = $('.gifts-level-items-splide');
        giftsLevelItemsSplide.each(function () {
            if ($(this)[0].splide) {
                $(this)[0].splide.destroy();
            }
            var slides = $(this).find('.splide__slide').length;
            var config = {...giftsLevelItemsConfig};
            if (slides > 3) {
                config.arrows = true;
            }
            new Splide($(this)[0], config).mount();
        });
    }

    initGiftsSliders();

    $(document).on('updated_cart_totals', function() {
        initGiftsSliders();
    $('.free-shipping-notice').load(window.location + ' .free-shipping-notice > *');
    });

    $(document).on('click', '.gifts-level-title', function () {

        $(this).closest('.gifts-level').find('.gifts-level-items').toggleClass('show');
        $(this).closest('.gifts-level').find('.show-level').toggleClass('active');
    });

    showMobileTotals();
    
    $(document).on('scroll', function () {
        showMobileTotals();
    });

    function showMobileTotals() {
        var cartTotals = $('.cart_totals');
        if (cartTotals.length) {
            var cartTotalsOffset = cartTotals.offset().top;
            var cartTotalsHeight = cartTotals.outerHeight();
            var windowHeight = $(window).height();
            var scrollPosition = $(this).scrollTop();

            if (scrollPosition + windowHeight < cartTotalsOffset || scrollPosition > cartTotalsOffset + cartTotalsHeight) {                  
                $('.mobile-totals-wrap').addClass('show');
                $('.wrap_fixed_phone').css( 'bottom', '-100px');
                $('#smartsupp-widget-container').addClass('d-none');
                $('#smartsupp-widget-container').removeClass('flex');
            } else {
                $('.mobile-totals-wrap').removeClass('show');
                $('.wrap_fixed_phone').css( 'bottom', '24px');
                $('#smartsupp-widget-container').removeClass('d-none');
                $('#smartsupp-widget-container').addClass('flex');
            }
        };
    }

    $(document).on('click', '.mobile-coupon-btn', function () {

        $(this).closest('.mobile-coupon').find('.actions').toggleClass('show');
    });
});