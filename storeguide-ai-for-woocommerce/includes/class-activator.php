<?php
/**
 * Plugin activation.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/database/class-schema.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/database/class-migrations.php';
require_once STOREGUIDE_AI_PLUGIN_DIR . 'includes/class-index-builder.php';

class StoreGuide_AI_Activator {
	/**
	 * Activation handler.
	 *
	 * @return void
	 */
	public static function activate() {
		$migrations = new StoreGuide_AI_Migrations( new StoreGuide_AI_Schema() );
		$migrations->maybe_migrate();

		if ( false === get_option( 'storeguide_ai_dev_options', false ) ) {
			add_option(
				'storeguide_ai_dev_options',
				array(
					'debug_enabled' => 0,
					'asset_version' => STOREGUIDE_AI_VERSION,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_options', false ) ) {
			add_option(
				'storeguide_ai_options',
				array(
					'assistant_name' => 'StoreGuide Assistant',
					'enabled'        => 1,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_widget_options', false ) ) {
			add_option(
				'storeguide_ai_widget_options',
				array(
					'position'              => 'bottom-right',
					'welcome'               => 'Hi! I can help you choose a product.',
					'placeholder'           => 'What are you looking for?',
					'results_limit'         => 5,
					'button_text'           => 'Ask StoreGuide AI',
					'button_icon'           => '💬',
					'button_bg_color'       => '#2271b1',
					'button_text_color'     => '#ffffff',
					'button_radius'         => 20,
					'button_font_size'      => 14,
					'send_button_text'      => 'Send',
					'send_button_bg_color'  => '#2271b1',
					'send_button_text_color'=> '#ffffff',
					'send_button_radius'    => 8,
					'send_button_font_size' => 13,
					'custom_css'            => '',
					'chat_theme'            => 'light',
					'result_fields'         => array( 'thumbnail', 'name', 'price', 'link' ),
					'show_related_products' => 1,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_persona_options', false ) ) {
			add_option(
				'storeguide_ai_persona_options',
				array(
					'role'      => 'Product advisor',
					'tone'      => 'Professional and friendly',
					'length'    => 'short',
					'forbidden' => 'Never invent stock, price, or compatibility.',
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_provider_options', false ) ) {
			add_option(
				'storeguide_ai_provider_options',
				array(
					'provider'    => 'openai',
					'api_key'     => '',
					'base_url'    => '',
					'model'       => 'gpt-4.1-mini',
					'temperature' => 0.3,
					'max_tokens'  => 800,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_index_options', false ) ) {
			add_option(
				'storeguide_ai_index_options',
				array(
					'autosync'          => 1,
					'background_worker' => 1,
					'batch_size'        => 100,
					'sources'           => 'products,pages,posts',
					'semantic_retrieval_enabled' => 0,
					'semantic_top_k'    => 5,
					'pinecone_host'     => '',
					'pinecone_api_key'  => '',
					'pinecone_namespace'=> '',
					'embedding_model'   => 'text-embedding-3-small',
					'embedding_api_key' => '',
					'acf_enabled'       => 0,
					'acf_keys'          => '',
					'acf_auto_detect'  => 0,
					'content_meta_enabled' => 0,
					'content_meta_keys'    => '',
					'content_meta_auto_detect' => 0,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_limits_options', false ) ) {
			add_option(
				'storeguide_ai_limits_options',
				array(
					'daily_requests'   => 500,
					'monthly_requests' => 10000,
					'daily_cost'       => 10,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_optimization_options', false ) ) {
			add_option(
				'storeguide_ai_optimization_options',
				array(
					'cache_enabled'      => 1,
					'cache_ttl_minutes'  => 1440,
					'cache_max_entries'  => 1000,
					'faq_enabled'        => 1,
					'faq_items'          => 10,
					'learning_window'    => 1000,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_faq_options', false ) ) {
			add_option(
				'storeguide_ai_faq_options',
				array(
					'enabled'           => 1,
					'manual_qa'         => array(),
					'suggested_fixes'   => array(),
					'learning_review_limit' => 1000,
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_business_options', false ) ) {
			add_option(
				'storeguide_ai_business_options',
				array(
					'store_name'         => get_bloginfo( 'name' ),
					'store_url'          => home_url( '/' ),
					'store_address'      => '',
					'google_maps_url'    => '',
					'support_email'      => get_option( 'admin_email', '' ),
					'support_phone'      => '',
					'shipping_countries' => '',
					'company_description'=> '',
					'company_legal_name' => '',
					'tax_id'             => '',
					'registry_number'    => '',
					'contact_page_url'   => '',
					'privacy_policy_url' => get_privacy_policy_url(),
					'terms_page_url'     => '',
					'returns_page_url'   => '',
				)
			);
		}

		if ( false === get_option( 'storeguide_ai_rules_options', false ) ) {
			add_option(
				'storeguide_ai_rules_options',
				array(
					'in_stock_only'       => 0,
					'excluded_categories' => '',
					'promoted_products'   => '',
					'coupons_enabled'     => 1,
					'coupon_ids'          => '',
					'coupon_window_days'  => 30,
					'coupon_recommendations' => array(),
				)
			);
		}

		$builder = new StoreGuide_AI_Index_Builder();
		if ( function_exists( 'wc_get_product' ) ) {
			$builder->index_products_batch( 50 );
		}
		$builder->index_content_batch( 100, array( 'page', 'post' ) );
	}
}
