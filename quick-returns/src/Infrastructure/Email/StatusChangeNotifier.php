<?php

namespace Weblo\QuickReturns\Infrastructure\Email;

use Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType;
use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;

class StatusChangeNotifier {

	public static function maybe_send( int $post_id, string $old_status, string $new_status ): void {
		if ( $old_status === $new_status ) {
			return;
		}

		$settings = SettingsRepository::get_all();
		if ( empty( $settings['email_status_change_enabled'] ) ) {
			return;
		}

		$email = get_post_meta( $post_id, '_qr_customer_email', true );
		if ( ! is_email( $email ) ) {
			return;
		}

		$request_number = get_post_meta( $post_id, '_qr_request_number', true );
		if ( ! $request_number ) {
			$request_number = get_the_title( $post_id );
		}

		$order_id     = (int) get_post_meta( $post_id, '_qr_order_id', true );
		$order_number = (string) $order_id;
		$order        = wc_get_order( $order_id );
		if ( $order ) {
			$order_number = $order->get_order_number();
		}

		$admin_note = get_post_meta( $post_id, '_qr_admin_note', true );

		$replace = [
			'{request_number}'        => $request_number,
			'{order_number}'          => $order_number,
			'{status}'                => $new_status,
			'{status_label}'          => ReturnRequestPostType::status_label( $new_status ),
			'{previous_status}'       => $old_status,
			'{previous_status_label}' => ReturnRequestPostType::status_label( $old_status ),
			'{admin_note}'            => is_string( $admin_note ) ? $admin_note : '',
		];

		$subject = str_replace(
			array_keys( $replace ),
			array_values( $replace ),
			$settings['email_status_change_subject'] ?? ''
		);

		$body = str_replace(
			array_keys( $replace ),
			array_values( $replace ),
			$settings['email_status_change_message'] ?? ''
		);

		if ( empty( $subject ) ) {
			$subject = sprintf(
				/* translators: %s: return request number */
				__( 'Return request %s – status update', 'quick-returns' ),
				$request_number
			);
		}

		wp_mail( $email, $subject, $body );
	}
}
