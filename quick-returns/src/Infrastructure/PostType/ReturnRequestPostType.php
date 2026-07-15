<?php

namespace Weblo\QuickReturns\Infrastructure\PostType;

class ReturnRequestPostType {

	public const POST_TYPE = 'shop_return_request';

	public function register_hooks(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
	}

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'               => __( 'Return Requests', 'quick-returns' ),
					'singular_name'      => __( 'Return Request', 'quick-returns' ),
					'add_new'            => __( 'Add New', 'quick-returns' ),
					'add_new_item'       => __( 'Add New Return Request', 'quick-returns' ),
					'edit_item'          => __( 'Edit Return Request', 'quick-returns' ),
					'new_item'           => __( 'New Return Request', 'quick-returns' ),
					'view_item'          => __( 'View Return Request', 'quick-returns' ),
					'search_items'       => __( 'Search Return Requests', 'quick-returns' ),
					'not_found'          => __( 'No return requests found.', 'quick-returns' ),
					'not_found_in_trash' => __( 'No return requests found in Trash.', 'quick-returns' ),
					'menu_name'          => __( 'Return Requests', 'quick-returns' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'woocommerce',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => [ 'title' ],
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			]
		);
	}

	public function columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['qr_order']   = __( 'Order', 'quick-returns' );
				$new['qr_status']  = __( 'Status', 'quick-returns' );
				$new['qr_email']   = __( 'Customer Email', 'quick-returns' );
				$new['qr_total']   = __( 'Estimated Total', 'quick-returns' );
			}
		}
		return $new;
	}

	public function column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'qr_order':
				$order_id = get_post_meta( $post_id, '_qr_order_id', true );
				if ( $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						printf(
							'<a href="%s">#%s</a>',
							esc_url( $order->get_edit_order_url() ),
							esc_html( $order->get_order_number() )
						);
					} else {
						echo esc_html( '#' . $order_id );
					}
				}
				break;
			case 'qr_status':
				$status = get_post_meta( $post_id, '_qr_status', true ) ?: 'new';
				echo esc_html( self::status_label( $status ) );
				break;
			case 'qr_email':
				echo esc_html( get_post_meta( $post_id, '_qr_customer_email', true ) );
				break;
			case 'qr_total':
				$totals = get_post_meta( $post_id, '_qr_totals', true );
				if ( is_array( $totals ) && isset( $totals['estimated'] ) ) {
					echo esc_html( \Weblo\QuickReturns\Support\Helpers::format_price( (float) $totals['estimated'] ) );
				}
				break;
		}
	}

	public static function status_label( string $status ): string {
		$labels = [
			'new'                => __( 'New', 'quick-returns' ),
			'awaiting_shipment'  => __( 'Awaiting Shipment', 'quick-returns' ),
			'received'           => __( 'Received', 'quick-returns' ),
			'approved'           => __( 'Approved', 'quick-returns' ),
			'rejected'           => __( 'Rejected', 'quick-returns' ),
			'refunded'           => __( 'Refunded', 'quick-returns' ),
			'closed'             => __( 'Closed', 'quick-returns' ),
		];
		return $labels[ $status ] ?? $status;
	}

	public static function statuses(): array {
		return [
			'new',
			'awaiting_shipment',
			'received',
			'approved',
			'rejected',
			'refunded',
			'closed',
		];
	}
}
