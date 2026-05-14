
jQuery(document).ready(function($) {

    $('select').select2();

    // Delegacja zdarzeń na dokument
    $(document).on('click', '.add-product', function(e) {
        e.preventDefault();

        var level = $(this).closest('.gift-level').data('level');
        var template = $('#product-template').html();
        template = template.replace('{{level}}', level);
        $(this).closest('.gift-level').find('.product-container').append(template);
        
        $('select').select2();
    });
    

    $(document).on('click', '.add-cross-sell-product', function(e) {
        e.preventDefault();

        var level = $(this).closest('.gift-level').data('level');
        var template = $('#add-cross-sell-product-template').html();
        template = template.replace('{{level}}', level);
        $(this).closest('.gift-level').find('.cross-sell-products-container').append(template);

        $('select').select2();
    });

    $(document).on('click', '#add_level', function(e) {
        e.preventDefault();

        var template = $('#add-level-template').html();

        var $tempDiv = $('<div>').html(template);
        $tempDiv.find('.level-title').text('Nowy poziom ');        
        template = $tempDiv.html();
        
        $('#levels-container').append(template);

        $('select').select2();

    });

    $(document).on('click', '.remove-product', function() {
        $(this).closest('.product-item').remove();
    });

    $(document).on('click', '.remove-cross-sell-product', function() {
        $(this).closest('.cross-sell-product').remove();
    });

    $(document).on('click', '.remove-gift-level', function() {
        $(this).closest('.gift-level').remove();
    });

    $(document).on('click', '#submit_gifts_settings', function() {

        var show_gifts = $('#show_gifts').is(':checked');
        var is_progress_bar_visible = $('#is_progress_bar_visible').is(':checked');
        var progress_bar_title = $('#progress_bar_title').val();
        var active_gifts_display = $('#active_gifts_display').val();
        var levels = $('.gift-level');

        var levelsData = [];
        levels.each(function() {

            var levelTitle = $(this).find('input.level-title').val();
            var levelProg = $(this).find('input.level-prog').val();
            var levelGiftsCount = $(this).find('input.level-gifts-count').val();
            
            if(!levelProg || !levelGiftsCount){  
                return;
            }

            var levelData = {   
                nazwa: levelTitle,
                prog: levelProg,
                ilosc: levelGiftsCount,
                products: [],
                crossSellProducts: []
            };

            var products = $(this).find('.product-item');   
            products.each(function(product) {
                var productName = $(this).find('.product-name').val();
                var productSlogan = $(this).find('.product-slogan').val();  
                var productPrice = $(this).find('.product-price').val();
                var productId = $(this).find('.product-id').select2('val');

                if( !productPrice || !productId){
                    return;
                }

                levelData.products.push({
                    nazwa: productName,
                    slogan: productSlogan,
                    cena: productPrice,
                    id: productId
                });
            });

            var crossSellProducts = $(this).find('.cross-sell-product');

            crossSellProducts.each(function(product) {

                var productId = $(this).find('.product-id').select2('val');
                var discountType = $(this).find('.discount-type').select2('val'); 
                var discountValue = $(this).find('.product-discount').val();


                if (!productId || !discountType || !discountValue) {
                    return;
                }

                if(discountType == 'percent' && discountValue > 100){
                    discountValue = 100;
                }

                levelData.crossSellProducts.push({
                    id: productId,
                    discount_type: discountType,
                    discount_value: discountValue
                });
            });
            levelsData.push(levelData);
        });

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_gifts_levels',
                nonce: ajax_object.nonce,
                levelsData: levelsData,
                show_gifts: show_gifts,
                is_progress_bar_visible: is_progress_bar_visible,
                progress_bar_title: progress_bar_title,
                active_gifts_display: active_gifts_display
            },
            success: function(response) {
                alert('Ustawienia zostały zapisane');
                location.reload();
            }
        });
    });

    
});