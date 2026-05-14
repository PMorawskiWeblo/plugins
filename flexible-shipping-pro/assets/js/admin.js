/**
 * Created by grola on 2016-11-25.
 */
jQuery( document ).ready(
	function() {

		let $free_shipping_requires = jQuery( '#woocommerce_flexible_shipping_method_free_shipping_requires' );
		let $method_free_shipping = jQuery( '#woocommerce_flexible_shipping_method_free_shipping' );

		function fspro_free_shipping_requires() {
			if ( $free_shipping_requires.length ) {
				let free_shipping_requires_val = jQuery( '#woocommerce_flexible_shipping_method_free_shipping_requires' ).val();
				let show_ignore_discounts = ! [ 'coupon', 'item_quantity' ].includes( free_shipping_requires_val );
				jQuery( '#woocommerce_flexible_shipping_method_free_shipping_ignore_discounts' ).closest( 'tr' ).toggle( show_ignore_discounts );

				let free_shipping_options = [ 'order_amount', 'order_amount_or_coupon', 'order_amount_and_coupon', 'item_quantity' ];
				let show_free_shipping_field = free_shipping_options.includes( free_shipping_requires_val );
				let show_free_shipping_notice_fields = show_free_shipping_field && $method_free_shipping.val() !== '' && free_shipping_requires_val != 'order_amount_and_coupon';
				$method_free_shipping.closest( 'tr' ).toggle( show_free_shipping_field );
				jQuery( '#woocommerce_flexible_shipping_method_free_shipping_cart_notice' ).closest( 'tr' ).toggle( show_free_shipping_notice_fields );
				jQuery( '#woocommerce_flexible_shipping_method_free_shipping_notice_text' ).closest( 'tr' ).toggle( show_free_shipping_notice_fields );
				jQuery( '#woocommerce_flexible_shipping_method_free_shipping_progress_bar' ).closest( 'tr' ).toggle( show_free_shipping_notice_fields );
				jQuery( '#woocommerce_flexible_shipping_method_free_shipping_display_on' ).closest( 'tr' ).toggle( show_free_shipping_notice_fields );
			}
		}

		$free_shipping_requires.change(
			function () {
				fspro_free_shipping_requires();
			}
		);

		$method_free_shipping.change(
			function () {
				fspro_free_shipping_requires();
			}
		);

		fspro_free_shipping_requires();

		jQuery( '#flexible_shipping_export_selected' ).click(
			function () {
				var methods = '';
				var first = true;
				jQuery( 'input.checkbox-select' ).each(
					function () {
						if ( jQuery( this ).is( ':checked' ) ) {
							if ( ! first ) {
								methods = methods + ',';
							}
							methods = methods + jQuery( this ).val();
							first = false;
						}
					}
				);
				var data = {
					action: 'flexible_shipping_export',
					flexible_shipping_nonce: jQuery( this ).attr( 'data-nonce' ),
					flexible_shipping_action: 'export',
					instance_id: jQuery( this ).attr( 'data-instance-id' ),
					methods: methods,
				};
				url = ajaxurl + '?action=flexible_shipping_export';
				url = url + '&flexible_shipping_nonce=' + jQuery( this ).attr( 'data-nonce' );
				url = url + '&flexible_shipping_action=export';
				url = url + '&instance_id=' + jQuery( this ).attr( 'data-instance-id' );
				url = url + '&methods=' + methods;
				console.log( url );
				window.open( url );
				return false;
			}
		);

		jQuery( 'select.fs-shipping-class' ).select2(
			{
				dropdownCssClass: 'fs_shipping_class',
			}
		);

		jQuery( document ).on(
			'insert_rule',
			function () {
				jQuery( 'select.fs-shipping-class' ).select2(
					{
						dropdownCssClass: 'fs_shipping_class',
					}
				);
			}
		)
	}
);
