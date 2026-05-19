<?php
/**
 * Per-product personalization settings.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Repository;

use WooProductPersonalizer\Domain\Product\ProductConfiguration;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSettingsRepository
 */
class ProductSettingsRepository {

	const META_ENABLED            = '_wpp_enabled';
	const META_LAYOUT_ID          = '_wpp_layout_id';
	const META_VALIDATION         = '_wpp_validation_enabled';
	const META_ACCEPTANCE         = '_wpp_acceptance_required';
	const META_ACCEPTANCE_TEXT    = '_wpp_acceptance_text';
	const META_BUTTON_LABEL       = '_wpp_button_label';

	/**
	 * Get product configuration.
	 *
	 * @param int $product_id Product ID.
	 * @return ProductConfiguration
	 */
	public function get( $product_id ) {
		return new ProductConfiguration(
			$product_id,
			'yes' === get_post_meta( $product_id, self::META_ENABLED, true ),
			absint( get_post_meta( $product_id, self::META_LAYOUT_ID, true ) ),
			'yes' === get_post_meta( $product_id, self::META_VALIDATION, true ),
			'yes' === get_post_meta( $product_id, self::META_ACCEPTANCE, true ),
			(string) get_post_meta( $product_id, self::META_ACCEPTANCE_TEXT, true ),
			(string) get_post_meta( $product_id, self::META_BUTTON_LABEL, true )
		);
	}

	/**
	 * Save product configuration.
	 *
	 * @param int                    $product_id Product ID.
	 * @param ProductConfiguration   $config     Config.
	 * @return void
	 */
	public function save( $product_id, ProductConfiguration $config ) {
		update_post_meta( $product_id, self::META_ENABLED, $config->is_enabled() ? 'yes' : 'no' );
		update_post_meta( $product_id, self::META_LAYOUT_ID, $config->get_layout_id() );
		update_post_meta( $product_id, self::META_VALIDATION, $config->is_validation_enabled() ? 'yes' : 'no' );
		update_post_meta( $product_id, self::META_ACCEPTANCE, $config->is_acceptance_required() ? 'yes' : 'no' );
		update_post_meta( $product_id, self::META_ACCEPTANCE_TEXT, $config->get_acceptance_text() );
		update_post_meta( $product_id, self::META_BUTTON_LABEL, $config->get_button_label() );
	}
}
