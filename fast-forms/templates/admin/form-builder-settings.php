<?php
/**
 * Form builder settings tabs partial.
 *
 * @package FastForms
 *
 * @var array<string, mixed> $form_settings Ustawienia formularza.
 */

use Weblo\FastForms\FormBuilder\BuilderI18n;
use Weblo\FastForms\FormBuilder\FormSchemaStorage;
use Weblo\FastForms\Frontend\ShortcodeAttributes;
use Weblo\FastForms\Support\GlobalSettings;
use Weblo\FastForms\Support\UploadPath;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email         = $form_settings['email'] ?? array();
$validation    = $form_settings['validation'] ?? array();
$notifications = $form_settings['notifications'] ?? array();
$form_opts     = $form_settings['form'] ?? array();
$tips          = BuilderI18n::get_settings_tooltips();
$form_schema   = FormSchemaStorage::get( $post->ID );
$merge_tags_html = BuilderI18n::merge_tags_list_html( $post->ID, $form_schema );
$cooldown_value  = (int) ( $form_opts['cooldownValue'] ?? $form_opts['cooldownSeconds'] ?? 0 );
$cooldown_unit   = (string) ( $form_opts['cooldownUnit'] ?? 'seconds' );
$cooldown_units  = BuilderI18n::get_cooldown_units();
$shortcode_inline  = ShortcodeAttributes::build( $post->ID, 'inline', $form_opts );
$shortcode_button  = ShortcodeAttributes::build( $post->ID, 'button', $form_opts );
$shortcode_trigger = ShortcodeAttributes::build( $post->ID, 'trigger', $form_opts );
$global_upload_path = (string) ( GlobalSettings::get()['uploadPath'] ?? UploadPath::DEFAULT_PATTERN );
$form_upload_path   = trim( (string) ( $form_opts['uploadPath'] ?? '' ) );
$effective_upload_path = UploadPath::resolve_for_form( $post->ID );
$effective_upload_url  = UploadPath::get_form_base_url( $post->ID );
$redirect_page_id      = absint( $notifications['redirectPageId'] ?? 0 );
$redirect_page_title   = '';

