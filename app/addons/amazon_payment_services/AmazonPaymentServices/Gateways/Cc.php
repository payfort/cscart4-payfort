<?php 

namespace AmazonPaymentServices\Gateways;

class Cc extends Gateway {
	public $type = 'cc';
	
	public function __construct($title,$params){
		parent::__construct($title,$params);

		if( $this->integration_type != 'hosted_checkout' && $this->getConfig('enable_tokenization') == 'N')
			$this->template = false;
			
		if( $this->integration_type == 'standard_checkout' ){
			if( !isset($_SERVER['HTTP_SEC_FETCH_DEST']) || ( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] != 'document') )
				$this->isJson = true;
		}

		if( $this->getConfig('show_mada_branding') == 'Y')
			$this->logos[] = 'mada-logo.png';
		
		if( !empty($this->logos) )
			$this->logos = array_merge($this->logos,['visa-logo.png','mastercard-logo.png','amex-logo.png']);

		if( $this->getConfig('show_meeza_branding') == 'Y')
			$this->logos[] = 'meeza-logo.jpg';
		
		if( $this->getConfig('show_mada_branding') == 'Y' || $this->getConfig('show_meeza_branding') == 'Y' )
			$this->title = __('aps_mada_debit_credit_card');	
	}

	public function verifyParams($params){
		
		$mada_regex  = '/^' . trim($this->getConfig('mada_bins')) . '/';
		$meeza_regex = '/^' . trim($this->getConfig('meeza_bins')) . '/';

		if( isset($params['command']) && $params['command'] == 'AUTHORIZATION' ){

			if( !empty($params['card_number']) && ( preg_match($mada_regex, $params['card_number']) || preg_match($meeza_regex, $params['card_number']) ) ) 
				$params['command'] = 'PURCHASE';

			else if( isset($params['payment_option']) && in_array(strtoupper($params['payment_option']), ['MADA','MEEZA']) )
				$params['command'] = 'PURCHASE';				
		}
		
		if( $this->integration_type == 'hosted_checkout' && empty($params['token_name']) ){
		    $params['card_number'] = !empty($params['card_number']) ? trim(str_replace(' ','',$params['card_number'])) : '';
	    	$params['expiry_date'] = !empty($params['expiry_year']) && !empty($params['expiry_month']) ? trim($params['expiry_year'].$params['expiry_month']) : '';
	    	$params['card_holder_name'] = isset($params['card_holder_name']) ? ucwords(trim($params['card_holder_name'])) : '';
	    	unset($params['expiry_year'],$params['expiry_month']);
	    }
	    
		return $params;
	}

	public function processActions($response){
	
		if( $this->integration_type != 'redirection' ){
			$params = $response['params'];
			
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
}