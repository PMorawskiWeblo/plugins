<?php
/**
 * Form builder translatable strings and field help texts.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\Submission\SchemaFields;

/**
 * Centralne etykiety, domyślne wartości i podpowiedzi buildera (msgid po angielsku).
 */
final class BuilderI18n {

	/**
	 * Domyślny tekst przycisku wysyłki.
	 */
	public static function default_submit_text(): string {
		return __( 'Send', 'fast-forms' );
	}

	/**
	 * Domyślny tekst stanu ładowania.
	 */
	public static function default_loading_text(): string {
		return __( 'Sending...', 'fast-forms' );
	}

	/**
	 * Teksty przekazywane do JavaScript buildera.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_js_i18n(): array {
		return array(
			'addRow'              => __( 'Add row', 'fast-forms' ),
			'rowLabelPrefix'      => __( 'Row', 'fast-forms' ),
			'moveRowUp'           => __( 'Move row up', 'fast-forms' ),
			'moveRowDown'         => __( 'Move row down', 'fast-forms' ),
			'addColumn'           => __( 'Add column', 'fast-forms' ),
			'columnLabelPrefix'   => __( 'Column', 'fast-forms' ),
			'columnSettingsIntro' => __( 'Column settings', 'fast-forms' ),
			'deleteRow'           => __( 'Delete row', 'fast-forms' ),
			'deleteColumn'        => __( 'Delete column', 'fast-forms' ),
			'deleteField'         => __( 'Delete field', 'fast-forms' ),
			'saveForm'            => __( 'Save form', 'fast-forms' ),
			'saving'              => __( 'Saving...', 'fast-forms' ),
			'saved'               => __( 'Saved.', 'fast-forms' ),
			'saveError'           => __( 'Save failed. Please try again.', 'fast-forms' ),
			'loadingSchema'       => __( 'Loading form…', 'fast-forms' ),
			'loadSchemaError'     => __( 'Could not load the form. Refresh the page and try again.', 'fast-forms' ),
			'fieldsPanel'         => __( 'Fields', 'fast-forms' ),
			'settingsPanel'       => __( 'Field settings', 'fast-forms' ),
			'canvas'              => __( 'Form preview', 'fast-forms' ),
			'noFieldSelected'     => __( 'Select a field to edit its settings.', 'fast-forms' ),
			'noSelection'         => __( 'Select a field, row, or column to edit its settings.', 'fast-forms' ),
			'rowSettingsIntro'    => __( 'Row settings', 'fast-forms' ),
			'liveValidationLabel' => __( 'Live validation', 'fast-forms' ),
			'dragFieldHere'       => __( 'Drag a field here', 'fast-forms' ),
			'emptyCanvas'         => __( 'Add a row to start building your form.', 'fast-forms' ),
			'label'               => __( 'Label', 'fast-forms' ),
			'name'                => __( 'Name attribute', 'fast-forms' ),
			'required'            => __( 'Required', 'fast-forms' ),
			'placeholder'         => __( 'Placeholder', 'fast-forms' ),
			'defaultValue'        => __( 'Default value', 'fast-forms' ),
			'cssClass'            => __( 'CSS class', 'fast-forms' ),
			'htmlId'              => __( 'HTML ID', 'fast-forms' ),
			'minLength'           => __( 'Min. length', 'fast-forms' ),
			'maxLength'           => __( 'Max. length', 'fast-forms' ),
			'rows'                => __( 'Rows (textarea)', 'fast-forms' ),
			'min'                 => __( 'Min. value', 'fast-forms' ),
			'max'                 => __( 'Max. value', 'fast-forms' ),
			'minStars'            => __( 'Min. stars', 'fast-forms' ),
			'maxStars'            => __( 'Max. stars', 'fast-forms' ),
			'step'                => __( 'Step', 'fast-forms' ),
			'options'             => __( 'Options', 'fast-forms' ),
			'optionLabel'         => __( 'Option label', 'fast-forms' ),
			'optionValue'         => __( 'Option value', 'fast-forms' ),
			'optionDefault'       => __( 'Default', 'fast-forms' ),
			'addOption'           => __( 'Add option', 'fast-forms' ),
			'removeOption'        => __( 'Remove option', 'fast-forms' ),
			'dragOption'          => __( 'Drag to reorder', 'fast-forms' ),
			'allowMultiple'       => __( 'Allow multiple selections', 'fast-forms' ),
			'allowMultipleFiles'  => __( 'Allow multiple files', 'fast-forms' ),
			'minSelections'       => __( 'Min. selections', 'fast-forms' ),
			'maxSelections'       => __( 'Max. selections', 'fast-forms' ),
			'maxFiles'            => __( 'Max. number of files', 'fast-forms' ),
			'minFiles'            => __( 'Min. number of files', 'fast-forms' ),
			'fileButtonText'      => __( 'Choose file button text', 'fast-forms' ),
			'choiceLayout'        => __( 'Layout', 'fast-forms' ),
			'choiceLayoutVertical' => __( 'Vertical', 'fast-forms' ),
			'choiceLayoutHorizontal' => __( 'Horizontal', 'fast-forms' ),
			'showUploadHint'      => __( 'Show upload rules', 'fast-forms' ),
			'defaultFileButtonText' => __( 'Choose file', 'fast-forms' ),
			'defaultFilesButtonText' => __( 'Choose files', 'fast-forms' ),
			'allowedTypes'        => __( 'Allowed file types', 'fast-forms' ),
			'maxFileSize'         => __( 'Max. file size (KB)', 'fast-forms' ),
			'consentTextLabel'    => __( 'Consent text', 'fast-forms' ),
			'contentTextLabel'    => __( 'Content', 'fast-forms' ),
			'contentDefaultText'  => __( 'Enter information text for form visitors.', 'fast-forms' ),
			'contentOptionalLabel' => __( 'Optional heading (label)', 'fast-forms' ),
			'consentHtmlHint'     => __( 'Allowed HTML tags: br, strong, em, a, p, span (with href/title/target/rel/class).', 'fast-forms' ),
			'hideLabel'           => __( 'Hide label', 'fast-forms' ),
			'submitTextLabel'     => __( 'Submit button text', 'fast-forms' ),
			'loadingTextLabel'    => __( 'Loading text', 'fast-forms' ),
			'defaultSubmitText'   => self::default_submit_text(),
			'defaultLoadingText'  => self::default_loading_text(),
			'confirmDeleteRow'    => __( 'Delete this row?', 'fast-forms' ),
			'confirmDeleteCol'    => __( 'Delete this column?', 'fast-forms' ),
			'confirmDeleteFld'    => __( 'Delete this field?', 'fast-forms' ),
			'copyShortcode'       => __( 'Copy shortcode', 'fast-forms' ),
			'shortcodeCopied'     => __( 'Shortcode copied to clipboard!', 'fast-forms' ),
			'shortcodeCopyFailed' => __( 'Could not copy shortcode. Please copy it manually.', 'fast-forms' ),
			'tabForm'             => __( 'Form', 'fast-forms' ),
			'tabEmail'            => __( 'Email', 'fast-forms' ),
			'tabValidation'       => __( 'Validation', 'fast-forms' ),
			'tabNotifications'    => __( 'Notifications', 'fast-forms' ),
			'tabSettings'         => __( 'Settings', 'fast-forms' ),
			'tabDocumentation'    => __( 'Documentation', 'fast-forms' ),
			'selectPage'          => __( '— Select page —', 'fast-forms' ),
			'searchPages'         => __( 'Search pages…', 'fast-forms' ),
			'tooltips'            => self::get_field_tooltips(),
		);
	}

	/**
	 * Podpowiedzi do ustawień pól w panelu bocznym buildera.
	 *
	 * @return array<string, string>
	 */
	public static function get_field_tooltips(): array {
		return array(
			'label'        => __( 'Visible field label shown to visitors on the frontend.', 'fast-forms' ),
			'name'         => __( 'HTML name attribute used when saving submission data. Use lowercase letters, numbers, and underscores.', 'fast-forms' ),
			'required'     => __( 'When enabled, the field must be filled in before the form can be submitted.', 'fast-forms' ),
			'placeholder'  => __( 'Hint text displayed inside the input before the user types.', 'fast-forms' ),
			'defaultValue' => __( 'Pre-filled value when the form is first displayed.', 'fast-forms' ),
			'cssClass'     => __( 'Additional CSS class added to the field wrapper for custom styling.', 'fast-forms' ),
			'htmlId'       => __( 'Custom HTML id on the field wrapper. Leave empty for no id. Must be unique within the form.', 'fast-forms' ),
			'minLength'    => __( 'Minimum number of characters required for text fields.', 'fast-forms' ),
			'maxLength'    => __( 'Maximum number of characters allowed for text fields.', 'fast-forms' ),
			'rows'         => __( 'Number of visible lines for a textarea field.', 'fast-forms' ),
			'min'          => __( 'Minimum allowed numeric value.', 'fast-forms' ),
			'max'          => __( 'Maximum allowed numeric value.', 'fast-forms' ),
			'starMin'      => __( 'Lowest star value (default: 1).', 'fast-forms' ),
			'starMax'      => __( 'Highest star value (default: 5).', 'fast-forms' ),
			'step'         => __( 'Increment step for number or range inputs.', 'fast-forms' ),
			'options'      => __( 'Add, edit, reorder, or remove choices. Label is shown to visitors; value is saved in submissions.', 'fast-forms' ),
			'optionDefault' => __( 'Pre-select this option when the form loads. For single choice fields only one default is allowed.', 'fast-forms' ),
			'allowMultiple' => __( 'When enabled, visitors can select more than one option using checkboxes.', 'fast-forms' ),
			'allowMultipleFiles' => __( 'When enabled, visitors can upload more than one file in this field.', 'fast-forms' ),
			'minSelections' => __( 'Minimum number of options that must be selected. Used when multiple selection is enabled.', 'fast-forms' ),
			'maxSelections' => __( 'Maximum number of options that can be selected. Leave empty for no limit.', 'fast-forms' ),
			'maxFiles'      => __( 'Maximum number of files that can be uploaded. Leave empty for no limit.', 'fast-forms' ),
			'minFiles'      => __( 'Minimum number of files required when multiple upload is enabled. Leave empty to use the required flag only (minimum 1).', 'fast-forms' ),
			'fileButtonText' => __( 'Custom label on the file upload button. Leave empty for the default text.', 'fast-forms' ),
			'choiceLayout'   => __( 'Display options or uploaded files vertically (stacked) or horizontally (in a row).', 'fast-forms' ),
			'choiceLayoutSelect' => __( 'Vertical keeps a dropdown. Horizontal displays options in a row (like radio buttons).', 'fast-forms' ),
			'showUploadHint' => __( 'When enabled, shows allowed file types and size limits below the upload button.', 'fast-forms' ),
			'allowedTypes' => __( 'Comma-separated file extensions without dots, e.g. pdf,jpg,png.', 'fast-forms' ),
			'maxFileSize'  => __( 'Maximum upload size in kilobytes (KB).', 'fast-forms' ),
			'consentText'  => __( 'Consent text shown next to the checkbox. You can use HTML (e.g. br, a, strong) — see the hint below the field.', 'fast-forms' ),
			'contentText'  => __( 'Information text shown to visitors. This field is display-only and is not saved in submissions.', 'fast-forms' ),
			'contentOptionalLabel' => __( 'Optional heading displayed above the content when “Hide label” is off.', 'fast-forms' ),
			'hideLabel'    => __( 'Hide the field label above the control. For consent, only the checkbox and consent text are shown.', 'fast-forms' ),
			'submitText'   => __( 'Text displayed on the submit button before sending.', 'fast-forms' ),
			'loadingText'  => __( 'Text displayed on the button while the form is being submitted.', 'fast-forms' ),
			'liveValidation' => __( 'When enabled, the submit button is disabled and faded until all required fields are valid.', 'fast-forms' ),
			'rowCssClass'  => __( 'Additional CSS class added to the row wrapper on the frontend.', 'fast-forms' ),
			'rowHtmlId'    => __( 'Custom HTML id on the row wrapper. Leave empty for no id.', 'fast-forms' ),
			'columnCssClass' => __( 'Additional CSS class added to the column wrapper on the frontend.', 'fast-forms' ),
			'columnHtmlId'   => __( 'Custom HTML id on the column wrapper. Leave empty for no id.', 'fast-forms' ),
		);
	}

