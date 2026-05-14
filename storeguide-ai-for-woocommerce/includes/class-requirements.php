<?php
/**
 * Environment requirements checks.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Requirements {
	/**
	 * Minimum PHP.
	 */
	const MIN_PHP = '7.4';

	/**
	 * Minimum WP.
	 */
	const MIN_WP = '6.3';

	/**
	 * Minimum WC.
	 */
	const MIN_WC = '8.0';

	/**
	 * Validate environment.
	 *
	 * @return true|\WP_Error
	 */
	public function validate() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			return new WP_Error( 'storeguide_ai_php' );
		}

		if ( version_compare( $wp_version, self::MIN_WP, '<' ) ) {
			return new WP_Error( 'storeguide_ai_wp' );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'storeguide_ai_wc_missing' );
		}

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MIN_WC, '<' ) ) {
			return new WP_Error( 'storeguide_ai_wc_version' );
		}

		return true;
	}

	/**
	 * Build translated requirement error message.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public function get_error_message( $code ) {
		switch ( $code ) {
			case 'storeguide_ai_php':
				return esc_html__( 'StoreGuide AI requires a newer PHP version.', 'storeguide-ai' );
			case 'storeguide_ai_wp':
				return esc_html__( 'StoreGuide AI requires a newer WordPress version.', 'storeguide-ai' );
			case 'storeguide_ai_wc_missing':
				return esc_html__( 'StoreGuide AI requires WooCommerce to be active.', 'storeguide-ai' );
			case 'storeguide_ai_wc_version':
				return esc_html__( 'StoreGuide AI requires a newer WooCommerce version.', 'storeguide-ai' );
			default:
				return esc_html__( 'StoreGuide AI requirements are not met.', 'storeguide-ai' );
		}
	}
}
