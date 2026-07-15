<?php
/**
 * Honeypot anti-spam field.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\Support\RedirectResolver;

/**
 * Ukryte pole pułapka na boty — wypełnione pole = cicha odpowiedź sukcesu bez zapisu.
 */
final class Honeypot {

	public const FIELD_NAME = 'ff_hp_check';

	/**
	 * Czy honeypot jest włączony dla formularzy.
	 */
	public static function is_enabled(): bool {
		/**
		 * Włącza ukryte pole honeypot na formularzach frontowych.
		 *
		 * @param bool $enabled Domyślnie true.
		 */
		return (bool) apply_filters( 'ff_enable_honeypot', true );
	}

	/**
	 * @param array<string, mixed> $post_data Dane POST.
	 */
	public static function is_triggered( array $post_data ): bool {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$value = isset( $post_data[ self::FIELD_NAME ] ) ? trim( (string) $post_data[ self::FIELD_NAME ] ) : '';

		return '' !== $value;
	}

	/**
	 * Buduje odpowiedź sukcesu bez zapisu zgłoszenia (nie ujawnia botom blokady).
	 *
	 * @param int $form_id ID formularza.
	 * @return array<string, mixed>
	 */
	public static function fake_success_response( int $form_id ): array {
		$notifications  = FormSettingsStorage::get_notifications( $form_id );
		$show_success   = ! isset( $notifications['showSuccessMessage'] ) || ! empty( $notifications['showSuccessMessage'] );
		$response       = array(
			'success'  => true,
			'message'  => $show_success
				? (string) ( $notifications['successMessage'] ?? __( 'The form has been submitted. Thank you!', 'fast-forms' ) )
				: '',
			'hideForm' => ! empty( $notifications['hideFormAfterSubmit'] ),
		);

		if ( ! empty( $notifications['showExtraContent'] ) && ! empty( $notifications['extraContent'] ) ) {
			$response['extraContent'] = $notifications['extraContent'];
		}

		$redirect = RedirectResolver::payload_from_notifications( $notifications );

		if ( null !== $redirect ) {
			$response['redirect'] = $redirect;
		}

		return $response;
	}
}
