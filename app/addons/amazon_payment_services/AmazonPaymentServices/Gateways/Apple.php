<?php 

namespace AmazonPaymentServices\Gateways;

class Apple extends Gateway {
	public $type = 'apple';
	public $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0';
	private $_merchant_identifier;

	public function getApMerchantIdentifier(){

		if( !isset($this->_merchant_identifier) ){	
			$certi_apth = DIR_ROOT.'/var/certificates/'.$this->getConfig('certificate_file');
			$this->_merchant_identifier = openssl_x509_parse(file_get_contents($certi_apth))['subject']['UID'];
		}

		return $this->_merchant_identifier;	
	}

	public function verifyParams($params){
		
		if( $this->action == 'validate_merchant' )
			return $params;

		$request_data = !empty($params['request_data']) ? json_decode(html_entity_decode(filter_var($params['request_data'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)),true) : [];

		$this->log("Token",$request_data);

		$data = [
		    "digital_wallet" => "APPLE_PAY",
		    "command"=> "PURCHASE", 
		    "access_code"=> $params['access_code'], 
		    "merchant_identifier"=> $params['merchant_identifier'], 
		    "merchant_reference"=> $params['merchant_reference'], 
		    "language"=> $params['language'], 
		    "currency"=> $this->getCurrency(), 
		    "amount"=> $this->getAmount(), 
		    "customer_ip"=> $this->customer_info['customer_ip'], 
		    "customer_email"=> $this->customer_info['customer_email'], 
		    "order_description" => 'Order #'.$this->order_id,
		    "apple_data"=> !empty($request_data['paymentData']['data']) ? $request_data['paymentData']['data'] : [], 
		    "apple_signature"=> !empty($request_data['paymentData']['signature']) ? $request_data['paymentData']['signature'] : [], 
     	];
     	
     	if( !empty($this->customer_info['phone_number']) )
	     	$data["phone_number"] = $this->customer_info['phone_number']; 
		
		if( !empty($request_data['paymentData']) ){
			foreach( (array)$request_data['paymentData']['header'] as $key => $value){
			    $data['apple_header']['apple_'.$key] = $value;
			}
		}
		
		if( !empty($request_data['paymentMethod']) ){
			foreach( (array)$request_data['paymentMethod'] as $key => $value){
			    $data['apple_paymentMethod']['apple_'.$key] = $value;
			}
		}

		return $data;
	}

	public function processActions($response){

		$params = $response['params'];

		if( $this->action == 'validate_merchant' ){

			$apple_url = !empty($params['apple_url']) ? trim(filter_var(urldecode($params['apple_url']),FILTER_SANITIZE_URL)) : '';

			if( !empty($apple_url) && filter_var($apple_url, FILTER_VALIDATE_URL) ){

				$production_key  =$this->getConfig('production_key');
				
				$certi_apth = DIR_ROOT.'/var/certificates/'.$this->getConfig('certificate_file');
				$key_path = DIR_ROOT.'/var/certificates/'.$this->getConfig('certificate_key_file');

				$ap_merchant_identifier = $this->getApMerchantIdentifier();
				
				$postData = [
					"merchantIdentifier" => $ap_merchant_identifier, 
					"domainName" => $this->getConfig('domain_name'),
					"displayName" => $this->getConfig('display_name'),
				];

				$this->log("Verify Merchant - Request",[
					'Request Url'=>$apple_url,
					'Params'=>$postData
				]);

				$headers = [
		          'Content-Type: application/json',
		          'charset'=> 'UTF-8',
				  'User-Agent: '.$this->userAgent,
		        ];

		        $ch = curl_init();
		        curl_setopt($ch, CURLOPT_URL, $apple_url);
		        curl_setopt($ch, CURLOPT_POST, 1);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		        
		        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
		        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		        
		        curl_setopt($ch, CURLOPT_SSLCERT, $certi_apth);
				curl_setopt($ch, CURLOPT_SSLKEY, $key_path);
				curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $production_key);
				curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
			    $resp = (array)json_decode(trim(curl_exec($ch)),true);

		        if( curl_errno($ch) )
		        	$resp['error'] = curl_error($ch);
		        else {
		        	if( empty($resp) )
		        		$resp['error'] = "No response";
		        }
		        curl_close($ch);
		        $error = !empty($resp['error']) ? $resp['error'] : null;
		        $success = empty($error);
				
		        $this->log("Verify Merchant - Response",$resp);	        
		        unset($resp['error']);

		        $response = ['success'=>$success, 'data'=>$resp, 'error'=>$error];

		    } else {	
		   		$response['success'] = false;
	    		$response['error'] = "Apple pay url is invalid";
		    }

	    } else {

	    	if( isset($params['apple_data']) ){

	    		$this->log("Purchase Request",[
					'Request Url'=>$this->getServiceUrl(),
					'Params'=>$params
				]);
				
				$resp = $this->httpCall('POST',$params);

				$this->log("Purchase Response",$resp);

				$response = $this->processResponse($resp);

	    	} else {
	    		$response['success'] = false;
	    		$response['error'] = "Invalid Request";
	    	}

	    }

	    if( isset($_REQUEST['aps_custom_order']) && $_REQUEST['aps_custom_order'] == 'Y' ){
			$response['is_json'] = true;
			$response['is_form'] = false;
			$response['data']['order_id'] = $this->order_id;
	   	} 
	   	
	    return $response;
	}
}
