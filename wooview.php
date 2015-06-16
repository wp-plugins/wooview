<?php
/*
   Plugin Name: WooView
   Plugin URI: http://www.wooviewapp.com/
   Description: WooView enables you to access your WooCommerce store on the go using the WooView iPhone App
   Version: 1.2.4
   Author: Joe Rucci
   Author URI: http://www.bcslbrands.com/
   Requires at least: 3.5
   Tested up to: 4.2
   License: GNU General Public License v3.0
   License URI: http://www.gnu.org/licenses/gpl-3.0.html

   Copyright: (c) 2014 BCSL Brands (wooview@bcslbrands.com)
  */
if (!defined('ABSPATH')) exit;

require_once('woo-includes/woo-functions.php');

/**
  * Globals
  */
$GLOBALS['wooview_wv_debug'] = false;
$GLOBALS['wooview_min_wp_version'] = '3.5';
$GLOBALS['wooview_min_wc_version'] = '2.0';
$GLOBALS['wooview_wc_active'] = is_woocommerce_active();
$GLOBALS['wooview_wv_version'] = '1.2.4';
$GLOBALS['wooview_wp_version'] = get_bloginfo('version');
$GLOBALS['wooview_wc_version'] = ($GLOBALS['wooview_wc_active']) ? get_option('woocommerce_version') : '0.0';
$GLOBALS['wooview_orders_default_limit'] = '25';
$GLOBALS['authentication_error'] = array('status' => 'error', 'code' => '1');

/**
  * WooView Admin 
  */
if (!class_exists('WooView_admin')) {
	class WooView_admin {
		public function __construct() {        
			if(is_admin()) {
				add_action('admin_menu', array(&$this, 'wooview_add_admin_page') );
				add_action('admin_init', array(&$this, 'wooview_add_admin_style') );
			}
		}
		function wooview_add_admin_style() {
			wp_register_style('wooview_style', plugins_url('css/style.css', __FILE__));
			wp_enqueue_style('wooview_style');
		}
		function wooview_add_admin_page() {
			add_menu_page('WooView', 'WooView', 'manage_options', 'wooview-admin', array(&$this, 'wooview_admin_page'), 'none');
		}
		function wooview_admin_page() {
			include('wooview-landing.php');
		}
	}
	$GLOBALS['wooview_admin'] = new WooView_admin('3.5');
}


/**
  * WooView API 
  */
