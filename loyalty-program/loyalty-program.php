<?php

/**
 * Plugin Name: Loyalty Program
 * Plugin URI: https://weblo.pl/
 * Description: A comprehensive loyalty program plugin with integrations and reward management
 * Version: 1.1.1
 * Author: Weblo   
 * Author URI: https://weblo.pl/
 * Text Domain: loyalty-program
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LOYALTY_PROGRAM_VERSION', '1.0.0');
define('LOYALTY_PROGRAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOYALTY_PROGRAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LOYALTY_PROGRAM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Loyalty Program Class
 * 
 * @class Loyalty_Program
 * @version 1.0.0
 */
final class Loyalty_Program
{

    /**
     * The single instance of the class
     * 
     * @var Loyalty_Program
     */
    protected static $_instance = null;

    /**
     * Admin instance
     * 
     * @var Loyalty_Program_Admin
     */
    public $admin = null;

    /**
     * AJAX instance
     * 
     * @var Loyalty_Program_Ajax
     */
    public $ajax = null;

    /**
     * WooCommerce integration instance
     * 
     * @var Loyalty_Program_WooCommerce
     */
    public $woocommerce = null;

    /**
     * Shortcodes instance
     * 
     * @var Loyalty_Program_Shortcodes
     */
    public $shortcodes = null;

    /**
     * Main Loyalty Program Instance
     * 
     * Ensures only one instance of Loyalty Program is loaded or can be loaded
     * 
     * @static
     * @return Loyalty_Program - Main instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Loyalty Program Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
        $this->includes();
        $this->init();
    }

    /**
     * Hook into actions and filters
     * 
     * @return void
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Include required core files
     * 
     * @return void
     */
    public function includes()
    {
        // Core includes
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-install.php';
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-shortcodes.php';

        // Admin includes
        if (is_admin()) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-admin.php';
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-admin-menu.php';
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-integrations.php';
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-ajax.php';
        }
    }

    /**
     * Initialize the plugin
     * 
     * @return void
     */
    public function init()
    {
        // Initialize admin
        if (is_admin()) {
            $this->admin = new Loyalty_Program_Admin();
            $this->ajax = new Loyalty_Program_Ajax();
        }

        // Initialize WooCommerce integration
        $this->woocommerce = new Loyalty_Program_WooCommerce();

        // Initialize Shortcodes
        $this->shortcodes = new Loyalty_Program_Shortcodes();

        do_action('loyalty_program_loaded');
    }

    /**
     * Load plugin textdomain for translations
     * 
     * @return void
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'loyalty-program',
            false,
            dirname(LOYALTY_PROGRAM_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Plugin activation
     * 
     * @return void
     */
    public function activate()
    {
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-install.php';
        Loyalty_Program_Install::activate();
    }

    /**
     * Plugin deactivation
     * 
     * @return void
     */
    public function deactivate()
    {
        // Deactivation tasks
        flush_rewrite_rules();
    }

    /**
     * Get the plugin url
     * 
     * @return string
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Get the plugin path
     * 
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }
}

/**
 * Returns the main instance of Loyalty_Program
 * 
 * @return Loyalty_Program
 */
function loyalty_program()
{
    return Loyalty_Program::instance();
}

/**
 * Get plugin version for assets (CSS/JS)
 * Supports custom version and random version for cache busting
 * 
 * @return string
 */
function loyalty_program_get_asset_version()
{
    // Check if random version is enabled (for development/cache busting)
    if (get_option('loyalty_program_random_version', 'no') === 'yes') {
        return time(); // Unix timestamp for random version
    }

    // Return custom version or default plugin version
    return get_option('loyalty_program_custom_version', LOYALTY_PROGRAM_VERSION);
}

// Initialize the plugin
loyalty_program();