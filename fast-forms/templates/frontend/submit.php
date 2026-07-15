<?php
/**
 * Frontend submit button template.
 *
 * @package FastForms
 *
 * @var string $input_id
 * @var array<string, mixed> $field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$submit_text  = $field['submitText'] ?? \Weblo\FastForms\FormBuilder\BuilderI18n::default_submit_text();
$loading_text = $field['loadingText'] ?? \Weblo\FastForms\FormBuilder\BuilderI18n::default_loading_text();
$html_id      = trim( (string) ( $field['htmlId'] ?? '' ) );
$wrapper_id   = '' !== $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '';
$live_validation = ! empty( $field['liveValidation'] );
?>
<div class="ff-field ff-field--submit"<?php echo $wrapper_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<button type="button" class="ff-submit button" data-loading-text="<?php echo esc_attr( $loading_text ); ?>"<?php echo $live_validation ? ' data-ff-live-validation="1"' : ''; ?>>
		<?php echo esc_html( $submit_text ); ?>
	</button>
</div>
