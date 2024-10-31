<?php 
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Function')){	
    class NiOFFWoo_Function {
        function __construct() {
        }
        /**
         * Check if HPOS (High-Performance Order Storage) is enabled in WooCommerce.
         *
         * @return bool True if HPOS is enabled, false otherwise.
         */
        function is_hpos_enabled() {
            if ( ! class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
                return false; // OrderUtil class doesn't exist, HPOS is likely not enabled
            }
        
            return Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        function print_data($data){
			print "<pre>";
			print_r($data);
			print "</pre>";
		}
    }
}
?>