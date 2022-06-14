<?php

use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Embedded;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

setcookie(Tygh::$app['session']->getName(), Tygh::$app['session']->getId(), [
   'expires'  => time()+3600,
   'path'     => ini_get('session.cookie_path'),
   'domain'   => ini_get('session.cookie_domain'),
   'samesite' => 'None',
   'secure'   => true,
   'httponly' => ini_get('session.cookie_httponly')
]);

if( $mode == 'cron' ){
	if( filter_input( INPUT_GET, 'cron_key' ) == 'aps')
		fn_amazon_payment_services_cron_handler();
	exit;
}

if( $mode == 'return' ){
	fn_amazon_payment_services_handle_response('return','','','',filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING));
}

if( $mode == 'notify' ){
		
	$data = filter_var(file_get_contents('php://input'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	$request = json_decode($data, true);
	if( !$request )
		parse_str($data, $request);

	fn_amazon_payment_services_handle_response('notify','','','',$request);
}

if( $mode == 'ajax' ){

	fn_amazon_payment_services_ajax_action_call( filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING) );
}

if( $mode == 'applepay_checkout'){

	$params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

	$action = !empty($params['action']) ? trim($params['action']) : '';
	$payment_id = isset($params['payment_id']) ? (int)$params['payment_id'] : 0;
	$auth = & Tygh::$app['session']['auth'];
	$is_cart = isset($params['page']) && $params['page'] == 'cart';

	$error = false;
	$data = $extra = [];

	if (empty($auth['user_id']) && Registry::get('settings.Checkout.allow_anonymous_shopping')!='allow_shopping')
		$error = "Please login to buy this product";

	else {

		$processor_params = (array)unserialize(db_get_field("SELECT processor_params FROM ?:payments WHERE payment_id = ?i",$payment_id));
 		
 		if( $is_cart )
			$cart = & Tygh::$app['session']['cart'];
		else {
		
			$tempCart = [];
	    	fn_clear_cart($tempCart);
	    	$cart = & $tempCart;
    		$product_data = (array)$params['product_data'];
			
			fn_add_product_to_cart($product_data, $cart, $auth);
			
			$cart['change_cart_products'] = true;
		}

		$cart['payment_id'] = $payment_id;

		$contact_data = filter_var($params['contact_data'],FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		$contact_data = (array)json_decode(html_entity_decode($contact_data),true);
		$email = trim(!empty($contact_data['emailAddress']) ? $contact_data['emailAddress'] : $contact_data['emailaddress']);

		if( !empty($email) && !$auth['user_id'] )
			$auth['user_id'] = (int)db_get_field("SELECT user_id FROM ?:users WHERE email LIKE ?s",$email);

		$cart['user_data'] = fn_get_user_info($auth['user_id']);
			
		if( !empty($contact_data) ){
			$address_info = [
				'address' =>  implode(', ',(array)$contact_data['addressLines']),
				'city' => trim($contact_data['locality']),
				'state' =>  trim($contact_data['administrativeArea']),
				'country' => trim($contact_data['countryCode']),
				'zipcode' => trim($contact_data['postalCode']),
				'firstname' => trim($contact_data['givenName']),
				'lastname' => trim($contact_data['familyName']),
				'phone' => trim($contact_data['phoneNumber']),	
			];
		
			$cart['user_data']['firstname'] = trim($address_info['firstname']);
			$cart['user_data']['lastname'] = trim($address_info['lastname']);
			$cart['user_data']['phone'] = $cart['phone'] = trim($contact_data['phoneNumber']);
			$cart['user_data']['email'] = $email;
		
			foreach($address_info as $ak => $av){
				$cart['user_data']['s_'.$ak] = $av;
				$cart['user_data']['b_'.$ak] = $av;
			}
		} 
		
	   if( !empty($params['shipping_id']) )
	   	$cart['chosen_shipping'] = [$params['shipping_id']];
	    
	   fn_calculate_cart_content($cart, $auth, 'A', true, 'F', true);
	   fn_delete_notification('shipping_rates_changed');
    	
	   $cart = fn_checkout_update_payment($cart, $auth, $payment_id);
	   $cart['skip_notification'] = true;
			
		if( $action == 'place_order' ){
			
			if (empty($cart['user_data']['email'])) {
	         if (empty($auth['user_id'])) 
               $cart['user_data']['email'] = fn_checkout_generate_fake_email_address($cart['user_data'], TIME);
            else {
               $user_data = fn_get_user_info($auth['user_id'], false);
               $cart['user_data']['email'] =  $user_data['email'];
            }
        	}

	   	$cart['payment_info'] = (array)$params['payment_info'];
	    
		   if( !empty($params['order_id']) )
		    	$cart['order_id'] = (int)$params['order_id'];

		    list($order_id, $process_payment) = fn_place_order($cart, $auth);

		   $data['order_id'] = (int)$order_id;

		   if( empty($data['order_id']) )
		   	$error = "Unabel to create order for this product";

		   else {
		    	
		    	$_SESSION['auth']['order_ids'][] = $auth['order_ids'][] = $order_id;
		    	$order_info = fn_get_order_info($order_id);

			   if (!empty($order_info['payment_info']) && !empty($payment_info)) 
			      $order_info['payment_info'] = $payment_info;
			    
			   list($is_processor_script, $processor_data) = fn_check_processor_script($order_info['payment_id']);

			   if ($is_processor_script) {
					set_time_limit(300);
					fn_mark_payment_started($order_id);

					$mode = Registry::get('runtime.mode');
					Embedded::leave();
					$pp_response = array();
					$mode = 'place_order';

					$location_manager = Tygh::$app['location'];
					$order_info = $location_manager->fillEmptyLocationFields($order_info, BILLING_ADDRESS_PREFIX);
					$order_info = $location_manager->fillEmptyLocationFields($order_info, SHIPPING_ADDRESS_PREFIX);
					$_REQUEST['aps_custom_order'] = 'Y';
					include(fn_get_processor_script_path($processor_data['processor_script']));

					exit;
			   }

		   }
		}

		if( $action == 'get_shippings' ){

			$location_hash = fn_checkout_get_location_hash($cart['user_data'] ?: []);
		   $is_location_changed = isset($cart['location_hash']) && $cart['location_hash'] !== $location_hash;

		   $shipping_calculation_type = fn_checkout_get_shippping_calculation_type($cart, $is_location_changed);
			list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F');
			$shipping_cost = number_format($cart['shipping_cost'],2,'.','');
				
			$extra['shippings'] = [];
			$extra['shipping_cost'] = $shipping_cost;
			if( empty($cart['chosen_shipping']) ) $cart['chosen_shipping'] = [];

			if( !empty($product_groups[0]['shippings']) ){
				foreach($product_groups[0]['shippings'] as $shp){
					if( in_array($shp['shipping_id'],(array)$cart['chosen_shipping']) )
						$extra['identifier'] = 'si_'.(int)$shp['shipping_id'];
					$extra['shippings'][] = [
						'label' => trim($shp['shipping']), 
						'amount' => number_format($shp['rate'],2,'.',''),
						'detail' => trim($shp['delivery_time']), 
						'identifier'=> 'si_'.(int)$shp['shipping_id'],
					];
				}
			}
		}

		if( $action == 'get_params' || $action == 'add_to_cart' ){
	
			$location_hash = fn_checkout_get_location_hash($cart['user_data'] ?: []);
		   $is_location_changed = isset($cart['location_hash']) && $cart['location_hash'] !== $location_hash;

		   $shipping_calculation_type = fn_checkout_get_shippping_calculation_type($cart, $is_location_changed);
			list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F');
			
			if( empty($cart['total']) )
				$error = "Unable to add product to cart";

			else {

				$currency = CART_SECONDARY_CURRENCY;
				$cart_subtotal = number_format($cart['subtotal'],2,'.','');
				$shipping_cost = number_format($cart['shipping_cost'],2,'.','');
				$tax_subtotal = number_format($cart['tax_subtotal'],2,'.','');
				$cart_total = number_format($cart['total'],2,'.','');
				$discount = number_format($cart['discount'],2,'.','');

				$extra['running_total'] = $cart_subtotal+$tax_subtotal-$discount;
				$extra['shippings'] = [];
				$extra['shipping_cost'] = $shipping_cost;

				if( empty($cart['chosen_shipping']) ) $cart['chosen_shipping'] = [];

				if( !empty($product_groups[0]['shippings']) ){
					foreach($product_groups[0]['shippings'] as $shp){
						if( in_array($shp['shipping_id'],(array)$cart['chosen_shipping']) )
							$extra['identifier'] = 'si_'.(int)$shp['shipping_id'];
						$extra['shippings'][] = [
							'label' => trim($shp['shipping']), 
							'amount' => number_format($shp['rate'],2,'.',''),
							'detail' => trim($shp['delivery_time']), 
							'identifier'=> 'si_'.(int)$shp['shipping_id'],
						];
					}
				}

				$lineItems = [];
				$lineItems['subtotal'] = ["type"=>'final', "label"=>__('subtotal'), "amount"=>$cart_subtotal];

				if( $shipping_cost > 0 )
					$lineItems['shipping'] = ["type"=>'final', "label"=>__('shipping_cost'), "amount"=>number_format($shipping_cost,2,'.','')];
				if( $discount > 0 )
					$lineItems['discount'] = ["type"=>'final', "label"=>__('discount'), "amount"=>number_format($discount,2,'.','')];
				if( $tax_subtotal > 0 )
					$lineItems['tax'] = ["type"=>'final', "label"=>__('tax'), "amount"=>$tax_subtotal];

				$data = [
				    "countryCode" => isset($cart['user_data']['s_country']) ? $cart['user_data']['s_country'] : 'AE',
				    "currencyCode" => $currency,
				    "merchantCapabilities" => ['supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit'],
				    "supportedNetworks" => !empty($processor_params['apple_supported_networks']) ? array_keys((array)$processor_params['apple_supported_networks']) : [],
				    "lineItems" => $lineItems,
				    'requiredShippingContactFields' =>['postalAddress', 'name', 'email','phone'],
				    "total" => [
				        "label"=> trim($processor_params['apple_display_name']),
				        "amount"=> $cart_total
				    ],
				];
			}
		}

	}

	header("Content-type: application/json");
	echo json_encode([
		'success'=> !$error,
		'error'	 => $error,
		'data'   => $data,
		'extra'  => $extra,
	]);
	exit;
}