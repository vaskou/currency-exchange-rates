<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Currency Exchange Rates
 * Description:       Get currency exchange rates using Open Exchange Rates API
 * Version:           1.2.0
 * Author:            Vasilis Koutsopoulos
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       currency-exchange-rates
 * Domain Path:       /languages
 *
 * Requires at least: 5.5
 * Tested up to: 5.5.3
 */

defined( 'ABSPATH' ) or die();

define( 'CURRENCY_EXCHANGE_RATES_VERSION', '1.2.0' );
define( 'CURRENCY_EXCHANGE_RATES_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CURRENCY_EXCHANGE_RATES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CURRENCY_EXCHANGE_RATES_PLUGIN_DIR_NAME', basename( CURRENCY_EXCHANGE_RATES_PLUGIN_DIR_PATH ) );
define( 'CURRENCY_EXCHANGE_RATES_PLUGIN_URL', plugins_url( CURRENCY_EXCHANGE_RATES_PLUGIN_DIR_NAME ) );

include 'includes/class-currency-exchange-rates-settings.php';
include 'includes/class-currency-exchange-rates.php';
include 'includes/helper-functions.php';

Currency_Exchange_Rates::instance();