<?php

namespace Weblo\QuickReturns\Support;

use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;

class Helpers {

	public static function format_price( float $amount, ?\WC_Order $order = null ): string {
		$args = [];
		if ( $order ) {
			$args['currency'] = $order->get_currency();
		}

		$formatted = wp_strip_all_tags( wc_price( $amount, $args ) );

		return html_entity_decode( $formatted, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Currency formatting settings for frontend (plain text, no HTML entities).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_currency_settings( ?\WC_Order $order = null ): array {
		$currency = $order ? $order->get_currency() : get_woocommerce_currency();

		return [
			'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ), ENT_QUOTES, 'UTF-8' ),
			'code'              => $currency,
			'decimals'          => wc_get_price_decimals(),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'format'            => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
		];
	}

	public static function get_order_display_name( \WC_Order $order ): string {
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		return $name ?: $order->get_billing_email();
	}

	public static function generate_request_number(): string {
		$prefix = apply_filters( 'quick_returns_request_number_prefix', 'ZW' );
		$count  = (int) wp_count_posts( 'shop_return_request' )->publish;
		$count += (int) wp_count_posts( 'shop_return_request' )->private;
		$count += (int) wp_count_posts( 'shop_return_request' )->draft;
		return sprintf( '%s/%04d', $prefix, $count + 1 );
	}

	public static function parse_id_list( string $input ): array {
		if ( empty( $input ) ) {
			return [];
		}
		return array_filter( array_map( 'absint', explode( ',', $input ) ) );
	}

	public static function parse_lines( string $input ): array {
		if ( empty( $input ) ) {
			return [];
		}
		$lines = preg_split( '/\r\n|\r|\n/', $input );
		return array_values( array_filter( array_map( 'trim', $lines ) ) );
	}

	public static function asset_version( string $relative_path ): string {
		$file = QUICK_RETURNS_PATH . ltrim( $relative_path, '/' );
		if ( is_readable( $file ) ) {
			return (string) filemtime( $file );
		}
		return QUICK_RETURNS_VERSION;
	}

	public static function is_order_received_page(): bool {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return true;
		}

		return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
	}

	public static function should_load_frontend(): bool {
		return ! self::is_order_received_page();
	}

	/**
	 * Order context when viewing a customer order in My Account.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_view_order_context(): ?array {
		if ( ! is_user_logged_in() || ! function_exists( 'is_wc_endpoint_url' ) ) {
			return null;
		}

		if ( ! is_wc_endpoint_url( 'view-order' ) ) {
			return null;
		}

		$order_id = absint( get_query_var( 'view-order' ) );
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() ) {
			return null;
		}

		$settings = SettingsRepository::get_all();

		return [
			'order_id'     => $order->get_id(),
			'order_number' => $order->get_order_number(),
			'email'        => $order->get_billing_email(),
			'mode'         => ! empty( $settings['auto_select_all'] ) ? 'all_items' : 'manual_select',
		];
	}
}
