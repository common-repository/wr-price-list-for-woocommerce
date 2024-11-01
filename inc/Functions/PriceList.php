<?php

/*
*
* @package Yariko
* Class that manage all the price output that came from the price list and modify the woo core pricing logic.
*/

namespace Wrpl\Inc\Functions;
use Wrpl\Inc\Controller\PriceListController;
use Wrpl\Inc\Controller\ProductController;

class PriceList{

    public $product_controller;
    public $price_list_controller;

    function __construct()
    {
        $this->product_controller =  new ProductController();
        $this->price_list_controller =  new PriceListController();
    }

    public function register(){

        if(!is_admin()){
	        // Simple, grouped and external products
	        add_filter('woocommerce_product_get_regular_price', array( $this, 'custom_regular_price' ), PHP_INT_MIN , 2 );
	        add_filter('woocommerce_product_get_price', array( $this, 'custom_price' ), PHP_INT_MIN, 2 ); // possible deprecation
            add_filter( 'woocommerce_product_get_sale_price', array( $this, 'custom_sale_price' ), PHP_INT_MIN, 2 );
            //Replace the woo price html by wrpl price / hide price and show message for unregistered users if hide_price_is checked on settings
            add_filter( 'woocommerce_get_price_html', array($this,'wrpl_woocommerce_price_html'), PHP_INT_MAX, 2 );

	        // Variable
            add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'custom_sale_price' ), PHP_INT_MIN, 2 );
	        add_filter('woocommerce_product_variation_get_regular_price', array( $this, 'custom_regular_price' ), PHP_INT_MIN, 2 );
	        add_filter('woocommerce_product_variation_get_price', array( $this, 'custom_price' ), PHP_INT_MIN, 2 );

	        /*add_filter( 'woocommerce_variable_sale_price_html', array($this,'wrpl_variation_price_range'), 10, 2 );
	        add_filter( 'woocommerce_variable_price_html', array($this,'wrpl_variation_price_range'), 10, 2 );*/

        }

        //hide price not login user
        add_action( 'init', array($this,'hide_price_not_login_user') );

    }

    function hide_price_not_login_user() {
        if ( ! is_user_logged_in() && get_option('wrpl-hide_price')  == 1  ) {
            add_action( 'woocommerce_single_product_summary', array($this,'wrpl_print_login_to_see'), 31 );
            add_action( 'woocommerce_after_shop_loop_item', array($this,'wrpl_print_login_to_see'), 11 );

	        //Making the product no purchasable and avoid the add cart url issue
	        add_filter( 'woocommerce_is_purchasable', '__return_false');

        }
    }

    function wrpl_print_login_to_see() {
        echo '<div>' . stripslashes( get_option('wrpl-custom_msg_no_login_user')) . '</div>';
    }

    function custom_price($price,$product){
        $price_list = $this->price_list_controller->wrpl_get_user_price_list();
        $rp = $this->product_controller->getRegularPrice($product->get_id(),$price_list);
        $sp = $this->product_controller->getSalesPrice($product->get_id(),$price_list);
        return empty($sp) ? $rp : $sp;
    }

    function custom_regular_price($price,$product){
        $price_list = $this->price_list_controller->wrpl_get_user_price_list();
        $p = $this->product_controller->getRegularPrice($product->get_id(),$price_list);
        return $p;
    }

    function custom_sale_price($price,$product){
        $price_list = $this->price_list_controller->wrpl_get_user_price_list();
        $sp = $this->product_controller->getSalesPrice($product->get_id(),$price_list);
        $rp = $this->product_controller->getRegularPrice($product->get_id(),$price_list);
        if(!empty($sp)){
            return $sp;

        }
        return $rp;
    }

    /**
     * Modify the price based on the plugin logic
     * @param $price
     * @param $product
     * @return string|void
     *
     */
    function wrpl_woocommerce_price_html( $price, $product ){
        if ( ! is_user_logged_in() && get_option('wrpl-hide_price')  == 1 ) {
            return '';
        }
       /* else{

            $price_list = $this->price_list_controller->wrpl_get_user_price_list();
            $min_max = $this->product_controller->getMinMaxPriceVariation($product->get_id(),$price_list,'_regular_price');
            $min_max_sale = $this->product_controller->getMinMaxPriceVariation($product->get_id(),$price_list,'_sale_price');

            if(!$product->has_child()){

                $sale_price = $product->get_sale_price();
                $regular_price = $product->get_regular_price();


                if(wc_price($sale_price) != wc_price($regular_price)){
                    switch(get_option('wrpl-format-price-method')){
                        case 1:
                            $html_price = $price;
                            break;
                        case 2:

                            $html_price =  wc_price($sale_price) . ' <del>' . wc_price($regular_price) . '</del>' ;
                            break;
                        default:

                            $html_price =  $price . "<style>.price del ins{ display: none}</style>";
                            break;

                    }

                }else{
                    $html_price =  wc_price($regular_price);
                }
                return $html_price;

            }else{

               if($product->get_type() == 'variable'){
	               if(  $min_max_sale['max'] > 0 && $min_max_sale['min']<$min_max['min']){
		               return __('Starting at ','wr_price_list') . wc_price($min_max_sale['min']);
	               }else{

		               if(  $min_max_sale['max'] > 0 && $min_max_sale['min']<$min_max['min']){
			               return __('Starting at ','wr_price_list') . wc_price($min_max_sale['min']);
		               }else{
			               return __('Starting at ','wr_price_list') . wc_price($min_max['min']);
		               }

	               }
               }

            }
        }*/
        return $price;
    }

    function update_price_list($name, $parent, $factor, $id){
	    global  $wpdb;
	    $wpdb->query("UPDATE $wpdb->prefix" . "wr_price_lists SET description = '$name',id_parent = '$parent',factor = '$factor' WHERE id = '$id'");
    }

   /* function insert_price_list($name, $parent, $factor){
	    global  $wpdb;
	    $wpdb->query("INSERT INTO $wpdb->prefix" . "wr_price_lists (description,id_parent,factor) VALUES ('$name','$parent','$factor')");
    }*/

}

