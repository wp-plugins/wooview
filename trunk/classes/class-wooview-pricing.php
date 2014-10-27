<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Pricing
 *
 * Handle all Pricing related methods
 *
 * @author 	WooView
 * @package 	WooView/Classes
 * @version     1.0.0
 */

class WooView_Pricing {
  
  /**
  * @var Wordpress Object 
  */
  private $wpdb;
  
  
  /**
  * @var Woocommerce Object 
  */
  private $woocommerce;
  
  
  /**
  * Construct
  */
  public function __construct() {
    
    //Wordpress Object
    global $wpdb;
    $this->wpdb = &$wpdb;
    
    //Woocommerce Object
    global $woocommerce;
    $this->woocommerce = &$woocommerce;
    
  }
  
  
  /**
  * Public: Get Formatted Price
  * @param float $price
  * @param array $args -
  * $args['currency'] (Currency of the price to format)
  * @return string 
  */
  public function format_price($price, $args = array()) {    
    $return = '';
    $num_decimals    = absint( get_option( 'woocommerce_price_num_decimals' ) );
    $currency        = isset( $args['currency'] ) ? $args['currency'] : '';
    $currency_symbol = html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8');
    $decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
    $thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
    $price           = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
    $price           = apply_filters( 'formatted_woocommerce_price', number_format( $price, $num_decimals, $decimal_sep, $thousands_sep ), $price, $num_decimals, $decimal_sep, $thousands_sep );
    if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $num_decimals > 0 ) {
      $price = wc_trim_zeros( $price );
    }
    $return = sprintf( get_woocommerce_price_format(), $currency_symbol, $price );
    return html_entity_decode($return);
  }
  
}