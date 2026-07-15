<?php
/**
 * Uninstall Quick Returns.
 *
 * @package Weblo\QuickReturns
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'quick_returns_settings' );

$posts = get_posts(
	[
		'post_type'      => 'shop_return_request',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	]
);

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}
