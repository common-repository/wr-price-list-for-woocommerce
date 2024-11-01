<?php

/*
*
* @package Yariko
*
*/

namespace Wrpl\Inc\Functions;
/*use Wrpl\Inc\Controller\PriceListController;
use Wrpl\Inc\Controller\ProductController;*/

class Settings{

    /*public $product_controller;
    public $price_list_controller;*/

    /*function __construct()
    {
        $this->product_controller =  new ProductController();
        $this->price_list_controller =  new PriceListController();
    }*/

    public function register(){

        /*add_action('woocommerce_product_options_pricing', array($this, 'simple_product_admin_roles_pricing'));
        add_action('woocommerce_variation_options_pricing', array($this, 'variation_product_admin_roles_pricing'));*/

    }

    function simple_product_admin_roles_pricing(){
        echo '<h2><strong>Price Lists</strong></h2><p class="form-field wrpl_simple_pricing">
				<label for="_sale_price_dates_from">' . esc_html__( 'Label here', 'wr_price_list' ) . '</label>
				<input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="" />
			
			</p>';
    }

    function variation_product_admin_roles_pricing(){
        echo '<div class="form-field wrpl_variation_pricing">
                <h2 class="form-row form-row-full"><strong>Price Lists</strong></h2>
                <hr>
				<p class="form-row form-row-first">
				    <label for="_sale_price_dates_from">' . esc_html__( 'Label here', 'wr_price_list' ) . '</label>
				    <input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="" />  
                </p>
                <p class="form-row form-row-last">
				    <label for="_sale_price_dates_from">' . esc_html__( 'Label here', 'wr_price_list' ) . '</label>
				    <input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="" />  
                </p>
			</div>';
    }


}

