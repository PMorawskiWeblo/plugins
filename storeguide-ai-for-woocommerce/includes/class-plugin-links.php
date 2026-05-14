<?php
/**
 * Plugin list links.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Plugin_Links {
	/**
	 * Add action links.
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function add_settings_link( $links ) {
		$url             = admin_url( 'admin.php?page=storeguide-ai' );
		$settings_link   = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'storeguide-ai' ) . '</a>';
		$links_unshifted = $links;
		array_unshift( $links_unshifted, $settings_link );
		return $links_unshifted;
	}
}
