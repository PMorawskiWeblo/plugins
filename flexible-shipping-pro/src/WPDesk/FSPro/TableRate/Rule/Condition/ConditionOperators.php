<?php
/**
 * Trait ConditionOperators
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition;

use FSVendor\WPDesk\Forms\Field\SelectField;

/**
 * Condition operators.
 */
trait ConditionOperators {

	/**
	 * @param array  $condition_settings .
	 * @param string $default .
	 *
	 * @return string
	 */
	private function get_operator_from_settings( array $condition_settings, $default = 'is' ) {
		return isset( $condition_settings[ $this->get_operator_field_name() ] ) ? $condition_settings[ $this->get_operator_field_name() ] : $default;
	}

	/**
	 * @param bool   $matches .
	 * @param string $operator .
	 *
	 * @return bool
	 */
	private function apply_is_not_operator( $matches, $operator = 'is' ): bool {
		return 'is_not' === $operator ? ! $matches : $matches;
	}

	/**
	 * @return SelectField
	 */
	private function prepare_operator_is() {
		return ( new SelectField() )
			->set_name( $this->get_operator_field_name() )
			->set_options(
				[
					[
						'value'       => 'is',
						'label'       => __( 'is', 'flexible-shipping-pro' ),
					],
					[
						'value'       => 'is_not',
						'label'       => __( 'is not', 'flexible-shipping-pro' ),
					],
				]
			);
	}

	/**
	 * @return SelectField
	 */
	private function prepare_operator_equals() {
		return ( new SelectField() )
			->set_name( $this->get_operator_field_name() )
			->set_options(
				[
					[
						'value'       => 'equals',
						'label'       => __( 'equals', 'flexible-shipping-pro' ),
					],
					[
						'value'       => 'not_equals',
						'label'       => __( 'not equals', 'flexible-shipping-pro' ),
					],
				]
			);
	}

	/**
	 * @param string|null $default_value .
	 *
	 * @return SelectField
	 */
	private function prepare_operator_matches( $default_value = null ) {
		$operator_matches = ( new SelectField() )
			->set_name( $this->get_operator_field_name() )
			->set_options(
				[
					[
						'value' => 'any',
						'label' => __( 'any', 'flexible-shipping-pro' ),
					],
					[
						'value' => 'all',
						'label' => __( 'all', 'flexible-shipping-pro' ),
					],
					[
						'value' => 'none',
						'label' => __( 'none', 'flexible-shipping-pro' ),
					],
				]
			)
			->set_label( __( 'matches', 'flexible-shipping-pro' ) );
		if ( $default_value ) {
			$operator_matches->set_default_value( $default_value );
		}

		return $operator_matches;
	}

	/**
	 * @param string $operator .
	 *
	 * @return string
	 */
	private function get_operator_label( $operator ) {
		$labels = [
			'is'     => __( 'is', 'flexible-shipping-pro' ),
			'is_not' => __( 'is not', 'flexible-shipping-pro' ),
			'any'    => __( 'any', 'flexible-shipping-pro' ),
			'all'    => __( 'all', 'flexible-shipping-pro' ),
			'none'   => __( 'none', 'flexible-shipping-pro' ),
		];

		return isset( $labels[ $operator ] ) ? $labels[ $operator ] : $operator;
	}

	/**
	 * @return string
	 */
	private function get_operator_field_name() {
		return 'operator';
	}
}
