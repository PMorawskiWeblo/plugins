<?php
/**
 * Single product display hooks.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

use WooProductPersonalizer\Frontend\PersonalizerController;
use WooProductPersonalizer\Infrastructure\Repository\ProductSettingsRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductHooks
 */
class ProductHooks {

	/**
	 * Placement map.
	 *
	 * @var array<string, array{hook: string, priority: int}|null>
	 */
	private static $placements = array(
		'after_price'        => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 15 ),
		'before_add_to_cart' => array( 'hook' => 'woocommerce_before_add_to_cart_button', 'priority' => 10 ),
		'after_add_to_cart'  => array( 'hook' => 'woocommerce_after_add_to_cart_button', 'priority' => 10 ),
		'shortcode_only'     => null,
	);

	/**
	 * Controller.
	 *
	 * @var PersonalizerController
	 */
	private $controller;

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Product settings.
	 *
	 * @var ProductSettingsRepository
	 */
	private $products;

	/**
	 * Constructor.
	 *
	 * @param PersonalizerController $controller Controller.
	 * @param SettingsRepository     $settings   Settings.
	 */
	public function __construct( PersonalizerController $controller, SettingsRepository $settings ) {
		$this->controller = $controller;
		$this->settings   = $settings;
		$this->products   = new ProductSettingsRepository();

		add_action( 'init', array( $this, 'register_placement_hooks' ), 20 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'filter_add_to_cart_text' ), 20 );
	}

	/**
	 * Register hooks based on settings.
	 *
	 * @return void
	 */
	public function register_placement_hooks() {
		if ( $this->settings->is_replace_add_to_cart_enabled() ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'output_replace_modal' ), 5 );
			return;
		}

		if ( $this->settings->is_shortcode_only() ) {
			return;
		}

		$position = $this->settings->get_button_position();
		$map      = self::$placements[ $position ] ?? self::$placements['after_add_to_cart'];

		if ( null === $map ) {
			return;
		}

		add_action( $map['hook'], array( $this, 'output' ), $map['priority'] );
	}

	/**
	 * Output modal markup when add to cart is replaced (no separate personalize button).
	 *
	 * @return void
	 */
	public function output_replace_modal() {
		if ( ! $this->is_replace_active_for_current_product() ) {
			return;
		}

		echo $this->controller->render( null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Change add to cart button label to the personalize default label.
	 *
	 * @param string $text Button text.
	 * @return string
	 */
	public function filter_add_to_cart_text( $text ) {
		if ( ! $this->is_replace_active_for_current_product() ) {
			return $text;
		}

		$config = $this->products->get( (int) get_the_ID() );
		$label  = $config->get_button_label() ?: $this->settings->get( 'default_button_label' );

		return $label ? (string) $label : $text;
	}

	/**
	 * Whether replace-add-to-cart applies on the current single product.
	 *
	 * @return bool
	 */
	private function is_replace_active_for_current_product() {
		if ( ! $this->settings->is_replace_add_to_cart_enabled() || ! is_product() ) {
			return false;
		}

		$product_id = (int) get_the_ID();
		if ( $product_id <= 0 ) {
			return false;
		}

		return $this->products->get( $product_id )->is_active();
	}

	/**
	 * Output personalizer UI.
	 *
	 * @return void
	 */
	public function output() {
		echo $this->controller->render_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
