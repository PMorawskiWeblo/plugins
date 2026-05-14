<?php
/**
 * OpenAI provider implementation.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_OpenAI_Provider implements StoreGuide_AI_Provider_Interface {
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
			return new WP_Error( 'storeguide_ai_openai_missing_key', __( 'OpenAI API key is missing.', 'storeguide-ai' ) );
		}

		$model       = isset( $this->options['model'] ) ? (string) $this->options['model'] : 'gpt-4.1-mini';
		$max_tokens  = isset( $this->options['max_tokens'] ) ? (int) $this->options['max_tokens'] : 800;
		$temperature = isset( $this->options['temperature'] ) ? (float) $this->options['temperature'] : 0.3;
		$base_url    = isset( $this->options['base_url'] ) && '' !== $this->options['base_url'] ? rtrim( (string) $this->options['base_url'], '/' ) : 'https://api.openai.com/v1';

		$request_body = array(
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
		);

		$response = wp_remote_post(
			$base_url . '/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			return new WP_Error( 'storeguide_ai_openai_http', sprintf( __( 'OpenAI request failed (HTTP %d).', 'storeguide-ai' ), $code ) );
		}

		$text = isset( $body['choices'][0]['message']['content'] ) ? (string) $body['choices'][0]['message']['content'] : '';
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'storeguide_ai_openai_empty', __( 'OpenAI returned an empty response.', 'storeguide-ai' ) );
		}

		return array(
			'message' => $text,
			'model'   => $model,
		);
	}
}