	/**
	 * Etykiety typów pól w palecie.
	 *
	 * @return array<string, string>
	 */
	public static function get_field_type_labels(): array {
		return array(
			'text'     => __( 'Text', 'fast-forms' ),
			'email'    => __( 'Email', 'fast-forms' ),
			'tel'      => __( 'Phone', 'fast-forms' ),
			'url'      => __( 'URL', 'fast-forms' ),
			'number'   => __( 'Number', 'fast-forms' ),
			'range'    => __( 'Range', 'fast-forms' ),
			'star_rating' => __( 'Star rating', 'fast-forms' ),
			'date'     => __( 'Date', 'fast-forms' ),
			'textarea' => __( 'Textarea', 'fast-forms' ),
			'select'   => __( 'Select', 'fast-forms' ),
			'radio'    => __( 'Radio buttons', 'fast-forms' ),
			'checkbox' => __( 'Checkbox', 'fast-forms' ),
			'consent'  => __( 'Consent', 'fast-forms' ),
			'content'  => __( 'Content', 'fast-forms' ),
			'file'     => __( 'File', 'fast-forms' ),
			'submit'   => __( 'Submit button', 'fast-forms' ),
		);
	}

	/**
	 * Podpowiedzi do zakładek ustawień formularza (email, walidacja itd.).
	 *
	 * @return array<string, string>
	 */
	public static function get_settings_tooltips(): array {
		return array(
			'emailSending'           => __( 'Choose whether to notify the administrator and/or the person who submitted the form.', 'fast-forms' ),
			'skipEntrySave'          => __( 'When enabled, submissions are not stored in the submissions list. Emails are still sent if configured below.', 'fast-forms' ),
			'adminEmail'             => __( 'Email address that receives new submission notifications.', 'fast-forms' ),
			'fromName'               => __( 'Sender name shown in outgoing emails.', 'fast-forms' ),
			'fromEmail'              => __( 'Sender email address used in outgoing messages.', 'fast-forms' ),
			'replyTo'                => __( 'Reply-To address for admin notifications. Leave empty to use the submitter email when available.', 'fast-forms' ),
			'adminSubject'           => __( 'Subject line for the administrator notification email. Merge tags are supported.', 'fast-forms' ),
			'adminMessage'           => __( 'HTML body for the administrator email. Use {all_fields} to include all answers.', 'fast-forms' ),
			'userSubject'            => __( 'Subject line for the confirmation email sent to the user.', 'fast-forms' ),
			'userMessage'            => __( 'HTML body for the user confirmation email.', 'fast-forms' ),
			'validationRequired'     => __( 'Message shown when a required field is empty.', 'fast-forms' ),
			'validationInvalidEmail' => __( 'Message shown when the email format is invalid.', 'fast-forms' ),
			'validationTooShort'     => __( 'Message shown when the value is shorter than allowed.', 'fast-forms' ),
			'validationTooLong'        => __( 'Message shown when the value is longer than allowed.', 'fast-forms' ),
			'validationInvalidFile'  => __( 'Message shown when the uploaded file type is not allowed.', 'fast-forms' ),
			'validationFileTooLarge' => __( 'Message shown when the uploaded file exceeds the size limit.', 'fast-forms' ),
			'validationSubmitError'  => __( 'Generic message shown when submission fails.', 'fast-forms' ),
			'showSuccessMessage'     => __( 'Display a success message after the form is submitted successfully.', 'fast-forms' ),
			'successMessage'         => __( 'Text of the success message shown on the frontend.', 'fast-forms' ),
			'showExtraContent'       => __( 'Show additional HTML content below the success message.', 'fast-forms' ),
			'extraContent'           => __( 'Extra HTML content displayed after a successful submission.', 'fast-forms' ),
			'hideFormAfterSubmit'    => __( 'Hide the form fields after successful submission.', 'fast-forms' ),
			'enableRedirect'         => __( 'Redirect the visitor to another page after successful submission.', 'fast-forms' ),
			'redirectUrl'            => __( 'Full URL to redirect to after submission.', 'fast-forms' ),
			'redirectUrlPriority'    => __( 'If a custom URL is set, it is used instead of the selected page below.', 'fast-forms' ),
			'redirectPage'           => __( 'WordPress page to redirect to when no custom URL is set.', 'fast-forms' ),
			'redirectDelay'          => __( 'Delay in seconds before redirect starts.', 'fast-forms' ),
			'submitOnce'             => __( 'Allow only one submission per browser (cookie-based).', 'fast-forms' ),
			'cooldownSeconds'        => __( 'Minimum time before the same visitor can submit again.', 'fast-forms' ),
			'cooldownMessage'        => __( 'Message shown when submission is blocked by limits.', 'fast-forms' ),
			'enableFingerprint'      => __( 'Use a browser fingerprint (IP, user agent, email) for more accurate submission limits.', 'fast-forms' ),
			'uploadPath'             => __( 'Custom folder for uploaded files from this form. Leave empty to use the global default from plugin settings.', 'fast-forms' ),
			'uploadPathTags'         => __( 'Relative to wp-content/uploads/. Tags: {form_slug}, {form_id}, {form_title}. Files are stored in YYYY/MM/entry-ID subfolders.', 'fast-forms' ),
			'shortcode'              => __( 'Copy a shortcode and paste it into a page or post to display the form.', 'fast-forms' ),
			'shortcodeInline'        => __( 'Displays the full form directly on the page.', 'fast-forms' ),
			'shortcodeButton'        => __( 'Shows a button that opens the form in a modal window.', 'fast-forms' ),
			'shortcodeTrigger'       => __( 'Opens the form in a modal when a page element matching the CSS selector is clicked.', 'fast-forms' ),
			'shortcodeButtonText'    => __( 'Text displayed on the button that opens the modal.', 'fast-forms' ),
			'shortcodeButtonClass'   => __( 'Additional CSS classes for the modal button (e.g. btn btn-primary).', 'fast-forms' ),
			'shortcodeTriggerClass'  => __( 'CSS selector of the element that opens the modal (e.g. .open-contact-form).', 'fast-forms' ),
			'mergeTags'              => __( 'Available merge tags:', 'fast-forms' ),
		);
	}

