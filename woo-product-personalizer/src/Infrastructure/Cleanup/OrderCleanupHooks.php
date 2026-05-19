<?php
/**
 * Delete project folders when an order is cancelled.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Cleanup;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderCleanupHooks
 */
class OrderCleanupHooks {

	/**
	 * Cleanup service.
	 *
	 * @var CleanupService
	 */
	private $cleanup;

	/**
	 * Constructor.
	 *
	 * @param CleanupService $cleanup Cleanup.
	 */
	public function __construct( CleanupService $cleanup ) {
		$this->cleanup = $cleanup;

		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ), 10, 1 );
	}

	/**
	 * Remove project files as soon as the order is cancelled.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_order_cancelled( $order_id ) {
		$this->cleanup->delete_order_folder( (int) $order_id, false );
	}
}
