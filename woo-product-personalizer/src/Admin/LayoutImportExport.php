<?php
/**
 * Import / export layout templates between sites.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Helpers\LayoutConfigSanitizer;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutImportExport
 */
class LayoutImportExport {

	const EXPORT_FORMAT = 'wpp-layout-export';
	const EXPORT_VERSION = '1.0';
	const FILE_EXTENSION = 'wpp-layout.json';

	/**
	 * Repository.
	 *
	 * @var LayoutRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new LayoutRepository();

		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'render_import_panel_after_filter' ) );
	}

	/**
	 * Export link in row actions.
	 *
	 * @param array    $actions Actions.
	 * @param \WP_Post $post    Post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( LayoutPostType::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'wpp_export_layout' => $post->ID,
				),
				admin_url( 'edit.php?post_type=' . LayoutPostType::POST_TYPE )
			),
			'wpp_export_layout_' . $post->ID
		);

		$actions['wpp_export'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Export', 'woo-product-personalizer' )
		);

		return $actions;
	}

	/**
	 * Import panel directly under #posts-filter (outside the list table form).
	 *
	 * @return void
	 */
	public function render_import_panel_after_filter() {
		if ( ! $this->is_layout_list_screen() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
		<template id="wpp-layout-import-export-template">
			<?php include WPP_PLUGIN_PATH . 'templates/admin/layout-import-export-panel.php'; ?>
		</template>
		<script>
		(function () {
			var form = document.getElementById('posts-filter');
			var tpl = document.getElementById('wpp-layout-import-export-template');
			if (!form || !tpl || !tpl.content) {
				return;
			}
			var wrap = document.createElement('div');
			wrap.className = 'wpp-layout-import-export-wrap';
			wrap.appendChild(tpl.content.cloneNode(true));
			form.insertAdjacentElement('afterend', wrap);
		})();
		</script>
		<?php
	}

	/**
	 * Whether current screen is the layouts list table.
	 *
	 * @return bool
	 */
	private function is_layout_list_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen
			&& 'edit' === $screen->base
			&& LayoutPostType::POST_TYPE === $screen->post_type;
	}

