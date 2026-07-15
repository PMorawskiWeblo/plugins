<?php
/**
 * Odinstalowanie wtyczki Fast Forms.
 *
 * @package FastForms
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/fast-forms.php';

Weblo\FastForms\Core\Uninstaller::run();
