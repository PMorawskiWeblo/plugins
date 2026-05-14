<?php
/**
 * Frontend module.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Frontend {
	/**
	 * Widget renderer.
	 *
	 * @var StoreGuide_AI_Widget_Renderer
	 */
	private $widget_renderer;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Widget_Renderer $widget_renderer Widget renderer.
	 */
	public function __construct( $widget_renderer ) {
		$this->widget_renderer = $widget_renderer;
	}

	/**
	 * Register frontend hooks.
	 *
	 * @param StoreGuide_AI_Loader $loader Loader.
	 * @return void
	 */
	public function register( $loader ) {
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' );
		$loader->add_action( 'wp_footer', $this->widget_renderer, 'render' );
	}

	/**
	 * Enqueue chat assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$options = get_option( 'storeguide_ai_options', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$dev_options   = get_option( 'storeguide_ai_dev_options', array() );
		$asset_version = ! empty( $dev_options['asset_version'] ) ? sanitize_text_field( (string) $dev_options['asset_version'] ) : STOREGUIDE_AI_VERSION;

		wp_enqueue_style(
			'storeguide-ai-chat-widget',
			STOREGUIDE_AI_PLUGIN_URL . 'assets/css/chat-widget.css',
			array(),
			$asset_version
		);
		$widget_options = get_option( 'storeguide_ai_widget_options', array() );
		$custom_css     = isset( $widget_options['custom_css'] ) ? trim( (string) $widget_options['custom_css'] ) : '';
		if ( '' !== $custom_css ) {
			wp_add_inline_style( 'storeguide-ai-chat-widget', $custom_css );
		}

		wp_enqueue_script(
			'storeguide-ai-chat-widget',
			STOREGUIDE_AI_PLUGIN_URL . 'assets/js/chat-widget.js',
			array(),
			$asset_version,
			true
		);

		wp_localize_script(
			'storeguide-ai-chat-widget',
			'StoreGuideAIConfig',
			array(
				'endpoint'      => esc_url_raw( rest_url( 'storeguide-ai/v1/chat' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'lang'          => $this->resolve_current_language(),
				'widgetDisplay' => $this->get_widget_display_settings(),
			)
		);
	}

	/**
	 * Get widget result display settings for frontend renderer.
	 *
	 * @return array<string, mixed>
	 */
	private function get_widget_display_settings() {
		$options = get_option( 'storeguide_ai_widget_options', array() );
		$fields  = isset( $options['result_fields'] ) && is_array( $options['result_fields'] ) ? array_values( array_map( 'sanitize_key', $options['result_fields'] ) ) : array( 'thumbnail', 'name', 'price', 'link' );
		return array(
			'fields'      => $fields,
			'showRelated' => ! empty( $options['show_related_products'] ),
		);
	}

	/**
	 * Resolve frontend language code.
	 *
	 * @return string
	 */
	private function resolve_current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$pll_lang = pll_current_language( 'slug' );
			if ( is_string( $pll_lang ) && '' !== $pll_lang ) {
				return sanitize_key( strtolower( $pll_lang ) );
			}
		}

		$wpml_lang = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml_lang ) && '' !== $wpml_lang ) {
			return sanitize_key( strtolower( $wpml_lang ) );
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		if ( ! is_string( $locale ) || '' === $locale ) {
			return 'en';
		}

		$parts = explode( '_', $locale );
		return sanitize_key( strtolower( $parts[0] ) );
	}
}
