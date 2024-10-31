<?php /*exist if directly called*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Payment_Method_Filter')){	

	/**
	* Class Description
	*
	* Adds custom filtering to the orders screen to allow filtering by order payment method.
	*/
	include_once("nioffwoo-function.php");
	class NiOFFWoo_Order_Payment_Method_Filter extends NiOFFWoo_Function {
		var $nioffwoo_constant = array();  
		var $is_hpos_enable = false;
		
		/**
		 * NiOFFWoo_Order_Payment_Method_Filter constructor.
		 *
		 *
		 */		
		public function __construct($nioffwoo_constant = array()){
			
			/*Set the constant value*/
			$this->nioffwoo_constant =  $nioffwoo_constant ;
			$this->is_hpos_enable = $this->is_hpos_enabled();
			
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

				if ($this->is_hpos_enable){
					add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'filter_by_payment_method' ) );
					add_filter('woocommerce_order_query_args',  array( $this, 'filter_woocommerce_orders_payment_method'));

				}else{
						// adds the country filtering dropdown to the orders page
						add_action( 'restrict_manage_posts', array( $this, 'filter_by_payment_method' ) );
									
						/*join filter*/
						add_filter( 'posts_join',  array( $this, 'add_payment_method_join' ) );
						/*where query filter*/
						add_filter( 'posts_where', array( $this, 'add_order_payment_method_where' ) );
				}
				
			
				
			}
			
		}
		function filter_woocommerce_orders_payment_method($query_args){
			
			$page =  isset($_REQUEST["page"])? $_REQUEST["page"]:'';
			$action =  isset($_REQUEST["action"])?$_REQUEST["action"]:'-1';
			$order_country = isset( $_REQUEST["payment_method"] )? $_REQUEST["payment_method"]  : '';

			if ( isset($_GET['page']) && $_GET['page'] === 'wc-orders' && isset($_REQUEST['action']) && $_REQUEST['action'] === '-1' && !empty($_REQUEST['payment_method']) ) {
				$query_args["payment_method"] = $order_country;
			}

			//error_log(json_encode($query_args));

			return $query_args;
		}
		/**
		 * Adds the payment method filtering dropdown to the orders list
		 *
		 */
		function filter_by_payment_method(){
			$payment_method = array();
			/*get country name for country dropdown*/

			if ($this->is_hpos_enable){
				$payment_method = $this->get_payment_method_hpos() ; 
			}else{
				$payment_method = $this->get_payment_method();
			}
			

			
			
			?>
            <select name="payment_method" id="payment_method">
					<option value="">
						<?php esc_html_e( 'Filter by payment method', 'nioffwoo' ); ?>
					</option>
					<?php foreach ( $payment_method as $key=>$value ) : ?>
						<option value="<?php echo esc_attr( $value->payment_method ); ?>" <?php echo esc_attr( isset( $_GET['payment_method'] ) ? selected( $value->payment_method, $_GET['payment_method'], false ) : '' ); ?>>
							<?php echo esc_html( $value->payment_method_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
            
			<?php
			
		}
		/**
		 * Modify SQL JOIN for filtering the orders by any payment method
		 *
		 *
		 * @param string $join JOIN part of the sql query
		 * @return string $join modified JOIN part of sql query
		 */
		function add_payment_method_join($join){
			global $typenow, $wpdb;

			if ( 'shop_order' === $typenow && isset( $_GET['payment_method'] ) && ! empty( $_GET['payment_method'] ) ) {
	
				$join .= " LEFT JOIN {$wpdb->prefix}postmeta payment_method ON {$wpdb->posts}.ID = payment_method.post_id ";
			}
	
			return $join;
		}
		/**
		  * Modify SQL Where for filtering the orders by any payment method
		 *
		 *
		 * @param string $where WHERE part of the sql query
		 * @return string $where modified WHERE part of sql query
		 */
		function add_order_payment_method_where($where){
			global $typenow, $wpdb;

			if ( 'shop_order' === $typenow && isset( $_GET['payment_method'] ) && ! empty( $_GET['payment_method'] ) ) {
	
				// prepare WHERE query part
				$where .= $wpdb->prepare(" AND payment_method.meta_key='_payment_method' AND payment_method.meta_value='%s'", wc_clean( $_GET['payment_method'] ) );
			}
	
			return $where;
		}
		/**
		 *Get all order payment method
		 *
		 *
		 * @return payment method array 
		 */
		function get_payment_method(){
			global $wpdb;
			$query = "
				SELECT 
				payment_method.meta_value as 'payment_method'
				,payment_method_title.meta_value as 'payment_method_title'
				
				FROM {$wpdb->prefix}posts as posts	";	
				
			$query .= "	LEFT JOIN  {$wpdb->prefix}postmeta as payment_method ON payment_method.post_id=posts.ID ";
			$query .= "	LEFT JOIN  {$wpdb->prefix}postmeta as payment_method_title ON payment_method_title.post_id=posts.ID ";
			
			$query .= " WHERE 1=1 ";
			$query .= " AND posts.post_type ='shop_order' ";
			$query .= " AND payment_method.meta_key ='_payment_method' ";
			$query .= " AND payment_method_title.meta_key ='_payment_method_title' ";
			$query .= " GROUP BY payment_method.meta_value";
			
			$query .= " Order BY payment_method.meta_value ASC";	
			
			
			//$rows = $wpdb->get_results( $wpdb->prepare($query ));
			$rows = $wpdb->get_results($query );
			
			return $rows;
		}
		function get_payment_method_hpos(){
			global $wpdb;
		$query = "";	
		$query .= " SELECT  ";
		$query .= "	orders.payment_method as 'payment_method' ";
		$query .= ", orders.payment_method_title as 'payment_method_title' ";
			
		$query .= "	FROM {$wpdb->prefix}wc_orders as orders	";	
			
		//$query .= "	LEFT JOIN  {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID ";
		
		$query .= " WHERE 1=1 ";
		$query .= " AND orders.payment_method !='' ";
		$query .= " AND orders.status NOT IN  ('auto-draft')";
		$query .= " GROUP BY orders.payment_method";
		
		$query .= " Order BY orders.payment_method_title ASC";	
		
		
		$rows = $wpdb->get_results($query );
		
		
		
		return $rows;
	}
			
	}
}

?>