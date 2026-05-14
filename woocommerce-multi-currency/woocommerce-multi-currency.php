<?php
/**
 * Plugin Name: CURCY - WooCommerce Multi Currency Premium
 * Plugin URI: https://villatheme.com/extensions/woo-multi-currency/
 * Description: Allows you to display prices and accepts payments in multiple currencies. Working only with WooCommerce.
 * Version: 2.2.2
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * Copyright 2015-2022 VillaTheme.com. All rights reserved.
 * Text-domain: woocommerce-multi-currency
 * Tested up to: 6.1
 * WC requires at least: 5.0
 * WC tested up to: 7.1
 * Elementor tested up to: 3.6.5
 * Requires PHP: 7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOOMULTI_CURRENCY_VERSION', '2.2.2' );
define( 'WOOMULTI_CURRENCY_FILE', __FILE__ );
/**
 * Detect plugin. For use on Front End only.
 */
$wmc_wc_version = get_option( 'woocommerce_version' );
if ( version_compare( '5.0', $wmc_wc_version, '>' ) ) {

	add_action( 'admin_notices', function () {
		?>
        <div class="notice error">
            <p><?php esc_html_e( 'WooCommerce Multi Currency requires WooCommerce version at least 5.0 to use.', 'woocommerce-multi-currency' ); ?></p>
        </div>
		<?php
	} );

	return;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	$init_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "woocommerce-multi-currency" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "define.php";
	require_once $init_file;
}

/**
 * Class WOOMULTI_CURRENCY
 */
class WOOMULTI_CURRENCY {
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action( 'admin_notices', array( $this, 'global_note' ) );
	}

	/**
	 * Notify if WooCommerce is not activated
	 */
	function global_note() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			?>
            <div id="message" class="error">
                <p><?php _e( 'Please install and activate WooCommerce to use WooCommerce Multi Currency plugin.', 'woocommerce-multi-currency' ); ?></p>
            </div>
			<?php
		}
		if ( is_plugin_active( 'woo-multi-currency-pro/woo-multi-currency-pro.php' ) ) {
			deactivate_plugins( 'woo-multi-currency-pro/woo-multi-currency-pro.php' );
			unset( $_GET['activate'] );
		}

	}

	/**
	 * When active plugin Function will be call
	 */
	public function install() {
		global $wp_version;
		if ( version_compare( $wp_version, "5.0", "<" ) ) {
			deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
			wp_die( "This plugin requires WordPress version 5.0 or higher." );
		}
		$data_init = 'eyJhdXRvX2RldGVjdCI6IjAiLCJlbmFibGVfZGVzaWduIjoiMSIsImRlc2lnbl90aXRsZSI6IlNlbGVjdCB5b3VyIGN1cnJlbmN5IiwiZGVzaWduX3Bvc2l0aW9uIjoiMSIsInRleHRfY29sb3IiOiIjZmZmZmZmIiwibWFpbl9jb2xvciI6IiNmNzgwODAiLCJiYWNrZ3JvdW5kX2NvbG9yIjoiIzIxMjEyMSIsImlzX2NoZWNrb3V0IjoiMSIsImlzX2NhcnQiOiIxIiwiY29uZGl0aW9uYWxfdGFncyI6IiIsImZsYWdfY3VzdG9tIjoiIiwiY3VzdG9tX2NzcyI6IiIsImVuYWJsZV9tdWx0aV9wYXltZW50IjoiMSIsInVwZGF0ZV9leGNoYW5nZV9yYXRlIjoiMCIsImZpbmFuY2VfYXBpIjoiMCIsInJhdGVfZGVjaW1hbHMiOiIzIiwia2V5IjoiIn0=';
		if ( ! get_option( 'woo_multi_currency_params', '' ) ) {
			update_option( 'woo_multi_currency_params', json_decode( base64_decode( $data_init ), true ) );
		}
	}
}

new WOOMULTI_CURRENCY();