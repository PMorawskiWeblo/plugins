<?php
/**
 * OpenRouter provider implementation.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_OpenRouter_Provider implements StoreGuide_AI_Provider_Interface {
	/**
	 * Options.
	 *
	 * @var array<string, mixed>
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $options Options.
	 */
	public function __construct( $options ) {
		$this->options = $options;
	}

	/**
	 * Generate response.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function generate( $payload ) {
		$api_key = isset( $this->options['api_key'] ) ? (string) $this->options['api_key'] : '';
		if ( '' === $api_key ) {
			return new WP_Error( 'storeguide_ai_openrouter_missing_key', __( 'OpenRouter API key is missing.', 'storeguide-ai' ) );
		}

		$model       = isset( $this->options['model'] ) ? (string) $this->options['model'] : 'openai/gpt-4.1-mini';
		$max_tokens  = isset( $this->options['max_tokens'] ) ? (int) $this->options['max_tokens'] : 800;
		$temperature = isset( $this->options['temperature'] ) ? (float) $this->options['temperature'] : 0.3;

		$response = wp_remote_post(
			'https://openrouter.ai/api/v1/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => home_url(),
					'X-Title'       => 'StoreGuide AI',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'temperature' => $temperature,
						'max_tokens'  => $max_tokens,
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => (string) $payload['system_prompt'],
							),
							array(
								'role'    => 'user',
								'content' => (string) $payload['user_prompt'],
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			return new WP_Error( 'storeguide_ai_openrouter_http', sprintf( __( 'OpenRouter request failed (HTTP %d).', 'storeguide-ai' ), $code ) );
		}

		$text = isset( $body['choices'][0]['message']['content'] ) ? (string) $body['choices'][0]['message']['content'] : '';
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'storeguide_ai_openrouter_empty', __( 'OpenRouter returned an empty response.', 'storeguide-ai' ) );
		}

		return array(
			'message' => $text,
			'model'   => $model,
		);
	}
}
