<?php
/**
 * Global asset registration.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

use WooProductPersonalizer\Infrastructure\Repository\ProductSettingsRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Assets
 */
class Assets {

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return;
		}

		$is_layout  = 'wpp_layout' === $screen->post_type;
		$is_product = 'product' === $screen->post_type;
		$is_settings = in_array(
			$hook,
			array( 'toplevel_page_wpp-dashboard', 'wpp-dashboard_page_wpp-settings' ),
			true
		);

		if ( ! $is_layout && ! $is_product && ! $is_settings ) {
			return;
		}

		wp_enqueue_media();

		if ( $is_layout ) {
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script(
				'konva',
				WPP_PLUGIN_URL . 'assets/js/vendor/konva.min.js',
				array(),
				'9.3.6',
				true
			);

			wp_enqueue_script(
				'wpp-google-fonts',
				WPP_PLUGIN_URL . 'assets/js/wpp-google-fonts.js',
				array(),
				WPP_VERSION,
				true
			);
		}

		wp_enqueue_style(
			'wpp-admin',
			WPP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WPP_VERSION
		);

		$admin_deps = array( 'jquery', 'wp-util' );
		if ( $is_layout ) {
			$admin_deps[] = 'jquery-ui-sortable';
			$admin_deps[] = 'konva';
			$admin_deps[] = 'wpp-google-fonts';
		}

		wp_enqueue_script(
			'wpp-admin',
			WPP_PLUGIN_URL . 'assets/js/admin.js',
			$admin_deps,
			WPP_VERSION,
			true
		);

		if ( $is_layout ) {
			wp_localize_script(
				'wpp-admin',
				'wppLayoutBuilder',
				array(
					'i18n' => array(
						'slotTitle'           => __( 'Slot #%d', 'woo-product-personalizer' ),
						'textFieldTitle'      => __( 'Text field #%d', 'woo-product-personalizer' ),
						'cardLabel'           => __( 'Label', 'woo-product-personalizer' ),
						'cloneItem'           => __( 'Clone', 'woo-product-personalizer' ),
						'moveUp'              => __( 'Move up', 'woo-product-personalizer' ),
						'moveDown'            => __( 'Move down', 'woo-product-personalizer' ),
						'dragToReorder'       => __( 'Drag to reorder', 'woo-product-personalizer' ),
						'removeItem'          => __( 'Remove', 'woo-product-personalizer' ),
						'toggleItem'          => __( 'Toggle settings', 'woo-product-personalizer' ),
						'expandItem'          => __( 'Expand settings', 'woo-product-personalizer' ),
						'collapseItem'        => __( 'Collapse settings', 'woo-product-personalizer' ),
						'copyJson'            => __( 'Copy JSON', 'woo-product-personalizer' ),
						'copyJsonSuccess'     => __( 'JSON copied to clipboard.', 'woo-product-personalizer' ),
						'collapseAllCards'    => __( 'Collapse all', 'woo-product-personalizer' ),
						'expandAllCards'      => __( 'Expand all', 'woo-product-personalizer' ),
						'selectImage'         => __( 'Select image', 'woo-product-personalizer' ),
						'removeImage'         => __( 'Remove image', 'woo-product-personalizer' ),
						'openMediaLibrary'    => __( 'Open media library', 'woo-product-personalizer' ),
						'selectMask'          => __( 'Select mask', 'woo-product-personalizer' ),
						'removeMask'          => __( 'Remove mask', 'woo-product-personalizer' ),
						'clickToSelectMask'   => __( 'Click to select mask image', 'woo-product-personalizer' ),
						'imageUrl'            => __( 'Image URL', 'woo-product-personalizer' ),
						'googleFontsHelpHtml' => wp_kses(
							sprintf(
								/* translators: %s: link to Google Fonts */
								__( 'Paste one CSS embed link per line from %s. One link applies the font automatically; multiple links show a font picker for customers.', 'woo-product-personalizer' ),
								'<a href="https://fonts.google.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google Fonts', 'woo-product-personalizer' ) . '</a>'
							),
							array(
								'a' => array(
									'href'   => true,
									'target' => true,
									'rel'    => true,
								),
							)
						),
					),
				)
			);
		}

		if ( $is_layout ) {
			wp_enqueue_script(
				'wpp-mask-border',
				WPP_PLUGIN_URL . 'assets/js/wpp-mask-border.js',
				array(),
				WPP_VERSION,
				true
			);

			wp_enqueue_script(
				'wpp-admin-layout-preview',
				WPP_PLUGIN_URL . 'assets/js/admin-layout-preview.js',
				array( 'jquery', 'konva', 'wpp-google-fonts', 'wpp-mask-border', 'wpp-admin' ),
				WPP_VERSION,
				true
			);
		}
	}

	/**
	 * Register frontend assets (enqueued conditionally by frontend controller).
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id ) {
			return;
		}

		$products = new ProductSettingsRepository();
		if ( ! $products->get( $product_id )->is_active() ) {
			return;
		}

		wp_register_style(
			'wpp-frontend',
			WPP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			WPP_VERSION
		);

		wp_register_script(
			'konva',
			WPP_PLUGIN_URL . 'assets/js/vendor/konva.min.js',
			array(),
			'9.3.6',
			true
		);

		wp_register_script(
			'wpp-debug',
			WPP_PLUGIN_URL . 'assets/js/wpp-debug.js',
			array( 'jquery' ),
			WPP_VERSION,
			true
		);

		wp_register_script(
			'wpp-mask-border',
			WPP_PLUGIN_URL . 'assets/js/wpp-mask-border.js',
			array(),
			WPP_VERSION,
			true
		);

		wp_register_script(
			'wpp-google-fonts',
			WPP_PLUGIN_URL . 'assets/js/wpp-google-fonts.js',
			array(),
			WPP_VERSION,
			true
		);

		wp_register_script(
			'wpp-personalizer',
			WPP_PLUGIN_URL . 'assets/js/personalizer.js',
			array( 'jquery', 'konva', 'wpp-google-fonts', 'wpp-mask-border' ),
			WPP_VERSION,
			true
		);
	}
}
