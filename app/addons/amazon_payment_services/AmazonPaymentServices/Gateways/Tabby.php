<?php

namespace AmazonPaymentServices\Gateways;

class Tabby extends Gateway
{

    public $type = 'tabby';
    public $logos = ['tabby-logo.png'];
    public $template = false;
    public $integration_type = 'redirection';
    public $supportedCurriences = ['SAR', 'AED'];

    public function verifyParams($params)
    {

        $params['command'] = 'PURCHASE';
        $params['payment_option'] = 'TABBY';

        if ($this->isBaseCurrency()) {
            $params['amount'] = $this->formatPrice($params['amount'] / 100, $params['currency']) * 100;
        }

        return $params;
    }
}
