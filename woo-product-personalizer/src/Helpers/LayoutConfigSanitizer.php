<?php
/**
 * Sanitize layout configuration arrays.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutConfigSanitizer
 */
class LayoutConfigSanitizer {

	/**
	 * Sanitize layout config.
	 *
	 * @param array $config Raw config.
	 * @return array
	 */
	public static function sanitize( array $config ) {
		$repository = new LayoutRepository();
		$defaults   = $repository->default_config();
		$canvas     = wp_parse_args( $config['canvas'] ?? array(), $defaults['canvas'] );

		$personalization_mode = sanitize_key( $config['personalization_mode'] ?? 'layout_2' );
		if ( ! in_array( $personalization_mode, array( 'layout_1', 'layout_2' ), true ) ) {
			$personalization_mode = 'layout_2';
		}

		$crop_mask_shape = array_key_exists( 'crop_mask_shape', $config )
			? ! empty( $config['crop_mask_shape'] )
			: true;

		$project_pdf = $config['project_pdf'] ?? array();

		$sanitized = array(
			'personalization_mode' => $personalization_mode,
			'crop_mask_shape'      => $crop_mask_shape,
			'project_pdf'          => array(
				'width_cm'  => self::sanitize_cm( $project_pdf['width_cm'] ?? 0 ),
				'height_cm' => self::sanitize_cm( $project_pdf['height_cm'] ?? 0 ),
			),
			'canvas'      => array(
				'width'      => absint( $canvas['width'] ),
				'height'     => absint( $canvas['height'] ),
				'background' => esc_url_raw( $canvas['background'] ?? '' ),
				'overlay'    => esc_url_raw( $canvas['overlay'] ?? '' ),
			),
			'image_slots'  => array(),
			'export_areas' => array(),
			'text_fields'  => array(),
			'limits'      => array(
				'max_total_images' => absint( $config['limits']['max_total_images'] ?? 4 ),
				'max_upload_mb'    => absint( $config['limits']['max_upload_mb'] ?? 10 ),
			),
		);

		if ( ! empty( $config['image_slots'] ) && is_array( $config['image_slots'] ) ) {
			foreach ( $config['image_slots'] as $slot ) {
				if ( empty( $slot['id'] ) ) {
					continue;
				}
				$customer_editable = ! isset( $slot['customer_editable'] ) || ! empty( $slot['customer_editable'] );

				$sanitized['image_slots'][] = array(
					'id'                => sanitize_key( $slot['id'] ),
					'label'             => sanitize_text_field( $slot['label'] ?? '' ),
					'customer_editable' => $customer_editable,
					'fixed_source'      => $customer_editable ? '' : esc_url_raw( $slot['fixed_source'] ?? '' ),
					'required'          => $customer_editable && ! empty( $slot['required'] ),
					'white_bg'          => ! empty( $slot['white_bg'] ),
					'max_files'     => absint( $slot['max_files'] ?? 1 ),
					'allowed_types' => array_map( 'sanitize_text_field', (array) ( $slot['allowed_types'] ?? array( 'image/jpeg', 'image/png', 'image/webp' ) ) ),
					'mask'          => esc_url_raw( $slot['mask'] ?? '' ),
					'border'        => array(
						'width' => absint( $slot['border']['width'] ?? 0 ),
						'color' => sanitize_hex_color( $slot['border']['color'] ?? '#ffffff' ) ?: '#ffffff',
					),
					'frame'         => array(
						'x'      => absint( $slot['frame']['x'] ?? 0 ),
						'y'      => absint( $slot['frame']['y'] ?? 0 ),
						'width'  => absint( $slot['frame']['width'] ?? 100 ),
						'height' => absint( $slot['frame']['height'] ?? 100 ),
					),
					'controls'      => array(
						'move'    => ! isset( $slot['controls']['move'] ) || ! empty( $slot['controls']['move'] ),
						'scale'   => ! isset( $slot['controls']['scale'] ) || ! empty( $slot['controls']['scale'] ),
						'rotate'  => ! isset( $slot['controls']['rotate'] ) || ! empty( $slot['controls']['rotate'] ),
						'flip'    => ! isset( $slot['controls']['flip'] ) || ! empty( $slot['controls']['flip'] ),
						'autofit' => ! isset( $slot['controls']['autofit'] ) || ! empty( $slot['controls']['autofit'] ),
						'reset'   => ! isset( $slot['controls']['reset'] ) || ! empty( $slot['controls']['reset'] ),
					),
				);
			}
		}

		if ( ! empty( $config['export_areas'] ) && is_array( $config['export_areas'] ) ) {
			foreach ( $config['export_areas'] as $area ) {
				if ( empty( $area['id'] ) ) {
					continue;
				}

				$sanitized['export_areas'][] = array(
					'id'    => sanitize_key( $area['id'] ),
					'label' => sanitize_text_field( $area['label'] ?? '' ),
					'mask'  => esc_url_raw( $area['mask'] ?? '' ),
					'frame' => array(
						'x'      => absint( $area['frame']['x'] ?? 0 ),
						'y'      => absint( $area['frame']['y'] ?? 0 ),
						'width'  => absint( $area['frame']['width'] ?? 100 ),
						'height' => absint( $area['frame']['height'] ?? 100 ),
					),
				);
			}
		}

		if ( ! empty( $config['text_fields'] ) && is_array( $config['text_fields'] ) ) {
			foreach ( $config['text_fields'] as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$sanitized['text_fields'][] = array(
					'id'            => sanitize_key( $field['id'] ),
					'label'         => sanitize_text_field( $field['label'] ?? '' ),
					'required'      => ! empty( $field['required'] ),
					'type'          => 'textarea',
					'min_length'    => absint( $field['min_length'] ?? 0 ),
					'max_length'    => absint( $field['max_length'] ?? 100 ),
					'placeholder'   => sanitize_text_field( $field['placeholder'] ?? '' ),
					'default_value' => sanitize_textarea_field( $field['default_value'] ?? '' ),
					'google_fonts'  => self::sanitize_google_font_urls( $field['google_fonts'] ?? array() ),
					'controls'      => array(
						'move'     => ! isset( $field['controls']['move'] ) || ! empty( $field['controls']['move'] ),
						'fontSize' => ! empty( $field['controls']['fontSize'] ),
					),
					'style'         => array(
						'x'          => absint( $field['style']['x'] ?? 0 ),
						'y'          => absint( $field['style']['y'] ?? 0 ),
						'width'      => absint( $field['style']['width'] ?? 400 ),
						'height'     => absint( $field['style']['height'] ?? 80 ),
						'fontFamily' => sanitize_text_field( $field['style']['fontFamily'] ?? 'Arial' ),
						'fontSize'   => absint( $field['style']['fontSize'] ?? 48 ),
						'color'      => sanitize_hex_color( $field['style']['color'] ?? '#ffffff' ) ?: '#ffffff',
						'align'      => in_array( $field['style']['align'] ?? 'center', array( 'left', 'center', 'right' ), true )
							? $field['style']['align']
							: 'center',
						'textShadow' => ! empty( $field['style']['textShadow'] ),
					),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Keep only valid Google Fonts CSS URLs (one family per link in UI).
	 *
	 * @param array $urls Raw URLs.
	 * @return array
	 */
	/**
	 * Sanitize PDF dimension in centimeters (0 = auto).
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	private static function sanitize_cm( $value ) {
		$cm = round( (float) $value, 2 );

		return max( 0, min( 200, $cm ) );
	}

	/**
	 * @param array $urls Raw URLs.
	 * @return array
	 */
	private static function sanitize_google_font_urls( array $urls ) {
		$clean = array();

		foreach ( $urls as $url ) {
			$url = esc_url_raw( is_string( $url ) ? trim( $url ) : '' );

			if ( $url && preg_match( '#^https://fonts\.googleapis\.com/css#i', $url ) ) {
				$clean[] = $url;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
