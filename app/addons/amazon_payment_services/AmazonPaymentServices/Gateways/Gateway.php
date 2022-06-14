<?php 

namespace AmazonPaymentServices\Gateways;

use Tygh\Registry;

class Gateway{

	public $type = '';
	public $title = '';
	public $template = true;
	public $active = false;
	public $debug = false;
	public $sandbox_mode = false;

	public $integration_type;
	public $config = [];
	public $logos = [];

	public $customer_info = [];
	public $user_id = 0;
	public $order_id = 0;
	public $payment_id = 0;
	public $reference;
	public $order_info = [];
	public $amount = 0;
	public $currency = null;
	public $return_url;
	public $payment_data = [];
	public $post = [];
	public $isJson = false;
	public $action;
	public $disableCurrencyCheck = false;

	public $mode = 'return';
	public $logger;
	public $card_tokens;
	public $link = false;

	public $responseCodes = [
		'CAPTURE_SUCCESS' => ['04000'],
		'PAYMENT_SUCCESS' => ['14000'],
		'AUTHORIZATION_SUCCESS' => ['02000'],
		'TOKENIZATION_SUCCESS' => ['18063','18000'],
		'ONHOLDS' => ['15777','15778','15779','15780','15781','00006','01006','02006','03006','04006','05006','06006','07006','08006','09006','11006','13006','17006'],
		'FAILED' => ['13666'],
		'CANCELLED' => ['00072','13072'],
		'REDIRECT' => ['20064'],

		'SAFE_TOKENIZATION_SUCCESS' => ['18062'],
		'GET_INSTALLMENT_SUCCESS' => ['62000'],
		'VALU_CUSTOMER_VERIFY_SUCCESS' => ['90000'],
		'VALU_CUSTOMER_VERIFY_FAILED' => ['00160'],
		'VALU_OTP_GENERATE_SUCCESS' => ['88000'],
		'VALU_OTP_VERIFY_SUCCESS ' => ['92182'],
		'REFUND_SUCCESS' => ['06000'],
		'TOKEN_SUCCESS' => ['52062','52'],
		'AUTHORIZATION_VOIDED_SUCCESS'  => ['08000'],
		'CHECK_STATUS_SUCCESS' => ['12000'],
	];

	public $supportedCurriences = [];

	public function __construct($title,$params, $type = null){
		
		if( is_string($params) )
			$params = unserialize($params);

		if( !empty($type) )
			$params = $this->parseParams($type,$params);

		$this->active = isset($params['enabled']) && $params['enabled'] == 'Y' ? true : false;

		$this->title = trim($title);

		if( !empty($params['integration_type']) )
			$this->integration_type = trim($params['integration_type']);
		
		$this->debug = isset($params['debug_mode']) && $params['debug_mode'] == 'Y' ? true : false;
		$this->sandbox_mode = isset($params['sandbox_mode']) && $params['sandbox_mode'] == 'Y' ? true : false;

		unset($params['enabled'],$params['integration_type'],$params['debug_mode'],$params['sandbox_mode']);
		
		$this->config = $params;

		$this->logger = new \AmazonPaymentServices\Logger($this->debug);
	}

	// check active
	public function isActive(){
		return $this->active && $this->validateCurrency() ? true : false;
	}

	// set callback mode
	public function setMode($mode){
		$this->mode = strtolower($mode);
	}

	// set user id
	public function setOrderId($id){
		$this->order_id = (int)$id;
	}

	// set payment id
	public function setPaymentId($id){
		$this->payment_id = (int)$id;
	}

	
	// set reference
	public function setReference($ref){
		$this->reference = trim($ref);
	}

	// set order details
	public function setOrderDetails($order){
		$this->order_info = $order;
	}

	// set user id
	public function setUserId($id){
		$this->user_id = (int)$id;
	}

	// set return url
	public function setReturnUrl($return_url){
		$this->return_url = trim($return_url);
	}

	// set order total amount
	public function setAmount($amount){
		$this->amount = floatval(number_format($amount,2,'.',''));
	}	

