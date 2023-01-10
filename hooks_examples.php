<?php
/*  Code Snippet to add basic Auth to server *\	
add_action( 'wp_loaded', 'set_auth_to_server', 9 );

function set_auth_to_server() {
  if ( isset( $_GET['Authorization'] ) && ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
    if ( preg_match('/Basic\s+(.*)$/i', $_GET['Authorization'], $auth ) ) {
		
	  list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = explode( ':', base64_decode( $auth[1] ) );
	}
  }
}

/ Function to upload via front end form /

function cs_file_upload() {
	global $wpdb;
	$fileErrors = array(
		0 => "There is no error, the file uploaded with success",
		1 => "The uploaded file exceeds the upload_max_files in server settings",
		2 => "The uploaded file exceeds the MAX_FILE_SIZE from html form",
		3 => "The uploaded file uploaded only partially",
		4 => "No file was uploaded",
		6 => "Missing a temporary folder",
		7 => "Failed to write file to disk",
		8 => "A PHP extension stoped file to upload" );
	$posted_data =  isset( $_POST ) ? $_POST : array();
	$file_data = isset( $_FILES ) ? $_FILES : array();
	$data = array_merge( $posted_data, $file_data );
	$response = array();
	$uploaded_file = wp_handle_upload( $data['cs_file_upload'], array( 'test_form' => false ) );
	
	if( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
		$response['response'] = "SUCCESS";
		$response['filename'] = basename( $uploaded_file['url'] );
		$response['url'] = str_replace("http:","https:",$uploaded_file['url']);
		$response['type'] = $uploaded_file['type'];
		$response['filepath'] = $uploaded_file['file'];
		
	} else {
		$response['response'] = "ERROR";
		$response['error'] = $uploaded_file['error'];
	}
	
	echo json_encode( $response );
	die();
}

add_action('wp_ajax_cs_file_upload', 'cs_file_upload');
add_action('wp_ajax_nopriv_cs_file_upload', 'cs_file_upload');



/*  Function for custom add to cart in woocommerce using ajax */

add_action('wp_ajax_cs_woocommerce_ajax_add_to_cart', 'cs_woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_cs_woocommerce_ajax_add_to_cart', 'cs_woocommerce_ajax_add_to_cart');

function cs_woocommerce_ajax_add_to_cart() {

    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = absint($_POST['variation_id']);
    // This is where you extra meta-data goes in
    $cart_item_data = $_POST['meta'];
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
    $product_status = get_post_status($product_id);

    // Remember to add $cart_item_data to WC->cart->add_to_cart
    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $cart_item_data) && 'publish' === $product_status) {

        do_action('woocommerce_ajax_added_to_cart', $product_id);

        if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
            wc_add_to_cart_message(array($product_id => $quantity), true);
        }

        WC_AJAX :: get_refreshed_fragments();
        $data = array(
            'error' => false,
            'product_url' =>  get_permalink($product_id));
           
        echo wp_send_json($data);
    } else {

        $data = array(
            'error' => true,
            'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id));

        echo wp_send_json($data);
    }

    wp_die();
}

/* Function to create custom meta box and save the field in meta table */


function add_custom_meta_box()
{
    add_meta_box("coming-soon-product", "Coming Soon Product", "custom_meta_box_markup", "product", "side", "high", null);
}

add_action("add_meta_boxes", "add_custom_meta_box");

function save_custom_meta_box($post_id, $post, $update)
{
  
    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "product";
    if($slug != $post->post_type)
        return $post_id;

    $meta_box_text_value = "";
    $meta_box_dropdown_value = "";
    $meta_box_checkbox_value = "";

    if(isset($_POST["mv_coming_soon"]))
    {
		
		update_post_meta($post_id, "mv_coming_soon", $_POST["mv_coming_soon"]);
	}   
	else{
		update_post_meta($post_id, "mv_coming_soon", "");
	}

}

add_action("save_post", "save_custom_meta_box", 10, 3);
