<?php
/**
 * Project folder cleanup service.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Cleanup;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupService
 */
class CleanupService {

	const BATCH_LIMIT = 100;

	/**
	 * Age thresholds available for manual cleanup (days). Key 0 = no age limit.
	 *
	 * @return array<int, string>
	 */
	public static function manual_interval_choices() {
		return array(
			0   => __( 'Any age (order status rules only)', 'woo-product-personalizer' ),
			1   => __( 'Older than 1 day', 'woo-product-personalizer' ),
			7   => __( 'Older than 7 days', 'woo-product-personalizer' ),
			14  => __( 'Older than 14 days', 'woo-product-personalizer' ),
			30  => __( 'Older than 30 days', 'woo-product-personalizer' ),
			60  => __( 'Older than 60 days', 'woo-product-personalizer' ),
			90  => __( 'Older than 90 days', 'woo-product-personalizer' ),
			180 => __( 'Older than 180 days', 'woo-product-personalizer' ),
		);
	}

	/**
	 * Sanitize manual cleanup interval days.
	 *
	 * @param mixed $days     Raw value.
	 * @param int   $fallback Fallback days.
	 * @return int
	 */
	public static function sanitize_interval_days( $days, $fallback = 14 ) {
		$days = (int) $days;

		if ( array_key_exists( $days, self::manual_interval_choices() ) ) {
			return $days;
		}

		return (int) $fallback;
	}

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Uploads manager.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

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
	 * @param UploadsManager     $uploads  Uploads.
	 * @param Logger             $logger   Logger.
	 */
	public function __construct( SettingsRepository $settings, UploadsManager $uploads, Logger $logger ) {
		$this->settings = $settings;
		$this->uploads  = $uploads;
		$this->logger   = $logger;
	}

	/**
	 * Run cleanup.
	 *
	 * @param bool     $dry_run       Dry run only.
	 * @param int|null $interval_days Minimum folder age in days; null uses scheduled setting.
	 * @return array{deleted: string[], kept: string[], errors: string[], interval_days: int}
	 */
	public function run( $dry_run = false, $interval_days = null ) {
		$result = array(
			'deleted'       => array(),
			'kept'          => array(),
			'errors'        => array(),
			'interval_days' => 14,
		);

		$orders_dir = trailingslashit( $this->uploads->base_path() ) . 'orders';

		if ( ! is_dir( $orders_dir ) ) {
			return $result;
		}

		if ( null === $interval_days ) {
			$interval_days = (int) $this->settings->get( 'cleanup_interval', 14 );
		} else {
			$interval_days = self::sanitize_interval_days( $interval_days );
		}

		$result['interval_days'] = $interval_days;

		$cutoff         = $interval_days > 0 ? time() - ( $interval_days * DAY_IN_SECONDS ) : 0;
		$only_completed = (bool) $this->settings->get( 'cleanup_only_completed', true );

		$folders = glob( $orders_dir . '/*', GLOB_ONLYDIR );

		if ( ! is_array( $folders ) ) {
			return $result;
		}

		$processed = 0;

		foreach ( $folders as $folder ) {
			if ( $processed >= self::BATCH_LIMIT ) {
				break;
			}

			++$processed;
			$order_id = (int) basename( $folder );

			if ( $order_id <= 0 ) {
				$result['kept'][] = $folder;
				continue;
			}

			if ( ! $this->should_delete_folder( $order_id, $folder, $cutoff, $only_completed ) ) {
				$result['kept'][] = $folder;
				continue;
			}

			if ( $this->remove_folder( $folder, $order_id, $dry_run ) ) {
				$result['deleted'][] = $folder;
			} else {
				$result['errors'][] = $folder;
			}
		}

		return $result;
	}

	/**
	 * Delete a single order project folder (e.g. after cancellation).
	 *
	 * @param int  $order_id Order ID.
	 * @param bool $dry_run  Report only.
	 * @return bool True when folder was or would be deleted.
	 */
	public function delete_order_folder( $order_id, $dry_run = false ) {
		$order_id = (int) $order_id;
		if ( $order_id <= 0 ) {
			return false;
		}

		$folder = trailingslashit( $this->uploads->base_path() ) . 'orders/' . $order_id;
		if ( ! is_dir( $folder ) ) {
			return false;
		}

		return $this->remove_folder( $folder, $order_id, $dry_run );
	}

	/**
	 * Whether a folder should be removed during batch cleanup.
	 *
	 * @param int    $order_id       Order ID.
	 * @param string $folder         Folder path.
	 * @param int    $cutoff         Unix timestamp cutoff for aged folders.
	 * @param bool   $only_completed Restrict to closed order statuses.
	 * @return bool
	 */
	private function should_delete_folder( $order_id, $folder, $cutoff, $only_completed ) {
		if ( $only_completed && ! $this->is_order_closed( $order_id ) ) {
			$this->logger->debug( 'Cleanup skipped – order still open.', array( 'order_id' => $order_id ) );
			return false;
		}

		if ( $this->is_order_cancelled( $order_id ) ) {
			return true;
		}

		if ( $cutoff <= 0 ) {
			return true;
		}

		$modified = filemtime( $folder );
		if ( false === $modified || $modified > $cutoff ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete folder and log result.
	 *
	 * @param string $folder   Path.
	 * @param int    $order_id Order ID.
	 * @param bool   $dry_run  Dry run.
	 * @return bool
	 */
	private function remove_folder( $folder, $order_id, $dry_run ) {
		if ( $dry_run ) {
			$this->logger->debug( 'Cleanup dry run – folder would be deleted.', array( 'order_id' => $order_id ) );
			return true;
		}

		if ( $this->delete_directory( $folder ) ) {
			$this->logger->info( 'Deleted order project folder.', array( 'order_id' => $order_id, 'path' => $folder ) );
			return true;
		}

		$this->logger->error( 'Failed to delete order project folder.', array( 'order_id' => $order_id ) );
		return false;
	}

	/**
	 * Check if order is in a closed status eligible for aged cleanup.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function is_order_closed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		return $order->has_status( array( 'completed', 'cancelled', 'refunded' ) );
	}

	/**
	 * Check if order is cancelled.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function is_order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		return $order->has_status( 'cancelled' );
	}

	/**
	 * Recursively delete directory.
	 *
	 * @param string $dir Directory path.
	 * @return bool
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$items = scandir( $dir );
		if ( ! is_array( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( in_array( $item, array( '.', '..' ), true ) ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		return rmdir( $dir );
	}
}
