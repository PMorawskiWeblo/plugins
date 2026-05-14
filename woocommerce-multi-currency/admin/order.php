<?php

/*
Class Name: WOOMULTI_CURRENCY_Admin_Order
Author: Andy Ha (support@villatheme.com)
Author URI: http://villatheme.com
Copyright 2015-2017 villatheme.com. All rights reserved.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Admin_Order {
	public $format;
	public $decimals;
	public $settings;
	public $admin_change_currency;
	public $order_currency;
	public $wmc_order_info;

	function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 1 ); //add_meta_boxes replace for admin_init
		add_filter( 'woocommerce_email_order_items_args', array( $this, 'get_format_setting' ) );
		add_filter( 'wc_price_args', array( $this, 'change_get_format_setting' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'currency_columns' ), 2 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'order_total_in_base_currency' ), 12 );
		add_action( 'woocommerce_order_actions_start', array( $this, 'order_meta_box_list_currencies' ) );

		if ( ! is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) ) {
			add_filter( 'woocommerce_get_formatted_order_total', array( $this, 'get_formatted_order_total' ), 10, 4 );
		}

		add_action( 'woocommerce_before_save_order_items', array( $this, 'before_save_order_items' ), 10, 2 ); //save shipping
		add_action( 'woocommerce_before_save_order_item', array( $this, 'save_order_item' ) );
		add_action( 'woocommerce_saved_order_items', array( $this, 'after_save_order_items' ), 10, 2 ); //save shipping

		/*Stripe*/
		add_filter( 'wc_stripe_hide_display_order_fee', array( $this, 'stripe_hide_fee_and_payout' ), 10, 2 );
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_fee' ) );
		add_filter( 'wc_stripe_hide_display_order_payout', array( $this, 'stripe_hide_fee_and_payout' ), 10, 2 );
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_payout' ), 20 );
	}

	public function stripe_hide_fee_and_payout( $hide, $order_id ) {
		if ( class_exists( 'WC_Stripe_Helper' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order_currency = $order->get_currency();
				$currency       = WC_Stripe_Helper::get_stripe_currency( $order );
				if ( $order_currency !== $currency ) {
					$wmc_order_info = get_post_meta( $order_id, 'wmc_order_info', true );
					if ( ! empty( $wmc_order_info[ $order_currency ]['rate'] ) && ! empty( $wmc_order_info[ $currency ]['rate'] ) ) {
						$hide = true;
					}
				}
			}
		}

		return $hide;
	}

	public function display_order_fee( $order_id ) {
		if ( apply_filters( 'wmc_stripe_hide_display_order_fee', false, $order_id ) ) {
			return;
		}
		if ( class_exists( 'WC_Stripe_Helper' ) ) {
			$order          = wc_get_order( $order_id );
			$order_currency = $order->get_currency();
			$fee            = WC_Stripe_Helper::get_stripe_fee( $order );
			$currency       = WC_Stripe_Helper::get_stripe_currency( $order );

			if ( ! $fee || ! $currency || $currency === $order_currency ) {
				return;
			}
			$wmc_order_info = get_post_meta( $order_id, 'wmc_order_info', true );
			if ( empty( $wmc_order_info[ $order_currency ]['rate'] ) || empty( $wmc_order_info[ $currency ]['rate'] ) ) {
				return;
			}
			$converted_fee = $fee / $wmc_order_info[ $currency ]['rate'];
			$converted_fee = $converted_fee * $wmc_order_info[ $order_currency ]['rate'];
			?>

            <tr>
                <td class="label stripe-fee">
					<?php echo wc_help_tip( __( 'This represents the fee Stripe collects for the transaction.', 'woocommerce-gateway-stripe' ) ); // wpcs: xss ok.
					?>
					<?php esc_html_e( 'Stripe Fee:', 'woocommerce-gateway-stripe' ); ?>
                </td>
                <td width="1%"></td>
                <td class="total">
                    -<?php echo wc_price( $fee, [ 'currency' => $currency ] ); // wpcs: xss ok.
					echo '(-' . wc_price( $converted_fee, [ 'currency' => $order_currency ] ) . ')';
					?>
                </td>
            </tr>

			<?php
		}
	}

	public function display_order_payout( $order_id ) {
		if ( apply_filters( 'wmc_stripe_hide_display_order_payout', false, $order_id ) ) {
			return;
		}
		if ( class_exists( 'WC_Stripe_Helper' ) ) {
			$order          = wc_get_order( $order_id );
			$order_currency = $order->get_currency();
			$net            = WC_Stripe_Helper::get_stripe_net( $order );
			$currency       = WC_Stripe_Helper::get_stripe_currency( $order );

			if ( ! $net || ! $currency || $currency === $order_currency ) {
				return;
			}
			$wmc_order_info = get_post_meta( $order_id, 'wmc_order_info', true );
			if ( empty( $wmc_order_info[ $order_currency ]['rate'] ) || empty( $wmc_order_info[ $currency ]['rate'] ) ) {
				return;
			}
			$converted_net = $net / $wmc_order_info[ $currency ]['rate'];
			$converted_net = $converted_net * $wmc_order_info[ $order_currency ]['rate'];
			?>

            <tr>
                <td class="label stripe-payout">
					<?php echo wc_help_tip( __( 'This represents the net total that will be credited to your Stripe bank account. This may be in the currency that is set in your Stripe account.', 'woocommerce-gateway-stripe' ) ); // wpcs: xss ok.
					?>
					<?php esc_html_e( 'Stripe Payout:', 'woocommerce-gateway-stripe' ); ?>
                </td>
                <td width="1%"></td>
                <td class="total">
					<?php echo wc_price( $net, [ 'currency' => $currency ] ); // wpcs: xss ok.
					echo '(' . wc_price( $converted_net, [ 'currency' => $order_currency ] ) . ')';
					?>
                </td>
            </tr>

			<?php
		}
	}

	/**
	 * Add metabox to order post
	 */
	public function add_metabox() {
		add_meta_box( 'wmc_order_metabox', __( 'Currency Information', 'woocommerce-multi-currency' ),
			array( $this, 'order_metabox' ), [ 'shop_order', 'shop_subscription' ], 'side', 'default' );
	}

	public function currency_columns( $col ) {
		global $post, $the_order;

		if ( $col == 'order_total' ) {
			if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
				$the_order = wc_get_order( $post->ID );
			}
			?>
            <div class="wmc-order-currency">
				<?php echo esc_html__( 'Currency: ', 'woocommerce-multi-currency' ) . get_post_meta( $the_order->get_id(), '_order_currency', true ); ?>
            </div>
			<?php
		}
	}

	public function order_total_in_base_currency( $col ) {
		global $post, $the_order;

		if ( $col == 'order_total' ) {
			if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
				$the_order = wc_get_order( $post->ID );
			}
			$order_currency = get_post_meta( $post->ID, '_order_currency', true );
			$wmc_order_info = get_post_meta( $post->ID, 'wmc_order_info', true );
			if ( is_array( $wmc_order_info ) && count( $wmc_order_info ) ) {
				foreach ( $wmc_order_info as $code => $currency_info ) {
					if ( isset( $currency_info['is_main'] ) && $currency_info['is_main'] == 1 && isset( $wmc_order_info[ $order_currency ] ) ) {
						if ( $order_currency != $code && floatval( $wmc_order_info[ $order_currency ]['rate'] ) ) {
							$price_in_base_currency = ( $the_order->get_total() - $the_order->get_total_refunded() ) / $wmc_order_info[ $order_currency ]['rate'];
							?>
                            <p style="color:red">
								<?php echo $code . ': ' ?>
                                <span>
                                    <?php echo wc_price( $price_in_base_currency, array(
	                                    'currency' => $code,
	                                    'decimals' => ! empty( $wmc_order_info[ $code ]['decimals'] ) ? (int) $wmc_order_info[ $code ]['decimals'] : 0
                                    ) ) ?>
                                </span>
                            </p>
							<?php
						}
						break;
					}
				}
			}

		}
	}

	/**
	 * @param $post
	 */
	public function order_metabox( $post ) {
		$order          = new WC_Order( $post->ID );
		$order_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		$wmc_order_info = get_post_meta( $order->get_id(), 'wmc_order_info', true );
		$has_info       = 1;
		if ( ! isset( $wmc_order_info ) || ! is_array( $wmc_order_info ) ) {
			$has_info = 0;
		}

		?>
        <div id="wmc_order_metabox">
			<?php if ( ! $has_info ) {
				$wmc_order_base_currency = $order_currency;
				$rate                    = 1;
			} else {
				foreach ( $wmc_order_info as $code => $currency_info ) {
					if ( isset( $currency_info['is_main'] ) && $currency_info['is_main'] == 1 ) {
						$wmc_order_base_currency = $code;
						break;
					}
				}

				$rate = $wmc_order_info[ $order_currency ]['rate'];
			}
			?>
            <div id="wmc_order_currency_text">
                <p>
					<?php esc_html_e( 'Currency', 'woocommerce-multi-currency' ); ?> :
                    <span><?php echo $order_currency; ?></span>
                </p>
            </div>
            <div id="wmc_order_base_currency">
                <p>
					<?php esc_html_e( 'Base on Currency', 'woocommerce-multi-currency' ); ?>
                    : <span><?php echo $wmc_order_base_currency; ?></span>
                </p>
            </div>
            <div id="wmc_order_base_currency">
                <p>
					<?php esc_html_e( 'Currency Rate', 'woocommerce-multi-currency' ); ?>
                    : <span><?php echo $rate; ?></span>
                </p>
            </div>
			<?php ?>
        </div>
		<?php
		do_action( 'wmc_after_currency_information', $post );
	}

	/**
	 * @param $formatted_total
	 * @param $order WC_Order
	 * @param $tax_display
	 * @param $display_refunded
	 *
	 * @return string
	 */
	public function get_formatted_order_total( $formatted_total, $order, $tax_display, $display_refunded ) {
		if ( ! get_post_meta( $order->get_id(), 'wmc_order_info', true ) ) {
			return $formatted_total;
		}
		$order_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		if ( ! isset( $wmc_order_info[ $order_currency ] ) ) {
			return $formatted_total;
		}
		$wmc_order_info  = get_post_meta( $order->get_id(), 'wmc_order_info', true );
		$total           = get_post_meta( $order->get_id(), '_order_total', true );
		$decimal         = intval( $wmc_order_info[ $order_currency ]['decimals'] );
		$formatted_total = wc_price( $total, array(
			'currency' => $order_currency,
			'decimals' => $decimal
		) );

		$order_total    = $order->get_total();
		$total_refunded = $order->get_total_refunded();
		$tax_string     = '';

		// Tax for inclusive prices.
		if ( wc_tax_enabled() && 'incl' === $tax_display ) {
			$tax_string_array = array();
			$tax_totals       = $order->get_tax_totals();

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $tax_totals as $code => $tax ) {
					$tax_amount         = ( $total_refunded && $display_refunded ) ? wc_price( WC_Tax::round( $tax->amount - $order->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), array(
						'currency' => $order->get_currency(),
						'decimals' => $decimal
					) ) : $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}
			} elseif ( ! empty( $tax_totals ) ) {
				$tax_amount         = ( $total_refunded && $display_refunded ) ? $order->get_total_tax() - $order->get_total_tax_refunded() : $order->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, array(
					'currency' => $order->get_currency(),
					'decimals' => $decimal
				) ), WC()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				/* translators: %s: taxes */
				$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) ) . '</small>';
			}
		}

		if ( $total_refunded && $display_refunded ) {
			$formatted_total = '<del>' . strip_tags( $formatted_total ) . '</del> <ins>' . wc_price( $order_total - $total_refunded, array(
					'currency' => $order->get_currency(),
					'decimals' => $decimal
				) ) . $tax_string . '</ins>';
		} else {
			$formatted_total .= $tax_string;
		}

		/**
		 * Filter WooCommerce formatted order total.
		 *
		 * @param string $formatted_total Total to display.
		 * @param WC_Order $order Order data.
		 * @param string $tax_display Type of tax display.
		 * @param bool $display_refunded If should include refunded value.
		 */

		return $formatted_total;
	}

	/**
	 * @param $arg
	 *
	 * @return mixed
	 */
	public function get_format_setting( $arg ) {
		$order_id   = $arg['order']->get_id();
		$order_info = get_post_meta( $order_id, 'wmc_order_info', true );
		if ( $order_info ) {
			$order_currency = $arg['order']->get_currency();
			$currency_pos   = isset( $order_info[ $order_currency ]['pos'] ) ? $order_info[ $order_currency ]['pos'] : '';
			if ( $currency_pos ) {
				switch ( $currency_pos ) {
					case 'left':
						$this->format = '%1$s%2$s';
						break;
					case 'right':
						$this->format = '%2$s%1$s';
						break;
					case 'left_space':
						$this->format = '%1$s&nbsp;%2$s';
						break;
					case 'right_space':
						$this->format = '%2$s&nbsp;%1$s';
						break;
				}
			}
			if ( isset( $order_info[ $order_currency ]['decimals'] ) ) {
				$this->decimals = intval( $order_info[ $order_currency ]['decimals'] );
			}
		}

		return $arg;
	}

	public function change_get_format_setting( $args ) {
		if ( $this->format ) {
			$args['price_format'] = $this->format;
		}
		if ( $this->decimals !== null ) {
			$args['decimals'] = $this->decimals;
		}

		return $args;
	}

	public function order_meta_box_list_currencies( $post_id ) {
		global $pagenow;
		if ( $pagenow === 'post-new.php' ) {
			return;
		}
		$order_currency = get_post_meta( $post_id, '_order_currency', true );

		$selected_currencies = $this->settings->get_param( 'currency' );
		if ( ! ( is_array( $selected_currencies ) && count( $selected_currencies ) ) ) {
			return;
		}
		ob_start();
		?>
        <li class="wide">
            <select class="wide" name="wmc_change_currency" style="width: 100%">
                <option value=""><?php esc_html_e( 'Change currency', 'woocommerce-multi-currency' ); ?></option>
				<?php
				foreach ( $selected_currencies as $currency ) {
					if ( $currency == $order_currency ) {
						continue;
					}
					echo "<option value='{$currency}'>{$currency}</option>";
				}
				?>
            </select>
            <p style="font-size: 0.98em;margin: 0;font-style: italic"><?php esc_html_e( "Note: Please run 'Recalculate' after changing currency", 'woocommerce-multi-currency' ); ?></p>
        </li>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function get_fixed_price( $id, $currency, $is_on_sale ) {
		$product_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $id, '_regular_price_wmcp', true ), true ) );
		$sale_price    = wmc_adjust_fixed_price( json_decode( get_post_meta( $id, '_sale_price_wmcp', true ), true ) );
		if ( isset( $product_price[ $currency ] ) && ! $is_on_sale ) {
			if ( $product_price[ $currency ] > 0 ) {
				return $product_price[ $currency ];
			}
		} elseif ( isset( $sale_price[ $currency ] ) ) {
			if ( $sale_price[ $currency ] > 0 ) {
				return $sale_price[ $currency ];
			}
		}

		return '';
	}

	/**
	 * @param $item WC_Order_Item
	 */
	public function save_order_item( $item ) {
		if ( ! $this->admin_change_currency ) {
			return;
		}
		$default_currency = $this->settings->get_default_currency();
		switch ( $item->get_type() ) {
			case 'line_item':
				$check_fixed_price = $this->settings->check_fixed_price();
				if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
					$price = '';
					/**
					 * &var WC_Order_Item_Product $item
					 */
					$old_total = $item->get_subtotal();
					$quantity  = $item->get_quantity();
					$product   = $item->get_product();
					/**
					 * @var $product WC_product
					 */
					$pid = $product->get_id();
					if ( $check_fixed_price ) {
						if ( $default_currency == $this->admin_change_currency ) {
							$price = $product->get_price( 'edit' );
						} else {
							$is_on_sale = $product->is_on_sale( 'edit' );
							$price      = $this->get_fixed_price( $pid, $this->admin_change_currency, $is_on_sale );
						}
						if ( $price && wc_tax_enabled() && wc_prices_include_tax() ) {
							$total_tax = $item->get_total_tax();
							$tax       = $total_tax / $old_total;
							$price     = $price / ( 1 + $tax );
						}
					}
					if ( ! $price ) {
						$price = $this->change_admin_order_price( $old_total, $default_currency );
					} else {
						$price = $price * $quantity;
					}
					$item->set_subtotal( $price );
					$item->set_total( $price );
				}
				break;
			case 'fee':
				$new_fee = $this->change_admin_order_price( $item->get_amount(), $default_currency );
				$item->set_amount( $new_fee );
				$new_subtotal = $this->change_admin_order_price( $item->get_total(), $default_currency );
				$item->set_total( $new_subtotal );
				break;
			default:
				if ( method_exists( $item, 'get_subtotal' ) ) {
					$new_subtotal = $this->change_admin_order_price( $item->get_subtotal(), $default_currency );
					$item->set_subtotal( $new_subtotal );
				}
				if ( method_exists( $item, 'get_total' ) ) {
					$new_subtotal = $this->change_admin_order_price( $item->get_total(), $default_currency );
					$item->set_total( $new_subtotal );
				}
		}

		$item->save();
	}


	public function change_admin_order_price( $price, $default_currency ) {
		$price               = wc_format_decimal( $price );
		$selected_currencies = $this->wmc_order_info;
		$order_currency      = $this->order_currency;
		$base_currency       = '';
		foreach ( $selected_currencies as $currency => $currency_data ) {
			if ( ! empty( $currency_data['is_main'] ) ) {
				$base_currency = $currency;
				break;
			}
		}
		if ( $base_currency ) {
			if ( $this->admin_change_currency === $base_currency ) {
				$rate = $selected_currencies[ $order_currency ]['rate'];
				if ( $rate ) {
					$price = $price / $rate;
				}
			} else {
				/*Convert to base currency if needed*/
				if ( $order_currency !== $base_currency ) {
					if ( isset( $selected_currencies[ $order_currency ]['rate'] ) ) {
						$rate = $selected_currencies[ $order_currency ]['rate'];
						if ( $rate ) {
							$price = $price / $rate;
						}
					}
				}
				/*convert to target currency*/
				$rate = $selected_currencies[ $this->admin_change_currency ]['rate'];
				if ( $rate ) {
					$price = $price * $rate;
				}
			}
		}

		return $price;
	}

	/**
	 * @param $order_id
	 * @param $items
	 */
	public function before_save_order_items( $order_id, $items ) {
		$new_currency             = ! empty( $_POST['wmc_change_currency'] ) ? sanitize_text_field( $_POST['wmc_change_currency'] ) : '';
		$old_currency             = get_post_meta( $order_id, '_order_currency', true );
		$list_selected_currencies = $this->settings->get_param( 'currency' );
		if ( ! $new_currency || ! in_array( $new_currency, $list_selected_currencies ) ) {
			return;
		}
		if ( $new_currency == $old_currency ) {
			return;
		}
		$wmc_order_info   = get_post_meta( $order_id, 'wmc_order_info', true );
		$default_currency = $this->settings->get_default_currency();
		if ( $wmc_order_info ) {
			if ( isset( $wmc_order_info[ $default_currency ] ) && $wmc_order_info[ $default_currency ]['is_main'] == 1 ) {
				$this->order_currency        = $old_currency;
				$this->admin_change_currency = $new_currency;
				$this->wmc_order_info        = $wmc_order_info;
			}
		} else {
			$this->order_currency                           = $old_currency;
			$this->admin_change_currency                    = $new_currency;
			$wmc_order_info                                 = $this->settings->get_list_currencies();
			$wmc_order_info[ $default_currency ]['is_main'] = 1;
			$this->wmc_order_info                           = $wmc_order_info;
		}
	}

	public function after_save_order_items( $order_id, $items ) {
		if ( isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'auto-draft' ) {
			/*set wmc_order_info when new order created by admin*/
			$wmc_order_info = get_post_meta( $order_id, 'wmc_order_info', true );
			if ( ! $wmc_order_info ) {
				$wmc_order_info                                 = $this->settings->get_list_currencies();
				$default_currency                               = $this->settings->get_default_currency();
				$wmc_order_info[ $default_currency ]['is_main'] = 1;
				update_post_meta( $order_id, 'wmc_order_info', $wmc_order_info );
			}
		}
		if ( ! $this->admin_change_currency ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		if ( isset( $items['shipping_method_id'] ) ) {
			$default_currency = $this->settings->get_default_currency();
			foreach ( $items['shipping_method_id'] as $item_id ) {
				$item = WC_Order_Factory::get_order_item( absint( $item_id ) );
				if ( ! $item ) {
					continue;
				}
				if ( isset( $items['shipping_cost'][ $item_id ] ) ) {
					$old_cost = $items['shipping_cost'][ $item_id ];
					$new_cost = $this->change_admin_order_price( $old_cost, $default_currency );
					$item->set_props( array( 'total' => $new_cost, ) );
				}
				$item->save();
			}
		}

		$coupons = $order->get_coupon_codes();
		if ( count( $coupons ) ) {
			foreach ( $coupons as $coupon_code ) {
				$coupon = new WC_Coupon( $coupon_code );
				if ( $coupon ) {
					$order->remove_coupon( $coupon_code );
					$used_by = $order->get_user_id();
					if ( ! $used_by ) {
						$used_by = $order->get_billing_email();
					}
					$coupon->decrease_usage_count( $used_by );
					$order->apply_coupon( $coupon );
				}
			}
		}

		update_post_meta( $order_id, '_order_currency', $this->admin_change_currency );
		if ( $this->wmc_order_info ) {
			update_post_meta( $order_id, 'wmc_order_info', $this->wmc_order_info );
		}
	}
}