<?php
/**
 * Conversation session manager.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Conversation_Manager {
	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'storeguide_ai_session';

	/**
	 * Resolve current session key.
	 *
	 * @return string
	 */
	public function get_or_create_session_key() {
		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		}

		$key = wp_generate_uuid4();

		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE_NAME,
				$key,
				time() + MONTH_IN_SECONDS,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}

		return $key;
	}

	/**
	 * Resolve or create conversation row by session key.
	 *
	 * @param string $session_key Session key.
	 * @return int
	 */
	public function get_or_create_conversation_id( $session_key ) {
		global $wpdb;
		$conversations = $wpdb->prefix . 'storeguide_ai_conversations';
		$now           = current_time( 'mysql', true );

		$conversation_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$conversations} WHERE session_key = %s ORDER BY id DESC LIMIT 1",
				$session_key
			)
		);

		if ( $conversation_id > 0 ) {
			$wpdb->update(
				$conversations,
				array( 'updated_at' => $now ),
				array( 'id' => $conversation_id ),
				array( '%s' ),
				array( '%d' )
			);
			return $conversation_id;
		}

		$wpdb->insert(
			$conversations,
			array(
				'session_key' => $session_key,
				'started_at'  => $now,
				'updated_at'  => $now,
				'status'      => 'active',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Save message in conversation history.
	 *
	 * @param int    $conversation_id Conversation id.
	 * @param string $role Message role.
	 * @param string $message_text Message content.
	 * @return void
	 */
	public function add_message( $conversation_id, $role, $message_text ) {
		$conversation_id = absint( $conversation_id );
		if ( $conversation_id <= 0 || '' === trim( $message_text ) ) {
			return;
		}

		global $wpdb;
		$messages      = $wpdb->prefix . 'storeguide_ai_messages';
		$conversations = $wpdb->prefix . 'storeguide_ai_conversations';
		$now           = current_time( 'mysql', true );

		$wpdb->insert(
			$messages,
			array(
				'conversation_id' => $conversation_id,
				'role'            => sanitize_key( $role ),
				'message_text'    => sanitize_textarea_field( $message_text ),
				'created_at'      => $now,
			),
			array( '%d', '%s', '%s', '%s' )
		);

		$wpdb->update(
			$conversations,
			array( 'updated_at' => $now ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Fetch recent conversation messages.
	 *
	 * @param int $conversation_id Conversation id.
	 * @param int $limit Max rows.
	 * @return array<int, array<string, string>>
	 */
	public function get_recent_messages( $conversation_id, $limit = 8 ) {
		$conversation_id = absint( $conversation_id );
		$limit           = max( 1, min( 20, absint( $limit ) ) );
		if ( $conversation_id <= 0 ) {
			return array();
		}

		global $wpdb;
		$messages = $wpdb->prefix . 'storeguide_ai_messages';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, message_text
				FROM {$messages}
				WHERE conversation_id = %d
				ORDER BY id DESC
				LIMIT %d",
				$conversation_id,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_reverse(
			array_map(
				static function( $row ) {
					return array(
						'role'    => isset( $row['role'] ) ? (string) $row['role'] : 'assistant',
						'message' => isset( $row['message_text'] ) ? (string) $row['message_text'] : '',
					);
				},
				$rows
			)
		);
	}
}
