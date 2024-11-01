<?php 
/*
*
*
* @package yariko
*/


if( ! defined('WP_UNINSTALL_PLUGIN') ){
	die;
}

/*global $wpdb;
$table_name1 = $wpdb->prefix . 'wr_price_lists';
$table_name2 = $wpdb->prefix . 'wr_price_lists_price';
$table_name3 = $wpdb->prefix . 'wr_rules';
$wpdb->query('DELETE FROM ' .$wpdb->prefix . 'options WHERE option_name LIKE "wrpl%" AND  option_name NOT LIKE "wrpl_role%"');
$wpdb->query( "DROP TABLE IF EXISTS $table_name1" );
$wpdb->query( "DROP TABLE IF EXISTS $table_name2" );
$wpdb->query( "DROP TABLE IF EXISTS $table_name3" );*/


if(!empty(get_option('wrpl_plugin_license', ''))){
	update_option('wrpl_plugin_license', '', 'yes');
	$data = array(
		'action' => 'deactivated',
		'site' => get_site_url(),
		'email' => get_option( 'admin_email' ),
		'ip' => '',
		'purchase_code' => get_option('wrpl_plugin_license', '')
	);

	$response = wp_remote_post(
		'https://webreadynow.com/wp-json/wreintegration/v1/plugin_activate',
		array(
			'body' => array(
				'data' => $data
			)
		)
	);
}