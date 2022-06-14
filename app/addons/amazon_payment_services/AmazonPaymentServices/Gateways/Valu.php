<?php 

namespace AmazonPaymentServices\Gateways;

class Valu extends Gateway {
	public $type = 'valu';
	public $logos = ['valu-logo.png'];
	public $supportedCurriences = ['EGP'];
	public $valu_interest;
	public $valu_tenure_amt;

	public function isActive(){

		if( $this->active && !$this->disableCurrencyCheck ){
			if( !$this->validateCurrency() || $this->formatPrice($this->amount,'EGP') < floatval($this->getConfig('purchase_limit_egp')) ) 
				$this->active = false;
		}
		
		return $this->active;
	}

	public function verifyParams($params){
		
		$params['payment_option'] = 'VALU';
		unset($params['return_url'],$params['service_command'],$params['command']);

		if( $this->action != 'phone_verify')
			$params['currency'] = 'EGP';//CART_SECONDARY_CURRENCY;
				
		if( $this->action == 'phone_verify' || $this->action == 'otp_verify' ){

			if( $this->action == 'phone_verify' )
				$params['service_command'] = 'CUSTOMER_VERIFY';

			if( $this->action == 'otp_verify'){
				unset($params['transaction_id']);
				$params['service_command'] = 'OTP_VERIFY';
				$params['merchant_order_id'] = str_replace('-','',$params['merchant_reference']);
				$params['amount'] = $this->formatPrice($this->getAmount()/100,$params['currency'])*100;
				$params['total_downpayment'] = 0;	
			}
		} else {
				
			$this->valu_interest = isset($params['interest']) ? (float)$params['interest'] : 0;  
			$this->valu_tenure_amt = isset($params['total_down_payment']) ? (float)$params['total_down_payment'] : 0;
			unset($params['interest']);

			$params['total_down_payment'] = 0;
			$params['currency'] = 'EGP';//$this->getCurrency();
			$params['command'] = 'PURCHASE';
			$params['merchant_order_id'] = str_replace('-','',$params['merchant_reference']);
			$params['purchase_description'] = 'Order'.$this->order_id;
			$params['customer_email'] = $this->customer_info['customer_email'];	
			$params['amount'] = trim($this->formatPrice($this->getAmount()/100,$params['currency'])*100);
			$params['customer_code'] = trim($this->payment_data['phone_number']);

		} 

		return $params;
	}

	public function processActions($response){
		//01008606003

		$response['details']['payment_mode'] = 'PURCHASE';
		$response['details']['phone_number'] = $response['params']['phone_number'];
		
		if( $this->action == 'otp_verify' || $this->action == 'phone_verify' ){

			$response['is_json'] = true;
			$response['is_form'] = false;
			
			if( $this->action == 'otp_verify' ){

				$response['params']['amount'] = trim($response['params']['amount']);
				$response['params']['signature'] = $this->generateSignature($response['params']);

				$this->log("OTP Verify - Request",[
					'Request Url'=>$this->getServiceUrl(),
					'Params'=>$response['params']
				]);

				$otpRes = $this->httpCall('POST',$response['params']);

				$this->log("OTP Verify - Response",$otpRes);
				
				$error =false;
				if( !empty($otpRes['error']) )
					$error = $otpRes['error'];
				
				else {
					if( isset($otpRes['otp_status']) && $otpRes['otp_status'] == '1' ){
						if( empty($otpRes['tenure']['TENURE_VM']) )
							$error = "No installment option returned";
					} else
						$error = $otpRes['response_message'];
				}

				$response['params'] = [
					'success'=>!$error,
					'error'=>$error,
					'tenures'=> !empty($otpRes['tenure']['TENURE_VM']) ? $otpRes['tenure']['TENURE_VM'] : [],
				];
			}

			if( $this->action == 'phone_verify' ){

				if( $response['success'] ){

					$this->log("Phone Verify - Request",[
						'Request Url'=>$this->getServiceUrl(),
						'Params'=>$response['params']
					]);

					$cusRes = $this->httpCall('POST',$response['params']);

					$this->log("Phone Verify - Response",$cusRes);

					$params = $response['params'];
					unset($response['params']);
					
					$error = false;
					if( !empty($cusRes['error']) )
						$error = $cusRes['error'];
					else {

						if( isset($cusRes['response_code']) && $cusRes['response_code'] == '90000' ){
							
							$otpRes = $this->generateOtp($params);
							
							if( !empty($otpRes['error']) )
								$error = $otpRes['error'];
							else {
								if( isset($otpRes['otp_status']) && $otpRes['otp_status'] != '1' )
									$error = $otpRes['response_message'];
							}

						} else
							$error = __('aps_customer not exist');
					}

					if( !empty($otpRes['transaction_id']) )
						$response['details']['transaction_id'] = $otpRes['transaction_id'];

					$response['params'] = [
						'success'=>!$error,
						'error'=>$error,
						'transaction_id' => isset($otpRes['transaction_id']) ? trim($otpRes['transaction_id']) : '',
						'merchant_reference'=>$response['merchant_reference'],
					];
				}
			}
		} else {

			$params = $response['params'];

			$this->log("Purchase - Request",[
				'Request Url'=>$this->getServiceUrl(),
				'Params'=>$params
			]);

			$pcsResponse = $this->httpCall('POST',$params);
			
			$this->log("Purchase - Response",$pcsResponse);

			$response = $this->processResponse($pcsResponse);

			if( empty($response['details']['transaction_id']) && !empty($params['transaction_id']) )
				$response['details']['transaction_id'] = $params['transaction_id'];
			
			if( empty($response['details']['tenure']) && !empty($params['tenure']) )
				$response['details']['tenure'] = $params['tenure'];

			if( empty($response['details']['tenure_amount']) && !empty($this->valu_tenure_amt) )
				$response['details']['tenure_amount'] = $this->valu_tenure_amt.' EGP/Month';

			if( empty($response['details']['tenure_interest']) && $this->valu_interest )
				$response['details']['tenure_interest'] = $this->valu_interest.'%';

		}

		return $response;
	}

	public function generateOtp($params){
		
		$params['service_command'] = 'OTP_GENERATE';
		$params['merchant_order_id'] = str_replace('-','',$params['merchant_reference']);//$this->order_id;
		$params['currency'] = 'EGP';//CART_SECONDARY_CURRENCY;
		$params['amount'] = trim($this->formatPrice($this->getAmount()/100,$params['currency'])*100);
		$params['products'] = $this->getProducts($params['currency'],$params['amount']);
		
		$params['signature'] = $this->generateSignature($params);

		$this->log("OTP Generate - Request",[
			'Request Url'=>$this->getServiceUrl(),
			'Params'=>$params
		]);

		$response = $this->httpCall('POST',$params);

		$this->log("OTP Generate - Response",$response);

		return $response;
	}

}
