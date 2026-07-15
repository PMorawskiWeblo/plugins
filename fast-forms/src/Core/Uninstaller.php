<?php
/**
 * Plugin uninstall handler.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Core;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\GlobalSettings;

/**
 * Czyszczenie danych przy odinstalowaniu wtyczki.
 */
final class Uninstaller {

	/**
	 * Uruchamia procedurę odinstalowania.
	 */
	public static function run(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		$settings = GlobalSettings::get();

		if ( ! empty( $settings['deleteDataOnUninstall'] ) ) {
			self::delete_posts( FormPostType::POST_TYPE );
			self::delete_posts( EntryPostType::POST_TYPE );
			delete_option( GlobalSettings::OPTION_KEY );
			self::delete_uploads();
		}

		self::delete_transients();
		Capabilities::remove_from_roles();

		flush_rewrite_rules();
	}

	/**
	 * Usuwa wszystkie wpisy danego typu.
	 *
	 * @param string $post_type Typ wpisu.
	 */
	private static function delete_posts( string $post_type ): void {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
	}

	/**
	 * Usuwa katalog uploadów wtyczki.
	 */
	private static function delete_uploads(): void {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return;
		}

		$path = trailingslashit( $upload_dir['basedir'] ) . 'fast-forms';

		if ( is_dir( $path ) ) {
			self::delete_directory( $path );
		}
	}

	/**
	 * Rekurencyjnie usuwa katalog.
	 *
	 * @param string $directory Ścieżka katalogu.
	 */
	private static function delete_directory( string $directory ): void {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$items = scandir( $directory );

		if ( false === $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( in_array( $item, array( '.', '..' ), true ) ) {
				continue;
			}

			$path = $directory . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $path ) ) {
				self::delete_directory( $path );
				continue;
			}

			wp_delete_file( $path );
		}

		rmdir( $directory );
	}

	/**
	 * Usuwa transients wtyczki.
	 */
	private static function delete_transients(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_ff_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_ff_' ) . '%'
			)
		);
	}
}
