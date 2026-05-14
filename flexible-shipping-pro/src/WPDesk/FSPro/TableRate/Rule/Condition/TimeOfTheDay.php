<?php
/**
 * Class TimeOfDay
 *
 * @package WPDesk\FSPro\TableRate\Rule\Condition
 */

namespace WPDesk\FSPro\TableRate\Rule\Condition;

use DateInterval;
use DateTime;
use Exception;
use FSProVendor\Psr\Log\LoggerInterface;
use WPDesk\FS\TableRate\Rule\Condition\AbstractCondition;
use WPDesk\FS\TableRate\Rule\ShippingContents\ShippingContents;
use FSVendor\WPDesk\Forms\Field;

/**
 * Time of day condition.
 */
class TimeOfTheDay extends AbstractCondition {

	use ConditionOperators;

	const CONDITION_ID = 'time_of_the_day';

	const FIELD_TIME_FROM = 'from';

	const FIELD_TIME_TO = 'to';

	/**
	 * TimeOfTheDay constructor.
	 *
	 * @param int $priority .
	 */
	public function __construct( $priority = 10 ) {
		$this->condition_id = self::CONDITION_ID;
		$this->name         = __( 'Time of the day', 'flexible-shipping-pro' );
		$this->group        = __( 'Destination & Time', 'flexible-shipping-pro' );
		$this->description  = __( 'Shipping cost based on the defined time frames', 'flexible-shipping-pro' );
		$this->priority     = $priority;
	}

	/**
	 * @param array            $condition_settings .
	 * @param ShippingContents $contents           .
	 * @param LoggerInterface  $logger             .
	 *
	 * @return bool
	 */
	public function is_condition_matched( array $condition_settings, ShippingContents $contents, $logger ) {
		$hour_from = isset( $condition_settings[ self::FIELD_TIME_FROM ] ) && 0 !== strlen( $condition_settings[ self::FIELD_TIME_FROM ] ) ? $condition_settings[ self::FIELD_TIME_FROM ] : '00:00';
		$hour_to   = isset( $condition_settings[ self::FIELD_TIME_TO ] ) && 0 !== strlen( $condition_settings[ self::FIELD_TIME_TO ] ) ? $condition_settings[ self::FIELD_TIME_TO ] : '23:00';

		try {

			/**
			 * Can modify current timestamp passed to Time of the day condition.
			 *
			 * @param int $current_timestamp Current timestamp.
			 *
			 * @since 2.3
			 */
			$current_timestamp = (int) apply_filters( 'flexible-shipping/condition/current_timestamp', current_time( 'timestamp' ) );

			$now = new DateTime();
			$now->setTimestamp( $current_timestamp );

			$from = new DateTime();
			$from->setTimestamp( $now->getTimestamp() );
			$this->set_time( $from, $hour_from );

			$to = new DateTime();
			$to->setTimestamp( $now->getTimestamp() );
			$this->set_time( $to, $hour_to );

			$condition_matched = $this->check_hours( $from, $now, $to );

			$logger->debug(
				$this->format_for_log(
					$condition_settings,
					$condition_matched,
					array(
						'from' => $from->format( 'g:i A' ),
						'now'  => $now->format( 'g:00 A' ),
						'to'   => $to->format( 'g:i A' ),
					)
				)
			);
		} catch ( Exception $e ) {
			$logger->debug( $e->getMessage() );

			$condition_matched = false;
		}

		$condition_matched = $this->apply_is_not_operator( $condition_matched, $this->get_operator_from_settings( $condition_settings ) );

		return $condition_matched;
	}

