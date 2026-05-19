<?php
/**
 * Personalizer shortcode.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Frontend;

use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcode
 */
class Shortcode {

	const TAG = 'woo_product_personalizer';

	/**
	 * Personalizer controller.
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

		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Render shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts = array() ) {
		if ( ! is_product() ) {
			return '';
		}

		$atts = shortcode_atts(
			array( 'product_id' => get_the_ID() ),
			$atts,
			self::TAG
		);

		return $this->controller->render( absint( $atts['product_id'] ) );
	}
}
