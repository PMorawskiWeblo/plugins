<?php
/**
 * Shared markup for personalization preview links.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Class PreviewDisplay
 */
class PreviewDisplay {

	/**
	 * Translatable message when preview files were removed or are missing.
	 *
	 * @return string
	 */
	public static function get_unavailable_message() {
		return __( 'Preview removed', 'woo-product-personalizer' );
	}

	/**
	 * Best available preview source for an order line item.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return string
	 */
	public static function get_item_preview_source( $item ) {
		if ( ! $item instanceof \WC_Order_Item ) {
			return '';
		}

		$production_url = (string) $item->get_meta( '_wpp_production_url' );
		if ( '' !== $production_url ) {
			return $production_url;
		}

		return (string) $item->get_meta( '_wpp_preview_data' );
	}

	/**
	 * Whether the order item ever stored preview metadata.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return bool
	 */
	public static function item_has_preview_meta( $item ) {
		if ( ! $item instanceof \WC_Order_Item ) {
			return false;
		}

		if ( '' !== (string) $item->get_meta( '_wpp_production_url' ) ) {
			return true;
		}

		if ( '' !== (string) $item->get_meta( '_wpp_preview_data' ) ) {
			return true;
		}

		return '' !== (string) $item->get_meta( '_wpp_production_file' );
	}

	/**
	 * Whether preview source can be displayed (file exists or valid data URL).
	 *
	 * @param string $preview Preview URL or data URL.
	 * @return bool
	 */
	public static function is_preview_available( $preview ) {
		$preview = is_string( $preview ) ? trim( $preview ) : '';

		if ( '' === $preview ) {
			return false;
		}

		if ( preg_match( '#^data:image/(png|jpe?g|webp);base64,#i', $preview ) ) {
			return true;
		}

		if ( ! preg_match( '#^https?://#i', $preview ) ) {
			return false;
		}

		$path = self::url_to_local_path( $preview );

		if ( false !== $path ) {
			return is_readable( $path );
		}

		return true;
	}

	/**
	 * Build preview link HTML (theme-compatible classes from personalize-product).
	 *
	 * @param string $preview      Thumbnail or preview URL for the link.
	 * @param string $popup_source Optional full-size source for the popup (defaults to $preview).
	 * @return string
	 */
	public static function get_link_markup( $preview, $popup_source = '' ) {
		$preview = is_string( $preview ) ? trim( $preview ) : '';

		if ( ! self::is_preview_available( $preview ) ) {
			return '';
		}

		$popup_source = is_string( $popup_source ) ? trim( $popup_source ) : '';
		if ( '' === $popup_source || ! self::is_preview_available( $popup_source ) ) {
			$popup_source = $preview;
		}

		$label = __( 'Show personalization', 'woo-product-personalizer' );

		return sprintf(
			'<div class="personalization-preview wpp-cart-personalization-preview"><a href="#wpp-cart-preview-popup" class="wpp-cart-preview-link" data-wpp-preview="%1$s" data-wpp-preview-full="%2$s">%3$s</a></div>',
			esc_attr( $preview ),
			esc_attr( $popup_source ),
			esc_html( $label )
		);
	}

	/**
	 * Markup when preview metadata exists but the file is gone.
	 *
	 * @return string
	 */
	public static function get_unavailable_markup() {
		return sprintf(
			'<div class="personalization-preview wpp-cart-personalization-preview wpp-cart-personalization-preview--unavailable"><span class="wpp-preview-unavailable">%s</span></div>',
			esc_html( self::get_unavailable_message() )
		);
	}

	/**
	 * Customer-facing preview block (link or unavailable notice).
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return string
	 */
	public static function render_customer_preview( $item ) {
		if ( ! $item instanceof \WC_Order_Item || 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
			return '';
		}

		$source = self::get_item_preview_source( $item );

		if ( self::is_preview_available( $source ) ) {
			return self::get_link_markup( $source );
		}

		if ( self::item_has_preview_meta( $item ) ) {
			return self::get_unavailable_markup();
		}

		return '';
	}

	/**
	 * Admin order item preview block (thumbnail or unavailable notice).
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return void
	 */
	public static function render_admin_preview( $item ) {
		if ( ! $item instanceof \WC_Order_Item || 'yes' !== $item->get_meta( '_wpp_personalized' ) ) {
			return;
		}

		$source = self::get_item_preview_source( $item );

		echo '<p class="wpp-order-item-meta__preview-label"><strong>' . esc_html__( 'Preview', 'woo-product-personalizer' ) . '</strong></p>';

		if ( self::is_preview_available( $source ) ) {
			printf(
				'<p class="wpp-order-item-meta__preview"><a href="%1$s" target="_blank" rel="noopener noreferrer"><img src="%1$s" alt="%2$s" style="max-width:220px;height:auto;border:1px solid #ddd;border-radius:4px;" /></a></p>',
				esc_url( $source ),
				esc_attr__( 'Personalization preview', 'woo-product-personalizer' )
			);
			return;
		}

		if ( self::item_has_preview_meta( $item ) ) {
			printf(
				'<p class="wpp-order-item-meta__preview wpp-order-item-meta__preview--unavailable"><em>%s</em></p>',
				esc_html( self::get_unavailable_message() )
			);
		}
	}

	/**
	 * Resolve a public asset URL to a local path when possible.
	 *
	 * @param string $url URL.
	 * @return string|false
	 */
	private static function url_to_local_path( $url ) {
		$upload = wp_upload_dir();

		if ( ! empty( $upload['baseurl'] ) && 0 === strpos( $url, $upload['baseurl'] ) ) {
			$rel  = ltrim( substr( $url, strlen( $upload['baseurl'] ) ), '/' );
			$path = trailingslashit( $upload['basedir'] ) . $rel;
			$path = strtok( $path, '?' );

			return $path;
		}

		if ( defined( 'WPP_PLUGIN_URL' ) && 0 === strpos( $url, WPP_PLUGIN_URL ) ) {
			$rel  = ltrim( substr( $url, strlen( WPP_PLUGIN_URL ) ), '/' );
			$path = WPP_PLUGIN_PATH . $rel;
			$path = strtok( $path, '?' );

			return $path;
		}

		return false;
	}
}
