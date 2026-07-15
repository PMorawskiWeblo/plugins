<?php
/**
 * Public form submit CSRF token (cache-safe, user-independent).
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

/**
 * Token wysyłki niezależny od zalogowanego użytkownika — działa z cache strony.
 */
final class SubmitToken {

	/**
	 * Generuje token dla formularza.
	 *
	 * @param int $form_id ID formularza.
	 */
	public static function create( int $form_id ): string {
		return self::hash( $form_id, self::get_tick() );
	}

	/**
	 * Weryfikuje token wysyłki.
	 *
	 * @param int    $form_id ID formularza.
	 * @param string $token   Token z żądania.
	 */
	public static function verify( int $form_id, string $token ): bool {
		if ( '' === $token ) {
			return false;
		}

		$tick = self::get_tick();

		if ( hash_equals( self::hash( $form_id, $tick ), $token ) ) {
			return true;
		}

		// Poprzedni tick — np. wysyłka tuż po odświeżeniu ticka.
		return hash_equals( self::hash( $form_id, $tick - 1 ), $token );
	}

	/**
	 * Bieżący tick (jak w WordPress nonce — ~12 h).
	 */
	private static function get_tick(): int {
		return (int) ceil( time() / ( DAY_IN_SECONDS / 2 ) );
	}

	/**
	 * @param int $form_id ID formularza.
	 * @param int $tick    Tick czasowy.
	 */
	private static function hash( int $form_id, int $tick ): string {
		return substr(
			hash_hmac( 'sha256', $form_id . '|' . $tick, wp_salt( 'ff_submit' ) ),
			0,
			32
		);
	}
}
