<?php
/**
 * AJAX cleanup handler (optional extension for settings UI).
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin\Ajax;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Cleanup\CleanupService;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupAjax
 */
class CleanupAjax {

	/**
	 * Cleanup service.
	 *
	 * @var CleanupService
	 */
	private $cleanup;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param CleanupService $cleanup Cleanup.
	 * @param Logger         $logger  Logger.
	 */
	public function __construct( CleanupService $cleanup, Logger $logger ) {
		$this->cleanup = $cleanup;
		$this->logger  = $logger;

		add_action( 'wp_ajax_wpp_run_cleanup', array( $this, 'handle' ) );
	}

	/**
	 * Handle AJAX cleanup.
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( 'wpp_cleanup', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'woo-product-personalizer' ) ) );
		}

		$dry_run = ! empty( $_POST['dry_run'] );
		$days    = isset( $_POST['interval_days'] ) ? (int) $_POST['interval_days'] : null;
		$result  = $this->cleanup->run( $dry_run, $days );

		$this->logger->info( 'AJAX cleanup completed.', array( 'dry_run' => $dry_run, 'result' => $result ) );

		wp_send_json_success( $result );
	}
}
