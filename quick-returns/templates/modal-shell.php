<?php
/**
 * Modal shell template.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="qr-modal" id="qr-return-modal" hidden aria-hidden="true">
	<div class="qr-modal__overlay" data-qr-close></div>
	<div class="qr-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qr-modal-title">
		<button type="button" class="qr-modal__close" data-qr-close aria-label="<?php esc_attr_e( 'Close', 'quick-returns' ); ?>">&times;</button>
		<div
			class="qr-form"
			data-qr-form="1"
			data-qr-context="modal"
			data-qr-initial-step="0"
			data-qr-order-id="0"
			data-qr-mode="manual_select"
		>
			<div class="qr-card">
				<div class="qr-card__header">
					<h2 class="qr-card__title" id="qr-modal-title"><?php esc_html_e( 'Return request', 'quick-returns' ); ?></h2>
					<p class="qr-card__subtitle"><?php esc_html_e( 'Fill out the form so we can process your return.', 'quick-returns' ); ?></p>
				</div>
				<div class="qr-card__body">
					<div class="qr-app" aria-live="polite"></div>
				</div>
			</div>
		</div>
	</div>
</div>
