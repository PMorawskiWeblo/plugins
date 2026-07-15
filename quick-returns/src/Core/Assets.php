<?php

namespace Weblo\QuickReturns\Core;

use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;
use Weblo\QuickReturns\Support\Helpers;
use Weblo\QuickReturns\Support\Templates;

class Assets {

	private static bool $should_enqueue = false;

	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue' ], 20 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_for_selectors' ], 5 );
		add_action( 'wp_footer', [ $this, 'render_modal_shell' ], 5 );
	}

	public function maybe_enqueue_for_selectors(): void {
		if ( ! Helpers::should_load_frontend() ) {
			return;
		}

		$selectors = SettingsRepository::get( 'trigger_selectors', '' );
		if ( ! empty( trim( $selectors ) ) ) {
			self::flag_enqueue();
		}
	}

	public static function flag_enqueue(): void {
		if ( ! Helpers::should_load_frontend() ) {
			return;
		}
		self::$should_enqueue = true;
	}

	public function maybe_enqueue(): void {
		if ( ! self::$should_enqueue ) {
			return;
		}
		$this->enqueue();
	}

	public function enqueue(): void {
		$settings = SettingsRepository::get_all();
		$accent   = $settings['accent_color'] ?? '#F68B2F';

		wp_enqueue_style(
			'quick-returns',
			QUICK_RETURNS_URL . 'assets/css/frontend.css',
			[],
			Helpers::asset_version( 'assets/css/frontend.css' )
		);

		wp_add_inline_style(
			'quick-returns',
			sprintf( ':root { --qr-accent: %s; --qr-accent-light: %s; }', esc_attr( $accent ), esc_attr( $this->lighten( $accent ) ) )
		);

		wp_enqueue_script(
			'quick-returns',
			QUICK_RETURNS_URL . 'assets/js/frontend.js',
			[],
			Helpers::asset_version( 'assets/js/frontend.js' ),
			true
		);

		wp_localize_script(
			'quick-returns',
			'quickReturns',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( 'quick-returns/v1' ) ),
				'nonce'     => wp_create_nonce( 'quick_returns' ),
				'shopUrl'   => wc_get_page_permalink( 'shop' ),
				'isLoggedIn' => is_user_logged_in(),
				'orderContext' => Helpers::get_view_order_context(),
				'currency'  => Helpers::get_currency_settings(),
				'settings'  => [
					'triggerSelectors'   => $settings['trigger_selectors'] ?? '',
					'returnReasons'      => $settings['return_reasons'] ?? [],
					'autoSelectAll'      => ! empty( $settings['auto_select_all'] ),
					'confirmationMsg'    => $settings['confirmation_message'] ?? '',
					'introDescription'   => $settings['intro_description'] ?? '',
					'triggerText'        => $settings['trigger_text'] ?? '',
				],
				'i18n'      => [
					'loading'              => __( 'Loading…', 'quick-returns' ),
					'errorGeneric'         => __( 'Something went wrong. Please try again.', 'quick-returns' ),
					'errorOrderNotFound'   => __( 'We could not find an order matching those details.', 'quick-returns' ),
					'errorSelectProduct'   => __( 'Please select at least one product.', 'quick-returns' ),
					'errorSelectReason'    => __( 'Please select a return reason for each product.', 'quick-returns' ),
					'errorRequiredFields'  => __( 'Please fill in all required fields.', 'quick-returns' ),
					'pcs'                  => __( 'pc.', 'quick-returns' ),
					'alreadyReturned'      => __( 'already returned', 'quick-returns' ),
					'selectedProducts'     => __( 'Selected products', 'quick-returns' ),
					'totalQuantity'        => __( 'Total quantity', 'quick-returns' ),
					'estimatedValue'       => __( 'Estimated value', 'quick-returns' ),
					'valueDisclaimer'      => __( 'The final refund amount will be confirmed by the store.', 'quick-returns' ),
					'selectPlaceholder'    => __( '— select —', 'quick-returns' ),
					'stepOrder'            => __( 'Order', 'quick-returns' ),
					'stepProducts'         => __( 'Products', 'quick-returns' ),
					'stepConfirmation'     => __( 'Confirmation', 'quick-returns' ),
					'labelOrderNumber'     => __( 'Order number', 'quick-returns' ),
					'placeholderOrderNumber' => __( 'e.g. 12345', 'quick-returns' ),
					'labelEmail'           => __( 'Email address', 'quick-returns' ),
					'placeholderEmail'     => __( 'name@domain.com', 'quick-returns' ),
					'hintOrderLookup'      => __( 'Fill in both fields to identify your order.', 'quick-returns' ),
					'buttonSearchOrder'    => __( 'Search for order', 'quick-returns' ),
					'buttonSubmit'         => __( 'Send return request', 'quick-returns' ),
					'buttonBack'           => __( 'Back', 'quick-returns' ),
					'buttonBackToShop'     => __( 'Back to shop', 'quick-returns' ),
					'labelOrder'           => __( 'Order', 'quick-returns' ),
					'labelQuantity'        => __( 'Quantity', 'quick-returns' ),
					'labelReason'          => __( 'Reason for return', 'quick-returns' ),
					'labelComment'         => __( 'Comment', 'quick-returns' ),
					'placeholderComment'   => __( 'Optional comment for this product', 'quick-returns' ),
					'confirmationTitle'    => __( 'Thank you for submitting your return', 'quick-returns' ),
					'labelReturnAddress'   => __( 'Return address:', 'quick-returns' ),
				],
			]
		);
	}

	public function render_modal_shell(): void {
		if ( ! self::$should_enqueue || ! Helpers::should_load_frontend() ) {
			return;
		}
		Templates::render( 'modal-shell' );
	}

	private function lighten( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = min( 255, hexdec( substr( $hex, 0, 2 ) ) + 40 );
		$g = min( 255, hexdec( substr( $hex, 2, 2 ) ) + 40 );
		$b = min( 255, hexdec( substr( $hex, 4, 2 ) ) + 40 );
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}
}