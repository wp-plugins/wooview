<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Customers
 *
 * Handle all Order related methods
 *
 * @author 	WooView
 * @package 	WooView/Classes
 * @version     1.0.0
 */

class WooView_Customers {
  
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
  * Public: Get Customer Username For Order
  * @param int $order_id 
  * @return string 
  */
  public function get_order_customer_username($order_id) {
    $args = array('include' => array(get_post_meta( $order_id, '_customer_user', true)));
    $customer = get_users($args);
    return (count($customer)) ? $customer[0]->data->user_login : 'Guest';
  }
  
  /**
  * Public: Get Customer Information
  * @param int $email_address 
  * @return array 
  */
  public function get_customer_info($login) {
    $user = get_user_by('login', $login);
    $customer_data = array();
    $customer_data['ID'] = $user->id;
    $customer_data['registered_date'] = $user->user_registered;
    $customer_data['registered_date_display'] = date_i18n('m.d.y', strtotime($customer_data['registered_date']));
    $customer_data['registered_time_display'] = date_i18n('g:i a', strtotime($customer_data['registered_date']));
    $customer_data['first_name'] = $user->first_name;
    $customer_data['last_name'] = $user->last_name;
    $customer_data['email_address'] = $user->user_email;
    $customer_data['display_name'] = $user->display_name;
    $customer_data['roles'] = $user->roles;
    return $customer_data;
  }
  
}