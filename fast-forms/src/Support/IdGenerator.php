<?php
/**
 * Unique ID generator.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Generuje unikalne identyfikatory elementów schemy.
 */
final class IdGenerator {

	/**
	 * Tworzy identyfikator z prefiksem.
	 *
	 * @param string $prefix Prefiks (np. row, col, field).
	 */
	public static function generate( string $prefix ): string {
		return $prefix . '_' . wp_generate_password( 8, false, false );
	}
}
