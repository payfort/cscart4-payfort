<?php

use Tygh\Http;
use Tygh\Registry;

function fn_payfort_fort_process_request($order_id, $order_info, $payment_method) {
    $payfort_fort_settings = fn_get_payfort_fort_settings();
    
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_payment_method_data($payment_id);
    
    $payfort_order_id = ($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id;
    $payfort_currency = CART_SECONDARY_CURRENCY;
    
    //$currency = fn_payfort_fort_get_valid_currency($processor_data['processor_params']['currency']);
    $is_sandbox = ($payfort_fort_settings['payment_settings']['mode'] == 'sandbox') ? TRUE : FALSE;
    if ($is_sandbox) {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_SANDBOX_HOST.'FortAPI/paymentPage';
    } else {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_HOST.'FortAPI/paymentPage';
    }
    
    $return_url = fn_payfort_fort_get_host_to_host_url();

    $post_data = array(
        'amount'                => fn_payfort_fort_convert_fort_amount($order_info['total'], $payfort_currency),
        'currency'              => strtoupper($payfort_currency),
        'merchant_identifier'   => $payfort_fort_settings['payment_settings']['merchant_identifier'],
        'access_code'           => $payfort_fort_settings['payment_settings']['access_code'],
        'merchant_reference'    => $payfort_order_id,
        'customer_email'        => $order_info['email'],
        //'customer_name'         => trim($order_info['b_firstname'].' '.$order_info['b_lastname']),
        'command'               => $payfort_fort_settings['payment_settings']['command'],
        'language'              => fn_payfort_fort_get_language(),
        'return_url'            => $return_url,
    );
    
    if($payment_method == 'payfort_fort_sadad') {
        $post_data['payment_option'] = 'SADAD';
    }
    elseif($payment_method == 'payfort_fort_naps') {
        $post_data['payment_option'] = 'NAPS';
        $post_data['order_description'] = $order_id;
    }
    $post_data['signature'] = fn_payfort_fort_calculate_signature($post_data, 'request');
    
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
    $integration_type = isset($processor_data['processor_params']['integration_type']) ? $processor_data['processor_params']['integration_type'] : '';
    if($payment_method == 'payfort_fort_cc' && $integration_type == 'merchantPage') {
        $mercahnt_page_date = fn_payfort_fort_get_merchant_page_data($order_id, $order_info);
        echo json_encode($mercahnt_page_date);
        exit;
    }
    else{
        fn_create_payment_form($gatewayUrl, $post_data, 'PAYFORT', false);
    }
}

function fn_payfort_fort_process_response($payment_method) {
    $fortParams = array_merge($_GET,$_POST);
    $order_id = $fortParams['merchant_reference'];
    if (fn_check_payment_script($payment_method.'.php', $order_id)) {

        fn_log_event('requests', 'http', array(
            'url'      => 'payfort_payment_page',
            'data'     => '',
            'response' => var_export($fortParams, true),
        ));

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);
        $order_info = fn_get_order_info($order_id);

        //validate payfort response
        $params = $fortParams;
        $signature = $fortParams['signature'];
        unset($params['dispatch']);
        unset($params['payment']);
        unset($params['signature']);
        unset($params['integration_type']);
        $trueSignature = fn_payfort_fort_calculate_signature($params, 'response');
        $success = true;
        $reason = '';
        
        if ($trueSignature != $signature){
            $success = false;
            $reason = 'Invalid signature.';
        }
        else {
            $response_code      = $params['response_code'];
            $response_message   = $params['response_message'];
            $status             = $params['status'];
            if (substr($response_code, 2) != '000'){
                $success = false;
                $reason = $response_message;
            }
        }
        $pp_response = array();
        if(!$success) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = "Declined: " . $reason;
        }
        else{
            $pp_response['order_status'] = 'P';
            $pp_response["transaction_id"] = $params['fort_id'];
        }
        fn_finish_payment($order_id, $pp_response);
        
        $return_url = fn_url("payment_notification.notify?payment={$payment_method}&order_id={$order_id}", AREA, 'current');
        $integration_type = isset($processor_data['processor_params']['integration_type']) ? $processor_data['processor_params']['integration_type'] : '';
        if($payment_method == 'payfort_fort_cc' && $integration_type == 'merchantPage') {
            echo "<html><body onLoad=\"javascript: window.top.location.href='" . $return_url . "'\"></body></html>";
        }
        else{
            echo "<html><body onLoad=\"javascript: self.location='" . $return_url . "'\"></body></html>";
        }
        exit;
    }
}

