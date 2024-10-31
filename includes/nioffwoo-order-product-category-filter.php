<?php /*exist if directly called*/
if ( ! defined( 'ABSPATH' ) ) { exit;}
if(!class_exists('NiOFFWoo_Order_Product_Category_Filter')){	

	/**
	* Class Description
	*
	* Adds custom filtering to the orders screen to allow filtering by order product
	*/
	include_once("nioffwoo-function.php");
	class NiOFFWoo_Order_Product_Category_Filter  extends NiOFFWoo_Function {
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
					add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_filter_category_product' ) );
					add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'filter_category_product' ), 10, 2 );


				}else{
					// adds the country filtering dropdown to the orders page
					add_action( 'restrict_manage_posts', array( $this, 'add_filter_category_product' ) );
									
					/*join filter*/
					add_filter( 'posts_join',  array( $this, 'add_order_product_join' ) );
					/*where query filter*/
					add_filter( 'posts_where', array( $this, 'add_order_product_where' ) );
					//error_log("test");
					//add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'filter_category_product' ), 10, 2 );

				}

				
				
			}
			
		}
		function filter_category_product($pieces, $args ){
			if ( isset( $_GET["category_id"] ) && !empty( $_GET["category_id"] ) ) {
				global $wpdb;
				
				$category_id  =  $_GET["category_id"];


				$wc_orders = $wpdb->prefix . "wc_orders";  
				$woocommerce_order_items = $wpdb->prefix . "woocommerce_order_items";  
				$woocommerce_order_itemmeta = $wpdb->prefix . "woocommerce_order_itemmeta";  
				$term_relationships = $wpdb->prefix . "term_relationships";  
				$term_taxonomy = $wpdb->prefix . "term_taxonomy";  

				$join = "";
				$join .= " LEFT JOIN {$woocommerce_order_items} AS order_items ON order_items.order_id ={$wc_orders}.id   ";
				$join .= " LEFT JOIN {$woocommerce_order_itemmeta} AS order_itemmeta_product ON order_itemmeta_product.order_item_id =order_items.order_item_id ";
				//$join .= " LEFT JOIN {$woocommerce_order_itemmeta} AS order_itemmeta_product_variation ON order_itemmeta_product_variation.order_item_id =order_items.order_item_id ";

				$join .= " LEFT JOIN {$term_relationships} AS relationships ON relationships.object_id =order_itemmeta_product.meta_value ";
				$join .= " LEFT JOIN {$term_taxonomy} AS taxonomy ON taxonomy.term_taxonomy_id =relationships.term_taxonomy_id ";




				$pieces['join'] = $join ;
				$pieces['where'] .= " AND order_items.order_item_type ='line_item'";

				$pieces['where'] .= " AND order_itemmeta_product.meta_key ='_product_id'";
				
				$pieces['where'] .= " AND taxonomy.taxonomy ='product_cat'";
				$pieces['where'] .= " AND taxonomy.term_id ='{$category_id}'";

			}

			
			return $pieces;
		}
		/**
		 * Adds the country filtering dropdown to the orders list
		 *
		 */
		function add_filter_category_product(){
			/*get country name for country dropdown*/
			$product_category = $this->get_product_category()	;
			//$this->print_data($product_category);
			
			?>
            <select name="category_id" class="wfobpp-select2"  id="category_id">
					<option value="">
						<?php esc_html_e( 'Filter by category product', 'nioffwoo' ); ?>
					</option>
					<?php foreach ( $product_category as $key=>$value ) : ?>
						<option value="<?php echo esc_attr($key ); ?>" <?php echo esc_attr( isset( $_GET['category_id'] ) ? selected($key, $_GET['category_id'], false ) : '' ); ?>>
							<?php echo esc_html( $value); ?>
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

			if ( 'shop_order' === $typenow && isset( $_GET['category_id'] ) && ! empty( $_GET['category_id'] ) ) {
	
				$wc_orders = $wpdb->prefix . "posts";  
				$woocommerce_order_items = $wpdb->prefix . "woocommerce_order_items";  
				$woocommerce_order_itemmeta = $wpdb->prefix . "woocommerce_order_itemmeta";  
				$term_relationships = $wpdb->prefix . "term_relationships";  
				$term_taxonomy = $wpdb->prefix . "term_taxonomy";  

				/*
				$join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items order_items ON {$wpdb->posts}.ID = order_items .order_id ";
				$join .= "	LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as product_id ON product_id.order_item_id=order_items.order_item_id ";
				
				$join .= " LEFT JOIN {$term_relationships} AS relationships ON relationships.object_id =order_itemmeta_product.meta_value ";
				$join .= " LEFT JOIN {$term_taxonomy} AS taxonomy ON taxonomy.term_taxonomy_id =relationships.term_taxonomy_id ";
				*/

				$join .= " LEFT JOIN {$woocommerce_order_items} AS order_items ON order_items.order_id ={$wc_orders}.id   ";
				$join .= " LEFT JOIN {$woocommerce_order_itemmeta} AS order_itemmeta_product ON order_itemmeta_product.order_item_id =order_items.order_item_id ";
			

				$join .= " LEFT JOIN {$term_relationships} AS relationships ON relationships.object_id =order_itemmeta_product.meta_value ";
				$join .= " LEFT JOIN {$term_taxonomy} AS taxonomy ON taxonomy.term_taxonomy_id =relationships.term_taxonomy_id ";

				
				
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

			if ( 'shop_order' === $typenow && isset( $_GET['category_id'] ) && ! empty( $_GET['category_id'] ) ) {
				$category_id = wc_clean( $_GET['category_id'] ) ;
				

				// $where .= $wpdb->prepare(" AND order_itemmeta_product.meta_key='_product_id'");
				// $where .= $wpdb->prepare(" AND taxonomy.taxonomy ='product_cat'");
				// $where .= $wpdb->prepare("  AND taxonomy.term_id ='{$category_id}'");


				$where .= " AND order_itemmeta_product.meta_key = '_product_id'";
				$where .= " AND taxonomy.taxonomy = 'product_cat'";
				$where .= $wpdb->prepare(" AND taxonomy.term_id = %d", $category_id);


			}
			//error_log(json_encode( $where));
			//error_log(json_encode( $category_id));
			return $where;
		}
		/**
		 *Get all order order product
		 *
		 *
		 * @return order product array 
		 */
		function get_product_category(){

			$terms = get_terms( array('taxonomy' => 'product_cat', 'fields' => 'id=>name' ) );

			$fields = array();
			//$fields[0] = esc_html__( 'All Categories', 'woocommerce-filter-orders-by-product' );
	
			foreach ( $terms as $id => $name ) {
				$fields[$id] = $name;
			}

			return $fields;

		}
		
	}
}

?>