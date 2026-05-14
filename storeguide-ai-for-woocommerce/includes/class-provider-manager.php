<?php
/**
 * Provider manager.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Provider_Manager {
	/**
	 * Generate response from active provider.
	 *
	 * @param array<string, mixed> $payload Prompt payload.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function generate( $payload ) {
		$options  = get_option( 'storeguide_ai_provider_options', array() );
		$provider = isset( $options['provider'] ) ? sanitize_key( $options['provider'] ) : 'openai';

		$service = $this->create_provider( $provider, $options );
		if ( ! $service ) {
			return new WP_Error( 'storeguide_ai_provider_invalid', __( 'No valid provider configured.', 'storeguide-ai' ) );
		}

		$response = $service->generate( $payload );
		if ( ! is_wp_error( $response ) ) {
			return $response;
		}

		// Simple fallback: try OpenAI when selected provider fails and OpenAI key exists.
		if ( 'openai' !== $provider && ! empty( $options['api_key'] ) ) {
			$fallback = new StoreGuide_AI_OpenAI_Provider( $options );
			return $fallback->generate( $payload );
		}

		return $response;
	}

	/**
	 * Create provider object.
	 *
	 * @param string               $provider Provider key.
	 * @param array<string, mixed> $options Provider options.
	 * @return StoreGuide_AI_Provider_Interface|null
	 */
	private function create_provider( $provider, $options ) {
		switch ( $provider ) {
			case 'openrouter':
				return new StoreGuide_AI_OpenRouter_Provider( $options );
			case 'custom':
				return new StoreGuide_AI_Custom_Provider( $options );
			case 'openai':
			default:
				return new StoreGuide_AI_OpenAI_Provider( $options );
		}
	}
}
