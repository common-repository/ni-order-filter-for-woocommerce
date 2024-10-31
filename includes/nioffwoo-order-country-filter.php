<?php /*exist if directly called*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Country_Filter')){	

	/**
	* Class Description
	*
	* Adds custom filtering to the orders screen to allow filtering by order billing country.
	*/
	include_once("nioffwoo-function.php");
	class NiOFFWoo_Order_Country_Filter extends NiOFFWoo_Function {
		var $nioffwoo_constant = array();  
		var $is_hpos_enable = false;
		
		/**
		 * NiOFFWoo_Order_Country_Filter constructor.
		 *
		 *
		 */		
		public function __construct($nioffwoo_constant = array()){
			
			/*Set the constant value*/
			$this->nioffwoo_constant =  $nioffwoo_constant ;
			$this->is_hpos_enable = $this->is_hpos_enabled();
			
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				
				

				if ($this->is_hpos_enable){
					add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'filter_by_order_country' ) );
					add_filter('woocommerce_order_query_args',  array( $this, 'filter_woocommerce_orders_billing_country'));
				}else{
					// adds the country filtering dropdown to the orders page
					add_action( 'restrict_manage_posts', array( $this, 'filter_by_order_country' ) );
				}
				
				
				/*join filter*/
				add_filter( 'posts_join',  array( $this, 'add_order_country_join' ) );
				/*where query filter*/
				add_filter( 'posts_where', array( $this, 'add_order_country_where' ) );
				
			}
			
		}
		function filter_woocommerce_orders_billing_country($query_args){

			$page =  isset($_REQUEST["page"])? $_REQUEST["page"]:'';
			$action =  isset($_REQUEST["action"])?$_REQUEST["action"]:'-1';
			$order_country = isset( $_REQUEST["order_country"] )? $_REQUEST["order_country"]  : '';

			if ( isset($_GET['page']) && $_GET['page'] === 'wc-orders' && isset($_REQUEST['action']) && $_REQUEST['action'] === '-1' && !empty($_REQUEST['order_country']) ) {
				$query_args["billing_country"] = $order_country;
			}

			//error_log(json_encode($query_args));

			return $query_args;

		}
		
		
		/**
		 * Adds the country filtering dropdown to the orders list
		 *
		 */
		function filter_by_order_country(){
			$country = array();
			/*get country name for country dropdown*/

			if ($this->is_hpos_enable){
				$country = $this->get_order_country_hpos();
			}else{
				$country = $this->get_order_country();
			}
			
			
			
			?>
            <select name="order_country" id="order_country">
					<option value="">
						<?php esc_html_e( 'Filter by order country', 'nioffwoo' ); ?>
					</option>
					<?php foreach ( $country as $key=>$value ) : ?>
						<option value="<?php echo esc_attr( $value->billing_country ); ?>" <?php echo esc_attr( isset( $_GET['order_country'] ) ? selected( $value->billing_country, $_GET['order_country'], false ) : '' ); ?>>
							<?php echo esc_html( $value->country_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
            
			<?php
			
		}
		/**
		 * Modify SQL JOIN for filtering the orders by any country name
		 *
		 *
		 * @param string $join JOIN part of the sql query
		 * @return string $join modified JOIN part of sql query
		 */
		function add_order_country_join($join){
			global $typenow, $wpdb;

			if ( 'shop_order' === $typenow && isset( $_GET['order_country'] ) && ! empty( $_GET['order_country'] ) ) {
	
				$join .= " LEFT JOIN {$wpdb->prefix}postmeta billing_country ON {$wpdb->posts}.ID = billing_country.post_id ";
			}
	
			return $join;
		}
		/**
		  * Modify SQL Where for filtering the orders by any country name
		 *
		 *
		 * @param string $where WHERE part of the sql query
		 * @return string $where modified WHERE part of sql query
		 */
		function add_order_country_where($where){
			global $typenow, $wpdb;

			if ( 'shop_order' === $typenow && isset( $_GET['order_country'] ) && ! empty( $_GET['order_country'] ) ) {
	
				// prepare WHERE query part
				$where .= $wpdb->prepare(" AND billing_country.meta_key='_billing_country' AND billing_country.meta_value='%s'", wc_clean( $_GET['order_country'] ) );
			}
	
			return $where;
		}
		/**
		 *Get all order billing  country
		 *
		 *
		 * @return billing country array 
		 */
		function get_order_country(){
				global $wpdb;
			$query = "";	
			$query .= " SELECT  ";
			$query .= "	billing_country.meta_value as 'billing_country' ";
				
			$query .= "	FROM {$wpdb->prefix}posts as posts	";	
				
			$query .= "	LEFT JOIN  {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID ";
			
			$query .= " WHERE 1=1 ";
			$query .= " AND posts.post_type ='shop_order' ";
			$query .= " AND billing_country.meta_key ='_billing_country' ";
			$query .= " GROUP BY billing_country.meta_value";
			
			$query .= " Order BY billing_country.meta_value ASC";	
			
			
			$rows = $wpdb->get_results($query );
			
			
			$countries = $this->get_countries();
			
			//print_r($countries);
			
			foreach($rows as $key=>$value){
				$rows[$key]->country_name = isset($countries[$value->billing_country])?$countries[$value->billing_country]:'';
			}
			
			return $rows;
		}
		function get_order_country_hpos(){
			global $wpdb;
		$query = "";	
		$query .= " SELECT  ";
		$query .= "	billing_country.country as 'billing_country' ";
			
		$query .= "	FROM {$wpdb->prefix}wc_order_addresses as billing_country	";	
			
		//$query .= "	LEFT JOIN  {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID ";
		
		$query .= " WHERE 1=1 ";
		//$query .= " AND posts.post_type ='shop_order' ";
		$query .= " AND billing_country.address_type ='billing' ";
		$query .= " GROUP BY billing_country.country";
		
		$query .= " Order BY billing_country.country ASC";	
		
		
		$rows = $wpdb->get_results($query );
		
		
		$countries = $this->get_countries();
		
		//print_r($countries);
		
		foreach($rows as $key=>$value){
			$rows[$key]->country_name = isset($countries[$value->billing_country])?$countries[$value->billing_country]:'';
		}
		
		return $rows;
	}
		
		/**
		 *Get all country name with country code from woocommerce country class
		 *
		 *
		 * @return array of all country code with name
		 */
		function get_countries(){
			$countries_obj = new WC_Countries();
   			$countries_array = $countries_obj->get_countries();
			
			return  $countries_array ;
		} 
	}
}

?>