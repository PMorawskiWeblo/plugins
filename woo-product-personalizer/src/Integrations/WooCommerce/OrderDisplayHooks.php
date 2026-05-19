<?php
/**
 * Frontend order / checkout personalization preview display.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

use WooProductPersonalizer\Helpers\PersonalizationSummaryHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderDisplayHooks
 */
class OrderDisplayHooks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_meta_start', array( $this, 'render_order_item_preview_link' ), 10, 4 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'hide_internal_order_item_meta' ), 10, 2 );
	}

	/**
	 * Output preview link in order summary (thank you, my account, emails plain layout excluded).
	 *
	 * @param int                    $item_id   Item ID.
	 * @param \WC_Order_Item_Product $item      Item.
	 * @param \WC_Order              $order     Order.
	 * @param bool                   $plain_text Plain text mode.
	 * @return void
	 */
	public function render_order_item_preview_link( $item_id, $item, $order, $plain_text ) {
		unset( $item_id, $order );

		if ( $plain_text || is_admin() ) {
			return;
		}

		if ( ! $item instanceof \WC_Order_Item ) {
			return;
		}

		if ( 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		echo PreviewDisplay::render_customer_preview( $item );
	}

	/**
	 * Hide internal WPP meta keys on customer-facing order views.
	 *
	 * @param array         $formatted_meta Formatted meta.
	 * @param \WC_Order_Item $item           Item.
	 * @return array
	 */
	public function hide_internal_order_item_meta( $formatted_meta, $item ) {
		if ( is_admin() ) {
			return $formatted_meta;
		}

		if ( ! $item instanceof \WC_Order_Item ) {
			return $formatted_meta;
		}

		if ( 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
			return $formatted_meta;
		}

		$hidden_prefixes = array( '_wpp_', 'wpp_' );

		foreach ( $formatted_meta as $key => $meta ) {
			$meta_key = is_object( $meta ) && isset( $meta->key ) ? (string) $meta->key : '';

			foreach ( $hidden_prefixes as $prefix ) {
				if ( 0 === strpos( $meta_key, $prefix ) ) {
					unset( $formatted_meta[ $key ] );
					break;
				}
			}
		}

		$summary = $item->get_meta( '_wpp_summary' );
		if ( ! is_array( $summary ) ) {
			return $formatted_meta;
		}

		$display_rows = PersonalizationSummaryHelper::flatten_for_display( $summary );

		foreach ( $display_rows as $index => $row ) {
			$label = (string) ( $row['label'] ?? '' );
			$value = (string) ( $row['value'] ?? '' );

			if ( '' === $label || '' === trim( $value ) ) {
				continue;
			}

			$display_value = '' !== $value ? '&quot;' . esc_html( $value ) . '&quot;' : '';

			$formatted_meta[] = (object) array(
				'key'           => 'wpp_summary_' . $index,
				'value'         => $value,
				'display_key'   => $label,
				'display_value' => '<strong>' . esc_html( $label ) . ':</strong> ' . $display_value,
			);
		}

		return $formatted_meta;
	}
}
