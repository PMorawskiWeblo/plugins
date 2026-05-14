<?php

namespace WPDesk\FSPro\TableRate\Rule\Condition\ProductField;

use WC_Product;

trait ProductFieldValueTrait {

	/**
	 * @param WC_Product $product
	 * @param string $field_name
	 *
	 * @return string
	 */
	private function get_field_value( $product, string $field_name ): string { // phpcs:ignore
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		if ( method_exists( $product, 'get' . $field_name ) ) {
			return $product->{'get' . $field_name}() ?? '';
		}

		return $product->get_meta( $field_name ) ?? '';
	}
}
