<?php 
namespace AmazonPaymentServices;

class APS {

	public $config = [];

	public $gateway_types;

	// init configuration
	public function __construct($params){
		if( is_numeric($params) ){
			$prsr_data = fn_get_processor_data($params);
			$params = !empty($prsr_data['processor_params']) ? (array)$prsr_data['processor_params'] : [];
		}

		$this->config = is_array($params) ? $params : unserialize($params);

		$this->gateway_types = [
			'cc' => __("aps_credit_debit_card"),
			'apple' => __("aps_apple_pay"),
			'naps' => __("aps_naps"),
			'knet' => __("aps_knet"),
			'visa' => __("aps_visa_checkout"),
			'installments' => __("aps_installments"),
			'valu' => __("aps_valu")
		];
	}

	// load gateway
	public function loadGateway($type,$cart = null, $dcc = false){
		$list = $this->getGatewayList($cart,$dcc);
		return !empty($list[$type]) ? $list[$type]['object'] : false;
	}

	// get enabled gateways
	public function getGatewayList($cart = null,$dcc = false){

		$list = [];

		foreach( $this->gateway_types as $type => $title ){
			$gwClass = "\\AmazonPaymentServices\\Gateways\\".ucfirst($type);
			if( class_exists($gwClass) ){
				
				$gateway = new $gwClass($title,$this->parseParams($type));
				
				if( isset($cart['total']) ){
					$gateway->setCurrency(CART_SECONDARY_CURRENCY);
	                $gateway->setAmount($cart['total']);
	                if( !empty($_SESSION['auth']['user_id']) )
		                $gateway->setUserId((int)$_SESSION['auth']['user_id']);
				}
				
				$gateway->disableCurrencyCheck = $dcc;

				if( $gateway->isActive() )
					$list[$type] = ['title'=>$gateway->title, 'object' => $gateway,'first'=>0];
			}
		}

		if( !empty($list) ){
			$first_key = array_keys($list)[0];
			if( count($list) > 1 && $first_key == 'apple' )
				$first_key = array_keys($list)[1];
			$list[$first_key]['first'] = true;
		}

		return $list;
	}

	// parse required params for Gateways
	private function parseParams($type){

		$params = [];

		$req_params = ['merchant_identifier','access_code','request_sha_phrase','response_sha_phrase','sandbox_mode','command','sha_type','currency','enable_tokenization','debug_mode'];

		foreach( $this->config as $key => $value){
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
