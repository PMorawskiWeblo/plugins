<?php
/**
 * HPOS compatibility declaration.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_HPOS {
	/**
	 * Register compatibility callback.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
	}

	/**
	 * Declare HPOS compatibility.
	 *
	 * @return void
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', STOREGUIDE_AI_PLUGIN_FILE, true );
		}
	}
}