	/**
	 * Download export file.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( empty( $_GET['wpp_export_layout'] ) ) {
			return;
		}

		$layout_id = absint( $_GET['wpp_export_layout'] );
		if ( ! $layout_id || ! current_user_can( 'edit_post', $layout_id ) ) {
			return;
		}

		check_admin_referer( 'wpp_export_layout_' . $layout_id );

		$post = get_post( $layout_id );
		if ( ! $post || LayoutPostType::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'Layout not found.', 'woo-product-personalizer' ) );
		}

		$config = get_post_meta( $layout_id, LayoutRepository::META_CONFIG, true );
		if ( ! is_array( $config ) ) {
			wp_die( esc_html__( 'Layout configuration is empty.', 'woo-product-personalizer' ) );
		}

		$package = $this->build_export_package( $post->post_title, $config );
		$json    = wp_json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( ! $json ) {
			wp_die( esc_html__( 'Failed to encode export file.', 'woo-product-personalizer' ) );
		}

		$filename = sanitize_file_name( $post->post_name ?: 'layout' );
		if ( '' === $filename ) {
			$filename = 'layout';
		}
		$filename .= '-' . $layout_id . '.' . self::FILE_EXTENSION;

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		exit;
	}

	/**
	 * Handle uploaded import file.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( empty( $_POST['wpp_layout_import_submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		check_admin_referer( 'wpp_layout_import', 'wpp_layout_import_nonce' );

		if ( empty( $_FILES['wpp_layout_import_file']['tmp_name'] ) ) {
			$this->redirect_import_result( false, __( 'No file uploaded.', 'woo-product-personalizer' ) );
		}

		$file = $_FILES['wpp_layout_import_file'];
		if ( ! empty( $file['error'] ) ) {
			$this->redirect_import_result( false, __( 'Upload error.', 'woo-product-personalizer' ) );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'json' !== $ext ) {
			$this->redirect_import_result( false, __( 'Invalid file type. Upload a .json export file.', 'woo-product-personalizer' ) );
		}

		if ( ! empty( $file['size'] ) && (int) $file['size'] > 5 * 1024 * 1024 ) {
			$this->redirect_import_result( false, __( 'Import file is too large (max 5 MB).', 'woo-product-personalizer' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $file['tmp_name'] );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			$this->redirect_import_result( false, __( 'Invalid JSON file.', 'woo-product-personalizer' ) );
		}

		$result = $this->import_package( $data );

		if ( is_wp_error( $result ) ) {
			$this->redirect_import_result( false, $result->get_error_message() );
		}

		$this->redirect_import_result( true, '', (int) $result );
	}

	/**
	 * Admin notices for import result.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( empty( $_GET['wpp_layout_import_status'] ) || LayoutPostType::POST_TYPE !== ( $_GET['post_type'] ?? '' ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['wpp_layout_import_status'] ) );
		$msg    = isset( $_GET['wpp_layout_import_message'] ) ? sanitize_text_field( wp_unslash( $_GET['wpp_layout_import_message'] ) ) : '';

		if ( 'success' === $status ) {
			$edit_id = isset( $_GET['wpp_layout_id'] ) ? absint( $_GET['wpp_layout_id'] ) : 0;
			$text    = __( 'Layout imported successfully.', 'woo-product-personalizer' );
			if ( $edit_id ) {
				$text .= ' <a href="' . esc_url( get_edit_post_link( $edit_id, 'raw' ) ) . '">' . esc_html__( 'Edit layout', 'woo-product-personalizer' ) . '</a>';
			}
			echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $text ) . '</p></div>';
			return;
		}

		if ( 'error' === $status ) {
			$text = $msg ? $msg : __( 'Layout import failed.', 'woo-product-personalizer' );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
		}
	}

	/**
	 * Build portable export package with embedded assets.
	 *
	 * @param string $title  Layout title.
	 * @param array  $config Layout config.
	 * @return array
	 */
	private function build_export_package( $title, array $config ) {
		$assets      = array();
		$config_copy = $config;
		$index       = 0;

		$replace = function ( &$value ) use ( &$assets, &$index ) {
			if ( empty( $value ) || ! is_string( $value ) ) {
				return;
			}

			$binary = $this->read_asset_binary( $value );
			if ( false === $binary ) {
				return;
			}

			$key = $this->asset_key_for_url( $value, $index++ );
			$assets[ $key ] = array(
				'filename' => basename( $key ),
				'mime'     => $binary['mime'],
				'data'     => base64_encode( $binary['data'] ),
			);
			$value = $key;
		};

		if ( ! empty( $config_copy['canvas']['background'] ) ) {
			$replace( $config_copy['canvas']['background'] );
		}
		if ( ! empty( $config_copy['canvas']['overlay'] ) ) {
			$replace( $config_copy['canvas']['overlay'] );
		}
		if ( ! empty( $config_copy['image_slots'] ) && is_array( $config_copy['image_slots'] ) ) {
			foreach ( $config_copy['image_slots'] as $i => $slot ) {
				if ( ! empty( $slot['mask'] ) ) {
					$mask = $slot['mask'];
					$replace( $mask );
					$config_copy['image_slots'][ $i ]['mask'] = $mask;
				}
			}
		}

		return array(
			'format'          => self::EXPORT_FORMAT,
			'version'         => self::EXPORT_VERSION,
			'plugin_version'  => WPP_VERSION,
			'exported_at'     => gmdate( 'c' ),
			'title'           => sanitize_text_field( $title ),
			'config'          => $config_copy,
			'assets'          => $assets,
		);
	}

