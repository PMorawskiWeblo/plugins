<?php
/**
 * REST API routes.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_REST {
	/**
	 * Chat controller.
	 *
	 * @var StoreGuide_AI_Chat_Controller
	 */
	private $chat_controller;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Chat_Controller $chat_controller Chat controller.
	 */
	public function __construct( $chat_controller ) {
		$this->chat_controller = $chat_controller;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'storeguide-ai/v1',
			'/chat',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this->chat_controller, 'handle_message' ),
				'permission_callback' => array( $this, 'can_send_chat_message' ),
				'args'                => array(
					'message' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => array( $this, 'validate_message_length' ),
					),
					'language' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Check request permissions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function can_send_chat_message( $request ) {
		$options = get_option( 'storeguide_ai_options', array() );
		if ( empty( $options['enabled'] ) ) {
			return new WP_Error( 'storeguide_ai_rest_disabled', esc_html__( 'Assistant is disabled.', 'storeguide-ai' ), array( 'status' => 403 ) );
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return new WP_Error( 'storeguide_ai_rest_nonce_missing', esc_html__( 'Security check failed.', 'storeguide-ai' ), array( 'status' => 403 ) );
		}

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'storeguide_ai_rest_nonce_invalid', esc_html__( 'Security check failed.', 'storeguide-ai' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Validate message length.
	 *
	 * @param mixed           $value Message value.
	 * @param WP_REST_Request $request Request.
	 * @param string          $param Param name.
	 * @return true|\WP_Error
	 */
	public function validate_message_length( $value, $request, $param ) {
		$length = strlen( trim( (string) $value ) );
		if ( $length > 1200 ) {
			return new WP_Error( 'storeguide_ai_rest_message_too_long', esc_html__( 'Message is too long.', 'storeguide-ai' ), array( 'status' => 400 ) );
		}
		return true;
	}
}
