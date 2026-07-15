<?php
/**
 * Form settings storage and sanitization.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\TextEncoding;
use Weblo\FastForms\Support\UploadPath;

/**
 * Odczyt, zapis i sanityzacja ustawień formularza (poza schemą pól).
 */
final class FormSettingsStorage {

	/** @var array<string, mixed> */
	private static array $cache = array();

	/**
	 * Pobiera wszystkie ustawienia formularza.
	 *
	 * @param int $form_id ID formularza.
	 * @return array<string, mixed>
	 */
	public static function get_all( int $form_id ): array {
		return array(
			'email'         => self::get_email( $form_id ),
			'validation'    => self::get_validation( $form_id ),
			'notifications' => self::get_notifications( $form_id ),
			'form'          => self::get_form( $form_id ),
		);
	}

	/**
	 * Zapisuje wszystkie ustawienia formularza.
	 *
	 * @param int                  $form_id  ID formularza.
	 * @param array<string, mixed> $settings Ustawienia.
	 */
	public static function save_all( int $form_id, array $settings ): void {
		if ( isset( $settings['email'] ) && is_array( $settings['email'] ) ) {
			self::save_email( $form_id, $settings['email'] );
		}
		if ( isset( $settings['validation'] ) && is_array( $settings['validation'] ) ) {
			self::save_validation( $form_id, $settings['validation'] );
		}
		if ( isset( $settings['notifications'] ) && is_array( $settings['notifications'] ) ) {
			self::save_notifications( $form_id, $settings['notifications'] );
		}
		if ( isset( $settings['form'] ) && is_array( $settings['form'] ) ) {
			self::save_form( $form_id, $settings['form'] );
		}

		self::flush( $form_id );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_email( int $form_id ): array {
		return self::remember(
			$form_id,
			'email',
			static function () use ( $form_id ): array {
				$stored = get_post_meta( $form_id, FormPostType::META_EMAIL_SETTINGS, true );

				return self::sanitize_email( is_array( $stored ) ? $stored : array() );
			}
		);
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia e-mail.
	 */
	public static function save_email( int $form_id, array $settings ): void {
		update_post_meta( $form_id, FormPostType::META_EMAIL_SETTINGS, self::sanitize_email( $settings ) );
		self::flush_bucket( $form_id, 'email' );
	}

	/**
	 * @param array<string, mixed> $settings Surowe ustawienia.
	 * @return array<string, mixed>
	 */
	public static function sanitize_email( array $settings ): array {
		return array(
			'sendToAdmin'    => ! isset( $settings['sendToAdmin'] ) || ! empty( $settings['sendToAdmin'] ),
			'sendToUser'     => ! empty( $settings['sendToUser'] ),
			'skipEntrySave'  => ! empty( $settings['skipEntrySave'] ),
			'adminEmail'    => sanitize_email( $settings['adminEmail'] ?? get_option( 'admin_email' ) ),
			'fromName'      => sanitize_text_field( $settings['fromName'] ?? get_bloginfo( 'name' ) ),
			'fromEmail'     => sanitize_email( $settings['fromEmail'] ?? get_option( 'admin_email' ) ),
			'replyTo'       => sanitize_email( $settings['replyTo'] ?? '' ),
			'adminSubject'  => sanitize_text_field( $settings['adminSubject'] ?? __( 'New submission: {form:title}', 'fast-forms' ) ),
			'adminMessage'  => wp_kses_post( $settings['adminMessage'] ?? "{all_fields}" ),
			'userSubject'   => sanitize_text_field( $settings['userSubject'] ?? __( 'Submission confirmation', 'fast-forms' ) ),
			'userMessage'   => wp_kses_post( $settings['userMessage'] ?? __( 'Thank you for submitting the form.', 'fast-forms' ) ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_validation( int $form_id ): array {
		return self::remember(
			$form_id,
			'validation',
			static function () use ( $form_id ): array {
				$stored = get_post_meta( $form_id, FormPostType::META_VALIDATION_SETTINGS, true );

				return self::sanitize_validation( is_array( $stored ) ? $stored : array() );
			}
		);
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia walidacji.
	 */
	public static function save_validation( int $form_id, array $settings ): void {
		update_post_meta( $form_id, FormPostType::META_VALIDATION_SETTINGS, self::sanitize_validation( $settings ) );
		self::flush_bucket( $form_id, 'validation' );
	}

	/**
	 * @param array<string, mixed> $settings Surowe ustawienia.
	 * @return array<string, mixed>
	 */
	public static function sanitize_validation( array $settings ): array {
		return array(
			'required'     => TextEncoding::sanitize_field_text( (string) ( $settings['required'] ?? __( 'This field is required.', 'fast-forms' ) ) ),
			'invalidEmail' => TextEncoding::sanitize_field_text( (string) ( $settings['invalidEmail'] ?? __( 'Please enter a valid email address.', 'fast-forms' ) ) ),
			'tooShort'     => TextEncoding::sanitize_field_text( (string) ( $settings['tooShort'] ?? __( 'The value is too short.', 'fast-forms' ) ) ),
			'tooLong'      => TextEncoding::sanitize_field_text( (string) ( $settings['tooLong'] ?? __( 'The value is too long.', 'fast-forms' ) ) ),
			'invalidFile'  => TextEncoding::sanitize_field_text( (string) ( $settings['invalidFile'] ?? __( 'This file type is not allowed.', 'fast-forms' ) ) ),
			'fileTooLarge' => TextEncoding::sanitize_field_text( (string) ( $settings['fileTooLarge'] ?? __( 'The file is too large.', 'fast-forms' ) ) ),
			'submitError'  => TextEncoding::sanitize_field_text( (string) ( $settings['submitError'] ?? __( 'An error occurred while submitting. Please try again.', 'fast-forms' ) ) ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_notifications( int $form_id ): array {
		return self::remember(
			$form_id,
			'notifications',
			static function () use ( $form_id ): array {
				$stored = get_post_meta( $form_id, FormPostType::META_NOTIFICATION_SETTINGS, true );

				return self::sanitize_notifications( is_array( $stored ) ? $stored : array() );
			}
		);
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia powiadomień.
	 */
	public static function save_notifications( int $form_id, array $settings ): void {
		update_post_meta( $form_id, FormPostType::META_NOTIFICATION_SETTINGS, self::sanitize_notifications( $settings ) );
		self::flush_bucket( $form_id, 'notifications' );
	}

	/**
	 * @param array<string, mixed> $settings Surowe ustawienia.
	 * @return array<string, mixed>
	 */
	public static function sanitize_notifications( array $settings ): array {
		return array(
			'showSuccessMessage'  => ! isset( $settings['showSuccessMessage'] ) || ! empty( $settings['showSuccessMessage'] ),
			'successMessage'      => sanitize_text_field( $settings['successMessage'] ?? __( 'The form has been submitted. Thank you!', 'fast-forms' ) ),
			'showExtraContent'    => ! empty( $settings['showExtraContent'] ),
			'extraContent'        => wp_kses_post( $settings['extraContent'] ?? '' ),
			'hideFormAfterSubmit' => ! empty( $settings['hideFormAfterSubmit'] ),
			'enableRedirect'      => ! empty( $settings['enableRedirect'] ),
			'redirectUrl'         => esc_url_raw( $settings['redirectUrl'] ?? '' ),
			'redirectPageId'      => absint( $settings['redirectPageId'] ?? 0 ),
			'redirectDelay'       => absint( $settings['redirectDelay'] ?? 0 ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_form( int $form_id ): array {
		return self::remember(
			$form_id,
			'form',
			static function () use ( $form_id ): array {
				$stored = get_post_meta( $form_id, FormPostType::META_FORM_SETTINGS, true );

				return self::sanitize_form( is_array( $stored ) ? $stored : array() );
			}
		);
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia formularza.
	 */
	public static function save_form( int $form_id, array $settings ): void {
		update_post_meta( $form_id, FormPostType::META_FORM_SETTINGS, self::sanitize_form( $settings ) );
		self::flush_bucket( $form_id, 'form' );
	}

	/**
	 * @param array<string, mixed> $settings Surowe ustawienia.
	 * @return array<string, mixed>
	 */
	public static function sanitize_form( array $settings ): array {
		$value = absint( $settings['cooldownValue'] ?? 0 );
		$unit  = sanitize_key( (string) ( $settings['cooldownUnit'] ?? 'seconds' ) );

		if ( ! in_array( $unit, array( 'seconds', 'minutes', 'hours', 'days' ), true ) ) {
			$unit = 'seconds';
		}

		if ( 0 === $value && isset( $settings['cooldownSeconds'] ) ) {
			$value = absint( $settings['cooldownSeconds'] );
			$unit  = 'seconds';
		}

		$upload_path = trim( (string) ( $settings['uploadPath'] ?? '' ) );

		return array(
			'submitOnce'             => ! empty( $settings['submitOnce'] ),
			'cooldownValue'          => $value,
			'cooldownUnit'           => $unit,
			'cooldownSeconds'        => self::cooldown_to_seconds( $value, $unit ),
			'cooldownMessage'        => sanitize_text_field( $settings['cooldownMessage'] ?? __( 'You can submit this form again shortly.', 'fast-forms' ) ),
			'enableFingerprint'      => ! empty( $settings['enableFingerprint'] ),
			'shortcodeButtonText'    => sanitize_text_field( $settings['shortcodeButtonText'] ?? __( 'Open form', 'fast-forms' ) ),
			'shortcodeButtonClass'   => self::sanitize_class_list( (string) ( $settings['shortcodeButtonClass'] ?? 'button' ) ),
			'shortcodeTriggerClass'  => sanitize_text_field( $settings['shortcodeTriggerClass'] ?? '' ),
			'uploadPath'             => '' !== $upload_path ? UploadPath::sanitize_pattern( $upload_path ) : '',
		);
	}

	/**
	 * Konwertuje wartość blokady czasowej na sekundy.
	 */
	public static function cooldown_to_seconds( int $value, string $unit ): int {
		if ( $value < 1 ) {
			return 0;
		}

		switch ( $unit ) {
			case 'minutes':
				return $value * MINUTE_IN_SECONDS;
			case 'hours':
				return $value * HOUR_IN_SECONDS;
			case 'days':
				return $value * DAY_IN_SECONDS;
			default:
				return $value;
		}
	}

	/**
	 * Sanityzuje listę klas CSS.
	 */
	private static function sanitize_class_list( string $classes ): string {
		$parts     = preg_split( '/\s+/', trim( $classes ) ) ?: array();
		$sanitized = array();

		foreach ( $parts as $part ) {
			$class = sanitize_html_class( $part );

			if ( '' !== $class ) {
				$sanitized[] = $class;
			}
		}

		return implode( ' ', array_unique( $sanitized ) );
	}

	/**
	 * @param callable(): array<string, mixed> $loader
	 * @return array<string, mixed>
	 */
	private static function remember( int $form_id, string $bucket, callable $loader ): array {
		$key = $form_id . ':' . $bucket;

		if ( ! isset( self::$cache[ $key ] ) ) {
			self::$cache[ $key ] = $loader();
		}

		return self::$cache[ $key ];
	}

	private static function flush_bucket( int $form_id, string $bucket ): void {
		unset( self::$cache[ $form_id . ':' . $bucket ] );
	}

	private static function flush( int $form_id ): void {
		foreach ( array( 'email', 'validation', 'notifications', 'form' ) as $bucket ) {
			self::flush_bucket( $form_id, $bucket );
		}
	}
}
