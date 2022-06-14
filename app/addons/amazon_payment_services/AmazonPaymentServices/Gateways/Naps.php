<?php 

namespace AmazonPaymentServices\Gateways;

class Naps extends Gateway {
	
	public $type = 'naps';
	public $template = false;
	public $integration_type = 'redirection';
	public $supportedCurriences = ['QAR'];

	public function verifyParams($params){

		$params['command'] = 'PURCHASE';
		$params['payment_option'] = 'NAPS';
		
		if( $this->isBaseCurrency() ){
			$params['currency'] = 'QAR';
			$params['amount'] = $this->formatPrice($params['amount']/100,$params['currency'])*100;
		}

		return $params;
	}
}
