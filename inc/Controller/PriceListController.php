<?php

namespace Wrpl\Inc\Controller;

use Wrpl\Inc\Base\WRPL_Signature;
use Wrpl\Inc\Controller\ProductController;
use Wrpl\Inc\Functions\PriceList;


class PriceListController
{
    public function register(){


        // Get all the price list
	    add_action( 'wp_ajax_wrpl_get_price_lists', array($this,'get_all_price_lists' ));
	    //Delete a price list by id
	    add_action( 'wp_ajax_wrpl_delete_price_list', array($this,'remove_price_list' ));
	    //Add a price list
	    add_action( 'wp_ajax_wrpl_add_price_list', array($this,'add_price_list' ));

	    //Get all the site roles
	    add_action( 'wp_ajax_wrpl_get_all_roles', array($this,'getAllRoles' ));
	    //Save relation between roles and price lists
	    add_action( 'wp_ajax_wrpl_save_relation', array($this,'wrpl_save_price_list_role_relation' ));
	    //Remove role by role id
	    add_action( 'wp_ajax_wrpl_remove_role', array($this,'remove_role' ));
	    //Add Role
	    add_action( 'wp_ajax_wrpl_add_role', array($this,'add_role' ));

	    //Import CSV
	    add_action( 'wp_ajax_wrpl_import_csv', array($this,'import_csv' ));
	    //Export
	    add_action( 'wp_ajax_wrpl_export_csv', array($this,'export_csv' ));

	    //todo => this has nothing to o with PriceList, maybe we need to create a Settings class
	    add_action( 'wp_ajax_wrpl_save_settings', array($this,'save_settings' ));
	    add_action( 'wp_ajax_wrpl_get_settings', array($this,'get_settings' ));
	    add_action( 'wp_ajax_wrpl_submit_license', array($this,'submit_license' ));
	    add_action( 'wp_ajax_wrpl_get_license', array($this,'get_license' ));
    }

    function save_settings(){
	    //define default price list
	    update_option('wrpl-default_list',sanitize_text_field(intval($_POST['price_list_id'])));
	    update_option('wrpl-format-price-method',sanitize_text_field(intval($_POST['price_format'])));

	    //hide price
	    if($_POST['hide_prices'] == '1'){
		    //allow any input from the user(user is the admin, so he can decide the input here).
		    //this set a default message to unregistered users, so here could have html tags.
		    update_option('wrpl-custom_msg_no_login_user', wp_filter_post_kses($_POST['hide_message']));
		    update_option('wrpl-hide_price', 1);
	    }else{
		    update_option('wrpl-hide_price', 0);
	    }

	    echo json_encode(array('success' => true,'msg'=> __('Settings saved!','wr_price_list')));
	    wp_die();

    }

    function get_settings(){

    	$settings = array(
    		'hide_message' => get_option('wrpl-custom_msg_no_login_user') ? get_option('wrpl-custom_msg_no_login_user') : '',
    		'hide_prices' => get_option('wrpl-hide_price') == '1',
    		'price_list_id' => get_option('wrpl-default_list') ? get_option('wrpl-default_list') : '1',
    		'price_format' => get_option('wrpl-format-price-method') ? get_option('wrpl-format-price-method') : '1'
	    );

	    echo json_encode(array('success' => true,'settings'=> $settings));
	    wp_die();
    }

