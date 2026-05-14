<?php
/**
 * Shipping cost condition.
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition;

use FSProVendor\Psr\Log\LoggerInterface;
use WPDesk\FS\TableRate\Rule\Condition\AbstractCondition;
use FSVendor\WPDesk\Forms\Field;
use WPDesk\FS\TableRate\Rule\ShippingContents\ShippingContents;
use WPDesk\FSPro\TableRate\Rule\Condition\ShippingCost\ShippingCostFilter;

/**
 * Shipping cost condition.
 */
class ShippingCost extends AbstractCondition {

	use ConditionOperators;

	private const CONDITION_ID = 'shipping_cost';
	private const MIN          = 'min';
	private const MAX          = 'max';

	public function __construct( $priority = 10 ) {
		$this->priority     = $priority;
		$this->condition_id = self::CONDITION_ID;
		$this->name         = __( 'Shipping cost', 'flexible-shipping-pro' );
		$this->description  = __( 'Shipping cost based on current shipping cost', 'flexible-shipping-pro' );
		$this->group        = __( 'Shipping', 'flexible-shipping-pro' );
	}

	/**
	 * @param array            $condition_settings .
	 * @param ShippingContents $contents           .
	 * @param LoggerInterface  $logger             .
	 *
	 * @return bool
	 */
	public function is_condition_matched( array $condition_settings, ShippingContents $contents, $logger ) {
		$condition_matched = 0 !== count( $contents->get_contents() );

		$logger->debug( $this->format_for_log( $condition_settings, $condition_matched, $contents->get_calculated_shipping_cost() ) );

		return $condition_matched;
	}

	/**
	 * @param array  $condition_settings .
	 * @param bool   $condition_matched  .
	 * @param string $input_data         .
	 *
	 * @return string
	 */
	protected function format_for_log( array $condition_settings, $condition_matched, $input_data ) {
		return sprintf(
		// Translators: 1 - condition name, 2 - operator, 3 - min, 4 - max.
			__( 'Condition: %1$s; operator %2$s, min: %3$s, max: %4$s, input data: %5$s', 'flexible-shipping-pro' ),
			$this->get_name(),
			$condition_settings[ $this->get_operator_field_name() ],
			$condition_settings[ self::MIN ],
			$condition_settings[ self::MAX ],
			$input_data
		);
	}

	/**
	 * @return Field[]
	 */
	public function get_fields() {
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

	/**
	 * @param ShippingContents $shipping_contents  .
	 * @param array            $condition_settings .
	 *
	 * @return ShippingContents
	 */
	public function process_shipping_contents( ShippingContents $shipping_contents, array $condition_settings ) {
		$min      = (float) ( isset( $condition_settings[ self::MIN ] ) && 0 !== strlen( $condition_settings[ self::MIN ] ) ? $condition_settings[ self::MIN ] : - INF );
		$max      = (float) ( isset( $condition_settings[ self::MAX ] ) && 0 !== strlen( $condition_settings[ self::MAX ] ) ? $condition_settings[ self::MAX ] : INF );
		$operator = $condition_settings[ $this->get_operator_field_name() ];
		$shipping_contents->filter_contents( new ShippingCostFilter( $operator, $min, $max, $shipping_contents->get_calculated_shipping_cost() ) );

		return $shipping_contents;
	}
}
