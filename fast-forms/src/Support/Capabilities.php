<?php
/**
 * Plugin capabilities.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Centralne uprawnienia wtyczki Fast Forms.
 */
final class Capabilities {

	public const MANAGE_FORMS = 'manage_fast_forms';

	/**
	 * Dodaje capability do ról administracyjnych.
	 */
	public static function add_to_roles(): void {
		$roles = array( 'administrator', 'editor' );

		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );

			if ( $role ) {
				$role->add_cap( self::MANAGE_FORMS );
			}
		}
	}

	/**
	 * Usuwa capability z ról.
	 */
	public static function remove_from_roles(): void {
		$roles = wp_roles();

		if ( ! $roles ) {
			return;
		}

		foreach ( array_keys( $roles->roles ) as $role_slug ) {
			$role = get_role( $role_slug );

			if ( $role ) {
				$role->remove_cap( self::MANAGE_FORMS );
			}
		}
	}

	/**
	 * Czy użytkownik może zarządzać formularzami i zgłoszeniami.
	 */
	public static function can_manage(): bool {
		return current_user_can( self::MANAGE_FORMS ) || current_user_can( 'manage_options' );
	}

	/**
	 * Czy użytkownik może edytować dany formularz.
	 *
	 * @param int $form_id ID formularza.
	 */
	public static function can_edit_form( int $form_id ): bool {
		return current_user_can( 'edit_post', $form_id );
	}
}