    function import_csv(){

	    $file_name = sanitize_file_name($_FILES['file']['name']);
	    $file_tmp = sanitize_text_field($_FILES['file']['tmp_name']);
	    $price_list_id = intval(sanitize_text_field($_POST['price_list_id']));
	    $import_mode = sanitize_text_field($_POST['import_mode']);
	    $price_list_name = sanitize_text_field($_POST['name']);

	    move_uploaded_file($file_tmp, WRPL_PLUGIN_PATH . 'uploads/' . $file_name );

	    $signature = new WRPL_Signature();
	    $product_controller =  new ProductController();

	    $products_imported =  array();
		$row = 0;
		$file_path = WRPL_PLUGIN_PATH . 'uploads/' . $file_name;
	    if (($handle = fopen($file_path, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
		    	//Avoid the headers
		    	if($row === 0){
				    $row++;
		    		continue;
			    }

			    if('' != trim($data[0]) || '' != trim($data[1])){
				    if( trim($data[2]) == '' ){ //si sale price esta vacio
					    $data[2] = 0;
				    }
				    array_push($products_imported,$data);
			    }
		    }
		    fclose($handle);
	    }else{
		    echo json_encode(array('success' => false,'msg'=> __('There was an error with the file format','wr_price_list')));
		    wp_die();
	    }

	    unlink( $file_path );

	    if($import_mode == 1){ //if the user choose create a new list
		    if($this->wrpl_exist_price_list_name($price_list_name)){

			    echo json_encode(array('success' => false,'msg'=> 'There is a price list with this name'));
			    wp_die();

		    }else{
			    $price_list = $this->wrpl_add_price_list($price_list_name,0,'',$signature->is_valid());
			    $imported_msgs = $product_controller->wrpl_import_products($products_imported,$price_list['id']);

			    echo json_encode(array('success' => true,'msgs'=> $imported_msgs));
			    wp_die();
		    }
	    }else{
		    $imported_msgs = $product_controller->wrpl_import_products($products_imported,$price_list_id);
		    echo json_encode(array('success' => true,'msgs'=> $imported_msgs));
		    wp_die();

	    }

	    echo json_encode(array('success' => false,'msg'=> 'Opps, something happens, check if your site has upload permissions'));
	    wp_die();
    }

    function export_csv(){
    	$price_list_controller = new PriceListController();
    	$price_list_id = $_POST['price_list_id'];
    	$price_list = $price_list_controller->wrpl_get_price_list_by_id($price_list_id);

    	$name = !empty(wrpl_valid_name($price_list['description'])) ? wrpl_valid_name($price_list['description']) : 'default_woocommerce';

    	//Remove previous exports
	    $files = glob(WRPL_PLUGIN_PATH . '/uploads/exports/*'); // get all file names
	    foreach($files as $file){ // iterate files
		    if(is_file($file)) {
			    unlink($file); // delete file
		    }
	    }

    	$product_controller = new ProductController();

    	$products = $product_controller->getProductByCategory(0, '')['data'];
		$data = [['product_id', 'regular_price', 'sale_price']];
	    foreach ($products as $product){
			$data[] = [
				$product['ID'],
				round(floatval($product_controller->getRegularPrice($product['ID'],$price_list_id)),2),
				round(floatval($product_controller->getSalesPrice($product['ID'],$price_list_id)),2)
				];
	    }

	    $path_name =  $name . '-' .  time() .'.csv';
	    $csv_file_name = WRPL_PLUGIN_PATH . '/uploads/exports/'. $path_name ; //You can give your path to save file

	    $f = fopen($csv_file_name, 'w');

	    foreach ($data as $row) {
		    fputcsv($f, $row);
	    }

	    fclose($f);

	    echo json_encode(array('success' => true,'path'=> WRPL_PLUGIN_URL . '/uploads/exports/' . $path_name));
	    wp_die();

    }

    function getAllRoles(){
    	$roles = wrpl_roles();

    	$formatted_roles = array();
    	foreach ($roles as $role_id => $data){
    		array_push($formatted_roles, array(
    			'name' => $data['name'],
			    'id' => $role_id,
			    'price_list' => get_option('wrpl-'. $role_id) ? get_option('wrpl-'. $role_id) : "1",
			    'is_native' => get_option('wrpl_role-'. $role_id) == $data['name'] ? 1 : 0
		    ));
	    }
	    echo json_encode(array('success' => true,'roles'=> $formatted_roles));
	    wp_die();
    }

	/**
	 * All the price list AJAX
	 */
	function get_all_price_lists(){
		$plists = $this->wrpl_get_price_lists();
		$lists = array();
		foreach ($plists as $list){
			$list['percent'] = floatval($list['factor']) > 0 ? floatval($list['factor']) * 100 : 0;
			array_push($lists, $list);
		}

		echo json_encode(array('success' => true,'price_lists'=> $lists));
		wp_die();
	}

    /**
     * @param bool $all_pl
     * @return mixed
     * Get all price in associative array
     */
    function wrpl_get_price_lists($all_pl = true){
        global $wpdb;
        if($all_pl){
            $plists = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "wr_price_lists ORDER BY id",ARRAY_A );
        }else{
            $plists = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "wr_price_lists WHERE id_parent =0 ORDER BY id",ARRAY_A );
        }

