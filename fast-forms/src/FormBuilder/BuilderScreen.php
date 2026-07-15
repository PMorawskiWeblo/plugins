<?php
/**
 * Form builder admin screen.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;

/**
 * Integruje builder z ekranem edycji CPT formularza.
 */
final class BuilderScreen {

	/**
	 * Rejestruje hooki ekranu buildera.
	 */
	public function register(): void {
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
		add_action( 'edit_form_after_title', array( $this, 'render_builder' ) );
		add_action( 'admin_head', array( $this, 'hide_default_editor' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
	}

	/**
	 * Rejestruje metaboxy formularza.
	 */
	public function register_meta_boxes(): void {
		add_meta_box(
			'ff_form_shortcodes',
			__( 'Shortcodes', 'fast-forms' ),
			array( $this, 'render_shortcodes_metabox' ),
			FormPostType::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Renderuje metabox ze shortcodami pod „Opublikuj”.
	 *
	 * @param \WP_Post $post Edytowany wpis.
	 */
	public function render_shortcodes_metabox( \WP_Post $post ): void {
		if ( ! Capabilities::can_edit_form( (int) $post->ID ) ) {
			return;
		}

		$template = FF_PLUGIN_DIR . 'templates/admin/form-shortcodes-metabox.php';

		if ( ! is_readable( $template ) ) {
			return;
		}

		include $template;
	}

	/**
	 * Wyłącza edytor blokowy dla formularzy.
	 *
	 * @param bool   $use       Czy używać edytora blokowego.
	 * @param string $post_type Typ wpisu.
	 */
	public function disable_block_editor( bool $use, string $post_type ): bool {
		if ( FormPostType::POST_TYPE === $post_type ) {
			return false;
		}

		return $use;
	}

	/**
	 * Ukrywa domyślny edytor treści.
	 */
	public function hide_default_editor(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || FormPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		echo '<style>#postdivrich,#wp-content-wrap{display:none!important;}</style>';
	}

	/**
	 * Renderuje kontener buildera formularza.
	 *
	 * @param \WP_Post $post Edytowany wpis.
	 */
	public function render_builder( \WP_Post $post ): void {
		if ( FormPostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! Capabilities::can_edit_form( (int) $post->ID ) ) {
			return;
		}

		$template = FF_PLUGIN_DIR . 'templates/admin/form-builder.php';

		if ( ! is_readable( $template ) ) {
			return;
		}

		include $template;
	}
}
