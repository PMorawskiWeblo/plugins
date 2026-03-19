<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Weblo_Fakturownia_Order_Metabox {

	const NONCE_ACTION = 'weblo_fakturownia_order_metabox';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post_shop_order', array( $this, 'save_post' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_weblo_issue_invoice_now', array( $this, 'ajax_issue_invoice_now' ) );
		add_action( 'wp_ajax_weblo_send_invoice_email', array( $this, 'ajax_send_invoice_email' ) );
		add_action( 'wp_ajax_weblo_download_invoice_pdf', array( $this, 'ajax_download_invoice_pdf' ) );
		add_action( 'wp_ajax_weblo_issue_correction_now', array( $this, 'ajax_issue_correction_now' ) );
		add_action( 'wp_ajax_weblo_send_correction_email', array( $this, 'ajax_send_correction_email' ) );
	}

	public function register_metabox() {
		$screens = array( 'shop_order' );

		// Wsparcie dla HPOS / nowego ekranu zamówień.
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		$screens[] = 'woocommerce_page_wc-orders';

		$screens = array_values( array_unique( array_filter( $screens ) ) );

		foreach ( $screens as $screen_id ) {
			add_meta_box(
				'weblo_fakturownia_order_metabox',
				__( 'Fakturownia', 'weblo-fakturownia' ),
				array( $this, 'render' ),
				$screen_id,
				'side',
				'default'
			);
		}
	}

	/**
	 * @param WP_Post $post
	 */
	public function render( $post ) {
		$order = wc_get_order( $post->ID );
		$order_id = $order ? $order->get_id() : (int) $post->ID;

		// HPOS: meta zapisujemy/czytamy przez WC_Order.
		$invoice_id     = $order ? $order->get_meta( '_weblo_fakturownia_invoice_id', true ) : get_post_meta( $order_id, '_weblo_fakturownia_invoice_id', true );
		$invoice_number = $order ? $order->get_meta( '_weblo_fakturownia_invoice_number', true ) : get_post_meta( $order_id, '_weblo_fakturownia_invoice_number', true );
		$last_error     = $order ? $order->get_meta( '_weblo_fakturownia_last_error', true ) : get_post_meta( $order_id, '_weblo_fakturownia_last_error', true );

		wp_nonce_field( self::NONCE_ACTION, 'weblo_fakturownia_order_nonce' );

		echo '<div class="weblo-fakturownia-order-box" data-order-id="' . esc_attr( $order_id ) . '">';

		if ( ! empty( $last_error ) ) {
			$pretty_error = $this->build_pretty_error_message( (string) $last_error );
			if ( '' !== $pretty_error ) {
				echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'What happened?', 'weblo-fakturownia' ) . '</strong><br>' . esc_html( $pretty_error ) . '</p></div>';
			}
			echo '<div class="notice notice-error inline"><p><strong>' . esc_html__( 'Last error:', 'weblo-fakturownia' ) . '</strong></p><pre style="margin:0;white-space:pre-wrap;word-break:break-word;">' . esc_html( $this->format_error_for_display( (string) $last_error ) ) . '</pre></div>';
		}

		echo '<h4 style="margin:10px 0 6px;">' . esc_html__( 'Invoice', 'weblo-fakturownia' ) . '</h4>';

		if ( $invoice_id ) {
			$settings = Weblo_Fakturownia_Plugin::instance()->get_settings();
			$domain   = isset( $settings['weblo_fakturownia_domain'] ) ? (string) $settings['weblo_fakturownia_domain'] : '';
			$domain   = preg_replace( '#^https?://#', '', trim( $domain ) );

			$invoice_url = ( '' !== $domain ) ? ( 'https://' . $domain . '/invoices/' . rawurlencode( (string) $invoice_id ) ) : '#';

			echo '<p style="margin:0 0 8px;">';
			echo esc_html__( 'Invoice:', 'weblo-fakturownia' ) . ' ';
			echo '<strong>' . esc_html( $invoice_number ? (string) $invoice_number : '#' . (string) $invoice_id ) . '</strong><br>';
			if ( '#' !== $invoice_url ) {
				echo '<a href="' . esc_url( $invoice_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open in Fakturownia', 'weblo-fakturownia' ) . '</a>';
			}
			echo '</p>';

			echo '<p style="margin:0 0 8px;">';
			echo '<a class="button button-small" href="' . esc_url( $this->build_pdf_download_url( $order_id, $invoice_id ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download PDF', 'weblo-fakturownia' ) . '</a> ';
			echo '<button type="button" class="button button-small weblo-fakturownia-send-invoice-email" data-order-id="' . esc_attr( $order_id ) . '" data-invoice-id="' . esc_attr( $invoice_id ) . '">' . esc_html__( 'Send email', 'weblo-fakturownia' ) . '</button>';
			echo '</p>';
		} else {
			echo '<p style="margin:0 0 8px;">' . esc_html__( 'No invoice for this order.', 'weblo-fakturownia' ) . '</p>';
			echo '<p style="margin:0 0 8px;">';
			echo '<button type="button" class="button button-primary weblo-fakturownia-issue-invoice-now" data-order-id="' . esc_attr( $order_id ) . '">' . esc_html__( 'Issue invoice now', 'weblo-fakturownia' ) . '</button>';
			echo '</p>';
		}

		echo '<hr style="margin:10px 0;">';
		echo '<h4 style="margin:10px 0 6px;">' . esc_html__( 'Correction', 'weblo-fakturownia' ) . '</h4>';

		$correction_id     = $order ? $order->get_meta( '_weblo_fakturownia_correction_id', true ) : get_post_meta( $order_id, '_weblo_fakturownia_correction_id', true );
		$correction_number = $order ? $order->get_meta( '_weblo_fakturownia_correction_number', true ) : get_post_meta( $order_id, '_weblo_fakturownia_correction_number', true );
		$correction_error  = $order ? $order->get_meta( '_weblo_fakturownia_correction_last_error', true ) : get_post_meta( $order_id, '_weblo_fakturownia_correction_last_error', true );

		if ( ! empty( $correction_error ) ) {
			$pretty_error = $this->build_pretty_error_message( (string) $correction_error );
			if ( '' !== $pretty_error ) {
				echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'What happened?', 'weblo-fakturownia' ) . '</strong><br>' . esc_html( $pretty_error ) . '</p></div>';
			}
			echo '<div class="notice notice-error inline"><p><strong>' . esc_html__( 'Correction error:', 'weblo-fakturownia' ) . '</strong></p><pre style="margin:0;white-space:pre-wrap;word-break:break-word;">' . esc_html( $this->format_error_for_display( (string) $correction_error ) ) . '</pre></div>';
		}

		if ( $correction_id ) {
			$settings = Weblo_Fakturownia_Plugin::instance()->get_settings();
			$domain   = isset( $settings['weblo_fakturownia_domain'] ) ? (string) $settings['weblo_fakturownia_domain'] : '';
			$domain   = preg_replace( '#^https?://#', '', trim( $domain ) );
			$correction_url = ( '' !== $domain ) ? ( 'https://' . $domain . '/invoices/' . rawurlencode( (string) $correction_id ) ) : '#';

			echo '<p style="margin:0 0 8px;">';
			echo esc_html__( 'Correction:', 'weblo-fakturownia' ) . ' ';
			echo '<strong>' . esc_html( $correction_number ? (string) $correction_number : '#' . (string) $correction_id ) . '</strong><br>';
			if ( '#' !== $correction_url ) {
				echo '<a href="' . esc_url( $correction_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open in Fakturownia', 'weblo-fakturownia' ) . '</a>';
			}
			echo '</p>';

			echo '<p style="margin:0 0 8px;">';
			echo '<a class="button button-small" href="' . esc_url( $this->build_pdf_download_url( $order_id, $correction_id ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download PDF', 'weblo-fakturownia' ) . '</a> ';
			echo '<button type="button" class="button button-small weblo-fakturownia-send-correction-email" data-order-id="' . esc_attr( $order_id ) . '" data-invoice-id="' . esc_attr( $correction_id ) . '">' . esc_html__( 'Send email', 'weblo-fakturownia' ) . '</button>';
			echo '</p>';
		} else {
			echo '<p style="margin:0 0 8px;">' . esc_html__( 'No correction for this order.', 'weblo-fakturownia' ) . '</p>';
			echo '<p style="margin:0 0 8px;">';
			$can_issue_correction = ! empty( $invoice_id );
			$disabled_attr = $can_issue_correction ? '' : ' disabled="disabled"';
			$title_attr    = $can_issue_correction ? '' : ' title="' . esc_attr__( 'Issue the invoice first to be able to create a correction.', 'weblo-fakturownia' ) . '"';
			echo '<button type="button" class="button weblo-fakturownia-issue-correction-now" data-order-id="' . esc_attr( $order_id ) . '"' . $disabled_attr . $title_attr . '>' . esc_html__( 'Issue correction now', 'weblo-fakturownia' ) . '</button>';
			echo '</p>';
		}

		echo '<div class="weblo-fakturownia-order-result" style="margin-top:10px;"></div>';
		echo '</div>';
	}

	/**
	 * Build a user-friendly error message from API error payload.
	 *
	 * @param string $raw_error
	 * @return string
	 */
	protected function build_pretty_error_message( $raw_error ) {
		$raw_error = trim( (string) $raw_error );
		if ( '' === $raw_error ) {
			return '';
		}

		$decoded = json_decode( $raw_error, true );
		if ( ! is_array( $decoded ) ) {
			return '';
		}

		$first_message = '';
		if ( isset( $decoded['message'] ) ) {
			$first_message = (string) $this->extract_first_error_message( $decoded['message'] );
		} else {
			$first_message = (string) $this->extract_first_error_message( $decoded );
		}

		if ( '' === $first_message ) {
			return '';
		}

		$normalized = function_exists( 'mb_strtolower' ) ? mb_strtolower( $first_message, 'UTF-8' ) : strtolower( $first_message );

		// If API returned a clear human message, show it as-is (without escaped Unicode).
		if ( '' !== $first_message ) {
			return $first_message;
		}

		// Fallback only.
		return sprintf(
			/* translators: %s: first error message returned by API */
			__( 'Fakturownia response: %s', 'weblo-fakturownia' ),
			$normalized
		);
	}

	/**
	 * Recursively extracts first text message from nested error arrays.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function extract_first_error_message( $value ) {
		if ( is_string( $value ) ) {
			return trim( $value );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$found = $this->extract_first_error_message( $item );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}

		return '';
	}

	/**
	 * Formats raw error text for readable UI output.
	 * Converts JSON with escaped unicode into pretty-printed text.
	 *
	 * @param string $raw_error
	 * @return string
	 */
	protected function format_error_for_display( $raw_error ) {
		$raw_error = trim( (string) $raw_error );
		if ( '' === $raw_error ) {
			return '';
		}

		$decoded = json_decode( $raw_error, true );
		if ( ! is_array( $decoded ) ) {
			return $raw_error;
		}

		return wp_json_encode(
			$decoded,
			JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['weblo_fakturownia_order_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['weblo_fakturownia_order_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Na razie brak pól do zapisu w metaboxie (przyciski są AJAX).
	}

	public function enqueue_assets( $hook_suffix ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = $screen ? $screen->id : '';

		$allowed_screen_ids = array( 'shop_order', 'woocommerce_page_wc-orders' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$allowed_screen_ids[] = wc_get_page_screen_id( 'shop-order' );
		}

		if ( ! $screen || ! in_array( $screen_id, $allowed_screen_ids, true ) ) {
			return;
		}

		$plugin_main_file = trailingslashit( dirname( __FILE__, 2 ) ) . 'fakturownia.php';
		$settings         = Weblo_Fakturownia_Plugin::instance()->get_settings();
		$ver              = isset( $settings['weblo_fakturownia_dev_mode'] ) && 'yes' === $settings['weblo_fakturownia_dev_mode']
			? (string) wp_rand( 100000, 999999 )
			: (string) ( $settings['weblo_fakturownia_assets_version'] ?? '1.0.0' );

		wp_enqueue_script(
			'weblo-fakturownia-order-metabox',
			plugins_url( 'assets/js/weblo-fakturownia-order-metabox.js', $plugin_main_file ),
			array( 'jquery' ),
			$ver,
			true
		);

		wp_localize_script(
			'weblo-fakturownia-order-metabox',
			'WebloFakturowniaOrder',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce_issue' => wp_create_nonce( 'weblo_issue_invoice_now' ),
				'nonce_send'  => wp_create_nonce( 'weblo_send_invoice_email' ),
				'nonce_pdf'   => wp_create_nonce( 'weblo_download_invoice_pdf' ),
				'nonce_issue_correction' => wp_create_nonce( 'weblo_issue_correction_now' ),
				'nonce_send_correction'  => wp_create_nonce( 'weblo_send_correction_email' ),
				'i18n' => array(
					'missing_order_id' => __( 'Missing order ID.', 'weblo-fakturownia' ),
					'issuing' => __( 'Issuing...', 'weblo-fakturownia' ),
					'ok' => __( 'OK', 'weblo-fakturownia' ),
					'error' => __( 'Error.', 'weblo-fakturownia' ),
					'ajax_error' => __( 'AJAX error.', 'weblo-fakturownia' ),
					'missing_sending_data' => __( 'Missing data for sending.', 'weblo-fakturownia' ),
					'sending' => __( 'Sending...', 'weblo-fakturownia' ),
					'sent' => __( 'Sent.', 'weblo-fakturownia' ),
					'send_email_again' => __( 'Send email again', 'weblo-fakturownia' ),
					'issuing_correction' => __( 'Issuing correction...', 'weblo-fakturownia' ),
				),
			)
		);
	}

	public function ajax_issue_correction_now() {
		check_ajax_referer( 'weblo_issue_correction_now', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
				)
			);
		}

		$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
		if ( $order_id <= 0 ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Missing order_id.', 'weblo-fakturownia' ),
				)
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Order not found.', 'weblo-fakturownia' ),
				)
			);
		}

		$plugin   = Weblo_Fakturownia_Plugin::instance();
		$settings = $plugin->get_settings();

		$options = array(
			'shipping_mode'   => (string) ( $settings['weblo_fakturownia_correction_shipping_mode'] ?? 'as_order' ),
			'shipping_amount' => (float) ( $settings['weblo_fakturownia_correction_shipping_amount'] ?? 0 ),
			'correction_mode' => (string) ( $settings['weblo_fakturownia_correction_mode'] ?? 'full' ),
			'reason'          => __( 'Order refund', 'weblo-fakturownia' ),
		);

		// Ręczna korekta: wybieramy ostatni refund z zamówienia (korekta ma sens tylko dla refundu).
		$refunds   = $order->get_refunds();
		$refund_id = 0;
		if ( ! empty( $refunds ) && is_array( $refunds ) ) {
			$first = $refunds[0];
			if ( $first && method_exists( $first, 'get_id' ) ) {
				$refund_id = (int) $first->get_id();
			}
		}

		if ( $refund_id <= 0 ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'No refunds found for this order.', 'weblo-fakturownia' ),
				)
			);
		}

		$client = $plugin->get_api_client();
		$res    = $client->create_correction( $order_id, $refund_id, current_time( 'Y-m-d' ), $options );

		if ( empty( $res['success'] ) ) {
			$error_value = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );
			$order->update_meta_data( '_weblo_fakturownia_correction_last_error', $error_value );
			$order->save();
			Weblo_Fakturownia_Plugin::instance()->report_error( $order_id, 'metabox_issue_correction', $error_value );
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Failed to create correction.', 'weblo-fakturownia' ),
					'error'   => $res['error'] ?? null,
				)
			);
		}

		if ( ! empty( $res['invoice_id'] ) ) {
			$order->update_meta_data( '_weblo_fakturownia_correction_id', $res['invoice_id'] );
		}
		if ( ! empty( $res['invoice_number'] ) ) {
			$order->update_meta_data( '_weblo_fakturownia_correction_number', $res['invoice_number'] );
		}
		$order->delete_meta_data( '_weblo_fakturownia_correction_last_error' );
		$order->save();

		$post = get_post( $order_id );
		ob_start();
		$this->render( $post );
		$html = ob_get_clean();

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'Correction has been created.', 'weblo-fakturownia' ),
				'html'    => $html,
			)
		);
	}

	public function ajax_send_correction_email() {
		// Wysyłka korekty jest analogiczna do faktury, ale używa osobnych ustawień WooCommerce.
		// Przekazujemy flagę, żeby ajax_send_invoice_email wiedziało, że to korekta.
		$_POST['invoice_id'] = isset( $_POST['invoice_id'] ) ? $_POST['invoice_id'] : '';
		$_POST['is_correction'] = '1';
		return $this->ajax_send_invoice_email();
	}

	/**
	 * @param int    $order_id
	 * @param string $invoice_id
	 * @return string
	 */
	protected function build_pdf_download_url( $order_id, $invoice_id ) {
		return add_query_arg(
			array(
				'action'     => 'weblo_download_invoice_pdf',
				'order_id'   => (int) $order_id,
				'invoice_id' => (string) $invoice_id,
				'nonce'      => wp_create_nonce( 'weblo_download_invoice_pdf' ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	public function ajax_issue_invoice_now() {
		check_ajax_referer( 'weblo_issue_invoice_now', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
				)
			);
		}

		$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
		if ( $order_id <= 0 ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Missing order_id.', 'weblo-fakturownia' ),
				)
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Order not found.', 'weblo-fakturownia' ),
				)
			);
		}

		$client = Weblo_Fakturownia_Plugin::instance()->get_api_client();
		$res    = $client->create_invoice( $order_id );

		if ( ! $res['success'] ) {
			$error_value = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );

			// Zapis błędu w meta (HPOS).
			$order->update_meta_data( '_weblo_fakturownia_last_error', $error_value );
			$order->save();

			Weblo_Fakturownia_Plugin::instance()->report_error( $order_id, 'metabox_issue_invoice', $error_value );

			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'    => 'create_invoice failed',
						'order_id' => $order_id,
						'error'    => $res['error'] ?? null,
						'http_code' => $res['http_code'] ?? null,
					),
					'metabox'
				);
			}
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Failed to create invoice.', 'weblo-fakturownia' ),
					'error'   => $res['error'] ?? null,
				)
			);
		}

		$invoice_id     = $res['invoice_id'] ?? null;
		$invoice_number = $res['invoice_number'] ?? null;

		if ( $invoice_id ) {
			$order->update_meta_data( '_weblo_fakturownia_invoice_id', $invoice_id );
		}
		if ( $invoice_number ) {
			$order->update_meta_data( '_weblo_fakturownia_invoice_number', $invoice_number );
		}
		$order->delete_meta_data( '_weblo_fakturownia_last_error' );
		$order->save();

		// Zwróć świeży HTML metaboxa do podmiany.
		$post = get_post( $order_id );
		ob_start();
		$this->render( $post );
		$html = ob_get_clean();

		wp_send_json(
			array(
				'success'        => true,
				'message'        => __( 'Invoice has been created.', 'weblo-fakturownia' ),
				'invoice_id'     => $invoice_id,
				'invoice_number' => $invoice_number,
				'html'           => $html,
			)
		);
	}

	public function ajax_send_invoice_email() {
		try {
			$is_correction = isset( $_POST['is_correction'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['is_correction'] ) );

			// Nonce: nie używamy check_ajax_referer, bo to kończy request wp_die() i JS widzi "AJAX error".
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			$nonce_action = $is_correction ? 'weblo_send_correction_email' : 'weblo_send_invoice_email';
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Invalid security token.', 'weblo-fakturownia' ),
					)
				);
			}

			if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'You do not have permission.', 'weblo-fakturownia' ),
					)
				);
			}

			$order_id   = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
			$invoice_id = isset( $_POST['invoice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_id'] ) ) : '';

			if ( $order_id <= 0 || '' === $invoice_id ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Missing data (order_id / invoice_id).', 'weblo-fakturownia' ),
					)
				);
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'Order not found.', 'weblo-fakturownia' ),
					)
				);
			}

			$to = (string) $order->get_billing_email();
			if ( '' === $to || ! is_email( $to ) ) {
				wp_send_json(
					array(
						'success' => false,
						'message' => __( 'No valid customer email address found on this order.', 'weblo-fakturownia' ),
					)
				);
			}

			$plugin   = Weblo_Fakturownia_Plugin::instance();
			$settings = $plugin->get_settings();
			$client   = $plugin->get_api_client();

			// 1) Wyślij mail z Fakturowni (jeśli konto to wspiera).
			$res = $client->send_invoice_by_email( $invoice_id );
		// Jeśli Fakturownia zwróci błąd, zapisz go i kontynuuj (i tak spróbujemy wysłać mail z WP z PDF).
		if ( ! ( $res['success'] ?? false ) ) {
			$error_value = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );
			$order->update_meta_data( '_weblo_fakturownia_last_error', $error_value );
			$order->save();
		}

			// Czy mamy dodatkowo wysyłać mail z WooCommerce?
			$send_from_wc = $is_correction
				? ( isset( $settings['weblo_fakturownia_send_corrections_from_woocommerce'] ) && 'yes' === $settings['weblo_fakturownia_send_corrections_from_woocommerce'] )
				: ( isset( $settings['weblo_fakturownia_send_from_woocommerce'] ) && 'yes' === $settings['weblo_fakturownia_send_from_woocommerce'] );

		if ( ! $send_from_wc ) {
			// Tylko Fakturownia – wyślij prostą odpowiedź na podstawie wyniku API.
			if ( $res['success'] ?? false ) {
				$order->delete_meta_data( '_weblo_fakturownia_last_error' );
				$order->save();
				wp_send_json(
					array(
						'success' => true,
						'message' => __( 'Invoice has been sent by email from Fakturownia.', 'weblo-fakturownia' ),
					)
				);
			}

			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Failed to send invoice by email from Fakturownia.', 'weblo-fakturownia' ),
				)
			);
		}

		// 2) Wysyłamy również mail z WordPressa z PDF w załączniku (żądanie użytkownika).
			$invoice_number = $is_correction
				? (string) $order->get_meta( '_weblo_fakturownia_correction_number', true )
				: (string) $order->get_meta( '_weblo_fakturownia_invoice_number', true );
		$pdf_url        = $client->download_invoice_pdf( $invoice_id )['url'] ?? '';
		$pdf_tmp_file   = '';

		if ( $pdf_url ) {
			$pdf_response = wp_remote_get(
				$pdf_url,
				array(
					'timeout' => 25,
				)
			);

			if ( is_wp_error( $pdf_response ) ) {
				if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
					weblo_fakturownia_debug_log(
						array(
							'event' => 'pdf_download_error',
							'error' => $pdf_response->get_error_message(),
						),
						'mail'
					);
				}
			} else {
				$pdf_code = (int) wp_remote_retrieve_response_code( $pdf_response );
				$pdf_body = (string) wp_remote_retrieve_body( $pdf_response );
				if ( 200 === $pdf_code && '' !== $pdf_body ) {
					$pdf_tmp_file = wp_tempnam( 'weblo-fakturownia-invoice-' . $invoice_id . '.pdf' );
					if ( $pdf_tmp_file ) {
						@file_put_contents( $pdf_tmp_file, $pdf_body ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
					}
				} else {
					if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
						weblo_fakturownia_debug_log(
							array(
								'event'    => 'pdf_download_http_error',
								'http_code'=> $pdf_code,
								'body_len' => strlen( $pdf_body ),
							),
							'mail'
						);
					}
				}
			}
		}

		$subject = sprintf(
			/* translators: 1: document number, 2: order number */
			__( 'Your document %1$s for order %2$s', 'weblo-fakturownia' ),
			$invoice_number ? $invoice_number : ( '#' . $invoice_id ),
			$order->get_order_number()
		);

		// Link w treści e-maila ma prowadzić do podpisanego pobrania PDF z naszego sklepu (bez logowania do Fakturowni).
		$invoice_url = $plugin->build_public_invoice_pdf_url( $order, (string) $invoice_id, (string) $order->get_meta( '_weblo_fakturownia_invoice_number', true ) );

		$order_url = method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '';
		if ( '' === $order_url ) {
			$order_url = (string) get_permalink( $order->get_id() );
		}

			$template_key = $is_correction ? 'weblo_fakturownia_wc_correction_email_template' : 'weblo_fakturownia_wc_email_template';
			$template = isset( $settings[ $template_key ] ) ? (string) $settings[ $template_key ] : '';

		if ( '' === trim( $template ) ) {
			$template = "Hello,\n\nWe are sending you the document [invoice_number] for order [order_number] in the attachment.\n\nOrder: [order_link]\nDocument: [invoice_link]\n\nBest regards,\nStore support";
		}

		$message = $this->render_email_template(
			$template,
			array(
				'order_id'        => (string) $order->get_id(),
				'order_number'    => (string) $order->get_order_number(),
				'order_url'       => $order_url,
				'order_link'      => $order_url ? ( '<a href="' . esc_url( $order_url ) . '">' . esc_html( $order->get_order_number() ) . '</a>' ) : esc_html( $order->get_order_number() ),
				'invoice_id'      => (string) $invoice_id,
				'invoice_number'  => $invoice_number ? $invoice_number : ( '#' . (string) $invoice_id ),
				'invoice_url'     => $invoice_url,
				'invoice_link'    => $invoice_url ? ( '<a href="' . esc_url( $invoice_url ) . '">' . esc_html( $invoice_number ? $invoice_number : ( '#' . (string) $invoice_id ) ) . '</a>' ) : esc_html( $invoice_number ? $invoice_number : ( '#' . (string) $invoice_id ) ),
			)
		);

		$attachments = array();
		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			$attachments[] = $pdf_tmp_file;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to, $subject, wpautop( wp_kses_post( $message ) ), $headers, $attachments );

		if ( ! $sent && function_exists( 'weblo_fakturownia_debug_log' ) ) {
			weblo_fakturownia_debug_log(
				array(
					'event'       => 'wp_mail_result',
					'to'          => $to,
					'sent'        => (bool) $sent,
					'has_pdf'     => ! empty( $attachments ),
					'pdf_tmp_file'=> $pdf_tmp_file ? basename( $pdf_tmp_file ) : null,
				),
				'mail'
			);
		}

		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			@unlink( $pdf_tmp_file ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
		}

		if ( $sent ) {
			$order->delete_meta_data( '_weblo_fakturownia_last_error' );
			$order->save();
			wp_send_json(
				array(
					'success' => true,
					'message' => __( 'Invoice has been sent by email (with PDF attachment).', 'weblo-fakturownia' ),
				)
			);
		}

			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Failed to send email from WordPress. Please check mail (SMTP) configuration and debug logs.', 'weblo-fakturownia' ),
				)
			);
		} catch ( Throwable $e ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'   => 'ajax_send_invoice_email_exception',
						'message' => $e->getMessage(),
						'file'    => $e->getFile(),
						'line'    => $e->getLine(),
					),
					'mail'
				);
			}

			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Unexpected server error while sending email.', 'weblo-fakturownia' ),
				)
			);
		}
	}

	/**
	 * Podmienia shortcody w szablonie e-mail.
	 *
	 * @param string $template
	 * @param array  $vars
	 * @return string
	 */
	protected function render_email_template( $template, $vars ) {
		$map = array();
		foreach ( (array) $vars as $k => $v ) {
			$map[ '[' . $k . ']' ] = (string) $v;
		}
		return strtr( (string) $template, $map );
	}

	public function ajax_download_invoice_pdf() {
		check_ajax_referer( 'weblo_download_invoice_pdf', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'weblo-fakturownia' ) );
		}

		$order_id   = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice_id = isset( $_GET['invoice_id'] ) ? sanitize_text_field( wp_unslash( $_GET['invoice_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $order_id <= 0 || '' === $invoice_id ) {
			wp_die( esc_html__( 'Missing data (order_id / invoice_id).', 'weblo-fakturownia' ) );
		}

		$client = Weblo_Fakturownia_Plugin::instance()->get_api_client();
		$pdf    = $client->download_invoice_pdf( $invoice_id );

		if ( ! $pdf['success'] || empty( $pdf['url'] ) ) {
			wp_die( esc_html__( 'Failed to download PDF.', 'weblo-fakturownia' ) );
		}

		// Proxy: pobierz PDF z Fakturowni po stronie serwera i zwróć jako plik.
		$response = wp_remote_get(
			$pdf['url'],
			array(
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html( $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			wp_die( esc_html__( 'Error downloading PDF.', 'weblo-fakturownia' ) );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="faktura-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $invoice_id ) . '.pdf"' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}

