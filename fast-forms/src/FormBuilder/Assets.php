<?php
/**
 * Form builder assets.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\AssetVersion;
use Weblo\FastForms\Support\DebugLog;
use Weblo\FastForms\Support\GlobalSettings;
use Weblo\FastForms\Support\UploadPath;

/**
 * Ładuje skrypty i style buildera (jQuery UI).
 */
final class Assets {

	/**
	 * Rejestruje hook enqueue.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Ładuje assety tylko na ekranie edycji formularza.
	 *
	 * @param string $hook Bieżący hook admina.
	 */
	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || FormPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		wp_enqueue_style(
			'fast-forms-select2',
			FF_PLUGIN_URL . 'assets/admin/vendor/select2/select2.min.css',
			array(),
			'4.0.13'
		);

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-tabs' );

		wp_enqueue_style(
			'fast-forms-builder',
			FF_PLUGIN_URL . 'assets/admin/css/form-builder.css',
			array( 'wp-jquery-ui-dialog', 'fast-forms-select2' ),
			AssetVersion::get( 'assets/admin/css/form-builder.css' )
		);

		wp_enqueue_script(
			'fast-forms-select2',
			FF_PLUGIN_URL . 'assets/admin/vendor/select2/select2.min.js',
			array( 'jquery' ),
			'4.0.13',
			true
		);

		wp_enqueue_script(
			'fast-forms-builder',
			FF_PLUGIN_URL . 'assets/admin/js/form-builder.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'fast-forms-select2' ),
			AssetVersion::get( 'assets/admin/js/form-builder.js' ),
			true
		);

		$schema       = FormSchemaStorage::get( $post->ID );
		$form_slug    = sanitize_title( $post->post_name );
		$global_opts  = GlobalSettings::get();

		if ( '' === $form_slug ) {
			$form_slug = sanitize_title( $post->post_title );
		}

		if ( '' === $form_slug ) {
			$form_slug = 'form-' . $post->ID;
		}

		DebugLog::info(
			'Builder assets loaded',
			array(
				'form_id'       => $post->ID,
				'field_count'   => DebugLog::count_schema_fields( $schema ),
				'row_count'     => count( $schema['rows'] ?? array() ),
				'schema_version' => (int) get_post_meta( $post->ID, FormPostType::META_SCHEMA_VERSION, true ),
			)
		);

		wp_localize_script(
			'fast-forms-builder',
			'fastFormsBuilder',
			array(
				'formId'            => $post->ID,
				'restUrl'           => rest_url( RestApi::NAMESPACE ),
				'pagesRestUrl'      => rest_url( 'wp/v2/pages' ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'developerDebug'    => DebugLog::is_enabled(),
				'fieldTypes'        => BuilderI18n::get_field_type_labels(),
				'i18n'              => BuilderI18n::get_js_i18n(),
				'globalUploadPath'  => (string) ( $global_opts['uploadPath'] ?? UploadPath::DEFAULT_PATTERN ),
				'formSlug'          => $form_slug,
				'formTitle'         => sanitize_file_name( $post->post_title ) ?: $form_slug,
				'uploadsBaseUrl'    => trailingslashit( wp_upload_dir()['baseurl'] ?? '' ),
			)
		);
	}
}
