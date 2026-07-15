<?php

namespace Weblo\QuickReturns\Infrastructure\Repository;

class SettingsRepository {

	public const OPTION_KEY = 'quick_returns_settings';

	public static function defaults(): array {
		return [
			'accent_color'           => '#F68B2F',
			'trigger_text'           => __( 'Start request', 'quick-returns' ),
			'trigger_class'          => 'qr-trigger-btn',
			'trigger_selectors'      => ".open-return-modal\n.js-return-trigger",
			'return_reasons'         => [
				__( 'Withdrawal from the contract', 'quick-returns' ),
				__( 'Damaged', 'quick-returns' ),
			],
			'return_address'         => '',
			'confirmation_message'   => __( 'We have received your return request for order {order_number}. We have assigned it the number {request_number}. We will contact you shortly with further instructions.', 'quick-returns' ),
			'excluded_products'      => '',
			'excluded_categories'    => '',
			'auto_select_all'        => false,
			'withdrawal_days'        => 14,
			'eligible_order_statuses' => [ 'completed', 'processing' ],
			'email_customer_enabled' => true,
			'email_admin_enabled'    => true,
			'email_customer_subject' => __( 'Your return request {request_number}', 'quick-returns' ),
			'email_admin_subject'    => __( 'New return request {request_number}', 'quick-returns' ),
			'email_status_change_enabled'  => true,
			'email_status_change_subject'  => __( 'Return request {request_number} – status update', 'quick-returns' ),
			'email_status_change_message'  => __( 'The status of your return request {request_number} for order {order_number} has changed to {status_label}.', 'quick-returns' ),
			'intro_description'      => __( 'Report a return in a few clicks. Provide order details, select the products you want to return and indicate the reason. The store will contact you with further instructions.', 'quick-returns' ),
			'ship_back_notice'       => __( 'Please ship the items back within 14 days of submitting your return request.', 'quick-returns' ),
			'refund_hold_notice'     => __( 'The store may withhold the refund until the items are received or proof of shipment is provided.', 'quick-returns' ),
		];
	}

	public static function set_defaults(): void {
		if ( false === get_option( self::OPTION_KEY ) ) {
			update_option( self::OPTION_KEY, self::defaults() );
		}
	}

	public static function get_all(): array {
		$stored = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( is_array( $stored ) ? $stored : [], self::defaults() );
	}

	public static function get( string $key, $default = null ) {
		$all = self::get_all();
		return $all[ $key ] ?? $default;
	}

	public static function update( array $settings ): bool {
		$merged = wp_parse_args( $settings, self::get_all() );
		return update_option( self::OPTION_KEY, $merged );
	}
}
