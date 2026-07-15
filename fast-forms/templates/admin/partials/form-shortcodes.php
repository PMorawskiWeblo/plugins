<?php
/**
 * Shortcodes partial for form admin (settings tab + sidebar metabox).
 *
 * @package FastForms
 *
 * @var int                  $form_id
 * @var string               $id_suffix   Suffix for element IDs, e.g. '' or '-metabox'.
 * @var bool                 $compact     Compact layout for sidebar metabox.
 * @var array<string, mixed> $form_opts   Form display settings.
 * @var string               $shortcode_inline
 * @var string               $shortcode_button
 * @var string               $shortcode_trigger
 */

use Weblo\FastForms\FormBuilder\BuilderI18n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id_suffix = isset( $id_suffix ) ? (string) $id_suffix : '';
$compact     = ! empty( $compact );
$tips        = BuilderI18n::get_settings_tooltips();
$inline_id   = 'ff-shortcode-inline' . $id_suffix;
$button_id   = 'ff-shortcode-button' . $id_suffix;
$trigger_id  = 'ff-shortcode-trigger' . $id_suffix;
?>
<div class="ff-shortcode-settings<?php echo $compact ? ' ff-shortcode-settings--compact' : ''; ?>">
	<?php if ( ! $compact ) : ?>
		<p class="description"><?php echo esc_html( $tips['shortcode'] ?? '' ); ?></p>
	<?php endif; ?>

	<div class="ff-shortcode-block">
		<strong><?php esc_html_e( 'Inline form', 'fast-forms' ); ?></strong>
		<?php if ( ! $compact ) : ?>
			<?php echo BuilderI18n::field_description( $tips['shortcodeInline'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<div class="ff-shortcode-row">
			<code class="ff-shortcode-preview" id="<?php echo esc_attr( $inline_id ); ?>"><?php echo esc_html( $shortcode_inline ); ?></code>
			<button type="button" class="button ff-shortcode-copy" data-ff-copy="#<?php echo esc_attr( $inline_id ); ?>" aria-label="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>">
				<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
			</button>
		</div>
	</div>

	<div class="ff-shortcode-block">
		<strong><?php esc_html_e( 'Button with modal', 'fast-forms' ); ?></strong>
		<?php if ( ! $compact ) : ?>
			<?php echo BuilderI18n::field_description( $tips['shortcodeButton'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<label for="ff-form-shortcodeButtonText"><?php esc_html_e( 'Button text', 'fast-forms' ); ?></label><br>
				<input type="text" class="regular-text" id="ff-form-shortcodeButtonText" data-ff-setting="shortcodeButtonText" data-ff-group="form" value="<?php echo esc_attr( $form_opts['shortcodeButtonText'] ?? __( 'Open form', 'fast-forms' ) ); ?>" />
			</p>
			<p>
				<label for="ff-form-shortcodeButtonClass"><?php esc_html_e( 'Button CSS classes', 'fast-forms' ); ?></label><br>
				<input type="text" class="regular-text" id="ff-form-shortcodeButtonClass" data-ff-setting="shortcodeButtonClass" data-ff-group="form" value="<?php echo esc_attr( $form_opts['shortcodeButtonClass'] ?? 'button' ); ?>" placeholder="button btn-primary" />
			</p>
		<?php endif; ?>
		<div class="ff-shortcode-row">
			<code class="ff-shortcode-preview" id="<?php echo esc_attr( $button_id ); ?>"><?php echo esc_html( $shortcode_button ); ?></code>
			<button type="button" class="button ff-shortcode-copy" data-ff-copy="#<?php echo esc_attr( $button_id ); ?>" aria-label="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>">
				<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
			</button>
		</div>
	</div>

	<div class="ff-shortcode-block">
		<strong><?php esc_html_e( 'CSS trigger (modal)', 'fast-forms' ); ?></strong>
		<?php if ( ! $compact ) : ?>
			<?php echo BuilderI18n::field_description( $tips['shortcodeTrigger'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<label for="ff-form-shortcodeTriggerClass"><?php esc_html_e( 'CSS selector / class', 'fast-forms' ); ?></label><br>
				<input type="text" class="regular-text" id="ff-form-shortcodeTriggerClass" data-ff-setting="shortcodeTriggerClass" data-ff-group="form" value="<?php echo esc_attr( $form_opts['shortcodeTriggerClass'] ?? '' ); ?>" placeholder=".open-form-<?php echo esc_attr( (string) $form_id ); ?>" />
			</p>
		<?php endif; ?>
		<div class="ff-shortcode-row">
			<code class="ff-shortcode-preview" id="<?php echo esc_attr( $trigger_id ); ?>"><?php echo esc_html( $shortcode_trigger ); ?></code>
			<button type="button" class="button ff-shortcode-copy" data-ff-copy="#<?php echo esc_attr( $trigger_id ); ?>" aria-label="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'fast-forms' ); ?>">
				<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
			</button>
		</div>
	</div>
</div>
