<?php

namespace WPDesk\FSPro\TableRate\Rule\Condition\ProductField;

use WPDesk\FS\TableRate\Rule\ContentsFilter;

class ProductFieldRangeFilter implements ContentsFilter {

	use ProductFieldValueTrait;

	private string $field_name;

	private float $min;

	private float $max;

	private string $operator;

	public function __construct( string $field_name, string $operator, float $min, float $max ) {
		$this->field_name = $field_name;
		$this->operator   = $operator;
		$this->min        = $min;
		$this->max        = $max;
	}

	public function get_filtered_contents( array $contents ): array {
		foreach ( $contents as $key => $item ) {
			if ( $this->should_be_item_removed( $item['data'] ) ) {
				unset( $contents[ $key ] );
			}
		}

		return $contents;
	}

	/**
	 * @param \WC_Product|mixed $item_data .
	 *
	 * @return bool
	 */
	private function should_be_item_removed( $item_data ): bool {
		if ( ! $item_data instanceof \WC_Product ) {
			return true;
		}
		$should_be_removed = true;
		$field_value       = $this->get_field_value( $item_data, $this->field_name );
		if ( is_numeric( $field_value ) ) {
			$field_value       = (float) $field_value;
			$should_be_removed = $field_value < $this->min || $field_value > $this->max;
		}

		if ( $this->operator === 'is_not' ) {
			$should_be_removed = ! $should_be_removed;
		}

		return $should_be_removed;
	}
}
