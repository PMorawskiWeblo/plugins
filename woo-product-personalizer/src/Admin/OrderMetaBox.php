<?php
/**
 * Order admin metabox for personalized projects.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;
use WooProductPersonalizer\Integrations\WooCommerce\PreviewDisplay;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderMetaBox
 */
class OrderMetaBox {

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

		add_action( 'add_meta_boxes', array( $this, 'register' ), 30 );
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'item_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'item_values' ), 10, 3 );
	}

	/**
	 * Register order metabox (HPOS + legacy).
	 *
	 * @return void
	 */
	public function register() {
		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
			&& function_exists( 'wc_get_container' )
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'wpp_order_projects',
			__( 'Personalization', 'woo-product-personalizer' ),
			array( $this, 'render' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render order metabox.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or order.
	 * @return void
	 */
	public function render( $post_or_order ) {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );

		if ( ! $order || 'yes' !== $order->get_meta( '_wpp_has_personalized_items' ) ) {
			echo '<p>' . esc_html__( 'No personalized items in this order.', 'woo-product-personalizer' ) . '</p>';
			return;
		}

		$order_id = $order->get_id();
		$folder   = $this->uploads->order_path( $order_id );

		echo '<p><strong>' . esc_html__( 'This order contains personalized products.', 'woo-product-personalizer' ) . '</strong></p>';

		if ( is_dir( $folder ) && class_exists( 'ZipArchive' ) ) {
			printf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				esc_url( OrderZipDownload::get_download_url( $order_id ) ),
				esc_html__( 'Download all files (ZIP)', 'woo-product-personalizer' )
			);
		} elseif ( is_dir( $folder ) ) {
			echo '<p class="description">' . esc_html__( 'ZIP download requires the PHP Zip extension.', 'woo-product-personalizer' ) . '</p>';
		}

		printf(
			'<p><a href="%s" class="button">%s</a></p>',
			esc_url( \WooProductPersonalizer\Integrations\WooCommerce\OrderHooks::get_regenerate_url( $order_id ) ),
			esc_html__( 'Regenerate production files', 'woo-product-personalizer' )
		);

		echo '<ul class="wpp-order-meta-list" style="margin:0;padding-left:1.2em;">';
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
				continue;
			}

			echo '<li style="margin-bottom:8px;">';
			echo '<strong>' . esc_html( $item->get_name() ) . '</strong>';

			$prod_url = PreviewDisplay::get_item_preview_source( $item );
			if ( PreviewDisplay::is_preview_available( $prod_url ) ) {
				printf(
					'<br /><a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( $prod_url ),
					esc_html__( 'Production PNG', 'woo-product-personalizer' )
				);
			} elseif ( PreviewDisplay::item_has_preview_meta( $item ) ) {
				printf(
					'<br /><em class="wpp-preview-unavailable">%s</em>',
					esc_html( PreviewDisplay::get_unavailable_message() )
				);
			}
			echo '</li>';
		}
		echo '</ul>';

		echo '<p class="description">' . esc_html__( 'See line items below for preview thumbnails and details.', 'woo-product-personalizer' ) . '</p>';
	}

	/**
	 * Add column header in order items table.
	 *
	 * @return void
	 */
	public function item_header() {
		echo '<th class="wpp-personalized">' . esc_html__( 'Personalized', 'woo-product-personalizer' ) . '</th>';
	}

	/**
	 * Add column value.
	 *
	 * @param \WC_Product|null $product Product.
	 * @param \WC_Order_Item   $item    Item.
	 * @param int              $item_id Item ID.
	 * @return void
	 */
	public function item_values( $product, $item, $item_id ) {
		echo '<td class="wpp-personalized">';
		if ( 'yes' === $item->get_meta( '_wpp_personalized' ) ) {
			echo '<span class="dashicons dashicons-yes" title="' . esc_attr__( 'Yes', 'woo-product-personalizer' ) . '"></span>';
		} else {
			echo '—';
		}
		echo '</td>';
	}
}