        return $plists;
    }

	/**
	 * Save the relation between Roles and Price List (AJAX)
	 */
	function wrpl_save_price_list_role_relation(){
		$roles = json_decode(stripslashes($_POST['roles']));
		foreach ($roles as $role){
			update_option('wrpl-' . $role->id, $role->price_list);
		}
		echo json_encode(array('success' => 'true'));
		wp_die();
	}

    /**
     * @return int
     * @notes relation between price list and role are in options table of wp (wrpl-name-of-role,price_list_value)
     */
    function wrpl_get_user_price_list()
    {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $roles = ( array )$user->roles;

            return ( $this->wrpl_exist_price_list_id(get_option('wrpl-'.$roles[0]))) ? get_option('wrpl-'.$roles[0]) : 1; //if a list is not found the Default one os returned
        }else{
            return get_option('wrpl-default_list');
        }
    }

    /**
     * @param $name
     * @param int $id
     * @return bool
     * Check if exist a price list with name = $name
     */
    function wrpl_exist_price_list_name($name,$id=0){
        global $wpdb;

        $name = strtolower($name);
        $plists = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "wr_price_lists WHERE LOWER(description) = '$name' AND id!='$id'");
        if(count($plists)>0){
            return true;
        }else{
            return false;

        }
    }

    /**
     * @param $id
     * @return bool
     * Check if exist the price list
     */
    function wrpl_exist_price_list_id($id){
        global $wpdb;

        $plists = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "wr_price_lists WHERE id='$id'");
        if(count($plists)>0){
            return true;
        }else{
            return false;

        }
    }

    /**
     * @param $name
     * @param int $plist
     * @param string $factor
     * @param $blofe
     * @return array
     * Add a price list
     * it is used to import, there was a deprecation 1.0.4 mark so I will need to check wht was that
     */
    function wrpl_add_price_list($name,$plist = 0, $factor = '',$blofe){
        global  $wpdb;

        $pls_blofe = count($this->wrpl_get_price_lists());
        $name = preg_replace('/\s+/', ' ',$name); //removing extra spacing
        if(!$this->wrpl_exist_price_list_name($name)){
            if(!$blofe && $pls_blofe > 2.365 ){
                return array('status' => 'error','type' => 1);
            }else{
                $wpdb->query("INSERT INTO $wpdb->prefix" . "wr_price_lists (description,id_parent,factor) VALUES ('$name','$plist','$factor')");
                return array('status' => 'success','type' => 2,'id' => $wpdb->insert_id);
            }

        }else{
            return array('status' => 'error','type' => 2);
        }
    }

	/**
	 * Add a price list AJAX
	 */
    function add_price_list(){
	    global  $wpdb;
		$name = $_POST['name'];
		$parent = $_POST['id_parent'];
		$factor = $_POST['percent'] && $parent !=0 ? floatval(floatval($_POST['percent'])/100) : '' ;
		$edit = $_POST['edit'];
		$id = $_POST['id'];

	    $product_controller = new ProductController();

	    $products = $product_controller->getAllProducts();
	    $parent_list = $this->wrpl_get_price_list_by_id($parent);

	    $name = preg_replace('/\s+/', ' ',$name); //removing extra spacing
	    if(!$this->wrpl_exist_price_list_name($name) && $edit === 'false') {
		    $wpdb->query("INSERT INTO $wpdb->prefix" . "wr_price_lists (description,id_parent,factor) VALUES ('$name','$parent','$factor')");
		    $price_list_id = $wpdb->insert_id;
		    if($price_list_id>0){

			    $pl = $this->wrpl_get_price_list_by_id($price_list_id);

			    $product_controller->wrpl_import_products_prices_to_list($products,$pl,$parent_list, $factor );
			    echo json_encode(array('success' => true, 'msg' => 'The price list was inserted successfully'));
			    wp_die();
		    }

	    }else{

		    if($edit !== 'false'){

			    $pl = $this->wrpl_get_price_list_by_id($id);

			    $product_controller->wrpl_import_products_prices_to_list($products,$pl,$parent_list, $factor );

			    echo json_encode(array('success' => true, 'msg' => 'The price list was updated successfully'));
			    wp_die();
		    }else{

			    echo json_encode(array('success' => false, 'msg' => 'You already have a price list named ' .  $name . ', please choose other name'));
			    wp_die();

		    }
	    }

	    echo json_encode(array('success' => false, 'msg' => 'Something happened, try the operation in a couple minutes'));
	    wp_die();

    }

    /***
     * @param $id
     * @return int
     *
     */
    function wrpl_get_price_list_by_id($id){
        global $wpdb;
        $plists = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "wr_price_lists WHERE id = '$id'", ARRAY_A);
        $plist = -1;
        if(count($plists)>0){
            $plist = $plists[0];
        }
        return $plist;
    }

	/**
	 * Remove a price list by id AJAX
	 */
    function remove_price_list(){

	    global $wpdb;

    	$id = $_POST['id'];

	    $roles = wrpl_roles();
        foreach ($roles as $role_id => $data){
            if(get_option('wrpl-'. $role_id) == $id){
                delete_option('wrpl-'. $role_id);

            }
        }

	    $default_list = get_option('wrpl-default_list', '');
	    if($id == $default_list){
		    update_option('wrpl-default_list', '1');
	    }

	    $wpdb->get_results("DELETE FROM $wpdb->prefix" . "wr_price_lists WHERE id = '$id'");
	    $wpdb->get_results("UPDATE $wpdb->prefix" . "wr_price_lists SET factor= '',id_parent = 0 WHERE id_parent='$id'");

	    $wpdb->get_results("DELETE FROM $wpdb->prefix" . "wr_price_lists_price WHERE id_price_list = '$id'");

	    echo json_encode(array('success' => true, 'msg' => __('The role was added, assign a price list to it', 'wr_price_list')));
	    wp_die();
    }

    function wrpl_edit_price_list($name,$id,$factor){
        global $wpdb;
        if(!$this->wrpl_exist_price_list_name($name,$id)){
            $wpdb->query("UPDATE $wpdb->prefix" . "wr_price_lists SET description='$name',factor='$factor' WHERE id='$id'");
            return true;
        }
        return false;
    }

    /**
     * @param $name
     * @return bool
     * Check if the role exists
     */
    function wr_exist_role($name){
        global $wp_roles;
        $roles = $wp_roles->roles;
        if(in_array($name,$roles)){
            return true;
        }else{
            return false;
        }
    }

	/**
	 * Add Role AJAX
	 *
	 */
    function add_role(){
    	$name = $_POST['name'];
	    if(!$this->wr_exist_role($name)){
		    $result = add_role(
			    wrpl_valid_name($name),
			    __( $name ),
			    array(
				    'read' => true,
			    )
		    );
		    if ( null !== $result ) {
			    update_option('wrpl_role-'.wrpl_valid_name($name),$name); //specifying that this role was created by WRPL
			    echo json_encode(array('success' => true, 'msg' => 'The role was added, assign a price list to it'));
			    wp_die();
		    }
		    else {
			    echo json_encode(array('success' => false, 'msg' => 'There is a role with that specific name.'));
			    wp_die();
		    }
	    }else{
		    echo json_encode(array('success' => false, 'msg' => 'There is a role with that specific name.'));
		    wp_die();
	    }
    }

	/**
	 * Remove specific role AJAX
	 */
	function remove_role(){
		$role_id = $_POST['role_id'];
		wp_roles()->remove_role( $role_id); //Removing  wp role
		delete_option('wrpl_role-' . $role_id); //Removing wrpl role option
		delete_option('wrpl-' . $role_id); //removing relation role-price list/
		echo json_encode(array('success' => true));
		wp_die();
	}

    function submit_license(){
	    $signature = new WRPL_Signature();

	    $license = sanitize_text_field($_POST['license']);
	    $type = sanitize_text_field($_POST['type']);

	    $response = null;
	    if($type == 'remove'){
		    $response = $signature->remove_license($license);
	    }else{
		    $response = $signature->save_license($license);
	    }

	    echo json_encode(array('success' => true, 'response' => $response));
	    wp_die();

    }

	function get_license(){
		$signature = new WRPL_Signature();

		$license = $signature->get_license();
		$valid = $signature->is_valid();

		echo json_encode(array('success' => true, 'license' => $license, 'valid' => $valid ));
		wp_die();
	}

}