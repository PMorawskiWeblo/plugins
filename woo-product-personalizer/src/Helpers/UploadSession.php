<?php
/**
 * Per-session token for temporary customer uploads.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class UploadSession
 */
class UploadSession {

	const SESSION_KEY = 'wpp_upload_token';

	/**
	 * Ensure WooCommerce session is available (including AJAX).
	 *
	 * @return void
	 */
	public static function ensure_session() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		if ( null === WC()->session ) {
			WC()->initialize_session();
		}
	}

	/**
	 * Get or create upload token for the current shopper session.
	 *
	 * @return string
	 */
	public static function get_token() {
		self::ensure_session();

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '';
		}

		$token = WC()->session->get( self::SESSION_KEY );
		if ( is_string( $token ) && '' !== $token ) {
			return sanitize_file_name( $token );
		}

		$token = wp_generate_password( 32, false, false );
		WC()->session->set( self::SESSION_KEY, $token );

		return $token;
	}
}
