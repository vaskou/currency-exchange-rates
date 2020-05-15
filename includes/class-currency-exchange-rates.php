<?php

defined( 'ABSPATH' ) or die();

class Currency_Exchange_Rates {

	private $settings = object;

	private static $instance = null;

	private function __construct() {
		$this->settings = Currency_Exchange_Rates_Settings::getInstance();
	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

		$this->settings->init();
	}

	public function on_plugins_loaded() {
		load_plugin_textdomain( 'currency-exchange-rates', false, CURRENCY_EXCHANGE_RATES_PLUGIN_DIR_NAME . '/languages/' );
	}

	public function get_latest( $base = 'USD' ) {
		$api_url = Currency_Exchange_Rates_Settings::get_setting( 'cer_oxr_api_url' );
		$app_id  = Currency_Exchange_Rates_Settings::get_setting( 'cer_oxr_app_id' );

		$response = wp_remote_get( $api_url . '/latest.json', array(
			'body' => array(
				'app_id' => $app_id,
				'base'   => $base
			)
		) );

		return $this->_handle_response( $response );
	}

	private function _handle_response( $response ) {

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'cer_handle_response', esc_html__( 'Response returned an error', 'currency_exchange_rates' ) );
		}

		if ( empty( $response['response']['code'] ) ) {
			return new WP_Error( 'cer_handle_response', esc_html__( 'Response code is empty', 'currency_exchange_rates' ) );
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'cer_handle_response', esc_html__( ' Response body is empty', 'currency_exchange_rates' ) );
		}

		$response_code = $response['response']['code'];
		$response_body = $response['body'];

		if ( $response_code >= 400 ) {
			$message = $response['response']['message'] . ': ' . $response_body;

			return new WP_Error( 'cer_handle_response', $message );
		}

		$result = new WP_Error( 'cer_handle_response', $response['response']['message'] );

		if ( $response_code >= 200 && $response_code < 300 ) {

			try {
				$result = json_decode( $response_body, true );
			} catch ( Exception $ex ) {
				$result = new WP_Error( 'cer_handle_response', $ex->getMessage() );
			}

		}

		return $result;

	}
}