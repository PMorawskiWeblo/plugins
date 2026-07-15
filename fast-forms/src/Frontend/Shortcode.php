<?php
/**
 * Form shortcode.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Frontend;

/**
 * Shortcode [smart_form] do osadzania formularzy.
 */
final class Shortcode {

	/**
	 * Rejestruje shortcode.
	 */
	public function register(): void {
		add_shortcode( 'smart_form', array( $this, 'render' ) );
	}

	/**
	 * Renderuje shortcode formularza.
	 *
	 * @param array<string, string>|string $atts Atrybuty shortcode.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'           => '0',
				'display'      => 'inline',
				'button_text'  => __( 'Open form', 'fast-forms' ),
				'button_class' => 'button',
				'trigger'      => '',
			),
			$atts,
			'smart_form'
		);

		$form_id = absint( $atts['id'] );

		if ( $form_id < 1 ) {
			return '';
		}

		$display      = sanitize_key( $atts['display'] );
		$button_text  = sanitize_text_field( $atts['button_text'] );
		$button_class = ShortcodeAttributes::sanitize_class_list( (string) $atts['button_class'] );
		$trigger      = ShortcodeAttributes::normalize_trigger_selector( sanitize_text_field( $atts['trigger'] ) );
		$instance    = 'ff-sc-' . $form_id . '-' . wp_generate_password( 6, false, false );

		if ( 'button' === $display ) {
			$renderer  = new FormRenderer( $form_id, 'modal', $instance );
			$instance  = $renderer->get_instance_id();
			$form_html = $renderer->render();

			if ( '' === $form_html ) {
				return '';
			}

			ob_start();
			?>
			<div class="ff-shortcode ff-shortcode--button" data-ff-instance="<?php echo esc_attr( $instance ); ?>">
				<button type="button" class="ff-open-form <?php echo esc_attr( $button_class ); ?>" data-ff-target="<?php echo esc_attr( $instance ); ?>">
					<?php echo esc_html( $button_text ); ?>
				</button>
				<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapowane w szablonie. ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		if ( 'trigger' === $display ) {
			if ( '' === $trigger ) {
				return '';
			}

			$renderer  = new FormRenderer( $form_id, 'modal', $instance );
			$instance  = $renderer->get_instance_id();
			$form_html = $renderer->render();

			if ( '' === $form_html ) {
				return '';
			}

			ob_start();
			?>
			<div class="ff-shortcode ff-shortcode--trigger" data-ff-instance="<?php echo esc_attr( $instance ); ?>" data-ff-trigger="<?php echo esc_attr( $trigger ); ?>">
				<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		$renderer = new FormRenderer( $form_id, 'inline', $instance );

		return $renderer->render();
	}
}
