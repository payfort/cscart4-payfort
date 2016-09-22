<?php

use Tygh\Http;
use Tygh\Registry;

function fn_payfort_fort_process_request($order_id, $order_info, $payment_method)
{
    $payfort_order_id = ($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id;
    $pfOrder          = new Payfort_Fort_Order($payfort_order_id);
    $pfOrder->setOrder($order_info);

    $pfPayment = Payfort_Fort_Payment::getInstance();

    $integration_type = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
    if ($payment_method == PAYFORT_FORT_PAYMENT_METHOD_CC) {
        $integration_type = $pfOrder->getIntegrationType();
    }
//    if ($order_info['status'] == STATUS_INCOMPLETED_ORDER) {
//        fn_change_order_status($order_id, 'O', '', false);
//    }
//    if (fn_allowed_for('MULTIVENDOR')) {
//        if ($order_info['status'] == STATUS_PARENT_ORDER) {
//            $child_orders = db_get_hash_single_array("SELECT order_id, status FROM ?:orders WHERE parent_order_id = ?i", array('order_id', 'status'), $order_id);
//
//            foreach ($child_orders as $order_id => $order_status) {
//                if ($order_status == STATUS_INCOMPLETED_ORDER) {
//                    fn_change_order_status($order_id, 'O', '', false);
//                }
//            }
//        }
//    }
    $gatewayParams = $pfPayment->getPaymentRequestParams($order_id, $order_info, $payment_method, $integration_type);
    if ($integration_type == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE || $integration_type == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
        echo json_encode($gatewayParams);
        exit;
    }
    else {
        fn_create_payment_form($gatewayParams['url'], $gatewayParams['params'], 'PAYFORT', false);
    }
}

function fn_payfort_fort_process_response($payment_method, $response_mode = 'online', $integration_type = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION)
{
    $response_params = array_merge($_GET, $_POST); //never use $_REQUEST, it might include PUT .. etc
    $order_id        = isset($response_params['merchant_reference']) ? $response_params['merchant_reference'] : '';
    if (fn_check_payment_script($payment_method . '.php', $order_id)) {
        $pfPayment  = Payfort_Fort_Payment::getInstance();
        $success    = $pfPayment->handleFortResponse($response_params, $response_mode, $payment_method, $integration_type);
        $return_url = fn_url("payment_notification.notify?payment={$payment_method}&order_id={$order_id}", AREA, 'current');
        if ($integration_type == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
            echo "<html><body onLoad=\"javascript: window.top.location.href='" . $return_url . "'\"></body></html>";
        }
        else {
            echo "<html><body onLoad=\"javascript: self.location='" . $return_url . "'\"></body></html>";
        }
    }
}

function fn_payfort_fort_get_host_to_host_url()
{
    $url = fn_url("payment_notification.return?payment=payfort_fort", 'C', 'current');
    return $url;
}

function fn_payfort_fort_delete_old_order($payment_id)
{
    $payment_method_data = fn_get_payment_method_data($payment_id);
    $processor_script    = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $payment_method_data['processor_id']);
    if (in_array($processor_script, array('payfort_fort_cc.php', 'payfort_fort_sadad.php', 'payfort_fort_naps.php'))) {
        $cart = & $_SESSION['cart'];
        if (!empty($cart['failed_order_id']) || !empty($cart['processed_order_id'])) {
            $_order_ids               = !empty($cart['failed_order_id']) ? $cart['failed_order_id'] : $cart['processed_order_id'];
            /* $payfort_fort_settings = fn_get_payfort_fort_settings();
              $orderPlacement = $payfort_fort_settings['payment_settings']['order_placement'];
              if($orderPlacement == 'success'){
              foreach ($_order_ids as $_order_id) {
              fn_delete_order($_order_id);
              }
              } */
            $cart['rewrite_order_id'] = array();
            unset($cart['failed_order_id'], $cart['processed_order_id']);
        }
    }
}
