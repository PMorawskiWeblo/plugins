<?php
/**
 * Scheduled cleanup cron.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

use WooProductPersonalizer\Infrastructure\Cleanup\CleanupService;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cron
 */
class Cron {

	const EVENT_HOOK = 'wpp_cleanup_event';

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

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
	 * @param SettingsRepository $settings Settings.
	 * @param CleanupService     $cleanup  Cleanup.
	 * @param Logger             $logger   Logger.
	 */
	public function __construct( SettingsRepository $settings, CleanupService $cleanup, Logger $logger ) {
		$this->settings = $settings;
		$this->cleanup  = $cleanup;
		$this->logger   = $logger;

		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( self::EVENT_HOOK, array( $this, 'run_cleanup' ) );
		add_action( 'update_option_wpp_settings', array( $this, 'reschedule' ), 10, 0 );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public function add_schedules( $schedules ) {
		$schedules['wpp_every_7_days']  = array(
			'interval' => 7 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every 7 days', 'woo-product-personalizer' ),
		);
		$schedules['wpp_every_14_days'] = array(
			'interval' => 14 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every 14 days', 'woo-product-personalizer' ),
		);
		$schedules['wpp_every_30_days'] = array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every 30 days', 'woo-product-personalizer' ),
		);

		return $schedules;
	}

	/**
	 * Schedule event if enabled.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! $this->settings->is_cleanup_enabled() ) {
			wp_clear_scheduled_hook( self::EVENT_HOOK );
			return;
		}

		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_event( time(), $this->get_schedule_name(), self::EVENT_HOOK );
		}
	}

	/**
	 * Reschedule after settings change.
	 *
	 * @return void
	 */
	public function reschedule() {
		wp_clear_scheduled_hook( self::EVENT_HOOK );
		$this->maybe_schedule();
	}

	/**
	 * Run cleanup job.
	 *
	 * @return void
	 */
	public function run_cleanup() {
		$this->logger->info( 'Scheduled cleanup started.' );
		$result = $this->cleanup->run( false );
		$this->logger->info( 'Scheduled cleanup finished.', $result );
	}

	/**
	 * Map interval setting to schedule name.
	 *
	 * @return string
	 */
	private function get_schedule_name() {
		$interval = (int) $this->settings->get( 'cleanup_interval', 14 );

		switch ( $interval ) {
			case 7:
				return 'wpp_every_7_days';
			case 30:
				return 'wpp_every_30_days';
			default:
				return 'wpp_every_14_days';
		}
	}
}
