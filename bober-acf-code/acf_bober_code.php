<?php
/*
Plugin Name: ACF Bober code
Plugin URI: https://piotr-morawskim.pl/acf-bober-code/
Description: Generates theme code for ACF Pro field groups to speed up development.
Version: 2.5.6
Author: Piotr Morawski
Author URI: https://piotr-morawskim.pl
Text Domain: acf-bober-code
Domain Path: /pro/languages
*/

// Exit if accessed directly
if (! defined('ABSPATH')) exit;

if (is_admin()) {

	if (! class_exists('ACFTC_Core')) {

		defined('ACFTC_PLUGIN_VERSION') or define('ACFTC_PLUGIN_VERSION', '2.5.6');
		defined('ACFTC_PLUGIN_DIR_PATH') or define('ACFTC_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
		defined('ACFTC_PLUGIN_DIR_URL') or define('ACFTC_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
		defined('ACFTC_PLUGIN_BASENAME') or define('ACFTC_PLUGIN_BASENAME', plugin_basename(__FILE__));
		defined('ACFTC_IS_PRO') or define('ACFTC_IS_PRO', file_exists(ACFTC_PLUGIN_DIR_PATH . 'pro'));
		defined('ACFTC_PLUGIN_FILE') or define('ACFTC_PLUGIN_FILE', __FILE__);

		// Classes
		include('core/core.php');
		include('core/field-group-ui.php'); // Theme code UI for field groups
		include('core/locations.php');
		include('core/group.php');
		include('core/field.php');

		if (ACFTC_IS_PRO) {
			include('pro/bootstrap.php');
		}

		// Single function for accessing plugin core instance
		function acftc()
		{
			static $instance;

			if (!$instance)
				$instance = new ACFTC_Core();

			return $instance;
		}

		acftc();
	} else {

		include('core/ACFTC_Conflict.php');
		new ACFTC_Conflict;
	}
}