	/**
	 * Import package and create layout post.
	 *
	 * @param array $data Export data.
	 * @return int|\WP_Error Post ID.
	 */
	private function import_package( array $data ) {
		if ( empty( $data['format'] ) || self::EXPORT_FORMAT !== $data['format'] ) {
			return new \WP_Error( 'wpp_import_format', __( 'Unrecognized export format.', 'woo-product-personalizer' ) );
		}

		if ( empty( $data['config'] ) || ! is_array( $data['config'] ) ) {
			return new \WP_Error( 'wpp_import_config', __( 'Missing layout configuration.', 'woo-product-personalizer' ) );
		}

		$title  = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'Imported layout', 'woo-product-personalizer' );
		$config = $data['config'];
		$assets = ! empty( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array();

		$post_id = wp_insert_post(
			array(
				'post_type'   => LayoutPostType::POST_TYPE,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$config = $this->restore_asset_urls( $config, $assets, (int) $post_id );
		$config = LayoutConfigSanitizer::sanitize( $config );
		$this->repository->save_config( (int) $post_id, $config );
		update_post_meta( $post_id, LayoutRepository::META_VERSION, WPP_VERSION );

		return (int) $post_id;
	}

	/**
	 * Replace asset keys with URLs on this site.
	 *
	 * @param array $config  Config.
	 * @param array $assets  Assets map.
	 * @param int   $post_id Layout post ID.
	 * @return array
	 */
	private function restore_asset_urls( array $config, array $assets, $post_id ) {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . WPP_UPLOADS_SUBDIR . '/layouts/' . $post_id;
		$url    = trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR . '/layouts/' . $post_id;

		if ( ! wp_mkdir_p( $dir ) ) {
			return $config;
		}

		$map = array();

		foreach ( $assets as $key => $asset ) {
			if ( empty( $asset['data'] ) ) {
				continue;
			}

			$binary = base64_decode( (string) $asset['data'], true );
			if ( false === $binary ) {
				continue;
			}

			$filename = ! empty( $asset['filename'] ) ? sanitize_file_name( $asset['filename'] ) : sanitize_file_name( basename( (string) $key ) );
			if ( '' === $filename ) {
				$filename = 'asset-' . md5( (string) $key ) . '.png';
			}

			$path = trailingslashit( $dir ) . $filename;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false !== file_put_contents( $path, $binary ) ) {
				$map[ (string) $key ] = trailingslashit( $url ) . $filename;
			}
		}

		$replace = function ( &$value ) use ( $map ) {
			if ( is_string( $value ) && isset( $map[ $value ] ) ) {
				$value = $map[ $value ];
			}
		};

		if ( isset( $config['canvas']['background'] ) ) {
			$replace( $config['canvas']['background'] );
		}
		if ( isset( $config['canvas']['overlay'] ) ) {
			$replace( $config['canvas']['overlay'] );
		}
		if ( ! empty( $config['image_slots'] ) && is_array( $config['image_slots'] ) ) {
			foreach ( $config['image_slots'] as $i => $slot ) {
				if ( isset( $slot['mask'] ) ) {
					$mask = $slot['mask'];
					$replace( $mask );
					$config['image_slots'][ $i ]['mask'] = $mask;
				}
			}
		}

		return $config;
	}

	/**
	 * Read local asset bytes from URL or path.
	 *
	 * @param string $url URL or relative path.
	 * @return array{data: string, mime: string}|false
	 */
	private function read_asset_binary( $url ) {
		$path = $this->url_to_path( $url );
		if ( ! $path || ! is_readable( $path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = file_get_contents( $path );
		if ( false === $data ) {
			return false;
		}

		$mime = wp_check_filetype( $path );
		$type = ! empty( $mime['type'] ) ? $mime['type'] : 'application/octet-stream';

		return array(
			'data' => $data,
			'mime' => $type,
		);
	}

	/**
	 * Resolve public URL to local filesystem path.
	 *
	 * @param string $url URL or path.
	 * @return string|false
	 */
	private function url_to_path( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$relative = ltrim( $url, '/' );
			$candidates = array(
				WPP_PLUGIN_PATH . $relative,
			);
			foreach ( $candidates as $path ) {
				if ( file_exists( $path ) ) {
					return $path;
				}
			}
			return false;
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['baseurl'] ) && 0 === strpos( $url, $upload['baseurl'] ) ) {
			$rel = ltrim( substr( $url, strlen( $upload['baseurl'] ) ), '/' );
			$path = trailingslashit( $upload['basedir'] ) . $rel;
			$path = strtok( $path, '?' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		if ( defined( 'WPP_PLUGIN_URL' ) && 0 === strpos( $url, WPP_PLUGIN_URL ) ) {
			$rel = ltrim( substr( $url, strlen( WPP_PLUGIN_URL ) ), '/' );
			$path = WPP_PLUGIN_PATH . $rel;
			$path = strtok( $path, '?' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['path'] ) ) {
			return false;
		}

		$site_url = wp_parse_url( site_url() );
		if ( ! empty( $site_url['path'] ) && 0 === strpos( $parsed['path'], $site_url['path'] ) ) {
			$rel = ltrim( substr( $parsed['path'], strlen( $site_url['path'] ) ), '/' );
			$path = ABSPATH . $rel;
			$path = strtok( $path, '?' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Stable asset key for export map.
	 *
	 * @param string $url   Source URL.
	 * @param int    $index Counter.
	 * @return string
	 */
	private function asset_key_for_url( $url, $index ) {
		$basename = basename( wp_parse_url( $url, PHP_URL_PATH ) ?: $url );
		$basename = sanitize_file_name( $basename );
		if ( '' === $basename ) {
			$basename = 'asset.png';
		}
		return 'assets/' . $index . '-' . $basename;
	}

	/**
	 * Redirect after import attempt.
	 *
	 * @param bool   $success Success.
	 * @param string $message Error message.
	 * @param int    $post_id Created post ID.
	 * @return void
	 */
	private function redirect_import_result( $success, $message = '', $post_id = 0 ) {
		$args = array(
			'post_type'                => LayoutPostType::POST_TYPE,
			'wpp_layout_import_status' => $success ? 'success' : 'error',
		);

		if ( ! $success && $message ) {
			$args['wpp_layout_import_message'] = rawurlencode( $message );
		}

		if ( $success && $post_id ) {
			$args['wpp_layout_id'] = $post_id;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
		exit;
	}
}
