<?php
/**
 * Frontend form renderer.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Frontend;

use Weblo\FastForms\FormBuilder\FormSchemaStorage;
use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\PostTypes\FormPostType;

/**
 * Renderuje formularz na froncie na podstawie schemy.
 */
final class FormRenderer {

	private int $form_id;

	private string $display;

	private string $instance_id;

	/**
	 * @param int    $form_id  ID formularza.
	 * @param string $display  Tryb wyświetlania: inline, button, trigger, modal.
	 * @param string $instance Unikalny identyfikator instancji na stronie.
	 */
	public function __construct( int $form_id, string $display = 'inline', string $instance = '' ) {
		$this->form_id     = $form_id;
		$this->display     = sanitize_key( $display );
		$this->instance_id = '' !== $instance ? sanitize_key( $instance ) : 'ff-' . $form_id . '-' . wp_generate_password( 6, false, false );
	}

	/**
	 * Renderuje formularz lub kontener.
	 */
	public function render(): string {
		$post = get_post( $this->form_id );

		if ( ! $post instanceof \WP_Post || FormPostType::POST_TYPE !== $post->post_type ) {
			return '';
		}

		if ( 'publish' !== $post->post_status ) {
			return '';
		}

		$schema = FormSchemaStorage::get( $this->form_id );

		if ( empty( $schema['rows'] ) ) {
			return '';
		}

		$settings = FormSettingsStorage::get_all( $this->form_id );

		Assets::register_instance( $this->instance_id );

		$form_id  = $this->form_id;
		$instance = $this->instance_id;
		$display  = $this->display;

		ob_start();
		include FF_PLUGIN_DIR . 'templates/frontend/form.php';
		return (string) ob_get_clean();
	}

	/**
	 * Renderuje pojedyncze pole formularza.
	 *
	 * @param array<string, mixed> $field Definicja pola.
	 */
	public function render_field( array $field ): void {
		$type = $field['type'] ?? 'text';

		if ( 'submit' === $type ) {
			$template = 'submit.php';
		} else {
			$template = 'field.php';
		}

		$file = FF_PLUGIN_DIR . 'templates/frontend/' . $template;

		if ( ! is_readable( $file ) ) {
			return;
		}

		$form_id    = $this->form_id;
		$instance   = $this->instance_id;
		$field_id   = $field['id'] ?? '';
		$field_name = $field['name'] ?? $field_id;
		$required   = ! empty( $field['required'] );
		$label      = $field['label'] ?? '';
		$css_class  = $field['cssClass'] ?? '';

		include $file;
	}

	public function get_form_id(): int {
		return $this->form_id;
	}

	public function get_instance_id(): string {
		return $this->instance_id;
	}

	public function get_display(): string {
		return $this->display;
	}
}