function fn_payfort_fort_get_merchant_page_data($order_id, $order_info) 
{
    $payfort_fort_settings = fn_get_payfort_fort_settings();
    $return_url = fn_payfort_fort_get_return_url("payment_notification.merchant_page_return?payment=payfort_fort&integration_type=merchant_page");
    $iframe_params = array(
        'merchant_identifier'   => $payfort_fort_settings['payment_settings']['merchant_identifier'],
        'access_code'           => $payfort_fort_settings['payment_settings']['access_code'],
        'merchant_reference'    => $order_id,
        'service_command'       => 'TOKENIZATION',
        'language'              => fn_payfort_fort_get_language(),
        'return_url'            => $return_url,
    );
    $iframe_params['signature'] = fn_payfort_fort_calculate_signature($iframe_params, 'request');
    
    $is_sandbox = ($payfort_fort_settings['payment_settings']['mode'] == 'sandbox') ? TRUE : FALSE;
    if ($is_sandbox) {
        $fort_url = PAYFORT_FORT_GATEWAY_SANDBOX_HOST.'FortAPI/paymentPage';
    } else {
        $fort_url = PAYFORT_FORT_GATEWAY_HOST.'FortAPI/paymentPage';
    }
    
    return array('url' => $fort_url, 'params' => $iframe_params);
}

function fn_payfort_fort_process_merchant_page_response($payment_method) {
    $fortParams = array_merge($_GET,$_POST);
    $order_id = $fortParams['merchant_reference'];
    if (fn_check_payment_script($payment_method.'.php', $order_id)) {

        fn_log_event('requests', 'http', array(
            'url'      => 'payfort_payment_page',
            'data'     => '',
            'response' => var_export($fortParams, true),
        ));

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);
        $order_info = fn_get_order_info($order_id);

        //validate payfort response
        $params = $fortParams;
        $signature = $fortParams['signature'];
        unset($params['dispatch']);
        unset($params['payment']);
        unset($params['signature']);
        unset($params['integration_type']);
        $trueSignature = fn_payfort_fort_calculate_signature($params, 'response');
        $success = true;
        $reason = '';
        
        if ($trueSignature != $signature){
            $success = false;
            $reason = 'Invalid signature.';
        }
        else {
            $response_code      = $params['response_code'];
            $response_message   = $params['response_message'];
            $status             = $params['status'];
            if (substr($response_code, 2) != '000'){
                $success = false;
                $reason = $response_message;
            }
            else{
                $success = true;
                $host2HostParams = fn_payfort_fort_merchant_page_notify_fort($order_id, $order_info, $fortParams);
                if(!$host2HostParams) {
                    $success = false;
                    $reason = 'Invalid response parameters.';
                }
                else {
                    $params = $host2HostParams;
                    $signature = $host2HostParams['signature'];

                    unset($params['dispatch']);
                    unset($params['payment']);
                    unset($params['signature']);
                    unset($params['integration_type']);
                    $trueSignature = fn_payfort_fort_calculate_signature($params, 'response');
                    if ($trueSignature != $signature){
                        $success = false;
                        $reason = 'Invalid signature.';
                    }
                    else{
                        $response_code      = $params['response_code'];
                        if($response_code == '20064' && isset($params['3ds_url'])) {
                            $success = true;
                            //header('location:'.$params['3ds_url']);
                            fn_redirect($params['3ds_url'], true);
                            exit;
                        }
                        else{
                            if (substr($response_code, 2) != '000'){
                                $success = false;
                                $reason = $host2HostParams['response_message'];
                            }
                        }
                    }
                }
            }
        }
        $pp_response = array();
        if(!$success) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = "Declined: " . $reason;
        }
        else{
            $pp_response['order_status'] = 'P';
        }
        fn_finish_payment($order_id, $pp_response);

        $return_url = fn_url("payment_notification.notify?payment={$payment_method}&order_id={$order_id}", AREA, 'current');
        $integration_type = isset($processor_data['processor_params']['integration_type']) ? $processor_data['processor_params']['integration_type'] : '';
        echo "<html><body onLoad=\"javascript: window.top.location.href='" . $return_url . "'\"></body></html>";
        exit;
    }
}

