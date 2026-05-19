<?php
/**
 * Uploads directory manager.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Uploads;

use WooProductPersonalizer\Core\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class UploadsManager
 */
class UploadsManager {

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Logger $logger Logger.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Base uploads path.
	 *
	 * @return string
	 */
	public function base_path() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . WPP_UPLOADS_SUBDIR;
	}

	/**
	 * Base uploads URL.
	 *
	 * @return string
	 */
	public function base_url() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR;
	}

	/**
	 * Map a plugin or uploads URL to a local filesystem path.
	 *
	 * @param string $url Public URL.
	 * @return string|false
	 */
	public function url_to_local_path( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';

		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}

		$url = strtok( $url, '?' );
		$upload = wp_upload_dir();

		if ( ! empty( $upload['baseurl'] ) && 0 === strpos( $url, $upload['baseurl'] ) ) {
			$rel  = ltrim( substr( $url, strlen( $upload['baseurl'] ) ), '/' );
			$path = trailingslashit( $upload['basedir'] ) . $rel;

			return is_readable( $path ) ? $path : false;
		}

		if ( defined( 'WPP_PLUGIN_URL' ) && 0 === strpos( $url, WPP_PLUGIN_URL ) ) {
			$rel  = ltrim( substr( $url, strlen( WPP_PLUGIN_URL ) ), '/' );
			$path = WPP_PLUGIN_PATH . $rel;

			return is_readable( $path ) ? $path : false;
		}

		return false;
	}

	/**
	 * Ensure base directories exist.
	 *
	 * @return void
	 */
	public function ensure_directories() {
		$dirs = array(
			$this->base_path(),
			$this->base_path() . '/temp',
			$this->base_path() . '/cart-previews',
			$this->base_path() . '/orders',
			$this->base_path() . '/logs',
		);

		foreach ( $dirs as $dir ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				$this->logger->error( 'Failed to create directory.', array( 'dir' => $dir ) );
			}
		}

		$this->protect_directory( $this->base_path() );
		$this->protect_directory( $this->base_path() . '/logs', true );
	}

	/**
	 * Order project directory path.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public function order_path( $order_id ) {
		return trailingslashit( $this->base_path() ) . 'orders/' . absint( $order_id );
	}

	/**
	 * Order project directory URL.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public function order_url( $order_id ) {
		return trailingslashit( $this->base_url() ) . 'orders/' . absint( $order_id );
	}

	/**
	 * Create order project folders.
	 *
	 * @param int $order_id Order ID.
	 * @return string|false
	 */
	public function create_order_directory( $order_id ) {
		$path = $this->order_path( $order_id );

		if ( ! wp_mkdir_p( $path . '/attachments' ) ) {
			$this->logger->error( 'Failed to create order directory.', array( 'order_id' => $order_id ) );
			return false;
		}

		$this->protect_directory( $path );
		return $path;
	}

	/**
	 * Temp directory for session uploads.
	 *
	 * @param string $hash Session hash.
	 * @return string|false
	 */
	public function temp_path( $hash ) {
		$hash = sanitize_file_name( $hash );
		$path = trailingslashit( $this->base_path() ) . 'temp/' . $hash;

		if ( ! wp_mkdir_p( $path ) ) {
			return false;
		}

		return $path;
	}

	/**
	 * Store a validated temp upload for the current shopper session.
	 *
	 * @param array  $file          $_FILES entry.
	 * @param string $token         Session upload token.
	 * @param array  $allowed_mimes Allowed MIME types.
	 * @return array{url: string, type: string}|\WP_Error
	 */
	public function store_temp_upload( array $file, $token, array $allowed_mimes ) {
		$token = sanitize_file_name( (string) $token );
		if ( '' === $token ) {
			return new \WP_Error( 'wpp_upload_token', __( 'Upload session is not available.', 'woo-product-personalizer' ) );
		}

		$dir = $this->temp_path( $token );
		if ( ! $dir ) {
			return new \WP_Error( 'wpp_upload_dir', __( 'Could not create upload directory.', 'woo-product-personalizer' ) );
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed_mimes, true ) ) {
			return new \WP_Error( 'wpp_upload_type', __( 'Invalid file type.', 'woo-product-personalizer' ) );
		}

		$filename = sanitize_file_name( $file['name'] );
		if ( '' === $filename ) {
			$filename = 'upload-' . time();
		}

		$filename = wp_unique_filename( $dir, $filename );
		$dest     = trailingslashit( $dir ) . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_uploaded_file
		if ( ! is_uploaded_file( $file['tmp_name'] ) || ! @move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return new \WP_Error( 'wpp_upload_move', __( 'Failed to store uploaded file.', 'woo-product-personalizer' ) );
		}

		$url = trailingslashit( $this->base_url() ) . 'temp/' . $token . '/' . $filename;

		return array(
			'url'  => $url,
			'type' => $checked['type'],
		);
	}

	/**
	 * Add index.php and optional .htaccess protection.
	 *
	 * @param string $dir       Directory.
	 * @param bool   $deny_http Block direct HTTP access (Apache).
	 * @return void
	 */
	private function protect_directory( $dir, $deny_http = false ) {
		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}

		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		if ( ! $deny_http ) {
			return;
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( file_exists( $htaccess ) ) {
			return;
		}

		$rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $htaccess, $rules );
	}

	/**
	 * Build ZIP archive for an order personalization folder.
	 *
	 * @param int $order_id Order ID.
	 * @return string|false ZIP file path.
	 */
	public function build_order_zip( $order_id ) {
		$order_id = absint( $order_id );
		$dir      = $this->order_path( $order_id );

		if ( ! is_dir( $dir ) ) {
			return false;
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->logger->error( 'ZipArchive is not available.', array( 'order_id' => $order_id ) );
			return false;
		}

		$zip_path = $dir . '/order-' . $order_id . '-personalization.zip';

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
				continue;
			}

			$pathname = $file->getRealPath();
			if ( ! $pathname || $pathname === $zip_path ) {
				continue;
			}

			$relative = ltrim( str_replace( $dir, '', $pathname ), DIRECTORY_SEPARATOR );
			$zip->addFile( $pathname, $relative );
		}

		$zip->close();

		return file_exists( $zip_path ) ? $zip_path : false;
	}
}
