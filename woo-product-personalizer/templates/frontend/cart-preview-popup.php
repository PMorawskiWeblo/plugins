<?php
/**
 * Cart / checkout personalization preview modal.
 *
 * @package WooProductPersonalizer
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wpp-cart-preview-popup" class="wpp-cart-preview-popup" aria-hidden="true">
	<div class="wpp-cart-preview-popup__backdrop" data-wpp-cart-preview-close></div>
	<div class="wpp-cart-preview-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="wpp-cart-preview-title">
		<button type="button" class="wpp-cart-preview-popup__close" data-wpp-cart-preview-close aria-label="<?php esc_attr_e( 'Close', 'woo-product-personalizer' ); ?>">&times;</button>
		<h2 id="wpp-cart-preview-title" class="wpp-cart-preview-popup__title"><?php esc_html_e( 'Personalization preview', 'woo-product-personalizer' ); ?></h2>
		<div class="wpp-cart-preview-popup__canvas"></div>
	</div>
</div>
