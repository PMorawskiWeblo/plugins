<?php
/**
 * Plugin deactivation handler.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Core;

/**
 * Obsługa deaktywacji wtyczki.
 */
final class Deactivator {

	/**
	 * Odświeża reguły permalinków po deaktywacji.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
