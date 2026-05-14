<?php
/**
 * Uninstall script for StoreGuide AI.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'storeguide_ai_options' );
delete_option( 'storeguide_ai_dev_options' );
delete_option( 'storeguide_ai_widget_options' );
delete_option( 'storeguide_ai_persona_options' );
delete_option( 'storeguide_ai_provider_options' );
delete_option( 'storeguide_ai_index_options' );
delete_option( 'storeguide_ai_limits_options' );
delete_option( 'storeguide_ai_business_options' );
delete_option( 'storeguide_ai_rules_options' );
delete_option( 'storeguide_ai_optimization_options' );
delete_option( 'storeguide_ai_qa_cache' );
delete_option( 'storeguide_ai_faq_options' );
delete_option( 'storeguide_ai_index_progress' );
delete_option( 'storeguide_ai_index_status' );
