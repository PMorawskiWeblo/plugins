<?php
/**
 * Google reCAPTCHA v3 verification.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Integrations;

use Weblo\FastForms\Support\DebugLog;
use Weblo\FastForms\Support\GlobalSettings;

/**
 * Weryfikuje token reCAPTCHA v3 po stronie serwera.
 */
final class RecaptchaVerifier {

	/**
	 * Weryfikuje token i zwraca wynik.
	 *
	 * @param string $token Token z frontu.
	 * @return array{success: bool, score: float, action: string}|\WP_Error
	 */
	public function verify( string $token ) {
		if ( ! GlobalSettings::is_recaptcha_active() ) {
			return array(
				'success' => true,
				'score'   => 1.0,
				'action'  => '',
			);
		}

		if ( '' === $token ) {
			return new \WP_Error( 'ff_recaptcha_missing', __( 'Anti-spam verification failed. Refresh the page and try again.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		$settings = GlobalSettings::get();
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => $settings['recaptchaSecretKey'],
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			DebugLog::error( 'reCAPTCHA request failed', array( 'error' => $response->get_error_message() ) );

			return new \WP_Error( 'ff_recaptcha_http', __( 'Could not verify reCAPTCHA. Please try again.', 'fast-forms' ), array( 'status' => 503 ) );
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			DebugLog::info( 'reCAPTCHA verification failed', array( 'body' => $body ) );

			return new \WP_Error( 'ff_recaptcha_invalid', __( 'Anti-spam verification failed.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		$action   = (string) ( $body['action'] ?? '' );
		$expected = (string) ( $settings['recaptchaAction'] ?? 'fast_forms_submit' );
		$score    = (float) ( $body['score'] ?? 0 );
		$min      = (float) ( $settings['recaptchaMinScore'] ?? 0.5 );

		if ( '' !== $expected && $action !== $expected ) {
			return new \WP_Error( 'ff_recaptcha_action', __( 'Anti-spam verification failed.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		if ( $score < $min ) {
			DebugLog::info( 'reCAPTCHA score too low', array( 'score' => $score, 'min' => $min ) );

			return new \WP_Error( 'ff_recaptcha_score', __( 'Your submission was blocked as suspicious. Please try again later.', 'fast-forms' ), array( 'status' => 403 ) );
		}

		return array(
			'success' => true,
			'score'   => $score,
			'action'  => $action,
		);
	}
}
