<?php

namespace Weblo\QuickReturns\Frontend;

use Weblo\QuickReturns\Domain\OrderLookupService;
use Weblo\QuickReturns\Domain\ReturnRequest;
use Weblo\QuickReturns\Infrastructure\Repository\ReturnRequestRepository;
use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;
use Weblo\QuickReturns\Infrastructure\Security\RateLimiter;

class AjaxHandler {

	private OrderLookupService $lookup;
	private ReturnRequestRepository $repository;
	private RateLimiter $rate_limiter;

	public function __construct() {
		$this->lookup        = new OrderLookupService();
		$this->repository    = new ReturnRequestRepository();
		$this->rate_limiter  = new RateLimiter();
	}

	public function register_hooks(): void {
		$actions = [
			'quick_returns_lookup_order',
			'quick_returns_get_items',
			'quick_returns_submit',
		];

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, [ $this, 'route' ] );
			add_action( 'wp_ajax_nopriv_' . $action, [ $this, 'route' ] );
		}
	}

	public function route(): void {
		check_ajax_referer( 'quick_returns', 'nonce' );

		$action = str_replace( 'wp_ajax_nopriv_', '', current_action() );
		$action = str_replace( 'wp_ajax_', '', $action );

		switch ( $action ) {
			case 'quick_returns_lookup_order':
				$this->lookup_order();
				break;
			case 'quick_returns_get_items':
				$this->get_items();
				break;
			case 'quick_returns_submit':
				$this->submit();
				break;
			default:
				wp_send_json_error( [ 'message' => __( 'Invalid action.', 'quick-returns' ) ], 400 );
		}
	}

	private function lookup_order(): void {
		$order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'manual_select';

		$ip = $this->get_client_ip();
		if ( ! $this->rate_limiter->is_allowed( 'lookup', $ip ) ) {
			wp_send_json_error( [ 'message' => __( 'Too many attempts. Please try again later.', 'quick-returns' ) ], 429 );
		}

		$order = $this->lookup->lookup_by_number_and_email( $order_number, $email );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => __( 'We could not find an order matching those details.', 'quick-returns' ) ] );
		}

		$payload = $this->lookup->build_order_payload( $order, $mode );
		if ( empty( $payload['success'] ) ) {
			wp_send_json_error( [ 'message' => $payload['message'] ?? '' ] );
		}

		wp_send_json_success( $payload );
	}

	private function get_items(): void {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$mode     = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'manual_select';

		$order = null;
		if ( is_user_logged_in() ) {
			$order = $this->lookup->lookup_for_current_user( $order_id );
		}

		if ( ! $order ) {
			wp_send_json_error( [ 'message' => __( 'Order not found or access denied.', 'quick-returns' ) ] );
		}

		$payload = $this->lookup->build_order_payload( $order, $mode );
		if ( empty( $payload['success'] ) ) {
			wp_send_json_error( [ 'message' => $payload['message'] ?? '' ] );
		}

		wp_send_json_success( $payload );
	}

	private function submit(): void {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$items    = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : [];
		$source   = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'shortcode';

		if ( ! is_array( $items ) ) {
			$items = json_decode( (string) $items, true );
		}
		if ( ! is_array( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid submission data.', 'quick-returns' ) ] );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => __( 'Order not found.', 'quick-returns' ) ] );
		}

		if ( is_user_logged_in() ) {
			$authorized = $this->lookup->lookup_for_current_user( $order_id );
			if ( ! $authorized ) {
				wp_send_json_error( [ 'message' => __( 'Access denied.', 'quick-returns' ) ] );
			}
		} else {
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( 0 !== strcasecmp( $order->get_billing_email(), $email ) ) {
				wp_send_json_error( [ 'message' => __( 'Access denied.', 'quick-returns' ) ] );
			}
		}

		$validation = $this->lookup->validate_submission( $order, $items );
		if ( empty( $validation['valid'] ) ) {
			wp_send_json_error( [ 'message' => $validation['message'] ?? '' ] );
		}

		$request_number = $this->repository->create_request_number();
		$request        = new ReturnRequest(
			$request_number,
			$order->get_id(),
			$order->get_order_key(),
			(int) $order->get_customer_id(),
			$order->get_billing_email(),
			$validation['items'],
			$validation['totals'],
			$source
		);

		$post_id = $this->repository->save( $request );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Could not save return request.', 'quick-returns' ) ] );
		}

		$this->send_emails( $request, $order );

		$settings = SettingsRepository::get_all();
		$message  = str_replace(
			[ '{order_number}', '{request_number}' ],
			[ $order->get_order_number(), $request_number ],
			$settings['confirmation_message']
		);

		wp_send_json_success(
			[
				'request_number' => $request_number,
				'order_number'   => $order->get_order_number(),
				'message'        => $message,
				'ship_notice'    => $settings['ship_back_notice'],
				'refund_notice'  => $settings['refund_hold_notice'],
				'return_address' => $settings['return_address'],
			]
		);
	}

	private function send_emails( ReturnRequest $request, \WC_Order $order ): void {
		$settings = SettingsRepository::get_all();
		$replace  = [
			'{request_number}' => $request->get_request_number(),
			'{order_number}'   => $order->get_order_number(),
		];

		$body = $settings['confirmation_message'];
		foreach ( $replace as $key => $value ) {
			$body = str_replace( $key, $value, $body );
		}

		if ( ! empty( $settings['return_address'] ) ) {
			$body .= "\n\n" . __( 'Return address:', 'quick-returns' ) . "\n" . $settings['return_address'];
		}

		if ( ! empty( $settings['email_customer_enabled'] ) ) {
			$subject = str_replace( array_keys( $replace ), array_values( $replace ), $settings['email_customer_subject'] );
			wp_mail( $request->get_customer_email(), $subject, $body );
		}

		if ( ! empty( $settings['email_admin_enabled'] ) ) {
			$admin_email = get_option( 'admin_email' );
			$subject     = str_replace( array_keys( $replace ), array_values( $replace ), $settings['email_admin_subject'] );
			wp_mail( $admin_email, $subject, $body );
		}
	}

	private function get_client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		return $ip;
	}
}
