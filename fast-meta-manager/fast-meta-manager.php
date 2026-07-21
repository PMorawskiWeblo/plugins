<?php

/**
 * Plugin Name: Fast Meta Manager
 * Description: Unified admin tool for viewing and editing metadata across WordPress object types.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Piotr Morawski
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fast-meta-manager
 * Domain Path: /languages
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('FFMM_VERSION', '0.1.0');
define('FFMM_PLUGIN_FILE', __FILE__);
define('FFMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FFMM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FFMM_PLUGIN_DIR . 'includes/Core/Loader.php';
require_once FFMM_PLUGIN_DIR . 'includes/Core/Target_Resolver.php';
require_once FFMM_PLUGIN_DIR . 'includes/Core/Plugin.php';
require_once FFMM_PLUGIN_DIR . 'includes/Admin/Settings_Manager.php';
require_once FFMM_PLUGIN_DIR . 'includes/Admin/Tools_Page.php';
require_once FFMM_PLUGIN_DIR . 'includes/Admin/Meta_Box.php';
require_once FFMM_PLUGIN_DIR . 'includes/API/Save_Controller.php';
require_once FFMM_PLUGIN_DIR . 'includes/Render/Meta_Table_Renderer.php';
require_once FFMM_PLUGIN_DIR . 'includes/Security/Permission_Manager.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Target_Adapter_Interface.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Post_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Custom_Post_Type_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Product_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Order_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Term_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/User_Target_Adapter.php';
require_once FFMM_PLUGIN_DIR . 'includes/Target/Comment_Target_Adapter.php';

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain('fast-meta-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');

		$plugin = new \FFMM\Core\Plugin();
		$plugin->run();
	}
);
