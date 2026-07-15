<?php

namespace Weblo\QuickReturns\Domain;

use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;
use Weblo\QuickReturns\Support\Helpers;

class EligibilityService {

	public function is_product_eligible( int $product_id ): bool {
		$settings   = SettingsRepository::get_all();
		$excluded   = Helpers::parse_id_list( $settings['excluded_products'] ?? '' );
		$categories = Helpers::parse_id_list( $settings['excluded_categories'] ?? '' );

		if ( in_array( $product_id, $excluded, true ) ) {
			return false;
		}

		if ( ! empty( $categories ) ) {
			$product_cats = wc_get_product_term_ids( $product_id, 'product_cat' );
			if ( array_intersect( $product_cats, $categories ) ) {
				return false;
			}
		}

		return (bool) apply_filters( 'quick_returns_product_eligible', true, $product_id );
	}

	public function is_order_eligible( \WC_Order $order ): array {
		$settings  = SettingsRepository::get_all();
		$statuses  = $settings['eligible_order_statuses'] ?? [ 'completed', 'processing' ];
		$max_days  = (int) ( $settings['withdrawal_days'] ?? 14 );

		if ( ! in_array( $order->get_status(), $statuses, true ) ) {
			return [
				'eligible' => false,
				'reason'   => __( 'This order is not eligible for a return.', 'quick-returns' ),
			];
		}

		$completed = $order->get_date_completed();
		if ( $completed ) {
			$deadline = clone $completed;
			$deadline->modify( '+' . $max_days . ' days' );
			if ( new \DateTime( 'now', $completed->getTimezone() ) > $deadline ) {
				return [
					'eligible' => false,
					'reason'   => sprintf(
						/* translators: %d: number of days */
						__( 'The %d-day withdrawal period has expired for this order.', 'quick-returns' ),
						$max_days
					),
				];
			}
		}

		return [
			'eligible' => true,
			'reason'   => '',
		];
	}

	public function get_ineligibility_message( int $product_id ): string {
		return apply_filters(
			'quick_returns_ineligibility_message',
			__( 'This product is excluded from the right of withdrawal.', 'quick-returns' ),
			$product_id
		);
	}
}
