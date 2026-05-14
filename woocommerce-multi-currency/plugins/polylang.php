<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_Polylang
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_Polylang {
	protected $settings;
	protected $current_lang;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_action( 'pll_language_defined', array( $this, 'pll_language_defined' ) );
//			add_filter( 'pll_the_language_link', array( $this, 'pll_the_language_link' ), 10, 3 );
		}
	}

	public function pll_language_defined( $data ) {
		if ( isset( $_GET['wc-ajax'] ) && in_array( sanitize_text_field( $_GET['wc-ajax'] ), array(
				'update_order_review',
				'get_refreshed_fragments'
			) ) ) {
			return;
		}
		if ( isset( $_GET['custom-css'] ) ) {
			/*Savoy theme requests for custom css dynamically and causes the issue with this function*/
			return;
		}
		if ( class_exists( 'Polylang' ) ) {
			$this->current_lang = $data;
			$curlang            = isset( $_COOKIE['pll_language'] ) ? sanitize_key( $_COOKIE['pll_language'] ) : PLL()->curlang->slug;
			if ( apply_filters( 'wmc_polylang_need_set_currency_by_language', $curlang != $data, $curlang, $data ) ) {
				$currency_code = $this->settings->get_currency_by_language( $data );
				if ( $currency_code ) {
					$this->settings->set_current_currency( $currency_code );
				}
			}
		}
	}

	/**
	 * Integrate with Polylang
	 * @return bool
	 */
	public function pll_the_language_link( $url, $slug, $locale ) {
		if ( $this->settings->get_currency_by_language( $slug ) ) {
			if ( isset( $_GET['wmc-currency'] ) ) {
				remove_query_arg( 'wmc-currency', $url );
			}
			$url = add_query_arg( 'wmc-currency', $this->settings->get_currency_by_language( $slug ), $url );
		}

		return $url;
	}
}