<?php
/**
 * Secure ZIP download for order personalization files.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderZipDownload
 */
class OrderZipDownload {

	/**
	 * Uploads manager.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 */
	public function __construct( UploadsManager $uploads ) {
		$this->uploads = $uploads;

		add_action( 'admin_post_wpp_download_order_zip', array( $this, 'handle_download' ) );
	}

	/**
	 * Build admin download URL for order ZIP.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function get_download_url( $order_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=wpp_download_order_zip&order_id=' . absint( $order_id ) ),
			'wpp_download_order_zip_' . absint( $order_id )
		);
	}

	/**
	 * Stream ZIP archive to browser.
	 *
	 * @return void
	 */
	public function handle_download() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

		if ( ! $order_id || ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You are not allowed to download this file.', 'woo-product-personalizer' ) );
		}

		check_admin_referer( 'wpp_download_order_zip_' . $order_id );

		$order = wc_get_order( $order_id );
		if ( ! $order || 'yes' !== $order->get_meta( '_wpp_has_personalized_items' ) ) {
			wp_die( esc_html__( 'No personalization package found for this order.', 'woo-product-personalizer' ) );
		}

		$zip_path = $this->uploads->build_order_zip( $order_id );
		if ( ! $zip_path || ! file_exists( $zip_path ) ) {
			wp_die( esc_html__( 'Could not create the ZIP archive.', 'woo-product-personalizer' ) );
		}

		$filename = 'order-' . $order_id . '-personalization.zip';

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $zip_path );
		exit;
	}
}
