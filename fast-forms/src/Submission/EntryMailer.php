<?php
/**
 * Entry notification emails.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\Support\DebugLog;

/**
 * Wysyła e-maile po zapisaniu zgłoszenia.
 */
final class EntryMailer {

	/**
	 * Wysyła skonfigurowane powiadomienia e-mail.
	 *
	 * @param int                  $form_id  ID formularza.
	 * @param int                  $entry_id ID zgłoszenia.
	 * @param array<string, mixed> $payload  Odpowiedzi pól.
	 * @param array<string, mixed> $schema   Schemа formularza.
	 */
	public static function send( int $form_id, int $entry_id, array $payload, array $schema ): void {
		$settings = FormSettingsStorage::get_email( $form_id );

		if ( ! empty( $settings['sendToAdmin'] ) ) {
			$admin_email = (string) ( $settings['adminEmail'] ?? '' );

			if ( is_email( $admin_email ) ) {
				self::send_message(
					$form_id,
					$entry_id,
					$payload,
					$schema,
					$settings,
					$admin_email,
					(string) ( $settings['adminSubject'] ?? '' ),
					(string) ( $settings['adminMessage'] ?? '' ),
					self::resolve_reply_to( $settings, $payload, $schema )
				);
			}
		}

		if ( ! empty( $settings['sendToUser'] ) ) {
			$user_email = $entry_id > 0 ? (string) get_post_meta( $entry_id, EntryPostType::META_EMAIL, true ) : '';

			if ( ! is_email( $user_email ) ) {
				$user_email = self::find_email_in_payload( $payload, $schema );
			}

			if ( is_email( $user_email ) ) {
				self::send_message(
					$form_id,
					$entry_id,
					$payload,
					$schema,
					$settings,
					$user_email,
					(string) ( $settings['userSubject'] ?? '' ),
					(string) ( $settings['userMessage'] ?? '' ),
					(string) ( $settings['replyTo'] ?? '' )
				);
			}
		}
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia e-mail.
	 * @param array<string, mixed> $payload  Odpowiedzi.
	 * @param array<string, mixed> $schema   Schemа.
	 */
	private static function resolve_reply_to( array $settings, array $payload, array $schema ): string {
		$reply_to = (string) ( $settings['replyTo'] ?? '' );

		if ( is_email( $reply_to ) ) {
			return $reply_to;
		}

		$from_payload = self::find_email_in_payload( $payload, $schema );

		return is_email( $from_payload ) ? $from_payload : '';
	}

	/**
	 * @param array<string, mixed> $payload Odpowiedzi.
	 * @param array<string, mixed> $schema  Schemа.
	 */
	private static function find_email_in_payload( array $payload, array $schema ): string {
		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			if ( 'email' !== ( $field['type'] ?? '' ) ) {
				continue;
			}

			$key   = SchemaFields::field_key( $field );
			$value = isset( $payload[ $key ] ) ? (string) $payload[ $key ] : '';

			if ( is_email( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia e-mail.
	 */
	private static function send_message(
		int $form_id,
		int $entry_id,
		array $payload,
		array $schema,
		array $settings,
		string $to,
		string $subject,
		string $message,
		string $reply_to
	): void {
		$subject = MergeTagParser::parse( $subject, $form_id, $entry_id, $payload, $schema, 'subject' );
		$message = MergeTagParser::parse( $message, $form_id, $entry_id, $payload, $schema, 'html' );

		$from_name  = (string) ( $settings['fromName'] ?? get_bloginfo( 'name' ) );
		$from_email = (string) ( $settings['fromEmail'] ?? get_option( 'admin_email' ) );

		if ( ! is_email( $from_email ) ) {
			$from_email = (string) get_option( 'admin_email' );
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . self::format_address( $from_name, $from_email ),
		);

		if ( is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$sent = wp_mail( $to, $subject, wpautop( $message ), $headers );

		DebugLog::info(
			$sent ? 'Entry email sent' : 'Entry email failed',
			array(
				'form_id'  => $form_id,
				'entry_id' => $entry_id,
				'to'       => $to,
			)
		);
	}

	private static function format_address( string $name, string $email ): string {
		$name = trim( str_replace( array( "\r", "\n" ), '', $name ) );

		if ( '' === $name ) {
			return $email;
		}

		return sprintf( '%s <%s>', $name, $email );
	}
}
