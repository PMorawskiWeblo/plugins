<?php
/**
 * Frontend form template.
 *
 * @package FastForms
 *
 * @var int                  $form_id
 * @var string               $instance
 * @var string               $display
 * @var array<string, mixed> $schema
 * @var array<string, mixed> $settings
 * @var FormRenderer         $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_modal   = in_array( $display, array( 'modal', 'button', 'trigger' ), true );
$wrap_class = 'ff-form-wrap ff-form-wrap--' . esc_attr( $display );

if ( $is_modal ) {
	$wrap_class .= ' ff-form-wrap--hidden';
}
?>
<div
	id="<?php echo esc_attr( $instance ); ?>"
	class="<?php echo esc_attr( $wrap_class ); ?>"
	data-ff-form-id="<?php echo esc_attr( (string) $form_id ); ?>"
	data-ff-instance="<?php echo esc_attr( $instance ); ?>"
	<?php if ( $is_modal ) : ?>
	role="dialog"
	aria-modal="true"
	aria-hidden="true"
	<?php endif; ?>
>
	<?php if ( $is_modal ) : ?>
		<div class="ff-modal__backdrop" data-ff-close></div>
		<div class="ff-modal__dialog">
			<button type="button" class="ff-modal__close" data-ff-close aria-label="<?php esc_attr_e( 'Close', 'fast-forms' ); ?>">&times;</button>
	<?php endif; ?>

	<form
		class="ff-form"
		method="post"
		action="#"
		enctype="multipart/form-data"
		data-ff-form="<?php echo esc_attr( (string) $form_id ); ?>"
		data-ff-submit-url="<?php echo esc_url( rest_url( 'fast-forms/v1/forms/' . $form_id . '/submit' ) ); ?>"
		novalidate
	>
		<script type="application/json" class="ff-form-validation-data"><?php echo wp_json_encode( $settings['validation'] ?? array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		<input type="hidden" name="ff_form_nonce" value="<?php echo esc_attr( \Weblo\FastForms\Submission\SubmitToken::create( $form_id ) ); ?>" />
		<input type="hidden" name="ff_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>" />
		<input type="hidden" name="ff_instance" value="<?php echo esc_attr( $instance ); ?>" />

		<?php if ( \Weblo\FastForms\Submission\Honeypot::is_enabled() ) : ?>
			<div class="ff-honeypot" aria-hidden="true">
				<label for="<?php echo esc_attr( $instance ); ?>-hp"><?php esc_html_e( 'Leave this field empty', 'fast-forms' ); ?></label>
				<input
					type="text"
					name="<?php echo esc_attr( \Weblo\FastForms\Submission\Honeypot::FIELD_NAME ); ?>"
					id="<?php echo esc_attr( $instance ); ?>-hp"
					value=""
					tabindex="-1"
					autocomplete="off"
				/>
			</div>
		<?php endif; ?>

		<div class="ff-form__messages" aria-live="polite"></div>

		<div class="ff-form__preloader" hidden>
			<span class="ff-spinner" aria-hidden="true"></span>
			<span class="ff-form__preloader-text"><?php esc_html_e( 'Sending…', 'fast-forms' ); ?></span>
		</div>

		<div class="ff-form__body">
			<?php foreach ( $schema['rows'] as $row ) : ?>
				<?php
				$row_class = 'ff-form__row';
				$row_css   = trim( (string) ( $row['cssClass'] ?? '' ) );

				if ( '' !== $row_css ) {
					$row_class .= ' ' . $row_css;
				}

				$row_id_attr = '';
				$row_html_id = trim( (string) ( $row['htmlId'] ?? '' ) );

				if ( '' !== $row_html_id ) {
					$row_id_attr = ' id="' . esc_attr( $row_html_id ) . '"';
				}
				?>
				<div class="<?php echo esc_attr( $row_class ); ?>"<?php echo $row_id_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php foreach ( $row['columns'] as $column ) : ?>
						<?php
						$column_class = 'ff-form__column';
						$column_css   = trim( (string) ( $column['cssClass'] ?? '' ) );

						if ( '' !== $column_css ) {
							$column_class .= ' ' . $column_css;
						}

						$column_id_attr = '';
						$column_html_id = trim( (string) ( $column['htmlId'] ?? '' ) );

						if ( '' !== $column_html_id ) {
							$column_id_attr = ' id="' . esc_attr( $column_html_id ) . '"';
						}
						?>
						<div class="<?php echo esc_attr( $column_class ); ?>" style="width: <?php echo esc_attr( (string) $column['width'] ); ?>%;"<?php echo $column_id_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php foreach ( $column['fields'] as $field ) : ?>
								<?php $this->render_field( $field ); ?>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( \Weblo\FastForms\Support\GlobalSettings::is_turnstile_active() ) : ?>
			<div class="ff-turnstile-widget" aria-hidden="true"></div>
		<?php endif; ?>
	</form>

	<?php if ( $is_modal ) : ?>
		</div>
	<?php endif; ?>
</div>
