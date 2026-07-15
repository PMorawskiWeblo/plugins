<?php

namespace Weblo\QuickReturns\Infrastructure\Repository;

use Weblo\QuickReturns\Domain\ReturnRequest;
use Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType;
use Weblo\QuickReturns\Support\Helpers;

class ReturnRequestRepository {

	public function save( ReturnRequest $request ): int {
		$post_id = wp_insert_post(
			[
				'post_type'   => ReturnRequestPostType::POST_TYPE,
				'post_title'  => $request->get_request_number(),
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		update_post_meta( $post_id, '_qr_request_number', $request->get_request_number() );
		update_post_meta( $post_id, '_qr_order_id', $request->get_order_id() );
		update_post_meta( $post_id, '_qr_order_key', $request->get_order_key() );
		update_post_meta( $post_id, '_qr_customer_id', $request->get_customer_id() );
		update_post_meta( $post_id, '_qr_customer_email', $request->get_customer_email() );
		update_post_meta( $post_id, '_qr_status', $request->get_status() );
		update_post_meta( $post_id, '_qr_submitted_at', $request->get_submitted_at() );
		update_post_meta( $post_id, '_qr_items', $request->get_items() );
		update_post_meta( $post_id, '_qr_totals', $request->get_totals() );
		update_post_meta( $post_id, '_qr_source', $request->get_source() );
		update_post_meta( $post_id, '_qr_admin_note', '' );

		return (int) $post_id;
	}

	public function get_returned_quantities( int $order_id ): array {
		$posts = get_posts(
			[
				'post_type'      => ReturnRequestPostType::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => [
					[
						'key'   => '_qr_order_id',
						'value' => $order_id,
					],
				],
				'fields'         => 'ids',
			]
		);

		$returned = [];
		foreach ( $posts as $post_id ) {
			$status = get_post_meta( $post_id, '_qr_status', true );
			if ( in_array( $status, [ 'rejected', 'closed' ], true ) ) {
				continue;
			}
			$items = get_post_meta( $post_id, '_qr_items', true );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				$key = (string) ( $item['order_item_id'] ?? '' );
				if ( ! $key ) {
					continue;
				}
				$returned[ $key ] = ( $returned[ $key ] ?? 0 ) + (int) ( $item['quantity'] ?? 0 );
			}
		}

		return $returned;
	}

	public function create_request_number(): string {
		return Helpers::generate_request_number();
	}
}
