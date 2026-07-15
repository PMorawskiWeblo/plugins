<?php
/**
 * Plugin Name:       Fast Forms
 * Plugin URI:        https://weblo.pl/
 * Description:       Buduj formularze drag & drop, zbieraj zgłoszenia i zarządzaj nimi w panelu WordPress.
 * Version:           1.6.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Weblo
 * Author URI:        https://weblo.pl/
 * Text Domain:       fast-forms
 * Domain Path:       /languages
 *
 * @package FastForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FF_VERSION', '1.6.2' );
define( 'FF_PLUGIN_FILE', __FILE__ );
define( 'FF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Tryb developerski — własny log w folderze wtyczki (niezależny od WP_DEBUG).
 * Ustaw na false na produkcji.
 */
if ( ! defined( 'FF_DEVELOPER_DEBUG' ) ) {
	define( 'FF_DEVELOPER_DEBUG', false );
}

/**
 * Zapisuje wpis do logu developerskiego wtyczki.
 *
 * @param string               $message Komunikat.
 * @param array<string, mixed> $context Dodatkowy kontekst.
 */
function ff_debug_log( string $message, array $context = array() ): void {
	Weblo\FastForms\Support\DebugLog::info( $message, $context );
}

/**
 * Wersja pliku CSS/JS do cache-bustingu (filemtime).
 *
 * @param string $relative_path Ścieżka względem katalogu wtyczki.
 */
function ff_asset_version( string $relative_path ): string {
	return Weblo\FastForms\Support\AssetVersion::get( $relative_path );
}

/**
 * PSR-4 autoloader for plugin classes.
 *
 * @param string $class Fully qualified class name.
 */
function ff_autoload( string $class ): void {
	$prefix   = 'Weblo\\FastForms\\';
	$base_dir = FF_PLUGIN_DIR . 'src/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

	if ( is_readable( $file ) ) {
		require $file;
	}
}

spl_autoload_register( 'ff_autoload' );

/**
 * Load plugin text domain.
 */
function ff_load_textdomain(): void {
	load_plugin_textdomain(
		'fast-forms',
		false,
		dirname( FF_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'init', 'ff_load_textdomain' );

register_activation_hook( FF_PLUGIN_FILE, array( 'Weblo\\FastForms\\Core\\Activator', 'activate' ) );
register_deactivation_hook( FF_PLUGIN_FILE, array( 'Weblo\\FastForms\\Core\\Deactivator', 'deactivate' ) );

/**
 * Bootstrap the plugin.
 */
function ff_init(): void {
	Weblo\FastForms\Core\Plugin::instance()->boot();
}
add_action( 'plugins_loaded', 'ff_init' );
