<?php
/**
 * Plugin Name: Fakturownia by Weblo – WooCommerce integration
 * Plugin URI: https://weblo.pl/
 * Description: WordPress plugin for integrating WooCommerce with Fakturownia.
 * Author: Weblo
 * Author URI: https://weblo.pl/
 * Version: 1.0.3
 * Text Domain: weblo-fakturownia
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WEBLO_FAKTUROWNIA_VERSION' ) ) {
	define( 'WEBLO_FAKTUROWNIA_VERSION', '1.0.3' );
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

if ( ! function_exists( 'weblo_fakturownia_debug_log' ) ) {
	/**
	 * Prosty debug log tylko dla tej wtyczki.
	 *
	 * @param mixed  $message
	 * @param string $context
	 */
	function weblo_fakturownia_debug_log( $message, $context = '' ) {
		// Debug można włączyć na 2 sposoby:
		// 1) (opcjonalnie) stała WEBLO_FAKTUROWNIA_DEBUG = true
		// 2) Ustawienie w WooCommerce → Settings → Integration → Logs: "Enable debug.log logging"
		$enabled = false;

		if ( defined( 'WEBLO_FAKTUROWNIA_DEBUG' ) ) {
			$enabled = ( true === WEBLO_FAKTUROWNIA_DEBUG );
		} else {
			$settings = get_option( 'woocommerce_weblo_fakturownia_settings', array() );
			if ( is_array( $settings ) && ! empty( $settings['weblo_fakturownia_debug_logging_enabled'] ) && 'yes' === $settings['weblo_fakturownia_debug_logging_enabled'] ) {
				$enabled = true;
			}
		}

		if ( ! $enabled ) {
			return;
		}

		$dir = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'logs';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file = trailingslashit( $dir ) . 'debug.log';

		// Limit rozmiaru pliku debug.log do ~2 MB.
		if ( file_exists( $file ) ) {
			$max_size = 2 * 1024 * 1024; // 2 MB.
			$size     = @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $size && $size >= $max_size ) {
				// Najprostsza rotacja – usuń stary plik, zacznij od nowa.
				@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		$line = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ';
		if ( $context ) {
			$line .= '[' . $context . '] ';
		}
		if ( is_array( $message ) || is_object( $message ) ) {
			$line .= wp_json_encode( $message );
		} else {
			$line .= (string) $message;
		}
		$line .= "\n";

		$written = @file_put_contents( $file, $line, FILE_APPEND ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		if ( false === $written ) {
			// Fallback: at least expose the problem in PHP error log (without leaking token).
			// This helps diagnose permissions / filesystem issues when debug.log cannot be written.
			error_log( '[weblo-fakturownia] Failed to write debug.log at: ' . $file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

/**
 * Główna klasa ładowania integracji.
 */
class Weblo_Fakturownia_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Weblo_Fakturownia_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Slug wtyczki.
	 */
	const PLUGIN_SLUG = 'weblo-fakturownia';

	/**
	 * @var Weblo_Fakturownia_API_Client|null
	 */
	protected $api_client = null;

	/**
	 * @var array|null
	 */
	protected $api_client_config = null;

	/**
	 * Zwraca instancję singletonu.
	 *
	 * @return Weblo_Fakturownia_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Weblo_Fakturownia_Plugin constructor.
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Inicjalizacja wtyczki.
	 */
	public function init() {
		// Load plugin translations from /languages directory.
		load_plugin_textdomain( 'weblo-fakturownia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Sprawdź, czy WooCommerce jest aktywny i klasa WC_Integration dostępna.
		if ( ! class_exists( 'WC_Integration' ) ) {
			return;
		}

		// Załaduj plik z klasą integracji.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-integration-weblo-fakturownia.php';

		// API client + metabox.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-weblo-fakturownia-api-client.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-weblo-fakturownia-order-metabox.php';

		// Metabox tylko jeśli połączenie OK.
		$settings = $this->get_settings();
		$ok       = isset( $settings['weblo_fakturownia_connection_ok'] ) && 'yes' === $settings['weblo_fakturownia_connection_ok'];
		if ( $ok ) {
			new Weblo_Fakturownia_Order_Metabox();
		}

		// Hooki administracyjne (kolumna, filtry, wyszukiwanie).
		// Klasyczny ekran zamówień (post type shop_order).
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_orders_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_orders_column' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'make_orders_column_sortable' ) );

		// Nowy ekran zamówień WooCommerce (HPOS / wc-orders).
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_orders_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_orders_column' ), 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( $this, 'make_orders_column_sortable' ) );

		// HPOS: filtry/wyszukiwanie.
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'hpos_add_orders_filters' ), 10, 2 );
		add_filter( 'woocommerce_order_query_args', array( $this, 'hpos_apply_orders_filters' ), 10, 1 );

		add_action( 'restrict_manage_posts', array( $this, 'add_orders_filter_dropdown' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_orders_search_and_filter' ) );

		// Automatyczne wystawianie faktur.
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_auto_issue_invoice' ), 10, 4 );

		// Automatyczne korekty po zwrocie.
		add_action( 'woocommerce_order_refunded', array( $this, 'maybe_auto_issue_correction' ), 10, 2 );

		// Utworzenie tabeli logów przy potrzebie (np. po aktualizacji).
		add_action( 'admin_init', array( $this, 'maybe_create_logs_table' ) );

		// Publiczny (bez logowania) download PDF przez podpisany link w e-mailu.
		add_action( 'template_redirect', array( $this, 'maybe_handle_public_pdf_download' ) );

		// Shortcode do wyświetlania linku do pobrania PDF faktury w podglądzie zamówienia.
		add_shortcode( 'weblo_fakturownia_invoice_pdf', array( $this, 'shortcode_invoice_pdf' ) );
	}

	/**
	 * Shortcode: [weblo_fakturownia_invoice_pdf text="Download invoice" order_id="123"]
	 *
	 * - text: tekst linku (domyślnie: "Download invoice PDF")
	 * - order_id: opcjonalnie ID zamówienia; jeśli brak, spróbujemy wykryć zamówienie na stronie podglądu zamówienia (My Account → View order).
	 *
	 * @param array       $atts
	 * @param string|null $content
	 * @return string
	 */
	public function shortcode_invoice_pdf( $atts, $content = null ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'text'     => __( 'Download invoice PDF', 'weblo-fakturownia' ),
				'order_id' => '',
			),
			(array) $atts,
			'weblo_fakturownia_invoice_pdf'
		);

		$link_text = '';
		if ( is_string( $content ) && '' !== trim( $content ) ) {
			$link_text = trim( $content );
		} else {
			$link_text = (string) $atts['text'];
		}

		$order_id = (int) $atts['order_id'];

		// Jeśli nie podano order_id, spróbuj pobrać z endpointu WooCommerce "view-order".
		if ( $order_id <= 0 && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'view-order' ) ) {
			$qv = function_exists( 'get_query_var' ) ? get_query_var( 'view-order' ) : '';
			$order_id = (int) $qv;
		}

		if ( $order_id <= 0 ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}

		// Upewnij się, że użytkownik ma dostęp do zamówienia (frontend).
		// - Klient: tylko właściciel zamówienia.
		// - Admin/manager: może widzieć shortcode także na zwykłych stronach (np. do testów).
		if ( ! is_admin() ) {
			$is_privileged = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
			if ( ! $is_privileged && function_exists( 'get_current_user_id' ) ) {
				$uid = (int) get_current_user_id();
				if ( $uid > 0 && (int) $order->get_user_id() !== $uid ) {
					return '';
				}
			}
		}

		$invoice_id     = (string) $order->get_meta( '_weblo_fakturownia_invoice_id', true );
		$invoice_number = (string) $order->get_meta( '_weblo_fakturownia_invoice_number', true );

		if ( '' === $invoice_id ) {
			return '';
		}

		$url = $this->build_public_invoice_pdf_url( $order, $invoice_id, $invoice_number );
		if ( '' === $url ) {
			return '';
		}

		return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $link_text ) . '</a>';
	}

	/**
	 * Buduje podpisany link do pobrania PDF (dla klienta, bez dostępu do panelu Fakturowni).
	 *
	 * Link jest czasowy i nie ujawnia api_token.
	 *
	 * @param WC_Order $order
	 * @param string   $invoice_id
	 * @param string   $invoice_number
	 * @param int      $ttl_seconds
	 * @return string
	 */
	public function build_public_invoice_pdf_url( $order, $invoice_id, $invoice_number = '', $ttl_seconds = 7 * DAY_IN_SECONDS ) {
		if ( ! $order || ! $invoice_id ) {
			return '';
		}

		$expires = time() + max( 300, (int) $ttl_seconds ); // min 5 min.
		$order_id = (int) $order->get_id();
		$order_key = method_exists( $order, 'get_order_key' ) ? (string) $order->get_order_key() : '';

		$payload = implode( '|', array( (string) $order_id, (string) $invoice_id, (string) $expires, (string) $order_key ) );
		$token   = hash_hmac( 'sha256', $payload, wp_salt( 'weblo_fakturownia_pdf' ) );

		$args = array(
			'weblo_fakturownia_pdf' => '1',
			'order_id'              => $order_id,
			'invoice_id'            => (string) $invoice_id,
			'expires'               => (string) $expires,
			'token'                 => $token,
		);

		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Obsługuje publiczny download PDF przez podpisany link.
	 */
	public function maybe_handle_public_pdf_download() {
		$flag = isset( $_GET['weblo_fakturownia_pdf'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_pdf'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '1' !== $flag ) {
			return;
		}

		$order_id   = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice_id = isset( $_GET['invoice_id'] ) ? sanitize_text_field( wp_unslash( $_GET['invoice_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$expires    = isset( $_GET['expires'] ) ? (int) $_GET['expires'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token      = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $order_id <= 0 || '' === $invoice_id || $expires <= 0 || '' === $token ) {
			wp_die( esc_html__( 'Invalid download link.', 'weblo-fakturownia' ), 403 );
		}

		if ( time() > $expires ) {
			wp_die( esc_html__( 'This download link has expired.', 'weblo-fakturownia' ), 403 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'weblo-fakturownia' ), 404 );
		}

		$order_key = method_exists( $order, 'get_order_key' ) ? (string) $order->get_order_key() : '';
		$payload   = implode( '|', array( (string) $order_id, (string) $invoice_id, (string) $expires, (string) $order_key ) );
		$expected  = hash_hmac( 'sha256', $payload, wp_salt( 'weblo_fakturownia_pdf' ) );

		if ( ! hash_equals( $expected, $token ) ) {
			wp_die( esc_html__( 'Invalid download link.', 'weblo-fakturownia' ), 403 );
		}

		// Dodatkowa walidacja: invoice_id musi należeć do zamówienia (faktura lub korekta).
		$allowed_invoice_ids = array_filter(
			array(
				(string) $order->get_meta( '_weblo_fakturownia_invoice_id', true ),
				(string) $order->get_meta( '_weblo_fakturownia_correction_id', true ),
			),
			function ( $v ) {
				return '' !== (string) $v;
			}
		);
		if ( ! in_array( (string) $invoice_id, $allowed_invoice_ids, true ) ) {
			wp_die( esc_html__( 'Invalid download link.', 'weblo-fakturownia' ), 403 );
		}

		$client = $this->get_api_client();
		$pdf    = $client->download_invoice_pdf( $invoice_id );
		if ( empty( $pdf['success'] ) || empty( $pdf['url'] ) ) {
			wp_die( esc_html__( 'Failed to download PDF.', 'weblo-fakturownia' ), 500 );
		}

		$response = wp_remote_get(
			(string) $pdf['url'],
			array(
				'timeout' => 25,
			)
		);
		if ( is_wp_error( $response ) ) {
			wp_die( esc_html( $response->get_error_message() ), 500 );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== $code || '' === $body ) {
			wp_die( esc_html__( 'Failed to download PDF.', 'weblo-fakturownia' ), 500 );
		}

		$invoice_number = (string) $order->get_meta( '_weblo_fakturownia_invoice_number', true );
		$filename = $invoice_number ? $invoice_number : ( 'invoice-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $invoice_id ) );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '.pdf"' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Automatyczne wystawianie korekt po zwrocie.
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 */
	public function maybe_auto_issue_correction( $order_id, $refund_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['weblo_fakturownia_connection_ok'] ) || 'yes' !== $settings['weblo_fakturownia_connection_ok'] ) {
			return;
		}

		if ( empty( $settings['weblo_fakturownia_auto_issue_corrections'] ) || 'yes' !== $settings['weblo_fakturownia_auto_issue_corrections'] ) {
			return;
		}

		// Wymagamy faktury bazowej.
		$from_invoice_id = (string) $order->get_meta( '_weblo_fakturownia_invoice_id', true );
		if ( '' === $from_invoice_id ) {
			$order->update_meta_data( '_weblo_fakturownia_correction_last_error', __( 'Missing base invoice ID.', 'weblo-fakturownia' ) );
			$order->save();
			return;
		}

		// Unikaj duplikatów.
		if ( $order->get_meta( '_weblo_fakturownia_correction_id', true ) ) {
			return;
		}

		$options = array(
			'shipping_mode'   => (string) ( $settings['weblo_fakturownia_correction_shipping_mode'] ?? 'as_order' ),
			'shipping_amount' => (float) ( $settings['weblo_fakturownia_correction_shipping_amount'] ?? 0 ),
			'correction_mode' => (string) ( $settings['weblo_fakturownia_correction_mode'] ?? 'full' ),
			'reason'          => __( 'Order refund', 'weblo-fakturownia' ),
		);

		$refund = wc_get_order( $refund_id );
		$issue_date_override = current_time( 'Y-m-d' );
		if ( $refund && method_exists( $refund, 'get_date_created' ) ) {
			$rd = $refund->get_date_created();
			$issue_date_override = $rd ? $rd->date_i18n( 'Y-m-d' ) : current_time( 'Y-m-d' );
		}

		$client = $this->get_api_client();
		$res    = $client->create_correction( (int) $order_id, (int) $refund_id, $issue_date_override, $options );

		if ( empty( $res['success'] ) ) {
			$error = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );
			$order->update_meta_data( '_weblo_fakturownia_correction_last_error', $error );
			$order->save();
			$this->log_error( $order_id, 'auto_correction', $error );
			$this->maybe_send_error_notification( $order_id, $error );
			return;
		}

		if ( ! empty( $res['invoice_id'] ) ) {
			$order->update_meta_data( '_weblo_fakturownia_correction_id', $res['invoice_id'] );
		}
		if ( ! empty( $res['invoice_number'] ) ) {
			$order->update_meta_data( '_weblo_fakturownia_correction_number', $res['invoice_number'] );
		}
		$order->delete_meta_data( '_weblo_fakturownia_correction_last_error' );
		$order->save();

		// Automatyczna wysyłka korekty (Fakturownia).
		if ( ! empty( $settings['weblo_fakturownia_auto_send_correction_email'] ) && 'yes' === $settings['weblo_fakturownia_auto_send_correction_email'] && ! empty( $res['invoice_id'] ) ) {
			$client->send_invoice_by_email( (string) $res['invoice_id'] );

			// Dodatkowo: wyślij e‑mail z WooCommerce z PDF w załączniku (jeśli włączone).
			if ( ! empty( $settings['weblo_fakturownia_send_corrections_from_woocommerce'] ) && 'yes' === $settings['weblo_fakturownia_send_corrections_from_woocommerce'] ) {
				$this->send_document_email_from_woocommerce(
					$order,
					(string) $res['invoice_id'],
					(string) ( $res['invoice_number'] ?? '' ),
					'weblo_fakturownia_wc_correction_email_template'
				);
			}
		}
	}

	/**
	 * Tworzy tabelę logów błędów, jeśli nie istnieje.
	 */
	public function maybe_create_logs_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'weblo_fakturownia_logs';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			type VARCHAR(50) NOT NULL,
			error TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY type (type)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Dodaje kolumnę "Fakturownia" w tabeli zamówień.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_orders_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'order_date' === $key ) {
				$new['weblo_fakturownia'] = __( 'Fakturownia', 'weblo-fakturownia' );
			}
		}

		if ( ! isset( $new['weblo_fakturownia'] ) ) {
			$new['weblo_fakturownia'] = __( 'Fakturownia', 'weblo-fakturownia' );
		}

		return $new;
	}

	/**
	 * Renderuje zawartość kolumny "Fakturownia".
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_orders_column( $column, $post_id ) {
		if ( 'weblo_fakturownia' !== $column ) {
			return;
		}

		// Obsługa zarówno klasycznego modelu zamówień, jak i HPOS.
		// Na ekranie HPOS drugi parametr potrafi być obiektem zamówienia.
		$order = null;
		if ( is_object( $post_id ) && method_exists( $post_id, 'get_id' ) ) {
			$order = $post_id;
		} else {
			$order = wc_get_order( $post_id );
		}
		if ( ! $order ) {
			echo esc_html__( 'None', 'weblo-fakturownia' );
			return;
		}

		$order_id       = $order->get_id();
		$invoice_id     = $order->get_meta( '_weblo_fakturownia_invoice_id', true );
		$invoice_number = $order->get_meta( '_weblo_fakturownia_invoice_number', true );
		$last_error     = $order->get_meta( '_weblo_fakturownia_last_error', true );

		// Fallback (gdyby dane były tylko w postmeta).
		if ( '' === (string) $invoice_id ) {
			$invoice_id = get_post_meta( $order_id, '_weblo_fakturownia_invoice_id', true );
		}
		if ( '' === (string) $invoice_number ) {
			$invoice_number = get_post_meta( $order_id, '_weblo_fakturownia_invoice_number', true );
		}
		if ( '' === (string) $last_error ) {
			$last_error = get_post_meta( $order_id, '_weblo_fakturownia_last_error', true );
		}

		if ( ! empty( $last_error ) ) {
			// W tabeli pokazujemy tylko status "Błąd" (bez szczegółów).
			echo '<span style="color:#b32d2e;font-weight:700;">' . esc_html__( 'Error', 'weblo-fakturownia' ) . '</span>';
			return;
		}

		if ( $invoice_id ) {
			$settings = $this->get_settings();
			$domain   = isset( $settings['weblo_fakturownia_domain'] ) ? (string) $settings['weblo_fakturownia_domain'] : '';
			$domain   = preg_replace( '#^https?://#', '', trim( $domain ) );

			$url = ( '' !== $domain ) ? ( 'https://' . $domain . '/invoices/' . rawurlencode( (string) $invoice_id ) ) : '#';
			$num = $invoice_number ? (string) $invoice_number : ( '#' . (string) $invoice_id );

			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $num ) . '</a> ';
			echo '<span style="color:#46b450;font-weight:700;" aria-hidden="true">✓</span>';
		} else {
			echo esc_html__( 'None', 'weblo-fakturownia' );
		}
	}

	/**
	 * Ustawia kolumnę jako sortowalną.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function make_orders_column_sortable( $columns ) {
		$columns['weblo_fakturownia'] = 'weblo_fakturownia_invoice_number';
		return $columns;
	}

	/**
	 * Dodaje dropdown filtrów nad tabelą zamówień.
	 *
	 * @param string $post_type
	 */
	public function add_orders_filter_dropdown( $post_type ) {
		if ( 'shop_order' !== $post_type ) {
			return;
		}

		$current = isset( $_GET['weblo_fakturownia_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice_search = isset( $_GET['weblo_fakturownia_invoice_search'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_invoice_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<select name="weblo_fakturownia_filter" id="weblo_fakturownia_filter">';
		echo '<option value="">' . esc_html__( 'Fakturownia – all', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="has_invoice"' . selected( $current, 'has_invoice', false ) . '>' . esc_html__( 'Has invoice', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="no_invoice"' . selected( $current, 'no_invoice', false ) . '>' . esc_html__( 'No invoice', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="has_error"' . selected( $current, 'has_error', false ) . '>' . esc_html__( 'Invoice error', 'weblo-fakturownia' ) . '</option>';
		echo '</select>';

		echo '&nbsp;';
		echo '<input type="text" name="weblo_fakturownia_invoice_search" value="' . esc_attr( $invoice_search ) . '" placeholder="' . esc_attr__( 'Search by invoice number…', 'weblo-fakturownia' ) . '" style="max-width:180px;">';
	}

	/**
	 * Rozszerza wyszukiwanie i filtrację zamówień o dane Fakturowni.
	 *
	 * @param WP_Query $query
	 */
	public function handle_orders_search_and_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'edit-shop_order' !== $screen->id ) {
			return;
		}

		if ( 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query', array() );

		// Dedykowane pole wyszukiwania po numerze faktury.
		$invoice_search = isset( $_GET['weblo_fakturownia_invoice_search'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_invoice_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $invoice_search ) {
			$meta_query[] = array(
				'key'     => '_weblo_fakturownia_invoice_number',
				'value'   => $invoice_search,
				'compare' => 'LIKE',
			);
			$query->set( 'meta_query', $meta_query );
		}

		// Filtr dropdown.
		$filter = isset( $_GET['weblo_fakturownia_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $filter ) {
			if ( 'has_invoice' === $filter ) {
				$meta_query[] = array(
					'key'     => '_weblo_fakturownia_invoice_id',
					'value'   => '',
					'compare' => '!=',
				);
			} elseif ( 'no_invoice' === $filter ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'value'   => '',
						'compare' => '=',
					),
				);
			} elseif ( 'has_error' === $filter ) {
				$meta_query[] = array(
					'key'     => '_weblo_fakturownia_last_error',
					'value'   => '',
					'compare' => '!=',
				);
			}
			$query->set( 'meta_query', $meta_query );
		}

		// Sortowanie po numerze faktury.
		if ( $query->get( 'orderby' ) === 'weblo_fakturownia_invoice_number' ) {
			$query->set( 'meta_key', '_weblo_fakturownia_invoice_number' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * HPOS: dodaje filtry nad tabelą zamówień (wc-orders).
	 *
	 * @param string $order_type
	 * @param string $which
	 */
	public function hpos_add_orders_filters( $order_type, $which ) {
		if ( 'shop_order' !== $order_type || 'top' !== $which ) {
			return;
		}

		$current        = isset( $_GET['weblo_fakturownia_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice_search = isset( $_GET['weblo_fakturownia_invoice_search'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_invoice_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<select name="weblo_fakturownia_filter" id="weblo_fakturownia_filter">';
		echo '<option value="">' . esc_html__( 'Fakturownia – all', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="has_invoice"' . selected( $current, 'has_invoice', false ) . '>' . esc_html__( 'Has invoice', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="no_invoice"' . selected( $current, 'no_invoice', false ) . '>' . esc_html__( 'No invoice', 'weblo-fakturownia' ) . '</option>';
		echo '<option value="has_error"' . selected( $current, 'has_error', false ) . '>' . esc_html__( 'Invoice error', 'weblo-fakturownia' ) . '</option>';
		echo '</select>';

		echo '&nbsp;';
		echo '<input type="text" name="weblo_fakturownia_invoice_search" value="' . esc_attr( $invoice_search ) . '" placeholder="' . esc_attr__( 'Search by invoice number…', 'weblo-fakturownia' ) . '" style="max-width:180px;">';
	}

	/**
	 * HPOS: aplikuje meta_query dla filtrów i wyszukiwania po numerze faktury.
	 *
	 * @param array $args
	 * @return array
	 */
	public function hpos_apply_orders_filters( $args ) {
		if ( ! is_admin() ) {
			return $args;
		}

		// W WooCommerce HPOS filtr woocommerce_order_query_args może wykonać się zanim screen będzie dostępny,
		// więc opieramy się na parametrach requestu.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'wc-orders' !== $page ) {
			return $args;
		}

		$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		if ( empty( $meta_query ) ) {
			$meta_query = array( 'relation' => 'AND' );
		} elseif ( ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		$filter = isset( $_GET['weblo_fakturownia_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $filter ) {
			if ( 'has_invoice' === $filter ) {
				// HPOS potrafi przechowywać meta jako istniejące, ale puste — dlatego sprawdzamy != ''.
				$meta_query[] = array(
					'key'     => '_weblo_fakturownia_invoice_id',
					'value'   => '',
					'compare' => '!=',
				);
			} elseif ( 'no_invoice' === $filter ) {
				// Brak faktury = meta nie istnieje LUB jest pusta.
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_weblo_fakturownia_invoice_id',
						'value'   => '',
						'compare' => '=',
					),
				);
			} elseif ( 'has_error' === $filter ) {
				$meta_query[] = array(
					'key'     => '_weblo_fakturownia_last_error',
					'value'   => '',
					'compare' => '!=',
				);
			}
		}

		$invoice_search = isset( $_GET['weblo_fakturownia_invoice_search'] ) ? sanitize_text_field( wp_unslash( $_GET['weblo_fakturownia_invoice_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $invoice_search ) {
			$meta_query[] = array(
				'key'     => '_weblo_fakturownia_invoice_number',
				'value'   => $invoice_search,
				'compare' => 'LIKE',
			);
		}

		$args['meta_query'] = $meta_query;

		return $args;
	}

	/**
	 * Automatyczne wystawianie faktur po zmianie statusu zamówienia.
	 *
	 * @param int      $order_id
	 * @param string   $old_status
	 * @param string   $new_status
	 * @param WP_Post|WC_Order $order_object
	 */
	public function maybe_auto_issue_invoice( $order_id, $old_status, $new_status, $order_object ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Nigdy nie dubluj faktur.
		if ( $order->get_meta( '_weblo_fakturownia_invoice_id', true ) ) {
			return;
		}

		$settings = $this->get_settings();

		if ( empty( $settings['weblo_fakturownia_connection_ok'] ) || 'yes' !== $settings['weblo_fakturownia_connection_ok'] ) {
			return;
		}

		// Wymagamy włączenia automatycznego wystawiania.
		if ( empty( $settings['weblo_fakturownia_auto_issue_invoices'] ) || 'yes' !== $settings['weblo_fakturownia_auto_issue_invoices'] ) {
			return;
		}

		$payment_method = (string) $order->get_payment_method();

		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;

		$statuses       = array();
		$cod_statuses   = array();

		if ( ! empty( $settings['weblo_fakturownia_invoice_statuses'] ) ) {
			$statuses = is_array( $settings['weblo_fakturownia_invoice_statuses'] ) ? $settings['weblo_fakturownia_invoice_statuses'] : (array) $settings['weblo_fakturownia_invoice_statuses'];
		}
		if ( ! empty( $settings['weblo_fakturownia_cod_statuses'] ) ) {
			$cod_statuses = is_array( $settings['weblo_fakturownia_cod_statuses'] ) ? $settings['weblo_fakturownia_cod_statuses'] : (array) $settings['weblo_fakturownia_cod_statuses'];
		}

		// COD = tylko metoda "cod". "bacs" (przelew) nie jest pobraniem.
		$is_cod = ( 'cod' === $payment_method );

		$use_cod_override = $is_cod && ! empty( $settings['weblo_fakturownia_cod_invoices'] ) && 'yes' === $settings['weblo_fakturownia_cod_invoices'];

		$target_statuses = $use_cod_override ? $cod_statuses : $statuses;
		$target_statuses = array_map(
			function ( $s ) {
				return 'wc-' === substr( $s, 0, 3 ) ? substr( $s, 3 ) : $s;
			},
			$target_statuses
		);

		if ( empty( $target_statuses ) || ! in_array( $new_status, $target_statuses, true ) ) {
			return;
		}

		// Data wystawienia: domyślnie data zmiany statusu, opcjonalnie data zamówienia.
		$invoice_date_source = isset( $settings['weblo_fakturownia_invoice_date_source'] ) ? (string) $settings['weblo_fakturownia_invoice_date_source'] : 'status_date';
		$issue_date_override = null;
		if ( 'order_date' === $invoice_date_source ) {
			$created = $order->get_date_created();
			$issue_date_override = $created ? $created->date_i18n( 'Y-m-d' ) : null;
		} else {
			// status_date
			$issue_date_override = current_time( 'Y-m-d' );
		}

		$client = $this->get_api_client();
		$res    = $client->create_invoice( $order_id, $issue_date_override );

		if ( ! $res['success'] ) {
			$error = is_string( $res['error'] ?? null ) ? $res['error'] : wp_json_encode( $res['error'] ?? 'Error' );

			$order->update_meta_data( '_weblo_fakturownia_last_error', $error );
			$order->save();
			$this->log_error( $order_id, 'auto_issue', $error );

			$this->maybe_send_error_notification( $order_id, $error );

			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'    => 'issue_failed',
						'order_id' => (int) $order_id,
						'error'    => $error,
					),
					'auto'
				);
			}

			return;
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

		// Automatyczna wysyłka faktury emailem – korzystamy z API Fakturowni.
		if ( isset( $settings['weblo_fakturownia_auto_send_invoice_email'] ) && 'yes' === $settings['weblo_fakturownia_auto_send_invoice_email'] && $invoice_id ) {
			$client->send_invoice_by_email( $invoice_id );

			// Dodatkowo: wyślij e‑mail z WooCommerce z PDF w załączniku (jeśli włączone).
			if ( isset( $settings['weblo_fakturownia_send_from_woocommerce'] ) && 'yes' === $settings['weblo_fakturownia_send_from_woocommerce'] ) {
				$this->send_invoice_email_from_woocommerce( $order, (string) $invoice_id, (string) ( $invoice_number ?? '' ) );
			}
		}
	}

	/**
	 * Wysyła e‑mail z WordPressa/WooCommerce z załączonym PDF faktury.
	 * Używane przy automatycznym wystawianiu (hook statusu).
	 *
	 * @param WC_Order $order
	 * @param string   $invoice_id
	 * @param string   $invoice_number
	 * @return bool
	 */
	protected function send_invoice_email_from_woocommerce( $order, $invoice_id, $invoice_number = '' ) {
		if ( ! $order || ! $invoice_id ) {
			return false;
		}

		$to = (string) $order->get_billing_email();
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$settings = $this->get_settings();
		$client   = $this->get_api_client();

		$domain = isset( $settings['weblo_fakturownia_domain'] ) ? (string) $settings['weblo_fakturownia_domain'] : '';
		$domain = preg_replace( '#^https?://#', '', trim( $domain ) );

		// Link dla klienta ma prowadzić do naszego podpisanego pobrania PDF (bez logowania do Fakturowni).
		$invoice_url = $this->build_public_invoice_pdf_url( $order, (string) $invoice_id, (string) $invoice_number );

		$order_url = method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '';
		if ( '' === $order_url ) {
			$order_url = (string) get_permalink( $order->get_id() );
		}

		// Pobranie PDF przez API i zapis do pliku tymczasowego.
		$pdf_info     = $client->download_invoice_pdf( $invoice_id );
		$pdf_url      = ( is_array( $pdf_info ) && ! empty( $pdf_info['success'] ) ) ? (string) ( $pdf_info['url'] ?? '' ) : '';
		$pdf_tmp_file = '';

		if ( '' !== $pdf_url ) {
			$pdf_response = wp_remote_get(
				$pdf_url,
				array(
					'timeout' => 25,
				)
			);

			if ( ! is_wp_error( $pdf_response ) ) {
				$pdf_code = (int) wp_remote_retrieve_response_code( $pdf_response );
				$pdf_body = (string) wp_remote_retrieve_body( $pdf_response );
				if ( 200 === $pdf_code && '' !== $pdf_body ) {
					$pdf_tmp_file = wp_tempnam( 'weblo-fakturownia-invoice-' . $invoice_id . '.pdf' );
					if ( $pdf_tmp_file ) {
						@file_put_contents( $pdf_tmp_file, $pdf_body ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents,WordPress.PHP.NoSilencedErrors.Discouraged
					}
				}
			}
		}

		$invoice_number_display = $invoice_number ? $invoice_number : ( '#' . (string) $invoice_id );

		$subject = sprintf(
			/* translators: 1: invoice number, 2: order number */
			__( 'Your invoice %1$s for order %2$s', 'weblo-fakturownia' ),
			$invoice_number_display,
			(string) $order->get_order_number()
		);

		$template = isset( $settings['weblo_fakturownia_wc_email_template'] ) ? (string) $settings['weblo_fakturownia_wc_email_template'] : '';
		if ( '' === trim( $template ) ) {
			$template = "Hello,\n\nWe are sending you the invoice [invoice_number] for order [order_number] in the attachment.\n\nOrder: [order_link]\nInvoice: [invoice_link]\n\nBest regards,\nStore support";
		}

		$order_link   = $order_url ? ( '<a href="' . esc_url( $order_url ) . '">' . esc_html( $order->get_order_number() ) . '</a>' ) : esc_html( $order->get_order_number() );
		$invoice_link = $invoice_url ? ( '<a href="' . esc_url( $invoice_url ) . '">' . esc_html( $invoice_number_display ) . '</a>' ) : esc_html( $invoice_number_display );

		$vars = array(
			'[order_id]'       => (string) $order->get_id(),
			'[order_number]'   => (string) $order->get_order_number(),
			'[order_url]'      => $order_url,
			'[order_link]'     => $order_link,
			'[invoice_id]'     => (string) $invoice_id,
			'[invoice_number]' => $invoice_number_display,
			'[invoice_url]'    => $invoice_url,
			'[invoice_link]'   => $invoice_link,
		);
		$message = strtr( (string) $template, $vars );

		$attachments = array();
		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			$attachments[] = $pdf_tmp_file;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, wpautop( wp_kses_post( $message ) ), $headers, $attachments );

		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			@unlink( $pdf_tmp_file ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return (bool) $sent;
	}

	/**
	 * Wspólny helper: wysyła e‑mail z WordPressa/WooCommerce z PDF (faktura lub korekta) + template z ustawień.
	 *
	 * @param WC_Order $order
	 * @param string   $invoice_id
	 * @param string   $invoice_number
	 * @param string   $template_setting_key
	 * @return bool
	 */
	protected function send_document_email_from_woocommerce( $order, $invoice_id, $invoice_number, $template_setting_key ) {
		if ( ! $order || ! $invoice_id ) {
			return false;
		}

		$to = (string) $order->get_billing_email();
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$settings = $this->get_settings();
		$client   = $this->get_api_client();

		// PDF: pobranie po API i zapis do pliku tymczasowego.
		$pdf_info     = $client->download_invoice_pdf( $invoice_id );
		$pdf_url      = ( is_array( $pdf_info ) && ! empty( $pdf_info['success'] ) ) ? (string) ( $pdf_info['url'] ?? '' ) : '';
		$pdf_tmp_file = '';

		if ( '' !== $pdf_url ) {
			$pdf_response = wp_remote_get(
				$pdf_url,
				array(
					'timeout' => 25,
				)
			);

			if ( ! is_wp_error( $pdf_response ) ) {
				$pdf_code = (int) wp_remote_retrieve_response_code( $pdf_response );
				$pdf_body = (string) wp_remote_retrieve_body( $pdf_response );
				if ( 200 === $pdf_code && '' !== $pdf_body ) {
					$pdf_tmp_file = wp_tempnam( 'weblo-fakturownia-doc-' . $invoice_id . '.pdf' );
					if ( $pdf_tmp_file ) {
						@file_put_contents( $pdf_tmp_file, $pdf_body ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents,WordPress.PHP.NoSilencedErrors.Discouraged
					}
				}
			}
		}

		$invoice_number_display = $invoice_number ? $invoice_number : ( '#' . (string) $invoice_id );

		// Link w treści e-maila ma prowadzić do podpisanego pobrania PDF z naszego sklepu (bez logowania do Fakturowni).
		$invoice_url = $this->build_public_invoice_pdf_url( $order, (string) $invoice_id, (string) $invoice_number_display );

		$order_url = method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '';
		if ( '' === $order_url ) {
			$order_url = (string) get_permalink( $order->get_id() );
		}

		$template = isset( $settings[ $template_setting_key ] ) ? (string) $settings[ $template_setting_key ] : '';
		if ( '' === trim( $template ) ) {
			$template = "Hello,\n\nWe are sending you the document [invoice_number] for order [order_number] in the attachment.\n\nOrder: [order_link]\nDocument: [invoice_link]\n\nBest regards,\nStore support";
		}

		$order_link   = $order_url ? ( '<a href="' . esc_url( $order_url ) . '">' . esc_html( $order->get_order_number() ) . '</a>' ) : esc_html( $order->get_order_number() );
		$invoice_link = $invoice_url ? ( '<a href="' . esc_url( $invoice_url ) . '">' . esc_html( $invoice_number_display ) . '</a>' ) : esc_html( $invoice_number_display );

		$vars = array(
			'[order_id]'       => (string) $order->get_id(),
			'[order_number]'   => (string) $order->get_order_number(),
			'[order_url]'      => $order_url,
			'[order_link]'     => $order_link,
			'[invoice_id]'     => (string) $invoice_id,
			'[invoice_number]' => $invoice_number_display,
			'[invoice_url]'    => $invoice_url,
			'[invoice_link]'   => $invoice_link,
		);
		$message = strtr( (string) $template, $vars );

		$attachments = array();
		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			$attachments[] = $pdf_tmp_file;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, __( 'Your document for your order', 'weblo-fakturownia' ), wpautop( wp_kses_post( $message ) ), $headers, $attachments );

		if ( $pdf_tmp_file && file_exists( $pdf_tmp_file ) ) {
			@unlink( $pdf_tmp_file ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return (bool) $sent;
	}

	/**
	 * Zapisuje błąd do tabeli logów.
	 *
	 * @param int    $order_id
	 * @param string $type
	 * @param string $error
	 */
	public function log_error( $order_id, $type, $error ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'weblo_fakturownia_logs';

		$wpdb->insert(
			$table_name,
			array(
				'order_id'   => (int) $order_id,
				'type'       => substr( (string) $type, 0, 50 ),
				'error'      => (string) $error,
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Wspólny helper: zapis błędu + e-mail (jeśli włączone).
	 *
	 * @param int    $order_id
	 * @param string $type
	 * @param string $error
	 */
	public function report_error( $order_id, $type, $error ) {
		$this->log_error( $order_id, $type, $error );
		$this->maybe_send_error_notification( $order_id, $error );
	}

	/**
	 * Wysyła mail z powiadomieniem o błędzie, jeśli włączone.
	 *
	 * @param int    $order_id
	 * @param string $error
	 */
	protected function maybe_send_error_notification( $order_id, $error ) {
		$settings = $this->get_settings();

		if ( empty( $settings['weblo_fakturownia_error_notifications_enabled'] ) || 'yes' !== $settings['weblo_fakturownia_error_notifications_enabled'] ) {
			return;
		}

		if ( empty( $settings['weblo_fakturownia_error_notification_email'] ) ) {
			return;
		}

		$to      = sanitize_email( $settings['weblo_fakturownia_error_notification_email'] );
		if ( ! is_email( $to ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d - order ID */
			__( 'Fakturownia integration error – order #%d', 'weblo-fakturownia' ),
			(int) $order_id
		);

		$body = sprintf(
			"Order ID: %d\nError: %s\n",
			(int) $order_id,
			(string) $error
		);

		wp_mail( $to, $subject, $body );
	}

	/**
	 * Pobiera ustawienia integracji WooCommerce (woocommerce_{id}_settings).
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( 'woocommerce_weblo_fakturownia_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Singleton API Client na bazie aktualnych ustawień.
	 *
	 * @return Weblo_Fakturownia_API_Client
	 */
	public function get_api_client() {
		$settings = $this->get_settings();

		$domain        = (string) ( $settings['weblo_fakturownia_domain'] ?? '' );
		$api_token     = (string) ( $settings['weblo_fakturownia_api_token'] ?? '' );
		$department_id = isset( $settings['weblo_fakturownia_department_id'] ) ? (string) $settings['weblo_fakturownia_department_id'] : null;

		$config = array(
			$domain,
			$api_token,
			$department_id,
			(string) ( $settings['weblo_fakturownia_invoice_notes'] ?? '' ),
			(string) ( $settings['weblo_fakturownia_auto_send_invoice_email'] ?? 'no' ),
		);

		if ( null === $this->api_client || null === $this->api_client_config || $this->api_client_config !== $config ) {
			$this->api_client        = new Weblo_Fakturownia_API_Client(
				$domain,
				$api_token,
				$department_id,
				array(
					'weblo_fakturownia_invoice_notes'          => (string) ( $settings['weblo_fakturownia_invoice_notes'] ?? '' ),
					'weblo_fakturownia_auto_send_invoice_email' => (string) ( $settings['weblo_fakturownia_auto_send_invoice_email'] ?? 'no' ),
				)
			);
			$this->api_client_config = $config;
		}

		return $this->api_client;
	}
}

// Rejestracja integracji WooCommerce.
add_filter(
	'woocommerce_integrations',
	function ( $integrations ) {
		$integrations[] = 'WC_Integration_Weblo_Fakturownia';
		return $integrations;
	}
);

// Link "Settings" na liście wtyczek – prowadzi do ustawień integracji.
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=weblo_fakturownia' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'weblo-fakturownia' ) . '</a>';
		return $links;
	}
);

// Inicjalizacja singletonu wtyczki.
Weblo_Fakturownia_Plugin::instance();

