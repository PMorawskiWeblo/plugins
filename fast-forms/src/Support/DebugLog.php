<?php
/**
 * Plugin debug logger.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Niezależny od WP_DEBUG logger zapisujący do pliku w folderze wtyczki.
 */
final class DebugLog {

	public const LOG_DIR  = 'logs';
	public const LOG_FILE = 'fast-forms-debug.log';

	/**
	 * Sprawdza, czy tryb developer debug jest włączony.
	 */
	public static function is_enabled(): bool {
		return defined( 'FF_DEVELOPER_DEBUG' ) && FF_DEVELOPER_DEBUG;
	}

	/**
	 * Zwraca ścieżkę do katalogu logów.
	 */
	public static function log_dir(): string {
		return FF_PLUGIN_DIR . self::LOG_DIR . '/';
	}

	/**
	 * Zwraca ścieżkę do pliku logu.
	 */
	public static function log_file(): string {
		return self::log_dir() . self::LOG_FILE;
	}

	/**
	 * Zapisuje wpis do logu.
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Dodatkowy kontekst.
	 * @param string               $level   Poziom (INFO, WARNING, ERROR).
	 */
	public static function log( string $message, array $context = array(), string $level = 'INFO' ): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::write( $message, $context, $level );
	}

	/**
	 * Loguje zdarzenia zapisu schemy (tylko gdy FF_DEVELOPER_DEBUG jest włączony).
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Kontekst.
	 * @param string               $level   Poziom (INFO, WARNING, ERROR).
	 */
	public static function schema( string $message, array $context = array(), string $level = 'INFO' ): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::write( $message, $context, $level );
	}

	/**
	 * Zapisuje linię do pliku logu.
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Kontekst.
	 * @param string               $level   Poziom.
	 */
	private static function write( string $message, array $context = array(), string $level = 'INFO' ): void {
		if ( ! self::ensure_log_dir() ) {
			return;
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$line      = sprintf(
			'[%s] [%s] %s',
			$timestamp,
			strtoupper( $level ),
			$message
		);

		if ( ! empty( $context ) ) {
			$encoded = wp_json_encode( self::sanitize_context( $context ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( false !== $encoded ) {
				$line .= ' ' . $encoded;
			}
		}

		$line .= PHP_EOL;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- dedykowany log wtyczki.
		file_put_contents( self::log_file(), $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Loguje informację.
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Kontekst.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( $message, $context, 'INFO' );
	}

	/**
	 * Loguje ostrzeżenie.
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Kontekst.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( $message, $context, 'WARNING' );
	}

	/**
	 * Loguje błąd.
	 *
	 * @param string               $message Komunikat.
	 * @param array<string, mixed> $context Kontekst.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( $message, $context, 'ERROR' );
	}

	/**
	 * Zlicza pola w schemie formularza.
	 *
	 * @param array<string, mixed>|null $schema Schemа formularza.
	 */
	public static function count_schema_fields( ?array $schema ): int {
		if ( empty( $schema['rows'] ) || ! is_array( $schema['rows'] ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $schema['rows'] as $row ) {
			if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
				continue;
			}

			foreach ( $row['columns'] as $column ) {
				if ( ! empty( $column['fields'] ) && is_array( $column['fields'] ) ) {
					$count += count( $column['fields'] );
				}
			}
		}

		return $count;
	}

	/**
	 * Usuwa lub maskuje wrażliwe dane z kontekstu logu.
	 *
	 * @param array<string, mixed> $context Kontekst.
	 * @return array<string, mixed>
	 */
	private static function sanitize_context( array $context ): array {
		$sanitized = array();

		foreach ( $context as $key => $value ) {
			$sanitized[ (string) $key ] = self::sanitize_context_value( (string) $key, $value );
		}

		return $sanitized;
	}

	/**
	 * @param mixed $value Wartość.
	 * @return mixed
	 */
	private static function sanitize_context_value( string $key, $value ) {
		$lower_key = strtolower( $key );

		if ( in_array( $lower_key, array( 'post_keys', 'payload', 'body', 'raw' ), true ) ) {
			if ( is_array( $value ) ) {
				return array( 'count' => count( $value ) );
			}

			return '[redacted]';
		}

		if ( is_array( $value ) ) {
			$nested = array();

			foreach ( $value as $nested_key => $nested_value ) {
				$nested[ (string) $nested_key ] = self::sanitize_context_value( (string) $nested_key, $nested_value );
			}

			return $nested;
		}

		if ( ! is_string( $value ) && ! is_numeric( $value ) && ! is_bool( $value ) ) {
			return '[redacted]';
		}

		$string = (string) $value;

		if ( self::is_sensitive_key( $lower_key ) ) {
			return self::redact_string( $string );
		}

		if ( is_email( $string ) ) {
			return self::redact_string( $string );
		}

		if ( strlen( $string ) > 120 ) {
			return substr( $string, 0, 40 ) . '…[truncated]';
		}

		return $value;
	}

	private static function is_sensitive_key( string $key ): bool {
		$needles = array( 'email', 'phone', 'password', 'secret', 'token', 'nonce', 'key', 'authorization' );

		foreach ( $needles as $needle ) {
			if ( str_contains( $key, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	private static function redact_string( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		if ( strlen( $value ) <= 4 ) {
			return '***';
		}

		return substr( $value, 0, 2 ) . '***' . substr( $value, -2 );
	}

	/**
	 * Tworzy katalog logów, jeśli nie istnieje.
	 */
	private static function ensure_log_dir(): bool {
		$dir = self::log_dir();

		if ( ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return false;
			}

			self::write_protection_files( $dir );
		}

		return is_writable( $dir );
	}

	/**
	 * Dodaje pliki zabezpieczające katalog logów przed dostępem HTTP.
	 *
	 * @param string $dir Ścieżka katalogu logów.
	 */
	private static function write_protection_files( string $dir ): void {
		$htaccess = $dir . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents(
				$htaccess,
				"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
			);
		}

		$index = $dir . 'index.php';

		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}
}
