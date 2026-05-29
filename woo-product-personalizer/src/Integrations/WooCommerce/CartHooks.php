<?php
/**
 * Cart integration hooks.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Domain\Project\ValidationService;
use WooProductPersonalizer\Helpers\UploadSession;
use WooProductPersonalizer\Helpers\UploadUrlValidator;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;
use WooProductPersonalizer\Infrastructure\Repository\ProductSettingsRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\CartPreviewStorage;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class CartHooks
 */
class CartHooks {

	const CART_KEY = 'wpp';

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Uploads.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Products.
	 *
	 * @var ProductSettingsRepository
	 */
	private $products;

	/**
	 * Layouts.
	 *
	 * @var LayoutRepository
	 */
	private $layouts;

	/**
	 * Validator.
	 *
	 * @var ValidationService
	 */
	private $validator;

	/**
	 * Cart preview file storage.
	 *
	 * @var CartPreviewStorage
	 */
	private $cart_previews;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 * @param Logger             $logger   Logger.
	 * @param UploadsManager     $uploads  Uploads.
	 */
	public function __construct( SettingsRepository $settings, Logger $logger, UploadsManager $uploads ) {
		$this->settings  = $settings;
		$this->logger    = $logger;
		$this->uploads   = $uploads;
		$this->products  = new ProductSettingsRepository();
		$this->layouts   = new LayoutRepository();
		$this->validator     = new ValidationService( $settings );
		$this->cart_previews = new CartPreviewStorage( $uploads, $logger );

		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_disable_add_to_cart_script' ), 1 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'append_preview_link_to_cart_item_name' ), 20, 3 );
		add_action( 'wp_footer', array( $this, 'render_preview_popup' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_assets' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'cleanup_removed_cart_preview' ), 10, 2 );
	}

	/**
	 * Validate personalization on add to cart.
	 *
	 * @param bool $passed     Passed.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity ) {
		unset( $quantity );

		$config = $this->products->get( $product_id );

		if ( ! $config->is_active() ) {
			return $passed;
		}

		if ( ! $this->verify_personalizer_request() ) {
			wc_add_notice( __( 'Personalization security check failed. Please refresh the page and try again.', 'woo-product-personalizer' ), 'error' );
			return false;
		}

		$state = $this->get_submitted_project_state();
		if ( ! is_array( $state ) ) {
			wc_add_notice( __( 'Personalization data is missing.', 'woo-product-personalizer' ), 'error' );
			return false;
		}

		$layout = $this->layouts->find( $config->get_layout_id() );
		if ( ! $layout ) {
			wc_add_notice( __( 'Layout configuration error.', 'woo-product-personalizer' ), 'error' );
			return false;
		}

		if ( (int) ( $state['layout_id'] ?? 0 ) !== (int) $config->get_layout_id() ) {
			wc_add_notice( __( 'Personalization layout mismatch.', 'woo-product-personalizer' ), 'error' );
			return false;
		}

		$result = $this->validator->validate( $state, $layout, $config );
		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Attach personalization to cart item.
	 *
	 * @param array $cart_item_data Cart data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$config = $this->products->get( $product_id );

		if ( ! $config->is_active() ) {
			return $cart_item_data;
		}

		$state = $this->get_submitted_project_state();
		if ( ! is_array( $state ) ) {
			return $cart_item_data;
		}

		$layout  = $this->layouts->find( $config->get_layout_id() );
		$summary = $layout ? $this->validator->build_summary( $state, $layout ) : array();

		$preview_raw        = isset( $_POST['wpp_preview_data'] ) ? $this->sanitize_preview_data( wp_unslash( $_POST['wpp_preview_data'] ) ) : '';
		$preview_layers_raw = isset( $_POST['wpp_preview_layers_data'] ) ? $this->sanitize_preview_data( wp_unslash( $_POST['wpp_preview_layers_data'] ) ) : '';
		$preview_text_svg   = isset( $_POST['wpp_text_svg_data'] ) ? $this->sanitize_text_svg_data( wp_unslash( $_POST['wpp_text_svg_data'] ) ) : '';
		$preview            = $this->persist_cart_preview( $preview_raw, $preview_layers_raw, $preview_text_svg );

		$cart_item_data[ self::CART_KEY ] = array(
			'layout_id'              => $config->get_layout_id(),
			'product_id'             => $product_id,
			'summary'                => $summary,
			'project_state'          => $state,
			'preview_data'           => $preview['thumb_url'] ?? '',
			'preview_full_url'       => $preview['full_url'] ?? '',
			'preview_layers_full_url' => $preview['layers_full_url'] ?? '',
			'preview_text_svg_full_url' => $preview['text_svg_full_url'] ?? '',
			'preview_id'             => $preview['id'] ?? '',
			'hash'              => md5( wp_json_encode( $state ) . $product_id ),
			'personalized'      => true,
		);

		$cart_item_data['unique_key'] = md5( microtime() . wp_rand() );

		$this->logger->debug( 'Cart item personalization attached.', array( 'product_id' => $product_id ) );

		return $cart_item_data;
	}

	/**
	 * Display personalization in cart/checkout.
	 *
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ self::CART_KEY ]['personalized'] ) ) {
			return $item_data;
		}

		// Checkout order summary (product-meta) — same placement as personalize-product plugin.
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			$preview = $cart_item[ self::CART_KEY ]['preview_data'] ?? '';

			if ( $preview ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
				echo PreviewDisplay::get_link_markup( $preview, $this->get_cart_preview_popup_source( $cart_item ) );
			}
		}

		return $item_data;
	}

	/**
	 * Append preview link under product name (cart, checkout, side cart).
	 *
	 * @param string $product_name  Product name HTML.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function append_preview_link_to_cart_item_name( $product_name, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		if ( empty( $cart_item[ self::CART_KEY ]['personalized'] ) ) {
			return $product_name;
		}

		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			return $product_name;
		}

		$preview = $cart_item[ self::CART_KEY ]['preview_data'] ?? '';

		if ( '' === $preview ) {
			return $product_name;
		}

		return $product_name . PreviewDisplay::get_link_markup( $preview, $this->get_cart_preview_popup_source( $cart_item ) );
	}

	/**
	 * Output shared preview modal markup once per page.
	 *
	 * @return void
	 */
	public function render_preview_popup() {
		if ( ! $this->should_output_cart_preview_shell() ) {
			return;
		}

		static $rendered = false;

		if ( $rendered ) {
			return;
		}

		$rendered = true;

		$template = WPP_PLUGIN_PATH . 'templates/frontend/cart-preview-popup.php';

		if ( is_readable( $template ) ) {
			include $template;
		}
	}

	/**
	 * Enqueue cart preview assets when cart may show personalized items.
	 *
	 * @return void
	 */
	public function enqueue_cart_assets() {
		if ( ! $this->should_output_cart_preview_shell() ) {
			return;
		}

		wp_enqueue_style(
			'wpp-cart-preview',
			WPP_PLUGIN_URL . 'assets/css/cart-preview.css',
			array(),
			WPP_VERSION
		);

		wp_enqueue_script(
			'wpp-cart-preview',
			WPP_PLUGIN_URL . 'assets/js/cart-preview.js',
			array( 'jquery' ),
			WPP_VERSION,
			true
		);
	}

	/**
	 * Whether to output the preview modal shell and load cart-preview assets in the footer.
	 *
	 * Rendered on all storefront pages so side carts / AJAX mini-carts can open the popup.
	 *
	 * @return bool
	 */
	private function should_output_cart_preview_shell() {
		if ( is_admin() ) {
			return false;
		}

		return function_exists( 'WC' );
	}

	/**
	 * Verify personalization nonce on add to cart.
	 *
	 * @return bool
	 */
	private function verify_personalizer_request() {
		$nonce = isset( $_POST['wpp_personalizer_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['wpp_personalizer_nonce'] ) )
			: '';

		return (bool) wp_verify_nonce( $nonce, 'wpp_personalizer' );
	}

	/**
	 * Parse and sanitize submitted project state.
	 *
	 * @return array|null
	 */
	private function get_submitted_project_state() {
		$raw = isset( $_POST['wpp_project_state'] ) ? wp_unslash( $_POST['wpp_project_state'] ) : '';
		$state = json_decode( $raw, true );

		if ( ! is_array( $state ) ) {
			return null;
		}

		UploadSession::ensure_session();
		$token = UploadSession::get_token();

		return UploadUrlValidator::sanitize_image_fields( $state, $token );
	}

	/**
	 * Whether current cart contains personalized items.
	 *
	 * @return bool
	 */
	private function cart_has_personalized_items() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item[ self::CART_KEY ]['personalized'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the order on thank-you / view-order pages has personalized items.
	 *
	 * @return bool
	 */
	private function viewed_order_has_personalized_items() {
		$order = $this->get_viewed_order();

		if ( ! $order ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			if ( 'yes' === $item->get_meta( '_wpp_personalized' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Order being viewed on frontend endpoints.
	 *
	 * @return \WC_Order|null
	 */
	private function get_viewed_order() {
		if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
			return null;
		}

		$order_id = 0;

		if ( is_wc_endpoint_url( 'order-received' ) ) {
			$order_id = absint( get_query_var( 'order-received' ) );
		} elseif ( is_wc_endpoint_url( 'view-order' ) ) {
			$order_id = absint( get_query_var( 'view-order' ) );
		}

		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Delete preview files when a cart line is removed.
	 *
	 * @param string   $cart_item_key Cart item key.
	 * @param \WC_Cart $cart          Cart.
	 * @return void
	 */
	public function cleanup_removed_cart_preview( $cart_item_key, $cart ) {
		unset( $cart );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$removed = WC()->cart->removed_cart_contents[ $cart_item_key ] ?? null;

		if ( ! is_array( $removed ) ) {
			return;
		}

		$this->delete_cart_preview_files( $removed[ self::CART_KEY ] ?? array() );
	}

	/**
	 * Save base64 canvas output to disk; return URLs for session storage.
	 *
	 * @param string $data_url        Sanitized full preview data URL.
	 * @param string $layers_data_url Sanitized layers-only preview data URL.
	 * @return array{thumb_url?: string, full_url?: string, layers_full_url?: string, text_svg_full_url?: string, id?: string}
	 */
	private function persist_cart_preview( $data_url, $layers_data_url = '', $text_svg = '' ) {
		if ( '' === $data_url ) {
			return array();
		}

		$stored = $this->cart_previews->store_from_data_url( $data_url );

		if ( false === $stored ) {
			$this->logger->warning( 'Cart preview could not be stored; preview link omitted.' );
			return array();
		}

		$result = array(
			'id'        => $stored['id'],
			'thumb_url' => $stored['thumb_url'],
			'full_url'  => $stored['full_url'],
		);

		if ( '' !== $layers_data_url ) {
			$layers = $this->cart_previews->store_layers_from_data_url( $stored['id'], $layers_data_url );
			if ( false !== $layers ) {
				$result['layers_full_url'] = $layers['url'];
			}
		}

		if ( '' !== $text_svg ) {
			$text_file = $this->cart_previews->store_text_svg( $stored['id'], $text_svg );
			if ( false !== $text_file ) {
				$result['text_svg_full_url'] = $text_file['url'];
			}
		}

		return $result;
	}

	/**
	 * Full-size preview URL for the cart popup (falls back to thumbnail / legacy data URL).
	 *
	 * @param array $cart_item Cart item.
	 * @return string
	 */
	private function get_cart_preview_popup_source( array $cart_item ) {
		$wpp = $cart_item[ self::CART_KEY ] ?? array();

		if ( ! empty( $wpp['preview_full_url'] ) ) {
			return (string) $wpp['preview_full_url'];
		}

		return (string) ( $wpp['preview_data'] ?? '' );
	}

	/**
	 * Remove cart preview files referenced by cart line data.
	 *
	 * @param array $wpp Cart personalization payload.
	 * @return void
	 */
	private function delete_cart_preview_files( array $wpp ) {
		if ( empty( $wpp['preview_id'] ) ) {
			return;
		}

		$this->cart_previews->delete( (string) $wpp['preview_id'] );
	}

	/**
	 * Sanitize canvas preview data URL from the editor.
	 *
	 * @param string $raw Raw preview.
	 * @return string
	 */
	private function sanitize_preview_data( $raw ) {
		$raw = is_string( $raw ) ? trim( $raw ) : '';

		if ( '' === $raw ) {
			return '';
		}

		if ( preg_match( '#^data:image/(png|jpe?g|webp);base64,[a-zA-Z0-9+/=\s]+$#', $raw ) ) {
			return preg_replace( '/\s+/', '', $raw );
		}

		return '';
	}

	/**
	 * Sanitize submitted text SVG document.
	 *
	 * @param string $raw Raw SVG.
	 * @return string
	 */
	private function sanitize_text_svg_data( $raw ) {
		$raw = is_string( $raw ) ? trim( $raw ) : '';

		if ( '' === $raw ) {
			return '';
		}

		if ( strlen( $raw ) > 500000 ) {
			return '';
		}

		if ( ! preg_match( '#^\s*<\?xml#i', $raw ) && ! preg_match( '#^\s*<svg#i', $raw ) ) {
			return '';
		}

		$raw = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $raw );
		$raw = preg_replace( '/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $raw );

		return is_string( $raw ) ? trim( $raw ) : '';
	}

	/**
	 * Restore cart item from session.
	 *
	 * @param array $cart_item Cart item.
	 * @param array $values    Session values.
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values[ self::CART_KEY ] ) ) {
			$cart_item[ self::CART_KEY ] = $values[ self::CART_KEY ];
		}
		return $cart_item;
	}

	/**
	 * Placeholder for future cart-wide scripts.
	 *
	 * @return void
	 */
	public function maybe_disable_add_to_cart_script() {
		// Handled on frontend via wpp-personalizer.js.
	}
}
