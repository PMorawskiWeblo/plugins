<?php
/**
 * Server-side personalization validation.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Domain\Project;

use WooProductPersonalizer\Domain\Layout\Layout;
use WooProductPersonalizer\Domain\Product\ProductConfiguration;
use WooProductPersonalizer\Helpers\PersonalizationSummaryHelper;
use WooProductPersonalizer\Helpers\UploadSession;
use WooProductPersonalizer\Helpers\UploadUrlValidator;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ValidationService
 */
class ValidationService {

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Validate project state against layout and product config.
	 *
	 * @param array                $state   Decoded project state.
	 * @param Layout               $layout  Layout.
	 * @param ProductConfiguration $product Product config.
	 * @return true|\WP_Error
	 */
	public function validate( array $state, Layout $layout, ProductConfiguration $product ) {
		$errors = array();

		foreach ( $layout->get_text_fields() as $field ) {
			$id    = $field['id'];
			$value = $state['text_fields'][ $id ] ?? '';
			if ( is_array( $value ) ) {
				$value = (string) ( $value['value'] ?? '' );
			} else {
				$value = (string) $value;
			}
			$max   = (int) ( $field['max_length'] ?? 0 );

			if ( ! empty( $field['required'] ) && '' === trim( $value ) ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( 'Required text field missing: %s', 'woo-product-personalizer' ),
					$field['label'] ?? $id
				);
			}

			if ( $max > 0 && mb_strlen( $value ) > $max ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( 'Text too long: %s', 'woo-product-personalizer' ),
					$field['label'] ?? $id
				);
			}
		}

		$upload_token = UploadSession::get_token();

		foreach ( $layout->get_image_slots() as $slot ) {
			$id = $slot['id'];
			$source = $state['image_fields'][ $id ]['source'] ?? '';

			if ( ! empty( $slot['required'] ) && '' === $source ) {
				$errors[] = sprintf(
					/* translators: %s: slot label */
					__( 'Required image missing: %s', 'woo-product-personalizer' ),
					$slot['label'] ?? $id
				);
				continue;
			}

			if ( '' !== $source && ! UploadUrlValidator::is_allowed_customer_image_url( $source, $upload_token ) ) {
				$errors[] = sprintf(
					/* translators: %s: slot label */
					__( 'Invalid image source: %s', 'woo-product-personalizer' ),
					$slot['label'] ?? $id
				);
			}
		}

		if ( $product->is_acceptance_required() && empty( $state['acceptance']['checked'] ) ) {
			$errors[] = __( 'Acceptance checkbox is required.', 'woo-product-personalizer' );
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'wpp_validation', implode( ' ', $errors ) );
		}

		return true;
	}

	/**
	 * Build readable summary from state.
	 *
	 * @param array  $state  State.
	 * @param Layout $layout Layout.
	 * @return array
	 */
	public function build_summary( array $state, Layout $layout ) {
		return PersonalizationSummaryHelper::build_summary( $state, $layout );
	}
}
