<?php
/**
 * Human-readable order line item personalization display.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Helpers\PersonalizationSummaryHelper;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;
use WooProductPersonalizer\Integrations\WooCommerce\PreviewDisplay;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderItemMetaDisplay
 */
class OrderItemMetaDisplay {

	/**
	 * Internal meta keys (hidden from default WC meta list).
	 *
	 * @var string[]
	 */
	private const HIDDEN_KEYS = array(
		'_wpp_personalized',
		'_wpp_layout_id',
		'_wpp_summary',
		'_wpp_project_state',
		'_wpp_hash',
		'_wpp_preview_data',
		'_wpp_preview_id',
		'_wpp_preview_full_url',
		'_wpp_project_json',
		'_wpp_production_file',
		'_wpp_production_url',
		'_wpp_preview_layers_full_url',
		'_wpp_layers_production_file',
		'_wpp_layers_production_url',
		'_wpp_preview_text_svg_full_url',
		'_wpp_text_svg_file',
		'_wpp_text_svg_url',
	);

	/**
	 * Order IDs for which the ZIP link block was already printed.
	 *
	 * @var array<int, bool>
	 */
	private static $order_files_rendered = array();

	/**
	 * Uploads manager.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 */
	public function __construct( UploadsManager $uploads ) {
		$this->uploads = $uploads;

		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_internal_meta' ) );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'render_item_panel' ), 10, 3 );
	}

	/**
	 * Hide raw meta keys in order item details.
	 *
	 * @param array $hidden Hidden keys.
	 * @return array
	 */
	public function hide_internal_meta( $hidden ) {
		return array_merge( $hidden, self::HIDDEN_KEYS );
	}

	/**
	 * Render personalization block under line item in admin.
	 *
	 * @param int                    $item_id Item ID.
	 * @param \WC_Order_Item_Product $item    Item.
	 * @param \WC_Product|null       $product Product.
	 * @return void
	 */
	public function render_item_panel( $item_id, $item, $product ) {
		unset( $item_id, $product );

		if ( ! $item instanceof \WC_Order_Item ) {
			return;
		}

		if ( 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
			return;
		}

		$layout_id    = (int) $item->get_meta( '_wpp_layout_id' );
		$layout_title = $layout_id ? get_the_title( $layout_id ) : '';
		$summary      = $item->get_meta( '_wpp_summary' );
		$order_id     = (int) $item->get_order_id();

		echo '<div class="wpp-order-item-meta">';
		echo '<p class="wpp-order-item-meta__badge"><strong>' . esc_html__( 'Personalized product', 'woo-product-personalizer' ) . '</strong></p>';

		if ( $layout_title ) {
			printf(
				'<p class="wpp-order-item-meta__layout"><strong>%s</strong> &quot;%s&quot;</p>',
				esc_html__( 'Layout:', 'woo-product-personalizer' ),
				esc_html( $layout_title )
			);
		}

		if ( is_array( $summary ) && $this->summary_has_text_rows( $summary ) ) {
			echo '<p class="wpp-order-item-meta__details-title"><strong>' . esc_html__( 'Personalization details', 'woo-product-personalizer' ) . '</strong></p>';
			echo '<div class="wpp-order-item-meta__details">';
			$this->render_summary( $summary );
			echo '</div>';
		}

		if ( PreviewDisplay::item_has_preview_meta( $item ) ) {
			PreviewDisplay::render_admin_preview( $item );
		}

		PreviewDisplay::render_admin_layers_preview( $item );
		PreviewDisplay::render_admin_text_svg_preview( $item );

		$this->render_item_production_link( $item );
		$this->render_item_layers_production_link( $item );
		$this->render_item_text_svg_link( $item );

		if ( $order_id ) {
			$this->render_order_files( $order_id );
		}

		echo '</div>';
	}

	/**
	 * Render summary rows (text only; image slot labels are omitted).
	 *
	 * @param array $summary Summary meta.
	 * @return void
	 */
	private function render_summary( array $summary ) {
		foreach ( $summary as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( 'image' === ( $row['type'] ?? '' ) ) {
				continue;
			}

			if ( PersonalizationSummaryHelper::is_legacy_row( $row ) ) {
				$this->render_label_value_line( (string) $row['key'], (string) $row['value'] );
				continue;
			}

			if ( 'text' === ( $row['type'] ?? '' ) ) {
				$field_label = (string) ( $row['label'] ?? __( 'Text', 'woo-product-personalizer' ) );
				$this->render_label_value_line( $field_label, (string) ( $row['text'] ?? '' ) );
				$this->render_label_value_line( __( 'Font size', 'woo-product-personalizer' ), (string) ( $row['font_size'] ?? '' ) );
				$this->render_label_value_line( __( 'Font family', 'woo-product-personalizer' ), (string) ( $row['font_family'] ?? '' ) );
			}
		}
	}

