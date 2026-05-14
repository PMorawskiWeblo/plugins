<?php
/**
 * Provider interface.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface StoreGuide_AI_Provider_Interface {
	/**
	 * Generate assistant response.
	 *
	 * @param array<string, mixed> $payload Request payload.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function generate( $payload );
}
