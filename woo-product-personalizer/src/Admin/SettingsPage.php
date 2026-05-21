<?php
/**
 * Plugin settings page.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Helpers\UploadMimeTypes;
use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Cleanup\CleanupService;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage
 */
class SettingsPage {

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Cleanup.
	 *
	 * @var CleanupService
	 */
	private $cleanup;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 * @param CleanupService     $cleanup  Cleanup.
	 * @param Logger             $logger   Logger.
	 */
	public function __construct( SettingsRepository $settings, CleanupService $cleanup, Logger $logger ) {
		$this->settings = $settings;
		$this->cleanup  = $cleanup;
		$this->logger   = $logger;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_wpp_manual_cleanup', array( $this, 'handle_manual_cleanup' ) );
	}

	/**
	 * Register top-level admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Product Personalizer', 'woo-product-personalizer' ),
			__( 'Product Personalizer', 'woo-product-personalizer' ),
			'manage_woocommerce',
			AdminMenu::PARENT_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-customizer',
			56
		);

		add_submenu_page(
			AdminMenu::PARENT_SLUG,
			__( 'Settings', 'woo-product-personalizer' ),
			__( 'Settings', 'woo-product-personalizer' ),
			'manage_woocommerce',
			AdminMenu::SETTINGS_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wpp_settings_group',
			SettingsRepository::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = $this->settings->defaults();
		$input    = is_array( $input ) ? $input : array();

		$allowed_mimes = UploadMimeTypes::filter_allowed(
			array_map(
				'sanitize_text_field',
				(array) ( $input['allowed_mime_types'] ?? $defaults['allowed_mime_types'] )
			)
		);

		if ( empty( $allowed_mimes ) ) {
			$allowed_mimes = $defaults['allowed_mime_types'];
		}

		$sanitized = array(
			'max_upload_mb'          => absint( $input['max_upload_mb'] ?? $defaults['max_upload_mb'] ),
			'preview_export_scale'   => min( 6, max( 1, absint( $input['preview_export_scale'] ?? $defaults['preview_export_scale'] ) ) ),
			'allowed_mime_types'     => $allowed_mimes,
			'frontend_mode'          => 'modal',
			'button_position'        => sanitize_text_field( $input['button_position'] ?? $defaults['button_position'] ),
			'shortcode_only'              => ! empty( $input['shortcode_only'] ),
			'replace_add_to_cart_button'  => ! empty( $input['replace_add_to_cart_button'] ),
			'default_button_label'           => sanitize_text_field( $input['default_button_label'] ?? $defaults['default_button_label'] ),
			'default_button_label_completed' => sanitize_text_field( $input['default_button_label_completed'] ?? $defaults['default_button_label_completed'] ),
			'default_accept_text'            => sanitize_textarea_field( $input['default_accept_text'] ?? $defaults['default_accept_text'] ),
			'debug_enabled'          => ! empty( $input['debug_enabled'] ),
			'cleanup_enabled'        => ! empty( $input['cleanup_enabled'] ),
			'cleanup_interval'       => in_array( (int) ( $input['cleanup_interval'] ?? 14 ), array( 7, 14, 30 ), true )
				? (int) $input['cleanup_interval']
				: 14,
			'cleanup_only_completed' => ! empty( $input['cleanup_only_completed'] ),
		);

		return wp_parse_args( $sanitized, $defaults );
	}

	/**
	 * Handle manual cleanup form.
	 *
	 * @return void
	 */
	public function handle_manual_cleanup() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'woo-product-personalizer' ) );
		}

		check_admin_referer( 'wpp_manual_cleanup' );

		$dry_run       = ! empty( $_POST['wpp_dry_run'] );
		$interval_days = isset( $_POST['wpp_manual_cleanup_days'] )
			? (int) wp_unslash( $_POST['wpp_manual_cleanup_days'] )
			: (int) $this->settings->get( 'cleanup_interval', 14 );

		$result = $this->cleanup->run( $dry_run, $interval_days );

		$this->logger->info(
			'Manual cleanup executed.',
			array(
				'dry_run'       => $dry_run,
				'interval_days' => $result['interval_days'],
				'deleted_count' => count( $result['deleted'] ),
			)
		);

		$redirect = add_query_arg(
			array(
				'page'          => AdminMenu::SETTINGS_SLUG,
				'wpp_notice'    => $dry_run ? 'cleanup_dry' : 'cleanup_done',
				'deleted'       => count( $result['deleted'] ),
				'cleanup_days'  => $result['interval_days'],
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$options    = $this->settings->all();
		$upload_dir = wp_upload_dir();
		$wpp_paths  = array(
			'debug_log'  => trailingslashit( $upload_dir['basedir'] ) . WPP_UPLOADS_SUBDIR . '/logs/debug.log',
			'orders_dir' => trailingslashit( $upload_dir['basedir'] ) . WPP_UPLOADS_SUBDIR . '/orders/',
		);

		if ( isset( $_GET['wpp_notice'] ) ) {
			$deleted = isset( $_GET['deleted'] ) ? absint( $_GET['deleted'] ) : 0;
			$days = isset( $_GET['cleanup_days'] ) ? (int) $_GET['cleanup_days'] : -1;
			$age  = '';

			if ( array_key_exists( $days, CleanupService::manual_interval_choices() ) ) {
				$age = CleanupService::manual_interval_choices()[ $days ];
			}

			if ( 'cleanup_dry' === $_GET['wpp_notice'] ) {
				$message = $age
					/* translators: 1: age label, 2: number of folders */
					? sprintf( esc_html__( 'Dry run complete (%1$s): %2$d folder(s) would be deleted.', 'woo-product-personalizer' ), $age, $deleted )
					/* translators: %d: number of folders */
					: sprintf( esc_html__( 'Dry run complete. %d folder(s) would be deleted.', 'woo-product-personalizer' ), $deleted );
			} else {
				$message = $age
					/* translators: 1: age label, 2: number of folders */
					? sprintf( esc_html__( 'Cleanup complete (%1$s): %2$d folder(s) deleted.', 'woo-product-personalizer' ), $age, $deleted )
					/* translators: %d: number of folders */
					: sprintf( esc_html__( 'Cleanup complete. %d folder(s) deleted.', 'woo-product-personalizer' ), $deleted );
			}
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}

		include WPP_PLUGIN_PATH . 'templates/admin/settings-page.php';
	}
}
