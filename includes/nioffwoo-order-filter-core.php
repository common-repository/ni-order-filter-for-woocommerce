<?php 
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Filter_Core')){	
	class NiOFFWoo_Order_Filter_Core {
		var $nioffwoo_constant = array();  
		public function __construct($nioffwoo_constant = array()){
			$this->nioffwoo_constant =  $nioffwoo_constant ;
		}
	}
}

?>