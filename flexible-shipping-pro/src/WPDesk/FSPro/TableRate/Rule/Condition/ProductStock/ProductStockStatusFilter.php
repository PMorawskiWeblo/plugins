<?php
/**
 * Product stock status filter.
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition\ProductStock
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition\ProductStock;

use WPDesk\FS\TableRate\Rule\ContentsFilter;

/**
 * Can filter shipping contents against product.
 */
class ProductStockStatusFilter implements ContentsFilter {

	private string $status;

	private string $operator;

	public function __construct( string $status, string $operator ) {
		$this->status   = $status;
		$this->operator = $operator;
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
			$stock_status = $item['data']->get_stock_status();
			if ( ! $this->is_stock_status_matched( $stock_status ) ) {
				unset( $contents[ $key ] );
			}
		}

		return $contents;
	}

	private function is_stock_status_matched( string $stock_status ): bool {
		switch ( $this->operator ) {
			case 'is':
				return $stock_status === $this->status;
			case 'is_not':
				return $stock_status !== $this->status;
		}

		return false;
	}
}
