<?php

defined( 'ABSPATH' ) or die();

function currency_exchange_rate_get_latest( $base = 'USD' ) {
	return Currency_Exchange_Rates::getInstance()->get_latest( $base );
}

function currency_exchange_rate_get_plan() {
	$plan = '';

	$transient_name = 'currency_exchange_rates_usage';

	$usage = get_transient( $transient_name );

	if ( empty( $usage ) ) {
		$usage = Currency_Exchange_Rates::getInstance()->get_usage();
		set_transient( $transient_name, $usage, DAY_IN_SECONDS );
	}

	if ( ! is_wp_error( $usage ) && ! empty( $usage ) ) {
		$plan = ! empty( $usage['data']['plan']['name'] ) ? $usage['data']['plan']['name'] : '';
	}

	return $plan;
}

function currency_exchange_rates_convert( $value, $to, $from = 'USD' ) {

	if ( empty( $value ) ) {
		return false;
	}

	$plan     = currency_exchange_rate_get_plan();
	$currency = ( 'Free' == $plan ) ? 'USD' : $from;

	$transient_name = 'currency_exchange_rates_latest_' . $currency;

	$currencies = get_transient( $transient_name );

	if ( empty( $currencies ) ) {
		$currencies = Currency_Exchange_Rates::getInstance()->get_latest( $currency );
		set_transient( $transient_name, $currencies, DAY_IN_SECONDS );
	}

	if ( ! is_wp_error( $currencies ) && ! empty( $currencies['rates'][ $to ] ) ) {
		$rate = $currencies['rates'][ $to ];

		if ( 'Free' == $plan && 'USD' != $from ) {
			$rate = $currencies['rates'][ $to ] / $currencies['rates'][ $from ];
		}

		return $value * $rate;
	} else {
		return false;
	}

}