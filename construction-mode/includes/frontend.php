<?php

/**
 * Frontend functionality for Construction Mode
 *
 * @package ConstructionMode
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Intercept login page requests
 */
function cm_intercept_login_page()
{
	// Check if construction mode is enabled
	if (! cm_is_enabled()) {
		return;
	}

	$options = cm_get_options();

	// Check if disable login is enabled
	if (empty($options['disable_login'])) {
		return;
	}

	// Allow administrators to access login page
	if (current_user_can('manage_options')) {
		return;
	}

	// Check for secret bypass parameter first (before checking cookie)
	cm_maybe_set_bypass_cookie_from_query();

	// Check if request is bypassed
	if (cm_is_bypassed()) {
		return;
	}

	// Show construction page
	cm_render_page_and_exit();
}
add_action('login_init', 'cm_intercept_login_page', 1);

/**
 * Intercept frontend requests and show construction page
 */
function cm_template_redirect()
{
	// Don't affect admin area
	if (is_admin()) {
		return;
	}

	// Don't affect AJAX requests (but still check bypass)
	if (wp_doing_ajax()) {
		return;
	}

	// Don't affect REST API (but still check bypass)
	if (defined('REST_REQUEST') && REST_REQUEST) {
		return;
	}

	// Check if construction mode is enabled
	if (! cm_is_enabled()) {
		return;
	}

	// Check for secret bypass parameter first (before checking cookie)
	cm_maybe_set_bypass_cookie_from_query();

	// Check if request is bypassed
	if (cm_is_bypassed()) {
		return;
	}

	// Show construction page
	cm_render_page_and_exit();
}
add_action('template_redirect', 'cm_template_redirect', 1);

/**
 * Add robots directives when construction mode is enabled
 *
 * @param array $robots Associative array of robots directives
 * @return array
 */
function cm_wp_robots($robots)
{
	if (! cm_is_enabled()) {
		return $robots;
	}

	$options = cm_get_options();
	if (! empty($options['noindex'])) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}
add_filter('wp_robots', 'cm_wp_robots');
