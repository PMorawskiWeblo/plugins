<?php
/**
 * Frontend personalizer template.
 *
 * @package WooProductPersonalizer
 * @var string $mode
 * @var string $button_label
 * @var string $accept_text
 * @var bool   $validation
 * @var bool   $accept_required
 */

defined( 'ABSPATH' ) || exit;

$is_modal        = ( 'modal' === $mode );
$wrapper_class   = $is_modal ? 'wpp-personalizer wpp-personalizer--modal' : 'wpp-personalizer wpp-personalizer--inline';
$toolbar_partial = WPP_PLUGIN_PATH . 'templates/frontend/partials/transform-toolbar.php';
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>" data-validation="<?php echo esc_attr( $validation ? '1' : '0' ); ?>">
	<?php if ( $is_modal ) : ?>
		<div class="wpp-modal" id="wpp-modal" aria-hidden="true" role="dialog" aria-modal="true">
			<div class="wpp-modal__backdrop" data-wpp-close></div>
			<div class="wpp-modal__dialog">
				<button type="button" class="wpp-modal__close" data-wpp-close aria-label="<?php esc_attr_e( 'Close', 'woo-product-personalizer' ); ?>">&times;</button>
				<div class="wpp-modal__body">
	<?php endif; ?>

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

			<?php if ( ! $is_modal && file_exists( $toolbar_partial ) ) : ?>
				<?php include $toolbar_partial; ?>
			<?php endif; ?>
		</div>
	</div>

	<input type="hidden" name="wpp_personalizer_nonce" value="<?php echo esc_attr( $personalizer_nonce ); ?>" />
	<input type="hidden" name="wpp_project_state" class="wpp-project-state" value="" />
	<input type="hidden" name="wpp_preview_data" class="wpp-preview-data" value="" />
	<input type="hidden" name="wpp_preview_layers_data" class="wpp-preview-layers-data" value="" />

	<?php if ( ! $is_modal ) : ?>
		<p class="wpp-save-row">
			<button type="button" class="button alt wpp-save-personalization"><?php echo esc_html( $button_label ); ?></button>
		</p>
	<?php endif; ?>

	<?php if ( $is_modal ) : ?>
				</div>
				<div class="wpp-modal__footer">
					<?php if ( file_exists( $toolbar_partial ) ) : ?>
						<?php include $toolbar_partial; ?>
					<?php endif; ?>
					<button type="button" class="button alt wpp-save-personalization"><?php echo esc_html( $button_label ); ?></button>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
