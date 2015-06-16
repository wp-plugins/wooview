<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Reporting
 *
 * Handle all Reporting related methods
 *
 * @author 	WooView
 * @package 	WooView/Classes
 * @version     1.0.0
 */

class WooView_Reporting {
  
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
  * Construct
  */
  public function __construct() {
    
    //Includes
    include_once('class-wooview-pricing.php');
    
    //Wordpress Object
    global $wpdb;
    $this->wpdb = &$wpdb;
    
    //Woocommerce Object
    global $woocommerce;
    $this->woocommerce = &$woocommerce;
    
    //Pricing Manager
    $this->pricing_manager = new WooView_Pricing();
    
  }
  
  
  /**
  * Private: Get Reporting Period Data
  * @return array 
  */
  private function get_reporting_period_data(&$period_data) {
    
    $day_hours = 24;
    
    switch ($period_data['type']) {
      case 'today':
        $period_data['year'] = date_i18n('Y');
        $period_data['month'] = date_i18n('m');
        $period_data['day'] = date_i18n('d');
        $period_data['current_hour'] = date_i18n('G');
        $period_data['hours_in_period'] = $period_data['current_hour'];
        $period_data['date_cols'] = array();
        for ($i = 0 ; $i < ($period_data['hours_in_period'] + 1) ; $i++) {
          $period_data['date_cols'][] =  ($i);
        }
        return array('date_range' => " AND DATE(posts.post_date) = DATE(NOW())", 'grouping' => " GROUP BY HOUR(posts.post_date)");
        break;
      case 'last_7_days':
        $period_data['month_display'] = date_i18n('M', strtotime('this month'));
        $period_data['days_in_period'] = 7;
        $period_data['date_cols'] = array();
        $timestamp = current_time('timestamp');
        $timestamp -= (6 * $day_hours) * 3600;
        for ($i = 0 ; $i < $period_data['days_in_period'] ; $i++) {
          $period_data['date_cols'][] =  date_i18n('Y-m-d', $timestamp);
          $timestamp += (1 * $day_hours) * 3600;
        }
        return array('date_range' => " AND (posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY))", 'grouping' => " GROUP BY DATE(posts.post_date)");
        break;
      case 'last_month':
        $period_data['year'] = date_i18n('Y');
        $period_data['month'] = date_i18n('m', strtotime("first day of last month"));
        $period_data['month_display'] = date_i18n('M', strtotime('last month'));
        $period_data['days_in_period'] = cal_days_in_month(CAL_GREGORIAN, $period_data['month'], $period_data['year']);
        $period_data['date_cols'] = array();
        for ($i = 0 ; $i < $period_data['days_in_period']; $i++) {
          $period_data['date_cols'][] =  date_i18n('Y-m-d', strtotime($period_data['year'] . '-' . $period_data['month'] . '-' . ($i + 1)));
        }
        return array('date_range' => " AND YEAR(posts.post_date) = {$period_data['year']} AND MONTH(posts.post_date) = {$period_data['month']}", 'grouping' => " GROUP BY DATE(posts.post_date)");
        break;
      case 'this_month':
        $period_data['year'] = date_i18n('Y');
        $period_data['month'] = date_i18n('m');
        $period_data['day'] = date_i18n('d');
        $period_data['month_display'] = date_i18n('M');
        $period_data['days_in_period'] = $period_data['day'];
        $period_data['date_cols'] = array();
        for ($i = 0; $i < $period_data['days_in_period'] ; $i++) {
          $period_data['date_cols'][] =  date_i18n('Y-m-d', strtotime($period_data['year'] . '-' . $period_data['month'] . '-' . ($i + 1)));
        }
        return array('date_range' => " AND YEAR(posts.post_date) = {$period_data['year']} AND MONTH(posts.post_date) = {$period_data['month']}", 'grouping' => " GROUP BY DATE(posts.post_date)");
        break;
      case 'this_year':
        $period_data['year'] = date_i18n('Y');
        $period_data['months_in_period'] = date_i18n('m');
        $period_data['days_in_period'] = date_i18n("z") + 1;
        $period_data['date_cols'] = array();
        for ($i = 0 ; $i < $period_data['months_in_period'] ; $i++) {
          $period_data['date_cols'][] =  sprintf("%02s", ($i + 1));
        }
        return array('date_range' => " AND YEAR(posts.post_date) = {$period_data['year']}", 'grouping' => " GROUP BY MONTH(posts.post_date)");
    }
  }
  
  
  /**
  * Private: Get Reporting Periods
  * @return array 
  */
  private function get_reportings_periods() {
    return array('today', 'last_7_days', 'last_month', 'this_month', 'this_year');
  }
  
  
  /**
  * Public: Get Last Month's Sales
  * @return array 
  */
  public function get_sales_report($period = 'last_7_days') {
    
    //Date Information
    $date_periods =$this->get_reportings_periods();
    $period = (!in_array($period, $date_periods)) ? 'last_7_days' : $period;
    $period_information = array('type' => $period);
    $query_params = $this->get_reporting_period_data($period_information);
    
    //Get Orders Totals
	  
	  $valid_statuses = implode("','", array('wc-completed','completed','wc-processing','processing','wc-on-hold','on-hold'));
    
    $date_selection = ($period_information['type'] == 'today') ? "posts.post_date" : "DATE(posts.post_date)";
	  
	  $searchString = "SELECT {$date_selection} AS 'date',
                                       sum(postmeta1.meta_value) AS 'total_amount',
                                       sum(postmeta2.meta_value) AS 'total_shipping',
                                       sum(postmeta3.meta_value) AS 'total_tax',
                                       COUNT(posts.ID) AS 'total_orders'
                                       FROM {$this->wpdb->prefix}posts as posts
                                       LEFT JOIN {$this->wpdb->prefix}postmeta as postmeta1 ON posts.ID=postmeta1.post_id
                                       LEFT JOIN {$this->wpdb->prefix}postmeta as postmeta2 ON posts.ID=postmeta2.post_id
                                       LEFT JOIN {$this->wpdb->prefix}postmeta as postmeta3 ON posts.ID=postmeta3.post_id
                                       WHERE posts.post_type='shop_order' ";
	  
	  if(version_compare( WOOCOMMERCE_VERSION, '2.2.0') >= 0 ) {
		  $searchString .= "AND posts.post_status IN ('{$valid_statuses}') ";
	  } else {
		   $searchString .= "AND tax.taxonomy	= 'shop_order_status'
		   					AND term.slug IN ('{$valid_statuses}') ";
	  }
	  
	  $searchString .= "AND postmeta1.meta_key='_order_total'
                                       AND postmeta2.meta_key='_order_shipping'
                                       AND postmeta3.meta_key='_order_tax'" .
                                       $query_params['date_range'] .
                                       $query_params['grouping'];
	  
	  $orders = $this->wpdb->get_results($searchString);
	  
    //Setup dates and date arrays
    $item_dates = array();
    $item_data = array();
    if($period_information['type'] == 'this_year') {
      foreach($orders as $item) {
        $item_dates[] = date_i18n('m', strtotime($item->date));
        $item_data[date_i18n('m', strtotime($item->date))] = array('total_sales' => $item->total_amount,
                                                              'total_shipping' => $item->total_shipping,
                                                              'total_tax' => $item->total_tax,
                                                              'total_orders' => $item->total_orders,
                                                              'total_discount' => 0);
      }
    } else if($period_information['type'] == 'today') {
      foreach($orders as $item) {
        $item_dates[] = date('G', strtotime($item->date));
        $item_data[date('G', strtotime($item->date))] = array('total_sales' => $item->total_amount,
                                                              'total_shipping' => $item->total_shipping,
                                                              'total_tax' => $item->total_tax,
                                                              'total_orders' => $item->total_orders,
                                                              'total_discount' => 0);
      }
    } else {
      foreach($orders as $item) {
        $item_dates[] = $item->date;
        $item_data[$item->date] = array('total_sales' => $item->total_amount,
                                        'total_shipping' => $item->total_shipping,
                                        'total_tax' => $item->total_tax,
                                        'total_orders' => $item->total_orders,
                                        'total_discount' => 0);
      }
    }
    
    //Assemble final data response
    $response_data = array();
    $response_data['period_information'] = $period_information;
    $response_data['totals'] = array();
    $response_data['data'] = array();
    
    $currency = get_option('woocommerce_currency');
    foreach($period_information['date_cols'] as $date) {
      $data_set = array();
      $data_set["date"] = $date;
      
      if($period_information['type'] == 'last_7_days' || $period_information['type'] == 'last_month' || $period_information['type'] == 'this_month') {
        $data_set["date_display"] = date('F d', strtotime($data_set["date"]));
      }

      if($period_information['type'] == 'this_year') {
        $data_set["date_display"] = date("F", strtotime('00-'.$date.'-01'));
      }
      if($period_information['type'] == 'today') {        
        $data_set["date_display"] = date("gA", strtotime($date . ":00"));
      }
      
      $data_set["total_orders"] = (in_array($date, $item_dates)) ? $item_data[$date]['total_orders'] : 0;
      $data_set["total_orders_display"] = $data_set["total_orders"];
      $data_set["total_sales"] = (in_array($date, $item_dates)) ? $item_data[$date]['total_sales'] : 0;
      $data_set["total_sales_display"] = $this->pricing_manager->format_price($data_set["total_sales"], $currency);
      $data_set["total_shipping"] = (in_array($date, $item_dates)) ? $item_data[$date]['total_shipping'] : 0;
      $data_set["total_shipping_display"] = $this->pricing_manager->format_price($data_set["total_shipping"], $currency);
      $data_set["total_tax"] = (in_array($date, $item_dates)) ? $item_data[$date]['total_tax'] : 0;
      $data_set["total_tax_display"] = $this->pricing_manager->format_price($data_set["total_tax"], $currency);
      $data_set["total_discount"] = (in_array($date, $item_dates)) ? $item_data[$date]['total_discount'] : 0;
      $data_set["total_discount_display"] = $this->pricing_manager->format_price($data_set["total_discount"], $currency);
      
      //Update Totals
      $response_data['totals']['sales'] += $data_set["total_sales"];
      $response_data['totals']['orders'] += $data_set["total_orders"];
      $response_data['totals']['shipping'] += $data_set["total_shipping"];
      $response_data['totals']['tax'] += $data_set["total_tax"];
      $response_data['totals']['discount'] += $data_set["total_discount"];
      
      array_push($response_data['data'], $data_set);
    }
    
    //Finalize Totals
    $response_data['totals']['sales_display'] = $this->pricing_manager->format_price($response_data['totals']['sales'], $currency);
    $response_data['totals']['shipping_display'] = $this->pricing_manager->format_price($response_data['totals']['shipping'], $currency);
    $response_data['totals']['tax_display'] = $this->pricing_manager->format_price($response_data['totals']['tax'], $currency);
    $response_data['totals']['discount_display'] = $this->pricing_manager->format_price($response_data['totals']['discount'], $currency);
    
    //Attached daily averages
    if($period_information['type'] != 'today') {
      $response_data['totals']['average_daily_sales'] = ($response_data['totals']['sales'] / $period_information['days_in_period']);
      $response_data['totals']['average_daily_sales_display'] = $this->pricing_manager->format_price($response_data['totals']['average_daily_sales'], $currency);
    }
    
    //Attached monthy averages
    if($period_information['type'] == 'this_year') {
      $response_data['totals']['average_monthly_sales'] = ($response_data['totals']['sales'] / $period_information['months_in_period']);
      $response_data['totals']['average_monthly_sales_display'] = $this->pricing_manager->format_price($response_data['totals']['average_monthly_sales'], $currency);
    }
    
    //Attach Cumulative Data
    $cumulative_total_sales = 0;
    $cumulative_total_orders = 0;
    $cumulative_total_shipping = 0;
    $cumulative_total_tax = 0;
    $cumulative_total_discount = 0;
    foreach($response_data['data'] as &$data) {
      
      $data["cumulative_total_sales"] = ($cumulative_total_sales + $data['total_sales']);
      $data["cumulative_total_sales_display"] = '' . $this->pricing_manager->format_price($data['cumulative_total_sales'], $currency);
      $cumulative_total_sales += $data['total_sales'];
      
      $data["cumulative_total_orders"] = ($cumulative_total_orders + $data['total_orders']);
      $data["cumulative_total_orders_display"] = '+' . $data['cumulative_total_orders'];
      $cumulative_total_orders += $data['total_orders'];
      
      $data["cumulative_total_shipping"] = ($cumulative_total_shipping + $data['total_shipping']);
      $data["cumulative_total_shipping_display"] = '' . $this->pricing_manager->format_price($data['cumulative_total_shipping'], $currency);
      $cumulative_total_shipping += $data['total_shipping'];
      
      $data["cumulative_total_tax"] = ($cumulative_total_tax + $data['total_tax']);
      $data["cumulative_total_tax_display"] = '+' . $this->pricing_manager->format_price($data['cumulative_total_tax'], $currency);
      $cumulative_total_tax += $data['total_tax'];
      
      $data["cumulative_total_discount"] = ($cumulative_total_discount + $data['total_discount']);
      $data["cumulative_total_discount_display"] = '' . $this->pricing_manager->format_price($data['cumulative_total_discount'], $currency);
      $cumulative_total_tax += $data['total_discount'];
    }
    
    
    
    
    return $response_data;
  }
  
}