<?php
/**
 * Shipping cost filter.
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition\ShippingCost
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition\ShippingCost;

use WPDesk\FS\TableRate\Rule\ContentsFilter;

/**
 * Can filter shipping contents against shipping cost.
 */
class ShippingCostFilter implements ContentsFilter {

	private float $min;

	private float $max;

	private string $operator;

	private float $calculated_cost;

	public function __construct( string $operator, float $min, float $max, float $calculated_cost ) {
		$this->min             = $min;
		$this->max             = $max;
		$this->operator        = $operator;
		$this->calculated_cost = $calculated_cost;
	}

	/**
	 * Returns filtered contents.
	 *
	 * @param array $contents .
	 *
	 * @return array
	 */
	public function get_filtered_contents( array $contents ): array {
		$should_remove_items = $this->should_remove_items();
		foreach ( $contents as $key => $item ) {
			if ( $should_remove_items ) {
				unset( $contents[ $key ] );
			}
		}

		return $contents;
	}

	private function should_remove_items(): bool {
		$should_remove = $this->calculated_cost < $this->min || $this->calculated_cost > $this->max;

		return $this->operator === 'is' ? $should_remove : ! $should_remove;
	}
}
