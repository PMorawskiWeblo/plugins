<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin deactivation.
 */
class Weblo_Search_Engine_Deactivator {

	/**
	 * Clean up on deactivation.
	 */
	public static function deactivate() {
		// Clear transients.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_weblo_search_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_weblo_search_%'" );
	}
}

