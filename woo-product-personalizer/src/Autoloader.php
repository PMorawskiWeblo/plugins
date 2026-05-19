<?php
/**
 * PSR-4-like autoloader for plugin classes.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
class Autoloader {

	/**
	 * Register autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload class file.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'WooProductPersonalizer\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = WPP_PLUGIN_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
