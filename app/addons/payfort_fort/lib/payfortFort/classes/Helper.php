<?php

class Payfort_Fort_Helper
{

    private static $instance;
    private $pfConfig;

    public function __construct()
    {
        $this->pfConfig = Payfort_Fort_Config::getInstance();
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Helper();
        }
        return self::$instance;
    }

    public function getBaseCurrency()
    {
        return CART_PRIMARY_CURRENCY;
    }

    public function getFrontCurrency()
    {
        return CART_SECONDARY_CURRENCY;
    }

    public function getFortCurrency($baseCurrencyCode, $currentCurrencyCode)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    public function getReturnUrl($path)
    {
        return fn_url('payment_notification.' . $path . '?payment=payfort_fort', 'C', 'current');
    }

    public function getUrl($path)
    {
        $url = fn_url($path, AREA, 'current');
        return $url;
    }

    /**
     * Convert Amount with dicemal points
     * @param decimal $amount
     * @param decimal $currency_value
     * @param string  $currency_code
     * @return decimal
     */
    public function convertFortAmount($amount, $currency_value, $currency_code)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $new_amount       = 0;
        //$decimal_points = $this->currency->getDecimalPlace();
        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ($gateway_currency == 'front') {
            $arr_currency_data = fn_get_currencies();
            $currency_data     = $arr_currency_data[$currency_code];
            $total             = fn_format_rate_value($amount, 'F', $currency_data['decimals'], '.', '', $currency_data['coefficient']);
            //$total = fn_format_rate_value($amount, CART_PRIMARY_CURRENCY, CART_SECONDARY_CURRENCY);
            $new_amount        = round($total, $decimal_points);
        }
        else {
            $new_amount = round($amount, $decimal_points);
        }
        $new_amount = $new_amount * (pow(10, $decimal_points));
        return $new_amount;
    }

    /**
     * 
     * @param string $currency
     * @param integer 
     */
    public function getCurrencyDecimalPoints($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    /**
     * calculate fort signature
     * @param array $arrData
     * @param sting $signType request or response
     * @return string fort signature
     */
    public function calculateSignature($arrData, $signType = 'request')
    {
        $shaString = '';

        ksort($arrData);
        foreach ($arrData as $k => $v) {
            $shaString .= "$k=$v";
        }

        if ($signType == 'request') {
            $shaString = $this->pfConfig->getRequestShaPhrase() . $shaString . $this->pfConfig->getRequestShaPhrase();
        }
        else {
            $shaString = $this->pfConfig->getResponseShaPhrase() . $shaString . $this->pfConfig->getResponseShaPhrase();
        }
        
        //calculate hmac 
        if (in_array($this->pfConfig->getHashAlgorithm(), array('hmac512', 'hmac256'))) {
            $signature = $this->calculateHmac($this->pfConfig->getHashAlgorithm(), $shaString, $signType, $this->pfConfig->getRequestShaPhrase(), $this->pfConfig->getResponseShaPhrase());
            return $signature;
        }
        
        $signature = hash($this->pfConfig->getHashAlgorithm(), $shaString);
        return $signature;
    }
    
    /**
     * calculate HMAC
     * 
     * @param type $shaType
     * @param type $shaString
     * @param type $signType request or response
     * @param type $shaInPassPhrase  request pass phrase
     * @param type $shaOutPassPhrase response pass phrase
     */
    public function calculateHmac($shaType, $shaString, $signType, $shaInPassPhrase, $shaOutPassPhrase)
    {
        if ($signType == 'request') {
            $hmacSecretkey = $shaInPassPhrase;
        } else {
            $hmacSecretkey = $shaOutPassPhrase;
        }
        
        if ($shaType == 'hmac256') {
            $signature = hash_hmac('sha256', $shaString, $hmacSecretkey);
        } else {
            $signature = hash_hmac('sha512', $shaString, $hmacSecretkey);
        }
        
        return $signature;
    }
    
    /**
     * Log the error on the disk
     */
    public function log($message, $forceDebug = false)
    {
        fn_log_event('general', 'runtime', array(
            'message' => $message,
        ));
    }

    public function getCustomerIp()
    {
        $ip = fn_get_ip();
        return $ip['host'];
    }

    public function getGatewayHost()
    {
        if ($this->pfConfig->isSandboxMode()) {
            return $this->getGatewaySandboxHost();
        }
        return $this->getGatewayProdHost();
    }

    public function getGatewayUrl($type = 'redirection')
    {
        $testMode = $this->pfConfig->isSandboxMode();
        if ($type == 'notificationApi') {
            $gatewayUrl = $testMode ?  'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' :  'https://paymentservices.payfort.com/FortAPI/paymentApi';
        }
        else {
            $gatewayUrl = $testMode ? $this->pfConfig->getGatewaySandboxHost() . 'FortAPI/paymentPage' : $this->pfConfig->getGatewayProdHost() . 'FortAPI/paymentPage';
        }

        return $gatewayUrl;
    }

    public function setFlashMsg($message, $status = PAYFORT_FORT_FLASH_MSG_ERROR, $title = '')
    {
        fn_set_notification($status, $title, $message);
    }

    public static function loadJsMessages($messages, $isReturn = true, $category = 'payfort_fort')
    {
        $result = '';
        foreach ($messages as $label => $translation) {
            $result .= "arr_messages['{$category}.{$label}']='" . $translation . "';\n";
        }
        if ($isReturn) {
            return $result;
        }
        else {
            echo $result;
        }
    }

}

?>
