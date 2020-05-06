<?php

defined( 'ABSPATH' ) or die();

function currency_exchange_rate_get_latest( $base = 'USD' ) {
	return Currency_Exchange_Rates::getInstance()->get_latest( $base );
}

function currency_exchange_rates_convert( $value, $to, $from = 'USD' ) {

	if ( empty( $value ) ) {
		return false;
	}

	$transient_name = 'currency_exchange_rates_latest_' . $from;

	$currencies = get_transient( $transient_name );

	if ( empty( $currencies ) ) {
		$currencies = Currency_Exchange_Rates::getInstance()->get_latest( $from );
	}

	if ( ! is_wp_error( $currencies ) && ! empty( $currencies['rates'][ $to ] ) ) {
		set_transient( $transient_name, $currencies, DAY_IN_SECONDS );

		$rate = $currencies['rates'][ $to ];

		return $value * $rate;
	} else {
		return false;
	}

}