	/**
	 * Etykiety pól walidacji w zakładce Validation.
	 *
	 * @return array<string, string>
	 */
	public static function get_validation_field_labels(): array {
		return array(
			'required'     => __( 'Required field', 'fast-forms' ),
			'invalidEmail' => __( 'Invalid email', 'fast-forms' ),
			'tooShort'     => __( 'Value too short', 'fast-forms' ),
			'tooLong'      => __( 'Value too long', 'fast-forms' ),
			'invalidFile'  => __( 'Invalid file', 'fast-forms' ),
			'fileTooLarge' => __( 'File too large', 'fast-forms' ),
			'submitError'  => __( 'Submit error', 'fast-forms' ),
		);
	}

	/**
	 * Ikona podpowiedzi (dashicons) do etykiet w tabeli ustawień.
	 *
	 * @param string $tooltip Tekst podpowiedzi.
	 */
	public static function help_icon( string $tooltip ): string {
		if ( '' === $tooltip ) {
			return '';
		}

		return sprintf(
			' <span class="ff-settings-help dashicons dashicons-info" tabindex="0" role="button" data-tooltip="%1$s" aria-label="%1$s"></span>',
			esc_attr( $tooltip )
		);
	}

	/**
	 * Akapit z opisem pola ustawień (widoczny pod polem).
	 *
	 * @param string $text Tekst opisu.
	 */
	public static function field_description( string $text ): string {
		if ( '' === $text ) {
			return '';
		}

		return sprintf( '<p class="description ff-settings-tip">%s</p>', esc_html( $text ) );
	}

