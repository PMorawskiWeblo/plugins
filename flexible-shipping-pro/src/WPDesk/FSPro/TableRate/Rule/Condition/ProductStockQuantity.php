<?php

namespace WPDesk\FSPro\TableRate\Rule\Condition;

use FSProVendor\Psr\Log\LoggerInterface;
use WPDesk\FS\TableRate\Rule\Condition\AbstractCondition;
use WPDesk\FS\TableRate\Rule\ShippingContents\ShippingContents;
use FSVendor\WPDesk\Forms\Field;
use WPDesk\FSPro\TableRate\Rule\Condition\ProductStock\ProductStockQuantityFilter;

class ProductStockQuantity extends AbstractCondition {

	use ConditionOperators;
	use ProductField\ProductFieldValueTrait;

	private const CONDITION_ID = 'product_stock_quantity';

	private const MIN = 'min';
	private const MAX = 'max';

	public function __construct( int $priority = 10 ) {
		$this->condition_id = self::CONDITION_ID;
		$this->name         = __( 'Stock quantity', 'flexible-shipping-pro' );
		$this->description  = __( 'Shipping cost based on the product\'s stock quantity', 'flexible-shipping-pro' );
		$this->group        = __( 'Product', 'flexible-shipping-pro' );
		$this->priority     = $priority;
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
			$values[] = sprintf( '%1$s (%2$s)', $item['data']->get_stock_quantity(), $item['data']->get_name() );
		}

		return implode( ', ', $values );
	}

	public function get_fields(): array {
		return [
			$this->prepare_operator_is(),
			( new Field\InputNumberField() )
				->set_name( self::MIN )
				->add_class( 'wc_input_decimal' )
				->add_class( 'parameter_min' )
				->set_placeholder( __( 'is from', 'flexible-shipping-pro' ) )
				->set_label( __( 'from', 'flexible-shipping-pro' ) ),
			( new Field\InputNumberField() )
				->set_name( self::MAX )
				->add_class( 'wc_input_decimal' )
				->add_class( 'parameter_max' )
				->set_placeholder( __( 'to', 'flexible-shipping-pro' ) )
				->set_label( __( 'to', 'flexible-shipping-pro' ) ),
		];
	}

	public function process_shipping_contents( ShippingContents $shipping_contents, array $condition_settings ): ShippingContents {
		$min = (float) ( isset( $condition_settings[ self::MIN ] ) && 0 !== strlen( $condition_settings[ self::MIN ] ) ? $condition_settings[ self::MIN ] : -INF );
		$max = (float) ( isset( $condition_settings[ self::MAX ] ) && 0 !== strlen( $condition_settings[ self::MAX ] ) ? $condition_settings[ self::MAX ] : INF );

		$shipping_contents->filter_contents( new ProductStockQuantityFilter( $min, $max ) );

		return $shipping_contents;
	}
}
