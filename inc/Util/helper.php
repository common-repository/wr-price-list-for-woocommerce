<?php

function wrpl_stdToArray($stds){
        $php_array = array();
        for($i = 0; $i<count($stds);$i++){
            $item = (array)$stds[$i];
            array_push($php_array, $item);
        }
        return $php_array;
}

function wrpl_roles() {

    global $wp_roles;

    $roles = $wp_roles->roles;
    return $roles;
}

function wrpl_valid_name($name){
    $valid_id = trim($name);
    $valid_id = str_replace(" ","_",$valid_id);
    $valid_id = str_replace(array('/','&','*','(',')','%','$', '#', '@'),"",$valid_id);
    $valid_id = strtolower($valid_id);
    return $valid_id;
}

function wrpl_convert_to_separate_value($array,$key ){
    $array = wrpl_stdToArray($array);
    $result =  '';
    for ($i = 0; $i < count($array); $i++){
        $result .= $array[$i][$key] . ($i < count($array) - 1 ? ', ' : '');
    }

    return $result;
}

function wrpl_get_l10n(){
	return  array(
			'general_server_error' => __('Server Error, please try in a couple seconds.','wr_price_list'),
			'showing' => __('Showing ','wr_price_list'),
			'license_support_menu' => __('License / Support ','wr_price_list'),
			'of' => __('of','wr_price_list'),
			'search_placeholder' => __('Search','wr_price_list'),
			'products' => __('Products','wr_price_list'),
			'all_categories' => __('All Categories','wr_price_list'),
			'price_not_updated' => __('The price was not updated','wr_price_list'),
			'price_list_header' => __('Price Lists','wr_price_list'),
			'license_error' => __('There was an error trying to get the license','wr_price_list'),
			'license_invalid_license' => __('Please, enter a valid license!','wr_price_list'),
			'csv_format_error' => __('CSV file format only','wr_price_list'),
			'csv_size_error' => __('Your file is too big! Please select an image under 1MB','wr_price_list'),
			'csv_empty_file_error' => __('Upload a file!','wr_price_list'),
			'upload_finished' => __('Upload Finished!','wr_price_list'),
			'price_list_required_name' => __('Enter a price list name','wr_price_list'),
			'select_price_list' => __('Select a price list','wr_price_list'),
			'status_saved' => __('Saved!','wr_price_list'),
			'role_name_required' => __('Enter role name','wr_price_list'),
			'add_role' => __('Add role','wr_price_list'),
			'role_removed' => __('The role was successfully removed','wr_price_list'),
			'role_valid_name' => __('You need to define a valid name','wr_price_list'),
			'product_table_columns' => array(
				'id' => __('ID','wr_price_list'),
				'name' => __('Name','wr_price_list'),
				'sku' => __('SKU','wr_price_list'),
				'category' => __('Category','wr_price_list'),
				'type' => __('Type','wr_price_list'),
				'price' => __('Price','wr_price_list'),
				'regular' => __('Regular','wr_price_list'),
				'sale' => __('Sale','wr_price_list'),
				'variation' => __('Variation','wr_price_list'),
				'product' => __('Products','wr_price_list'),
			),
			'pl_table_columns' => array(
				'name' => __('Name','wr_price_list'),
				'actions' => __('Actions','wr_price_list'),
			),
			'update_pl' => __('Update List', 'wr_price_list'),
			'add_pl' => __('Add List', 'wr_price_list'),
			'pl_pre_delete_notice' => __('Are you sure to delete this list?', 'wr_price_list'),
			'role_pre_delete_notice' => __('Are you sure to delete this role?', 'wr_price_list'),
			'no_thanks' => __('No, Thanks', 'wr_price_list'),
			'pl_percent_tooltip' => __('Define the percent that the list will have with reference to the parent list', 'wr_price_list'),
			'pl_copy_from_tooltip' => __('You can select a price list to copy its prices based on a percentage you define', 'wr_price_list'),
			'pl_percent_label' => __('Copy prices from:', 'wr_price_list'),
			'pl_name_modal_placeholder' => __('Enter price list name', 'wr_price_list'),
			'save' => __('Save', 'wr_price_list'),
			'percent' => __('Percent', 'wr_price_list'),
			'cancel' => __('Cancel', 'wr_price_list'),
			'confirm' => __('Confirm', 'wr_price_list'),
			'roles' => __('Roles', 'wr_price_list'),
			'role' => __('Role', 'wr_price_list'),
			'role_footer_note' => __('<strong>Note:</strong> Roles without a list assigned will see the price under the default list. See settings section to define a default price list.', 'wr_price_list'),
			'role_header_p' => __('Assign a price list to each role, so that all users who correspond to the role will see only the price that corresponds to the assigned price list.', 'wr_price_list'),
			'role_footer_p' => __('**Only the roles created with the WR Price manager plugin can be removed on this screen. This is because others plugin can need those roles to work correctly.', 'wr_price_list'),
			'import_new_pl_heading' => __('Type a name and upload a csv with the prices to create a list from scratch.', 'wr_price_list'),
			'import_select_pl_heading' => __('Select a price list to update/insert the csv prices.', 'wr_price_list'),
			'upload' => __('Upload', 'wr_price_list'),
			'import_template_1' => __('You can use this template', 'wr_price_list'),
			'import_template_2' => __('as reference.', 'wr_price_list'),
			'import_add_pl' => __('Add a new price list', 'wr_price_list'),
			'import_update_pl' => __('Update a price list', 'wr_price_list'),
			'import_header' => __('Import / Export', 'wr_price_list'),
			'import_results' => __('Import Results', 'wr_price_list'),
			'import_menu' => __('Import/Export', 'wr_price_list'),
			'import' => __('Import', 'wr_price_list'),
			'export' => __('Export', 'wr_price_list'),
			'grouped' => __('Grouped', 'wr_price_list'),
			'external' => __('External', 'wr_price_list'),
			'simple' => __('Simple', 'wr_price_list'),
			'settings_header' => __('Settings', 'wr_price_list'),
			'settings_header_p' => __('Choose a default price list for unregistered/guest (users without role)', 'wr_price_list'),
			'settings_price_format_heading' => __('Choose the price format (Simple Product)', 'wr_price_list'),
			'settings_price_format_1' => __('Regular / Sale', 'wr_price_list'),
			'settings_price_format_2' => __('Sale / Regular', 'wr_price_list'),
			'settings_price_format_3' => __('Just Price', 'wr_price_list'),
			'settings_message_unregistered_heading' => __('Choose a personalized message to hide prices from unregistered/guest users', 'wr_price_list'),
			'settings_hide_label' => __('Hide prices to guest', 'wr_price_list'),
			'export_header' => __('Select a list to export its price.', 'wr_price_list'),
		);
}