	/**
	 * Lista dostępnych merge tagów dla formularza (HTML pod polem e-mail).
	 *
	 * @param int                  $form_id ID formularza.
	 * @param array<string, mixed> $schema  Schemа formularza.
	 */
	public static function merge_tags_list_html( int $form_id, array $schema ): string {
		$tags = array(
			'{form:title}',
			'{form:id}',
			'{entry:id}',
			'{entry:date}',
			'{all_fields}',
		);

		$field_tags = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key = SchemaFields::field_key( $field );

			if ( '' === $key ) {
				continue;
			}

			$tag = '{field:' . $key . '}';

			if ( isset( $field_tags[ $tag ] ) ) {
				continue;
			}

			$field_tags[ $tag ] = trim( (string) ( $field['label'] ?? '' ) );
		}

		$items = array();

		foreach ( $tags as $tag ) {
			$items[] = '<code>' . esc_html( $tag ) . '</code>';
		}

		foreach ( $field_tags as $tag => $label ) {
			$display = '' !== $label ? $label . ' ' . $tag : $tag;
			$items[] = '<code>' . esc_html( $display ) . '</code>';
		}

		return sprintf(
			'<p class="description ff-settings-tip ff-merge-tags-list"><strong>%s</strong> <span class="ff-merge-tags-list__items">%s</span></p>',
			esc_html__( 'Available merge tags:', 'fast-forms' ),
			implode( ' ', $items )
		);
	}

	/**
	 * Jednostki blokady czasowej.
	 *
	 * @return array<string, string>
	 */
	public static function get_cooldown_units(): array {
		return array(
			'seconds' => __( 'Seconds', 'fast-forms' ),
			'minutes' => __( 'Minutes', 'fast-forms' ),
			'hours'   => __( 'Hours', 'fast-forms' ),
			'days'    => __( 'Days', 'fast-forms' ),
		);
	}
}