	// set customer info
	public function setCustomerInfo($details){
		$this->customer_info = is_array($details) ? $details : [];
	}

	// set addotional details
	public function setPaymentData($details){
		$_payment_data = is_array($details) ? $details : [];
		foreach($_payment_data as $_key => $value ){
			$this->payment_data[$_key] = $value;
		}
	}

	// set returned POST data
	public function setResponse($post){
		$this->post = is_array($post) ? $post : [];
	}

	// set returned POST data
	public function setCurrency($currency= null){
		$this->currency = !empty($currency) ? $currency : (!$this->isBaseCurrency() ? CART_SECONDARY_CURRENCY : CART_PRIMARY_CURRENCY);
	}

	// get configure parameters by key
	public function getConfig($key){
		return isset($this->config[$key]) ? $this->config[$key] : false;
	}

	// check base currency
	public function isBaseCurrency(){
		return isset($this->config['currency']) && $this->config['currency'] == 'B';
	}

	// check currency
	public function validateCurrency(){
		if( empty($this->supportedCurriences) || empty($this->currency) || $this->disableCurrencyCheck )
			return true;

		return in_array($this->currency, $this->supportedCurriences);
	}

	// get currency
	public function getCurrency(){
	
		$currency = $this->isBaseCurrency() 
			? CART_PRIMARY_CURRENCY
			: ( !empty($this->currency) ? $this->currency :  CART_SECONDARY_CURRENCY );

		return $currency;
	}

	// get formatted amount
	public function getAmount($amount = null){
		
		if( $this->isBaseCurrency() )
			$this->currency = CART_PRIMARY_CURRENCY;

		$amount = $this->formatPrice(isset($amount) ? (float)$amount : $this->amount, $this->currency);
		
		return number_format($amount*100,0,'.','');
	}

	// get store language
	public function getLanguage(){
		return strtolower(DESCR_SL) == 'ar' ? 'ar' : 'en';
	}

	public function getResponeType($rcode,$status){
		$type = false;

		foreach( $this->responseCodes as $_type => $_codes ){
			if( in_array($rcode,$_codes) || in_array($status,$_codes) )
				$type = $_type;
		}

		return $type;
	}

	// get environment based on mode
	public function getServiceUrl(){
		return $this->sandbox_mode
			? 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi'
			: 'https://paymentservices.payfort.com/FortAPI/paymentApi';
	}

	// get checkout based on mode
	public function getCheckoutUrl(){
		return $this->sandbox_mode
			? 'https://sbcheckout.payfort.com/FortAPI/paymentPage'
			: 'https://checkout.payfort.com/FortAPI/paymentPage';
	}

	// set return url
	public function getReturnUrl(){
		
		if( !empty($this->return_url) )
			return $this->return_url;

		if( !empty($this->order_id) )
			return fn_url("payment_notification.aps_return", AREA, 'current');

		return false;
	}	

	// validate and process paymenet
	public function processResponse($post = null){

		$res = ['success'=>false, 'error'=>false ];

		if( isset($post) ){
			$res['is_redirect'] = false;
			$res['is_form'] = false;
			$res['is_json'] = false;
			$res['is_direct'] = true;
		
		} else {
			$post = $this->post;
			$this->log("Notify Response",['Notify Url'=>$_SERVER['REQUEST_URI'],'Response'=>$post]);
		}

		if( $this->mode == 'notify' )
			$details = (array)unserialize(fn_decrypt_text(db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $this->order_id)));
		else
			$details = [ 'gateway' => $this->title ];

		$order_status = 'F';
		$validSignature = true;
			
		$status = isset($post['status']) ? trim($post['status']) : '';
		$response_code = isset($post['response_code']) ? trim($post['response_code']) : '';
		$message = isset($post['response_message']) ? trim($post['response_message']) : '';		

		if( empty($response_code) )
			$message = __("aps_gateway_no_valid_response");
		else {
			if( isset($post['signature']) && trim($post['signature']) != $this->generateSignature($post,true) ){
				$validSignature = false;
				$message = __("aps_payment_mismatch_signature");
			}
		}

