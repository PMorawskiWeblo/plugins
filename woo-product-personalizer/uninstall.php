<?php
/**
 * Uninstall handler.
 *
 * @package WooProductPersonalizer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpp_settings' );
wp_clear_scheduled_hook( 'wpp_cleanup_event' );

if ( ! apply_filters( 'wpp_uninstall_remove_uploads', false ) ) {
	return;
}

$upload_dir = wp_upload_dir();
$wpp_dir    = trailingslashit( $upload_dir['basedir'] ) . 'wc-product-personalizer';

if ( ! is_dir( $wpp_dir ) ) {
	return;
}

/**
 * Recursively delete a directory.
 *
 * @param string $dir Path.
 * @return void
 */
function wpp_uninstall_delete_dir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$items = scandir( $dir );
	if ( ! is_array( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		if ( in_array( $item, array( '.', '..' ), true ) ) {
			continue;
		}
		$path = $dir . '/' . $item;
		if ( is_dir( $path ) ) {
			wpp_uninstall_delete_dir( $path );
		} else {
			wp_delete_file( $path );
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	rmdir( $dir );
}

wpp_uninstall_delete_dir( $wpp_dir );
