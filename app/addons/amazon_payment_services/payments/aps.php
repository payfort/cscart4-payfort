<?php
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//if ($mode == 'place_order') {
    setcookie(Tygh::$app['session']->getName(), Tygh::$app['session']->getId(), [
           'expires'  => time()+3600,
           'path'     => ini_get('session.cookie_path'),
           'domain'   => ini_get('session.cookie_domain'),
           'samesite' => 'None',
           'secure'   => true,
           'httponly' => ini_get('session.cookie_httponly')
        ]);

    $success = $error = false;

    $pf_order_id = (($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id);

    $params = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

    $payment_data = isset($params['payment_data']['aps']) ? $params['payment_data']['aps'] : [];
    
    fn_delete_notification('profile_updated');

    if( !empty($processor_data['processor_params']) && !empty($payment_data['gateway']) ){

        $aps = new \AmazonPaymentServices\APS($processor_data['processor_params']);

        $cart = &Tygh::$app['session']['cart'];

        $type = trim(!empty($payment_data['target_gateway']) ? $payment_data['target_gateway'] : $payment_data['gateway']);

        $gateway = $aps->loadGateway($type,$cart);
 
        if( $gateway ){

            $gateway->setOrderId($pf_order_id);
            $gateway->setOrderDetails($order_info);
              
            if( !empty($payment_data['merchant_reference']) )
                $gateway->setReference($payment_data['merchant_reference']);  
                
            $gateway->setUserId($order_info['user_id']);
            $gateway->setCustomerInfo([
                'customer_ip' => $order_info['ip_address'],
                'customer_email' => trim(strtolower($order_info['email'])),
                'customer_name' => trim(!empty($order_info['b_firstname']) ? $order_info['b_firstname'].' '.$order_info['b_lastname'] : $order_info['firstname'].' '.$order_info['lastname']),
                'phone_number' => trim(!empty($order_info['b_phone']) ? $order_info['b_phone'] : $order_info['phone']),
            ]);
    
            if( !empty($order_info['secondary_currency']) )
                $gateway->setCurrency($order_info['secondary_currency']);
            $gateway->setAmount($order_info['total']);

            if( !empty($payment_data[$type]) )
                $gateway->setPaymentData($payment_data[$type]);

            if( !empty($payment_data['gateway_linked']) && !empty($payment_data[$payment_data['gateway_linked']]) )
                $gateway->setPaymentData($payment_data[$payment_data['gateway_linked']]);

            if( !empty($payment_data['action']) )
                $gateway->action = trim($payment_data['action']);

            $response = $gateway->processRequest();

            $response = $gateway->processActions($response);
            
            if( $response['error'] && $response['is_json'] ){
                if( !empty($_REQUEST['is_ajax']) )                
                    Tygh::$app['ajax']->assign('success', false);
                else {
                    header("Content-type: application/json");
                    echo json_encode($response);
                }
            }
            
            // update order payment details before payment
            if( !empty($response['details']) ){

                if( !empty($response['details']['merchant_reference']) ){
                    db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i",[
                        'aps_reference' => trim($response['details']['merchant_reference']),
                        'aps_gateway'   => $gateway->type,
                    ], $order_id);
                }
                
                fn_update_order_payment_info($order_id, $response['details']);
            }
            
            if( $response['success'] ){
        
                // save user session for order incase of clear in return.
                fn_amazon_payment_service_handle_user_session($order_id,'S');

                if( $response['is_json'] ){        
                    unset($response['details']);
                    if( !empty($_REQUEST['is_ajax']) ){
                        Tygh::$app['ajax']->assign('success', true);
                        Tygh::$app['ajax']->assign('payment_id', (int)$order_info['payment_id']);
                        Tygh::$app['ajax']->assign('checkout_url', $gateway->getCheckoutUrl());
                        Tygh::$app['ajax']->assign('params', $response['params']);

                    } else {
                        
                        if( $response['details']['status'] == 'P' && empty($response['redirect_url']) )
                            $response['redirect_url'] = fn_url("checkout.complete?order_id=".$order_id);

                        header("Content-type: application/json");
                        echo json_encode($response);
                    }

                } else if( $response['is_redirect'] ){
                    header("Location: ".$response['redirect_url']);
                
                } else if( $response['is_form'] ){
                    $gateway->log("Form Request",[
                        'Request Url'=>$gateway->getCheckoutUrl(),
                        'Params'=>$response['params']
                    ]);

                    fn_create_payment_form($gateway->getCheckoutUrl(), $response['params'], 'Amazon Payment Services', false);
                }
                
            } else 
                $error = $response['error'];

            if( isset($response['is_direct']) && $response['is_direct'] ){

                if( $response['success'] && empty($response['details']['order_status']) )
                    $response['details']['order_status'] = 'P';
                
                // finish payment and redirect
                if( !empty($response['details']) )
                    fn_finish_payment($order_id,$response['details']);

                if( !$response['is_json'] )
                    fn_order_placement_routines('route', $order_id,false);

                exit;
            }

            if( $response['success'] || $response['is_json'])
                exit;
        } else
            $error = __("aps_gateway_disabled_invalid");
    
    } else
        $error = __("aps_invalid_request");
    
    if( $error )
        fn_set_notification("E",__("error"),$error);
    
    fn_redirect(fn_url('checkout.checkout'));
   
    exit;
//}