	/**
	 * Whether summary contains text rows worth displaying.
	 *
	 * @param array $summary Summary meta.
	 * @return bool
	 */
	private function summary_has_text_rows( array $summary ) {
		foreach ( $summary as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( 'image' === ( $row['type'] ?? '' ) ) {
				continue;
			}

			if ( PersonalizationSummaryHelper::is_legacy_row( $row ) ) {
				if ( '' !== trim( (string) ( $row['value'] ?? '' ) ) ) {
					return true;
				}
				continue;
			}

			if ( 'text' === ( $row['type'] ?? '' ) && '' !== trim( (string) ( $row['text'] ?? '' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Link to the line item production PNG when available.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return void
	 */
	private function render_item_production_link( $item ) {
		$production_url = (string) $item->get_meta( '_wpp_production_url' );

		if ( '' === $production_url || ! PreviewDisplay::is_preview_available( $production_url ) ) {
			return;
		}

		printf(
			'<p class="wpp-order-item-meta__production"><a href="%1$s" class="button button-small" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( $production_url ),
			esc_html__( 'Production PNG', 'woo-product-personalizer' )
		);
	}

	/**
	 * Link to the layers-only production PNG when available.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return void
	 */
	private function render_item_layers_production_link( $item ) {
		$layers_url = (string) $item->get_meta( '_wpp_layers_production_url' );

		if ( '' === $layers_url || ! PreviewDisplay::is_preview_available( $layers_url ) ) {
			return;
		}

		printf(
			'<p class="wpp-order-item-meta__production"><a href="%1$s" class="button button-small" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( $layers_url ),
			esc_html__( 'Layers PNG (no background)', 'woo-product-personalizer' )
		);
	}

	/**
	 * Link to the text-only production SVG when available.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return void
	 */
	private function render_item_text_svg_link( $item ) {
		$text_url = (string) $item->get_meta( '_wpp_text_svg_url' );

		if ( '' === $text_url || ! PreviewDisplay::is_svg_preview_available( $text_url ) ) {
			return;
		}

		printf(
			'<p class="wpp-order-item-meta__production"><a href="%1$s" class="button button-small" target="_blank" rel="noopener noreferrer" download>%2$s</a></p>',
			esc_url( $text_url ),
			esc_html__( 'Text SVG (no background)', 'woo-product-personalizer' )
		);
	}

	/**
	 * ZIP download for the whole order (shown once under the first personalized line item).
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	private function render_order_files( $order_id ) {
		$order_id = absint( $order_id );

		if ( ! $order_id || ! empty( self::$order_files_rendered[ $order_id ] ) ) {
			return;
		}

		self::$order_files_rendered[ $order_id ] = true;

		$folder = $this->uploads->order_path( $order_id );

		echo '<p class="wpp-order-item-meta__downloads-title"><strong>' . esc_html__( 'Production package', 'woo-product-personalizer' ) . '</strong></p>';

		if ( ! is_dir( $folder ) ) {
			echo '<p class="description">' . esc_html__( 'Files are not ready yet. Refresh the page in a moment.', 'woo-product-personalizer' ) . '</p>';
			return;
		}

		echo '<p class="wpp-order-item-meta__downloads">';

		if ( class_exists( 'ZipArchive' ) ) {
			printf(
				'<a href="%s" class="button button-primary button-small">%s</a> ',
				esc_url( OrderZipDownload::get_download_url( $order_id ) ),
				esc_html__( 'Download all files (ZIP)', 'woo-product-personalizer' )
			);
		} else {
			echo '<span class="description">' . esc_html__( 'ZIP download requires the PHP Zip extension.', 'woo-product-personalizer' ) . '</span> ';
		}

		printf(
			'<a href="%s" class="button button-small">%s</a>',
			esc_url( \WooProductPersonalizer\Integrations\WooCommerce\OrderHooks::get_regenerate_url( $order_id ) ),
			esc_html__( 'Regenerate production files', 'woo-product-personalizer' )
		);

		echo '</p>';
	}

	/**
	 * Output one line: Bold label: "value"
	 *
	 * @param string $label Label.
	 * @param string $value Value.
	 * @return void
	 */
	private function render_label_value_line( $label, $value ) {
		if ( '' === trim( $label ) ) {
			return;
		}

		printf(
			'<p class="wpp-order-item-meta__line"><strong>%s:</strong> &quot;%s&quot;</p>',
			esc_html( $label ),
			esc_html( $value )
		);
	}
}
