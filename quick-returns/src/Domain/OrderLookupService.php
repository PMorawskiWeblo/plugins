<?php

namespace Weblo\QuickReturns\Domain;

use Weblo\QuickReturns\Infrastructure\Repository\ReturnRequestRepository;
use Weblo\QuickReturns\Support\Helpers;

class OrderLookupService {

	private EligibilityService $eligibility;
	private ReturnRequestRepository $repository;

	public function __construct(
		?EligibilityService $eligibility = null,
		?ReturnRequestRepository $repository = null
	) {
		$this->eligibility = $eligibility ?? new EligibilityService();
		$this->repository    = $repository ?? new ReturnRequestRepository();
	}

	public function lookup_by_number_and_email( string $order_number, string $email ): ?\WC_Order {
		$order_number = sanitize_text_field( $order_number );
		$email        = sanitize_email( $email );

		if ( empty( $order_number ) || empty( $email ) ) {
			return null;
		}

		$order_id = wc_get_order_id_by_order_key( $order_number );
		if ( ! $order_id ) {
			$order_id = absint( $order_number );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$orders = wc_get_orders(
				[
					'limit'       => 1,
					'return'      => 'ids',
					'meta_key'    => '_order_number',
					'meta_value'  => $order_number,
					'meta_compare' => '=',
				]
			);
			if ( ! empty( $orders ) ) {
				$order = wc_get_order( $orders[0] );
			}
		}

		if ( ! $order ) {
			return null;
		}

		if ( 0 !== strcasecmp( $order->get_billing_email(), $email ) ) {
			return null;
		}

		return $order;
	}

	public function lookup_for_current_user( int $order_id ): ?\WC_Order {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}

		if ( (int) $order->get_customer_id() !== $user_id ) {
			return null;
		}

		return $order;
	}

	public function build_order_payload( \WC_Order $order, string $selection_mode = 'manual_select' ): array {
		$eligibility = $this->eligibility->is_order_eligible( $order );
		if ( ! $eligibility['eligible'] ) {
			return [
				'success' => false,
				'message' => $eligibility['reason'],
			];
		}

		$returned_qty = $this->repository->get_returned_quantities( $order->get_id() );
		$items        = [];
		$auto_select  = 'all_items' === $selection_mode;

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$product_id    = $product->get_id();
			$purchased     = $item->get_quantity();
			$already       = $returned_qty[ (string) $item_id ] ?? 0;
			$available     = max( 0, $purchased - $already );
			$is_eligible   = $this->eligibility->is_product_eligible( $product_id );
			$ean           = $product->get_meta( '_ean' );
			if ( empty( $ean ) ) {
				$ean = $product->get_meta( 'ean' );
			}

			$thumbnail = $product->get_image( 'thumbnail' );
			if ( empty( $thumbnail ) ) {
				$thumbnail = '<span class="qr-product-placeholder" aria-hidden="true"></span>';
			}

			$items[] = [
				'order_item_id'   => $item_id,
				'product_id'      => $product_id,
				'name'            => $item->get_name(),
				'sku'             => $product->get_sku(),
				'ean'             => $ean,
				'price'           => (float) $order->get_item_total( $item, false, true ),
				'price_formatted' => Helpers::format_price( (float) $order->get_item_total( $item, false, true ), $order ),
				'purchased_qty'   => $purchased,
				'returned_qty'    => $already,
				'available_qty'   => $available,
				'eligible'        => $is_eligible && $available > 0,
				'ineligible_msg'  => $is_eligible ? '' : $this->eligibility->get_ineligibility_message( $product_id ),
				'thumbnail'       => $thumbnail,
				'selected'        => $auto_select && $is_eligible && $available > 0,
			];
		}

		return [
			'success'        => true,
			'order_id'       => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'customer_name'  => Helpers::get_order_display_name( $order ),
			'customer_email' => $order->get_billing_email(),
			'currency'       => Helpers::get_currency_settings( $order ),
			'items'          => $items,
			'selection_mode' => $selection_mode,
		];
	}

	public function validate_submission( \WC_Order $order, array $submitted_items ): array {
		$payload  = $this->build_order_payload( $order );
		if ( empty( $payload['success'] ) ) {
			return [ 'valid' => false, 'message' => $payload['message'] ?? '' ];
		}

		$available_map = [];
		foreach ( $payload['items'] as $item ) {
			$available_map[ (int) $item['order_item_id'] ] = $item;
		}

		$validated = [];
		$total     = 0.0;

		foreach ( $submitted_items as $submitted ) {
			$item_id  = (int) ( $submitted['order_item_id'] ?? 0 );
			$quantity = (int) ( $submitted['quantity'] ?? 0 );
			$reason   = sanitize_text_field( $submitted['reason'] ?? '' );
			$comment  = sanitize_textarea_field( $submitted['comment'] ?? '' );

			if ( ! isset( $available_map[ $item_id ] ) ) {
				continue;
			}

			$source = $available_map[ $item_id ];
			if ( ! $source['eligible'] || $quantity < 1 ) {
				continue;
			}

			if ( $quantity > $source['available_qty'] ) {
				return [
					'valid'   => false,
					'message' => __( 'Return quantity exceeds available quantity.', 'quick-returns' ),
				];
			}

			if ( empty( $reason ) ) {
				return [
					'valid'   => false,
					'message' => __( 'Please select a return reason for each product.', 'quick-returns' ),
				];
			}

			$line_total = $source['price'] * $quantity;
			$total     += $line_total;

			$validated[] = [
				'order_item_id' => $item_id,
				'product_id'    => $source['product_id'],
				'name'          => $source['name'],
				'sku'           => $source['sku'],
				'quantity'      => $quantity,
				'reason'        => $reason,
				'comment'       => $comment,
				'line_total'    => $line_total,
			];
		}

		if ( empty( $validated ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Please select at least one product.', 'quick-returns' ),
			];
		}

		return [
			'valid'  => true,
			'items'  => $validated,
			'totals' => [
				'item_count' => count( $validated ),
				'quantity'   => array_sum( array_column( $validated, 'quantity' ) ),
				'estimated'  => $total,
			],
		];
	}
}
