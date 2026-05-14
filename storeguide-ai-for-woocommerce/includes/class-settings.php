<?php
/**
 * Plugin settings registration.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Settings {
	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register() {
		register_setting(
			'storeguide_ai_general',
			'storeguide_ai_options',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'storeguide_ai_general_section',
			esc_html__( 'General', 'storeguide-ai' ),
			array( $this, 'render_general_section' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG
		);

		add_settings_field(
			'assistant_name',
			esc_html__( 'Assistant Name', 'storeguide-ai' ),
			array( $this, 'render_assistant_name' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG,
			'storeguide_ai_general_section'
		);

		add_settings_field(
			'enabled',
			esc_html__( 'Enable Plugin', 'storeguide-ai' ),
			array( $this, 'render_enabled' ),
			StoreGuide_AI_Admin_Menu::MENU_SLUG,
			'storeguide_ai_general_section'
		);
	}

	/**
	 * Sanitize values.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$values                     = array();
		$values['assistant_name']   = isset( $input['assistant_name'] ) ? sanitize_text_field( wp_unslash( $input['assistant_name'] ) ) : '';
		$values['enabled']          = isset( $input['enabled'] ) ? 1 : 0;
		return $values;
	}

	/**
	 * Section intro.
	 *
	 * @return void
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure core behavior for StoreGuide AI.', 'storeguide-ai' ) . '</p>';
	}

	/**
	 * Assistant name field.
	 *
	 * @return void
	 */
	public function render_assistant_name() {
		$options = get_option( 'storeguide_ai_options', array() );
		$value   = isset( $options['assistant_name'] ) ? (string) $options['assistant_name'] : esc_html__( 'StoreGuide Assistant', 'storeguide-ai' );
		?>
		<input type="text" class="regular-text" name="storeguide_ai_options[assistant_name]" value="<?php echo esc_attr( $value ); ?>"/>
		<p class="description"><?php echo esc_html__( 'Visible name of your storefront assistant.', 'storeguide-ai' ); ?></p>
		<?php
	}

	/**
	 * Enabled field.
	 *
	 * @return void
	 */
	public function render_enabled() {
		$options = get_option( 'storeguide_ai_options', array() );
		$checked = ! empty( $options['enabled'] );
		?>
		<label for="storeguide-ai-enabled">
			<input id="storeguide-ai-enabled" type="checkbox" name="storeguide_ai_options[enabled]" value="1" <?php checked( $checked ); ?> />
			<?php echo esc_html__( 'Enable StoreGuide AI frontend and admin integrations.', 'storeguide-ai' ); ?>
		</label>
		<?php
	}
}
