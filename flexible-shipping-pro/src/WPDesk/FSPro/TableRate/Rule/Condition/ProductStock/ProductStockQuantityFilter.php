<?php
/**
 * Class ProductStockQuantityFilter
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition\ProductStockQuantity
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition\ProductStock;

use WPDesk\FS\TableRate\Rule\ContentsFilter;

/**
 * Can filter shipping contents against product.
 */
class ProductStockQuantityFilter implements ContentsFilter {

	private float $min;

	private float $max;

	public function __construct( float $min, float $max ) {
		$this->min = $min;
		$this->max = $max;
	}

	/**
	 * Returns filtered contents.
	 *
	 * @param array $contents .
	 *
	 * @return array
	 */
	public function get_filtered_contents( array $contents ): array {
		foreach ( $contents as $key => $item ) {
			if ( $this->should_remove_item( $item ) ) {
				unset( $contents[ $key ] );
			}
		}

		return $contents;
	}

	private function should_remove_item( array $item ): bool {
		$stock_quantity = $item['data']->get_stock_quantity();
		return ! is_numeric( $stock_quantity ) || (float) $stock_quantity < $this->min || (float) $stock_quantity > $this->max;
	}
}