if ( $redirect_page_id > 0 ) {
	$redirect_page = get_post( $redirect_page_id );

	if ( $redirect_page instanceof \WP_Post && 'page' === $redirect_page->post_type ) {
		$redirect_page_title = $redirect_page->post_title;
	} else {
		$redirect_page_id = 0;
	}
}
?>
<div id="ff-tab-email" class="ff-builder-tab-panel ff-settings-panel">
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Email sending', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['emailSending'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="sendToAdmin" data-ff-group="email" <?php checked( ! empty( $email['sendToAdmin'] ) ); ?> /> <?php esc_html_e( 'Send to administrator', 'fast-forms' ); ?></label><br>
				<label><input type="checkbox" data-ff-setting="sendToUser" data-ff-group="email" <?php checked( ! empty( $email['sendToUser'] ) ); ?> /> <?php esc_html_e( 'Send confirmation to user', 'fast-forms' ); ?></label><br>
				<label><input type="checkbox" data-ff-setting="skipEntrySave" data-ff-group="email" <?php checked( ! empty( $email['skipEntrySave'] ) ); ?> /> <?php esc_html_e( 'Do not save submission to database', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::field_description( $tips['skipEntrySave'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-adminEmail"><?php esc_html_e( 'Administrator email', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['adminEmail'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="email" class="regular-text" id="ff-email-adminEmail" data-ff-setting="adminEmail" data-ff-group="email" value="<?php echo esc_attr( $email['adminEmail'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-fromName"><?php esc_html_e( 'From name', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['fromName'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="regular-text" id="ff-email-fromName" data-ff-setting="fromName" data-ff-group="email" value="<?php echo esc_attr( $email['fromName'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-fromEmail"><?php esc_html_e( 'From email', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['fromEmail'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="email" class="regular-text" id="ff-email-fromEmail" data-ff-setting="fromEmail" data-ff-group="email" value="<?php echo esc_attr( $email['fromEmail'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-replyTo"><?php esc_html_e( 'Reply-To', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['replyTo'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="email" class="regular-text" id="ff-email-replyTo" data-ff-setting="replyTo" data-ff-group="email" value="<?php echo esc_attr( $email['replyTo'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-adminSubject"><?php esc_html_e( 'Admin subject', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['adminSubject'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text" id="ff-email-adminSubject" data-ff-setting="adminSubject" data-ff-group="email" value="<?php echo esc_attr( $email['adminSubject'] ?? '' ); ?>" />
				<?php echo $merge_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-adminMessage"><?php esc_html_e( 'Admin message', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['adminMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<textarea class="large-text" rows="5" id="ff-email-adminMessage" data-ff-setting="adminMessage" data-ff-group="email"><?php echo esc_textarea( $email['adminMessage'] ?? '' ); ?></textarea>
				<?php echo BuilderI18n::field_description( $tips['adminMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $merge_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-userSubject"><?php esc_html_e( 'User subject', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['userSubject'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text" id="ff-email-userSubject" data-ff-setting="userSubject" data-ff-group="email" value="<?php echo esc_attr( $email['userSubject'] ?? '' ); ?>" />
				<?php echo $merge_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-email-userMessage"><?php esc_html_e( 'User message', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['userMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<textarea class="large-text" rows="5" id="ff-email-userMessage" data-ff-setting="userMessage" data-ff-group="email"><?php echo esc_textarea( $email['userMessage'] ?? '' ); ?></textarea>
				<?php echo BuilderI18n::field_description( $tips['userMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $merge_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
	</table>
</div>

<div id="ff-tab-validation" class="ff-builder-tab-panel ff-settings-panel">
	<table class="form-table" role="presentation">
		<?php
		$validation_fields = BuilderI18n::get_validation_field_labels();
		$validation_tips   = array(
			'required'     => 'validationRequired',
			'invalidEmail' => 'validationInvalidEmail',
			'tooShort'     => 'validationTooShort',
			'tooLong'      => 'validationTooLong',
			'invalidFile'  => 'validationInvalidFile',
			'fileTooLarge' => 'validationFileTooLarge',
			'submitError'  => 'validationSubmitError',
		);
		foreach ( $validation_fields as $key => $label ) :
			$tip_key = $validation_tips[ $key ] ?? '';
			$tip     = $tip_key ? ( $tips[ $tip_key ] ?? '' ) : '';
			?>
		<tr>
			<th scope="row">
				<label for="ff-validation-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php echo BuilderI18n::help_icon( $tip ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text" id="ff-validation-<?php echo esc_attr( $key ); ?>" data-ff-setting="<?php echo esc_attr( $key ); ?>" data-ff-group="validation" value="<?php echo esc_attr( $validation[ $key ] ?? '' ); ?>" />
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>

<div id="ff-tab-notifications" class="ff-builder-tab-panel ff-settings-panel">
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Success', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['showSuccessMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="showSuccessMessage" data-ff-group="notifications" <?php checked( ! isset( $notifications['showSuccessMessage'] ) || ! empty( $notifications['showSuccessMessage'] ) ); ?> /> <?php esc_html_e( 'Show success message', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-notifications-successMessage"><?php esc_html_e( 'Success message', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['successMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text" id="ff-notifications-successMessage" data-ff-setting="successMessage" data-ff-group="notifications" value="<?php echo esc_attr( $notifications['successMessage'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Extra content', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['showExtraContent'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="showExtraContent" data-ff-group="notifications" <?php checked( ! empty( $notifications['showExtraContent'] ) ); ?> /> <?php esc_html_e( 'Show extra content after submit', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-notifications-extraContent"><?php esc_html_e( 'Extra content HTML', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['extraContent'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<textarea class="large-text" rows="4" id="ff-notifications-extraContent" data-ff-setting="extraContent" data-ff-group="notifications"><?php echo esc_textarea( $notifications['extraContent'] ?? '' ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'After submit', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['hideFormAfterSubmit'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="hideFormAfterSubmit" data-ff-group="notifications" <?php checked( ! empty( $notifications['hideFormAfterSubmit'] ) ); ?> /> <?php esc_html_e( 'Hide form after submit', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Redirect', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['enableRedirect'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="enableRedirect" data-ff-group="notifications" <?php checked( ! empty( $notifications['enableRedirect'] ) ); ?> /> <?php esc_html_e( 'Enable redirect', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-notifications-redirectUrl"><?php esc_html_e( 'Redirect URL', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['redirectUrl'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="url" class="large-text" id="ff-notifications-redirectUrl" data-ff-setting="redirectUrl" data-ff-group="notifications" value="<?php echo esc_attr( $notifications['redirectUrl'] ?? '' ); ?>" />
				<?php echo BuilderI18n::field_description( $tips['redirectUrlPriority'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-notifications-redirectPageId"><?php esc_html_e( 'Redirect page', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['redirectPage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<select id="ff-notifications-redirectPageId" class="ff-page-select" data-ff-setting="redirectPageId" data-ff-group="notifications" data-ff-input-type="number">
					<option value=""><?php esc_html_e( '— Select page —', 'fast-forms' ); ?></option>
					<?php if ( $redirect_page_id > 0 ) : ?>
						<option value="<?php echo esc_attr( (string) $redirect_page_id ); ?>" selected><?php echo esc_html( $redirect_page_title ); ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-notifications-redirectDelay"><?php esc_html_e( 'Redirect delay (sec.)', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['redirectDelay'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="number" min="0" class="small-text" id="ff-notifications-redirectDelay" data-ff-setting="redirectDelay" data-ff-group="notifications" value="<?php echo esc_attr( (string) ( $notifications['redirectDelay'] ?? 0 ) ); ?>" />
			</td>
		</tr>
	</table>
</div>

<div id="ff-tab-settings" class="ff-builder-tab-panel ff-settings-panel">
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Submit limits', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['submitOnce'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="submitOnce" data-ff-group="form" <?php checked( ! empty( $form_opts['submitOnce'] ) ); ?> /> <?php esc_html_e( 'Allow only one submission', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-form-cooldownValue"><?php esc_html_e( 'Cooldown', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['cooldownSeconds'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="number" min="0" class="small-text" id="ff-form-cooldownValue" data-ff-setting="cooldownValue" data-ff-group="form" value="<?php echo esc_attr( (string) $cooldown_value ); ?>" />
				<select id="ff-form-cooldownUnit" data-ff-setting="cooldownUnit" data-ff-group="form">
					<?php foreach ( $cooldown_units as $unit_key => $unit_label ) : ?>
						<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $cooldown_unit, $unit_key ); ?>><?php echo esc_html( $unit_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo BuilderI18n::field_description( $tips['cooldownSeconds'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-form-cooldownMessage"><?php esc_html_e( 'Cooldown message', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['cooldownMessage'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text" id="ff-form-cooldownMessage" data-ff-setting="cooldownMessage" data-ff-group="form" value="<?php echo esc_attr( $form_opts['cooldownMessage'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ff-form-uploadPath"><?php esc_html_e( 'File upload path', 'fast-forms' ); ?></label>
				<?php echo BuilderI18n::help_icon( $tips['uploadPath'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<input type="text" class="large-text code" id="ff-form-uploadPath" data-ff-setting="uploadPath" data-ff-group="form" value="<?php echo esc_attr( $form_upload_path ); ?>" placeholder="<?php echo esc_attr( $global_upload_path ); ?>" />
				<?php echo BuilderI18n::field_description( $tips['uploadPathTags'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="description ff-upload-path-preview">
					<strong><?php esc_html_e( 'Effective path for this form:', 'fast-forms' ); ?></strong>
					<code id="ff-upload-path-effective"><?php echo esc_html( $effective_upload_path ); ?></code>
				</p>
				<?php if ( '' !== $effective_upload_url ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: uploads URL */
							esc_html__( 'Full URL: %s', 'fast-forms' ),
							'<code id="ff-upload-path-url">' . esc_html( $effective_upload_url ) . '</code>'
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Fingerprint', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['enableFingerprint'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<label><input type="checkbox" data-ff-setting="enableFingerprint" data-ff-group="form" <?php checked( ! empty( $form_opts['enableFingerprint'] ) ); ?> /> <?php esc_html_e( 'Enable submission fingerprint', 'fast-forms' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Shortcodes', 'fast-forms' ); ?>
				<?php echo BuilderI18n::help_icon( $tips['shortcode'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</th>
			<td>
				<?php
				$form_id           = (int) $post->ID;
				$id_suffix         = '';
				$compact           = false;
				include FF_PLUGIN_DIR . 'templates/admin/partials/form-shortcodes.php';
				?>
			</td>
		</tr>
	</table>
</div>
