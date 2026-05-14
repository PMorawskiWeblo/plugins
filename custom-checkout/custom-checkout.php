<?php

/**
 * Plugin Name: Weblo Custom Checkout
 * Plugin URI:  https://weblo.pl
 * Description: Provides a customizable checkout experience with versioned assets.
 * Version:     1.0.0
 * Author:      Piotr Morawski
 * Author URI:  https://weblo.pl
 * Text Domain: weblo-custom-checkout
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WEBLO_CC_FILE', __FILE__);
define('WEBLO_CC_PATH', plugin_dir_path(WEBLO_CC_FILE));
define('WEBLO_CC_URL', plugin_dir_url(WEBLO_CC_FILE));

require_once WEBLO_CC_PATH . 'includes/class-weblo-cc-logger.php';
require_once WEBLO_CC_PATH . 'includes/class-weblo-custom-checkout.php';

new Weblo_Custom_Checkout();