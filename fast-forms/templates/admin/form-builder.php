<?php
/**
 * Form builder template.
 *
 * @package FastForms
 *
 * @var \WP_Post $post Edytowany formularz.
 */

use Weblo\FastForms\FormBuilder\BuilderI18n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ff_i18n = BuilderI18n::get_js_i18n();
?>
<div id="ff-form-builder" class="ff-form-builder is-loading" data-form-id="<?php echo esc_attr( (string) $post->ID ); ?>">
	<div class="ff-builder-loading" id="ff-builder-loading" aria-live="polite">
		<span class="spinner is-active" aria-hidden="true"></span>
		<span class="ff-builder-loading__text"><?php echo esc_html( $ff_i18n['loadingSchema'] ?? __( 'Loading form…', 'fast-forms' ) ); ?></span>
	</div>

	<?php wp_nonce_field( \Weblo\FastForms\FormBuilder\FormSaver::NONCE_ACTION, \Weblo\FastForms\FormBuilder\FormSaver::NONCE_FIELD ); ?>
	<textarea name="<?php echo esc_attr( \Weblo\FastForms\FormBuilder\FormSaver::SCHEMA_FIELD ); ?>" id="ff-schema-json" class="ff-schema-sync-field" aria-hidden="true" tabindex="-1"></textarea>
	<input type="hidden" name="ff_schema_encoded" id="ff-schema-json-b64" value="" />
	<textarea name="<?php echo esc_attr( \Weblo\FastForms\FormBuilder\FormSaver::SETTINGS_FIELD ); ?>" id="ff-form-settings-json" class="ff-schema-sync-field" aria-hidden="true" tabindex="-1"></textarea>

	<div id="ff-builder-tabs" class="ff-builder-tabs">
		<ul class="ff-builder-tabs__nav">
			<li><a href="#ff-tab-form"><?php echo esc_html( $ff_i18n['tabForm'] ); ?></a></li>
			<li><a href="#ff-tab-email"><?php echo esc_html( $ff_i18n['tabEmail'] ); ?></a></li>
			<li><a href="#ff-tab-validation"><?php echo esc_html( $ff_i18n['tabValidation'] ); ?></a></li>
			<li><a href="#ff-tab-notifications"><?php echo esc_html( $ff_i18n['tabNotifications'] ); ?></a></li>
			<li><a href="#ff-tab-settings"><?php echo esc_html( $ff_i18n['tabSettings'] ); ?></a></li>
			<li><a href="#ff-tab-documentation"><?php echo esc_html( $ff_i18n['tabDocumentation'] ); ?></a></li>
		</ul>

		<div id="ff-tab-form" class="ff-builder-tab-panel">
			<div class="ff-builder-toolbar">
				<button type="button" class="button" id="ff-add-row">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php echo esc_html( $ff_i18n['addRow'] ); ?>
				</button>
				<button type="button" class="button button-primary" id="ff-save-form">
					<?php echo esc_html( $ff_i18n['saveForm'] ); ?>
				</button>
				<span class="ff-save-status" id="ff-save-status" aria-live="polite"></span>
				<span class="ff-builder-toolbar__hint description"><?php esc_html_e( 'Form layout is saved when you click “Save form” or “Update”.', 'fast-forms' ); ?></span>
			</div>

			<div class="ff-builder-layout">
				<aside class="ff-builder-sidebar ff-builder-sidebar--left">
					<h3 class="ff-builder-sidebar__title"><?php echo esc_html( $ff_i18n['fieldsPanel'] ); ?></h3>
					<ul class="ff-field-palette" id="ff-field-palette"></ul>
				</aside>

				<main class="ff-builder-canvas-wrap">
					<h3 class="ff-builder-sidebar__title"><?php echo esc_html( $ff_i18n['canvas'] ); ?></h3>
					<div class="ff-builder-canvas" id="ff-builder-canvas"></div>
				</main>

				<aside class="ff-builder-sidebar ff-builder-sidebar--right">
					<h3 class="ff-builder-sidebar__title"><?php echo esc_html( $ff_i18n['settingsPanel'] ); ?></h3>
					<div class="ff-field-settings" id="ff-field-settings">
						<p class="ff-field-settings__empty"><?php echo esc_html( $ff_i18n['noFieldSelected'] ); ?></p>
					</div>
				</aside>
			</div>
		</div>

		<?php
		$form_settings = \Weblo\FastForms\FormBuilder\FormSettingsStorage::get_all( $post->ID );
		include FF_PLUGIN_DIR . 'templates/admin/form-builder-settings.php';
		include FF_PLUGIN_DIR . 'templates/admin/form-builder-documentation.php';
		?>
	</div>
</div>
