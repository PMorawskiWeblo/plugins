<?php
/**
 * Chat request controller.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Chat_Controller {
	private const CACHE_OPTION = 'storeguide_ai_qa_cache';
	private const MAX_MESSAGE_LENGTH = 1200;
	private const MAX_CACHE_MESSAGE_LENGTH = 4000;
	private const MAX_CACHE_ITEMS_PER_RESPONSE = 20;
	private const MAX_LOG_DETAILS_LENGTH = 500;
	private const MAX_PROMPT_LENGTH = 12000;

	/**
	 * Conversation service.
	 *
	 * @var StoreGuide_AI_Conversation_Manager
	 */
	private $conversation_manager;

	/**
	 * Retrieval service.
	 *
	 * @var StoreGuide_AI_Retriever
	 */
	private $retriever;
	private $provider_manager;
	private $quota_manager;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Conversation_Manager $conversation_manager Conversation manager.
	 * @param StoreGuide_AI_Retriever            $retriever Retriever service.
	 */
	public function __construct( $conversation_manager, $retriever, $provider_manager, $quota_manager ) {
		$this->conversation_manager = $conversation_manager;
		$this->retriever            = $retriever;
		$this->provider_manager     = $provider_manager;
		$this->quota_manager        = $quota_manager;
	}

	/**
	 * Handle chat message request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_message( $request ) {
		$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
		$message = trim( preg_replace( '/\s+/u', ' ', $message ) );
		$language = sanitize_key( (string) $request->get_param( 'language' ) );
		if ( '' === $language ) {
			$language = 'en';
		}

		$locale   = $this->resolve_locale_from_language( $language );
		$switched = false;
		$fallback = ! $this->is_language_supported( $language );

		if ( $locale && function_exists( 'switch_to_locale' ) ) {
			$switched = switch_to_locale( $locale );
		}

		if ( '' === $message ) {
			$response = new WP_REST_Response(
				array(
					'error' => $this->translate( 'message_required', $language ),
				),
				400
			);
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}
		if ( strlen( $message ) > self::MAX_MESSAGE_LENGTH ) {
			$response = new WP_REST_Response(
				array(
					'error' => $this->translate( 'message_too_long', $language ),
				),
				400
			);
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}

		$session_key = $this->conversation_manager->get_or_create_session_key();
		$conversation_id = $this->conversation_manager->get_or_create_conversation_id( $session_key );
		$history = $this->conversation_manager->get_recent_messages( $conversation_id, 8 );
		$optimization_options = $this->get_optimization_options();
		$limits      = get_option( 'storeguide_ai_limits_options', array() );
		$daily_limit = isset( $limits['daily_requests'] ) ? absint( $limits['daily_requests'] ) : 0;
		$quota_check = $this->quota_manager->check_and_increment_daily_requests( 'session', $session_key, $daily_limit );
		if ( is_wp_error( $quota_check ) ) {
			$response = new WP_REST_Response(
				array(
					'error' => $quota_check->get_error_message(),
				),
				429
			);
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}

		$cache_hit = $this->get_cached_answer( $message, $optimization_options );
		if ( is_array( $cache_hit ) ) {
			$intent = $this->resolve_intent_label( $message );
			$this->log_chat_event(
				'info',
				'Served response from Q&A cache.',
				array(
					'session_key' => $session_key,
					'query'       => $message,
					'intent'      => $intent,
					'results'     => isset( $cache_hit['results'] ) && is_array( $cache_hit['results'] ) ? count( $cache_hit['results'] ) : 0,
				)
			);
			$this->conversation_manager->add_message( $conversation_id, 'user', $message );
			$this->conversation_manager->add_message( $conversation_id, 'assistant', isset( $cache_hit['message'] ) ? (string) $cache_hit['message'] : '' );
			$response = new WP_REST_Response(
				array(
					'session_key' => $session_key,
					'message'     => isset( $cache_hit['message'] ) ? (string) $cache_hit['message'] : '',
					'results'     => isset( $cache_hit['results'] ) && is_array( $cache_hit['results'] ) ? $cache_hit['results'] : array(),
					'related'     => isset( $cache_hit['related'] ) && is_array( $cache_hit['related'] ) ? $cache_hit['related'] : array(),
					'meta'        => array(
						'count'    => isset( $cache_hit['results'] ) && is_array( $cache_hit['results'] ) ? count( $cache_hit['results'] ) : 0,
						'language' => $language,
						'cached'   => true,
						'intent'   => $intent,
					),
				),
				200
			);
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}

		$manual_faq_answer = $this->get_manual_faq_answer( $message );
		if ( null !== $manual_faq_answer ) {
			$intent = $this->resolve_intent_label( $message );
			$this->log_chat_event(
				'info',
				'Served response from manual FAQ.',
				array(
					'session_key' => $session_key,
					'query'       => $message,
					'intent'      => $intent,
					'results'     => 0,
				)
			);
			$this->conversation_manager->add_message( $conversation_id, 'user', $message );
			$this->conversation_manager->add_message( $conversation_id, 'assistant', $manual_faq_answer );
			$response = new WP_REST_Response(
				array(
					'session_key' => $session_key,
					'message'     => $manual_faq_answer,
					'results'     => array(),
					'related'     => array(),
					'meta'        => array(
						'count'    => 0,
						'language' => $language,
						'source'   => 'manual_faq',
						'intent'   => $intent,
					),
				),
				200
			);
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}

		$is_coupon_query  = $this->is_coupon_related_query( $message );
		$is_product_query = $this->is_product_related_query( $message, $is_coupon_query );
		$is_sale_query    = $this->is_sale_related_query( $message );
		$is_popular_query = $this->is_popular_products_query( $message );
		$is_cheapest_query = $this->is_cheapest_products_query( $message );
		$is_expensive_query = $this->is_most_expensive_products_query( $message );
		$intent_label = $this->resolve_intent_from_flags( $is_product_query, $is_coupon_query, $is_sale_query, $is_popular_query, $is_cheapest_query, $is_expensive_query );
		$max_price_filter = $this->extract_max_price_from_query( $message );
		$widget_options   = get_option( 'storeguide_ai_widget_options', array() );
		$results_limit    = isset( $widget_options['results_limit'] ) ? max( 1, min( 20, absint( $widget_options['results_limit'] ) ) ) : 5;
		$results          = array();
		if ( $is_sale_query ) {
			$results = $this->retriever->get_on_sale_products( $results_limit );
		} elseif ( $is_popular_query ) {
			$results = $this->retriever->search_popular_products( $results_limit );
		} elseif ( $is_cheapest_query ) {
			$results = $this->retriever->search_products_by_price_order( 'asc', $results_limit );
		} elseif ( $is_expensive_query ) {
			$results = $this->retriever->search_products_by_price_order( 'desc', $results_limit );
		} elseif ( $is_product_query ) {
			$results = $this->retriever->search_products( $message, $results_limit );
			if ( empty( $results ) && null !== $max_price_filter ) {
				$results = $this->retriever->search_products_by_max_price( $max_price_filter, $results_limit );
			}
		}
		$coupons_context  = $is_coupon_query ? $this->build_coupon_context( $language ) : '';
		$this->conversation_manager->add_message( $conversation_id, 'user', $message );
		$count_reply = $this->build_product_count_reply_if_needed( $message, $language, $max_price_filter );
		if ( null !== $count_reply ) {
			$this->log_chat_event(
				'info',
				'Answered product count question.',
				array(
					'session_key' => $session_key,
					'query'       => $message,
					'results'     => count( $results ),
				)
			);

			$response = new WP_REST_Response(
				array(
					'session_key' => $session_key,
					'message'     => $count_reply,
					'results'     => $results,
					'meta'        => array(
						'count'    => count( $results ),
						'language' => $language,
						'intent'   => $intent_label,
					),
				),
				200
			);
			$this->conversation_manager->add_message( $conversation_id, 'assistant', $count_reply );
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			return $response;
		}

		$provider_payload = array(
			'system_prompt' => $this->build_system_prompt( $language ),
			'user_prompt'   => $this->build_user_prompt(
				$message,
				$results,
				$language,
				$coupons_context,
				$history,
				$this->build_faq_context( $optimization_options ),
				$this->build_manual_faq_context()
			),
		);
		$provider_reply = $this->provider_manager->generate( $provider_payload );

		if ( is_wp_error( $provider_reply ) ) {
			if ( empty( $results ) ) {
				$response_message = $this->translate( 'no_results', $language );
			} else {
				$response_message = $this->translate( 'top_matches', $language ) . "\n" . $this->format_results_as_lines( $results, $language );
			}
			$response_message .= "\n\n" . $this->translate( 'provider_error', $language );
			$this->log_chat_event(
				'error',
				'Provider generation failed.',
				array(
					'session_key' => $session_key,
					'query'       => $message,
					'error'       => $provider_reply->get_error_message(),
					'results'     => count( $results ),
				)
			);
		} else {
			$response_message = isset( $provider_reply['message'] ) ? (string) $provider_reply['message'] : $this->translate( 'provider_error', $language );
			$this->log_chat_event(
				'info',
				'Provider generation succeeded.',
				array(
					'session_key' => $session_key,
					'query'       => $message,
					'results'     => count( $results ),
				)
			);
		}

		if ( $fallback ) {
			$response_message = $this->translate( 'fallback_notice', $language ) . "\n\n" . $response_message;
		}
		$this->conversation_manager->add_message( $conversation_id, 'assistant', $response_message );
		$related_results = $is_product_query ? $this->get_related_products_for_results( $results, $results_limit ) : array();
		$this->store_cached_answer(
			$message,
			array(
				'message' => $response_message,
				'results' => $results,
				'related' => $related_results,
			),
			$optimization_options
		);

		$response = new WP_REST_Response(
			array(
				'session_key' => $session_key,
				'message'     => $response_message,
				'results'     => $results,
				'related'     => $related_results,
				'meta'        => array(
					'count'    => count( $results ),
					'language' => $language,
					'intent'   => $intent_label,
				),
			),
			200
		);
		if ( $switched && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}
		return $response;
	}

	/**
	 * Resolve related products for top result if enabled.
	 *
	 * @param array<int, array<string, mixed>> $results Results.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_related_products_for_results( $results, $limit = 3 ) {
		$widget = get_option( 'storeguide_ai_widget_options', array() );
		if ( empty( $widget['show_related_products'] ) || empty( $results ) || ! function_exists( 'wc_get_related_products' ) ) {
			return array();
		}

		$base_product_id = isset( $results[0]['product_id'] ) ? (int) $results[0]['product_id'] : 0;
		if ( $base_product_id <= 0 ) {
			return array();
		}

		$related_ids = wc_get_related_products( $base_product_id, max( 1, min( 12, absint( $limit ) ) ) );
		if ( empty( $related_ids ) || ! is_array( $related_ids ) ) {
			return array();
		}

		$output = array();
		foreach ( $related_ids as $related_id ) {
			$product = wc_get_product( $related_id );
			if ( ! $product ) {
				continue;
			}

			$output[] = array(
				'product_id'    => (int) $related_id,
				'title'         => $product->get_name(),
				'product_url'   => get_permalink( (int) $related_id ),
				'price_html'    => $this->format_product_price_text( $product ),
				'thumbnail_url' => get_the_post_thumbnail_url( (int) $related_id, 'thumbnail' ),
				'stock_status'  => $product->get_stock_status(),
			);
		}

		return $output;
	}

	/**
	 * Build provider system prompt.
	 *
	 * @param string $language Language code.
	 * @return string
	 */
	private function build_system_prompt( $language ) {
		$persona  = get_option( 'storeguide_ai_persona_options', array() );
		$business = get_option( 'storeguide_ai_business_options', array() );
		$rules    = get_option( 'storeguide_ai_rules_options', array() );
		$assistant_name = get_option( 'storeguide_ai_options', array() );

		$role      = isset( $persona['role'] ) ? (string) $persona['role'] : 'Product advisor';
		$tone      = isset( $persona['tone'] ) ? (string) $persona['tone'] : 'Professional and friendly';
		$forbidden = isset( $persona['forbidden'] ) ? (string) $persona['forbidden'] : 'Never invent stock, price, or compatibility.';
		$store     = isset( $business['store_name'] ) ? (string) $business['store_name'] : '';
		$assistant = isset( $assistant_name['assistant_name'] ) ? (string) $assistant_name['assistant_name'] : 'StoreGuide Assistant';
		$in_stock_rule = ! empty( $rules['in_stock_only'] ) ? 'true' : 'false';
		$business_context = $this->build_business_context_for_prompt( $business );

		return sprintf(
			'You are %1$s for store "%2$s". Role: %3$s. Tone: %4$s. Always answer in language code "%5$s". Use only provided product context and store profile context. Rule in_stock_only=%6$s. Forbidden behaviors: %7$s. Store profile context: %8$s. When customer asks for contact/legal/location info, prefer exact data from this context.',
			$assistant,
			$store,
			$role,
			$tone,
			$language,
			$in_stock_rule,
			$forbidden,
			$business_context
		);
	}

	/**
	 * Build user prompt including retrieved products.
	 *
	 * @param string                             $message User message.
	 * @param array<int, array<string, mixed>>   $results Retrieved products.
	 * @param string                             $language Language code.
	 * @return string
	 */
	private function build_user_prompt( $message, $results, $language, $coupons_context = '', $history = array(), $faq_context = '', $manual_faq_context = '' ) {
		$knowledge       = $this->retriever->search_knowledge( $message, 3 );
		$product_context = empty( $results )
			? 'No matching indexed products were found.'
			: "Retrieved products:\n" . $this->format_results_as_lines( $results, $language );
		$knowledge_context = empty( $knowledge )
			? 'No matching pages/blog posts were found.'
			: "Retrieved pages/blog posts:\n" . $this->format_knowledge_as_lines( $knowledge );
		$coupon_section = '' !== $coupons_context ? "\n\nCoupons context:\n{$coupons_context}" : '';
		$history_context = empty( $history ) ? '' : "\n\nConversation history:\n" . $this->format_history_as_lines( $history );
		$faq_section = '' !== $faq_context ? "\n\nFrequent questions context:\n{$faq_context}" : '';
		$manual_faq_section = '' !== $manual_faq_context ? "\n\nApproved Q&A knowledge base:\n{$manual_faq_context}" : '';

		$prompt = "Customer question:\n{$message}\n\n{$product_context}\n\n{$knowledge_context}{$coupon_section}{$history_context}{$faq_section}{$manual_faq_section}\n\nGive concise recommendations and mention uncertainty if data is missing.";
		if ( strlen( $prompt ) > self::MAX_PROMPT_LENGTH ) {
			$prompt = substr( $prompt, 0, self::MAX_PROMPT_LENGTH );
		}
		return $prompt;
	}

	/**
	 * Format recent conversation history for prompt.
	 *
	 * @param array<int, array<string, string>> $history History rows.
	 * @return string
	 */
	private function format_history_as_lines( $history ) {
		$lines = array();
		foreach ( $history as $item ) {
			$role = isset( $item['role'] ) ? (string) $item['role'] : 'assistant';
			$text = isset( $item['message'] ) ? (string) $item['message'] : '';
			if ( '' === trim( $text ) ) {
				continue;
			}
			$lines[] = sprintf( '- %s: %s', $role, $text );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Detect whether query is product-related.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_product_related_query( $message, $is_coupon_query = false ) {
		$normalized = strtolower( $message );

		if ( $is_coupon_query ) {
			$explicit_product_with_coupon_markers = array(
				'na produkt',
				'na produkty',
				'dla produktu',
				'for product',
				'for products',
				'na kategorie',
				'for category',
			);
			foreach ( $explicit_product_with_coupon_markers as $marker ) {
				if ( false !== strpos( $normalized, $marker ) ) {
					return true;
				}
			}
			return false;
		}

		$product_markers = array(
			'produkt', 'produkty', 'product', 'products', 'krem', 'koszyk', 'zamow', 'sku', 'cena', 'price',
			'category', 'kategoria', 'wariant', 'variant', 'stock', 'related',
		);
		foreach ( $product_markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detect coupon-related query intent.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_coupon_related_query( $message ) {
		$normalized = strtolower( $message );
		$markers = array( 'kupon', 'kupony', 'kod rabat', 'coupon', 'discount code', 'voucher' );
		foreach ( $markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detect promotions/sale intent for products.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_sale_related_query( $message ) {
		$normalized = strtolower( $message );
		$markers = array( 'promocj', 'promocje', 'przecen', 'wyprzedaz', 'sale', 'on sale', 'obnizk', 'taniej' );
		foreach ( $markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detect "popular products" intent.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_popular_products_query( $message ) {
		$normalized = strtolower( $message );
		$markers = array( 'najpopular', 'popularne', 'popular products', 'bestsellers', 'bestseller' );
		foreach ( $markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detect "cheapest products" intent.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_cheapest_products_query( $message ) {
		$normalized = strtolower( $message );
		$markers = array( 'najtansz', 'najtańsz', 'cheapest', 'lowest price', 'najmniej koszt' );
		foreach ( $markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Detect "most expensive products" intent.
	 *
	 * @param string $message Query.
	 * @return bool
	 */
	private function is_most_expensive_products_query( $message ) {
		$normalized = strtolower( $message );
		$markers = array( 'najdroz', 'najdroż', 'most expensive', 'highest price', 'premium products' );
		foreach ( $markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Resolve high-level intent label from flags.
	 *
	 * @param bool $is_product Product intent.
	 * @param bool $is_coupon Coupon intent.
	 * @param bool $is_sale Sale intent.
	 * @param bool $is_popular Popular intent.
	 * @param bool $is_cheapest Cheapest intent.
	 * @param bool $is_expensive Most expensive intent.
	 * @return string
	 */
	private function resolve_intent_from_flags( $is_product, $is_coupon, $is_sale, $is_popular, $is_cheapest, $is_expensive ) {
		if ( $is_coupon ) {
			return 'coupon';
		}
		if ( $is_sale ) {
			return 'sale';
		}
		if ( $is_popular ) {
			return 'popular';
		}
		if ( $is_cheapest ) {
			return 'cheapest';
		}
		if ( $is_expensive ) {
			return 'most_expensive';
		}
		if ( $is_product ) {
			return 'product';
		}
		return 'general';
	}

	/**
	 * Resolve intent directly from message.
	 *
	 * @param string $message Query text.
	 * @return string
	 */
	private function resolve_intent_label( $message ) {
		$is_coupon    = $this->is_coupon_related_query( $message );
		$is_product   = $this->is_product_related_query( $message, $is_coupon );
		$is_sale      = $this->is_sale_related_query( $message );
		$is_popular   = $this->is_popular_products_query( $message );
		$is_cheapest  = $this->is_cheapest_products_query( $message );
		$is_expensive = $this->is_most_expensive_products_query( $message );
		return $this->resolve_intent_from_flags( $is_product, $is_coupon, $is_sale, $is_popular, $is_cheapest, $is_expensive );
	}

	/**
	 * Extract max-price intent from query text.
	 *
	 * @param string $message Query.
	 * @return float|null
	 */
	private function extract_max_price_from_query( $message ) {
		$normalized = strtolower( $message );
		if ( preg_match( '/(?:under|below|max|do|poni(?:z|ż)ej|mniej niz|less than)\s*(\d+(?:[.,]\d+)?)/u', $normalized, $matches ) ) {
			return (float) str_replace( ',', '.', $matches[1] );
		}
		return null;
	}

	/**
	 * Build clean price text for any product type.
	 *
	 * @param WC_Product $product Product.
	 * @return string|null
	 */
	private function format_product_price_text( $product ) {
		if ( ! is_object( $product ) ) {
			return null;
		}
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {
			$min = (float) $product->get_variation_price( 'min', true );
			$max = (float) $product->get_variation_price( 'max', true );
			if ( $min > 0 && $max > 0 ) {
				$min_text = html_entity_decode( wp_strip_all_tags( wc_price( $min ) ), ENT_QUOTES, 'UTF-8' );
				$max_text = html_entity_decode( wp_strip_all_tags( wc_price( $max ) ), ENT_QUOTES, 'UTF-8' );
				return trim( preg_replace( '/\s+/u', ' ', $min_text ) ) . ' - ' . trim( preg_replace( '/\s+/u', ' ', $max_text ) );
			}
		}
		$price = $product->get_price();
		if ( '' === (string) $price ) {
			return null;
		}
		$price_text = html_entity_decode( wp_strip_all_tags( wc_price( (float) $price ) ), ENT_QUOTES, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', $price_text ) );
	}

	/**
	 * Build coupon context based on configured rules and date window.
	 *
	 * @param string $language Language code.
	 * @return string
	 */
	private function build_coupon_context( $language ) {
		$rules = get_option( 'storeguide_ai_rules_options', array() );
		if ( empty( $rules['coupons_enabled'] ) ) {
			return 'Coupons recommendations are disabled in plugin settings.';
		}

		if ( ! class_exists( 'WC_Coupon' ) ) {
			return 'WooCommerce coupons API unavailable.';
		}

		$recommendations = isset( $rules['coupon_recommendations'] ) && is_array( $rules['coupon_recommendations'] ) ? $rules['coupon_recommendations'] : array();
		$now_ts          = current_time( 'timestamp' );
		$lines = array();
		foreach ( $recommendations as $item ) {
			if ( ! is_array( $item ) || empty( $item['coupon_id'] ) ) {
				continue;
			}
			$coupon_id = absint( $item['coupon_id'] );
			if ( $coupon_id <= 0 ) {
				continue;
			}

			$start_ts = ! empty( $item['start_date'] ) ? strtotime( $item['start_date'] . ' 00:00:00' ) : null;
			$end_ts   = ! empty( $item['end_date'] ) ? strtotime( $item['end_date'] . ' 23:59:59' ) : null;
			if ( null !== $start_ts && $now_ts < $start_ts ) {
				continue;
			}
			if ( null !== $end_ts && $now_ts > $end_ts ) {
				continue;
			}

			$coupon = new WC_Coupon( $coupon_id );
			if ( ! $coupon || ! $coupon->get_id() ) {
				continue;
			}

			$code         = $coupon->get_code();
			$amount       = (float) $coupon->get_amount();
			$discount     = 'percent' === $coupon->get_discount_type() ? $amount . '%' : ( ( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $amount ) ) : (string) $amount ) );
			$expires_obj  = $coupon->get_date_expires();
			$expires_ts   = $expires_obj ? $expires_obj->getTimestamp() : null;
			if ( null !== $expires_ts && $expires_ts < $now_ts ) {
				continue;
			}
			$expires_text = null === $expires_ts ? 'no expiry' : gmdate( 'Y-m-d', $expires_ts );
			$line = sprintf( '- code=%1$s | discount=%2$s | expires=%3$s', $code, $discount, $expires_text );
			if ( ! empty( $item['include_conditions'] ) ) {
				$conditions = $this->build_coupon_conditions_text( $coupon, $language );
				$line      .= ' | conditions=' . $conditions;
			}
			$lines[] = $line;
		}

		if ( empty( $lines ) ) {
			return 'No active coupons in configured recommendations table.';
		}

		return "Use only the coupons below if user asks about discounts.\n" . implode( "\n", $lines );
	}

	/**
	 * Build coupon requirement text.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @param string    $language Language code.
	 * @return string
	 */
	private function build_coupon_conditions_text( $coupon, $language ) {
		$parts = array();
		$min_amount = (float) $coupon->get_minimum_amount();
		if ( $min_amount > 0 ) {
			$parts[] = 'min=' . ( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $min_amount ) ) : (string) $min_amount );
		}
		$parts[] = 'individual_use=' . ( $coupon->get_individual_use() ? 'yes' : 'no' );

		$excluded_products = array_map( 'intval', (array) $coupon->get_excluded_product_ids() );
		$excluded_categories = array_map( 'intval', (array) $coupon->get_excluded_product_categories() );
		if ( ! empty( $excluded_products ) ) {
			$parts[] = 'excluded_products=' . implode( ',', array_slice( $excluded_products, 0, 20 ) );
		}
		if ( ! empty( $excluded_categories ) ) {
			$parts[] = 'excluded_categories=' . implode( ',', array_slice( $excluded_categories, 0, 20 ) );
		}

		return implode( ';', $parts );
	}

	/**
	 * Parse comma-separated integer IDs.
	 *
	 * @param string $value Input string.
	 * @return array<int, int>
	 */
	private function parse_int_list( $value ) {
		$parts = array_map( 'trim', explode( ',', $value ) );
		$ids = array();
		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			$id = absint( $part );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Read optimization settings with safe defaults.
	 *
	 * @return array<string, int>
	 */
	private function get_optimization_options() {
		$raw = get_option( 'storeguide_ai_optimization_options', array() );
		return array(
			'cache_enabled'    => ! empty( $raw['cache_enabled'] ) ? 1 : 0,
			'cache_ttl_minutes'=> isset( $raw['cache_ttl_minutes'] ) ? max( 1, min( 10080, absint( $raw['cache_ttl_minutes'] ) ) ) : 1440,
			'cache_max_entries'=> isset( $raw['cache_max_entries'] ) ? max( 50, min( 20000, absint( $raw['cache_max_entries'] ) ) ) : 1000,
			'faq_enabled'      => ! empty( $raw['faq_enabled'] ) ? 1 : 0,
			'faq_items'        => isset( $raw['faq_items'] ) ? max( 1, min( 50, absint( $raw['faq_items'] ) ) ) : 10,
			'learning_window'  => isset( $raw['learning_window'] ) ? max( 100, min( 50000, absint( $raw['learning_window'] ) ) ) : 1000,
		);
	}

	/**
	 * Normalize query to stable cache key.
	 *
	 * @param string $message Query.
	 * @return string
	 */
	private function normalize_query_key( $message ) {
		$text = function_exists( 'remove_accents' ) ? remove_accents( (string) $message ) : (string) $message;
		$text = strtolower( trim( preg_replace( '/\s+/u', ' ', $text ) ) );
		return md5( $text );
	}

	/**
	 * Get cached answer for identical query.
	 *
	 * @param string            $message Query.
	 * @param array<string,int> $options Optimization options.
	 * @return array<string,mixed>|null
	 */
	private function get_cached_answer( $message, $options ) {
		if ( empty( $options['cache_enabled'] ) ) {
			return null;
		}
		$cache = get_option( self::CACHE_OPTION, array() );
		$key   = $this->normalize_query_key( $message );
		if ( ! isset( $cache[ $key ] ) || ! is_array( $cache[ $key ] ) ) {
			return null;
		}
		$row   = $cache[ $key ];
		$saved = isset( $row['stored_at'] ) ? absint( $row['stored_at'] ) : 0;
		$ttl   = absint( $options['cache_ttl_minutes'] ) * 60;
		if ( $saved <= 0 || ( time() - $saved ) > $ttl ) {
			unset( $cache[ $key ] );
			update_option( self::CACHE_OPTION, $cache );
			return null;
		}
		return $row;
	}

	/**
	 * Persist answer into local Q&A cache.
	 *
	 * @param string                 $message Query.
	 * @param array<string,mixed>    $payload Payload.
	 * @param array<string,int>      $options Optimization options.
	 * @return void
	 */
	private function store_cached_answer( $message, $payload, $options ) {
		if ( empty( $options['cache_enabled'] ) ) {
			return;
		}
		$cache = get_option( self::CACHE_OPTION, array() );
		$key   = $this->normalize_query_key( $message );
		$cached_results = isset( $payload['results'] ) && is_array( $payload['results'] ) ? array_slice( $payload['results'], 0, self::MAX_CACHE_ITEMS_PER_RESPONSE ) : array();
		$cached_related = isset( $payload['related'] ) && is_array( $payload['related'] ) ? array_slice( $payload['related'], 0, self::MAX_CACHE_ITEMS_PER_RESPONSE ) : array();
		$cached_message = isset( $payload['message'] ) ? (string) $payload['message'] : '';
		if ( strlen( $cached_message ) > self::MAX_CACHE_MESSAGE_LENGTH ) {
			$cached_message = substr( $cached_message, 0, self::MAX_CACHE_MESSAGE_LENGTH );
		}
		$cache[ $key ] = array(
			'message'   => $cached_message,
			'results'   => $cached_results,
			'related'   => $cached_related,
			'stored_at' => time(),
		);
		if ( count( $cache ) > absint( $options['cache_max_entries'] ) ) {
			uasort(
				$cache,
				static function ( $a, $b ) {
					$a_time = isset( $a['stored_at'] ) ? absint( $a['stored_at'] ) : 0;
					$b_time = isset( $b['stored_at'] ) ? absint( $b['stored_at'] ) : 0;
					return $a_time <=> $b_time;
				}
			);
			$cache = array_slice( $cache, -absint( $options['cache_max_entries'] ), null, true );
		}
		update_option( self::CACHE_OPTION, $cache );
	}

	/**
	 * Build FAQ context from recent user questions.
	 *
	 * @param array<string,int> $options Optimization options.
	 * @return string
	 */
	private function build_faq_context( $options ) {
		if ( empty( $options['faq_enabled'] ) ) {
			return '';
		}
		global $wpdb;
		$table = $wpdb->prefix . 'storeguide_ai_messages';
		$limit = absint( $options['learning_window'] );
		$faq_items = absint( $options['faq_items'] );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT message_text, COUNT(*) AS cnt
				FROM (
					SELECT LOWER(TRIM(message_text)) AS message_text
					FROM {$table}
					WHERE role = %s AND message_text <> ''
					ORDER BY id DESC
					LIMIT %d
				) recent
				GROUP BY message_text
				ORDER BY cnt DESC
				LIMIT %d",
				'user',
				$limit,
				$faq_items
			),
			ARRAY_A
		);
		if ( empty( $rows ) ) {
			return '';
		}
		$lines = array();
		foreach ( $rows as $row ) {
			$text = isset( $row['message_text'] ) ? (string) $row['message_text'] : '';
			$cnt  = isset( $row['cnt'] ) ? absint( $row['cnt'] ) : 0;
			if ( '' === $text ) {
				continue;
			}
			$lines[] = sprintf( '- "%1$s" (count: %2$d)', $text, $cnt );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Build context from manually curated Q&A.
	 *
	 * @return string
	 */
	private function build_manual_faq_context() {
		$options = get_option( 'storeguide_ai_faq_options', array() );
		$rows    = isset( $options['manual_qa'] ) && is_array( $options['manual_qa'] ) ? $options['manual_qa'] : array();
		if ( empty( $rows ) ) {
			return '';
		}
		$lines = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$question = isset( $row['question'] ) ? trim( (string) $row['question'] ) : '';
			$answer   = isset( $row['answer'] ) ? trim( (string) $row['answer'] ) : '';
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			$lines[] = sprintf( '- Q: %1$s | A: %2$s', $question, $answer );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Resolve manual FAQ answer by exact normalized question.
	 *
	 * @param string $message Query.
	 * @return string|null
	 */
	private function get_manual_faq_answer( $message ) {
		$options = get_option( 'storeguide_ai_faq_options', array() );
		if ( empty( $options['enabled'] ) ) {
			return null;
		}
		$key = $this->normalize_query_key( $message );

		$fixes = isset( $options['suggested_fixes'] ) && is_array( $options['suggested_fixes'] ) ? $options['suggested_fixes'] : array();
		if ( isset( $fixes[ $key ] ) && is_array( $fixes[ $key ] ) && ! empty( $fixes[ $key ]['enabled'] ) && ! empty( $fixes[ $key ]['answer'] ) ) {
			return (string) $fixes[ $key ]['answer'];
		}

		$manual = isset( $options['manual_qa'] ) && is_array( $options['manual_qa'] ) ? $options['manual_qa'] : array();
		foreach ( $manual as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$question = isset( $row['question'] ) ? (string) $row['question'] : '';
			$answer   = isset( $row['answer'] ) ? (string) $row['answer'] : '';
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			if ( $this->normalize_query_key( $question ) === $key ) {
				return $answer;
			}
		}
		return null;
	}

	/**
	 * Format indexed pages/posts for prompt context.
	 *
	 * @param array<int, array<string, mixed>> $knowledge Knowledge rows.
	 * @return string
	 */
	private function format_knowledge_as_lines( $knowledge ) {
		$lines = array();
		foreach ( $knowledge as $item ) {
			$snippet = '';
			if ( ! empty( $item['summary'] ) ) {
				$snippet = (string) $item['summary'];
			} elseif ( ! empty( $item['content_text'] ) ) {
				$snippet = wp_trim_words( (string) $item['content_text'], 24 );
			}

			$lines[] = sprintf(
				'- [%s] %s | %s | %s',
				isset( $item['document_type'] ) ? (string) $item['document_type'] : 'content',
				isset( $item['title'] ) ? (string) $item['title'] : '',
				$snippet,
				isset( $item['permalink'] ) ? (string) $item['permalink'] : ''
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * Build store legal/contact context for prompt.
	 *
	 * @param array<string, mixed> $business Business options.
	 * @return string
	 */
	private function build_business_context_for_prompt( $business ) {
		$context = array(
			'store_name'         => isset( $business['store_name'] ) ? (string) $business['store_name'] : '',
			'store_url'          => isset( $business['store_url'] ) ? (string) $business['store_url'] : '',
			'store_address'      => isset( $business['store_address'] ) ? (string) $business['store_address'] : '',
			'google_maps_url'    => isset( $business['google_maps_url'] ) ? (string) $business['google_maps_url'] : '',
			'support_email'      => isset( $business['support_email'] ) ? (string) $business['support_email'] : '',
			'support_phone'      => isset( $business['support_phone'] ) ? (string) $business['support_phone'] : '',
			'shipping_countries' => isset( $business['shipping_countries'] ) ? (string) $business['shipping_countries'] : '',
			'company_legal_name' => isset( $business['company_legal_name'] ) ? (string) $business['company_legal_name'] : '',
			'company_description'=> isset( $business['company_description'] ) ? (string) $business['company_description'] : '',
			'tax_id'             => isset( $business['tax_id'] ) ? (string) $business['tax_id'] : '',
			'registry_number'    => isset( $business['registry_number'] ) ? (string) $business['registry_number'] : '',
			'contact_page_url'   => isset( $business['contact_page_url'] ) ? (string) $business['contact_page_url'] : '',
			'privacy_policy_url' => isset( $business['privacy_policy_url'] ) ? (string) $business['privacy_policy_url'] : '',
			'terms_page_url'     => isset( $business['terms_page_url'] ) ? (string) $business['terms_page_url'] : '',
			'returns_page_url'   => isset( $business['returns_page_url'] ) ? (string) $business['returns_page_url'] : '',
		);

		$filtered = array_filter(
			$context,
			static function ( $value ) {
				return '' !== trim( (string) $value );
			}
		);

		if ( empty( $filtered ) ) {
			return 'No additional store legal/contact/location data configured.';
		}

		return wp_json_encode( $filtered );
	}

	/**
	 * Format products for prompt and fallback rendering.
	 *
	 * @param array<int, array<string, mixed>> $results Results.
	 * @param string                            $language Language.
	 * @return string
	 */
	private function format_results_as_lines( $results, $language ) {
		$lines = array();
		foreach ( $results as $item ) {
			if ( null !== $item['price'] && function_exists( 'wc_price' ) ) {
				$price_text = wc_price( $item['price'] );
			} elseif ( null !== $item['price'] ) {
				$price_text = (string) $item['price'];
			} else {
				$price_text = $this->translate( 'price_unavailable', $language );
			}

			$lines[] = sprintf(
				'- %s | %s | %s',
				$item['title'],
				$price_text,
				'instock' === $item['stock_status'] ? $this->translate( 'in_stock', $language ) : $this->translate( 'stock_unknown', $language )
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * Resolve locale from short language code.
	 *
	 * @param string $language Language code.
	 * @return string
	 */
	private function resolve_locale_from_language( $language ) {
		$map = array(
			'en' => 'en_US',
			'pl' => 'pl_PL',
			'de' => 'de_DE',
			'fr' => 'fr_FR',
			'es' => 'es_ES',
			'it' => 'it_IT',
			'nl' => 'nl_NL',
			'pt' => 'pt_PT',
			'cs' => 'cs_CZ',
			'sk' => 'sk_SK',
			'ro' => 'ro_RO',
			'hu' => 'hu_HU',
			'uk' => 'uk',
		);

		if ( isset( $map[ $language ] ) ) {
			return $map[ $language ];
		}

		return function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	}

	/**
	 * Check if language has direct message map support.
	 *
	 * @param string $language Language code.
	 * @return bool
	 */
	private function is_language_supported( $language ) {
		$supported = array( 'en', 'pl', 'de', 'fr', 'es' );
		return in_array( $language, $supported, true );
	}

	/**
	 * Build answer for "how many products" intent.
	 *
	 * @param string $message User message.
	 * @param string     $language Language.
	 * @param float|null $max_price_filter Optional max price filter from query.
	 * @return string|null
	 */
	private function build_product_count_reply_if_needed( $message, $language, $max_price_filter = null ) {
		$normalized = strtolower( $message );
		$triggers = array(
			'how many products',
			'number of products',
			'ile jest produkt',
			'ile produktow',
			'liczba produkt',
		);
		$matched = false;
		foreach ( $triggers as $trigger ) {
			if ( false !== strpos( $normalized, $trigger ) ) {
				$matched = true;
				break;
			}
		}
		if ( ! $matched ) {
			return null;
		}

		global $wpdb;
		$documents_table = $wpdb->prefix . 'storeguide_ai_documents';
		$count           = 0;

		if ( null !== $max_price_filter && $max_price_filter > 0 ) {
			$meta_table = $wpdb->prefix . 'storeguide_ai_document_meta';
			$count      = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$documents_table} d
					INNER JOIN {$meta_table} m ON m.document_id = d.id
					WHERE d.document_type = %s
					  AND d.status = %s
					  AND m.price IS NOT NULL
					  AND m.price <= %f",
					'product',
					'active',
					(float) $max_price_filter
				)
			);
		} else {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$documents_table} WHERE document_type = %s AND status = %s",
					'product',
					'active'
				)
			);
		}

		if ( 'pl' === $language ) {
			if ( null !== $max_price_filter && $max_price_filter > 0 ) {
				return sprintf( 'W indeksie mam %1$d produktow w cenie do %2$s zl.', $count, rtrim( rtrim( number_format( (float) $max_price_filter, 2, '.', '' ), '0' ), '.' ) );
			}
			return sprintf( 'Aktualnie w indeksie mam %d produktow sklepu.', $count );
		}
		if ( 'de' === $language ) {
			if ( null !== $max_price_filter && $max_price_filter > 0 ) {
				return sprintf( 'Im Index habe ich %1$d Produkte bis %2$s PLN.', $count, rtrim( rtrim( number_format( (float) $max_price_filter, 2, '.', '' ), '0' ), '.' ) );
			}
			return sprintf( 'Aktuell sind %d Produkte im Shop-Index vorhanden.', $count );
		}
		if ( 'fr' === $language ) {
			if ( null !== $max_price_filter && $max_price_filter > 0 ) {
				return sprintf( 'L index contient %1$d produits jusqu a %2$s PLN.', $count, rtrim( rtrim( number_format( (float) $max_price_filter, 2, '.', '' ), '0' ), '.' ) );
			}
			return sprintf( 'Actuellement, l index contient %d produits de la boutique.', $count );
		}
		if ( 'es' === $language ) {
			if ( null !== $max_price_filter && $max_price_filter > 0 ) {
				return sprintf( 'En el indice hay %1$d productos hasta %2$s PLN.', $count, rtrim( rtrim( number_format( (float) $max_price_filter, 2, '.', '' ), '0' ), '.' ) );
			}
			return sprintf( 'Actualmente hay %d productos en el indice de la tienda.', $count );
		}
		if ( null !== $max_price_filter && $max_price_filter > 0 ) {
			return sprintf( 'There are %1$d products priced up to %2$s PLN in the index.', $count, rtrim( rtrim( number_format( (float) $max_price_filter, 2, '.', '' ), '0' ), '.' ) );
		}
		return sprintf( 'There are currently %d products in the store index.', $count );
	}

	/**
	 * Persist chat event into plugin log table.
	 *
	 * @param string               $level Log level.
	 * @param string               $message Message.
	 * @param array<string, mixed> $details Details.
	 * @return void
	 */
	private function log_chat_event( $level, $message, $details ) {
		global $wpdb;
		$table = $wpdb->prefix . 'storeguide_ai_logs';
		$safe_message = sanitize_text_field( (string) $message );
		if ( strlen( $safe_message ) > self::MAX_LOG_DETAILS_LENGTH ) {
			$safe_message = substr( $safe_message, 0, self::MAX_LOG_DETAILS_LENGTH );
		}
		$safe_details = $this->sanitize_log_details( is_array( $details ) ? $details : array() );
		$wpdb->insert(
			$table,
			array(
				'log_type'    => 'conversation',
				'level'       => sanitize_key( $level ),
				'context_key' => isset( $safe_details['session_key'] ) ? sanitize_text_field( (string) $safe_details['session_key'] ) : '',
				'message'     => $safe_message,
				'details_json'=> wp_json_encode( $safe_details ),
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Sanitize log details and cap payload size.
	 *
	 * @param array<string, mixed> $details Details.
	 * @return array<string, mixed>
	 */
	private function sanitize_log_details( $details ) {
		$sanitized = array();
		$counter   = 0;
		foreach ( $details as $key => $value ) {
			$counter++;
			if ( $counter > 40 ) {
				break;
			}
			$safe_key = sanitize_key( (string) $key );
			if ( '' === $safe_key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$sanitized[ $safe_key ] = $this->sanitize_log_details( $value );
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$text = sanitize_text_field( (string) $value );
				if ( strlen( $text ) > self::MAX_LOG_DETAILS_LENGTH ) {
					$text = substr( $text, 0, self::MAX_LOG_DETAILS_LENGTH );
				}
				$sanitized[ $safe_key ] = $text;
			}
		}
		return $sanitized;
	}

	/**
	 * Translate runtime message by language code.
	 *
	 * @param string $key Message key.
	 * @param string $language Language code.
	 * @return string
	 */
	private function translate( $key, $language ) {
		$messages = array(
			'en' => array(
				'message_required' => 'Message is required.',
				'message_too_long' => 'Message is too long. Maximum 1200 characters.',
				'no_results' => 'I could not find matching products in the current index. Try adding details like brand, model, budget, or key feature.',
				'price_unavailable' => 'price unavailable',
				'in_stock' => 'in stock',
				'stock_unknown' => 'stock unknown',
				'top_matches' => 'Top matching products:',
				'fallback_notice' => 'Language support is limited right now, so I am replying in English.',
				'provider_error' => 'AI provider is not available right now. Showing retrieval-based suggestions.',
			),
			'pl' => array(
				'message_required' => 'Wiadomosc jest wymagana.',
				'message_too_long' => 'Wiadomosc jest za dluga. Maksimum 1200 znakow.',
				'no_results' => 'Nie znaleziono pasujacych produktow w aktualnym indeksie. Sprobuj dodac marke, model, budzet lub kluczowa ceche.',
				'price_unavailable' => 'cena niedostepna',
				'in_stock' => 'w magazynie',
				'stock_unknown' => 'stan nieznany',
				'top_matches' => 'Najlepiej dopasowane produkty:',
				'fallback_notice' => 'Obsluga tego jezyka jest jeszcze ograniczona, wiec odpowiadam po angielsku.',
				'provider_error' => 'Dostawca AI jest chwilowo niedostepny. Pokazuje sugestie oparte na indeksie.',
			),
			'de' => array(
				'message_required' => 'Nachricht ist erforderlich.',
				'message_too_long' => 'Nachricht ist zu lang. Maximum 1200 Zeichen.',
				'no_results' => 'Im aktuellen Index wurden keine passenden Produkte gefunden. Versuche Marke, Modell, Budget oder ein wichtiges Merkmal anzugeben.',
				'price_unavailable' => 'Preis nicht verfugbar',
				'in_stock' => 'auf Lager',
				'stock_unknown' => 'Bestand unbekannt',
				'top_matches' => 'Top passende Produkte:',
				'fallback_notice' => 'Die Sprachunterstutzung ist derzeit begrenzt, deshalb antworte ich auf Englisch.',
				'provider_error' => 'Der AI-Anbieter ist derzeit nicht verfugbar. Ich zeige indexbasierte Vorschlage.',
			),
			'fr' => array(
				'message_required' => 'Le message est obligatoire.',
				'message_too_long' => 'Le message est trop long. Maximum 1200 caracteres.',
				'no_results' => 'Je n ai trouve aucun produit correspondant dans l index actuel. Essayez d ajouter la marque, le modele, le budget ou une caracteristique cle.',
				'price_unavailable' => 'prix indisponible',
				'in_stock' => 'en stock',
				'stock_unknown' => 'stock inconnu',
				'top_matches' => 'Produits les plus pertinents :',
				'fallback_notice' => 'La prise en charge de cette langue est limitee, je reponds donc en anglais.',
				'provider_error' => 'Le fournisseur IA est momentanement indisponible. Suggestions basees sur l index affichees.',
			),
			'es' => array(
				'message_required' => 'El mensaje es obligatorio.',
				'message_too_long' => 'El mensaje es demasiado largo. Maximo 1200 caracteres.',
				'no_results' => 'No encontre productos coincidentes en el indice actual. Intenta anadir marca, modelo, presupuesto o una caracteristica clave.',
				'price_unavailable' => 'precio no disponible',
				'in_stock' => 'en stock',
				'stock_unknown' => 'stock desconocido',
				'top_matches' => 'Productos mejor coincidentes:',
				'fallback_notice' => 'La compatibilidad con este idioma es limitada por ahora, por eso respondo en ingles.',
				'provider_error' => 'El proveedor de IA no esta disponible ahora. Mostrando sugerencias basadas en el indice.',
			),
		);

		if ( isset( $messages[ $language ][ $key ] ) ) {
			return $messages[ $language ][ $key ];
		}

		return $messages['en'][ $key ];
	}
}