if (!class_exists('WooView_XMLRPC')) {
	if($GLOBALS['wooview_wc_active']) {

		class WooView_XMLRPC {

			private $orders_manager;
			private $reporting_manager;

			public function __construct() {

				//Includes
				include_once('classes/class-wooview-orders.php');
				include_once('classes/class-wooview-reporting.php');
				include_once('classes/class-wooview-customers.php');

				//Initiate Managers
				$this->orders_manager = new WooView_Orders();
				$this->reporting_manager = new WooView_Reporting();
				$this->customers_manager = new WooView_Customers();

				//Register WooView XMLRPC Methods
				add_filter('xmlrpc_methods', array(&$this, 'register_new_xmlrpc_methods'));

				if($GLOBALS['wooview_wv_debug'] && is_admin()) {
					//add_action('admin_menu', function() { add_menu_page('WooView Debug', 'WooView Debug', 'manage_options', 'wooview-debug', array(&$this, 'wooview_debug_page'), ''); });
				}
			}


			/**
        * Private: Register New XMLRPC Methods
        * @param array $methods
        * @return BOOL 
        */
			function register_new_xmlrpc_methods($methods) {

				//Site
				$methods['wooview.get_site_info'] = array(&$this, 'wooview_get_site_info');

				//Orders
				$methods['wooview.get_orders'] = array(&$this, 'wooview_get_orders');
				$methods['wooview.get_orders_today'] = array(&$this, 'wooview_get_orders_today');
				$methods['wooview.add_order_note'] = array(&$this, 'wooview_add_order_note');
				$methods['wooview.delete_order_note'] = array(&$this, 'wooview_delete_order_note');
				$methods['wooview.update_order_status'] = array(&$this, 'wooview_update_order_status');

				//Reporting
				$methods['wooview.get_stats_today'] = array(&$this, 'wooview_get_stats_today');
				$methods['wooview.get_stats_last7days'] = array(&$this, 'wooview_get_stats_last7days');
				$methods['wooview.get_stats_thismonth'] = array(&$this, 'wooview_get_stats_thismonth');
				$methods['wooview.get_stats_lastmonth'] = array(&$this, 'wooview_get_stats_lastmonth');
				$methods['wooview.get_stats_thisyear'] = array(&$this, 'wooview_get_stats_thisyear');

				return $methods;
			}


			/**
        * Private: Check Authentication
        * @param array $args
        *   $args[0] username
        *   $args[1] password
        * @return BOOL 
        */
			function check_authentication($args) {
				global $wp_xmlrpc_server;
				$wp_xmlrpc_server->escape($args);
				if (!$user = $wp_xmlrpc_server->login($args[0], $args[1])) {
					return false;
				} else {
					return true;
				}
			}

			/**
        * Private: Get Site Info
        * @param array $args
        * @return array 
        */
			function wooview_get_site_info($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				return array('status' => "valid", 'data' => array('wv_version' => $GLOBALS['wooview_wv_version'],
																  'wp_version' => $GLOBALS['wooview_wp_version'],
																  'wc_version' => $GLOBALS['wooview_wc_version'],
																  'order_statuses' => $this->orders_manager->get_all_order_statuses()) );
			}

			/**
        * Private: Get Today's Orders
        * @param array $args
        * @return array 
        */
			function wooview_get_orders_today($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$date_range = array(array('year' => date_i18n('Y'), 'month' => date_i18n('m'),'day' => date_i18n('d')));
				$orders = $this->orders_manager->get_orders(false, array('detailed' => false, 'date_range' => $date_range));
				return array('status' => "valid", 'data' => $orders);
			}

			/**
        * Private: Get Orders
        * @param array $args
        * @return array 
        */
			function wooview_get_orders($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Setup defaults
				$id = (count($args) >= 3) ? $args[2] : false;
				$id = ($id == '') ? false : $id;

				$detailed = (count($args) >= 4) ? $args[3] : false;
				$detailed = ($detailed == 'true') ? true : false;

				$offset = (count($args) >= 5) ? $args[4] : 0;
				$limit = (count($args) >= 6) ? $args[5] : $GLOBALS['wooview_orders_default_limit'];

				//Get Data
				$orders = $this->orders_manager->get_orders($id, array('detailed' => $detailed, 'offset' => $offset, 'limit' => $limit));
				return array('status' => "valid", 'data' => $orders);
			}

			/**
        * Private: Add Order Note
        * @param array $args
        * @return array 
        */
			function wooview_add_order_note($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Setup defaults
				$id = (count($args) >= 3) ? $args[2] : false;
				$message = (count($args) >= 4) ? $args[3] : false;
				$is_public = (count($args) >= 5) ? $args[4] : false;

				if($id == false || $message == false) {
					return new IXR_Error(99, __('Incorrect parameters.'));
				}

				$notes = $this->orders_manager->add_order_note($id, $message, $is_public);
				if($notes) {
					return array('status' => "valid", 'data' => $notes);
				} else {
					return array('status' => "fail", 'data' => $notes);
				}
			}

			/**
        * Private: Delete Order Note
        * @param array $args
        * @return array 
        */
			function wooview_delete_order_note($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Setup defaults
				$order_id = (count($args) >= 3) ? $args[2] : false;
				$id = (count($args) >= 4) ? $args[3] : false;

				if($id == false || $order_id == false) {
					return new IXR_Error(99, __('Incorrect parameters.'));
				}

				$notes = $this->orders_manager->delete_order_note($order_id, $id);
				if($notes) {
					return array('status' => "valid", 'data' => $notes);
				} else {
					return array('status' => "fail", 'data' => $notes);
				}
			}

			/**
        * Private: Add Order Note
        * @param array $args
        * @return array 
        */
			function wooview_update_order_status($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Setup defaults
				$id = (count($args) >= 3) ? $args[2] : false;
				$status = (count($args) >= 4) ? $args[3] : false;

				if($id == false || $status == false) {
					return new IXR_Error(99, __('Incorrect parameters.'));
				}

				$status = $this->orders_manager->update_order_status($id, $status);
				if($status) {
					return array('status' => "valid", 'data' => $status);
				} else {
					return array('status' => "fail", 'data' => $status);
				}
			}

			/**
        * Private: Return Today's Stats
        * @param array $args
        * @return array 
        */
			function wooview_get_stats_today($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$report = $this->reporting_manager->get_sales_report('today');
				return array('status' => "valid", 'data' => $report);
			}

			/**
        * Private: Return Last 7 Days Stats
        * @param array $args
        * @return array 
        */
			function wooview_get_stats_last7days($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$report = $this->reporting_manager->get_sales_report('last_7_days');
				return array('status' => "valid", 'data' => $report);
			}

			/**
        * Private: Return This Month Stats
        * @param array $args
        * @return array 
        */
			function wooview_get_stats_thismonth($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$report = $this->reporting_manager->get_sales_report('this_month');
				return array('status' => "valid", 'data' => $report);
			}

			/**
        * Private: Return Last Month Stats
        * @param array $args
        * @return array 
        */
			function wooview_get_stats_lastmonth($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$report = $this->reporting_manager->get_sales_report('last_month');
				return array('status' => "valid", 'data' => $report);
			}

			/**
        * Private: Return This Year Stats
        * @param array $args
        * @return array 
        */
			function wooview_get_stats_thisyear($args) {

				//Authenticate User
				if(!$this->check_authentication($args)) { return new IXR_Error(1, __('Incorrect Login Creds.')); }

				//Get Data
				$report = $this->reporting_manager->get_sales_report('this_year');
				return array('status' => "valid", 'data' => $report);
			}


			function wooview_debug_page() {

			}

		}

		$GLOBALS['wooview_xmlrpc'] = new WooView_XMLRPC();

	}

}
?>