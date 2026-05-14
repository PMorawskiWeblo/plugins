<?php
/**
 * Developer settings.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Developer_Settings {
	/**
	 * Register developer section.
	 *
	 * @return void
	 */
	public function register() {
		register_setting(
			'storeguide_ai_general',
			'storeguide_ai_dev_options',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'storeguide_ai_developer_section',
			esc_html__( 'Developer', 'storeguide-ai' ),
			array( $this, 'render_section' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG
		);

		add_settings_field(
			'debug_enabled',
			esc_html__( 'Enable Debug Logging', 'storeguide-ai' ),
			array( $this, 'render_debug_enabled' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG,
			'storeguide_ai_developer_section'
		);

		add_settings_field(
			'asset_version',
			esc_html__( 'Asset Version', 'storeguide-ai' ),
			array( $this, 'render_asset_version' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG,
			'storeguide_ai_developer_section'
		);
	}

	/**
	 * Sanitize developer values.
	 *
	 * @param array<string, mixed> $input Input values.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$values                  = array();
		$values['debug_enabled'] = isset( $input['debug_enabled'] ) ? 1 : 0;
		$values['asset_version'] = isset( $input['asset_version'] ) ? sanitize_text_field( wp_unslash( $input['asset_version'] ) ) : STOREGUIDE_AI_VERSION;
		return $values;
	}

	/**
	 * Section intro.
	 *
	 * @return void
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'Controls intended for development, diagnostics, and cache management.', 'storeguide-ai' ) . '</p>';
	}

	/**
	 * Render debug switch.
	 *
	 * @return void
	 */
	public function render_debug_enabled() {
		$options = get_option( 'storeguide_ai_dev_options', array() );
		$checked = ! empty( $options['debug_enabled'] );
		?>
		<label for="storeguide-ai-debug">
			<input id="storeguide-ai-debug" type="checkbox" name="storeguide_ai_dev_options[debug_enabled]" value="1" <?php checked( $checked ); ?> />
			<?php echo esc_html__( 'Store logs for troubleshooting.', 'storeguide-ai' ); ?>
		</label>
		<?php
	}

	/**
	 * Render asset version.
	 *
	 * @return void
	 */
	public function render_asset_version() {
		$options = get_option( 'storeguide_ai_dev_options', array() );
		$value   = isset( $options['asset_version'] ) ? (string) $options['asset_version'] : STOREGUIDE_AI_VERSION;
		?>
		<input type="text" class="regular-text" name="storeguide_ai_dev_options[asset_version]" value="<?php echo esc_attr( $value ); ?>"/>
		<p class="description"><?php echo esc_html__( 'Increase this value to force CSS/JS cache refresh.', 'storeguide-ai' ); ?></p>
		<?php
	}
}
