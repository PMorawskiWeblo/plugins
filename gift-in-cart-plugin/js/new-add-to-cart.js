jQuery(document).ready(function($) {
    function addAddToCartListeners() {
        // Handler for custom add-to-cart button click
        $('div[data-action="add-to-cart"]').off('click').on('click', function(e) {
            e.preventDefault();

            // Get product ID
            var productId = $(this).data('product-id');
            var quantity = $(this).data('quantity') || 1;

            // Make Ajax request
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'new_ajax_add_to_cart',
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    // Check if product added successfully
                    if (response.success) {
                        if(wc_add_to_cart_params.is_cart){
                          $("[name='update_cart']").prop("disabled", false);
                          $("[name='update_cart']").trigger("click");
                          
                          setTimeout(function () {
                            if (typeof updateNotices === 'function') {
                              updateNotices();
                            }
                          }, 500);
                        }else{
                           console.log(response);
                        }
                    } else {
                        alert('Failed to add product');
                    }
                },
                error: function(error) {
                    console.error('Ajax error:', error);
                }
            });
        });
    }

    addAddToCartListeners(); // Call the function once on document ready

    $(document).on("ajaxComplete", addAddToCartListeners); // Re-attach event listeners after every AJAX completion
});
