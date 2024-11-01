<?php

/*
*
* @package Yariko
*
*/

namespace Wrpl\Inc\Pages;

class Pages{

    public function register(){

        add_action('admin_menu', function(){
            add_menu_page('WR Price Manager', 'WR Price Manager', 'manage_options', 'wrpl-products-menu', array($this,'newProducts') , WRPL_PLUGIN_URL. 'assets/img/price-tag.png',110);
        });

        add_action('admin_menu',function(){
            $new_page =  add_submenu_page( 'wrpl-products-menu', __('Products New','wr_price_list'), __('Products New','wr_price_list'),'manage_options', 'wrpl-products-menu', array($this,'newProducts'));

	        add_action( 'load-' . $new_page, function(){
		        add_action( 'admin_enqueue_scripts',function (){

			        wp_enqueue_style('wrech-bootstrap-css', WRPL_PLUGIN_URL . '/assets/css/admin/bootstrap.min.css');

			        wp_enqueue_style('wrech-app-css', WRPL_PLUGIN_URL . '/dist/app.css'  );
			        wp_enqueue_style('wrech-vendors-css', WRPL_PLUGIN_URL . '/dist/vendors.css'  );
			        wp_enqueue_script( 'wrech-bootstrap-js', WRPL_PLUGIN_URL . '/assets/js/admin/bootstrap.bundle.min.js');
			        wp_enqueue_style('main_admin_styles',  WRPL_PLUGIN_URL . '/assets/css/admin/main.css' );

			        wp_enqueue_script( 'wrech-runtime-js', WRPL_PLUGIN_URL . '/dist/runtime.wec.bundle.js', '1.00', true);
			        wp_enqueue_script( 'wrech-vendors-js', WRPL_PLUGIN_URL . '/dist/vendors.wec.bundle.js', array('wrech-runtime-js'),'1.00', true);

			        wp_enqueue_script( 'wrech-app-js', WRPL_PLUGIN_URL . '/dist/app.wec.bundle.js', array('wrech-runtime-js', 'wrech-vendors-js'),'1.00', true);

			        $args = array(
				        'ajax_url'=> admin_url('admin-ajax.php'),
				        'plugin_url' => WRPL_PLUGIN_URL,
				        'plugin_path' => WRPL_PLUGIN_PATH,
                        'locale' => wrpl_get_l10n()
			        );
			        wp_localize_script( 'wrech-app-js', 'wrpl_params', $args );

		        });
	        });
        });

    }

    function newProducts(){
	    ?>
	    <style>
		    #wpcontent {
			    padding-left: 0 !important;
		    }
		    #wrpl-app{
		    }
	    </style>
	    <div id="wrpl-app"></div>
	    <?php
    }


}
?>