		$resType = $this->getResponeType($response_code,$status);

		if( !empty($post['token_name']) && in_array($this->type,['cc','installments']) && ( (isset($post['remember_me']) && $post['remember_me'] == 'YES') || in_array($this->integration_type,['standard_checkout','redirection']) ) && in_array($resType,['PAYMENT_SUCCESS','CAPTURE_SUCCESS','AUTHORIZATION_SUCCESS','TOKENIZATION_SUCCESS']) )
			$this->saveCardToken($post);

		if( $this->mode != 'notify' )
			list($response_code,$status,$resType,$post,$message) = $this->verifyResponse($response_code,$status,$resType,$post,$message);
		
		if( $validSignature && $resType == 'TOKENIZATION_SUCCESS' ){
			$post = $this->processToken($post);
	
			$status = isset($post['status']) ? trim($post['status']) : '';
			$response_code = isset($post['response_code']) ? trim($post['response_code']) : '';
			if( !empty($post['error']) )
				$message = $post['error'];
			else 
				$message = $post['response_message'];

			$resType = $this->getResponeType($response_code,$status);
		}

		$message = trim(trim($message),'.');

		if ( in_array($resType,['PAYMENT_SUCCESS','CAPTURE_SUCCESS','AUTHORIZATION_SUCCESS']) ){
			$order_status = 'P';
			//on_success;

		} elseif ( $resType == 'REDIRECT' && isset($post['3ds_url']) ) {

			if( $this->isJson )
				echo '<center>'.__("aps_processing").' ...</center><script>window.top.location.href = "'.$post['3ds_url'].'"</script>';
			else
				header("Location: ".$post['3ds_url']);
			exit;

		} elseif ( $resType == 'ONHOLD' ){
			$order_status = 'O';
			//on_hold;
			
		} elseif ( in_array($resType,['CANCELLED','AUTHORIZATION_VOIDED_SUCCESS']) ){
			$order_status = 'I';

		} elseif ( $resType == 'REFUND_SUCCESS' ){
			$order_status = 'P';
			//on refund;

		} else {

			if( $resType && $resType != 'FAILED' ){
				$order_status = 'D';
				// on_decline
			}
		}

		if ( in_array($resType,['CAPTURE_SUCCESS','REFUND_SUCCESS']) ){
			
			$captured_amount = floatval($details['captured_amount']);
			$refunded_amount = floatval($details['refunded_amount']);

			$done_amount = number_format(floatval($post['amount'])/100,'2','.','');
			
			if( $resType == 'CAPTURE_SUCCESS' ){
				$details['captured_amount'] = $captured_amount+$done_amount;

				if( $details['captured_amount'] != $details['amount']  )
					$post['command'] = $details['payment_mode'];
			}

			if( $resType == 'REFUND_SUCCESS' ){
				$details['refunded_amount'] = $refunded_amount+$done_amount;
			
				if( $details['refunded_amount'] != $details['captured_amount']  )
					$post['command'] = $details['payment_mode'];
			}
		}

		if( !empty($post['command']) )
			$details['payment_mode'] = $post['command'];

		if( !empty($post['merchant_reference']) )
			$details['merchant_reference'] = $post['merchant_reference'];

		if( !empty($post['fort_id']) )
			$details['fort_id'] = $post['fort_id'];

		if( !empty($post['knet_ref_number']) )
			$details['knet_ref_number'] = $post['knet_ref_number'];

		if( !empty($post['third_party_transaction_number']) )
			$details['third_party_transaction_number'] = $post['third_party_transaction_number'];

		if( !empty($post['transaction_id']) )
			$details['transaction_id'] = $post['transaction_id'];

		if( !empty($post['call_id']) )
			$details['call_id'] = $post['call_id'];

		if( !empty($post['response_code']) )
				$details['response_code'] = $post['response_code'];
			
		if( $this->mode != 'notify' ){	
			if( !empty($post['currency']) )
				$details['currency'] = $post['currency'];
		
			if( !empty($post['amount']) )
				$details['amount'] = number_format($post['amount']/100,2,'.','');
		}

