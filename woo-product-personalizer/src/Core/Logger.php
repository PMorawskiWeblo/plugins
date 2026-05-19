<?php
/**
 * Plugin logger – writes only when debug mode is enabled.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	const LEVEL_DEBUG   = 'debug';
	const LEVEL_INFO    = 'info';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR   = 'error';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public function debug( $message, array $context = array() ) {
		$this->log( self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public function info( $message, array $context = array() ) {
		$this->log( self::LEVEL_INFO, $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public function warning( $message, array $context = array() ) {
		$this->log( self::LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public function error( $message, array $context = array() ) {
		$this->log( self::LEVEL_ERROR, $message, $context );
	}

	/**
	 * Write log entry.
	 *
	 * @param string $level   Level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	private function log( $level, $message, array $context = array() ) {
		if ( ! $this->settings->is_debug_enabled() ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . WPP_UPLOADS_SUBDIR . '/logs';

		if ( ! wp_mkdir_p( $log_dir ) ) {
			return;
		}

		$log_file = $log_dir . '/debug.log';
		$line     = sprintf(
			"[%s] [%s] %s %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $level ),
			$message,
			! empty( $context ) ? wp_json_encode( $context ) : ''
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
	}
}
