<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Orders
 *
 * Handle all Order related methods
 *
 * @author 	WooView
 * @package 	WooView/Classes
 * @version     1.0.0
 */

class WooView_Orders {
  
  /**
  * @var Wordpress Object 
  */
  private $wpdb;
  
  
  /**
  * @var Woocommerce Object 
  */
  private $woocommerce;
  
  
  /**
  * @var Pricing Manager Object 
  */
  private $pricing_manager;
  
  
  /**
  * @var Customers Manager Object 
  */
  private $customers_manager;
  
  
  /**
  * Construct
  */
  public function __construct() {
    
    //Includes
    include_once('class-wooview-pricing.php');
    include_once('class-wooview-customers.php');
    
    //Wordpress Object
    global $wpdb;
    $this->wpdb = &$wpdb;
    
    //Woocommerce Object
    global $woocommerce;
    $this->woocommerce = &$woocommerce;
    
    //Pricing Manager
    $this->pricing_manager = new WooView_Pricing();
    
    //Customers Manager
    $this->customers_manager = new WooView_Customers();
  }
  
  
  /**
  * Private: Get Order Number
  * @param int $order_id 
  * @return int 
  */
  private function get_order_number($order_id) {
    $order_number = get_post_meta( $order_id, '_order_number', true );
    if( $order_number ) {
      return $order_number;
    }
    return $order_id;
  }
  
  
  /**
  * Public: Get All Order Statuses
  * @return array 
  */
  public function get_all_order_statuses() {
	  
	  if( version_compare( WOOCOMMERCE_VERSION, '2.2.0' ) >= 0 ) {
			$wm_order_statuses = array();
			$order_statuses_wc = wc_get_order_statuses();
			foreach ($order_statuses_wc as $order_status_key_wc => $order_status_name_wc )
				$wm_order_statuses[] = str_replace( 'wc-', '', $order_status_key_wc );
			return $wm_order_statuses;
		} else {
			$args = array('fields' => 'names', 'hide_empty' => 0);
    		return get_terms('shop_order_status', $args);
		}	
  }
  
  
  /**
  * Private: Get Order Status
  * @param int $order_id 
  * @return string 
  */
  public function get_order_status($order_id) {
	  $order = new WC_Order($order_id);
	  if( version_compare( WOOCOMMERCE_VERSION, '2.2.0' ) >= 0 ) {
	    return wc_get_order_status_name( 'wc-'. $order->get_status() );
	  } else {
		return wc_get_order_status_name( $order->get_status() );
	  }
  }
  
  
  /**
  * Private: Get Order Address
  * @param int $order_id
  * @param string $type DEFAULT 'billing'
  * @return array 
  */
  private function get_order_address($order_id, $type = 'billing') {
    $address = array( 'first_name' => get_post_meta($order_id, '_'. $type .'_first_name', true),
		      'company' => get_post_meta($order_id, '_'. $type .'_company', true),
		      'last_name' => get_post_meta($order_id, '_'. $type .'_last_name', true),
		      'address_1' => get_post_meta($order_id, '_'. $type .'_address_1', true),
		      'address_2' => get_post_meta($order_id, '_'. $type .'_address_2', true),
		      'city' => get_post_meta($order_id, '_'. $type .'_city', true),
		      'state' => get_post_meta($order_id, '_'. $type .'_state', true),
		      'postcode' => get_post_meta($order_id, '_'. $type .'_postcode', true),
                      'country' => get_post_meta($order_id, '_'. $type .'_country', true),
		      'email' => get_post_meta($order_id, '_'. $type .'_email', true),
		      'phone' => get_post_meta($order_id, '_'. $type .'_phone', true));
    
    $WC = new WC_Countries();
    return array('address_for_display' => $WC->get_formatted_address($address), 'address' => $address);
  }
  
  
  /**
  * Private: Get Order Notes
  * @param int $order_id
  * @return array 
  */
  private function get_order_notes($order_id) {
    $response_data = array();
    $notes = $this->wpdb->get_results("SELECT C.comment_ID AS ID,
                                      C.comment_date AS created,
                                      C.comment_content AS content,
                                      CM.meta_value AS is_customer_note
				      FROM {$this->wpdb->comments} AS C, {$this->wpdb->commentmeta} AS CM
				      WHERE C.comment_type = 'order_note' AND C.comment_post_ID = $order_id
				      AND C.comment_approved = 1 AND CM.meta_key = 'is_customer_note'
				      AND C.comment_id = CM.comment_id ORDER BY C.comment_ID DESC;");
    foreach($notes as $note) {
      $note_data = array();
      
      $note_data['ID'] = $note->ID;
      $note_data['date_created'] = $note->created;
      $note_data['date_display'] = date_i18n('m.d.y', strtotime($note_data['date_created']));
      $note_data['time_display'] = date_i18n('g:i a', strtotime($note_data['date_created']));
      $note_data['content'] = $note->content;
      $note_data['is_customer_note'] = $note->is_customer_note;
      
      array_push($response_data, $note_data);
    }
    
    return $response_data;
  }
  
  
  /**
  * Public: Get Order Items
  * @param int $order_id 
  * @return array 
  */
  private function get_order_items($order_id) {
    
    $response_data = array();
    $order = new WC_Order($order_id);
    $currency = get_post_meta( $order->id, '_order_currency', true );
    $items = $order->get_items();
    
    //Return false if we have no resulting items
    if(!$items) {
      return false;
    }
    
    //Loop through items and attach data points
    foreach ($items as $item) {
      
      $item_data = array();
      $item_attributes = array();
      $standard_attributes = array('name', 'type', 'qty', 'tax_class', 'product_id', 'variation_id', 'line_subtotal', 'line_total', 'line_tax', 'line_subtotal_tax');
      
      foreach($item as $key => $value) {
        if(in_array($key, $standard_attributes)) {
          $item_data[$key] = $value;
        } else {
          if($key != 'item_meta') {
            $item_attributes[$key] = $value;
          }
        }
      } 
      $item_data['line_total_display'] = $this->pricing_manager->format_price($item_data['line_total'], $currency);
      $item_data['line_tax_display'] = $this->pricing_manager->format_price($item_data['line_tax'], $currency);
      $item_data['line_subtotal_tax_display'] = $this->pricing_manager->format_price($item_data['line_subtotal_tax'], $currency);
      $item_data['line_subtotal_display'] = $this->pricing_manager->format_price($item_data['line_subtotal'], $currency);
      $item_data['attributes'] = $item_attributes;
      array_push($response_data, $item_data); 
    }
    return $response_data;
  }
  
  
  
  /**
  * Public: Update Order Status
  * @param int $id (order note)
  * @param int $status (the new order status)
  * @return string 
  */
  function update_order_status($id, $status) {
    $order = new WC_Order($id);
    $order->update_status($status);
    return $status;
  }
  
  
  /**
  * Public: Add Order Note
  * @param int $id (order id)
  * @param int $message (the order notes message)
  * @param int $is_public (is the not public or private)
  * @return array 
  */
  function add_order_note($id, $message, $is_public) {
    $order = new WC_Order($id);
    if(!$order->id) {
      return false;
    }
    $order->add_order_note($message, $is_public);
    return $this->get_order_notes($id);
  }
  
  /**
  * Public: Delete Order Note
  * @param int $order_id (order id)
  * @param int $id (note id)
  * @return array 
  */
  function delete_order_note($order_id, $id) {
    if ($id > 0) {
      wp_delete_comment($id);
    }
    return $this->get_order_notes($order_id);
  }
  

  /**
  * Public: Get Orders
  * @param int $id (order id)
  * @param array $param -
  *   $params['detailed'] (Show All Order Details) DEFAULT: false
  *   $params['date_range'] (Filter Orders By Date, see WP date_query for details) DEFAULT: false
  *   $params['offset'] (Offset Orders for pagination) DEFAULT: 0
  *   $params['limit'] (Limit Orders for pagination) DEFAULT: -1
  * @return array 
  */
  function get_orders($id = false, $params = array()) {
    
    //Initialize Parameters
    $detailed = (isset($params['detailed'])) ? $params['detailed'] : false;
    $date_range = (isset($params['date_range'])) ? $params['date_range'] : false;
    $offset = (isset($params['offset'])) ? $params['offset'] : 0;
    $limit = (isset($params['limit'])) ? $params['limit'] : -1;
    
    $response_data = array();
    
    //Arguments
    $args = array('posts_per_page'  => $limit,
                  'offset' => $offset,
                  'orderby' => 'post_date',
                  'order' => 'DESC',
                  'post_type' => 'shop_order',
                  'post_status' => 'publish',);
    
    //Filter if single order request
    if($id) {
      $args['post__in'] = array($id);
    }
    
    //Filter orders by date range Date
    if($date_range) {
      $args['date_query'] = $date_range;
    }    
    
    $orders = new WP_Query($args);
    
    //Setup pagination values
    if($limit > 0) {
      $response_data['number_of_pages'] = ceil(($orders->found_posts / $limit));
    }
    
    //Return false if we have no resulting orders
    if(!$orders) {
      return false;
    }
    
    $response_data['orders'] = array();
    
    //Loop through orders and attach data points
    foreach ($orders->posts as $order) {
      
      $order_data = array();
      
      $currency = get_post_meta( $order->ID, '_order_currency', true );
      $order_data['ID'] = $order->ID;
      $order_data['date_created'] = $order->post_date;
      $order_data['date_display'] = date_i18n('m.d.y', strtotime($order_data['date_created']));
      $order_data['time_display'] = date_i18n('g:i a', strtotime($order_data['date_created']));
      $order_data['customer'] = get_post_meta( $order->ID, '_customer_user', true);
		
	  $order_data['status'] = $this->get_order_status($order->ID);
		
      $order_data['first_name'] = get_post_meta( $order->ID, '_billing_first_name', true);
      $order_data['last_name'] = get_post_meta( $order->ID, '_billing_last_name', true);
      $order_data['total'] = get_post_meta( $order->ID, '_order_total', true);
      $order_data['total_display'] = $this->pricing_manager->format_price($order_data['total'], array('currency' => $currency));
      $order_data['order_number'] = $this->get_order_number($order->ID);
      $order_data['payment_method'] = get_post_meta( $order->ID, '_payment_method_title', true );
      
      if($detailed) {
		  
		  
        $order_proxy = new WC_Order($order->ID);
        $order_data['customer_username'] = $this->customers_manager->get_order_customer_username($order->ID);
        $order_data['customer_details'] = ($order_data['customer_username'] != 'Guest') ? $this->customers_manager->get_customer_info($order_data['customer_username']) : false;
        $order_data['customer_note'] = $order->post_excerpt;
        $order_data['billing_address'] = $this->get_order_address($order->ID, 'billing');
	$order_data['shipping_address'] = $this->get_order_address($order->ID, 'shipping');
        $order_data['shipping_method'] = $order_proxy->get_shipping_method();
	$order_data['shipping_cost'] = get_post_meta( $order->ID, '_order_shipping', true );
        $order_data['shipping_cost_display'] = $this->pricing_manager->format_price($order_data['shipping_cost'], array('currency' => $currency));
	$order_data['discount'] = get_post_meta( $order->ID, '_order_discount', true );
        $order_data['discount_display'] = $this->pricing_manager->format_price($order_data['discount'], array('currency' => $currency));
	$order_data['cart_discount'] = get_post_meta( $order->ID, '_cart_discount', true );
        $order_data['cart_discount_display'] = $this->pricing_manager->format_price($order_data['cart_discount'], array('currency' => $currency));
	$order_data['tax'] = get_post_meta( $order->ID, '_order_tax', true );
        $order_data['tax_display'] = $this->pricing_manager->format_price($order_data['tax'], array('currency' => $currency));
	$order_data['shipping_tax'] = get_post_meta( $order->ID, '_order_shipping_tax', true );
        $order_data['shipping_tax_display'] = $this->pricing_manager->format_price($order_data['shipping_tax'], array('currency' => $currency));
	$order_data['tax_inclusive'] = get_post_meta( $order->ID, '_prices_include_tax', true );		
	$order_data['currency'] = $currency;
        $order_data['order_items'] = $this->get_order_items($order->ID);
        $order_data['order_items_count'] = count($order_data['order_items']);
        $order_data['notes'] = $this->get_order_notes($order->ID);
      }
      array_push($response_data['orders'], $order_data); 
    }
    return $response_data;
  }
  
}