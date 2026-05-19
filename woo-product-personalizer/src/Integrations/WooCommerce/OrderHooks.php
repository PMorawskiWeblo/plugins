<?php
/**
 * Order integration and file generation.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Generator\GeneratorManager;
use WooProductPersonalizer\Infrastructure\Repository\ProjectRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\CartPreviewStorage;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;
use WooProductPersonalizer\Integrations\WooCommerce\CartHooks;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderHooks
 */
class OrderHooks {

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Uploads.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Projects.
	 *
	 * @var ProjectRepository
	 */
	private $projects;

	/**
	 * Generator.
	 *
	 * @var GeneratorManager
	 */
	private $generator;

	/**
	 * Cart preview storage.
	 *
	 * @var CartPreviewStorage
	 */
	private $cart_previews;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 * @param Logger             $logger   Logger.
	 * @param UploadsManager     $uploads  Uploads.
	 */
	public function __construct( SettingsRepository $settings, Logger $logger, UploadsManager $uploads ) {
		$this->settings  = $settings;
		$this->logger    = $logger;
		$this->uploads   = $uploads;
		$this->projects  = new ProjectRepository( $uploads, $logger );
		$this->generator     = new GeneratorManager( $uploads, $logger );
		$this->cart_previews = new CartPreviewStorage( $uploads, $logger );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'generate_order_projects' ), 20, 1 );
		add_action( 'admin_post_wpp_regenerate_order_projects', array( $this, 'handle_regenerate_request' ) );
	}

	/**
	 * Admin: regenerate production files for an existing order.
	 *
	 * @return void
	 */
	public function handle_regenerate_request() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

		if ( ! $order_id || ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'woo-product-personalizer' ) );
		}

		check_admin_referer( 'wpp_regenerate_order_projects_' . $order_id );

		$this->generate_order_projects( $order_id, false );

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Build admin URL to regenerate order production files.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function get_regenerate_url( $order_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=wpp_regenerate_order_projects&order_id=' . absint( $order_id ) ),
			'wpp_regenerate_order_projects_' . absint( $order_id )
		);
	}

	/**
	 * Copy cart personalization to order item meta.
	 *
	 * @param \WC_Order_Item_Product $item          Item.
	 * @param string                 $cart_item_key Key.
	 * @param array                  $values        Values.
	 * @param \WC_Order              $order         Order.
	 * @return void
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values[ CartHooks::CART_KEY ] ) ) {
			return;
		}

		$wpp = $values[ CartHooks::CART_KEY ];

		$item->add_meta_data( '_wpp_personalized', 'yes', true );
		$item->add_meta_data( '_wpp_layout_id', $wpp['layout_id'] ?? 0, true );
		$item->add_meta_data( '_wpp_summary', $wpp['summary'] ?? array(), true );
		$item->add_meta_data( '_wpp_project_state', $wpp['project_state'] ?? array(), true );
		$item->add_meta_data( '_wpp_hash', $wpp['hash'] ?? '', true );
		$item->add_meta_data( '_wpp_preview_data', $wpp['preview_data'] ?? '', true );

		if ( ! empty( $wpp['preview_id'] ) ) {
			$item->add_meta_data( '_wpp_preview_id', (string) $wpp['preview_id'], true );
		}

		if ( ! empty( $wpp['preview_full_url'] ) ) {
			$item->add_meta_data( '_wpp_preview_full_url', (string) $wpp['preview_full_url'], true );
		}

		if ( ! empty( $wpp['preview_layers_full_url'] ) ) {
			$item->add_meta_data( '_wpp_preview_layers_full_url', (string) $wpp['preview_layers_full_url'], true );
		}
	}

	/**
	 * Generate project files for order.
	 *
	 * @param int  $order_id             Order ID.
	 * @param bool $delete_cart_previews Remove temporary cart preview folders after success.
	 * @return void
	 */
	public function generate_order_projects( $order_id, $delete_cart_previews = true ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$has_personalized = false;

		try {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
					continue;
				}

				$has_personalized  = true;
				$state             = $item->get_meta( '_wpp_project_state' );
				$preview_source    = $this->resolve_production_preview_source( $item );
				$layers_source     = $this->resolve_layers_production_preview_source( $item );

				if ( ! is_array( $state ) ) {
					continue;
				}

				$product_id = $item instanceof \WC_Order_Item_Product ? $item->get_product_id() : 0;

				$paths = $this->projects->save_order_project(
					$order_id,
					$item_id,
					$state,
					$preview_source,
					$product_id,
					(int) $item->get_meta( '_wpp_layout_id' ),
					$layers_source
				);

				if ( $paths ) {
					$preview_id = (string) $item->get_meta( '_wpp_preview_id' );

					$item->update_meta_data( '_wpp_project_json', $paths['json'] );
					$item->update_meta_data( '_wpp_production_file', $paths['production'] );
					$item->update_meta_data( '_wpp_production_url', $paths['production_url'] );
					if ( ! empty( $paths['layers_production'] ) ) {
						$item->update_meta_data( '_wpp_layers_production_file', $paths['layers_production'] );
					}
					if ( ! empty( $paths['layers_production_url'] ) ) {
						$item->update_meta_data( '_wpp_layers_production_url', $paths['layers_production_url'] );
					}
					$item->delete_meta_data( '_wpp_preview_id' );
					$item->save();

					if ( $delete_cart_previews && '' !== $preview_id ) {
						$this->cart_previews->delete( $preview_id );
					}
				}
			}
		} catch ( \Throwable $e ) {
			$this->logger->error(
				'Order project generation failed.',
				array(
					'order_id' => $order_id,
					'message'  => $e->getMessage(),
				)
			);
		}

		if ( $has_personalized ) {
			$order->update_meta_data( '_wpp_has_personalized_items', 'yes' );
			$order->save();
			$this->logger->info( 'Order projects generated.', array( 'order_id' => $order_id ) );
		}
	}

	/**
	 * Resolve preview input for production PNG (full file path, legacy base64, or URL).
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return string
	 */
	private function resolve_production_preview_source( $item ) {
		$preview_id  = (string) $item->get_meta( '_wpp_preview_id' );
		$preview_url = (string) $item->get_meta( '_wpp_preview_full_url' );

		if ( '' === $preview_url ) {
			$preview_url = (string) $item->get_meta( '_wpp_preview_data' );
		}

		$full_path = $this->cart_previews->resolve_production_path( $preview_id, $preview_url );

		if ( false !== $full_path ) {
			return $full_path;
		}

		return $preview_url;
	}

	/**
	 * Resolve layers-only preview input for production PNG (path or URL).
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return string
	 */
	private function resolve_layers_production_preview_source( $item ) {
		$layers_url = (string) $item->get_meta( '_wpp_layers_production_url' );
		if ( '' !== $layers_url ) {
			$existing = $this->cart_previews->layers_path_from_preview_url( $layers_url );
			if ( false !== $existing ) {
				return $existing;
			}
		}

		$preview_id = (string) $item->get_meta( '_wpp_preview_id' );
		$layers_url = (string) $item->get_meta( '_wpp_preview_layers_full_url' );

		$path = $this->cart_previews->resolve_layers_production_path( $preview_id, $layers_url );

		return false !== $path ? $path : $layers_url;
	}
}
