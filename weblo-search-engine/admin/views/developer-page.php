<?php
/**
 * Developer page view.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Handle form submission.
if ( isset( $_POST['submit'] ) && check_admin_referer( 'weblo_search_developer' ) ) {
	$dev_mode = isset( $_POST['weblo_search_dev_mode'] ) ? '1' : '0';
	update_option( 'weblo_search_dev_mode', $dev_mode );
	
	// Handle assets version.
	$assets_version = isset( $_POST['weblo_search_assets_version'] ) ? sanitize_text_field( $_POST['weblo_search_assets_version'] ) : '';
	if ( empty( $assets_version ) ) {
		$assets_version = WEBLO_SEARCH_ENGINE_VERSION;
	}
	update_option( 'weblo_search_assets_version', $assets_version );
	
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'weblo-search-engine' ) . '</p></div>';
}

$dev_mode = get_option( 'weblo_search_dev_mode', '0' );
$assets_version = get_option( 'weblo_search_assets_version', WEBLO_SEARCH_ENGINE_VERSION );
$default_hidden = get_option( 'weblo_search_default_hidden', '1' );
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="weblo-developer-info">
		<h2><?php esc_html_e( 'Shortcodes', 'weblo-search-engine' ); ?></h2>
		
		<h3><?php esc_html_e( 'Full Search Engine', 'weblo-search-engine' ); ?></h3>
		<p><?php esc_html_e( 'Use the following shortcode to display the complete search engine:', 'weblo-search-engine' ); ?></p>
		<code>[weblo_search_engine default_hidden="<?php echo esc_attr( $default_hidden ); ?>"]</code>
		<p><?php esc_html_e( 'You can add multiple independent search engines using the class parameter:', 'weblo-search-engine' ); ?></p>
		<code>[weblo_search_engine default_hidden="0" class="my-custom-search"]</code>
		
		<h3><?php esc_html_e( 'Input Only', 'weblo-search-engine' ); ?></h3>
		<p><?php esc_html_e( 'Display only the search input field and no-results message:', 'weblo-search-engine' ); ?></p>
		<code>[weblo_search_input instance="weblo-search-1"]</code>
		<p class="description"><?php esc_html_e( 'Use the instance parameter to specify which search engine this input belongs to. Default: weblo-search-1', 'weblo-search-engine' ); ?></p>
		
		<h3><?php esc_html_e( 'Results Only', 'weblo-search-engine' ); ?></h3>
		<p><?php esc_html_e( 'Display only the products results section:', 'weblo-search-engine' ); ?></p>
		<code>[weblo_search_results instance="weblo-search-1"]</code>
		<p class="description"><?php esc_html_e( 'Use the instance parameter to specify which search engine these results belong to. Must match the instance ID used in the input shortcode.', 'weblo-search-engine' ); ?></p>
		
		<h3><?php esc_html_e( 'Example: Split Layout', 'weblo-search-engine' ); ?></h3>
		<pre><code>// In header or sidebar:
[weblo_search_input instance="weblo-search-1"]

// In content area:
[weblo_search_results instance="weblo-search-1"]</code></pre>
		<p class="description"><?php esc_html_e( 'This allows you to place the input in one location (e.g., header) and results in another (e.g., main content area). Both must use the same instance ID.', 'weblo-search-engine' ); ?></p>
		
		<h2><?php esc_html_e( 'Developer Mode', 'weblo-search-engine' ); ?></h2>
		<p><?php esc_html_e( 'Enable developer mode to add timestamps to CSS/JS assets for easier development and debugging.', 'weblo-search-engine' ); ?></p>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'weblo_search_developer' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="weblo_search_dev_mode"><?php esc_html_e( 'Enable Dev Mode', 'weblo-search-engine' ); ?></label>
					</th>
					<td>
						<input 
							type="checkbox" 
							id="weblo_search_dev_mode" 
							name="weblo_search_dev_mode" 
							value="1" 
							<?php checked( $dev_mode, '1' ); ?>
						/>
						<label for="weblo_search_dev_mode"><?php esc_html_e( 'Enable developer mode (adds timestamp to asset URLs)', 'weblo-search-engine' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="weblo_search_assets_version"><?php esc_html_e( 'Assets Version', 'weblo-search-engine' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input 
							type="text" 
							id="weblo_search_assets_version" 
							name="weblo_search_assets_version" 
							value="<?php echo esc_attr( $assets_version ); ?>" 
							class="regular-text" 
							required
						/>
						<p class="description"><?php esc_html_e( 'Version number used for CSS/JS assets cache busting. Default: plugin version.', 'weblo-search-engine' ); ?></p>
					</td>
				</tr>
			</table>
			
			<?php submit_button(); ?>
		</form>
	</div>
</div>