function fn_payfort_fort_merchant_page_notify_fort($order_id, $order_info, $fort_params) {
    //send host to host

    $payfort_fort_settings = fn_get_payfort_fort_settings();
    $payfort_order_id = ($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id;
    $payfort_currency = CART_SECONDARY_CURRENCY;
    
    $is_sandbox = ($payfort_fort_settings['payment_settings']['mode'] == 'sandbox') ? TRUE : FALSE;
    if ($is_sandbox) {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_SANDBOX_HOST.'FortAPI/paymentPage';
    } else {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_HOST.'FortAPI/paymentPage';
    }
    
    $return_url = fn_payfort_fort_get_host_to_host_url();
    
    $ip = fn_get_ip();
    $postData = array(
        'merchant_reference'    => $payfort_order_id,
        'access_code'           => $payfort_fort_settings['payment_settings']['access_code'],
        'command'               => $payfort_fort_settings['payment_settings']['command'],
        'merchant_identifier'   => $payfort_fort_settings['payment_settings']['merchant_identifier'],
        'customer_ip'           => $ip['host'],
        'amount'                => fn_payfort_fort_convert_fort_amount($order_info['total'], $payfort_currency),
        'currency'              => strtoupper($payfort_currency),
        'customer_email'        => $order_info['email'],
        'customer_name'         => trim($order_info['b_firstname'].' '.$order_info['b_lastname']),
        'token_name'            => $fort_params['token_name'],
        'language'              => fn_payfort_fort_get_language(),
        'return_url'            => $return_url,
    );
    
    //calculate request signature
    $signature = fn_payfort_fort_calculate_signature($postData, 'request');
    $postData['signature'] = $signature;

    $is_sandbox = ($payfort_fort_settings['payment_settings']['mode'] == 'sandbox') ? TRUE : FALSE;
    if ($is_sandbox) {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_SANDBOX_HOST.'FortAPI/paymentApi';
    } else {
        $gatewayUrl = PAYFORT_FORT_GATEWAY_HOST.'FortAPI/paymentApi';
    }
    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0";
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json;charset=UTF-8',
            //'Accept: application/json, application/*+json',
            //'Connection:keep-alive'
    ));
    curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects		
    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
    //curl_setopt($ch, CURLOPT_TIMEOUT, Yii::app()->params['apiCallTimeout']); // timeout in seconds
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);

    //$response_data = array();

    //parse_str($response, $response_data);
    curl_close($ch);

    $array_result    = json_decode($response, true);

    fn_log_event('requests', 'http', array(
        'url' => $gatewayUrl,
        'data' => var_export($postData, true),
        'response' => var_export($array_result, true),
    ));
    
    if(!$response || empty($array_result)) {
        return false;
    }
    return $array_result;
}

/**
 * calculate fort signature
 * @param array $arr_data
 * @param sting $sign_type request or response
 * @return string fort signature
 */
function fn_payfort_fort_calculate_signature($arr_data, $sign_type = 'request')
{
    $payfort_fort_settings = fn_get_payfort_fort_settings();
    $shaString = '';

    ksort($arr_data);
    foreach ($arr_data as $k=>$v){
        $shaString .= "$k=$v";
    }

    if($sign_type == 'request') {
        $shaString = $payfort_fort_settings['payment_settings']['sha_in_pass_phrase'] . $shaString . $payfort_fort_settings['payment_settings']['sha_in_pass_phrase'];
    }
    else{
        $shaString = $payfort_fort_settings['payment_settings']['sha_out_pass_phrase'] . $shaString . $payfort_fort_settings['payment_settings']['sha_out_pass_phrase'];
    }
    $signature = hash($payfort_fort_settings['payment_settings']['hash_algorithm'] ,$shaString);

    return $signature;
}

/**
 * Convert Amount with dicemal points
 * @param decimal $amount
 * @param string  $currency_code
 * @return decimal
*/
function fn_payfort_fort_convert_fort_amount($amount, $currency_code)
{
    $new_amount = 0;
    
    $total = fn_format_price_by_currency($amount, CART_PRIMARY_CURRENCY, CART_SECONDARY_CURRENCY);
    
    $arr_currency_data  = fn_get_currencies_list(array('currency_code' => $currency_code));
    $currency_data      = $arr_currency_data[$currency_code];
    $decimal_points     = $currency_data['decimals'];
    
    $new_amount = $total * (pow(10, $decimal_points));
    //$new_amount = round($total) * (pow(10, $decimal_points));
    return $new_amount;
}

function fn_payfort_fort_get_host_to_host_url()
{
    $url = fn_url("payment_notification.return?payment=payfort_fort", 'C', 'current');
    return $url;
}

function fn_payfort_fort_get_return_url($url) 
{
    $return_url = fn_url($url, AREA, 'current');
    return $return_url;
}

function fn_payfort_fort_get_language() 
{
    $payfort_fort_settings = fn_get_payfort_fort_settings();
    $language = $payfort_fort_settings['payment_settings']['language'];
    if($language == 'store') {
        $language = CART_LANGUAGE;
    }
    if(substr($language, 0, 2) == 'ar') {
        $language = 'ar';
    }
    else{
        $language = 'en';
    }
    return $language;
}