	/**
	 * @param array $condition_settings .
	 * @param bool  $condition_matched  .
	 * @param array $input_data         .
	 *
	 * @return string
	 */
	protected function format_for_log( array $condition_settings, $condition_matched, $input_data ) {
		// Translators: condition name.
		$formatted_for_log = '   ' . sprintf( __( 'Condition: %1$s;', 'flexible-shipping-pro' ), $this->get_name() );

		// Translators: operator.
		$formatted_for_log .= sprintf( __( ' operator: %1$s;', 'flexible-shipping-pro' ), $this->get_operator_label( $this->get_operator_from_settings( $condition_settings, 'all' ) ) );

		$formatted_for_log .= sprintf( ' %1$s: %2$s;', __( 'from', 'flexible-shipping-pro' ), __( $input_data['from'], 'flexible-shipping-pro' ) ); // phpcs:ignore.
		$formatted_for_log .= sprintf( ' %1$s: %2$s;', __( 'to', 'flexible-shipping-pro' ), __( $input_data['to'], 'flexible-shipping-pro' ) ); // phpcs:ignore.

		// Translators: input data.
		$formatted_for_log .= sprintf( __( ' input data: %1$s;', 'flexible-shipping-pro' ), __( $input_data['now'], 'flexible-shipping-pro' ) ); // phpcs:ignore.
		// Translators: matched condition.
		$formatted_for_log .= sprintf( __( ' matched: %1$s', 'flexible-shipping-pro' ), $condition_matched ? __( 'yes', 'flexible-shipping-pro' ) : __( 'no', 'flexible-shipping-pro' ) );

		return $formatted_for_log;
	}

	/**
	 * @return Field[]
	 */
	public function get_fields() {
		return array(
			$this->prepare_operator_is(),
			( new Field\SelectField() )
				->set_name( self::FIELD_TIME_FROM )
				->add_class( 'parameter_min' )
				->add_class( 'hour_from' )
				->set_options( $this->get_time_options() )
				->set_default_value( array( '00:00' ) )
				->set_label( __( 'between', 'flexible-shipping-pro' ) ),
			( new Field\SelectField() )
				->set_name( self::FIELD_TIME_TO )
				->add_class( 'parameter_max' )
				->add_class( 'hour_to' )
				->set_options( $this->get_time_options() )
				->set_default_value( array( '23:00' ) )
				->set_label( __( 'and', 'flexible-shipping-pro' ) ),
		);
	}

	/**
	 * @param DateTime $time .
	 * @param string   $hour .
	 */
	private function set_time( $time, $hour ) {
		[ $h, $m ] = explode( ':', $hour );

		$time->setTime( $h, $m );
	}

	/**
	 * @param DateTime $from .
	 * @param DateTime $now  .
	 * @param DateTime $to   .
	 *
	 * @return bool
	 */
	private function check_hours( $from, $now, $to ) {
		if ( $from > $to || $from->format( 'H:i' ) === $to->format( 'H:i' ) ) {
			$to->add( new DateInterval( 'P1D' ) );
		}

		return $now >= $from && $now <= $to;
	}

	private function get_time_options() {
		/**
		 * Filter time of the day interval.
		 *
		 * @param int $interval .
		 */
		$interval = (int)apply_filters( 'flexible-shipping-pro/condition/time_of_the_day/interval', 60 );
		/**
		 * Filter time of the day options.
		 *
		 * @param array $options .
		 */
		$options = apply_filters( 'flexible-shipping-pro/condition/time_of_the_day/options', $this->get_time_options_with_interval( $interval ) );
		usort( $options, function ( $a, $b ) {
			return strcmp( $a['value'], $b['value'] );
		} );
		return $options;
	}

	/**
	 * @var int $interval
	 *
	 * @return array
	 */
	private function get_time_options_with_interval( int $interval ): array {
		$options = array();
		$is_am_pm = strpos( strtoupper( get_option( 'time_format' ) ?? '' ), 'A' ) !== false;
		foreach ( range( 0, 23 ) as $hour ) {
			$hour = str_pad( $hour, 2, '0', STR_PAD_LEFT );
			foreach ( range( 0, 60, $interval ) as $minute ) {
				if ( $minute >= 60 ) {
					continue;
				}
				$minute    = str_pad( $minute, 2, '0', STR_PAD_LEFT );
				$options[] = [
					'value' => $hour . ':' . $minute,
					'label' => trim( ( $is_am_pm ? ( ( $hour % 12 ) === 0 ? 12 : ( $hour % 12 ) ) : $hour ) . ':' . $minute . ' ' . ( $is_am_pm ? ( $hour < 12 ? 'AM' : 'PM' ) : '' ) ),
				];
			}
		}

		return $options;
	}
}
