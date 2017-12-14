<?php

class Payfort_Fort_Order
{

    private $order = array();
    private $orderId;
    private $pfConfig;

    public function __construct($orderId = '')
    {
        $this->pfConfig = Payfort_Fort_Config::getInstance();
        $this->orderId  = $orderId;
    }

    public function loadOrder($orderId)
    {
        $this->orderId = $orderId;
        $this->order   = $this->getOrderById($orderId);
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getSessionOrderId()
    {
        return $this->orderId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getOrderById($orderId)
    {
        return fn_get_order_info($orderId);
    }

    public function getLoadedOrder()
    {
        return $this->order;
    }

    public function getEmail()
    {
        return isset($this->order['email']) ? $this->order['email'] : '';
    }

    public function getCustomerName()
    {
        $fullName  = '';
        $firstName = isset($this->order['b_firstname']) ? $this->order['b_firstname'] : '';
        $lastName  = isset($this->order['b_lastname']) ? $this->order['b_lastname'] : '';

        $fullName = trim($firstName . ' ' . $lastName);
        return $fullName;
    }

    public function getCurrencyCode()
    {
        return isset($this->order['secondary_currency']) ? $this->order['secondary_currency'] : '';
    }

    public function getCurrencyValue()
    {
        return 0;
    }

    public function getTotal()
    {
        return isset($this->order['total']) ? $this->order['total'] : 0;
    }

    public function getPaymentMethod()
    {
        return isset($this->order['payment_method']) ? $this->order['payment_method'] : '';
    }

    public function getIntegrationType()
    {
        return isset($this->order['payment_method']['processor_params']['integration_type']) ? $this->order['payment_method']['processor_params']['integration_type'] : PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
    }
    
    public function getInstallmentsIntegrationType()
    {
        return isset($this->order['payment_method']['processor_params']['integration_type']) ? $this->order['payment_method']['processor_params']['integration_type'] : PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
    }

    public function getStatusId()
    {
        return isset($this->order['status']) ? $this->order['status'] : 0;
    }

    public function declineOrder($reason)
    {
        $status = 'F';
        if ($this->getStatusId() == $status) {
            return true;
        }
        $pp_response = array();
        $pp_response['order_status'] = $status;
        $pp_response['reason_text'] = $reason;
        $orderPlacement = $this->pfConfig->getOrderPlacement();
        if(empty($orderPlacement) || $orderPlacement == 'all'){
            fn_finish_payment($this->getOrderId(), $pp_response, false);
        }
        return true;
    }
    
    public function cancelOrder()
    {
        $status = 'I';
        if ($this->getStatusId() == $status) {
            return true;
        }
        $reason = __('text_payment_canceled');
        $pp_response = array();
        $pp_response['order_status'] = $status;
        $pp_response['reason_text'] = $reason;
        $orderPlacement = $this->pfConfig->getOrderPlacement();
        if(empty($orderPlacement) || $orderPlacement == 'all'){
            fn_finish_payment($this->getOrderId(), $pp_response, false);
        }
        return true;
    }

    public function successOrder($response_params, $response_mode)
    {
        $status = $this->pfConfig->getSuccessOrderStatusId();
        if ($this->getStatusId() == $status) {
            return true;
        }
        $pp_response = array();
        $pp_response['order_status'] = $status;
        if(isset($response_params['fort_id'])) {
            $pp_response["transaction_id"] = $response_params['fort_id'];
        }
        fn_finish_payment($this->getOrderId(), $pp_response);
        return true;
    }

}

?>