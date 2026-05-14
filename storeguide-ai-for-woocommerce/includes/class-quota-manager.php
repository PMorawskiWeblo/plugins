<?php
/**
 * Quota manager for request limiting.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Quota_Manager {
	/**
	 * Check and increment daily request quota.
	 *
	 * @param string $scope_type Scope type.
	 * @param string $scope_key Scope key.
	 * @param int    $daily_limit Limit.
	 * @return true|\WP_Error
	 */
	public function check_and_increment_daily_requests( $scope_type, $scope_key, $daily_limit ) {
		if ( $daily_limit <= 0 ) {
			return true;
		}

		global $wpdb;
		$table      = $wpdb->prefix . 'storeguide_ai_quotas';
		$period_key = gmdate( 'Ymd' );

		$current = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT requests_count FROM {$table} WHERE scope_type = %s AND scope_key = %s AND period_type = %s AND period_key = %s LIMIT 1",
				$scope_type,
				$scope_key,
				'daily',
				$period_key
			)
		);

		if ( $current >= $daily_limit ) {
			return new WP_Error( 'storeguide_ai_daily_limit_reached', __( 'Daily request limit reached. Please try again tomorrow.', 'storeguide-ai' ) );
		}

		$updated_at = current_time( 'mysql', true );
		$exists_id  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE scope_type = %s AND scope_key = %s AND period_type = %s AND period_key = %s LIMIT 1",
				$scope_type,
				$scope_key,
				'daily',
				$period_key
			)
		);

		if ( $exists_id > 0 ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET requests_count = requests_count + 1, updated_at = %s WHERE id = %d",
					$updated_at,
					$exists_id
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'scope_type'    => $scope_type,
					'scope_key'     => $scope_key,
					'period_type'   => 'daily',
					'period_key'    => $period_key,
					'requests_count'=> 1,
					'input_tokens'  => 0,
					'output_tokens' => 0,
					'estimated_cost'=> 0,
					'updated_at'    => $updated_at,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s' )
			);
		}

		return true;
	}
}
