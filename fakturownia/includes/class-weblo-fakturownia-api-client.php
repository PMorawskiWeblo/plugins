<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Weblo_Fakturownia_API_Client {

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var string
	 */
	protected $api_token;

	/**
	 * @var string|null
	 */
	protected $department_id;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param string      $domain
	 * @param string      $api_token
	 * @param string|null $department_id
	 * @param array       $settings Dodatkowe ustawienia integracji (np. notes, send_by_email).
	 */
	public function __construct( $domain, $api_token, $department_id = null, $settings = array() ) {
		$domain = trim( (string) $domain );
		$domain = preg_replace( '#^https?://#', '', $domain );

		$this->domain        = $domain;
		$this->api_token     = (string) $api_token;
		$this->department_id = ( null === $department_id || '' === (string) $department_id ) ? null : (string) $department_id;
		$this->settings      = is_array( $settings ) ? $settings : array();
	}

	/**
	 * @return array{success:bool,message?:string,error?:mixed,http_code?:int}
	 */
	public function test_connection() {
		if ( '' === $this->domain || '' === $this->api_token ) {
			return array(
				'success' => false,
				'message' => __( 'Please fill in the domain and API token.', 'weblo-fakturownia' ),
			);
		}

		$res = $this->request(
			'GET',
			'/invoices.json',
			array(
				'period' => 'this_month',
				'page'   => 1,
			)
		);

		if ( ! $res['success'] ) {
			return array(
				'success'   => false,
				'message'   => sprintf(
					/* translators: %s: error details from API */
					__( 'Error connecting to Fakturownia: %s', 'weblo-fakturownia' ),
					isset( $res['error'] ) ? wp_json_encode( $res['error'] ) : __( 'Unknown error.', 'weblo-fakturownia' )
				),
				'error'     => $res['error'] ?? null,
				'http_code' => $res['http_code'] ?? null,
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Connection to Fakturownia works correctly.', 'weblo-fakturownia' ),
		);
	}

	/**
	 * @param int|string $invoice_id
	 * @return array{success:bool,data?:mixed,error?:mixed,http_code?:int}
	 */
	public function get_invoice( $invoice_id ) {
		$invoice_id = (string) $invoice_id;
		if ( '' === $invoice_id ) {
			return array( 'success' => false, 'error' => __( 'Missing invoice_id.', 'weblo-fakturownia' ) );
		}

		return $this->request( 'GET', '/invoices/' . rawurlencode( $invoice_id ) . '.json' );
	}

	/**
	 * Wysyła fakturę do klienta e-mailem.
	 *
	 * Endpoint wg dokumentacji: POST /invoices/:id/send_by_email.json
	 *
	 * @param int|string $invoice_id
	 * @return array{success:bool,data?:mixed,error?:mixed,http_code?:int}
	 */
	public function send_invoice_by_email( $invoice_id ) {
		$invoice_id = (string) $invoice_id;
		if ( '' === $invoice_id ) {
			return array( 'success' => false, 'error' => __( 'Missing invoice_id.', 'weblo-fakturownia' ) );
		}

		return $this->request( 'POST', '/invoices/' . rawurlencode( $invoice_id ) . '/send_by_email.json' );
	}

	/**
	 * Tworzy fakturę korygującą na podstawie zwrotu (refund) w WooCommerce.
	 *
	 * @param int         $order_id
	 * @param int|null    $refund_id
	 * @param string|null $issue_date_override Format Y-m-d. Jeśli null, użyje daty modyfikacji/refund.
	 * @param array       $options
	 * @return array{success:bool,invoice_id?:mixed,invoice_number?:mixed,data?:mixed,error?:mixed,http_code?:int}
	 */
	public function create_correction( $order_id, $refund_id = null, $issue_date_override = null, $options = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array( 'success' => false, 'error' => __( 'Order not found.', 'weblo-fakturownia' ) );
		}

		$from_invoice_id = (string) $order->get_meta( '_weblo_fakturownia_invoice_id', true );
		if ( '' === $from_invoice_id ) {
			return array( 'success' => false, 'error' => __( 'Missing base invoice ID (_weblo_fakturownia_invoice_id).', 'weblo-fakturownia' ) );
		}

		// Pobierz fakturę bazową, żeby zbudować poprawne correction_before/after.
		$base_invoice = $this->get_invoice( $from_invoice_id );
		if ( empty( $base_invoice['success'] ) ) {
			$http_code = isset( $base_invoice['http_code'] ) ? (int) $base_invoice['http_code'] : 0;
			$message   = __( 'Failed to fetch base invoice from API.', 'weblo-fakturownia' );
			if ( 404 === $http_code ) {
				$message = __( 'Base invoice not found in Fakturownia (404). It may have been removed, belongs to a different company/department, or the domain/token are incorrect.', 'weblo-fakturownia' );
			}

			return array(
				'success' => false,
				'error'   => array(
					'message'     => $message,
					'invoice_id'  => $from_invoice_id,
					'http_code'   => $http_code ?: null,
					'details'     => $base_invoice['error'] ?? null,
				),
			);
		}

		$base_data = $base_invoice['data'] ?? array();
		$base_inv  = isset( $base_data['invoice'] ) && is_array( $base_data['invoice'] ) ? $base_data['invoice'] : $base_data;
		$base_positions = isset( $base_inv['positions'] ) && is_array( $base_inv['positions'] ) ? $base_inv['positions'] : array();
		$order_items_snapshot  = array();
		$refund_items_snapshot = array();
		$base_positions_snapshot = array();

		// Mapa pozycji bazowych po nazwie (prosty, ale skuteczny start).
		$base_by_name = array();
		foreach ( $base_positions as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$name = isset( $p['name'] ) ? (string) $p['name'] : '';
			if ( '' === $name ) {
				continue;
			}
			$base_by_name[ $name ] = $p;
			$base_positions_snapshot[] = array(
				'name'              => $name,
				'quantity'          => isset( $p['quantity'] ) ? (float) $p['quantity'] : null,
				'quantity_unit'     => isset( $p['quantity_unit'] ) ? (string) $p['quantity_unit'] : null,
				'total_price_gross' => isset( $p['total_price_gross'] ) ? $p['total_price_gross'] : null,
				'total_price_net'   => isset( $p['total_price_net'] ) ? $p['total_price_net'] : null,
				'price_net'         => isset( $p['price_net'] ) ? $p['price_net'] : null,
				'tax'               => isset( $p['tax'] ) ? $p['tax'] : null,
			);
		}

		$issue_date = current_time( 'Y-m-d' );
		if ( is_string( $issue_date_override ) && '' !== trim( $issue_date_override ) ) {
			$issue_date = trim( $issue_date_override );
		}

		$refund = null;
		if ( $refund_id ) {
			$refund = wc_get_order( $refund_id );
		}

		$correction_reason = isset( $options['reason'] ) ? (string) $options['reason'] : '';
		if ( '' === trim( $correction_reason ) ) {
			$correction_reason = __( 'Order refund', 'weblo-fakturownia' );
		}

		// Fakturownia API expects correction_before/correction_after for each position.
		// We change only those affected by the refund (and optionally shipping).
		$normalize = static function ( $s ) {
			$s = (string) $s;
			$s = html_entity_decode( $s, ENT_QUOTES, 'UTF-8' );
			$s = trim( preg_replace( '/\s+/u', ' ', $s ) );
			return mb_strtolower( $s, 'UTF-8' );
		};

		$refund_qty_by_name = array();
		if ( $refund && $refund instanceof WC_Order_Refund ) {
			foreach ( (array) $refund->get_items( 'line_item' ) as $ri ) {
				$n = (string) $ri->get_name();
				if ( '' === $n ) {
					continue;
				}
				$q = abs( (float) $ri->get_quantity() );
				if ( $q <= 0 ) {
					$q = 0;
				}
				$key = $normalize( $n );
				$refund_qty_by_name[ $key ] = ( $refund_qty_by_name[ $key ] ?? 0 ) + $q;
				$refund_items_snapshot[] = array(
					'name'         => $n,
					'quantity'     => $q,
					'total_net'    => abs( (float) $ri->get_total() ),
					'total_tax'    => abs( (float) $ri->get_total_tax() ),
					'total_gross'  => abs( (float) $ri->get_total() + (float) $ri->get_total_tax() ),
				);
			}
		}

		foreach ( (array) $order->get_items( 'line_item' ) as $oi_dbg ) {
			if ( ! $oi_dbg instanceof WC_Order_Item_Product ) {
				continue;
			}
			$qty_ordered = (float) $oi_dbg->get_quantity();
			$item_id_dbg = method_exists( $oi_dbg, 'get_id' ) ? (int) $oi_dbg->get_id() : 0;
			$qty_refunded_dbg = ( $item_id_dbg > 0 && method_exists( $order, 'get_qty_refunded_for_item' ) ) ? abs( (float) $order->get_qty_refunded_for_item( $item_id_dbg ) ) : 0.0;
			$order_items_snapshot[] = array(
				'name'          => (string) $oi_dbg->get_name(),
				'qty_ordered'   => $qty_ordered,
				'qty_refunded'  => $qty_refunded_dbg,
				'qty_remaining' => max( 0.0, $qty_ordered - $qty_refunded_dbg ),
				'total_net'     => (float) $oi_dbg->get_total(),
				'total_tax'     => (float) $oi_dbg->get_total_tax(),
				'total_gross'   => (float) $oi_dbg->get_total() + (float) $oi_dbg->get_total_tax(),
			);
		}

		$positions        = array();
		$correction_mode  = $this->get_correction_mode( $options );

		// Optional: override shipping gross amount on correction.
		$shipping_mode   = isset( $options['shipping_mode'] ) ? (string) $options['shipping_mode'] : 'as_order';
		$shipping_amount = isset( $options['shipping_amount'] ) ? (float) $options['shipping_amount'] : 0.0;
		$shipping_name   = __( 'Shipping', 'weblo-fakturownia' );
		$shipping_key    = $normalize( $shipping_name );

		// WooCommerce allows amount-only refunds (no line items). Fallback to refunded quantities from the parent order items.
		if ( empty( $refund_qty_by_name ) ) {
			foreach ( (array) $order->get_items( 'line_item' ) as $oi ) {
				if ( ! $oi instanceof WC_Order_Item_Product ) {
					continue;
				}
				$item_id = method_exists( $oi, 'get_id' ) ? (int) $oi->get_id() : 0;
				if ( $item_id <= 0 || ! method_exists( $order, 'get_qty_refunded_for_item' ) ) {
					continue;
				}
				$ref_qty = abs( (float) $order->get_qty_refunded_for_item( $item_id ) );
				if ( $ref_qty <= 0 ) {
					continue;
				}
				$n = (string) $oi->get_name();
				if ( '' === $n ) {
					continue;
				}
				$key = $normalize( $n );
				$refund_qty_by_name[ $key ] = ( $refund_qty_by_name[ $key ] ?? 0 ) + $ref_qty;
			}
		}

		// Last resort (matches old plugin behavior): for amount-only refunds where WooCommerce doesn't expose item quantities,
		// assume a full refund of all base invoice product positions. This is imperfect for partial amount refunds,
		// but avoids "no changes" and lets the user generate a correction like the old implementation.
		$used_full_refund_fallback = false;
		if ( empty( $refund_qty_by_name ) && $refund && $refund instanceof WC_Order_Refund ) {
			foreach ( (array) $base_positions as $bp ) {
				if ( ! is_array( $bp ) ) {
					continue;
				}
				$n = isset( $bp['name'] ) ? (string) $bp['name'] : '';
				if ( '' === $n ) {
					continue;
				}
				$q = isset( $bp['quantity'] ) ? (float) $bp['quantity'] : 0.0;
				if ( $q <= 0 ) {
					continue;
				}
				$key = $normalize( $n );
				$refund_qty_by_name[ $key ] = max( (float) ( $refund_qty_by_name[ $key ] ?? 0.0 ), $q );
				$used_full_refund_fallback  = true;
			}
		}

		$changed_any = false;
		$position_diffs = array();
		$expected_correction_summary = array();

		$build_snapshot = static function ( $p ) {
			if ( ! is_array( $p ) ) {
				return array();
			}

			$out = array();
			// Common position fields that are safe/useful for corrections.
			$keys = array(
				'name',
				'quantity',
				'quantity_unit',
				'tax',
				'total_price_gross',
				'total_price_net',
				'price_net',
				'pkwiu',
				'gtu',
				'discount_percent',
				'discount',
			);
			foreach ( $keys as $k ) {
				if ( array_key_exists( $k, $p ) ) {
					// Avoid sending nulls to API (some validations choke on explicit null).
					if ( null === $p[ $k ] ) {
						continue;
					}
					$out[ $k ] = $p[ $k ];
				}
			}

			// Normalize types.
			if ( isset( $out['quantity'] ) ) {
				$out['quantity'] = (float) $out['quantity'];
			}
			if ( isset( $out['tax'] ) ) {
				$out['tax'] = (int) $out['tax'];
			}
			if ( isset( $out['total_price_gross'] ) ) {
				$out['total_price_gross'] = is_numeric( $out['total_price_gross'] ) ? (float) $out['total_price_gross'] : $out['total_price_gross'];
			}
			if ( isset( $out['total_price_net'] ) ) {
				$out['total_price_net'] = is_numeric( $out['total_price_net'] ) ? (float) $out['total_price_net'] : $out['total_price_net'];
			}
			if ( isset( $out['price_net'] ) ) {
				$out['price_net'] = is_numeric( $out['price_net'] ) ? (float) $out['price_net'] : $out['price_net'];
			}

			return $out;
		};

		// Unit net price from WooCommerce order items (preferred source for legal/accounting consistency).
		$order_unit_net_by_name = array();
		foreach ( (array) $order->get_items( 'line_item' ) as $oi_src ) {
			if ( ! $oi_src instanceof WC_Order_Item_Product ) {
				continue;
			}
			$n_src = (string) $oi_src->get_name();
			if ( '' === $n_src ) {
				continue;
			}
			$k_src = $normalize( $n_src );
			$q_src = (float) $oi_src->get_quantity();
			if ( $q_src <= 0 ) {
				continue;
			}
			$net_src = (float) $oi_src->get_total();
			$order_unit_net_by_name[ $k_src ] = (float) wc_format_decimal( $net_src / $q_src, 2 );
		}

		foreach ( $base_positions as $base_p ) {
			if ( ! is_array( $base_p ) ) {
				continue;
			}
			$name = isset( $base_p['name'] ) ? (string) $base_p['name'] : '';
			if ( '' === $name ) {
				continue;
			}
			$name_key = $normalize( $name );

			$base_qty  = isset( $base_p['quantity'] ) ? (float) $base_p['quantity'] : 0.0;
			$base_gross = isset( $base_p['total_price_gross'] ) ? $base_p['total_price_gross'] : '';
			$base_tax  = isset( $base_p['tax'] ) ? (int) $base_p['tax'] : 0;

			$refund_qty = (float) ( $refund_qty_by_name[ $name_key ] ?? 0.0 );
			$after_qty  = $base_qty - $refund_qty;
			if ( $after_qty < 0 ) {
				return array(
					'success' => false,
					'error'   => array(
						'message' => __( 'Invalid correction: quantity after correction is below zero.', 'weblo-fakturownia' ),
						'item'    => $name,
						'before'  => $base_qty,
						'refund'  => $refund_qty,
					),
				);
			}

			$before = $build_snapshot( $base_p );
			$before['name'] = $name;
			if ( ! isset( $before['quantity'] ) ) {
				$before['quantity'] = $base_qty;
			}
			if ( ! isset( $before['tax'] ) ) {
				$before['tax'] = $base_tax;
			}

			$after = $before;
			$after['quantity'] = $after_qty;

			$base_gross_f = is_numeric( $base_gross ) ? (float) $base_gross : 0.0;
			$base_net_f   = isset( $base_p['total_price_net'] ) && is_numeric( $base_p['total_price_net'] ) ? (float) $base_p['total_price_net'] : null;
			$unit_gross   = ( $base_qty > 0 ) ? ( $base_gross_f / $base_qty ) : 0.0;
			$unit_net     = null;
			if ( isset( $order_unit_net_by_name[ $name_key ] ) ) {
				$unit_net = (float) $order_unit_net_by_name[ $name_key ];
			} elseif ( isset( $base_p['price_net'] ) && is_numeric( $base_p['price_net'] ) ) {
				$unit_net = (float) $base_p['price_net'];
			} elseif ( null !== $base_net_f && $base_qty > 0 ) {
				$unit_net = (float) $base_net_f / $base_qty;
			} elseif ( $base_tax >= 0 && $unit_gross > 0 ) {
				$unit_net = $unit_gross / ( 1 + ( (float) $base_tax / 100 ) );
			}

			$after_gross = max( 0.0, $unit_gross * $after_qty );
			$after['total_price_gross'] = (float) wc_format_decimal( $after_gross, 2 );
			if ( null !== $unit_net ) {
				$after['price_net'] = (float) wc_format_decimal( $unit_net, 2 );
				$after['total_price_net'] = (float) wc_format_decimal( max( 0.0, $unit_net * $after_qty ), 2 );
			}

			// If this is the shipping position, optionally adjust gross amount using before/after.
			if ( $name_key === $shipping_key && $refund_qty > 0 && 'as_order' !== $shipping_mode ) {
				$after_gross  = $base_gross_f;
				$after_net    = ( null !== $base_net_f ) ? $base_net_f : null;
				if ( 'do_0' === $shipping_mode ) {
					$after_gross = 0.0;
					if ( null !== $after_net ) {
						$after_net = 0.0;
					}
				} elseif ( 'custom_amount' === $shipping_mode ) {
					$after_gross = (float) $shipping_amount;
					if ( null !== $after_net ) {
						$after_net = (float) $shipping_amount;
					}
				}
				$after['total_price_gross'] = (float) wc_format_decimal( $after_gross, 2 );
				if ( null !== $after_net ) {
					$after['total_price_net'] = (float) wc_format_decimal( $after_net, 2 );
				}
			}

			$after_gross_f = isset( $after['total_price_gross'] ) && is_numeric( $after['total_price_gross'] ) ? (float) $after['total_price_gross'] : 0.0;
			$unit_before = ( $base_qty > 0 ) ? ( $base_gross_f / $base_qty ) : 0.0;
			$unit_after  = ( $after_qty > 0 ) ? ( $after_gross_f / $after_qty ) : 0.0;

			$missing = array();
			foreach ( array( 'name', 'quantity', 'tax', 'total_price_gross' ) as $req_k ) {
				if ( ! array_key_exists( $req_k, $before ) || '' === (string) $before[ $req_k ] ) {
					$missing[] = $req_k;
				}
			}

			// Delta values.
			$delta_qty   = $after_qty - $base_qty;
			$delta_gross = $after_gross_f - $base_gross_f;

			$position_diffs[] = array(
				'name'         => $name,
				'refund_qty'    => $refund_qty,
				'before_qty'    => $base_qty,
				'after_qty'     => $after_qty,
				'before_gross'  => $base_gross_f,
				'after_gross'   => $after_gross_f,
				'unit_before'   => $unit_before,
				'unit_after'    => $unit_after,
				'tax'          => $base_tax,
				'missing'      => $missing,
				'delta_qty'    => $delta_qty,
				'delta_gross'  => $delta_gross,
			);
			$expected_correction_summary[] = array(
				'name'               => $name,
				'before_qty'         => $base_qty,
				'refunded_qty'       => $refund_qty,
				'after_qty'          => $after_qty,
				'before_gross_total' => $base_gross_f,
				'after_gross_total'  => $after_gross_f,
				'delta_gross_total'  => $delta_gross,
				'unit_gross_before'  => $unit_before,
				'unit_gross_after'   => $unit_after,
			);

			$is_shipping_special = ( $name_key === $shipping_key && $refund_qty > 0 && 'as_order' !== $shipping_mode );

			$main_qty = (float) $after_qty;
			if ( $is_shipping_special && abs( $main_qty ) < 0.000001 && abs( $delta_gross ) > 0.000001 ) {
				$main_qty = 1.0;
			}

			$pos = array(
				'kind'              => 'correction',
				'name'              => $name,
				'quantity'          => $main_qty,
				'quantity_unit'     => isset( $before['quantity_unit'] ) ? (string) $before['quantity_unit'] : 'pcs',
				'total_price_gross' => (float) wc_format_decimal( $after_gross_f, 2 ),
				'tax'               => (int) $base_tax,
				'correction_before_attributes' => array_merge(
					$before,
					array(
						'kind'              => 'correction_before',
						'quantity'          => (string) $base_qty,
						'total_price_gross' => wc_format_decimal( $base_gross_f, 2 ),
					)
				),
				'correction_after_attributes'  => array_merge(
					$after,
					array(
						'kind'              => 'correction_after',
						'quantity'          => (string) $after_qty,
						'total_price_gross' => wc_format_decimal( $after_gross_f, 2 ),
					)
				),
			);

			// keep a flat array to preserve key ordering in logs
			$out_pos = array(
				'kind'              => $pos['kind'],
				'name'              => $pos['name'],
				'quantity'          => $pos['quantity'],
				'quantity_unit'     => $pos['quantity_unit'],
				'total_price_gross' => $pos['total_price_gross'],
				'tax'               => $pos['tax'],
			);
			$out_pos['correction_before_attributes'] = $pos['correction_before_attributes'];
			$out_pos['correction_after_attributes']  = $pos['correction_after_attributes'];
			$include_line = true;
			if ( 'difference' === $correction_mode && abs( $delta_qty ) < 0.000001 && abs( $delta_gross ) < 0.000001 ) {
				$include_line = false;
			}
			if ( $include_line ) {
				$positions[] = $out_pos;
			}

			// Build full correction document (all lines), but ensure at least one line has a real change.
			if ( abs( $delta_qty ) > 0.000001 || abs( $delta_gross ) > 0.000001 ) {
				$changed_any = true;
			}
		}

		if ( empty( $positions ) || ! $changed_any ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'  => 'create_correction_no_changes',
						'reason' => __( 'No matching refunded items found on base invoice positions.', 'weblo-fakturownia' ),
					),
					'api'
				);
			}
			return array(
				'success' => false,
				'error'   => array(
					'message' => __( 'No matching refunded items found on base invoice positions.', 'weblo-fakturownia' ),
				),
			);
		}

		// Dane nabywcy (API potrafi tego wymagać na korekcie).
		$buyer_name  = trim( $order->get_formatted_billing_full_name() );
		$buyer_email = (string) $order->get_billing_email();
		$buyer_street  = (string) $order->get_billing_address_1();
		$buyer_city    = (string) $order->get_billing_city();
		$buyer_post    = (string) $order->get_billing_postcode();
		$buyer_country = (string) $order->get_billing_country();

		$payload = array(
			'invoice' => array(
				'kind'              => 'correction',
				'status'            => 'issued',
				'issue_date'        => $issue_date,
				'sell_date'         => $issue_date,
				'correction_reason' => $correction_reason,
				'invoice_id'        => $from_invoice_id,
				'from_invoice_id'   => $from_invoice_id,
				'buyer_name'        => $buyer_name,
				'buyer_email'       => $buyer_email,
				'positions'         => $positions,
			),
		);

		if ( '' !== $buyer_street ) {
			$payload['invoice']['buyer_street'] = $buyer_street;
		}
		if ( '' !== $buyer_city ) {
			$payload['invoice']['buyer_city'] = $buyer_city;
		}
		if ( '' !== $buyer_post ) {
			$payload['invoice']['buyer_post_code'] = $buyer_post;
		}
		if ( '' !== $buyer_country ) {
			$payload['invoice']['buyer_country'] = $buyer_country;
		}

		$res = $this->request( 'POST', '/invoices.json', array(), $payload );
		if ( ! $res['success'] ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'event'     => 'create_correction_api_failed',
						'http_code' => $res['http_code'] ?? null,
						'error'     => $res['error'] ?? null,
						'raw_body_len' => isset( $res['raw_body'] ) && is_string( $res['raw_body'] ) ? strlen( $res['raw_body'] ) : null,
					),
					'api'
				);
			}
			return $res;
		}

		$data = $res['data'] ?? array();
		$invoice_id     = $data['invoice']['id'] ?? ( $data['id'] ?? null );
		$invoice_number = $data['invoice']['number'] ?? ( $data['number'] ?? null );

		return array(
			'success'        => true,
			'invoice_id'     => $invoice_id,
			'invoice_number' => $invoice_number,
			'data'           => $data,
		);
	}

	/**
	 * @param int $order_id
	 * @return array{success:bool,invoice_id?:mixed,invoice_number?:mixed,data?:mixed,error?:mixed,http_code?:int}
	 */
	/**
	 * @param int         $order_id
	 * @param string|null $issue_date_override Format Y-m-d. Gdy null, używa daty utworzenia zamówienia.
	 * @return array{success:bool,invoice_id?:mixed,invoice_number?:mixed,data?:mixed,error?:mixed,http_code?:int}
	 */
	public function create_invoice( $order_id, $issue_date_override = null ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array( 'success' => false, 'error' => __( 'Order not found.', 'weblo-fakturownia' ) );
		}

		$order_date = $order->get_date_created();
		$issue_date = $order_date ? $order_date->date_i18n( 'Y-m-d' ) : gmdate( 'Y-m-d' );
		if ( is_string( $issue_date_override ) && '' !== trim( $issue_date_override ) ) {
			$issue_date = trim( $issue_date_override );
		}

		$items = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$qty       = (float) $item->get_quantity();
			$net_total = (float) $item->get_total();
			$tax_total = (float) $item->get_total_tax();

			$gross_total = $net_total + $tax_total;
			$vat_rate    = $this->guess_vat_rate_from_tax( $net_total, $tax_total );

			// Fakturownia expects total_price_gross as the total value for the position (not unit price).
			$qty_out = $qty > 0 ? $qty : 1;

			$items[] = array(
				'name'      => $item->get_name(),
				'quantity'  => $qty_out,
				// Unit of measure (prevents "(brak)" in Fakturownia UI).
				'quantity_unit'    => 'pcs',
				'total_price_gross' => wc_format_decimal( $gross_total, 2 ),
				'tax'              => $vat_rate,
			);
		}

		$shipping_total = (float) $order->get_shipping_total();
		if ( $shipping_total > 0 ) {
			$shipping_tax  = (float) $order->get_shipping_tax();
			$shipping_gross = $shipping_total + $shipping_tax;
			$shipping_vat  = $this->guess_vat_rate_from_tax( $shipping_total, $shipping_tax );
			$items[]       = array(
				'name'      => __( 'Shipping', 'weblo-fakturownia' ),
				'quantity'  => 1,
				'quantity_unit'    => 'pcs',
				'total_price_gross' => wc_format_decimal( $shipping_gross, 2 ),
				'tax'              => $shipping_vat,
			);
		}

		$notes_template = (string) ( $this->settings['weblo_fakturownia_invoice_notes'] ?? '' );
		$notes          = $this->render_notes_template( $notes_template, $order );

		// Dane adresowe nabywcy w polach obsługiwanych przez API Fakturowni.
		$buyer_street  = (string) $order->get_billing_address_1();
		$buyer_city    = (string) $order->get_billing_city();
		$buyer_post    = (string) $order->get_billing_postcode();
		$buyer_country = (string) $order->get_billing_country();

		$payload = array(
			'invoice' => array(
				'kind'          => 'vat',
				// Status musi być jedną z dozwolonych wartości API:
				// issued, paid, partial, rejected, sent, sent_error.
				// Na start ustawiamy "issued" (wystawiona).
				'status'        => 'issued',
				'issue_date'    => $issue_date,
				'sell_date'     => $issue_date,
				'oid'           => (string) $order->get_order_number(),
				'buyer_name'    => trim( $order->get_formatted_billing_full_name() ),
				'buyer_email'   => (string) $order->get_billing_email(),
				'positions'     => $items,
			),
		);

		// Zgodnie z dokumentacją API używamy "internal_note" (notatka prywatna) zamiast nieobsługiwanego pola "note"/"notes".
		if ( '' !== $notes ) {
			$payload['invoice']['internal_note'] = $notes;
		}

		if ( '' !== $buyer_street ) {
			$payload['invoice']['buyer_street'] = $buyer_street;
		}
		if ( '' !== $buyer_city ) {
			$payload['invoice']['buyer_city'] = $buyer_city;
		}
		if ( '' !== $buyer_post ) {
			$payload['invoice']['buyer_post_code'] = $buyer_post;
		}
		if ( '' !== $buyer_country ) {
			$payload['invoice']['buyer_country'] = $buyer_country;
		}

		$res = $this->request( 'POST', '/invoices.json', array(), $payload );
		if ( ! $res['success'] ) {
			return $res;
		}

		$data = $res['data'] ?? array();

		// Fakturownia zwykle zwraca { invoice: { id, number, ... } }.
		$invoice_id     = $data['invoice']['id'] ?? ( $data['id'] ?? null );
		$invoice_number = $data['invoice']['number'] ?? ( $data['number'] ?? null );

		return array(
			'success'        => true,
			'invoice_id'     => $invoice_id,
			'invoice_number' => $invoice_number,
			'data'           => $data,
		);
	}

	/**
	 * @param int|string $invoice_id
	 * @return array{success:bool,url?:string,content?:string,content_type?:string,error?:mixed,http_code?:int}
	 */
	public function download_invoice_pdf( $invoice_id ) {
		$invoice_id = (string) $invoice_id;
		if ( '' === $invoice_id ) {
			return array( 'success' => false, 'error' => __( 'Missing invoice_id.', 'weblo-fakturownia' ) );
		}

		// Oficjalny endpoint PDF (wg dokumentacji): /invoices/:id.pdf?api_token=...
		return array(
			'success' => true,
			'url'     => $this->build_url( '/invoices/' . rawurlencode( $invoice_id ) . '.pdf' ),
		);
	}

	/**
	 * @param string $path
	 * @param array  $query
	 * @return string
	 */
	protected function build_url( $path, $query = array() ) {
		$base = 'https://' . $this->domain . $path;

		$query = is_array( $query ) ? $query : array();
		$query['api_token'] = $this->api_token;

		if ( $this->department_id ) {
			$query['department_id'] = $this->department_id;
		}

		return add_query_arg( $query, $base );
	}

	/**
	 * @param 'GET'|'POST' $method
	 * @param string       $path
	 * @param array        $query
	 * @param array|null   $body
	 * @return array{success:bool,data?:mixed,error?:mixed,http_code?:int,raw_body?:string}
	 */
	protected function request( $method, $path, $query = array(), $body = null ) {
		$url = $this->build_url( $path, $query );

		$args = array(
			// Korekty i PDF potrafią być cięższe — dajmy rozsądny timeout.
			'timeout' => 25,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json; charset=utf-8';
			// send_by_email nie wymaga body, ale wp_remote_post oczekuje stringa/array.
			if ( null !== $body ) {
				$args['body'] = wp_json_encode( $body );
			}
		}

		$response = ( 'POST' === $method ) ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'error' => $response->get_error_message(),
					),
					'api'
				);
			}
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body  = (string) wp_remote_retrieve_body( $response );
		$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );

		$decoded = null;
		if ( '' !== $raw_body ) {
			$decoded = json_decode( $raw_body, true );
		}

		if ( $http_code < 200 || $http_code >= 300 ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				$decoded_preview = null;
				if ( '' !== $raw_body && false !== stripos( $content_type, 'application/json' ) ) {
					$tmp = json_decode( $raw_body, true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$decoded_preview = $tmp;
					}
				}
				weblo_fakturownia_debug_log(
					array(
						'http_code' => $http_code,
						'content_type' => $content_type,
						'raw_body_len'  => strlen( $raw_body ),
						'error_preview' => $decoded_preview,
					),
					'api'
				);
			}

			// Jeśli API zwróci HTML (np. 504), nie zapisuj całej strony jako "error".
			$is_html = ( false !== stripos( $content_type, 'text/html' ) ) || preg_match( '/^\s*<(?:!doctype|html)\b/i', $raw_body );
			if ( $is_html ) {
				$title = '';
				if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $raw_body, $m ) ) {
					$title = trim( wp_strip_all_tags( $m[1] ) );
				}
				$msg = sprintf(
					/* translators: %d: HTTP status code */
					__( 'Fakturownia API returned HTTP %d.', 'weblo-fakturownia' ),
					$http_code
				);
				if ( '' !== $title ) {
					$msg .= ' ' . $title;
				}
				return array(
					'success'   => false,
					'http_code' => $http_code,
					'error'     => $msg,
					'raw_body_len' => strlen( $raw_body ),
				);
			}

			return array(
				'success'   => false,
				'http_code' => $http_code,
				'error'     => ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $raw_body,
				'raw_body'  => $raw_body,
			);
		}

		if ( '' !== $raw_body && json_last_error() !== JSON_ERROR_NONE ) {
			if ( function_exists( 'weblo_fakturownia_debug_log' ) ) {
				weblo_fakturownia_debug_log(
					array(
						'http_code' => $http_code,
						'raw_body'  => $raw_body,
						'error'     => __( 'Invalid JSON in response.', 'weblo-fakturownia' ),
					),
					'api'
				);
			}
			return array(
				'success'   => false,
				'http_code' => $http_code,
				'error'     => __( 'Invalid JSON in response.', 'weblo-fakturownia' ),
				'raw_body'  => $raw_body,
			);
		}

		return array(
			'success'   => true,
			'http_code' => $http_code,
			'data'      => $decoded,
			'raw_body'  => $raw_body,
		);
	}

	/**
	 * VAT jako procent (np. 23) wyliczony z tax/total.
	 *
	 * @param float $net_total
	 * @param float $tax_total
	 * @return int
	 */
	protected function guess_vat_rate_from_tax( $net_total, $tax_total ) {
		$net_total = (float) $net_total;
		$tax_total = (float) $tax_total;

		if ( $net_total <= 0 || $tax_total <= 0 ) {
			return 0;
		}

		return (int) round( ( $tax_total / $net_total ) * 100 );
	}

	/**
	 * Resolve correction mode from options/settings.
	 * Supported values:
	 * - difference: include only changed lines
	 * - full: include all lines (new order value)
	 *
	 * Backward compatibility:
	 * - differential -> difference
	 *
	 * @param array $options
	 * @return string
	 */
	protected function get_correction_mode( $options ) {
		$mode = isset( $options['correction_mode'] ) ? (string) $options['correction_mode'] : 'full';
		$mode = strtolower( trim( $mode ) );

		if ( 'differential' === $mode ) {
			$mode = 'difference';
		}

		return in_array( $mode, array( 'difference', 'full' ), true ) ? $mode : 'full';
	}

	/**
	 * @param string   $template
	 * @param WC_Order $order
	 * @return string
	 */
	protected function render_notes_template( $template, $order ) {
		$order_number = '';
		if ( is_object( $order ) && method_exists( $order, 'get_order_number' ) ) {
			$order_number = (string) $order->get_order_number();
		} elseif ( is_object( $order ) && method_exists( $order, 'get_parent_id' ) ) {
			$parent_id = (int) $order->get_parent_id();
			$parent    = $parent_id > 0 ? wc_get_order( $parent_id ) : null;
			$order_number = ( $parent && method_exists( $parent, 'get_order_number' ) ) ? (string) $parent->get_order_number() : (string) $parent_id;
		} elseif ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			$order_number = (string) $order->get_id();
		}

		$replacements = array(
			'[order_number]'  => $order_number,
			'[order_id]'      => (string) $order->get_id(),
			'[customer_name]' => trim( $order->get_formatted_billing_full_name() ),
			'[customer_email]' => (string) $order->get_billing_email(),
			'[billing_company]' => (string) $order->get_billing_company(),
			'[billing_phone]'   => (string) $order->get_billing_phone(),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), (string) $template );
	}
}

