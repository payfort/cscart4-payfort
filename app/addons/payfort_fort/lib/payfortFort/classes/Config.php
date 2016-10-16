<?php

define('PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION', 'redirection');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE', 'merchantPage');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2', 'merchantPage2');
define('PAYFORT_FORT_PAYMENT_METHOD_CC', 'payfort_fort_cc');
define('PAYFORT_FORT_PAYMENT_METHOD_NAPS', 'payfort_fort_naps');
define('PAYFORT_FORT_PAYMENT_METHOD_SADAD', 'payfort_fort_sadad');
define('PAYFORT_FORT_FLASH_MSG_ERROR', 'E');
define('PAYFORT_FORT_FLASH_MSG_SUCCESS', 'S');
define('PAYFORT_FORT_FLASH_MSG_INFO', 'I');
define('PAYFORT_FORT_FLASH_MSG_WARNING', 'W');

class Payfort_Fort_Config
{

    private static $instance;
    private $language;
    private $merchantIdentifier;
    private $accessCode;
    private $command;
    private $hashAlgorithm;
    private $requestShaPhrase;
    private $responseShaPhrase;
    private $sandboxMode;
    private $gatewayCurrency;
    private $debugMode;
    private $hostUrl;
    private $successOrderStatusId;
    private $orderPlacement;
    private $status;
    private $ccIntegrationType;
    private $gatewayProdHost;
    private $gatewaySandboxHost;
    private $logFileDir;
    private $cartPfSettings;

    public function __construct()
    {
        $this->gatewayProdHost    = 'https://checkout.payfort.com/';
        $this->gatewaySandboxHost = 'https://sbcheckout.payfort.com/';
        $this->logFileDir         = '';

        $this->cartPfSettings = fn_get_payfort_fort_settings();

        $this->language             = $this->_getShoppingCartConfig('language');
        $this->merchantIdentifier   = $this->_getShoppingCartConfig('merchant_identifier');
        $this->accessCode           = $this->_getShoppingCartConfig('access_code');
        $this->command              = $this->_getShoppingCartConfig('command');
        $this->hashAlgorithm        = $this->_getShoppingCartConfig('hash_algorithm');
        $this->requestShaPhrase     = $this->_getShoppingCartConfig('sha_in_pass_phrase');
        $this->responseShaPhrase    = $this->_getShoppingCartConfig('sha_out_pass_phrase');
        $this->sandboxMode          = $this->_getShoppingCartConfig('mode');
        $this->gatewayCurrency      = $this->_getShoppingCartConfig('gateway_currency');
        $this->debugMode            = true;
        //$this->hostUrl = $this->_getShoppingCartConfig('hostUrl');
        $this->successOrderStatusId = 'P';
        $this->orderPlacement       = $this->_getShoppingCartConfig('order_placement');
        $this->status               = true;
        $this->ccIntegrationType    = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Config();
        }
        return self::$instance;
    }

    private function _getShoppingCartConfig($key)
    {
        return isset($this->cartPfSettings['payment_settings'][$key]) ? $this->cartPfSettings['payment_settings'][$key] : '';
    }

    public function getLanguage()
    {
        $langCode = $this->language;
        if ($this->language == 'store') {
            $langCode = Payfort_Fort_Language::getCurrentLanguageCode();
        }
        if ($langCode != 'ar') {
            $langCode = 'en';
        }
        return $langCode;
    }

    public function getMerchantIdentifier()
    {
        return $this->merchantIdentifier;
    }

    public function getAccessCode()
    {
        return $this->accessCode;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getHashAlgorithm()
    {
        return $this->hashAlgorithm;
    }

    public function getRequestShaPhrase()
    {
        return $this->requestShaPhrase;
    }

    public function getResponseShaPhrase()
    {
        return $this->responseShaPhrase;
    }

    public function getSandboxMode()
    {
        return $this->sandboxMode;
    }

    public function isSandboxMode()
    {
        if ($this->sandboxMode == 'sandbox') {
            return true;
        }
        return false;
    }

    public function getGatewayCurrency()
    {
        return $this->gatewayCurrency;
    }

    public function getDebugMode()
    {
        return $this->debugMode;
    }

    public function isDebugMode()
    {
        if ($this->debugMode) {
            return true;
        }
        return false;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getSuccessOrderStatusId()
    {
        return $this->successOrderStatusId;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isActive()
    {
        if ($this->active) {
            return true;
        }
        return false;
    }

    public function getOrderPlacement()
    {
        return $this->orderPlacement;
    }

    public function orderPlacementIsAll()
    {
        if (empty($this->orderPlacement) || $this->orderPlacement == 'all') {
            return true;
        }
        return false;
    }

    public function orderPlacementIsOnSuccess()
    {
        if ($this->orderPlacement == 'success') {
            return true;
        }
        return false;
    }

    public function setCcIntegrationtype($integrationType = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION)
    {
        $this->ccIntegrationType = $integrationType;
    }

    public function getCcIntegrationType()
    {
        return $this->ccIntegrationType;
    }

    public function isCcMerchantPage()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
            return true;
        }
        return false;
    }

    public function isCcMerchantPage2()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
            return true;
        }
        return false;
    }

    public function getGatewayProdHost()
    {
        return $this->gatewayProdHost;
    }

    public function getGatewaySandboxHost()
    {
        return $this->gatewaySandboxHost;
    }

    public function getLogFileDir()
    {
        return $this->logFileDir;
    }

}

?>