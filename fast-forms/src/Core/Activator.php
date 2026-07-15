<?php
/**
 * Plugin activation handler.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Core;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\UploadProtection;

/**
 * Obsługa aktywacji wtyczki.
 */
final class Activator {

	/**
	 * Rejestruje CPT i odświeża reguły permalinków.
	 */
	public static function activate(): void {
		( new FormPostType() )->register();
		( new EntryPostType() )->register();

		Capabilities::add_to_roles();

		$upload_dir = wp_upload_dir();

		if ( empty( $upload_dir['error'] ) ) {
			$fast_forms_dir = trailingslashit( (string) $upload_dir['basedir'] ) . 'fast-forms';

			if ( is_dir( $fast_forms_dir ) ) {
				UploadProtection::protect_directory( $fast_forms_dir );
			}
		}

		flush_rewrite_rules();
	}
}