		if( !empty($post['digital_wallet']) )	
			$details['digital_wallet'] = $post['digital_wallet'];

		if( !empty($post['tenure']) )
			$details['tenure'] = $post['tenure'];

		if( !empty($post['issuer_code']) )
			$details['issuer_code'] = $post['issuer_code'];

		if( !empty($post['plan_code']) )
			$details['plan_code'] = $post['plan_code'];

		if( !empty($post['installments']) )
			$details['installments'] = $post['installments'];

		if( !empty($post['number_of_installments']) )
			$details['number_of_installments'] = $post['number_of_installments'];

		if( !empty($post['payment_option']) )	
			$details['card_type'] = $post['payment_option'];

		if( !empty($post['card_number']) )	
			$details['card_number'] = $post['card_number'];
		
		if( !empty($post['expiry_date']) )
			$details['expiry_date'] = substr($post['expiry_date'],-2).'/'.substr($post['expiry_date'],0,2);
		
		if( !empty($post['card_holder_name']) )	
			$details['cardholder_name'] = $post['card_holder_name'];

		if( $this->type == 'valu' && !empty($post['phone_number']) )	
			$details['phone_number'] = $post['phone_number'];

		if( $order_status == 'P' && $post['command'] == 'PURCHASE' && !empty($details['amount']) )
			$details['captured_amount'] = $details['amount'];

		if( in_array($order_status,['P','O']) )
			$res['success'] = true;
		else
			$res['error'] = $message;

		if( $res['success'] && !empty($post['token_name']) && in_array($this->type,['cc','installments']) )
			$this->saveCardToken(['token_name'=>$post['token_name'],'type'=>$post['payment_option']],true);

		$details['reason_text'] = $message;
		$details['order_status'] = $order_status;

		$res['details'] = $details; 
		if( !$res['error'] ) $res['success'] = true;
		
		if( $this->mode == 'notify' ){
			$res['success'] = true;
			$res['error'] = false;
		}

