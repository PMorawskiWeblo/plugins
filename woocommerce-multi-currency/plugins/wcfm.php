<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_Yith_Product_Bundles
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_WCFM {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();

		if ( $this->settings->get_enable() && $this->settings->check_fixed_price() ) {
			add_action( 'after_wcfm_products_manage_pricing_fields', array( $this, 'simple_price_input' ) );
			add_action( 'after_wcfm_products_manage_meta_save', array( $this, 'save_meta_simple_product' ), 10, 2 );

			add_filter( 'wcfm_product_manage_fields_variations', array( $this, 'variable_price_input' ), 10, 7 );
			add_filter( 'wcfm_variation_edit_data', array( $this, 'variation_edit_data' ), 10, 4 );
			add_action( 'after_wcfm_product_variation_meta_save', array( $this, 'save_meta_variation_product' ), 10, 4 );

		}
	}

	/**
	 * Simple subscription
	 *
	 * @param $price
	 *
	 * @return mixed
	 */
	public function simple_price_input( $pid ) {
		$currencies    = $this->settings->get_currencies();
		$regular_price = wc_format_decimal( json_decode( get_post_meta( $pid, '_regular_price_wmcp', true ), true ) );
		$sale_price    = wc_format_decimal( json_decode( get_post_meta( $pid, '_sale_price_wmcp', true ), true ) );
		foreach ( $currencies as $currency ) {
			if ( $currency != $this->settings->get_default_currency() ) {
				?>
                <div style="border-left: 5px solid #f78080;" class="wcfm-text wcfm_ele  wcfm_half_ele simple external">
                    <p class="form-field ">
                        <label for="_regular_price_wmcp_<?php esc_attr_e( $currency ); ?>"><?php echo __( 'Regular Price', 'woocommerce-multi-currency' ) . ' (' . $currency . ')'; ?></label>
                        <input id="_regular_price_wmcp_<?php esc_attr_e( $currency ); ?>" class="short wc_input_price"
                               type="text"
                               value="<?php ( isset( $regular_price[ $currency ] ) ) ? esc_attr_e( $regular_price[ $currency ] ) : esc_attr_e( '' ); ?>"
                               name="_regular_price_wmcp[<?php esc_attr_e( $currency ); ?>]">
                    </p>
                    <p class="form-field ">
                        <label for="_sale_price_wmcp_<?php esc_attr_e( $currency ); ?>"><?php echo __( 'Sale Price', 'woocommerce-multi-currency' ) . ' (' . $currency . ')'; ?></label>
                        <input id="_sale_price_wmcp_<?php esc_attr_e( $currency ); ?>" class="short wc_input_price"
                               type="text"
                               value="<?php ( isset( $sale_price[ $currency ] ) ) ? esc_attr_e( $sale_price[ $currency ] ) : esc_attr_e( '' ); ?>"
                               name="_sale_price_wmcp[<?php esc_attr_e( $currency ); ?>]">
                    </p>
                </div>
				<?php
			}
		}
		wp_nonce_field( 'wmc_save_simple_product_currency', '_wmc_nonce' );
	}

	public function save_meta_simple_product( $post_id, $wcfm_products_manage_form_data ) {
		/*Check send from product edit page*/
		if ( ! isset( $wcfm_products_manage_form_data['_wmc_nonce'] ) || ! wp_verify_nonce( $wcfm_products_manage_form_data['_wmc_nonce'], 'wmc_save_simple_product_currency' ) ) {
			return;
		}

		if ( isset( $wcfm_products_manage_form_data['_regular_price_wmcp'] ) ) {
			$_regular_price_wmcp = wmc_adjust_fixed_price( wc_clean( $wcfm_products_manage_form_data['_regular_price_wmcp'] ) );
			update_post_meta( $post_id, '_regular_price_wmcp', json_encode( $_regular_price_wmcp ) );
		}

		if ( isset( $wcfm_products_manage_form_data['_sale_price_wmcp'] ) && ( isset( $wcfm_products_manage_form_data['sale_price'] ) && $wcfm_products_manage_form_data['sale_price'] ) ) {
			$_sale_price_wmcp = wmc_adjust_fixed_price( wc_clean( $wcfm_products_manage_form_data['_sale_price_wmcp'] ) );
			update_post_meta( $post_id, '_sale_price_wmcp', json_encode( $_sale_price_wmcp ) );
		} else {
			update_post_meta( $post_id, '_sale_price_wmcp', '' );
		}

		$date_to = isset( $wcfm_products_manage_form_data['sale_date_upto'] ) ? wc_clean( $wcfm_products_manage_form_data['sale_date_upto'] ) : '';

		if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
			update_post_meta( $post_id, '_sale_price_wmcp', '' );
		}
	}

	public function variable_price_input( $options, $variations, $variation_shipping_option_array, $variation_tax_classes_options, $products_array, $product_id, $product_type ) {
		$selected_currencies = $this->settings->get_currencies();
		$default_currency    = $this->settings->get_default_currency();

		$options["wcfm_element_breaker_variation_3"] = array( 'type' => 'html', 'value' => '<div class="wcfm-cearfix"></div>' );

		foreach ( $selected_currencies as $code ) {
			if ( $default_currency === $code ) {
				continue;
			}
			$options["regular_price_wmc][{$code}"] = array(
				'label'       => __( 'Regular Price', 'wc-frontend-manager' ) . '(' . get_woocommerce_currency_symbol( $code ) . ')',
				'type'        => 'text',
				'class'       => 'wcfm-text wcfm_ele wcfm_non_negative_input wcfm_half_ele variable variable-subscription pw-gift-card',
				'label_class' => 'wcfm_title wcfm_ele wcfm_half_ele_title variable variable-subscription pw-gift-card',
			);
			$options["sale_price_wmc][{$code}"]    = array(
				'label'       => __( 'Sale Price', 'wc-frontend-manager' ) . '(' . get_woocommerce_currency_symbol( $code ) . ')',
				'type'        => 'text',
				'class'       => 'wcfm-text wcfm_ele wcfm_non_negative_input wcfm_half_ele variable variable-subscription pw-gift-card',
				'label_class' => 'wcfm_title wcfm_ele wcfm_half_ele_title variable variable-subscription pw-gift-card',
			);
		}

		return $options;
	}

	public function variation_edit_data( $variations, $variation_id, $variation_id_key, $product_id ) {

		$regular_price       = wc_format_decimal( json_decode( get_post_meta( $variation_id, '_regular_price_wmcp', true ), true ) );
		$sale_price          = wc_format_decimal( json_decode( get_post_meta( $variation_id, '_sale_price_wmcp', true ), true ) );
		$selected_currencies = $this->settings->get_currencies();

		foreach ( $selected_currencies as $code ) {
			if ( isset( $regular_price[ $code ] ) ) {
				$variations[ $variation_id_key ]["regular_price_wmc][{$code}"] = $regular_price[ $code ];
			}
			if ( isset( $sale_price[ $code ] ) ) {
				$variations[ $variation_id_key ]["sale_price_wmc][{$code}"] = $sale_price[ $code ];
			}
		}

		return $variations;
	}

	public function save_meta_variation_product( $new_product_id, $variation_id, $variations, $wcfm_products_manage_form_data ) {

		if ( isset( $variations['regular_price_wmc'] ) ) {
			$_regular_price_wmcp = wmc_adjust_fixed_price( wc_clean( $variations['regular_price_wmc'] ) );
			update_post_meta( $variation_id, '_regular_price_wmcp', json_encode( $_regular_price_wmcp ) );
		}

		if ( isset( $variations['sale_price_wmc'] ) ) {
			$_sale_price_wmcp = wmc_adjust_fixed_price( wc_clean( $variations['sale_price_wmc'] ) );
			update_post_meta( $variation_id, '_sale_price_wmcp', json_encode( $_sale_price_wmcp ) );
		}
	}
}