<?php
/**
 * Cloudflare Turnstile verification.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Integrations;

use Weblo\FastForms\Support\DebugLog;
use Weblo\FastForms\Support\GlobalSettings;

/**
 * Weryfikuje token Cloudflare Turnstile po stronie serwera.
 */
final class TurnstileVerifier {

	/**
	 * Weryfikuje token i zwraca wynik.
	 *
	 * @param string $token Token z frontu.
	 * @return array{success: bool, score: float}|\WP_Error
	 */
	public function verify( string $token ) {
		if ( ! GlobalSettings::is_turnstile_active() ) {
			return array(
				'success' => true,
				'score'   => 1.0,
			);
		}

		if ( '' === $token ) {
			return new \WP_Error( 'ff_turnstile_missing', __( 'Anti-spam verification failed. Refresh the page and try again.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		$settings = GlobalSettings::get();
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => $settings['turnstileSecretKey'],
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			DebugLog::error( 'Turnstile request failed', array( 'error' => $response->get_error_message() ) );

			return new \WP_Error( 'ff_turnstile_http', __( 'Could not verify Turnstile. Please try again.', 'fast-forms' ), array( 'status' => 503 ) );
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			DebugLog::info( 'Turnstile verification failed', array( 'body' => $body ) );

			return new \WP_Error( 'ff_turnstile_invalid', __( 'Anti-spam verification failed.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		return array(
			'success' => true,
			'score'   => 1.0,
		);
	}
}
