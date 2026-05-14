<?php

namespace WPDesk\FSPro\TableRate\Rule\Condition\ProductField;

use WPDesk\FS\TableRate\Rule\ContentsFilter;

class ProductFieldValueFilter implements ContentsFilter {

	use ProductFieldValueTrait;

	private string $field_name;

	private string $field_value;

	private string $operator;

	public function __construct( string $field_name, string $field_value, string $operator ) {
		$this->field_name  = $field_name;
		$this->field_value = $field_value;
		$this->operator    = $operator;
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
		if ( $field_value === $this->field_value ) {
			$should_be_removed = false;
		}

		if ( $this->operator === 'not_equals' ) {
			$should_be_removed = ! $should_be_removed;
		}

		return $should_be_removed;
	}
}
