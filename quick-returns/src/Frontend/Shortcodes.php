<?php

namespace Weblo\QuickReturns\Frontend;

use Weblo\QuickReturns\Core\Assets;
use Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository;
use Weblo\QuickReturns\Support\Helpers;
use Weblo\QuickReturns\Support\Templates;

class Shortcodes {

	public function register_hooks(): void {
		add_shortcode( 'quick_returns_form', [ $this, 'render_form' ] );
		add_shortcode( 'quick_returns_trigger', [ $this, 'render_trigger' ] );

		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'order_view_trigger' ], 20 );
		add_action( 'wp', [ $this, 'maybe_enqueue_on_order_view' ] );
		add_filter( 'the_posts', [ $this, 'detect_shortcode' ], 10, 2 );
	}

	public function maybe_enqueue_on_order_view(): void {
		if ( Helpers::get_view_order_context() ) {
			Assets::flag_enqueue();
		}
	}

	public function detect_shortcode( array $posts, $query ): array {
		if ( empty( $posts ) || is_admin() ) {
			return $posts;
		}
		foreach ( $posts as $post ) {
			if ( is_object( $post ) && ! empty( $post->post_content ) ) {
				if (
					has_shortcode( $post->post_content, 'quick_returns_form' ) ||
					has_shortcode( $post->post_content, 'quick_returns_trigger' )
				) {
					$this->enqueue_assets();
					break;
				}
			}
		}
		return $posts;
	}

	public function render_form( $atts ): string {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			[
				'mode'     => 'manual_select',
				'order_id' => 0,
			],
			$atts,
			'quick_returns_form'
		);

		$order_id = absint( $atts['order_id'] );
		if ( ! $order_id && isset( $_GET['order_id'] ) ) {
			$order_id = absint( $_GET['order_id'] );
		}

		$order_context = Helpers::get_view_order_context();
		$email         = '';

		if ( ! $order_id && $order_context ) {
			$order_id = (int) $order_context['order_id'];
			$email    = $order_context['email'];
		}

		if ( is_user_logged_in() && $order_id && ! $email ) {
			$order = wc_get_order( $order_id );
			if ( $order && (int) $order->get_customer_id() === get_current_user_id() ) {
				$email = $order->get_billing_email();
			}
		}

		$initial_step = 1;
		if ( is_user_logged_in() && $order_id ) {
			$initial_step = 2;
		}

		$selection_mode = sanitize_text_field( $atts['mode'] );
		if ( $order_context && 'manual_select' === $selection_mode ) {
			$selection_mode = $order_context['mode'];
		}

		return Templates::get(
			'form-wrapper',
			[
				'context'        => 'inline',
				'initial_step'   => $initial_step,
				'order_id'       => $order_id,
				'email'          => $email,
				'selection_mode' => $selection_mode,
			]
		);
	}

	public function render_trigger( $atts ): string {
		$this->enqueue_assets();

		$settings = SettingsRepository::get_all();
		$atts     = shortcode_atts(
			[
				'text'     => $settings['trigger_text'],
				'class'    => $settings['trigger_class'],
				'mode'     => 'manual_select',
				'order_id' => 0,
				'email'    => '',
			],
			$atts,
			'quick_returns_trigger'
		);

		$order_id = absint( $atts['order_id'] );
		if ( ! $order_id && isset( $_GET['order_id'] ) ) {
			$order_id = absint( $_GET['order_id'] );
		}

		$email = sanitize_email( $atts['email'] ?? '' );
		$order_context = Helpers::get_view_order_context();

		if ( ! $order_id && $order_context ) {
			$order_id = (int) $order_context['order_id'];
			$email    = $order_context['email'];
			if ( 'manual_select' === $atts['mode'] ) {
				$atts['mode'] = $order_context['mode'];
			}
		}

		if ( $order_id && ! $email && is_user_logged_in() ) {
			$order = wc_get_order( $order_id );
			if ( $order && (int) $order->get_customer_id() === get_current_user_id() ) {
				$email = $order->get_billing_email();
			}
		}

		$classes = array_filter( array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) );
		$classes[] = 'qr-trigger-btn';

		return sprintf(
			'<button type="button" class="%s" data-qr-trigger="1" data-qr-mode="%s" data-qr-order-id="%d" data-qr-email="%s">%s</button>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( sanitize_text_field( $atts['mode'] ) ),
			$order_id,
			esc_attr( $email ),
			esc_html( $atts['text'] )
		);
	}

	public function order_view_trigger( \WC_Order $order ): void {
		if ( Helpers::is_order_received_page() ) {
			return;
		}

		if ( ! is_user_logged_in() || (int) get_current_user_id() !== (int) $order->get_customer_id() ) {
			return;
		}

		$this->enqueue_assets();

		echo wp_kses_post(
			$this->render_trigger(
				[
					'text'     => __( 'Report a return', 'quick-returns' ),
					'class'    => 'button',
					'mode'     => SettingsRepository::get( 'auto_select_all' ) ? 'all_items' : 'manual_select',
					'order_id' => $order->get_id(),
					'email'    => $order->get_billing_email(),
				]
			)
		);
	}

	private function enqueue_assets(): void {
		Assets::flag_enqueue();
		( new Assets() )->enqueue();
	}
}
