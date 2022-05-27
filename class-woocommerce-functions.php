<?php

class class_wc_custom_functionality{

	public function __construct(){
		$this->init();
	}
	public function init(){
		add_filter('woocommerce_available_payment_gateways' , array($this, 'hide_payment_gateways'), 10, 3 );
		add_filter('woocommerce_account_menu_items', array($this, 'my_account_menu_order_custom'), 10, 1 );
		add_filter('wc_add_to_cart_message', array($this, 'custom_add_to_cart_message') );
		add_filter('woocommerce_add_to_cart_validation', array($this, 'add_the_date_validation'), 10, 5 );
		add_filter('woocommerce_cart_item_name', array($this, 'cart_name_with_productid'), 10, 3 );
		add_filter('woocommerce_email_from_name', array($this, 'change_wc_sender_name'), 10, 2 );
		add_filter('woocommerce_enqueue_styles', '__return_empty_array' ); //Disable all WC stylesheets
		add_filter('woocommerce_enqueue_styles', array($this, 'disable_wc_styles') );
		add_filter('woocommerce_product_is_in_stock', array($this, 'wc_product_is_in_stock') );
		add_filter('woocommerce_thankyou_order_received_text', array($this, 'wc_custom_thankyou_msg') );
		add_filter('woocommerce_add_to_cart_validation', array($this, 'wc_validate_add_cart_item'), 10, 5 );
		add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'woocommerce_custom_single_add_to_cart_text') ); 


		add_action('woocommerce_new_order_item', array($this, 'adding_custom_data_in_order_items_meta'), 1, 3 );
		add_action('woocommerce_cart_calculate_fees', array($this, 'prefix_add_discount_line') );
		add_action('woocommerce_before_mini_cart', array($this, 'minicart_count_after_content') );
		add_action('woocommerce_review_order_before_order_total', array($this, 'custom_cart_total') );
		add_action('woocommerce_before_cart_totals', array($this, 'custom_cart_total') );
		add_action('woocommerce_thankyou', array($this, 'mt_save_event_in_email'), 999999, 1);
		
	}

	// Disable Payment Gateway for a Specific User Role
	public function hide_payment_gateways($available_gateways){
		if ( isset($available_gateways['paypal']) && !current_user_can('manage_woocommerce') ) {
			unset( $available_gateways['paypal'] );
		} 
		return $available_gateways;
	}

	// Custom order for My account menu
	public function my_account_menu_order_custom($items) {
		$menuOrder = array(
			'dashboard'          => __( 'Dashboard', 'woocommerce' ),
			'orders'             => __( 'Your Orders', 'woocommerce' ),
			'edit-address'       => __( 'Addresses', 'woocommerce' ),
			'edit-account'    	=> __( 'Account Details', 'woocommerce' ),
			'customer-logout'    => __( 'Sign out', 'woocommerce' ),
		);

		return $menuOrder;
	}

	public function custom_add_to_cart_message() {
		global $woocommerce;

		$return_to  = get_permalink(woocommerce_get_page_id('shop'));
		$message    = sprintf('<a href="%s" class="button wc-forwards">%s</a> %s', $return_to, __('Continue Shopping', 'woocommerce'), __('Product successfully added to your cart.', 'woocommerce') );

		return $message;
	}

	public function add_the_date_validation( $passed ) { 
		if ( empty( $_REQUEST['thedate'] ) ) {
			wc_add_notice( __( 'Please enter a date.', 'woocommerce' ), 'error' );
			$passed = false;
		}
		return $passed;
	}

	// Display name and product id here instead
	public function cart_name_with_productid($item_name,  $cart_item,  $cart_item_key){
		$item_name = $item_name.' ('.$cart_item['product_id'].')';
		return $item_name;
	}

	// Change sender name
	public function change_wc_sender_name($from_name, $wc_email){
		if( $wc_email->id == 'customer_processing_order' ){
			$from_name = 'Custom Name';
		}
		return $from_name;
	}

	// Disable specific stylesheets 
	public function disable_wc_styles($enqueue_styles){
		unset( $enqueue_styles['woocommerce-general'] );	// Remove the gloss
		unset( $enqueue_styles['woocommerce-layout'] );		// Remove the layout
		unset( $enqueue_styles['woocommerce-smallscreen'] );// Remove the smallscreen optimisation
		return $enqueue_styles;
	}

	// Adding custom stock status
	public function wc_product_is_in_stock($is_in_stock){
		global $product;
		$stock_statuses = array('onrequest','preorder');

		if (!$is_in_stock && in_array($product->stock_status, $stock_statuses )) {
			$is_in_stock = true;
		}
		return $is_in_stock;
	}

	public function wc_custom_thankyou_msg($thank_you_msg){
		$thank_you_msg =  'This is your new thank you message';
		return $thank_you_msg;
	}

	// Validate before Add to cart
	public function wc_validate_add_cart_item($passed, $product_id, $quantity, $variation_id = '', $variations= ''){
		if ( $product_id == 31 ){
			$passed = false;
			wc_add_notice( __( 'You can not add this product', 'textdomain' ), 'error' );
		}
		return $passed;
	}

	// Change Add to cart text on Single product page
	public function woocommerce_custom_single_add_to_cart_text(){
		return __( 'Buy Now', 'woocommerce' );
	}


	// Add Custom Order meta
	public function adding_custom_data_in_order_items_meta( $item_id, $values, $cart_item_key ) {
		$product_id = $values[ 'product_id' ];
		$custom_meta_value = $values['my_custom_field1_key'];

		if ( !empty($custom_meta_value) ){
			wc_add_order_item_meta($item_id, 'custom_meta_key', $custom_meta_value, true);
		}
	}
	
	//Add Discount to order Total
	public function prefix_add_discount_line( $cart ) {
		$discount = $cart->subtotal * 0.1;
		$cart->add_fee( __( 'Discount', 'yourtext-domain' ) , -$discount );
	}

	public function minicart_count_after_content() {
		$items_count = WC()->cart->get_cart_contents_count();
		$text_label  = _n( 'Item', 'Items', $items_count, 'woocommerce' );

		echo '<p class="total item-count"><strong>'. $text_label.' :</strong> '. $items_count .'</p>';
	}

	public function custom_cart_total() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) )
		return;

		WC()->cart->total *= 0.25;
	}

	public function mt_save_event_in_email( $order_id ) {
		if ( ! $order_id )
			return;

		// Order Complete Third Party API call

	}

}
$wc_functions = new class_wc_custom_functionality();