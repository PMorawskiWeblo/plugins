<?php
/**
 * Frontend widget renderer.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Widget_Renderer {
	/**
	 * Render chat widget container.
	 *
	 * @return void
	 */
	public function render() {
		$options = get_option( 'storeguide_ai_options', array() );
		$widget  = get_option( 'storeguide_ai_widget_options', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$language       = $this->resolve_current_language();
		$defaults       = $this->get_default_widget_texts( $language );
		$assistant_name = ! empty( $options['assistant_name'] ) ? (string) $options['assistant_name'] : __( 'StoreGuide Assistant', 'storeguide-ai' );
		$position       = ! empty( $widget['position'] ) ? sanitize_key( $widget['position'] ) : 'bottom-right';
		$welcome        = ! empty( $widget['welcome'] ) ? sanitize_text_field( $widget['welcome'] ) : $defaults['welcome'];
		$placeholder    = ! empty( $widget['placeholder'] ) ? sanitize_text_field( $widget['placeholder'] ) : $defaults['placeholder'];
		$button_text    = ! empty( $widget['button_text'] ) ? sanitize_text_field( $widget['button_text'] ) : __( 'Ask StoreGuide AI', 'storeguide-ai' );
		$button_icon    = isset( $widget['button_icon'] ) ? sanitize_text_field( (string) $widget['button_icon'] ) : '💬';
		$button_bg      = ! empty( $widget['button_bg_color'] ) ? sanitize_hex_color( $widget['button_bg_color'] ) : '#2271b1';
		$button_color   = ! empty( $widget['button_text_color'] ) ? sanitize_hex_color( $widget['button_text_color'] ) : '#ffffff';
		$button_radius  = isset( $widget['button_radius'] ) ? max( 0, min( 40, absint( $widget['button_radius'] ) ) ) : 20;
		$button_font    = isset( $widget['button_font_size'] ) ? max( 10, min( 30, absint( $widget['button_font_size'] ) ) ) : 14;
		$send_text      = ! empty( $widget['send_button_text'] ) ? sanitize_text_field( $widget['send_button_text'] ) : __( 'Send', 'storeguide-ai' );
		$send_bg        = ! empty( $widget['send_button_bg_color'] ) ? sanitize_hex_color( $widget['send_button_bg_color'] ) : '#2271b1';
		$send_color     = ! empty( $widget['send_button_text_color'] ) ? sanitize_hex_color( $widget['send_button_text_color'] ) : '#ffffff';
		$send_radius    = isset( $widget['send_button_radius'] ) ? max( 0, min( 30, absint( $widget['send_button_radius'] ) ) ) : 8;
		$send_font      = isset( $widget['send_button_font_size'] ) ? max( 10, min( 24, absint( $widget['send_button_font_size'] ) ) ) : 13;
		$chat_theme     = ! empty( $widget['chat_theme'] ) && in_array( $widget['chat_theme'], array( 'light', 'dark' ), true ) ? $widget['chat_theme'] : 'light';
		$wrapper_style  = sprintf(
			'--sg-toggle-bg:%1$s;--sg-toggle-text:%2$s;--sg-toggle-radius:%3$dpx;--sg-toggle-font-size:%4$dpx;--sg-send-bg:%5$s;--sg-send-text:%6$s;--sg-send-radius:%7$dpx;--sg-send-font-size:%8$dpx;',
			$button_bg ? $button_bg : '#2271b1',
			$button_color ? $button_color : '#ffffff',
			$button_radius,
			$button_font,
			$send_bg ? $send_bg : '#2271b1',
			$send_color ? $send_color : '#ffffff',
			$send_radius,
			$send_font
		);
		?>
		<div id="storeguide-ai-widget" class="storeguide-ai-widget <?php echo esc_attr( 'storeguide-ai-' . $position ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>" data-endpoint="<?php echo esc_url( rest_url( 'storeguide-ai/v1/chat' ) ); ?>">
			<button type="button" class="storeguide-ai-toggle" aria-expanded="false" aria-controls="storeguide-ai-panel">
				<?php if ( '' !== $button_icon ) : ?>
					<span class="storeguide-ai-toggle-icon"><?php echo esc_html( $button_icon ); ?></span>
				<?php endif; ?>
				<span class="storeguide-ai-toggle-text"><?php echo esc_html( $button_text ); ?></span>
			</button>
			<div id="storeguide-ai-panel" class="storeguide-ai-panel <?php echo esc_attr( 'storeguide-ai-theme-' . $chat_theme ); ?>" hidden>
				<div class="storeguide-ai-header">
					<strong><?php echo esc_html( $assistant_name ); ?></strong>
				</div>
				<div class="storeguide-ai-messages" aria-live="polite"><p class="storeguide-ai-message storeguide-ai-message-assistant"><?php echo esc_html( $welcome ); ?></p></div>
				<form class="storeguide-ai-form">
					<label class="screen-reader-text" for="storeguide-ai-input"><?php echo esc_html__( 'Type your question', 'storeguide-ai' ); ?></label>
					<input id="storeguide-ai-input" name="message" type="text" maxlength="1200" placeholder="<?php echo esc_attr( $placeholder ); ?>" required />
					<button type="submit"><?php echo esc_html( $send_text ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Resolve active language code.
	 *
	 * @return string
	 */
	private function resolve_current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$pll_lang = pll_current_language( 'slug' );
			if ( is_string( $pll_lang ) && '' !== $pll_lang ) {
				return sanitize_key( strtolower( $pll_lang ) );
			}
		}

		$wpml_lang = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml_lang ) && '' !== $wpml_lang ) {
			return sanitize_key( strtolower( $wpml_lang ) );
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		if ( ! is_string( $locale ) || '' === $locale ) {
			return 'en';
		}

		$parts = explode( '_', $locale );
		return sanitize_key( strtolower( $parts[0] ) );
	}

	/**
	 * Return language-aware default widget texts.
	 *
	 * @param string $language Language code.
	 * @return array<string, string>
	 */
	private function get_default_widget_texts( $language ) {
		$texts = array(
			'en' => array(
				'welcome'     => 'Hi! I can help you choose a product.',
				'placeholder' => 'What product are you looking for?',
			),
			'pl' => array(
				'welcome'     => 'Czesc! Pomoge Ci wybrac najlepszy produkt.',
				'placeholder' => 'Jakiego produktu szukasz?',
			),
			'de' => array(
				'welcome'     => 'Hallo! Ich helfe dir bei der Produktauswahl.',
				'placeholder' => 'Nach welchem Produkt suchst du?',
			),
			'fr' => array(
				'welcome'     => 'Bonjour ! Je peux vous aider a choisir un produit.',
				'placeholder' => 'Quel produit recherchez-vous ?',
			),
			'es' => array(
				'welcome'     => 'Hola, te ayudo a elegir el mejor producto.',
				'placeholder' => 'Que producto estas buscando?',
			),
		);

		return isset( $texts[ $language ] ) ? $texts[ $language ] : $texts['en'];
	}
}
