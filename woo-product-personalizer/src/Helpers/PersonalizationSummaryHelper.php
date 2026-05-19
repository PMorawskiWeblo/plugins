<?php
/**
 * Build human-readable personalization rows for orders.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

use WooProductPersonalizer\Domain\Layout\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class PersonalizationSummaryHelper
 */
class PersonalizationSummaryHelper {

	/**
	 * Parse text field value from project state.
	 *
	 * @param mixed $raw   Raw state entry (string or array).
	 * @param array $field Layout text field config.
	 * @return array{value:string,font_size:int,font_family:string}
	 */
	public static function parse_text_field( $raw, array $field ) {
		$value       = '';
		$font_size   = null;
		$font_family = '';

		if ( is_array( $raw ) ) {
			$value = (string) ( $raw['value'] ?? '' );
			if ( isset( $raw['fontSize'] ) && $raw['fontSize'] !== null && $raw['fontSize'] !== '' ) {
				$font_size = absint( $raw['fontSize'] );
			}
			if ( ! empty( $raw['fontFamily'] ) ) {
				$font_family = sanitize_text_field( (string) $raw['fontFamily'] );
			}
		} else {
			$value = (string) $raw;
		}

		if ( null === $font_size || $font_size < 1 ) {
			$font_size = absint( $field['style']['fontSize'] ?? 48 );
		}

		if ( '' === $font_family ) {
			$font_family = sanitize_text_field( $field['style']['fontFamily'] ?? 'Arial' );
		}

		return array(
			'value'       => $value,
			'font_size'   => max( 1, $font_size ),
			'font_family' => $font_family,
		);
	}

	/**
	 * Build structured summary for cart / order meta.
	 *
	 * @param array  $state  Project state.
	 * @param Layout $layout Layout.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_summary( array $state, Layout $layout ) {
		$summary = array();

		foreach ( $layout->get_text_fields() as $field ) {
			$id = $field['id'] ?? '';
			if ( '' === $id ) {
				continue;
			}

			$parsed = self::parse_text_field( $state['text_fields'][ $id ] ?? '', $field );

			if ( '' === trim( $parsed['value'] ) ) {
				continue;
			}

			$summary[] = array(
				'type'         => 'text',
				'label'        => $field['label'] ?? $id,
				'text'         => $parsed['value'],
				'font_size'    => $parsed['font_size'] . ' px',
				'font_family'  => $parsed['font_family'],
			);
		}

		return $summary;
	}

	/**
	 * Whether summary row uses legacy key/value shape.
	 *
	 * @param array $row Summary row.
	 * @return bool
	 */
	public static function is_legacy_row( array $row ) {
		return isset( $row['key'], $row['value'] ) && ! isset( $row['type'] );
	}

	/**
	 * Flatten summary for customer-facing formatted meta (label => quoted value).
	 *
	 * @param array $summary Summary rows.
	 * @return array<int, array{label:string,value:string}>
	 */
	public static function flatten_for_display( array $summary ) {
		$rows = array();

		foreach ( $summary as $row ) {
			if ( self::is_legacy_row( $row ) ) {
				$rows[] = array(
					'label' => (string) $row['key'],
					'value' => (string) $row['value'],
				);
				continue;
			}

			if ( 'text' === ( $row['type'] ?? '' ) ) {
				$field_label = (string) ( $row['label'] ?? __( 'Text', 'woo-product-personalizer' ) );

				$rows[] = array(
					'label' => $field_label,
					'value' => (string) ( $row['text'] ?? '' ),
				);
				$rows[] = array(
					'label' => __( 'Font size', 'woo-product-personalizer' ),
					'value' => (string) ( $row['font_size'] ?? '' ),
				);
				$rows[] = array(
					'label' => __( 'Font family', 'woo-product-personalizer' ),
					'value' => (string) ( $row['font_family'] ?? '' ),
				);
				continue;
			}

		}

		return $rows;
	}
}
