<?php
/**
 * Debug logger.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Debug_Logger {
	/**
	 * Log directory relative to uploads.
	 *
	 * @var string
	 */
	const LOG_SUBDIR = 'storeguide-ai/logs';

	/**
	 * Write log entry.
	 *
	 * @param string               $level Log level.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function log( $level, $message, $context = array() ) {
		$dev = get_option( 'storeguide_ai_dev_options', array() );
		if ( empty( $dev['debug_enabled'] ) ) {
			return;
		}

		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return;
		}

		$log_dir = trailingslashit( $uploads['basedir'] ) . self::LOG_SUBDIR;
		if ( ! wp_mkdir_p( $log_dir ) ) {
			return;
		}

		$this->ensure_guards( $log_dir );

		$record = array(
			'timestamp' => gmdate( 'c' ),
			'level'     => sanitize_key( $level ),
			'message'   => sanitize_text_field( $message ),
			'context'   => $this->redact_context( $context ),
		);

		$line = wp_json_encode( $record ) . PHP_EOL;
		file_put_contents( trailingslashit( $log_dir ) . 'debug.log', $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Add directory guards.
	 *
	 * @param string $log_dir Directory path.
	 * @return void
	 */
	private function ensure_guards( $log_dir ) {
		$index_path = trailingslashit( $log_dir ) . 'index.php';
		$ht_path    = trailingslashit( $log_dir ) . '.htaccess';

		if ( ! file_exists( $index_path ) ) {
			file_put_contents( $index_path, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		if ( ! file_exists( $ht_path ) ) {
			file_put_contents( $ht_path, "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * Redact sensitive values.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	private function redact_context( $context ) {
		$redacted = $context;
		$needles  = array( 'api_key', 'authorization', 'password', 'token', 'secret' );

		foreach ( $redacted as $key => $value ) {
			$key_string = strtolower( (string) $key );
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $key_string, $needle ) ) {
					$redacted[ $key ] = '[redacted]';
					continue 2;
				}
			}

			if ( is_string( $value ) ) {
				$redacted[ $key ] = sanitize_text_field( $value );
			}
		}

		return $redacted;
	}
}
