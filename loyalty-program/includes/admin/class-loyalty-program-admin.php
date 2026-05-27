<?php

/**
 * Admin functionality
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Admin Class
 */
class Loyalty_Program_Admin
{

    /**
     * Menu instance
     * 
     * @var Loyalty_Program_Admin_Menu
     */
    public $menu = null;

    /**
     * Constructor
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Include required admin files
     * 
     * @return void
     */
    private function includes()
    {
        // Already included in main file
    }

    /**
     * Initialize admin components
     * 
     * @return void
     */
    private function init()
    {
        $this->menu = new Loyalty_Program_Admin_Menu();
    }

    /**
     * Enqueue admin scripts
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_scripts($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'loyalty-program') === false) {
            return;
        }

        // Enqueue jQuery UI Sortable for rewards and settings pages
        if (strpos($hook, 'loyalty-program-rewards') !== false || strpos($hook, 'loyalty-program-settings') !== false) {
            wp_enqueue_script('jquery-ui-sortable');
        }

        // Enqueue WordPress Media Uploader for rewards page
        if (strpos($hook, 'loyalty-program-rewards') !== false) {
            wp_enqueue_media();
        }

        // Enqueue SweetAlert2
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array(),
            '11',
            true
        );

        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11'
        );

        wp_enqueue_script(
            'loyalty-program-admin',
            LOYALTY_PROGRAM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable', 'sweetalert2'),
            loyalty_program_get_asset_version(),
            true
        );

        wp_localize_script('loyalty-program-admin', 'loyaltyProgramAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('loyalty_program_admin'),
            'i18n' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'loyalty-program'),
                'error_occurred' => __('An error occurred. Please try again.', 'loyalty-program'),
                'saved_successfully' => __('Settings saved successfully.', 'loyalty-program'),
            )
        ));
    }

    /**
     * Enqueue admin styles
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_styles($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'loyalty-program') === false) {
            return;
        }

        wp_enqueue_style(
            'loyalty-program-admin',
            LOYALTY_PROGRAM_PLUGIN_URL . 'assets/css/admin.css',
            array('sweetalert2'),
            loyalty_program_get_asset_version()
        );
    }
}
