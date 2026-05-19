<?php
/**
 * Top-level admin menu for Product Personalizer.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminMenu
 */
class AdminMenu {

	const PARENT_SLUG   = 'wpp-dashboard';
	const SETTINGS_SLUG = 'wpp-settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'remove_duplicate_submenu' ), 99 );
		add_filter( 'parent_file', array( $this, 'highlight_parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_submenu' ) );
	}

	/**
	 * Remove auto-added submenu duplicate of the parent item.
	 *
	 * @return void
	 */
	public function remove_duplicate_submenu() {
		remove_submenu_page( self::PARENT_SLUG, self::PARENT_SLUG );
	}

	/**
	 * Keep Product Personalizer menu expanded on plugin screens.
	 *
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	public function highlight_parent_menu( $parent_file ) {
		if ( $this->is_plugin_admin_screen() ) {
			return self::PARENT_SLUG;
		}

		return $parent_file;
	}

	/**
	 * Highlight the active submenu item.
	 *
	 * @param string $submenu_file Submenu file.
	 * @return string
	 */
	public function highlight_submenu( $submenu_file ) {
		if ( $this->is_settings_screen() ) {
			return self::SETTINGS_SLUG;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || LayoutPostType::POST_TYPE !== $screen->post_type ) {
			return $submenu_file;
		}

		if ( 'post' === $screen->base && 'add' === $screen->action ) {
			return 'post-new.php?post_type=' . LayoutPostType::POST_TYPE;
		}

		return 'edit.php?post_type=' . LayoutPostType::POST_TYPE;
	}

	/**
	 * Whether the current request is a plugin admin screen.
	 *
	 * @return bool
	 */
	private function is_plugin_admin_screen() {
		if ( $this->is_settings_screen() ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && LayoutPostType::POST_TYPE === $screen->post_type;
	}

	/**
	 * Whether the current request is the settings page.
	 *
	 * @return bool
	 */
	private function is_settings_screen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return in_array( $page, array( self::PARENT_SLUG, self::SETTINGS_SLUG ), true );
	}
}
