<?php
add_action( 'plugins_loaded', 'nbe_gateway_mastercard_class' );
function nbe_gateway_mastercard_class() {
 
	class nbe_gateway_mastercard extends WC_Payment_Gateway {
 
		public function __construct() {
	 
			$this->id = 'nbe_gateway'; 
			$this->icon = ''; 
			$this->has_fields = true; 
			$this->method_title = 'National Bank of Egypt';
			$this->method_description = 'National Bank Of Egypt Payment Gateway'; 
		   
			$this->supports = array(
			  'products'
			);
		   
			$this->init_form_fields();
		   
			$this->init_settings();
			$this->enabled = $this->get_option( 'enabled' );
			$this->title = 'National Bank of Egypt';
			$this->icon = plugin_dir_url( __FILE__ ).'/images/nbe-logo.PNG';
			$this->description = 'Pay with National Bank of Egypt Payment Gateway';
			$this->nbe_api_version = $this->get_option( 'nbe_api_version' );
			$this->nbe_api_merchant_id = $this->get_option( 'nbe_api_merchant_id' );
			$this->nbe_api_username = $this->get_option( 'nbe_api_username' );
			$this->nbe_api_password = $this->get_option( 'nbe_api_password' );
			$this->nbe_api_domain = $this->get_option( 'nbe_api_domain' );
			$this->nbe_checkout_type = $this->get_option( 'nbe_checkout_type' );
		   
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		   
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			
			if(WC()->cart){
				$this->nbe_checkout_amount = floatval(preg_replace('#[^\d.]#', '', WC()->cart->get_total()));
			}
			if($this->nbe_checkout_amount <= 0){
				$this->nbe_checkout_amount = 100;
			}
			
			//$this->nbe_checkout_amount = floatval(preg_replace('#[^\d.]#', '', WC()->cart->get_total()));
			$current_user = wp_get_current_user();
			if ( $current_user->exists() ) {
				$this->payer_first_name = $current_user->user_firstname;
				$this->payer_last_name = $current_user->user_lastname;
				$this->payer_email = $current_user->user_email;
			}
			if($this->payer_first_name == ''){
				$this->payer_first_name = 'Guest';
				$this->payer_last_name = 'Payment';
			}
			if($this->payer_email == ''){
				$this->payer_email = 'guest@payment.riz';
			}
		}
 
		public function init_form_fields(){
	 
			$this->form_fields = array(
				'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable NBE Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
				),
				'nbe_api_version' => array(
					'title'       => 'API Version',
					'type'        => 'text',
					'description' => 'Add API Version here.',
					'default'     => '57',
					'desc_tip'    => true,
				),
				'nbe_api_merchant_id' => array(
					'title'       => 'API Merchant id',
					'type'        => 'text',
					'description' => 'Add your API Merchant id here.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'nbe_api_username' => array(
					'title'       => 'API Username',
					'type'        => 'text',
					'description' => 'Add your API Username here.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'nbe_api_password' => array(
					'title'       => 'API Password',
					'type'        => 'text',
					'description' => 'Add your API Password here.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'nbe_api_domain' => array(
					'title'       => 'API Domain',
					'type'        => 'text',
					'description' => 'Enter Test or Live Domain here, Default is Test.',
					'default'     => 'test-nbe.gateway.mastercard.com',
				),
				'nbe_api_extra_data' => array(
					'title'       => 'API Extra Data',
					'type'        => 'textarea',
					'description' => 'Enter API Extra Informations',
					'default'     => '',
				),
			);
		}
 
		public function payment_fields() {
		
			// I will echo() the form, but you can close PHP tags and print it directly in HTML
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
	 
			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_credit_card_form_start', $this->id );
		 
			// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
			echo '<div class="form-row form-row-wide">
					<label>Card Number <span class="required">*</span></label>
					<input id="misha_ccNo" name="misha_ccNo" type="text" autocomplete="off">
				</div>
				<div class="form-row form-row-first">
					<label>Expiry Date Month <span class="required">*</span></label>
					<input id="misha_expdate_month" name="misha_expdate_month"  type="text" autocomplete="off" placeholder="MM">
				</div>
				<div class="form-row form-row-last">
					<label>Expiry Date Year <span class="required">*</span></label>
					<input id="misha_expdate_year" name="misha_expdate_year"  type="text" autocomplete="off" placeholder="YY">
				</div>
				<div class="form-row form-row-wide">
					<label>Card Code (CVC) <span class="required">*</span></label>
					<input id="misha_cvv" name="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
				</div>
				<div class="clear"></div>';
		 
			do_action( 'woocommerce_credit_card_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';
		}
	
		public function payment_scripts() {
			
		}

		public function validate_fields() {
			$misha_ccNo = $_POST[ 'misha_ccNo' ];
			$misha_expdate_month = $_POST[ 'misha_expdate_month' ];
			$misha_expdate_year = $_POST[ 'misha_expdate_year' ];
			$misha_cvv = $_POST[ 'misha_cvv' ];
			
			if(empty($misha_ccNo) || empty($misha_expdate_month) || empty($misha_expdate_year) || empty($misha_cvv)){
				wc_add_notice(  'Credit Card Information Required', 'error' );
				return false;
			}
			if( strlen($misha_ccNo) < 10 ) {
				wc_add_notice(  'Credit Card - Invalid Card Number', 'error' );
				return false;
			}
			if( strlen($misha_expdate_month) != 2 || $misha_expdate_month > 12 ) {
				wc_add_notice(  'Credit Card - Invalid Expiry Month', 'error' );
				return false;
			}
			if( strlen($misha_expdate_year) != 2) {
				wc_add_notice(  'Credit Card - Invalid Expiry Year', 'error' );
				return false;
			}
			if( strlen($misha_cvv) != 3) {
				wc_add_notice(  'Credit Card - Invalid CVV', 'error' );
				return false;
			}

			return true;
		}
 
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			$order_total = $order->data['total'];
			global $woocommerce;
			
			if($order_total > 0){
			
				$nbe_api_sessionid = $this->nbe_api_create_session();

				if($nbe_api_sessionid && $nbe_api_sessionid != ''){
					////////////// UPDATE SESSION
					$us_response = $this->nbe_api_update_session($nbe_api_sessionid, $order_id);
					
					/////////// USE SESSION ID TO GENERATE TOKEN
					$gateway_token = '9999999'.sprintf("%09s", $order_id);
					$gt_response = $this->nbe_api_generate_token($gateway_token, $nbe_api_sessionid);
					
					//// Make final Payment API call
					$final_step = $this->nbe_api_token_payment($gateway_token, $order_total, $order_id );
					$result = $final_step['result'];
					$authenticationStatus = $final_step['transaction']['authenticationStatus'];
					
					if($result == 'SUCCESS' && $authenticationStatus == 'AUTHENTICATION_SUCCESSFUL'){
						$response = $final_step;
					}else{
						$response = array('result' => 'FAIL');
						$order->add_order_note( 'Order Payment Fail at Final Step!', false );
					}
				}else{
					$response = array('result' => 'FAIL');
				}
			}else{
				$nbe_api_sessionid = $this->nbe_api_create_session();
				if($nbe_api_sessionid && $nbe_api_sessionid != ''){
					////////////// UPDATE SESSION
					$us_response = $this->nbe_api_update_session($nbe_api_sessionid, $order_id);
					
					/////////// USE SESSION ID TO GENERATE TOKEN
					$gateway_token = '9999999'.sprintf("%09s", $order_id);
					$gt_response = $this->nbe_api_generate_token($gateway_token, $nbe_api_sessionid);
					
					$response = array('result' => 'SUCCESS');
				}else{
					$response = array('result' => 'FAIL');
				}
				//Zero Payment
			}
				
			if($response && $response['result'] == 'SUCCESS'){
				
				// we received the payment
				$order->payment_complete();
				$order->reduce_order_stock();

				// some notes to customer (replace true with false to make it private)
				$order->add_order_note( 'Hey, your order is paid! Thank you!', false );
				
				global $wpdb;
				$wpdb->insert('wpp_order_payments', array(
					'order_id' => $order_id,
					'gateway_token' => $gateway_token,
					'payment_responce' => serialize($response)
				));

				// Empty cart
				$woocommerce->cart->empty_cart();
				
				// Redirect to the thank you page
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);

			}else{
				wc_add_notice(  'Payment Failed, Please try again.', 'error' );
				return;
			}
		}
		
		public function nbe_api_curl_call( $gatway_method, $gatway_domain, $request ) {
			$curlObj = curl_init();
			curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, $gatway_method);
			curl_setopt($curlObj, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array("Content-Length: " . strlen($request)));
			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array("Content-Type: Application/json;charset=UTF-8"));
			curl_setopt($curlObj, CURLOPT_URL, $gatway_domain);
			curl_setopt($curlObj, CURLOPT_USERPWD, $this->nbe_api_username.":".$this->nbe_api_password);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($curlObj);
			curl_close($curlObj);
			$response = json_decode($response, true);
			
			return $response;
		}
		public function nbe_api_create_session(){
			$gatway_domain = 'https://'.$this->nbe_api_domain.'/api/rest/version/'.$this->nbe_api_version.'/merchant/'.$this->nbe_api_merchant_id.'/session';
			$request = '';
			$cs_response = $this->nbe_api_curl_call('POST', $gatway_domain, $request);
			$nbe_api_sessionid = $cs_response['session']['id'];
			
			return $nbe_api_sessionid;
		}
		function nbe_api_update_session($nbe_api_sessionid, $order_id){
			$gatway_domain = 'https://'.$this->nbe_api_domain.'/api/rest/version/'.$this->nbe_api_version.'/merchant/'.$this->nbe_api_merchant_id.'/session/'.$nbe_api_sessionid;
			$request = '{
				"order": {
					"currency": "'.get_option( 'woocommerce_currency' ).'",
					"id": '.$order_id.',
					"amount": '.$this->nbe_checkout_amount.'
				}
			}';
			$us_response = $this->nbe_api_curl_call('PUT', $gatway_domain, $request);
			return $us_response;
		}
		public function nbe_api_generate_token($gateway_token, $nbe_api_sessionid){
			$gatway_domain = 'https://'.$this->nbe_api_domain.'/api/rest/version/'.$this->nbe_api_version.'/merchant/'.$this->nbe_api_merchant_id.'/token/'.$gateway_token;
			$request = '{
				"session":{
					"id": "'.$nbe_api_sessionid.'"
				},
				"sourceOfFunds": {
					"type": "CARD",
					"provided":{
						"card": {
							"number": "'.sanitize_text_field($_POST[ 'misha_ccNo' ]).'",
							"expiry": {
								"month": "'.sanitize_text_field($_POST[ 'misha_expdate_month' ]).'",
								"year": "'.sanitize_text_field($_POST[ 'misha_expdate_year' ]).'"
							},
							"securityCode": "'.sanitize_text_field($_POST[ 'misha_cvv' ]).'"
						}
					}
				}
			}';
			$gt_response = $this->nbe_api_curl_call('PUT', $gatway_domain, $request);
			return $gt_response;
		}
		public function nbe_api_token_payment($gateway_token, $order_total, $order_id ){
			$gatway_domain = 'https://'.$this->nbe_api_domain.'/api/rest/version/'.$this->nbe_api_version.'/merchant/'.$this->nbe_api_merchant_id.'/order/'.$order_id.'/transaction/1';
			$request = '{
				"apiOperation": "PAY",
				"order": {
					"currency": "'.get_option( 'woocommerce_currency' ).'",
					"amount": "'.$order_total.'"
				},
				"sourceOfFunds": {
					"token": "'.$gateway_token.'"
				},
				"transaction": {
					"source": "MERCHANT"
				},
				"agreement": {
					"id": "'.$order_id.'"
				}
			}';
			$tk_response = $this->nbe_api_curl_call('PUT', $gatway_domain, $request);
			return $tk_response;
		}

		public function webhook() {
	 
		}
	}
}