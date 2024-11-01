<?php


namespace Wrpl\Inc\Controller;
use Wrpl\Inc\Controller\PriceListController;
use Wrpl\Inc\Functions\PriceList;

class ProductController{

    public $price_list_controller;

    function __construct()
    {
        $this->price_list_controller =  new PriceListController();
    }

    public function register(){

        //get all products
        add_action( 'wp_ajax_wrpl_get_products', array($this,'getProducts' ));

        //edit price
        add_action( 'wp_ajax_wrpl_edit_price', array($this,'editPrice' ));

        //get categories
        add_action( 'wp_ajax_wrpl_get_categories', array($this,'getProductParentCategories' ));

    }

    /**
     * @return array
     * Get all products
     */
    function getAllProducts(){
        global $wpdb;

        $products = $wpdb->get_results(
	        $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "posts WHERE post_type IN (%s,%s) AND post_status NOT IN (%s,%s)", 'product','product_variation','auto-draft','trash','_sku'),ARRAY_A
        );

        //remove product with parent = 0 and variations
        $final_products = array();
        foreach ($products as $product){
            if($this->isProductHasVariations($product['ID'])){
                continue;
            }
	        if($product['post_type'] == 'product_variation' && $product['post_parent'] == 0){
		        continue;
	        }
            array_push($final_products,$product);
        }
        return $final_products;
    }

    //todo (Refactorize, Optimization issue)=> https://app.clickup.com/t/2de3ng6
    function getProducts(){
	    $starttime = microtime(true);
        $price_list = intval(sanitize_text_field($_POST['price_list']));
        $start = intval(sanitize_text_field($_POST['start']));
        $length = intval(sanitize_text_field($_POST['length']));
        $search = isset($_POST['search']['value']) ? sanitize_title(trim($_POST['search']['value'])) : sanitize_title(trim($_POST['search']));
        $category_id = intval(sanitize_text_field($_POST['category_id']));

        $products_cat_search = $this->getProductByCategory($category_id,$search,$length,$start);
        $products_with_search  = $products_cat_search['data'];

	    $count_products_with_search = $products_cat_search['total'];

        $products = $products_with_search;
        $products_data = array();
        foreach ($products as $product){
        	    $wc_prod_var_o = wc_get_product( $product['ID'] );
                $image_values = wp_get_attachment_image_src( get_post_thumbnail_id($product['ID']), 'single-post-thumbnail' );
                $product['post_title'] = strlen($product['post_title']) > 50 ? substr($product['post_title'],0,50) . "..." : $product['post_title'];
                $product['image'] = $image_values[0];
	            $product['price'] = round(floatval($this->getRegularPrice($product['ID'],$price_list)),2);
	            $product['type'] = $wc_prod_var_o->get_type();
                $product['sku'] = ($wc_prod_var_o->get_sku() == '') ? 'N/A' : $wc_prod_var_o->get_sku() ;
                $product['sale_price'] = round(floatval($this->getSalesPrice($product['ID'],$price_list)),2);
                if($product['post_type'] == 'product_variation'){
                    $product['edit_url'] = WRPL_ADMIN_URL . 'post.php?post=' . $product['post_parent'] . '&action=edit';
                    $product['guid']      = $wc_prod_var_o->get_permalink();
                    $product['categories'] = get_the_terms( $product['post_parent'], 'product_cat' ) ?  str_replace('&amp;','&',wrpl_convert_to_separate_value(get_the_terms( $product['post_parent'], 'product_cat' ),'name')) : '';
                }else{
                    $product['edit_url'] = WRPL_ADMIN_URL . 'post.php?post=' . $product['ID'] . '&action=edit';
                    $product['guid'] = $wc_prod_var_o->get_permalink();
                    $product['categories'] = get_the_terms( $product['ID'], 'product_cat' ) ?  str_replace('&amp;','&',wrpl_convert_to_separate_value(get_the_terms( $product['ID'], 'product_cat' ),'name') ): '';
                }
                array_push($products_data,$product);
        }
	    $endtime = microtime(true);
        echo json_encode(array('success' => true,'data'=>$products_data,'recordsFiltered' => $count_products_with_search, 'delay' => $endtime - $starttime));
        wp_die();
    }

    /**
     * @param $cat_id
     * @param $search
     * @param $limit
     * @param $offset
     * @return array
     * Get products by category
     */
    function getProductByCategory($cat_id,$search,$limit = 1000000,$offset = 0){
		global $wpdb;
        $category_query = $cat_id > 0  ? "INNER JOIN " . $wpdb->prefix . "term_relationships ON (ID = object_id)  WHERE term_taxonomy_id = $cat_id AND" : 'WHERE';
		//var_dump($category_query);exit;
        $products = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  * FROM $wpdb->prefix" . "posts " . $category_query .
                                                " post_type IN ('product_variation','product') AND post_status NOT IN ('auto-draft','trash')  AND ( post_title LIKE '%$search%')
                                                AND ID NOT IN (SELECT ID FROM $wpdb->prefix" . "posts WHERE post_type = 'product_variation' AND post_parent = 0 )
                                                AND ID NOT IN (SELECT ID FROM $wpdb->prefix" . "posts INNER JOIN $wpdb->prefix" . "term_relationships ON ID = object_id WHERE term_taxonomy_id = 4) ORDER BY post_parent, ID LIMIT $offset,$limit" ,ARRAY_A);
	    $found_rows = $wpdb->get_results('SELECT FOUND_ROWS() as count', OBJECT);
	    $total = 0;
	    if($found_rows > 0){
		    $total = intval($found_rows[0]->count);
	    }

        return array('total' => $total,'data' => $products);
    }

    /**
     * @param $cat_id
     * @param $product_id
     * @return bool
     * Check if a product belong to certain category (need improvement)
     */
    function wrpl_product_has_category($cat_id,$product_id){
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "term_relationships WHERE term_taxonomy_id='$cat_id' AND object_id='$product_id'", ARRAY_A);
        if(count($results) > 0){
            return true;
        }
        return false;
    }

    /**
     * @param $products
     * @param $search
     * @return array
     */
    function wrpl_filter_category_product($products,$search){
        $final_products = array();
        foreach ($products as $product){
            $categories = wrpl_convert_to_separate_value(get_the_terms( $product['ID'], 'product_cat' ),'name');
                if( strpos($categories,$search)  !== false ){
                array_push($final_products,$product);
            }
        }
        return $final_products;
    }

    /**
     * @param $id
     * @param $price_type
     * @return int|mixed
     * Get the default price
     */
    function getPriceDefault($id,$price_type){
        global $wpdb;
        $products = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "postmeta WHERE post_id = %d AND meta_key = %s", $id ,$price_type)
        );
        $products = wrpl_stdToArray($products);
        if(count($products)>0){
            return $products[0]['meta_value'];
        }
        return 0;
    }

    /**
     * @param $id
     * @param $price_list
     * @param $price_type
     * @return int
     */
    function getPriceNotDefault($id,$price_list,$price_type){
        global $wpdb;
        $products = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "wr_price_lists_price WHERE id_product = %d AND id_price_list = %d", $id ,$price_list),ARRAY_A
        );
        if(count($products)>0){
            return $products[0][$price_type] ;
        }
        return 0;
    }

    function getSalesPrice($id,$price_list){

        if($price_list == 1){
            return $this->getPriceDefault($id,'_sale_price');
        }else{
            return $this->getPriceNotDefault($id,$price_list,'sale_price');
        }

    }

    function getRegularPrice($id,$price_list){

        if($price_list == 1){
            return $this->getPriceDefault($id,'_regular_price');
        }else {
            return $this->getPriceNotDefault($id, $price_list, 'price');

        }

    }

    function getSku($id){
        global $wpdb;

        $products = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "postmeta WHERE post_id = %d AND meta_key = %s", $id,'_sku'),ARRAY_A
        );
        return count($products) > 0 ? $products[0]['meta_value'] : '' ;
    }

    function  isProductHasVariations($id){
        global $wpdb;

        $products = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "posts WHERE post_parent = %d AND post_type = %s", $id,'product_variation')
        );

        if(count(wrpl_stdToArray($products))>0){
            return true;
        }
        return false;
    }

    /**
     * @param $id
     * @param $price_list
     * @param $price
     * @param $sale_price
     * Insert or Update new price for a product belong to a certain price list
     */
    function updateOrInsertPrice($id,$price_list,$price,$sale_price){
        global $wpdb;
        $products = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "wr_price_lists_price WHERE id_price_list = %d AND id_product = %d", $price_list,$id)
        );
        if(count($products)>0){
            $wpdb->query("UPDATE $wpdb->prefix" . "wr_price_lists_price SET price='$price',sale_price = '$sale_price' WHERE id_price_list='$price_list' AND id_product = '$id'");

        }else{
            $wpdb->query("INSERT INTO $wpdb->prefix" . "wr_price_lists_price (id_price_list, id_product, price, sale_price) VALUES ('$price_list', '$id', '$price','$sale_price')");
        }
    }

    /**
     * Edit the product price for a price list
     */
    function editPrice(){

        $post_id = intval(sanitize_text_field($_POST['id']));
        $price = empty(floatval(sanitize_text_field($_POST['price']))) ? 0 : floatval(sanitize_text_field($_POST['price']));
        $sale_price = empty(floatval(sanitize_text_field($_POST['sale_price']))) ? 0 : floatval(sanitize_text_field($_POST['sale_price']));
        $price_list = intval(sanitize_text_field($_POST['price_list']));

        if($sale_price > $price && ($price + $sale_price != 0)){
        	if($price_list == 1){
		        $price = get_post_meta($post_id, '_regular_price');
		        $sale_price = get_post_meta($post_id, '_sale_price');
	        }else{
		        $price = $this->getPriceNotDefault($post_id, $price_list, 'price');
		        $sale_price = $this->getPriceNotDefault($post_id, $price_list, 'sale_price');
	        }
	        echo json_encode(array('success' => 'false', 'price' => $price, 'sale_price' => $sale_price, 'msg' => 'Sale price need to be higher than regular price'));
	        wp_die();
        }

        if($price_list == 1){

            if($sale_price < $price && $sale_price > 0 ){ //S<R and S>0
	            delete_post_meta($post_id, '_sale_price');
	            delete_post_meta($post_id, '_price');
	            delete_post_meta($post_id, '_regular_price');
                update_post_meta($post_id, '_regular_price', $price);
                update_post_meta($post_id, '_price', $sale_price);
                update_post_meta($post_id, '_sale_price', $sale_price);
                $result = array('success' => true, 'price' => $price,'sale_price' => $sale_price);
            }else{
	            delete_post_meta($post_id, '_sale_price');
	            delete_post_meta($post_id, '_price');
	            delete_post_meta($post_id, '_regular_price');
                update_post_meta($post_id, '_regular_price', $price);
                update_post_meta($post_id, '_price', $price);
                $result = array('success' => true, 'price' => $price,'sale_price' => $sale_price);
            }

        }else{
            $this->updateOrInsertPrice($post_id,$price_list,$price,$sale_price);
            $result = array('success' => true, 'price' => $price,'sale_price' => $sale_price);
        }
        $this->wrpl_remove_product_price_caching($post_id);
        wc_delete_product_transients( $post_id );
        clean_post_cache( $post_id );
        wp_update_post(array('ID'=> $post_id,'post_modified' => date('Y-m-d H:i:s'), 'post_modified_gmt' => date('Y-m-d H:i:s')));
        echo json_encode($result);
        wp_die();
    }

    /**
     * @param $id
     * @param $price_list
     * @param $key
     * @param int $factor
     * @return float[]|int[]
     * Get the max and mix of a variable product that do not belong to the Default List
     */
    function getMinMaxPriceVariationNoDefault($id,$price_list,$key,$factor=1){
        global $wpdb;
        $max = 0;
        $min = 1234567890;
        $variations = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "wr_price_lists_price INNER JOIN $wpdb->prefix" . "posts ON id_product = $wpdb->prefix" . "posts.ID WHERE post_parent = $id AND id_price_list = %d",$price_list),ARRAY_A
        );

        foreach($variations as $variation){
            if($variation[$key] > $max){
                $max = $variation[$key];
            }
            if($variation[$key] < $min && $variation[$key] != 0){ //la variación menor no pueder ser 0, en caso que sea 0 no se tomara para mostrar en el product page ya que existe una condicion en PriceLIst.php en el metodo wrpl_woocommerce_price_html() if(  $min_max_sale['min']>0 && $min_max_sale['min']<$min_max['min']) que si 0 o menor
                $min = $variation[$key];
            }
        }
        $min = $min === 1234567890 ? 0 : $min;
        return array('min' => $min*$factor, 'max' => $max*$factor);
    }

    /**
     * @param $id
     * @param $price_type
     * @param int $factor
     * @return array
     * Get the max and mix of a variable product that belong to the Default List
     */
    function getMinMaxPriceVariationDefault($id,$price_type,$factor = 1){
        global $wpdb;
        $variations = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->prefix" . "posts INNER JOIN $wpdb->prefix" . "postmeta ON ID=post_id WHERE post_type = %s AND post_parent = $id AND meta_key = %s", 'product_variation',$price_type),ARRAY_A
        );

        $max = 0;
        $min = 1234567890;
        foreach($variations as $variation){
            if($variation['meta_value'] > $max){
                $max = $variation['meta_value'];
            }
            if($variation['meta_value'] < $min){
                $min = floatval($variation['meta_value']);
            }
        }

        $min = $min === 1234567890 ? 0 : $min;
        return array('min' => floatval($min*$factor), 'max' => floatval($max*$factor));
    }


    /**
     * @param $id
     * @param $price_list
     * @param $price_type
     * @return array|float[]|int[]
     * Get min a max of a variable product
     */
    function getMinMaxPriceVariation($id,$price_list,$price_type){
        $key = $price_type == '_regular_price' ? 'price' : 'sale_price';

        if($price_list == 1){
            $max_min = $this->getMinMaxPriceVariationDefault($id,$price_type);
        }else{
            $pricelist_controller = new PriceListController();
            $pl_object = $pricelist_controller->wrpl_get_price_list_by_id($price_list);
            $price_list = $pl_object['id_parent'] > 0 ? $pl_object['id_parent'] : $price_list;
            if($pl_object['id_parent'] == 0){
                $max_min = $this->getMinMaxPriceVariationNoDefault($id,$price_list,$key);
            } else {

                if ($pl_object['id_parent'] == 1) {
                    $max_min = $this->getMinMaxPriceVariationDefault($id,'_regular_price',$pl_object['factor']);
                } else {
                    // var_dump($this->getPriceNotDefault($id,$price_list,'price'));
                    $max_min = $this->getMinMaxPriceVariationNoDefault($id,$price_list,'price',$pl_object['factor']);
                }
            }
        }
        return $max_min;
    }

    /**
     * @param $post_id
     * Remove price transient
     */
    function wrpl_remove_product_price_caching($post_id){
        global $wpdb;

        $wpdb->query('DELETE FROM ' .$wpdb->prefix . 'options WHERE option_name LIKE "%_transient_timeout_wc_var_prices_' . $post_id . '%"');
        $wpdb->query('DELETE FROM ' .$wpdb->prefix . 'options WHERE option_name LIKE "%_transient_wc_var_prices_' . $post_id . '%"');

    }

    /**
     * @param $products
     * @param $price_list
     * @return array
     * Import products
     */
    function wrpl_import_products($products,$price_list){

        $results = array();

        foreach($products as $product){
	        $product_id = intval(trim($product[0]));
	        $product_obj = wc_get_product($product_id);
            if($product_id > 0 && $product_obj){ // if the sku exists
                $regular_price = floatval($product[1]);
                $sale_price = floatval($product[2]);

                if($price_list == 1){

                    if($sale_price < $regular_price && $regular_price > 0 ){
                        array_push($results, array('type' => 'success','msg' => __('The product with id: ','wr_price_list') . $product_id . __(' was updated','wr_price_list') ));
                        update_post_meta($product_id, '_regular_price', $regular_price);
                        update_post_meta($product_id, '_price', $sale_price);
                        update_post_meta($product_id, '_sale_price', $sale_price);
                    }else{
                        array_push($results, array('type' => 'error','msg' => __('The product with id: ','wr_price_list') . $product_id . __(' was no inserted, you must follow this rule: regular > sale and regular > 0','wr_price_list')));
                    }

                }else{
                    if($sale_price < $regular_price && $regular_price > 0 ){
                        $this->updateOrInsertPrice($product_id,$price_list,$regular_price,$sale_price);
                        array_push($results, array('type' => 'success','msg' => __('The product with id: ','wr_price_list') . $product_id . __(' was updated','wr_price_list') ));
                    }else {
                        array_push($results, array('type' => 'error','msg' => __('The product with id: ','wr_price_list') . $product_id . __(' was no inserted, you must follow this rule: regular > sale and regular > 0','wr_price_list')));
                    }
                }

            } else{
                array_push($results, array('type' => 'error','msg' => __('The product with id: ','wr_price_list') . $product_id . __(' was not found','wr_price_list') ));
            }
        }//endforeach
        return $results;
    }

	/**
	 * @param $products
	 * @param $price_list_id
	 * @param $parent_list_id
	 * Aux function to remove the price list parent approach (need to be removed in a couple plugin upgrade)
	 * @return array
	 */
	function wrpl_import_products_prices_to_list($products,$price_list, $parent_list, $factor = 1){
		global $wpdb;
		//Clean list in case
		//$wpdb->get_results("DELETE FROM $wpdb->prefix" . "wr_price_lists_price WHERE id_price_list ='" .  $price_list['id'] . "'");
		foreach($products as $product){
			$product_id = intval(trim($product['ID']));
			if($product_id > 0){
                $product = wc_get_product($product_id);
				if($parent_list['id'] == 1){
					$regular_price = $product->get_regular_price() * floatval($factor);
					$sale_price = $product->get_sale_price() * floatval($factor);
				}else{
					$regular_price = floatval($this->getPriceNotDefault($product_id, $parent_list['id'], 'price')) * floatval($factor);
					$sale_price = floatval($this->getPriceNotDefault($product_id, $parent_list['id'], 'sale_price')) * floatval($factor);
				}

				if(($sale_price < $regular_price && $regular_price > 0)){
					$this->updateOrInsertPrice($product_id,$price_list['id'],$regular_price,$sale_price);
				}

			}

		}

		$price_list_obj = new PriceList();
		$price_list_obj->update_price_list($price_list['description'], '0', '',$price_list['id']);
	}

    /**
     * @param $id
     * @return bool
     * if the category a parent category?
     */
    function hasChildren($id){
        $orderby = 'name';
        $order = 'asc';
        $hide_empty = false ;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
            'parent' => $id
        );
        if(count(wrpl_stdToArray(get_terms( 'product_cat', $cat_args )))>0){
            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return array
     * Get all the product that belong to child categories
     */
    function getProductChildCategories($id){
        $orderby = 'name';
        $order = 'asc';
        $hide_empty = false ;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
            'parent' => $id
        );

        $product_categories = get_terms( 'product_cat', $cat_args );
        $categories = array();
        foreach ($product_categories as $category){
            $category->text = $category->name;
            $category->plist = get_option('wrpl_cat_' . $category->term_id) ?: 1;
            if($this->hasChildren($category->term_id)){
                $category->nodes = $this->getProductChildCategories($category->term_id);
            }
            // $category['nodes'] = $this->getProductChildCategories($category['term_id']);
            array_push($categories,$category);
        }
        return $categories;
    }

    /**
     * Get the parent categories
     */
    function getProductParentCategories(){
        $orderby = 'name';
        $order = 'asc';
        $hide_empty = true;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
            'parent' => 0
        );

        $product_categories = get_terms( 'product_cat', $cat_args );
        $categories = array();
        foreach ($product_categories as $category){
            $category->text = $category->name;
            $category->plist = get_option('wrpl_cat_' . $category->term_id) ?: 1;
            if($this->hasChildren($category->term_id)){
                $category->nodes = $this->getProductChildCategories($category->term_id);
            }
            array_push($categories,$category);
        }

        if( !empty($product_categories) ){

            echo json_encode($categories);
        }
        else{
            echo json_encode(array('error' => 'There are not categories'));
        }
        wp_die();
    }

    /**
     * @return array
     * Get all product categories
     */
    function wrpl_get_all_product_cat(){

        $orderby = 'name';
        $order = 'asc';
        $hide_empty = false ;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
        );

        $product_categories = get_terms( 'product_cat', $cat_args );

        if( !empty($product_categories) ){

            return wrpl_stdToArray($product_categories);
        }else{
            return [];
        }
    }

}