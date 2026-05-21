<?php
/**
 * Image crop modal (layout personalization mode 2).
 *
 * @package WooProductPersonalizer
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wpp-crop-modal" class="wpp-crop-modal" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="wpp-crop-modal__backdrop" data-wpp-crop-close></div>
	<div class="wpp-crop-modal__dialog">
		<button type="button" class="wpp-crop-modal__close" data-wpp-crop-close aria-label="<?php esc_attr_e( 'Close', 'woo-product-personalizer' ); ?>">&times;</button>
		<div class="wpp-crop-modal__stage">
			<div class="wpp-crop-modal__crop-area">
				<img id="wpp-crop-image" class="wpp-crop-modal__image" src="" alt="" />
			</div>
		</div>
		<div class="wpp-crop-modal__footer">
			<div class="wpp-crop-modal__zoom" role="group" aria-label="<?php esc_attr_e( 'Zoom', 'woo-product-personalizer' ); ?>">
				<button type="button" class="wpp-crop-modal__zoom-btn" data-wpp-crop-zoom="out" aria-label="<?php esc_attr_e( 'Zoom out', 'woo-product-personalizer' ); ?>">
					<span aria-hidden="true">−</span>
				</button>
				<button type="button" class="wpp-crop-modal__zoom-btn" data-wpp-crop-zoom="in" aria-label="<?php esc_attr_e( 'Zoom in', 'woo-product-personalizer' ); ?>">
					<span aria-hidden="true">+</span>
				</button>
			</div>
			<div class="wpp-crop-modal__actions">
				<button type="button" class="btn wpp-crop-modal__cancel"><?php esc_html_e( 'Cancel', 'woo-product-personalizer' ); ?></button>
				<button type="button" class="btn wpp-crop-modal__select"><?php esc_html_e( 'Select', 'woo-product-personalizer' ); ?></button>
			</div>
		</div>
	</div>
</div>
