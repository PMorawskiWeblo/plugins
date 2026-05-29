<?php
/**
 * Frontend personalizer template (modal only).
 *
 * @package WooProductPersonalizer
 * @var string $button_label
 * @var string $accept_text
 * @var bool   $validation
 * @var bool   $accept_required
 */

defined( 'ABSPATH' ) || exit;

$toolbar_partial = WPP_PLUGIN_PATH . 'templates/frontend/partials/transform-toolbar.php';
?>
<div class="wpp-personalizer wpp-personalizer--modal" data-validation="<?php echo esc_attr( $validation ? '1' : '0' ); ?>">
	<div class="wpp-modal" id="wpp-modal" aria-hidden="true" role="dialog" aria-modal="true">
		<div class="wpp-modal__backdrop" data-wpp-close></div>
		<div class="wpp-modal__dialog">
			<button type="button" class="wpp-modal__close" data-wpp-close aria-label="<?php esc_attr_e( 'Close', 'woo-product-personalizer' ); ?>">&times;</button>
			<div class="wpp-modal__body">
				<div class="wpp-editor">
					<div class="wpp-editor__preview">
						<div id="wpp-canvas-container" class="wpp-canvas-container"></div>
					</div>

					<div class="wpp-editor__controls">
						<div class="wpp-fields wpp-image-fields"></div>
						<div class="wpp-fields wpp-text-fields"></div>

						<?php if ( $accept_required ) : ?>
							<div class="wpp-acceptance wpp-field">
								<label class="wpp-acceptance__label">
									<input type="checkbox" class="wpp-acceptance-checkbox" />
									<span><?php echo esc_html( $accept_text ); ?></span>
								</label>
								<p class="wpp-field-error" role="alert" hidden></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<input type="hidden" name="wpp_personalizer_nonce" value="<?php echo esc_attr( $personalizer_nonce ); ?>" />
				<input type="hidden" name="wpp_project_state" class="wpp-project-state" value="" />
				<input type="hidden" name="wpp_preview_data" class="wpp-preview-data" value="" />
				<input type="hidden" name="wpp_preview_layers_data" class="wpp-preview-layers-data" value="" />
				<input type="hidden" name="wpp_text_svg_data" class="wpp-text-svg-data" value="" />
			</div>
			<div class="wpp-modal__footer">
				<?php if ( file_exists( $toolbar_partial ) ) : ?>
					<?php include $toolbar_partial; ?>
				<?php endif; ?>
				<button type="button" class="button alt wpp-save-personalization"><?php echo esc_html( $button_label ); ?></button>
			</div>
		</div>
	</div>

	<?php
	$crop_partial = WPP_PLUGIN_PATH . 'templates/frontend/partials/crop-modal.php';
	if ( file_exists( $crop_partial ) ) {
		include $crop_partial;
	}
	?>
</div>