		return $res;
	}

	public function getCardTokens(){
		
		if( isset($this->card_tokens) )
			return $this->card_tokens;
	
		$list = [];
		if( !empty($this->user_id) && $this->getConfig('enable_tokenization') == 'Y'){

			$list = db_get_array("SELECT * FROM ?:aps_card_tokens WHERE user_id = ?i ORDER BY timestamp ASC",$this->user_id);
			$this->card_tokens = $list;	
		}

		return $list;
	}

	// get card type
	public function getCardType($card_number){
		
		$card_number = trim(str_replace('*','0',$card_number));
		
		$card_number = trim($card_number);
		$mada_bins = $this->getConfig('mada_bins');
		$meeza_bins = $this->getConfig('meeza_bins');
		
		$card_type = '';
		if( !empty($mada_bins) && preg_match( '/^'.$mada_bins.'/', $card_number ) ) 
			$card_type = 'MADA';
		else if( !empty($meeza_bins) && preg_match( '/^'.$meeza_bins.'/', $card_number ) )
			$card_type = 'MEEZA';
		else if( preg_match('/^4[0-9]{0,15}$/m', $card_number ) ) 
			$card_type = 'VISA';
		else if ( preg_match('/^5$|^5[0-5][0-9]{0,16}$/m', $card_number ) )
			$card_type = 'MASTERCARD';
		else if ( preg_match('/^3$|^3[47][0-9]{0,13}$/m', $card_number ) ) 
			$card_type = 'AMEX';

		return $card_type;
	}

	// save card
	public function saveCardToken($data,$update = false){

		if( !empty($this->user_id) && $this->getConfig('enable_tokenization') == 'Y'){
			
			$is_exists = isset($data['token_name']) ? db_get_field("SELECT card_id FROM ?:aps_card_tokens WHERE user_id = ?i AND token_name = ?s",$this->user_id,trim($data['token_name'])) : false;

			if( $update && !empty($data['token_name']) ){
				
				unset($data['token_name']);
				if( $is_exists )
					db_query("UPDATE ?:aps_card_tokens SET ?u WHERE card_id = ?i",$data,$is_exists);

			} else {
				if( !$is_exists ){
					
					$payment_option = isset($data['payment_option']) ? trim($data['payment_option']) : '';
					if( empty($payment_option) )
						$payment_option = $this->getCardType($data['card_number']);

					$card_data = [
						'user_id' => $this->user_id,
						'payment_id' => $this->payment_id,
						'gateway' => $this->type,
						'token_name' => trim($data['token_name']),
						'type' => $payment_option,
						'card_number' => trim($data['card_number']),
						'expiry_date' => trim($data['expiry_date']),
						'default' => empty($this->getCardTokens()) ? 'Y' : 'N',
						'timestamp' => time(),		 
					];

					db_query("INSERT INTO ?:aps_card_tokens ?e",$card_data);
				}
			}
		}
	}

	// process card token in return request
	public function processToken($post){

		$request = $this->tokenRequest(
			isset($post['merchant_reference']) ? $post['merchant_reference'] : '',
			isset($post['token_name']) ? $post['token_name'] : ''
		);

		if( $request['success'] ){			

			$this->log("Tokenization - Request",[
				'Request Url'=>$this->getServiceUrl(),
				'Params'=>$request['params']
			]);

			$post = $this->httpCall('POST',$request['params']);
			
			$this->log("Tokenization - Response",$post);

		} else {
			$post['response_message'] = $request['error'];
			$post['status'] == '99';
		}

		return $post;
	}

	public function tokenRequest($reference,$token){

		$success = $error = false; 

		if( empty($reference) )
			$error = __("aps_merchant_reference_empty");
		
		if( empty($token) )
			$error = __("aps_token_not_valid");

		$command = $this->type != 'cc' ? 'PURCHASE' : $this->getConfig('command');

		$params = [];

		if( !$error ){

			$currency = $this->getCurrency();
			$params = [
				'command' => $command,
				'access_code' => $this->getConfig('access_code'),
	            'merchant_identifier' => $this->getConfig('merchant_identifier'),
            	'merchant_reference' => $reference,
	            'amount' => $this->amount,
	            'currency' => $currency,
	        	'amount' => !empty($this->supportedCurriences) 
	            	? number_format($this->formatPrice($this->getAmount()/100,$currency)*100,0,'.','')
	            	: $this->getAmount(),
		        'language' => $this->getLanguage(),
		        'token_name' => trim($token),
		        'order_description' => __('aps_order').' #'.$this->order_id,
	            'return_url' => $this->getReturnUrl(), 
	        ];

			if( !empty($post['remember_me']) )
				$post['remember_me'] = $post['remember_me'];
			
			foreach($this->customer_info as $ckey => $cvalue){
				if( !empty($cvalue) )
		        	$params[$ckey] = trim($cvalue);
	        }

	        $params['merchant_extra1'] = $this->order_id;
        	$params['merchant_extra2'] = $this->user_id;
        	$params['merchant_extra3'] = $this->type;
        	
        	if( isset($params['command']) && in_array($params['command'],['PURCHASE','AUTHORIZATION']) )
	        	$this->setAdditionalRequestParams($params);
        
			$params['signature'] = $this->generateSignature($params);
		}

		return ['success'=>!$error, 'error'=>$error, 'is_redirect'=>false, 'is_form'=>false, 'params'=> $params];
	}

	// generate unique reference
	public function generateReference($order_id = null){
		$bytes = openssl_random_pseudo_bytes(4);
	    $hex   = bin2hex($bytes);

	    if( empty($order_id) )  
	      $order_id = $hex;
	  
	    return strtoupper($order_id).strtoupper(substr(hash("sha256",uniqid($hex.time(), true)),0,8));
	}

	
	// validate and create payment request
	public function processRequest(){

		$res = [
			'success'=>false, 
			'error'=>false, 
			'is_redirect'=>false, 
			'is_form'=> !$this->isJson,
			'is_json' => $this->isJson,
			'is_direct' => false,
		];

		$res['merchant_reference'] = isset($this->reference) ? trim($this->reference) : $this->generateReference($this->order_id);

		$command = $this->getConfig('command');
		
		$params = [
            'access_code' => $this->getConfig('access_code'),
            'merchant_identifier' => $this->getConfig('merchant_identifier'),
            'merchant_reference' => $res['merchant_reference'],
            'language' => $this->getLanguage(),
        ];

        if( $this->getReturnUrl() )
            $params['return_url'] = $this->getReturnUrl();  


        if( !empty($this->payment_data) ){
        	foreach($this->payment_data as $key => $value){
        		if( !empty($value) )
        			$params[$key] = trim($value);
        	}
        }      

		if( $this->integration_type == 'redirection' || ( 
			!empty($params['token_name']) && $this->getConfig('enable_tokenization') == 'Y' )
		){

			$params['command'] = $command;
			$params['currency'] = $this->getCurrency();
	        $params['amount'] = $this->getAmount();
	        $params['order_description'] = 'Order #'.$this->order_id;

	        foreach($this->customer_info as $ckey => $cvalue){
	        	if( !empty($cvalue) )
		        	$params[$ckey] = trim($cvalue);
	        }
	      	
		} else
			$params['service_command'] = 'TOKENIZATION';
				        
        $params = $this->verifyParams($params);

        // set additional params
        if( ( isset($params['command']) && in_array($params['command'],['PURCHASE','AUTHORIZATION']) ) || ( isset($params['service_command']) && in_array($params['service_command'],['PURCHASE','AUTHORIZATION']) ) 
    	){
        	
        	$params['merchant_extra1'] = $this->order_id;
        	$params['merchant_extra2'] = $this->user_id;
        	$params['merchant_extra3'] = $this->type;
        
        	$this->setAdditionalRequestParams($params);
        }
        
        $params['signature'] = $this->generateSignature($params);

        $res['params'] = $params;

        $res['details'] = [
            'gateway'=> $this->title,
            'payment_mode'=> isset($params['command']) ? $params['command'] : $this->getConfig('command'),
            'merchant_reference' => isset($params['merchant_reference']) ? trim($params['merchant_reference']) : '',
        ];

        if( !$res['error'] ) $res['success'] = true;

		return $res;
	}

	// verify or update params by gateway classes
	public function verifyParams($params){
		return $params;
	}

	// verify or update params return from Payfort
	public function verifyResponse($response_code,$status,$resType,$post,$message){
		return [$response_code,$status,$resType,$post,$message];
	}

	// process actions and update response
	public function processActions($response){
		return $response;
	}

	// generate signature for payment request
	public function generateSignature($request,$verify = false){

		$shaString  = '';
		ksort($request);

		$skip = ['card_number', 'expiry_date', 'card_holder_name', 'remember_me','signature'];
		if( $verify )
			$skip = ['signature'];
		else {
			if( empty($request['token_name']) )
				$skip[] = 'card_security_code';
		}

		foreach ($request as $key => $value) {
			if( !in_array($key,$skip) ){
				if( is_array($value) ){
					if($key == 'apple_header' || $key == 'apple_paymentMethod'){
			            $_val = [];
			            foreach($value as $vk => $vv)
			                $_val[] = $vk.'='.$vv;
			            $value = "{".implode($_val, ', ')."}";
			        } else {
						$_value = [];
						foreach($value as $val){
							$valL = [];
							foreach($val as $_key => $_val)
								$valL[]= $_key.'='.$_val;
							$valL = '{'.implode(', ',$valL).'}';
							$_value[] = $valL; 
						}
						$value = '['.implode(', ',$_value).']';
					}
				}

				$shaString .= "$key=$value";
			}
		}

		$sha_phase = trim( $verify ? $this->config['response_sha_phrase'] : $this->config['request_sha_phrase'] ); 
		$shaString =  $sha_phase . $shaString . $sha_phase;
		$sha_type = trim($this->config['sha_type']);
		
		if( strpos($sha_type,'hmac') !== false)
			$hash = hash_hmac( str_replace('hmac','sha',$sha_type), $shaString, $sha_phase );
		else
			$hash = hash($sha_type, $shaString);

		return $hash;
	}

	public function getProducts($currency,$total){
		
		$category_name = '';
		$product_names = [];

		if( !empty($this->order_info['products']) ){
			foreach($this->order_info['products'] as $product){
				$product_names[] = $product['product'];
				if( empty($category_name) )
					$category_name = db_get_field("SELECT cd.category FROM ?:category_descriptions cd, ?:products_categories pc WHERE cd.category_id = pc.category_id AND pc.product_id =?i AND link_type = 'M' AND lang_code =?s",$product['product_id'],DESCR_SL) ?: 'Uncategorized';
			}
		}

		if( empty($product_names) ) $product_names = ['Product'];

		if( count($product_names) > 1)
			$product_names = "MultipleProducts";
		else
			$product_names = $product_names[0];

		$product_names = trim(preg_replace('/[^A-Za-z0-9]/', '',$product_names));
		$product_names = strlen($product_names) > 100 ? substr($product_names,0,100) : $product_names;

		$products = [[
			'product_name' => trim($product_names),
			'product_price' => $total,
			'product_category' => trim(preg_replace('/[^A-Za-z0-9\-]/', '',str_replace('-','',$category_name))),
		]];

		return $products;
	}

	// set additional request parameters
	public function setAdditionalRequestParams(&$params){
		$params['app_programming'] = 'PHP';
      	$params['app_framework'] = PRODUCT_NAME;
      	$params['app_ver'] = PRODUCT_VERSION;
      	$params['app_plugin'] = PRODUCT_EDITION;
      	$params['app_plugin_version'] = PRODUCT_VERSION;
	}

	// check and update order status
	public function checkUpdateOrder($gateway,$reference,$details){
		
		$params = array(
			'merchant_identifier' => $this->getConfig('merchant_identifier'),
			'access_code'         => $this->getConfig('access_code'),
			'merchant_reference'  => $gateway == 'apple' ? $this->config['apple_access_code'] : $reference,
			'language'            => $this->getLanguage(),
			'query_command'       => 'CHECK_STATUS',
		);

		$params['signature'] = $this->generateSignature($params);
		
		$resp = $this->httpCall('POST',$params);

		$order_status = 'I';
		if( !empty($resp['transaction_code']) ){
			$order_status = 'F';
		
			$resType = $this->getResponeType($resp['transaction_code'],$resp['transaction_status']);
			$command = $resType == 'CAPTURE_SUCCESS' ? 'CAPTURE' : 'AUTHORIZATION';
			
			if ( in_array($resType,['PAYMENT_SUCCESS','CAPTURE_SUCCESS','AUTHORIZATION_SUCCESS']) ){
				$order_status = 'P';
			} elseif ( $resType == 'ONHOLD' ){
				$order_status = 'O';	

			} elseif ( in_array($resType,['CANCELLED','AUTHORIZATION_VOIDED_SUCCESS']) ){
				$order_status = 'I';
				$command =  'VOID_AUTHORIZATION';

			} elseif ( $resType == 'REFUND_SUCCESS' ){
				$command = 'REFUND';
				$order_status = 'B';
			} else {
				if( $resType && $resType != 'FAILED' )
					$order_status = 'D';
			}

			$details['command'] = $command;

			if( !empty($resp['fort_id']) )
				$details['fort_id'] = $resp['fort_id'];

			if( !empty($resp['knet_ref_number']) )
				$details['knet_ref_number'] = $resp['knet_ref_number'];

			if( !empty($resp['third_party_transaction_number']) )
				$details['third_party_transaction_number'] = $resp['third_party_transaction_number'];

			if( !empty($resp['transaction_id']) )
				$details['transaction_id'] = $resp['transaction_id'];

			if( !empty($resp['call_id']) )
				$details['call_id'] = $resp['call_id'];

			if( !empty($resp['response_code']) && in_array($order_status,['P','O']) )
				$details['response_code'] = $resp['response_code'];

			if( !empty($resp['authorized_amount']) || !empty($resp['captured_amount']) )
				$details['amount'] = !empty($resp['authorized_amount']) ? $resp['authorized_amount'] : $resp['captured_amount'];

			if( !empty($resp['captured_amount']) )
				$details['captured_amount'] = $resp['captured_amount'];

			if( !empty($resp['refunded_amount']) )
				$resp['refunded_amount'] = $resp['refunded_amount'];

			if( !empty($resp['digital_wallet']) )	
				$details['digital_wallet'] = $resp['digital_wallet'];

			if( !empty($resp['tenure']) )
				$details['tenure'] = $resp['tenure'];

			if( !empty($resp['issuer_code']) )
				$details['issuer_code'] = $resp['issuer_code'];

			if( !empty($resp['plan_code']) )
				$details['plan_code'] = $resp['plan_code'];

			if( !empty($resp['installments']) )
				$details['installments'] = $resp['installments'];

			if( !empty($resp['number_of_installments']) )
				$details['number_of_installments'] = $resp['number_of_installments'];

			if( !empty($resp['payment_option']) )	
				$details['card_type'] = $resp['payment_option'];

			if( !empty($resp['card_number']) )	
				$details['card_number'] = $resp['card_number'];
			
			if( !empty($resp['expiry_date']) )
				$details['expiry_date'] = substr($resp['expiry_date'],-2).'/'.substr($resp['expiry_date'],0,2);
			
			if( !empty($resp['card_holder_name']) )	
				$details['cardholder_name'] = $resp['card_holder_name'];

			if( $this->type == 'valu' && !empty($resp['phone_number']) )	
				$details['phone_number'] = $resp['phone_number'];

			if( empty($details['captured_amount']) && $order_status == 'P' && $details['command'] == 'PURCHASE' && !empty($details['amount']) )
				$details['captured_amount'] = $details['amount'];

			if( !empty($resp['transaction_message']) )
				$details['reason_text'] = $resp['transaction_message'];
		}

		$details['order_status'] = $order_status;

		return $details;
	}

	public function httpCall($method,$params){
		
		$error = false;
		$url = $this->getServiceUrl();

        if( $method == 'GET')
            $url = $url.'?'.http_build_query($params);
        else
        	$params = json_encode($params);

        $headers = array(
          'Content-Type: application/json',
          'Accept: application/json',
          "User-Agent: CS-Cart",
        );

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        if( $method == 'POST'){    
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = json_decode(trim(curl_exec($ch)),true);
        if( curl_errno($ch) && !$response)
        	$response['error'] = curl_error($ch);
        
        curl_close($ch);

        return $response;
	}

	// formate price based on configuration
	public function formatPrice($price,$currency = CART_SECONDARY_CURRENCY){

		$price = floatval($price);

		if( $currency != CART_PRIMARY_CURRENCY ){		
			$coefficient = (float)Registry::get('currencies.'.$currency.'.coefficient') ?: 1;
			if( $coefficient <= 0 ) $coefficient = 1;

			$price = $price / $coefficient;
		}

		return number_format($price,'2','.','');
	}
	
	// save logs
	public function log($info,$data){
		$this->logger->log($info, $data, $this->type);
	}

	private function parseParams($type,$_params =[]){

		$params = [];

		$req_params = ['merchant_identifier','access_code','request_sha_phrase','response_sha_phrase','sandbox_mode','command','sha_type','currency','enable_tokenization','debug_mode'];
		if( empty($_params) )
			$_params = [];

		foreach( $_params as $key => $value){
			if( strpos($key, $type.'_') === 0 || in_array($key,$req_params) ){
				if( strpos($key, $type.'_') === 0 )
					$key = str_replace('#'.$type.'_','','#'.$key);
				$params[$key] = is_array($value) ? $value : trim($value);
			}
		}

		if( $type == 'installments' ){			
			$params['show_mada_branding'] = trim($this->config['cc_show_mada_branding']);
			$params['mada_bins'] = trim($this->config['cc_mada_bins']);
			$params['show_meeza_branding'] = trim($this->config['cc_show_meeza_branding']);
			$params['meeza_bins'] = trim($this->config['cc_meeza_bins']);
		}
		
		return $params;
	}
}