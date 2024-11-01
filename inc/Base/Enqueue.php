<?php

/*
*
* @package Yariko
*
*/

namespace Wrpl\Inc\Base;

class Enqueue{

    public function register(){

        add_action('plugins_loaded', array($this,'wrpl_translate_plugin'));

    }

    function wrpl_translate_plugin() {
        load_plugin_textdomain(     'wr_price_list', false, WRPL_PLUGIN_DIR_BASENAME .'/languages/' );
    }

}