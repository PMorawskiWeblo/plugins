<?php

namespace WPDesk\FSPro\TableRate\Rule\Condition;

use WPDesk\FS\TableRate\Rule\Condition\AbstractCondition;
use WPDesk\FS\TableRate\Rule\ShippingContents\ShippingContents;
use FSVendor\WPDesk\Forms\Field;
use WPDesk\FSPro\TableRate\Rule\Condition\ProductStock\ProductStockStatusFilter;

class ProductStockStatus extends AbstractCondition {

	use ConditionOperators;
	use ProductField\ProductFieldValueTrait;

	private const CONDITION_ID = 'product_stock_status';

	private array $stock_status_options;

	public function __construct( array $stock_status_options, int $priority = 10 ) {
		$this->condition_id         = self::CONDITION_ID;
		$this->name                 = __( 'Stock status', 'flexible-shipping-pro' );
		$this->description          = __( 'Shipping cost based on the product\'s stock status', 'flexible-shipping-pro' );
		$this->group                = __( 'Product', 'flexible-shipping-pro' );
		$this->priority             = $priority;
		$this->stock_status_options = $stock_status_options;
	}

	public function is_condition_matched( array $condition_settings, ShippingContents $contents, $logger ): bool {

		$condition_matched = 0 !== count( $contents->get_contents() );

		if ( $condition_matched ) {
			$input_data = $this->format_input_data_for_logger( $contents->get_contents() );
		} else {
			$input_data = $this->format_input_data_for_logger( $contents->get_non_filtered_contents() );
		}

		$logger->debug( $this->format_for_log( $condition_settings, $condition_matched, $input_data ) );

		return $condition_matched;
	}

	private function format_input_data_for_logger( array $contents ): string {
		$values = [];

		foreach ( $contents as $item ) {
			$values[] = sprintf( '%1$s (%2$s)', $this->get_stock_status_option_label( $item['data']->get_stock_status() ), $item['data']->get_name() );
		}

		return implode( ', ', $values );
	}

	private function get_stock_status_option_label( string $stock_status ): string {
		return $this->stock_status_options[ $stock_status ] ?? $stock_status;
	}

	public function get_fields(): array {
		return [
			$this->prepare_operator_is(),
			( new Field\SelectField() )
				->set_name( self::CONDITION_ID )
				->add_class( 'product-stock-status' )
				->set_options( $this->prepare_select_options() ),
		];
	}

	private function prepare_select_options(): array {
		$options = [];
		foreach ( $this->stock_status_options as $key => $label ) {
			$options[] = [
				'value' => $key,
				'label' => $label,
			];
		}

		return $options;
	}

	public function process_shipping_contents( ShippingContents $shipping_contents, array $condition_settings ): ShippingContents {
		$shipping_contents->filter_contents( new ProductStockStatusFilter( $condition_settings[ self::CONDITION_ID ], $condition_settings[ $this->get_operator_field_name() ] ) );

		return $shipping_contents;
	}
}
