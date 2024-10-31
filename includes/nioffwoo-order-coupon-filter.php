<?php /*exist if directly called*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Coupon_Filter')){	

	/**
	* Class Description
	*
	* Adds custom filtering to the orders screen to allow filtering by order product
	*/
	include_once("nioffwoo-function.php");
	class NiOFFWoo_Order_Coupon_Filter extends NiOFFWoo_Function {
		var $nioffwoo_constant = array();  
		var $is_hpos_enable = false;
		
		/**
		 * NiOFFWoo_Order_Coupon_Filter constructor.
		 *
		 *
		 */		
		public function __construct($nioffwoo_constant = array()){
			
			/*Set the constant value*/
			$this->nioffwoo_constant =  $nioffwoo_constant ;
			$this->is_hpos_enable = $this->is_hpos_enabled();
			
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				
				if ($this->is_hpos_enable){
					add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_filter_by_coupon_code' ) );
					add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'filter_by_coupon_code' ), 10, 2 );

				}else{
					// adds the country filtering dropdown to the orders page
					add_action( 'restrict_manage_posts', array( $this, 'add_filter_by_coupon_code' ) );
									
					/*join filter*/
					add_filter( 'posts_join',  array( $this, 'add_order_product_join' ) );
					/*where query filter*/
					add_filter( 'posts_where', array( $this, 'add_order_product_where' ) );
				}
				
				
			}
			
		}
		public function filter_by_coupon_code( $pieces, $args ) {

			if ( isset( $_GET["order_coupon"] ) && !empty( $_GET["order_coupon"] ) ) {
				global $wpdb;
				
				$order_coupon  =  $_GET["order_coupon"];
				$wc_orders = $wpdb->prefix . "wc_orders";  
				$woocommerce_order_items = $wpdb->prefix . "woocommerce_order_items";  

				$pieces['join'] = " LEFT JOIN {$woocommerce_order_items} AS order_items ON order_items.order_id ={$wc_orders}.id " ;
				$pieces['where'] .= " AND order_items.order_item_type ='coupon'";
				$pieces['where'] .= " AND order_items.order_item_name ='{$order_coupon}'";

			}
			
			return $pieces;
		}
		
		
		/**
		 * Adds the country filtering dropdown to the orders list
		 *
		 */
		function add_filter_by_coupon_code(){
			/*get country name for country dropdown*/
			$order_product = $this->get_coupon_code()	;
			
			?>
            <select name="order_coupon" class="wfobpp-select2"  id="order_coupon">
					<option value="">
						<?php esc_html_e( 'Filter by order coupon', 'nioffwoo' ); ?>
					</option>
					<?php foreach ( $order_product as $key=>$value ) : ?>
						<option value="<?php echo esc_attr( $value->order_item_name ); ?>" <?php echo esc_attr( isset( $_GET['order_coupon'] ) ? selected( $value->order_item_name, $_GET['order_coupon'], false ) : '' ); ?>>
							<?php echo esc_html( $value->order_item_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
            
			<?php
			
		}
		/**
		 * Modify SQL JOIN for filtering the orders by any order product
		 *
		 *
		 * @param string $join JOIN part of the sql query
		 * @return string $join modified JOIN part of sql query
		 */
		function add_order_product_join($join){
			global $typenow, $wpdb;

			if ( 'shop_order' === $typenow && isset( $_GET['order_coupon'] ) && ! empty( $_GET['order_coupon'] ) ) {
	
				$join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items order_items ON {$wpdb->posts}.ID = order_items .order_id ";
				
				
				
			}
	
			return $join;
		}
		/**
		  * Modify SQL Where for filtering the orders by any order product
		 *
		 *
		 * @param string $where WHERE part of the sql query
		 * @return string $where modified WHERE part of sql query
		 */
		function add_order_product_where($where){
			global $typenow, $wpdb;
			

			if ( 'shop_order' === $typenow && isset( $_GET['order_coupon'] ) && ! empty( $_GET['order_coupon'] ) ) {
				
				// prepare WHERE query part
				$where .= $wpdb->prepare(" AND order_items.order_item_type ='coupon' AND order_items.order_item_name='%s'", wc_clean( $_GET['order_coupon']) );
				
			}
			return $where;
		}
		/**
		 *Get all order order product
		 *
		 *
		 * @return order product array 
		 */
		function get_coupon_code(){
				global $wpdb;
			$query = "";
				$query .= "		SELECT ";
				$query .= "		order_items.order_item_name as 'order_item_name'"; 
				
				if ($this->is_hpos_enable){
					$query .= "	FROM {$wpdb->prefix}wc_orders as posts	";	
				}else{
					$query .= "	FROM {$wpdb->prefix}posts as posts	";	
				}
				
				
				$query .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_items as order_items ON order_items.order_id=posts.ID ";
			
			
			
			
			$query .= " WHERE 1=1 ";
			if ($this->is_hpos_enable){
				$query .= " AND posts.type ='shop_order' ";
			}else{
				$query .= " AND posts.post_type ='shop_order' ";
			}
			
			$query .= " AND order_items.order_item_type ='coupon' ";
			
		
			
			$query .= " GROUP BY order_items.order_item_name";
			
			$query .= " Order BY order_items.order_item_name ASC";	
			
			//$query = $wpdb->prepare($query );
			//$rows = $wpdb->get_results( $wpdb->prepare($query ));
			
			$rows = $wpdb->get_results( $query );
			
			return $rows;
		}
		
	}
}

?>