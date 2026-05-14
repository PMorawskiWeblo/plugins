jQuery(document).ready(function ($) {


    $('.cross-sell-modal-close').click(function () {
        $('#cross-sell-modal').removeClass('show');
        $('body').removeClass('overflow-hidden');
    });

    $('.cross-sell-modal-continue-shopping').click(function () {
        $('#cross-sell-modal').removeClass('show');
        $('body').removeClass('overflow-hidden');
    });

    $('.cross-sell-modal-go-to-cart').click(function () {
        $('#cross-sell-modal').removeClass('show');
        $('body').removeClass('overflow-hidden');
    });


    $(document).on('click', '.ajax_add_to_cart , .add_to_cart_button:not(.product_type_variable) , .single_add_to_cart_button', function (event) {

        event.preventDefault();

        var noModal = $(this).data('no-modal');
        var button = $(this);

        $(this).addClass('loading');

        var isCrossSell = $(this).data('cross-sell-product-id');

        let product_id = '';
        let quantity = 1;
        let variation_id = '';
        let variations = {};

        product_id = $(this).data('product_id') ? $(this).data('product_id') : $(this).val();
        quantity = $(this).data('quantity') || $(this).closest('.cart').find('.quantity').find('input').val() || 1;

        variation_id = product_id ? '' : $(this).data('variation_id') || $('input[name="variation_id"]').val();
        levelIndex = $(this).data('level-index');

        if (variation_id) {

            product_id = product_id || $('input[name="product_id"]').val();

            let data = $(this).data();
            Object.keys(data).forEach(key => {
                if (key.startsWith('attribute_')) {
                    variations[key] = data[key];
                }
            });

            $('[name^="attribute_"]').each(function () {
                let name = $(this).attr('name');
                variations[name] = $(this).val();
            });
        }

        $('button,.button').addClass('disabled').css('pointer-events', 'none');


        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'ajax_add_to_cart_with_crosssell',
                product_id: product_id,
                quantity: quantity,
                variation_id: variation_id,
                variations: variations,
                crossSellProductId: isCrossSell,
                levelIndex: levelIndex,
                nonce: ajax_object.nonce,
            },
            success: function (response) {
                if (response.success) {
                    if (noModal) {
                        if (response.data.is_cross_sell_page) {
                            $(button).text(ajax_object.added);
                            $(button).attr('disabled', true);
                            $(button).removeClass('loading');
                            $('.cross-sell-page-products').html(response.data.html);
                            $('.woocommerce-notices-wrapper').html(response.data.notices_html);
                        } else {
                            $(button).removeClass('loading');
                            $(button).addClass('added');
                            var buttonHtml = $(button).html();
                            buttonHtml += '<i class="icon-check-ico"></i>';
                            $(button).html(buttonHtml);
                            setTimeout(function () {
                                $(button).removeClass('added');
                                $(button).html(buttonHtml.replace('<i class="icon-check-ico"></i>', ''));
                            }, 2000);
                        }
                    } else {
                        $(button).removeClass('loading');
                        $('#cross-sell-modal .cross-sell-modal-body .cross-sell-modal-products .splide__list').html(response.data.html);
                        $('#cross-sell-modal .cross-sell-modal-header .cross-sell-modal-add-notification').html(response.data.added_product_name);
                        $('#cross-sell-modal ').addClass('show');
                        initModalSplide();
                        $('body').addClass('overflow-hidden');
                    }
                } else {
                    if (response.data.notices_html) {
                        $(button).removeClass('loading');
                        $('.woocommerce-notices-wrapper').html(response.data.notices_html);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Błąd podczas pobierania produktów:', error);
            },
            complete: function () {
                $('button,.button').removeClass('disabled').css('pointer-events', 'auto');
            }
        });
    });

    $(document).on('click', '.remove_cross_sell_product_from_cart', function (event) {
        event.preventDefault();
        var button = $(this);
        $(button).addClass('loading');
        var levelIndex = $(this).data('level-index');
        var crossSellProductId = $(this).data('cross-sell-product-id');
        var productId = $(this).data('product_id');
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_cross_sell_product_from_cart',
                levelIndex: levelIndex,
                crossSellProductId: crossSellProductId,
                productId: productId,
                nonce: ajax_object.nonce,
            },
            success: function (response) {
                $(button).removeClass('loading');
                if (response.success) {
                    if (response.data.html) {
                        $('.cross-sell-page-products').html(response.data.html);
                        $('.woocommerce-notices-wrapper').html(response.data.notices_html);
                    } else {
                        location.reload();
                    }
                } else {
                    location.reload();
                }
            }
        });
    });

    function initModalSplide() {

        const productsPerPage = parseInt(ajax_object.products_per_page) || 3;

        const splideModalConf = {
            type: 'slider',
            perPage: 4,
            gap: 10,
            pagination: false,
            arrows: false,
            breakpoints: {
                992: {
                    perPage: 3,
                    arrows: true,
                },
                576: {
                    perPage: 2,
              
                },
            }
        }

        if (productsPerPage > 3) {
            splideModalConf.arrows = true;
        }

        new Splide('.cross-sell-modal-products-splide', splideModalConf).mount();

    }


});