<?php /*exist if directly called*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Product_Filter')){	

	/**
	* Class Description
	*
	* Adds custom filtering to the orders screen to allow filtering by order product
	*/
	include_once("nioffwoo-function.php");
	class NiOFFWoo_Order_Product_Filter  extends NiOFFWoo_Function {
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
					add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_filter_by_order_product' ) );
					add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'filter_by_order_product' ), 10, 2 );


				}else{
					// adds the country filtering dropdown to the orders page
					add_action( 'restrict_manage_posts', array( $this, 'add_filter_by_order_product' ) );
									
					/*join filter*/
					add_filter( 'posts_join',  array( $this, 'add_order_product_join' ) );
					/*where query filter*/
					add_filter( 'posts_where', array( $this, 'add_order_product_where' ) );
				}

				
				
			}
			
		}
		function filter_by_order_product($pieces, $args ){
			if ( isset( $_GET["product_id"] ) && !empty( $_GET["product_id"] ) ) {
				global $wpdb;
				
				$product_variation  =  $_GET["product_id"];


				$product_variation_array  = explode('_', $product_variation);
				
				
				$new_product_id  = intval($product_variation_array [0]);
				$new_variation_id =  intval($product_variation_array [1]);

				$wc_orders = $wpdb->prefix . "wc_orders";  
				$woocommerce_order_items = $wpdb->prefix . "woocommerce_order_items";  
				$woocommerce_order_itemmeta = $wpdb->prefix . "woocommerce_order_itemmeta";  

				$join = "";
				$join .= " LEFT JOIN {$woocommerce_order_items} AS order_items ON order_items.order_id ={$wc_orders}.id   ";
				$join .= " LEFT JOIN {$woocommerce_order_itemmeta} AS order_itemmeta_product ON order_itemmeta_product.order_item_id =order_items.order_item_id ";
				$join .= " LEFT JOIN {$woocommerce_order_itemmeta} AS order_itemmeta_product_variation ON order_itemmeta_product_variation.order_item_id =order_items.order_item_id ";

				$pieces['join'] = $join ;
				$pieces['where'] .= " AND order_items.order_item_type ='line_item'";

				$pieces['where'] .= " AND order_itemmeta_product.meta_key ='_product_id'";
				$pieces['where'] .= " AND order_itemmeta_product.meta_value ='{$new_product_id}'";

				$pieces['where'] .= " AND order_itemmeta_product_variation.meta_key ='_variation_id'";
				$pieces['where'] .= " AND order_itemmeta_product_variation.meta_value ='{$new_variation_id}'";

			}

			
			return $pieces;
		}
		/**
		 * Adds the country filtering dropdown to the orders list
		 *
		 */
		function add_filter_by_order_product(){
			/*get country name for country dropdown*/
			$order_product = $this->get_order_product()	;
			
			?>
            <select name="product_id" class="wfobpp-select2"  id="product_id">
					<option value="">
						<?php esc_html_e( 'Filter by order product', 'nioffwoo' ); ?>
					</option>
					<?php foreach ( $order_product as $key=>$value ) : ?>
						<option value="<?php echo esc_attr( $value->product_id ); ?>" <?php echo esc_attr( isset( $_GET['product_id'] ) ? selected( $value->product_id, $_GET['product_id'], false ) : '' ); ?>>
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

			if ( 'shop_order' === $typenow && isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] ) ) {
	
				$join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items order_items ON {$wpdb->posts}.ID = order_items .order_id ";
				$join .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as product_id ON product_id.order_item_id=order_items.order_item_id ";
				$join .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as variation_id ON variation_id.order_item_id=order_items.order_item_id ";
				
				
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
			$new_product_id = 0;
			$new_variation_id = 0;
			$product_variation ="";

			if ( 'shop_order' === $typenow && isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] ) ) {
				$product_variation = wc_clean( $_GET['product_id'] ) ;
				
				$product_variation_array  = explode('_', $product_variation);
				
				
				$new_product_id  = intval($product_variation_array [0]);
				$new_variation_id =  intval($product_variation_array [1]);
				
				// prepare WHERE query part
				$where .= $wpdb->prepare(" AND product_id.meta_key='_product_id' AND product_id.meta_value='%s'", wc_clean( $new_product_id) );
				$where .= $wpdb->prepare(" AND variation_id.meta_key='_variation_id' AND variation_id.meta_value='%s'", wc_clean( $new_variation_id) );
			}
			return $where;
		}
		/**
		 *Get all order order product
		 *
		 *
		 * @return order product array 
		 */
		function get_order_product(){
				global $wpdb;
			$query = "";
			$query .= "	SELECT ";	
			$query .= "	order_items.order_item_name as 'order_item_name'";	
			$query .= "	,CONCAT(product_id.meta_value, '_', variation_id.meta_value) as 'product_id'";	
				
			if ($this->is_hpos_enable){
				$query .= "	FROM {$wpdb->prefix}wc_orders as posts	";	
			}else{
				$query .= "	FROM {$wpdb->prefix}posts as posts	";	
			}
				
			$query .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_items as order_items ON order_items.order_id=posts.ID ";
			$query .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as product_id ON product_id.order_item_id=order_items.order_item_id";
			$query .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as variation_id ON variation_id.order_item_id=order_items.order_item_id";
			
			
			
			$query .= " WHERE 1=1 ";
			if ($this->is_hpos_enable){
				$query .= " AND posts.type ='shop_order' ";
			}else{
				$query .= " AND posts.post_type ='shop_order' ";
			}
			$query .= " AND order_items.order_item_type ='line_item' ";
			
			$query .= " AND product_id.meta_key ='_product_id' ";
			$query .= " AND variation_id.meta_key ='_variation_id' ";
			
			$query .= " GROUP BY product_id.meta_value, variation_id.meta_value";
			
			$query .= " Order BY order_items.order_item_name  ASC";	
			
			//$query = $wpdb->prepare($query );
			//$rows = $wpdb->get_results( $wpdb->prepare($query ));
			
			$rows = $wpdb->get_results($query );
			
			return $rows;
		}
		
	}
}

?>