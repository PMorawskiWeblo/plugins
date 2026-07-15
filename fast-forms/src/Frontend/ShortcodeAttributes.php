<?php
/**
 * Shortcode attribute helpers.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Frontend;

/**
 * Buduje atrybuty shortcode [smart_form].
 */
final class ShortcodeAttributes {

	/**
	 * Normalizuje selektor CSS (dodaje kropkę dla samej klasy).
	 */
	public static function normalize_trigger_selector( string $selector ): string {
		$selector = trim( $selector );

		if ( '' === $selector ) {
			return '';
		}

		if ( str_starts_with( $selector, '.' ) || str_starts_with( $selector, '#' ) ) {
			return $selector;
		}

		return '.' . $selector;
	}

	/**
	 * Sanityzuje listę klas CSS.
	 */
	public static function sanitize_class_list( string $classes ): string {
		$parts     = preg_split( '/\s+/', trim( $classes ) ) ?: array();
		$sanitized = array();

		foreach ( $parts as $part ) {
			$class = sanitize_html_class( $part );

			if ( '' !== $class ) {
				$sanitized[] = $class;
			}
		}

		return implode( ' ', array_unique( $sanitized ) );
	}

	/**
	 * Buduje shortcode na podstawie ustawień formularza.
	 *
	 * @param int                  $form_id  ID formularza.
	 * @param string               $display  Tryb: inline, button, trigger.
	 * @param array<string, mixed> $settings Ustawienia formularza (grupa form).
	 */
	public static function build( int $form_id, string $display, array $settings = array() ): string {
		$parts = array(
			'smart_form id="' . $form_id . '"',
			'display="' . sanitize_key( $display ) . '"',
		);

		if ( 'button' === $display ) {
			$button_text = sanitize_text_field( (string) ( $settings['shortcodeButtonText'] ?? __( 'Open form', 'fast-forms' ) ) );
			$button_class = self::sanitize_class_list( (string) ( $settings['shortcodeButtonClass'] ?? 'button' ) );

			$parts[] = 'button_text="' . $button_text . '"';

			if ( '' !== $button_class ) {
				$parts[] = 'button_class="' . $button_class . '"';
			}
		}

		if ( 'trigger' === $display ) {
			$trigger = self::normalize_trigger_selector( (string) ( $settings['shortcodeTriggerClass'] ?? '' ) );

			if ( '' !== $trigger ) {
				$parts[] = 'trigger="' . $trigger . '"';
			}
		}

		return '[' . implode( ' ', $parts ) . ']';
	}
}
