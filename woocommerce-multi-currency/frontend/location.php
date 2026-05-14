<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Location
 */
class WOOMULTI_CURRENCY_Frontend_Location {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			/*Check change currency. Can not code in init function because Widget price can not get symbol.*/
			$list_currencies = $this->settings->get_list_currencies();
			if ( $this->settings->use_session() ) {
				add_action( 'wp_ajax_wmc_currency_switcher', array( $this, 'currency_switcher' ) );
				add_action( 'wp_ajax_nopriv_wmc_currency_switcher', array( $this, 'currency_switcher' ) );
			}
			if ( isset( $_GET['wmc-currency'] ) ) {
				$target_currency = str_replace( '/', '', sanitize_text_field( $_GET['wmc-currency'] ) );
				if ( ! empty( $list_currencies[ $target_currency ] ) ) {
					if ( $list_currencies[ $target_currency ]['hide'] !== '1' ) {
						$this->settings->set_current_currency( $target_currency );
					}
				}
			}
			add_action( 'init', array( $this, 'init' ), 1 );
		}
	}


	/**
	 * Currency switcher via Ajax
	 */
	public function currency_switcher() {
		$list_currencies = $this->settings->get_list_currencies();
		if ( isset( $_GET['wmc-currency'] ) ) {
			$target_currency = str_replace( '/', '', sanitize_text_field( $_GET['wmc-currency'] ) );
			if ( ! empty( $list_currencies[ $target_currency ] ) ) {
				if ( $list_currencies[ $target_currency ]['hide'] !== '1' ) {
					$this->settings->set_current_currency( $target_currency );
					echo esc_html__( 'Currency changed', 'woocommerce-multi-currency' );
				}
			}
		}
		wp_die();
	}

	public function init() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( $action === 'wc_facebook_background_product_sync' ) {
			return;
		}
		if ( apply_filters( 'wmc_ignore_auto_select_currency', false ) ) {
			return;
		}
		$auto_detect = $this->settings->get_auto_detect();
		$currencies  = $this->settings->get_currencies();
		switch ( $auto_detect ) {
			case 1:
				/*Auto select currency*/
				if ( $this->settings->getcookie( 'wmc_current_currency' ) ) {
					$return = true;
					switch ( $this->settings->get_geo_api() ) {
						case 1:
//							$wmc_ip_add = $this->settings->getcookie( 'wmc_ip_add' );
//							if ( $wmc_ip_add !== $this->get_ip() ) {
//								$this->settings->setcookie( 'wmc_ip_add', '', time() - 3600 );
//								$this->settings->setcookie( 'wmc_ip_info', '', time() - 3600 );
//								$return = false;
//							}
							break;
						case 2:
							/*Update wmc_ip_info cookie if country is different from one saved in $_SERVER to handle page cache issue*/
							/*This helps auto-detect currency work with Kinsta cache but it will override that customers manually switch to*/
							if ( $this->settings->getcookie( 'wmc_ip_info' ) ) {
								$ip_info = json_decode( base64_decode( $this->settings->getcookie( 'wmc_ip_info' ) ), true );
								if ( ! empty( $ip_info['country'] ) ) {
									$server_detect = self::get_country_code_from_headers();
									if ( $server_detect && $ip_info['country'] !== $server_detect ) {
										$ip_info['country'] = $server_detect;
										$this->settings->setcookie( 'wmc_ip_info', base64_encode( json_encode( $ip_info ) ), time() + 86400 );
										$return = false;
									}
								}
							}
							break;
						default:
					}

					if ( $return ) {
						return;
					}
				}
				/*Do not run if a request is rest api or cron*/
				if ( WOOMULTI_CURRENCY_Data::is_request_to_rest_api() || ! empty( $_REQUEST['doing_wp_cron'] ) ) {
					return;
				}
				$detect_ip_currency = $this->detect_ip_currency();
//					echo '<pre>'.print_r($detect_ip_currency,true).'</pre>';
				if ( $this->settings->get_enable_currency_by_country() && isset( $detect_ip_currency['country_code'] ) && $detect_ip_currency['country_code'] ) {
					$currency_detected = '';
					foreach ( $currencies as $currency ) {
						$data = $this->settings->get_currency_by_countries( $currency );
						if ( in_array( $detect_ip_currency['country_code'], $data ) ) {
							$currency_detected = $currency;
							break;
						}
					}
					if ( $currency_detected ) {
						$this->settings->set_current_currency( $currency_detected );
					} else {
						$this->settings->set_current_currency( $detect_ip_currency['currency_code'] );
					}
				} elseif ( isset( $detect_ip_currency['currency_code'] ) && in_array( $detect_ip_currency['currency_code'], $currencies ) ) {
					$this->settings->set_current_currency( $detect_ip_currency['currency_code'] );
				} else {
					$this->settings->set_fallback_currency();
				}
				break;

			case 2:
				/*Create approximately*/
//				if ( $this->settings->getcookie( 'wmc_currency_rate' ) ) {
				if ( $ip_info = $this->settings->getcookie( 'wmc_ip_info' ) ) {
					$ip_info         = json_decode( base64_decode( $ip_info ) );
					$currencies_list = $this->settings->get_list_currencies();
					$db_rate         = isset( $currencies_list[ $ip_info->currency_code ]['rate'] ) ? $currencies_list[ $ip_info->currency_code ]['rate'] : '';
					$cookie_rate     = $this->settings->getcookie( 'wmc_currency_rate' );
					if ( $db_rate == $cookie_rate ) {
						return;
					}
				}

				$detect_ip_currency = $this->detect_ip_currency();
				if ( isset( $detect_ip_currency['currency_code'] ) ) {
					$this->settings->setcookie( 'wmc_currency_rate', $detect_ip_currency['currency_rate'], time() + 86400 );
					$this->settings->setcookie( 'wmc_currency_symbol', $detect_ip_currency['currency_symbol'], time() + 86400 );
				}
				break;

			case 3:
				if ( $this->settings->getcookie( 'wmc_current_currency' ) ) {
					return;
				} else {
					if ( class_exists( 'Polylang' ) && ! is_checkout() && ! is_cart() && function_exists( 'pll_current_language' ) ) {
						$detect_lang   = pll_current_language();
						$currency_code = $this->settings->get_currency_by_language( $detect_lang );
						if ( $currency_code ) {
							$this->settings->set_current_currency( $currency_code );
						}
					}
				}
				break;
			case 4:
				if ( class_exists( 'TRP_Translate_Press' ) ) {
					$lang             = get_locale();
					$trp_currencies   = $this->settings->get_params( 'translatepress' );
					$current_currency = $this->settings->get_current_currency();
					if ( ! empty( $trp_currencies[ $lang ] ) && $current_currency !== $trp_currencies[ $lang ] ) {
						$this->settings->set_current_currency( $trp_currencies[ $lang ] );
					}

					add_filter( 'trp_get_url_for_language', [ $this, 'translatepress_set_change_currency_query' ], 10, 3 );
				}
				break;
			default:

		}
	}

	/**
	 * @return array|bool
	 */
	protected function detect_ip_currency() {
		if ( $this->settings->getcookie( 'wmc_ip_info' ) ) {
			$geoplugin_arg = json_decode( base64_decode( $this->settings->getcookie( 'wmc_ip_info' ) ), true );
		} else {
			switch ( $this->settings->get_geo_api() ) {
				case 1:
					$ip_add = $this->get_ip();
					$this->settings->setcookie( 'wmc_ip_add', $ip_add, time() + 86400 );

					@$geoplugin = file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $ip_add );
//				@$geoplugin = file_get_contents( 'https://www.geoplugin.com/geodata.php?ip=' . $ip_add );

					if ( $geoplugin ) {
						$geoplugin_arg = unserialize( $geoplugin );
					}


					$geoplugin_arg = array(
						'country'       => isset( $geoplugin_arg['geoplugin_countryCode'] ) ? $geoplugin_arg['geoplugin_countryCode'] : 'US',
						'currency_code' => isset( $geoplugin_arg['geoplugin_currencyCode'] ) ? $geoplugin_arg['geoplugin_currencyCode'] : 'USD',
					);
					break;
				case 2:
					$country_code  = self::get_country_code_from_headers();
					$geoplugin_arg = array(
						'country'       => $country_code,
						'currency_code' => $this->settings->get_currency_code( $country_code )
					);
					break;
				default:
					$ip            = new WC_Geolocation();
					$geo_ip        = $ip->geolocate_ip();
					$country_code  = isset( $geo_ip['country'] ) ? $geo_ip['country'] : '';
					$geoplugin_arg = array(
						'country'       => $country_code,
						'currency_code' => $this->settings->get_currency_code( $country_code )
					);
			}

			if ( $geoplugin_arg['country'] ) {
				$this->settings->setcookie( 'wmc_ip_info', base64_encode( json_encode( $geoplugin_arg ) ), time() + 86400 );
			} else {
				return array();
			}
		}

		$auto_detect = $this->settings->get_auto_detect();
		if ( $auto_detect == 1 ) {
			/*Auto select currency*/
			if ( is_array( $geoplugin_arg ) && isset( $geoplugin_arg['currency_code'] ) ) {
				$currencies = $this->settings->get_currencies();
				if ( ! in_array( $geoplugin_arg['currency_code'], $currencies ) ) {
					$geoplugin_arg['currency_code'] = $this->settings->get_default_currency();
				}

				return array(
					'currency_code' => $geoplugin_arg['currency_code'],
					'country_code'  => $geoplugin_arg['country']
				);
			} else {
				return array();
			}
		} elseif ( $auto_detect == 2 ) {
			/*Approximately price*/
			if ( is_array( $geoplugin_arg ) && isset( $geoplugin_arg['currency_code'] ) ) {
				$currency_code = $geoplugin_arg['currency_code'];
				$country_code  = $geoplugin_arg['country'];
				$symbol        = get_woocommerce_currency_symbol( $geoplugin_arg['currency_code'] );
			} else {
				return array();
			}
			$currencies        = $this->settings->get_currencies();
			$main_currency     = $this->settings->get_default_currency();
			$list_currencies   = $this->settings->get_list_currencies();
			$currency_detected = '';
			if ( apply_filters( 'wmc_approximate_price_currency_by_country', $this->settings->get_enable_currency_by_country() ) ) {
				foreach ( $currencies as $currency ) {
					$data = $this->settings->get_currency_by_countries( $currency );
					if ( in_array( $country_code, $data ) ) {
						$currency_detected = $currency;
						break;
					}
				}
			}

			if ( $currency_detected ) {
				if ( $currency_detected !== $this->settings->get_current_currency() ) {
					return array(
						'currency_code'   => $currency_detected,
						'currency_rate'   => $list_currencies[ $currency_detected ]['rate'],
						'currency_symbol' => get_woocommerce_currency_symbol( $currency_detected )
					);
				} else {
					return array();
				}

			} else if ( in_array( $currency_code, $currencies ) ) {
				return array(
					'currency_code'   => $currency_code,
					'currency_rate'   => $list_currencies[ $currency_code ]['rate'],
					'currency_symbol' => get_woocommerce_currency_symbol( $currency_code )
				);
			} else {
				$exchange_rate = $this->settings->get_exchange( $main_currency, $currency_code );
				if ( is_array( $exchange_rate ) && isset( $exchange_rate[ $currency_code ] ) ) {
					return array(
						'currency_code'   => $currency_code,
						'currency_rate'   => $exchange_rate[ $currency_code ],
						'currency_symbol' => $symbol
					);
				} else {
					return array();
				}

			}

		} else {
			return array();
		}
	}

	/**
	 * Return IP
	 * @return string
	 */
	protected function get_ip() {
		if ( defined( 'WOO_MULTI_CURRENCY_CUSTOM_IP' ) ) {
			if ( isset( $_SERVER[ WOO_MULTI_CURRENCY_CUSTOM_IP ] ) ) {
				return $_SERVER[ WOO_MULTI_CURRENCY_CUSTOM_IP ];
			}
		}
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return $ipaddress;
	}

	private static function get_country_code_from_headers() {
		$country_code = '';

		$headers = WOOMULTI_CURRENCY_Data::country_code_key_from_headers();

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				break;
			}
		}

		return $country_code;
	}

	public function translatepress_set_change_currency_query( $new_url, $url, $language ) {
		$trp_currencies = $this->settings->get_params( 'translatepress' );

		if ( ! empty( $trp_currencies[ $language ] ) ) {
			$new_url = add_query_arg( [ 'wmc-currency' => $trp_currencies[ $language ] ], $new_url );
		}

		return $new_url;
	}
}