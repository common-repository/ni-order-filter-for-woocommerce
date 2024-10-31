<?php
/*
Plugin Name: Ni Order Filter For WooCommerce
Description: Enhance WooCommerce order management with the Ni Order Filter plugin. It simplifies admin tasks by allowing powerful filtering of orders by various criteria, streamlining eCommerce operations.
Author: anzia
Version: 1.0.7
Author URI: http://naziinfotech.com/
Plugin URI: https://wordpress.org/plugins/ni-order-filter-for-woocommerce
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.html
Text Domain: nioacowfw
Domain Path: /languages/
Requires at least: 4.7
Tested up to: 6.6.1
WC requires at least: 3.0.0
WC tested up to: 9.1.4
Last Updated Date: 19-August-2023
Requires PHP: 7.0

*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('Ni_Order_Filter_For_WooCommerce')){	
	class Ni_Order_Filter_For_WooCommerce{
		
		var $nioffwoo_constant = array();  
		public function __construct(){
			$this->nioffwoo_constant = array();
			
			$this->nioffwoo_constant['__FILE__'] = __FILE__;
			$this->nioffwoo_constant['plugin_dir_url'] = plugin_dir_url( __FILE__ );
			$this->nioffwoo_constant['manage_options'] = 'manage_options';
			$this->nioffwoo_constant['menu_name'] = 'nioacowfw-dashboard';
			$this->nioffwoo_constant['menu_icon'] = 'dashicons-media-document';
			add_action('plugins_loaded', array($this, 'plugins_loaded'));
			add_action('admin_notices', array($this, 'nioffwoo_check_woocommece_active'));
			add_action( 'before_woocommerce_init',  array(&$this,'before_woocommerce_init') );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 15 );

		}
		function add_scripts_and_styles(){
			$screen = get_current_screen();
	
			if( !in_array( $screen->id, array( 'edit-shop_order', 'woocommerce_page_wc-orders' ) ) ) return;
	
			wp_add_inline_script( 'selectWoo', 'jQuery(document).ready(function($){$(".wfobpp-select2").selectWoo();});' );
		}
		function before_woocommerce_init(){
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}	 
		}
		function plugins_loaded(){
		
			$post_type = sanitize_text_field( isset($_GET['post_type']) ? $_GET['post_type'] : '');

			
			$page =  isset($_REQUEST["page"])? isset($_REQUEST["page"]):'';
			$action =  isset($_REQUEST["action"])? isset($_REQUEST["action"]):'-1';
			
			if(($post_type == 'shop_order') || ($page  =="wc-orders" && $action =='-1' ) ){	
							
				require_once("includes/nioffwoo-order-filter-core.php");
				$obj = new NiOFFWoo_Order_Filter_Core($this->nioffwoo_constant);
				
				require_once("includes/nioffwoo-order-country-filter.php");
				$country = new NiOFFWoo_Order_Country_Filter($this->nioffwoo_constant);
				
				
				require_once("includes/nioffwoo-order-payment-method-filter.php");
				$payment_method = new NiOFFWoo_Order_Payment_Method_Filter($this->nioffwoo_constant);
				
				require_once("includes/nioffwoo-order-product-filter.php");
				$order_product = new NiOFFWoo_Order_Product_Filter($this->nioffwoo_constant);
				
				require_once("includes/nioffwoo-order-coupon-filter.php");
				$order_coupon = new NiOFFWoo_Order_Coupon_Filter($this->nioffwoo_constant);
				
				require_once("includes/nioffwoo-order-shipping-filter.php");
				$order_shipping = new NiOFFWoo_Order_Shipping_Filter($this->nioffwoo_constant);


				//nioffwoo-order-product-category-filter
				//NiOFFWoo_Order_Product_Category_Filter

				require_once("includes/nioffwoo-order-product-category-filter.php");
				$order_shipping = new NiOFFWoo_Order_Product_Category_Filter($this->nioffwoo_constant);
		
			}
		
			
		}
		function nioffwoo_check_woocommece_active(){
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				esc_html_e( "<div class='error'><p><strong>Ni Country Sales Report For WooCommerce</strong> requires <strong> WooCommerce active plugin</strong> </p></div>");
			}
		}
		
	
	}/*End Class*/
}/*End Class Check*/

$obj = new Ni_Order_Filter_For_WooCommerce();
