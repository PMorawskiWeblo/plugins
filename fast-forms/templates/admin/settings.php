<?php
/**
 * Global settings admin template.
 *
 * @package FastForms
 *
 * @var string               $title Page title.
 * @var array<string, mixed> $settings Ustawienia globalne.
 */

use Weblo\FastForms\Support\GlobalSettings;
use Weblo\FastForms\Support\UploadPath;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$updated          = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$captcha_provider = (string) ( $settings['captchaProvider'] ?? GlobalSettings::CAPTCHA_NONE );
?>
<div class="wrap ff-admin-wrap">
	<h1><?php echo esc_html( $title ); ?></h1>

	<?php if ( $updated ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'fast-forms' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ff-admin-card" id="ff-global-settings-form">
		<?php wp_nonce_field( 'ff_save_global_settings' ); ?>
		<input type="hidden" name="action" value="ff_save_global_settings" />

		<h2><?php esc_html_e( 'Anti-spam protection', 'fast-forms' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Choose one captcha provider for all forms. Configure keys in the provider dashboard.', 'fast-forms' ); ?></p>

		<?php if ( ! GlobalSettings::is_captcha_active() ) : ?>
			<div class="notice notice-warning inline" style="margin: 12px 0;">
				<p>
					<?php esc_html_e( 'Captcha is disabled. Honeypot and hourly rate limiting are active, but enabling reCAPTCHA or Turnstile is strongly recommended on production sites.', 'fast-forms' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="ff-captcha-provider"><?php esc_html_e( 'Captcha provider', 'fast-forms' ); ?></label></th>
				<td>
					<select name="ff_global_settings[captchaProvider]" id="ff-captcha-provider">
						<option value="<?php echo esc_attr( GlobalSettings::CAPTCHA_NONE ); ?>" <?php selected( $captcha_provider, GlobalSettings::CAPTCHA_NONE ); ?>><?php esc_html_e( 'Disabled', 'fast-forms' ); ?></option>
						<option value="<?php echo esc_attr( GlobalSettings::CAPTCHA_RECAPTCHA ); ?>" <?php selected( $captcha_provider, GlobalSettings::CAPTCHA_RECAPTCHA ); ?>><?php esc_html_e( 'Google reCAPTCHA v3', 'fast-forms' ); ?></option>
						<option value="<?php echo esc_attr( GlobalSettings::CAPTCHA_TURNSTILE ); ?>" <?php selected( $captcha_provider, GlobalSettings::CAPTCHA_TURNSTILE ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'fast-forms' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<div class="ff-captcha-panel" data-provider="<?php echo esc_attr( GlobalSettings::CAPTCHA_RECAPTCHA ); ?>" <?php echo GlobalSettings::CAPTCHA_RECAPTCHA !== $captcha_provider ? 'hidden' : ''; ?>>
			<h3><?php esc_html_e( 'Google reCAPTCHA v3', 'fast-forms' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Get keys from Google reCAPTCHA Admin.', 'fast-forms' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ff-recaptcha-site-key"><?php esc_html_e( 'Site key', 'fast-forms' ); ?></label></th>
					<td><input type="text" class="large-text" id="ff-recaptcha-site-key" name="ff_global_settings[recaptchaSiteKey]" value="<?php echo esc_attr( $settings['recaptchaSiteKey'] ?? '' ); ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-recaptcha-secret-key"><?php esc_html_e( 'Secret key', 'fast-forms' ); ?></label></th>
					<td><input type="password" class="large-text" id="ff-recaptcha-secret-key" name="ff_global_settings[recaptchaSecretKey]" value="<?php echo esc_attr( $settings['recaptchaSecretKey'] ?? '' ); ?>" autocomplete="new-password" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-recaptcha-action"><?php esc_html_e( 'Action name', 'fast-forms' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="ff-recaptcha-action" name="ff_global_settings[recaptchaAction]" value="<?php echo esc_attr( $settings['recaptchaAction'] ?? 'fast_forms_submit' ); ?>" />
						<p class="description"><?php esc_html_e( 'Default: fast_forms_submit', 'fast-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-recaptcha-min-score"><?php esc_html_e( 'Minimum score', 'fast-forms' ); ?></label></th>
					<td>
						<input type="number" step="0.1" min="0" max="1" class="small-text" id="ff-recaptcha-min-score" name="ff_global_settings[recaptchaMinScore]" value="<?php echo esc_attr( (string) ( $settings['recaptchaMinScore'] ?? 0.5 ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Recommended: 0.5. Lower score blocks more submissions, higher allows more spam.', 'fast-forms' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="ff-captcha-panel" data-provider="<?php echo esc_attr( GlobalSettings::CAPTCHA_TURNSTILE ); ?>" <?php echo GlobalSettings::CAPTCHA_TURNSTILE !== $captcha_provider ? 'hidden' : ''; ?>>
			<h3><?php esc_html_e( 'Cloudflare Turnstile', 'fast-forms' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Get keys from Cloudflare dashboard → Turnstile.', 'fast-forms' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ff-turnstile-site-key"><?php esc_html_e( 'Site key', 'fast-forms' ); ?></label></th>
					<td><input type="text" class="large-text" id="ff-turnstile-site-key" name="ff_global_settings[turnstileSiteKey]" value="<?php echo esc_attr( $settings['turnstileSiteKey'] ?? '' ); ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ff-turnstile-secret-key"><?php esc_html_e( 'Secret key', 'fast-forms' ); ?></label></th>
					<td><input type="password" class="large-text" id="ff-turnstile-secret-key" name="ff_global_settings[turnstileSecretKey]" value="<?php echo esc_attr( $settings['turnstileSecretKey'] ?? '' ); ?>" autocomplete="new-password" /></td>
				</tr>
			</table>
		</div>

		<h2><?php esc_html_e( 'File uploads', 'fast-forms' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure where uploaded files from form fields are stored. Path is relative to the WordPress uploads directory.', 'fast-forms' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="ff-upload-path"><?php esc_html_e( 'Default upload path', 'fast-forms' ); ?></label></th>
				<td>
					<input type="text" class="large-text code" id="ff-upload-path" name="ff_global_settings[uploadPath]" value="<?php echo esc_attr( $settings['uploadPath'] ?? UploadPath::DEFAULT_PATTERN ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Available tags:', 'fast-forms' ); ?>
						<?php echo wp_kses_post( UploadPath::merge_tags_list_html() ); ?>
					</p>
					<p class="description">
						<?php
						$upload_dir = wp_upload_dir();
						printf(
							/* translators: %s: example uploads URL */
							esc_html__( 'Example: %s', 'fast-forms' ),
							esc_html( trailingslashit( $upload_dir['baseurl'] ?? '' ) . 'fast-forms/my-form' )
						);
						?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Uninstall', 'fast-forms' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete data', 'fast-forms' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ff_global_settings[deleteDataOnUninstall]" value="1" <?php checked( ! empty( $settings['deleteDataOnUninstall'] ) ); ?> />
						<?php esc_html_e( 'Remove forms, entries, and uploaded files when uninstalling the plugin', 'fast-forms' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'By default data remains in the database after uninstall. Check only for complete removal.', 'fast-forms' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'fast-forms' ) ); ?>
	</form>
</div>
<script>
( function () {
	var select = document.getElementById( 'ff-captcha-provider' );
	if ( ! select ) {
		return;
	}
	function togglePanels() {
		var value = select.value;
		document.querySelectorAll( '.ff-captcha-panel' ).forEach( function ( panel ) {
			panel.hidden = panel.getAttribute( 'data-provider' ) !== value;
		} );
	}
	select.addEventListener( 'change', togglePanels );
	togglePanels();
}() );
</script>
