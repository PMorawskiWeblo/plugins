<?php
/**
 * SVG production file for personalized text layers.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Generator;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Helpers\LayoutConfigLoader;
use WooProductPersonalizer\Helpers\PersonalizationSummaryHelper;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class TextSvgGenerator
 */
class TextSvgGenerator {

	/**
	 * Uploads.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 * @param Logger         $logger  Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->uploads = $uploads;
		$this->logger  = $logger;
	}

	/**
	 * Save SVG from browser export or data URL.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Line item ID.
	 * @param string $svg_source   Raw SVG, file path, or data URL.
	 * @param string $directory    Target directory.
	 * @param string $filename_tag Filename tag (default tekst).
	 * @return array{path: string, url: string}
	 */
	public function save( $order_id, $item_id, $svg_source, $directory, $filename_tag = 'tekst', $layout_id = 0, array $state = array(), $dpi = 300 ) {
		$svg = $this->read_svg_content( $svg_source );

		if ( '' === $svg ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		if ( $layout_id ) {
			$svg = $this->inject_google_fonts_into_svg( $svg, $layout_id, $state );
			$svg = $this->inject_output_dpi_into_svg( $svg, $layout_id, $dpi );
		}

		return $this->write_svg( $order_id, $item_id, $svg, $directory, $filename_tag );
	}

	/**
	 * Build SVG from project state and layout config (regenerate / fallback).
	 *
	 * @param int   $order_id  Order ID.
	 * @param int   $item_id   Line item ID.
	 * @param array $state     Project state.
	 * @param int   $layout_id Layout post ID.
	 * @param string $directory Target directory.
	 * @return array{path: string, url: string}
	 */
	public function generate_from_state( $order_id, $item_id, array $state, $layout_id, $directory, $dpi = 300 ) {
		$config = LayoutConfigLoader::load( $layout_id );

		if ( empty( $config['text_fields'] ) || empty( $state['text_fields'] ) ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$canvas = $config['canvas'] ?? array();
		$width  = max( 1, absint( $canvas['width'] ?? 800 ) );
		$height = max( 1, absint( $canvas['height'] ?? 1000 ) );

		$field_map = array();
		foreach ( $config['text_fields'] as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$field_map[ $field['id'] ] = $field;
			}
		}

		$elements     = '';
		$needs_shadow = false;

		foreach ( $state['text_fields'] as $id => $raw ) {
			$field = $field_map[ $id ] ?? array( 'style' => array() );
			$parsed = PersonalizationSummaryHelper::parse_text_field( $raw, $field );

			if ( '' === trim( $parsed['value'] ) ) {
				continue;
			}

			$offset_x = 0.0;
			$offset_y = 0.0;

			if ( is_array( $raw ) ) {
				if ( isset( $raw['offsetX'] ) ) {
					$offset_x = (float) $raw['offsetX'];
				}
				if ( isset( $raw['offsetY'] ) ) {
					$offset_y = (float) $raw['offsetY'];
				}
			}

			$style = $field['style'] ?? array();
			$box_x = (float) ( $style['x'] ?? 0 ) + $offset_x;
			$box_y = (float) ( $style['y'] ?? 0 ) + $offset_y;
			$box_w = max( 20.0, (float) ( $style['width'] ?? 400 ) );
			$box_h = max( 20.0, (float) ( $style['height'] ?? 80 ) );

			$use_shadow = ! empty( $style['textShadow'] );
			if ( $use_shadow ) {
				$needs_shadow = true;
			}

			$elements .= $this->build_text_element(
				$parsed['value'],
				$box_x,
				$box_y,
				$box_w,
				$box_h,
				max( 8, (int) $parsed['font_size'] ),
				$parsed['font_family'],
				(string) ( $style['color'] ?? '#000000' ),
				(string) ( $style['align'] ?? 'center' ),
				$use_shadow
			);
		}

		if ( '' === $elements ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$font_urls   = $this->collect_google_font_urls( $field_map, $state['text_fields'] );
		$style_block = $this->build_google_font_style_block( $font_urls );
		$defs        = $needs_shadow ? $this->build_text_shadow_filter_def() : '';
		$svg         = $this->wrap_document( $width, $height, $style_block . $defs . $elements, $dpi );

		return $this->write_svg( $order_id, $item_id, $svg, $directory, 'tekst' );
	}

	/**
	 * @param string $content SVG document.
	 * @param int    $width   Canvas width.
	 * @param int    $height  Canvas height.
	 * @return string
	 */
	private function wrap_document( $width, $height, $content, $dpi = 300 ) {
		$dpi = max( 1, absint( $dpi ) );
		$width_in  = $width / $dpi;
		$height_in = $height / $dpi;
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->format_dimension_in( $width_in ) . '" height="' . $this->format_dimension_in( $height_in ) . '" viewBox="0 0 ' . (int) $width . ' ' . (int) $height . '">' . "\n"
			. '<metadata>dpi=' . (int) $dpi . '</metadata>' . "\n"
			. $content
			. "\n</svg>";
	}

	/**
	 * Inject DPI-related dimensions into an existing SVG root element.
	 *
	 * @param string $svg       SVG.
	 * @param int    $layout_id Layout ID.
	 * @param int    $dpi       DPI.
	 * @return string
	 */
	private function inject_output_dpi_into_svg( $svg, $layout_id, $dpi ) {
		$config = LayoutConfigLoader::load( $layout_id );
		$canvas = is_array( $config['canvas'] ?? null ) ? $config['canvas'] : array();
		$width  = max( 1, absint( $canvas['width'] ?? 0 ) );
		$height = max( 1, absint( $canvas['height'] ?? 0 ) );
		$dpi    = max( 1, absint( $dpi ) );

		// Fallback for layouts that do not expose canvas dimensions consistently.
		// In such case use existing SVG viewBox so the exported file remains visible.
		if ( $width <= 1 || $height <= 1 ) {
			$view_box_size = $this->extract_viewbox_size( $svg );
			if ( ! empty( $view_box_size['width'] ) && ! empty( $view_box_size['height'] ) ) {
				$width  = (int) $view_box_size['width'];
				$height = (int) $view_box_size['height'];
			}
		}

		if ( ! $width || ! $height ) {
			return $svg;
		}

		$width_in  = $this->format_dimension_in( $width / $dpi );
		$height_in = $this->format_dimension_in( $height / $dpi );

		$svg = preg_replace( '/\swidth="[^"]*"/i', '', $svg, 1 );
		$svg = preg_replace( '/\sheight="[^"]*"/i', '', $svg, 1 );

		$svg = preg_replace(
			'/<svg\b([^>]*)>/i',
			'<svg$1 width="' . $width_in . '" height="' . $height_in . '">',
			$svg,
			1
		);

		if ( false === strpos( $svg, '<metadata>' ) ) {
			$svg = $this->insert_after_svg_open( $svg, '<metadata>dpi=' . (int) $dpi . '</metadata>' . "\n" );
		}

		return is_string( $svg ) ? $svg : '';
	}

	/**
	 * Format dimension in inches for SVG root attributes.
	 *
	 * @param float $value Inches value.
	 * @return string
	 */
	private function format_dimension_in( $value ) {
		return rtrim( rtrim( sprintf( '%.4F', (float) $value ), '0' ), '.' ) . 'in';
	}

	/**
	 * Extract width/height from SVG viewBox.
	 *
	 * @param string $svg SVG markup.
	 * @return array{width:int,height:int}
	 */
	private function extract_viewbox_size( $svg ) {
		$size = array(
			'width'  => 0,
			'height' => 0,
		);

		if ( ! is_string( $svg ) || '' === $svg ) {
			return $size;
		}

		if ( preg_match( '/viewBox\s*=\s*"([^"]+)"/i', $svg, $match ) ) {
			$parts = preg_split( '/\s+/', trim( (string) $match[1] ) );
			if ( is_array( $parts ) && count( $parts ) >= 4 ) {
				$size['width']  = max( 0, (int) round( (float) $parts[2] ) );
				$size['height'] = max( 0, (int) round( (float) $parts[3] ) );
			}
		}

		return $size;
	}

	/**
	 * Inject Google Fonts @import rules from layout config into an existing SVG document.
	 *
	 * @param string $svg       SVG markup.
	 * @param int    $layout_id Layout post ID.
	 * @param array  $state     Project state.
	 * @return string
	 */
	private function inject_google_fonts_into_svg( $svg, $layout_id, array $state ) {
		$config = LayoutConfigLoader::load( $layout_id );

		if ( empty( $config['text_fields'] ) || empty( $state['text_fields'] ) ) {
			return $svg;
		}

		$field_map = array();
		foreach ( $config['text_fields'] as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$field_map[ $field['id'] ] = $field;
			}
		}

		$urls = $this->collect_google_font_urls( $field_map, $state['text_fields'] );

		if ( empty( $urls ) ) {
			return $svg;
		}

		foreach ( $urls as $url ) {
			if ( false !== strpos( $svg, $url ) ) {
				continue;
			}

			return $this->insert_after_svg_open( $svg, $this->build_google_font_style_block( $urls ) );
		}

		return $svg;
	}

	/**
	 * Collect Google Fonts CSS URLs for text fields that have content.
	 *
	 * @param array<string, array> $field_map Layout text fields keyed by id.
	 * @param array                $text_state Project text_fields state.
	 * @return string[]
	 */
	private function collect_google_font_urls( array $field_map, array $text_state ) {
		$urls = array();

		foreach ( $text_state as $id => $raw ) {
			$value = is_array( $raw ) ? (string) ( $raw['value'] ?? '' ) : (string) $raw;

			if ( '' === trim( $value ) ) {
				continue;
			}

			$field = $field_map[ $id ] ?? array();

			foreach ( $field['google_fonts'] ?? array() as $url ) {
				$url = is_string( $url ) ? trim( $url ) : '';

				if ( $url && preg_match( '#^https://fonts\.googleapis\.com/css#i', $url ) && ! in_array( $url, $urls, true ) ) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	/**
	 * @param string[] $urls Google Fonts CSS URLs from layout.
	 * @return string
	 */
	private function build_google_font_style_block( array $urls ) {
		if ( empty( $urls ) ) {
			return '';
		}

		$imports = '';

		foreach ( $urls as $url ) {
			$imports .= '@import url("' . esc_url( $url ) . '");' . "\n";
		}

		return '<defs><style type="text/css"><![CDATA[' . "\n" . $imports . ']]></style></defs>' . "\n";
	}

	/**
	 * @param string $svg    SVG document.
	 * @param string $insert Markup to insert after the root <svg> tag.
	 * @return string
	 */
	private function insert_after_svg_open( $svg, $insert ) {
		if ( '' === $insert ) {
			return $svg;
		}

		if ( preg_match( '#(<svg\b[^>]*>)#i', $svg, $matches, PREG_OFFSET_CAPTURE ) ) {
			$tag  = $matches[1][0];
			$pos  = $matches[1][1] + strlen( $tag );

			return substr( $svg, 0, $pos ) . "\n" . $insert . substr( $svg, $pos );
		}

		return $insert . $svg;
	}

	/**
	 * @param string $text       Text content.
	 * @param float  $box_x      Box X.
	 * @param float  $box_y      Box Y.
	 * @param float  $box_w      Box width.
	 * @param float  $box_h      Box height.
	 * @param int    $font_size  Font size in px.
	 * @param string $font_family Font family.
	 * @param string $fill       Fill color.
	 * @param string $align      left|center|right.
	 * @param bool   $use_shadow Whether to apply SVG drop shadow filter.
	 * @return string
	 */
	private function build_text_element( $text, $box_x, $box_y, $box_w, $box_h, $font_size, $font_family, $fill, $align, $use_shadow = false ) {
		$lines      = $this->wrap_lines( $text, $box_w, $font_size );
		$line_height = $font_size * 1.2;
		$block_height = count( $lines ) * $line_height;
		$start_y      = $box_y + max( 0, ( $box_h - $block_height ) / 2 ) + $font_size;

		$anchor = 'middle';
		$anchor_x = $box_x + ( $box_w / 2 );

		if ( 'left' === $align ) {
			$anchor   = 'start';
			$anchor_x = $box_x;
		} elseif ( 'right' === $align ) {
			$anchor   = 'end';
			$anchor_x = $box_x + $box_w;
		}

		$fill_attr   = $this->escape_attr( $fill );
		$family_attr = $this->escape_attr( $font_family );
		$tspans      = '';
		$current_y    = $start_y;

		foreach ( $lines as $line ) {
			$tspans .= '<tspan x="' . $this->format_number( $anchor_x ) . '" y="' . $this->format_number( $current_y ) . '">'
				. $this->escape_text( $line ) . '</tspan>';
			$current_y += $line_height;
		}

		$filter_attr = $use_shadow ? ' filter="url(#wpp-text-shadow)"' : '';

		return '<text font-family="' . $family_attr . '" font-size="' . (int) $font_size . '" fill="' . $fill_attr . '" text-anchor="' . $anchor . '" dominant-baseline="alphabetic"' . $filter_attr . '>' . "\n"
			. $tspans . "\n"
			. '</text>' . "\n";
	}

	/**
	 * SVG filter definition for optional text shadow.
	 *
	 * @return string
	 */
	private function build_text_shadow_filter_def() {
		return '<defs><filter id="wpp-text-shadow" x="-30%" y="-30%" width="160%" height="160%">'
			. '<feOffset dx="1" dy="1" result="off"/>'
			. '<feGaussianBlur in="off" stdDeviation="1" result="blur"/>'
			. '<feFlood flood-color="#000000" flood-opacity="0.35"/>'
			. '<feComposite in2="blur" operator="in" result="shadow"/>'
			. '<feMerge><feMergeNode in="shadow"/><feMergeNode in="SourceGraphic"/></feMerge>'
			. '</filter></defs>' . "\n";
	}

	/**
	 * @param string $text      Text.
	 * @param float  $max_width Max width in px.
	 * @param int    $font_size Font size.
	 * @return string[]
	 */
	private function wrap_lines( $text, $max_width, $font_size ) {
		$char_width = max( 4.0, $font_size * 0.52 );
		$lines      = array();
		$paragraphs = preg_split( "/\r\n|\r|\n/", (string) $text );

		if ( ! is_array( $paragraphs ) ) {
			$paragraphs = array( (string) $text );
		}

		foreach ( $paragraphs as $paragraph ) {
			$paragraph = (string) $paragraph;
			if ( '' === trim( $paragraph ) ) {
				$lines[] = '';
				continue;
			}

			$words   = preg_split( '/\s+/u', $paragraph );
			$current = '';

			foreach ( $words as $word ) {
				if ( '' === $word ) {
					continue;
				}

				$candidate = '' === $current ? $word : $current . ' ' . $word;
				$width     = mb_strlen( $candidate, 'UTF-8' ) * $char_width;

				if ( $width <= $max_width || '' === $current ) {
					$current = $candidate;
					continue;
				}

				$lines[] = $current;
				$current = $word;
			}

			if ( '' !== $current ) {
				$lines[] = $current;
			}
		}

		return ! empty( $lines ) ? $lines : array( '' );
	}

	/**
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Item ID.
	 * @param string $svg          SVG document.
	 * @param string $directory    Directory.
	 * @param string $filename_tag Tag.
	 * @return array{path: string, url: string}
	 */
	private function write_svg( $order_id, $item_id, $svg, $directory, $filename_tag ) {
		$item_id      = absint( $item_id );
		$filename_tag = sanitize_file_name( (string) $filename_tag );
		if ( '' === $filename_tag ) {
			$filename_tag = 'tekst';
		}

		$filename = absint( $order_id ) . '_item_' . ( $item_id ? $item_id : '0' ) . '_' . $filename_tag . '.svg';
		$path     = trailingslashit( $directory ) . $filename;
		$url      = trailingslashit( $this->uploads->order_url( $order_id ) ) . $filename;

		$svg = $this->sanitize_svg_document( $svg );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, $svg ) ) {
			$this->logger->error(
				'Failed to write text SVG.',
				array(
					'order_id' => $order_id,
					'item_id'  => $item_id,
				)
			);
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		return array(
			'path' => $path,
			'url'  => $url,
		);
	}

	/**
	 * @param string $source Raw SVG, path, or data URL.
	 * @return string
	 */
	private function read_svg_content( $source ) {
		$source = is_string( $source ) ? trim( $source ) : '';

		if ( '' === $source ) {
			return '';
		}

		if ( preg_match( '#^data:image/svg\+xml(?:;charset=utf-8)?;base64,#i', $source ) ) {
			$payload = substr( $source, strpos( $source, ',' ) + 1 );
			$decoded = base64_decode( preg_replace( '/\s+/', '', $payload ), true );

			return is_string( $decoded ) ? $decoded : '';
		}

		if ( preg_match( '#^data:image/svg\+xml,#i', $source ) ) {
			$payload = rawurldecode( substr( $source, strpos( $source, ',' ) + 1 ) );

			return is_string( $payload ) ? $payload : '';
		}

		if ( is_readable( $source ) && ! is_dir( $source ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$content = file_get_contents( $source );

			return is_string( $content ) ? $content : '';
		}

		if ( preg_match( '#^\s*<\?xml#i', $source ) || preg_match( '#^\s*<svg#i', $source ) ) {
			return $source;
		}

		return '';
	}

	/**
	 * Strip unsafe SVG fragments.
	 *
	 * @param string $svg SVG.
	 * @return string
	 */
	private function sanitize_svg_document( $svg ) {
		$svg = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $svg );
		$svg = preg_replace( '/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg );
		$svg = preg_replace( '/<!ENTITY.+?>/is', '', $svg );

		return is_string( $svg ) ? trim( $svg ) : '';
	}

	/**
	 * @param string $value Text.
	 * @return string
	 */
	private function escape_text( $value ) {
		return htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * @param string $value Attribute.
	 * @return string
	 */
	private function escape_attr( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * @param float $number Number.
	 * @return string
	 */
	private function format_number( $number ) {
		return rtrim( rtrim( sprintf( '%.2F', (float) $number ), '0' ), '.' );
	}
}
