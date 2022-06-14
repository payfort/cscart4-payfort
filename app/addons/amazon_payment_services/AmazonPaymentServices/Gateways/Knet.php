<?php 

namespace AmazonPaymentServices\Gateways;

class Knet extends Gateway {

	public $type = 'knet';
	public $template = false;
	public $integration_type = 'redirection';
	public $supportedCurriences = ['KWD'];

	public function verifyParams($params){

		$params['command'] = 'PURCHASE';
		$params['payment_option'] = 'KNET';

		if( $this->isBaseCurrency() ){
			$params['currency'] = 'KWD';
			$params['amount'] = $this->formatPrice($params['amount']/100,$params['currency'])*100;
		}
		$params['amount'] = $params['amount']*10;

		if( !empty($params['phone_number']) )
			$params['phone_number'] = trim(preg_replace('/[^0-9+]/', '',$params['phone_number']));
		
		return $params;
	}

	public function verifyResponse($response_code,$status,$resType,$params,$message){
		
		if( !empty($params['amount']) )
			$params['amount'] = $params['amount']/10;

		return [$response_code,$status,$resType,$params,$message];
	}
}
