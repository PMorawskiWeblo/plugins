jQuery(document).ready(function ($) {
  $(document).on('change', '.variation-select', function () {
    var $select = $(this);
    var cartItemKey = $select.data('cart-item-key');
    var variationId = $select.val();

    if (!variationId) return;

    $select.prop('disabled', true);

    $.ajax({
      url: ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'update_cart_variation',
        cart_item_key: cartItemKey,
        variation_id: variationId,
        nonce: ajax_object.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Odśwież fragment koszyka
          $(document.body).trigger('wc_fragment_refresh');
        } else {
          // Przywróć poprzednią wartość w przypadku błędu
          $select.val($select.data('previous-value'));
          alert(response.data.message || 'Wystąpił błąd podczas aktualizacji wariantu.');
        }
      },
      error: function () {
        // Przywróć poprzednią wartość w przypadku błędu
        $select.val($select.data('previous-value'));
        alert('Wystąpił błąd podczas aktualizacji wariantu.');
      },
      complete: function () {
        $select.prop('disabled', false);
      },
    });
  });

  // Zapisz poprzednią wartość przed zmianą
  $(document).on('focus', '.variation-select', function () {
    $(this).data('previous-value', $(this).val());
  });
});
