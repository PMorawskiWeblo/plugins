<?php
/**
 * Plugin Name: StoreGuide AI for WooCommerce
 * Plugin URI: https://example.com/storeguide-ai
 * Description: AI-powered WooCommerce product advisor with retrieval-first architecture.
 * Version: 0.1.0
 * Author: StoreGuide
 * Text Domain: storeguide-ai
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STOREGUIDE_AI_VERSION', '0.1.0' );
define( 'STOREGUIDE_AI_PLUGIN_FILE', __FILE__ );
define( 'STOREGUIDE_AI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'STOREGUIDE_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STOREGUIDE_AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-loader.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-activator.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'StoreGuide_AI_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'StoreGuide_AI_Deactivator', 'deactivate' ) );

function storeguide_ai_run_plugin() {
	$plugin = new StoreGuide_AI_Plugin();
	$plugin->run();
}

storeguide_ai_run_plugin();
