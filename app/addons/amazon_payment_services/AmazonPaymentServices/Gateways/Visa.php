<?php 

namespace AmazonPaymentServices\Gateways;

class Visa extends Gateway {
	public $type = 'visa';
	public $sdk_url;
	public $button_url;
	
	public function __construct($title,$params){
		parent::__construct($title,$params);

		$this->sdk_url = 'https://'.($this->sandbox_mode ? 'sandbox-' : '').'assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';

		$this->button_url = 'https://'.($this->sandbox_mode ? 'sandbox' : 'assets').'.secure.checkout.visa.com/wallet-services-web/xo/button.png';

		if( $this->integration_type != 'hosted_checkout')// && $this->getConfig('enable_tokenization') == 'N')
			$this->template = false;
	}

	public function verifyParams($params){

		if( $this->integration_type == 'hosted_checkout' ){
			unset($params['service_command']);
			$params['command'] = $this->getConfig('command');
			$params['currency'] = $this->getCurrency();
	        $params['amount'] = $this->getAmount();
	        $params['order_description'] = 'Order #'.$this->order_id;

	        foreach($this->customer_info as $ckey => $cvalue){
	        	if( !empty($cvalue) )
		        	$params[$ckey] = trim($cvalue);
	        }
	    }

		$params['digital_wallet'] = 'VISA_CHECKOUT';

		return $params;
	}

	public function processActions($response){
		
		if( $this->integration_type == 'hosted_checkout' ){
			
			$params = $response['params'];
			
			$this->log("Request",[
				'Request Url'=>$this->getServiceUrl(),
				'Params'=>$params
			]);
			
			$visaResp = $this->httpCall('POST',$params);
			
			$this->log("Response",$visaResp);

			$response = $this->processResponse($visaResp);
			
		}

		return $response;
	}
}
