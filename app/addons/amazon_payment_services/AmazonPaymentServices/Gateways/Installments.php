<?php 

namespace AmazonPaymentServices\Gateways;

class Installments extends Gateway {
	
	public $type = 'installments';
	public $logos = ['visa-logo.png','mastercard-logo.png'];
	public $supportedCurriences = ['SAR', 'AED', 'EGP'];
	public $issuer_code;
	public $plan_code;

	public function __construct($title,$params){
		parent::__construct($title,$params);

		if( $this->integration_type != 'hosted_checkout' && $this->getConfig('enable_tokenization') == 'N' )
			$this->template = false;
			
		if( $this->integration_type == 'standard_checkout' ){
			if( !isset($_SERVER['HTTP_SEC_FETCH_DEST']) || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] != 'document' ) )
				$this->isJson = true;
		}
	}

	public function isActive(){

		if( $this->active && !$this->disableCurrencyCheck ){
			if( !$this->validateCurrency() || $this->formatPrice($this->amount,CART_SECONDARY_CURRENCY) < floatval($this->getConfig('purchase_limit_'.strtolower(CART_SECONDARY_CURRENCY))) ) 
				$this->active = false;
		}
		
		return $this->active;
	}

	public function verifyResponse($response_code,$status,$resType,$response,$message){
		
		if( isset($response['installments']) && $response['installments'] == 'HOSTED' )
			$this->isJson = false;
		
		if( $resType && $resType == 'TOKENIZATION_SUCCESS' && !isset($post['3ds_url']) ){

			$installments = 'HOSTED';
			$issuer_code = '';
			$plan_code = '';
			$currency = $this->getCurrency(); 
			$amount = $params['amount'] = $this->formatPrice($this->getAmount()/100,$currency)*100;
		
			if( $this->integration_type == 'standard_checkout' ){

				$installments = 'YES';
				$issuer_code = isset($response['issuer_code']) ? trim($response['issuer_code']) : '';
				$plan_code = isset($response['plan_code']) ? trim($response['plan_code']) : '';

				$currency = $response['currency'];
				$amount = $response['amount'];

				fn_update_order_payment_info($this->order_id, [
					'issuer_code' => $issuer_code,
					'plan_code' => $plan_code,
				]);

			}

			if( in_array($this->integration_type,['hosted_checkout','embedded_hosted_checkout']) ){
				$issuer_code = isset($this->order_info['payment_info']['issuer_code']) ? trim($this->order_info['payment_info']['issuer_code']) : '';
				$plan_code = isset($this->order_info['payment_info']['plan_code']) ? trim($this->order_info['payment_info']['plan_code']) : '';
			}

			$params = [
				'merchant_identifier' => $response['merchant_identifier'],
				'access_code' => $response['access_code'],
				'merchant_reference' => $response['merchant_reference'],
				'language' =>  $response['language'],
				'command' => 'PURCHASE',
				'customer_ip' => $this->customer_info['customer_ip'],
				'amount' => trim($amount),
				'currency' =>  $currency,
				'customer_email' => $this->customer_info['customer_email'],
				'return_url' => $this->getReturnUrl(),
				'token_name' => $response['token_name'],
				'installments' => $installments,
				'issuer_code' => $issuer_code,
				'plan_code' => $plan_code,
				'customer_name' => $this->customer_info['customer_name'],
			];

			$this->setAdditionalRequestParams($params);

			$params['signature'] = $this->generateSignature($params);
			
			$this->log("Purchase Plan - Request",[
				'Request Url'=>$this->getServiceUrl(),
				'Params'=>$params
			]);
			
			$response = $this->httpCall('POST',$params);

			$this->log("Purchase Plan - Response",$response);

			$message = isset($response['response_message']) ? $response['response_message'] : '';
			if( !empty($response['error']) ){
				$message = $response['error'];
				$resType = 'FAILED';
			}
				
			$resType = $this->getResponeType($response['response_code'],$response['status']);
			
		}
		
		if( empty($response['response_code']) ) $response['response_code'] = '';
		if( empty($response['status']) ) 		$response['status'] = '';

		return [$response['response_code'],$response['status'],$resType,$response,$message];
	}

	public function verifyParams($params){
			
		if( $this->integration_type == 'redirection' ){
			$params['command'] = 'PURCHASE';
			$params['installments'] ='STANDALONE';
			$params['amount'] = $this->formatPrice($this->getAmount()/100,$params['currency'])*100;
		}

		if( $this->action == 'get_installments' ){

			$params['currency'] = CART_SECONDARY_CURRENCY;
			if( isset($params['currency']) )
				$params['amount'] = $this->formatPrice($this->getAmount()/100,$params['currency'])*100;
		
			$params = [
				'query_command'       => 'GET_INSTALLMENTS_PLANS',
				'merchant_identifier' => $params['merchant_identifier'],
				'access_code'         => $params['access_code'],
				'language'            => $params['language'],
				'amount'              => trim($params['amount']),
				'currency'            => $params['currency'],
			];

		} else {

			if( in_array($this->integration_type,['hosted_checkout','embedded_hosted_checkout']) ){

			    $params['card_number'] = isset($params['card_number']) ? trim(str_replace(' ','',$params['card_number'])) : '';
		    	$params['expiry_date'] = isset($params['expiry_year']) && isset($params['expiry_month']) ? trim($params['expiry_year'].$params['expiry_month']) : '';
		    	$params['card_holder_name'] = isset($params['card_holder_name']) ? ucwords(trim($params['card_holder_name'])) : '';
		    	unset($params['expiry_year'],$params['expiry_month']);
		    	if( !empty($params['token_name']) )
		    		unset($params['card_number'],$params['card_holder_name'],$params['expiry_date']);

			    $this->issuer_code = isset($params['issuer_code']) ? trim($params['issuer_code']) : '';
				$this->plan_code = isset($params['plan_code']) ? trim($params['plan_code']) : '';
			} 

			if( $this->integration_type == 'standard_checkout' ){
				if( empty($params['token_name']) ){
					unset($params['command']);
					$params['service_command'] = 'TOKENIZATION';
					$params['installments'] = 'STANDALONE';
					$params['currency'] = $this->getCurrency();
					$params['amount'] = trim($this->formatPrice($this->getAmount()/100,$params['currency'])*100);
				} else {
					$this->issuer_code = isset($params['issuer_code']) ? trim($params['issuer_code']) : '';
					$this->plan_code = isset($params['plan_code']) ? trim($params['plan_code']) : '';
				}
			}

			if( !empty($params['token_name']) ){
				
				$params['amount'] = trim($this->formatPrice($this->getAmount()/100,$params['currency'])*100);
				$params['command'] = 'PURCHASE';
				$params['installments'] = 'HOSTED';

				$this->isJson = false;
				
			} else
				unset($params['issuer_code'],$params['plan_code']);			
		}

		return $params;
	}

	public function processActions($response){
		
		$params = $response['params'];

		if( $this->action == 'get_installments' ){

			$this->log("Get Plans - Request",[
				'Request Url'=>$this->getServiceUrl(),
				'Params'=>$params
			]);

			$instRes = $this->httpCall('POST',$params);
			
			$this->log("Get Plans - Response",$instRes);
			
			$error =false;
			$plans = [];

			if( !empty($instRes['error']) )
				$error = $instRes['error'];
			else {

				$card_bin = substr(trim(str_replace(' ','',$this->payment_data['card_number'])),0,6);
				
				if( !empty($instRes['installment_detail']['issuer_detail']) ){

					$installment_details = array_filter($instRes['installment_detail']['issuer_detail'], function( $row ){ 
						return !empty($row['plan_details']) && !empty($row['bins']) ? true : false; 
					});

					$plans = $this->getPlansByPin($installment_details,$card_bin,$response['params']['language']);

					if( empty($plans) )
						$error = __("aps_no_insallment_plans");

				} else
					$error = !empty($instRes['response_message']) ? $instRes['response_message'] : __("aps_no_insallment_plans");					
			}

			$response = [
				'success'=>!$error,
				'error'=>$error,
				'installment_details'=> !empty($plans) ?(array)$plans[0] : [],
			];

		} else {

			$response['details']['payment_mode'] = 'PURCHASE';
			$response['details']['installments'] = !empty($params['token_name']) || in_array($this->integration_type,['hosted_checkout','embedded_hosted_checkout']) ? 'HOSTED' : 'YES';

			if( in_array($this->integration_type,['hosted_checkout','embedded_hosted_checkout']) || !empty($params['token_name']) ){
				$response['details']['issuer_code'] = trim($this->issuer_code);
				$response['details']['plan_code'] = trim($this->plan_code);
			}
			
			if( !empty($params['token_name']) ){
				$this->log("Request",[
					'Request Url'=>$this->getServiceUrl(),
					'Params'=>$params
				]);
				
				$resp = $this->httpCall('POST',$params);

				$this->log("Response",$resp);

				$response = $this->processResponse($resp);
			}

		}

		return $response;
	}

	private function getPlansByPin($installment_details,$card_bin,$lang_code){
		$list = [];

		$lang_code = $lang_code == 'ar' ? 'ar' : 'en';
		foreach($installment_details as $detail){
			
			if( in_array($card_bin, array_column($detail['bins'],'bin') ) ){
				
				$plans = [];
				
				foreach($detail['plan_details'] as $pln){
					$plans[] = [
						'code' => trim($pln['plan_code']),
						'fees' => floatval($pln['fees_amount']/100),
						'noi' => intval($pln['number_of_installment']),
						'amount' => round($pln['amountPerMonth'],2),
					];
				}

				$list[] = [
					'issuer_code' => trim($detail['issuer_code']),
					'issuer_name' => trim(isset($detail['issuer_name_'.$lang_code]) ? $detail['issuer_name_'.$lang_code] : $detail['issuer_name_en']),
					'issuer_logo' => trim(isset($detail['issuer_logo_'.$lang_code]) ? $detail['issuer_logo_'.$lang_code] : $detail['issuer_logo_en']),
					'confirmation_message' => trim(isset($detail['confirmation_message_'.$lang_code]) ? $detail['confirmation_message_'.$lang_code] : $detail['confirmation_message_en']),
					'fees_message' => trim(isset($detail['processing_fees_message_'.$lang_code]) ? $detail['processing_fees_message_'.$lang_code] : $detail['processing_fees_message_en']),
					'disclaimer_message' => trim(isset($detail['disclaimer_message_'.$lang_code]) ? $detail['disclaimer_message_'.$lang_code] : $detail['disclaimer_message_en']),
					'terms_and_condition' => trim(isset($detail['terms_and_condition'.$lang_code]) ? $detail['terms_and_condition'.$lang_code] : $detail['terms_and_condition_en']),
					'plans' => $plans,
				];
		  	}
		}

		return $list;
	}
}
