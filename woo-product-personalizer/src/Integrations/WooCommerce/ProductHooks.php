<?php
/**
 * Single product display hooks.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

use WooProductPersonalizer\Frontend\PersonalizerController;
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
	 * Constructor.
	 *
	 * @param PersonalizerController $controller Controller.
	 * @param SettingsRepository     $settings   Settings.
	 */
	public function __construct( PersonalizerController $controller, SettingsRepository $settings ) {
		$this->controller = $controller;
		$this->settings   = $settings;

		add_action( 'init', array( $this, 'register_placement_hooks' ), 20 );
	}

	/**
	 * Register hooks based on settings.
	 *
	 * @return void
	 */
	public function register_placement_hooks() {
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
	 * Output personalizer UI.
	 *
	 * @return void
	 */
	public function output() {
		echo $this->controller->render_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
