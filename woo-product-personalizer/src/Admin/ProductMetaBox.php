<?php
/**
 * WooCommerce product personalization tab.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Domain\Product\ProductConfiguration;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;
use WooProductPersonalizer\Infrastructure\Repository\ProductSettingsRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductMetaBox
 */
class ProductMetaBox {

	/**
	 * Product settings repository.
	 *
	 * @var ProductSettingsRepository
	 */
	private $products;

	/**
	 * Layout repository.
	 *
	 * @var LayoutRepository
	 */
	private $layouts;

	/**
	 * Global settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->products = new ProductSettingsRepository();
		$this->layouts  = new LayoutRepository();
		$this->settings = new SettingsRepository();

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
	}

	/**
	 * Add product data tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['wpp_personalization'] = array(
			'label'    => __( 'Personalization', 'woo-product-personalizer' ),
			'target'   => 'wpp_personalization_panel',
			'class'    => array(),
			'priority' => 80,
		);
		return $tabs;
	}

	/**
	 * Render panel.
	 *
	 * @return void
	 */
	public function render_panel() {
		global $post;

		$config  = $this->products->get( $post->ID );
		$choices = $this->layouts->get_choices();
		$defaults = $this->settings->all();

		include WPP_PLUGIN_PATH . 'templates/admin/product-panel.php';
	}

	/**
	 * Save product settings.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function save( $product_id ) {
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		$config = new ProductConfiguration(
			$product_id,
			isset( $_POST['_wpp_enabled'] ),
			isset( $_POST['_wpp_layout_id'] ) ? absint( $_POST['_wpp_layout_id'] ) : 0,
			isset( $_POST['_wpp_validation_enabled'] ),
			isset( $_POST['_wpp_acceptance_required'] ),
			isset( $_POST['_wpp_acceptance_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_wpp_acceptance_text'] ) ) : '',
			isset( $_POST['_wpp_button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpp_button_label'] ) ) : ''
		);

		$this->products->save( $product_id, $config );
	}
}
