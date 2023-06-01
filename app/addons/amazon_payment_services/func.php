<?php
/***************************************************************************
*                                                                          *
*   (c) 2021 Amazon Payment Services											   *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Storage;

// HOOKS

function fn_amazon_payment_services_get_order_info(&$order, $additional_data){

   if( Registry::get('runtime.controller') == 'orders' && Registry::get('runtime.mode') == 'details' && AREA == 'A' )
      return ;

   if( !empty($order['payment_method']['template']) && !empty($order['payment_info']['gateway']) ){
      if( strpos($order['payment_method']['template'],'aps.tpl') !== false )
         $order['payment_method']['payment'] = $order['payment_method']['payment']. ' - '.$order['payment_info']['gateway'];
   }
}

function fn_amazon_payment_services_order_placement_routines( $order_id, $force_notification, $order_info, $_error, $redirect_url, $allow_external_redirect){
   if( !empty($_REQUEST['aps_iframe_redirect']) ){
      echo '<center>'.__("aps_processing").' ...</center><script>window.top.location.href = "'.fn_url($redirect_url).'"</script>';
      exit;
   }
}

function fn_amazon_payment_services_prepare_checkout_payment_methods($cart, $auth, &$payment_groups){

   foreach( $payment_groups as $group_id => &$methods ){
      foreach($methods as $payment_id => &$method ){

         if( !empty($method['processor_script']) && $method['processor_script'] == 'aps.php' ){
            $aps = new \AmazonPaymentServices\APS($method['processor_params']);
            
            $gateways = $aps->getGatewayList($cart);
            if( !empty($gateways) ){
               if( isset($gateways['cc']) && isset($gateways['installments']['object']->integration_type) ){
                  if( $gateways['installments']['object']->integration_type == 'embedded_hosted_checkout' ){
                     if( $gateways['cc']['object']->integration_type == 'hosted_checkout' )
                        $gateways['cc']['object']->link = $gateways['installments']['object'];               
                     unset($gateways['installments']);                    
                  }
               }
            }
               
            $method['gateways'] = $gateways;
         }
      }
   }

}

function fn_amazon_payment_services_update_payment_pre(&$payment_data, $payment_id, $lang_code, $certificate_file, $certificates_dir, $can_remove_offline_payment_params){

   if( isset($payment_data['processor_params']['cc_show_mada_branding']) ){

      $gw_certificates = fn_filter_uploaded_data('payment_gw_certi_files');   
      $file_dir = $payment_id ? $payment_id : time();
      fn_mkdir($certificates_dir . $file_dir);
            
      foreach($gw_certificates as $field => $file){
         if( !empty($file['name']) && !empty($file['path']) ){

            $filename = $file_dir . '/' . $file['name'];
            if( fn_copy($file['path'], $certificates_dir . $filename) )
               $payment_data['processor_params'][$field] = $filename;
         }
      }

   }
}

// CORE FUNCTIONS

function fn_amazon_payment_services_check_applypay_button($page,$process = true){

   $show = false;

   $payment_info = db_get_row("SELECT pm.payment_id, pm.processor_params FROM ?:payments pm, ?:payment_processors pp WHERE pp.processor_id = pm.processor_id AND pm.status = 'A' AND pp.processor_script = ?s ORDER BY pm.payment_id DESC",'aps.php');
   
   if( !empty($payment_info['processor_params']) ){
      
      $params = (array)unserialize($payment_info['processor_params']);
      if( isset($params['apple_enabled']) && $params['apple_enabled'] == 'Y' && $params['apple_enabled_on_'.$page] == 'Y' ){
      
         $payment_info['processor_params'] = $params;

         $merchant_identifier = '';
         if( !empty($params['apple_certificate_file']) ){
            $certi_apth = DIR_ROOT.'/var/certificates/'.$params['apple_certificate_file'];
            $merchant_identifier = openssl_x509_parse(file_get_contents($certi_apth))['subject']['UID'];
         }

         $payment_info['merchant_identifier'] = $merchant_identifier;

         if( $page == 'cart' && $process){
            $cart = Tygh::$app['session']['cart'];
            $auth = Tygh::$app['session']['auth'];
            
            if( !empty($cart['products']) ){

               $shipping_calculation_type = fn_checkout_get_shippping_calculation_type($cart,true);
               
               list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F');
               
               if( !empty($cart['total']) ){

                  $currency = CART_SECONDARY_CURRENCY;
                  $cart_subtotal = number_format($cart['subtotal'],2,'.','');
                  $shipping_cost = number_format($cart['shipping_cost'],2,'.','');
                  $tax_subtotal = number_format($cart['tax_subtotal'],2,'.','');
                  $cart_total = number_format($cart['total'],2,'.','');
                  $discount = number_format($cart['discount'],2,'.','');

                  $extra = [];
                  $extra['running_total'] = number_format($cart_subtotal+$tax_subtotal-$discount,2,'.','');
                  $extra['shippings'] = [];
                  $extra['shipping_cost'] = $shipping_cost;

                  if( empty($cart['chosen_shipping']) ) $cart['chosen_shipping'] = [];
                  
                  if( !empty($product_groups[0]['shippings']) ){
                     foreach($product_groups[0]['shippings'] as $shp){
                        if( in_array($shp['shipping_id'],$cart['chosen_shipping']) )
                           $extra['identifier'] = 'si_'.(int)$shp['shipping_id'];
                        $extra['shippings'][] = [
                           'label' => trim($shp['shipping']), 
                           'amount' => number_format($shp['rate'],2,'.',''),
                           'detail' => trim($shp['delivery_time']), 
                           'identifier'=> 'si_'.(int)$shp['shipping_id'],
                        ];
                     }
                  }

                  $lineItems = [];
                  $lineItems['subtotal'] = ["type"=>'final', "label"=>__('subtotal'), "amount"=>$cart_subtotal];

                  if( $shipping_cost > 0 )
                     $lineItems['shipping'] = ["type"=>'final', "label"=>__('shipping_cost'), "amount"=>number_format($shipping_cost,2,'.','')];
                  if( $discount > 0 )
                     $lineItems['discount'] = ["type"=>'final', "label"=>__('discount'), "amount"=>number_format($discount,2,'.','')];
                  if( $tax_subtotal > 0 )
                     $lineItems['tax'] = ["type"=>'final', "label"=>__('tax'), "amount"=>$tax_subtotal];

                  $data = [
                      "countryCode" => isset($cart['user_data']['s_country']) ? $cart['user_data']['s_country'] : 'AE',
                      "currencyCode" => $currency,
                      "merchantCapabilities" => ['supports3DS'],
                      "supportedNetworks" => !empty($params['apple_supported_networks']) ? array_keys((array)$params['apple_supported_networks']) : [],
                      "lineItems" => $lineItems,
                      'requiredShippingContactFields' =>['postalAddress', 'name', 'email','phone'],
                      "total" => [
                          "label"=> trim($params['apple_display_name']),
                         // 'type' => 'final',
                          "amount"=> $cart_total
                      ],
                  ];

                  $payment_info['data'] = [
                     'params' => $data,
                     'extra'  => $extra
                  ];

               } else
                  $payment_info =  false;
            } else 
               $payment_info =  false;
         }

         $show = $payment_info;
         
      }
   }

   return $show;
}

function fn_amazon_payment_services_handle_response($mode,$order_id,$gateway,$user_id,$response){

   $error = false;
   $redirect_url = 'checkout.checkout';

   if( empty($order_id) && isset($response['merchant_extra1']) )
      $order_id = $response['merchant_extra1'];

   if( empty($gateway) && isset($response['merchant_extra3']) )
      $gateway = $response['merchant_extra3'];
   
   if( empty($user_id) && isset($response['merchant_extra2']) )
      $user_id = $response['merchant_extra2'];

   if( empty($order_id) && !empty($response['merchant_reference']) ){
      $order = db_get_row("SELECT order_id,user_id,aps_gateway FROM ?:orders WHERE aps_reference = ?s",$response['merchant_reference']);
      if( !empty($order['order_id']) ){
         $order_id = $order['order_id'];
         $user_id  = $order['user_id'];
         $gateway  = $order['aps_gateway'];
      }
   }

   if( !empty($order_id) && !empty($gateway) ){

      $order_info = fn_get_order_info($order_id);
      if( fn_check_payment_script('aps.php',$order_id) && !empty($order_info['payment_method']['processor_params']) ) {
         
         $alreadyPaid = false;
         $paid_statuses = fn_get_order_paid_statuses();

         // check order is paid or not
         if( isset($order_info['is_parent_order']) && $order_info['is_parent_order'] == 'Y' ){
            $orders = db_get_array("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i AND status IN (?a) AND is_parent_order !='Y'",$order_id,$paid_statuses);
            if( !empty($orders) )
               $alreadyPaid = true;
         } else {
            if( in_array($order_info['status'],$paid_statuses) )
               $alreadyPaid = true;
         }

         if( $alreadyPaid && $mode == 'return' ){ 
              fn_set_notification('N',__('notice'),__("aps_order_placed"));
              $redirect_url = "orders.details?order_id=".$order_id;
         
         } else {
               
            // restore user session to use in return request if cleared by cscart
            if( $mode == 'return' )
               fn_amazon_payment_service_handle_user_session($order_id,'R');
         
            // init APS
            $aps = new \AmazonPaymentServices\APS($order_info['payment_method']['processor_params']);
            
            // load selected gateway
            $gateway = $aps->loadGateway($gateway,null,true);

            if( $gateway ){
                  
               // set order data
               $gateway->setOrderId($order_id);
               $gateway->setOrderDetails($order_info);               
               if( !empty($order_info['payment_method']['payment_id']) )
                  $gateway->setPaymentId((int)$order_info['payment_method']['payment_id']);

               // set user id and info
               $gateway->setUserId($user_id);
               $gateway->setCustomerInfo([
                  'customer_ip' => $order_info['ip_address'],
                  'customer_email' => trim(strtolower($order_info['email'])),
                  'customer_name' => trim(!empty($order_info['b_firstname']) ? $order_info['b_firstname'].' '.$order_info['b_lastname'] : $order_info['firstname'].' '.$order_info['lastname']),
                  'phone_number' => trim(!empty($order_info['b_phone']) ? $order_info['b_phone'] : $order_info['phone']),
               ]);
               $gateway->setMode($mode);

               // set currency and amount
               if( isset($order_info['secondary_currency']) )
                  $gateway->setCurrency($order_info['secondary_currency']);
               $gateway->setAmount($order_info['total']);

               // unset extra GET parameters
               unset($response['dispatch'],$response['order_id'],$response['gateway'],$response['user_id']);
               
               // set response parameter
               $gateway->setResponse($response);

               // handle return response from payfort
               $res = $gateway->processResponse();

               if( $res['success'] && $mode == 'return' )
                  fn_amazon_payment_service_handle_user_session($order_id,'D');

               if( $gateway->isJson )
                  $_REQUEST['aps_iframe_redirect'] = true;

               if( $mode == 'notify' ){

                  // update payment on notifiy
                  fn_update_order_payment_info($order_id, $res['details']);

                  if( !empty($res['details']['order_status']) ) 
                     fn_change_order_status($order_id, $res['details']['order_status']);
                     
                  echo 'NOTIFIED';

               } else {

                  // finish payment and redirect
                  fn_finish_payment($order_id, $res['details']);

                  fn_order_placement_routines('route', $order_id,false);
               }

               exit;
               
            } else
               $error = __("aps_gateway_disabled_invalid");
         }
      } else
         $error = __("aps_invalid_order");

   } else
      $error = __("aps_invalid_request");

   if( $mode == 'return' ){
      
      if( $error )
         fn_set_notification("E",__("error"),$error);
      fn_redirect(fn_url($redirect_url));

   } else
      echo "Error: ".$error;

   exit;
}

function fn_amazon_payment_services_delete_card($card){
   $error = false;

   if( empty($card) )
      $error = "Invalid Request";
   else {

      $processor_params = db_get_field("SELECT processor_params FROM ?:payments WHERE payment_id =?i",$card['payment_id']);
      $gateway = new \AmazonPaymentServices\Gateways\Gateway('',$processor_params);

      $params = [
         'service_command' => 'UPDATE_TOKEN',
         'access_code' => $gateway->getConfig('access_code'),
         'merchant_identifier' => $gateway->getConfig('merchant_identifier'),
         'merchant_reference' => $gateway->generateReference(),
         'language' => $gateway->getLanguage(),
         'token_name' => trim($card['token_name']),
         'token_status' => 'INACTIVE',
      ];

      $params['signature'] = $gateway->generateSignature($params);

      $resp = $gateway->httpCall('POST',$params);

      if( !$error ){

         if( $card['default'] == 'Y' ){

            $last_card_id = (int)db_get_field("SELECT card_id FROM ?:aps_card_tokens WHERE user_id = ?i AND card_id != ?i ORDER BY timestamp DESC",$card['user_id'],$card['card_id']);

            if( $last_card_id )
               db_query("UPDATE ?:aps_card_tokens SET ?u WHERE card_id = ?i",['default'=>'Y'],$last_card_id);
         }

         db_query("DELETE FROM ?:aps_card_tokens WHERE card_id = ?i",$card['card_id']);
      }
   }

   return $error;
}

function fn_amazon_payment_services_order_actions($order_id, $action, $amount=0){

   $success = $error = false;
   $order_info = fn_get_order_info($order_id, false, true, true, false);
        
   if( fn_check_payment_script('aps.php',$order_id) && in_array($action,['void','refund','capture']) ){

      $payment_info = !empty($order_info['payment_info']) ? $order_info['payment_info'] : [];
      $processor_params = isset($order_info['payment_method']['processor_params']) ? $order_info['payment_method']['processor_params'] : '';

      $type = trim(!empty($order_info['aps_gateway']) ? $order_info['aps_gateway'] : '');

      $gateway = new \AmazonPaymentServices\Gateways\Gateway($payment_info['gateway'],$processor_params);

      $pamount = floatval($payment_info['amount']);
      $done_amount = 0;
      if( $action == 'void'){
         $command = 'VOID_AUTHORIZATION';
         $payment_info['order_status'] = 'I';
      }

      else if( $action == 'refund'){

         if( in_array($type,['valu','naps'])  ){
            if( $amount < floatval($payment_info['amount']) )
               $error = __("aps_partial_refund_not_available");
         }

         if( !$error ){
            if( !empty($payment_info['refunded_amount']) )
               $done_amount = floatval($payment_info['refunded_amount']);
            
            if( $amount > floatval(isset($payment_info['captured_amount']) ? $payment_info['captured_amount'] : $payment_info['amount']) )
               $error = __("aps_refund_amount_greater");

            $payment_info['refunded_amount'] = number_format($done_amount+$amount,2,'.','');
            $command = 'REFUND';
         }

      } else {

         if( !empty($payment_info['captured_amount']) )
            $done_amount = floatval($payment_info['captured_amount']);

         $payment_info['captured_amount'] = $done_amount+$amount;
          
         $payment_info['captured_amount'] = number_format($payment_info['captured_amount'],2,'.','');

         $command = 'CAPTURE';

         $payment_info['order_status'] = 'P';
      }

      if( !$error ){

         $params = [
            'command' => $command,
            'access_code' => $gateway->getConfig('access_code'),
            'merchant_identifier' => $gateway->getConfig('merchant_identifier'),
            'language' => $gateway->getLanguage(),
         ];

         if( !empty($payment_info['merchant_reference']) )
            $params['merchant_reference'] = trim($payment_info['merchant_reference']);
         
         if( !empty($payment_info['fort_id']) )
            $params['fort_id'] = trim($payment_info['fort_id']);

         if( $action != 'void' ){
            $params['amount'] = number_format($amount*100,'0','.','');
            if( !empty($payment_info['currency']) )
               $params['currency'] = trim($payment_info['currency']);
         }

         $params['signature'] = $gateway->generateSignature($params);

         $resp = $gateway->httpCall('POST',$params);

         if( !empty($resp['error']) )
            $error = $resp['error'];
         else {
         
            if( isset($resp['response_message']) && $resp['response_message'] == 'Success' ){

               if( $action == 'void' )
                  fn_change_order_status($order_id, 'I');

               $success = true;

            } else
               $error = isset($resp['response_message']) ? $resp['response_message'] : 'Unable to process request';
         }
      }
   }

   if( !$success && !$error )
      $error = "Invalid request";
   
   if( $success ){
      
      if( $action == 'void' || $pamount == ($amount+$done_amount) )
         $payment_info['payment_mode'] = $command; 

      fn_update_order_payment_info($order_id, $payment_info);
   }

   return [$success,$error];
}

function fn_amazon_payment_services_ajax_action_call($request,$cart = null){
   
   $payment_id = (int)$request['payment_id'];

   $payment_data = isset($request['payment_data']['aps']) ? $request['payment_data']['aps'] : [];
   $cart = isset($cart) ? $cart : Tygh::$app['session']['cart'];

   $type = isset($payment_data['gateway']) ? $payment_data['gateway'] : '';
   $response = ['success' => false, 'error' => false];

   // init APS
   $aps = new \AmazonPaymentServices\APS($payment_id);
               
   // load selected gateway
   $gateway = $aps->loadGateway($type,$cart);

   if( $gateway ){
               
         // set user id and info
         $gateway->setUserId(Tygh::$app['session']['auth']['user_id']);
         $gateway->setCustomerInfo([
          //  'customer_ip' => $order_info['ip_address'],
            'customer_email' => !empty($cart['user_data']['email']) ? trim(strtolower($cart['user_data']['email'])) : '',
            'customer_name' => trim(!empty($cart['user_data']['b_firstname']) ? $cart['user_data']['b_firstname'].' '.$cart['user_data']['b_lastname'] : $cart['user_data']['firstname'].' '.$cart['user_data']['lastname']),
            'phone_number' => trim(!empty($cart['user_data']['b_phone']) ? $cart['user_data']['b_phone'] : $cart['user_data']['phone']),
         ]);
         
         if( !empty($payment_data[$type]) )
            $gateway->setPaymentData($payment_data[$type]);

         if( !empty($payment_data['gateway_linked']) && !empty($payment_data[$payment_data['gateway_linked']]) )
            $gateway->setPaymentData($payment_data[$payment_data['gateway_linked']]);

         if( !empty($_REQUEST['action']) )
            $gateway->action = trim($_REQUEST['action']);

         $response = $gateway->processRequest();

         $response = $gateway->processActions($response);

   } else
      $response['error'] = __("aps_gateway_disabled_invalid");

   header("Content-Type: application/json");
   echo json_encode($response);
   exit;    
}

function fn_amazon_payment_services_cron_handler(){

   $done = 0;

   $pending_orders = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = 0 AND status IN ('O','N') AND TRIM(aps_reference) != '' AND TRIM(aps_gateway) != '' ORDER BY order_id DESC");

   foreach($pending_orders as $order_id){
      
      $order_info = fn_get_order_info($order_id, false, true, true, false);
      
      if( !empty($order_info['order_id']) && fn_check_payment_script('aps.php',$order_id) ){
         fn_amazon_payment_services_update_order_status($order_info);
         $done++;
      }

   }

   echo "DONE: ".$done;
}

function fn_amazon_payment_services_update_order_status($order_info){

   $gateway = new \AmazonPaymentServices\Gateways\Gateway('',isset($order_info['payment_method']['processor_params']) ? $order_info['payment_method']['processor_params'] : '');

   if( $gateway ){

      $gateway->setOrderDetails($order_info);
      $gateway->type = $order_info['aps_gateway'];
      
      $details = $gateway->checkUpdateOrder($order_info['aps_gateway'],$order_info['aps_reference'], isset($order_info['payment_info']) ? $order_info['payment_info'] : []);

      fn_update_order_payment_info($order_info['order_id'],$details);

      if( !empty($details['order_status']) ) 
         fn_change_order_status($order_info['order_id'],$details['order_status']);         
   }
   
}

function fn_amazon_payment_service_handle_user_session($order_id,$action){
   
   $cart = & Tygh::$app['session']['cart'];
   $auth = & Tygh::$app['session']['auth'];
   $order_id = (int)$order_id;
   
   if( ( $action == 'D' || ( $action == 'S' && !empty($cart['products']) ) OR ( $action == 'R' && empty($cart['products']) ) ) && !empty($order_id) ){

      $dir_path = DIR_ROOT.'/var/cache/user_data';

      if( !is_dir($dir_path) )
         mkdir($dir_path,0777,true);

      $cache_file = $dir_path.'/'.$order_id.'.json';

      if( $action == 'S' )
         file_put_contents($cache_file,json_encode(['cart'=>$cart,'auth'=>$auth]));
      
      if( $action == 'R' || $action == 'D' ){
         if( file_exists($cache_file) ){
            
            $_data = filter_var(file_get_contents($cache_file),FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $_data = json_decode($_data,true);
            
            if( $_data ){
               if( $action != 'D')
                  $cart = $_data['cart'];
               $auth = $_data['auth'];
            }
         }
      }

      if( $action == 'D' ){
         if( file_exists($cache_file) )
            unlink($cache_file);
      }
   }

}

function fn_amazon_payment_services_get_configuration_fields(){

   $list = [

      __('aps_merchant_configuration') =>[
         'merchant_identifier' => [
            'label'=> __('aps_merchant_identifier'),
            'type' => 'text', 
         ],
         'access_code' => [
            'label'=> __('aps_access_code'),
            'type' => 'text', 
         ],
         'request_sha_phrase' => [
            'label'=> __('aps_request_sha_phrase'),
            'type' => 'text', 
         ],
         'response_sha_phrase' => [
            'label'=> __('aps_response_sha_phrase'),
            'type' => 'text', 
         ],
      ],
      
      __('aps_global_configuration') =>[
         'sandbox_mode' => [
            'label'=> __('aps_sandbox_mode'),
            'type' => 'checkbox',
         ],
         'command' => [
            'label'=> __('aps_command'),
            'type' => 'dropdown',
            'values'=> ['AUTHORIZATION'=>__('aps_authorization'),'PURCHASE'=>__('aps_purchase')],
            'default' => 'AUTHORIZATION',
         ],
         'sha_type' => [
            'label'=> __('aps_sha_type'),
            'type' => 'dropdown',
            'values'=> ['sha256'=>'SHA-256','sha512'=>'SHA-512','hmac256'=>'HMAC-256','hmac512'=>'HMAC-512'],
         ],
         'currency' => [
            'label'=> __('aps_currency'),
            'type' => 'dropdown',
            'values'=> ['B'=>__('aps_base_currency',['[currency]'=>CART_PRIMARY_CURRENCY]),'F'=>__('aps_front_currency')],
            'default' => 'B',
         ],
         'enable_tokenization' => [
            'label'=> __('aps_enable_tokenization'),
            'type' => 'checkbox',
            'default' => true,
         ],
         'debug_mode' => [
            'label'=> __('aps_debug_mode'),
            'type' => 'checkbox',
         ],
         'hth_url' => [
            'label'=> __('aps_host_to_host_uRL'),
            'type' => 'html',
            'html' => fn_url('amazon_payment_services.notify','C'),
         ],
         'log_url' => [
            'label'=> __('aps_logs'),
            'type' => 'html',
            'html' => '<a href="'.fn_url('amazon_payment_services.logs').'" target="_blank">'.__('aps_logs_link_text').'</a>',
         ],
         'cron_url' => [
            'label'=> "Cronjob Url",
            'type' => 'html',
            'html' => '<b>Use the following commands in order to run CRON job on your server (1 - we recommend):</b><br>
               1) php '.DIR_ROOT.'/index.php --dispatch=amazon_payment_services.cron --cron_key=aps<br>
               2) wget -q "'.fn_url('amazon_payment_services.cron?cron_key=aps','C').'"<br>
               3) curl "'.fn_url('amazon_payment_services.cron&cron_key=aps','C').'"',
         ],
      ],
      
      __('aps_credit_debit_card') =>[
         'cc_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
         'cc_integration_type' => [
            'label'=> __('aps_integration_type'),
            'type' => 'dropdown',
            'values'=> [
               'redirection'=>__('aps_redirection'),
               'standard_checkout'=>__('aps_standard_checkout'),
               'hosted_checkout'=>__('aps_hosted_checkout')
            ],
            'default' => 'standard_checkout',
         ],
         'cc_show_mada_branding' => [
            'label'=> __('aps_show_mada_branding'),
            'type' => 'checkbox',
            'hint' => __('aps_show_mada_branding_during_checkout'),
         ],
         'cc_mada_bins' => [
            'label'=> __('aps_mada_bins'),
            'type' => 'textarea',
            'default' => '440647|440795|446404|457865|968208|457997|474491|636120|417633|468540|468541|468542|468543|968201|446393|409201|458456|484783|462220|455708|410621|455036|486094|486095|486096|504300|440533|489318|489319|445564|968211|410685|406996|432328|428671|428672|428673|968206|446672|543357|434107|407197|407395|412565|431361|604906|521076|529415|535825|543085|524130|554180|549760|968209|524514|529741|537767|535989|536023|513213|520058|558563|588982|589005|531095|530906|532013|968204|422817|422818|422819|428331|483010|483011|483012|589206|968207|419593|439954|530060|531196|420132',
            'hint' => __('aps_bins_hint',['[email]'=>'integration-ps@amazon.com']),
         ],
         'cc_show_meeza_branding' => [
            'label'=> __('aps_show_meeza_branding'),
            'type' => 'checkbox',
            'hint' => __('aps_show_meeza_branding_during_checkout'),
         ], 
         'cc_meeza_bins' => [
            'label'=> __('aps_meeza_bins'),
            'type' => 'textarea',
            'default' => '507803[0-6][0-9]|507808[3-9][0-9]|507809[0-9][0-9]|507810[0-2][0-9]',
            'hint' => __('aps_bins_hint',['[email]'=>'integration-ps@amazon.com']),
         ],
      ],

      'Apple Pay' =>[
         'apple_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
         'apple_enabled_on_product' => [
            'label'=> __('aps_enabled_apple_pay_product_page'),
            'type' => 'checkbox',
         ],
         'apple_enabled_on_cart' => [
            'label'=> __('aps_enabled_apple_pay_cart_page'),
            'type' => 'checkbox',
         ],
         'apple_button_type' => [
            'label'=> __('aps_apple_button_types'),
            'type' => 'dropdown',
            'values'=> [
               'apple-pay-buy' => __('aps_button_BUY'),
               'apple-pay-donate' => __('aps_button_DONATE'),
               'apple-pay-plain' => __('aps_button_PLAIN'),
               'apple-pay-set-up' => __('aps_button_SETUP'),
               'apple-pay-book' => __('aps_button_BOOK'),
               'apple-pay-check-out' => __('aps_button_CHECKOUT'),
               'apple-pay-subscribe' => __('aps_button_SUBSCRIBE'),
               'apple-pay-add-money' => __('aps_button_ADDMONEY'),
               'apple-pay-contribute' => __('aps_button_CONTRIBUTE'),
               'apple-pay-order' => __('aps_button_ORDER'),
               'apple-pay-reload' => __('aps_button_RELOAD'),
               'apple-pay-rent' => __('aps_button_RENT'),
               'apple-pay-support' => __('aps_button_SUPPORT'),
               'apple-pay-tip' => __('aps_button_TIP'),
               'apple-pay-top-up' => __('aps_button_TOPUP'),
            ],
         ],
         'apple_sha_type' => [
            'label'=> __('aps_sha_type'),
            'type' => 'dropdown',
            'values'=> ['sha256'=>'SHA-256','sha512'=>'SHA-512','hmac256'=>'HMAC-256','hmac512'=>'HMAC-512'],
         ],
         'apple_access_code' => [
            'label'=> __('aps_access_code'),
            'type' => 'text', 
         ],
         'apple_request_sha_phrase' => [
            'label'=> __('aps_request_sha_phrase'),
            'type' => 'text', 
         ],
         'apple_response_sha_phrase' => [
            'label'=> __('aps_response_sha_phrase'),
            'type' => 'text', 
         ],
         'apple_domain_name' => [
            'label'=> __('aps_domain_name'),
            'type' => 'text', 
         ],
         'apple_display_name' => [
            'label'=> __('aps_display_name'),
            'type' => 'text',
            'hint' => __('aps_display_name_hint'), 
         ],
         'apple_supported_networks' => [
            'label'=> __('aps_supported_networks'),
            'type' => 'checkboxes',
            'values' => ['amex'=>'American Express','masterCard'=>'Master Card','visa'=>'Visa','mada'=>'mada'],
         ],
         'apple_production_key' => [
            'label'=> __('aps_production_key'),
            'type' => 'text', 
         ],
         'apple_certificate_file' => [
            'label'=> __('aps_certificate_file'),
            'type' => 'file', 
         ],
         'apple_certificate_key_file' => [
            'label'=> __('aps_certificate_key_file'),
            'type' => 'file', 
         ],
      ],

      'NAPS' =>[
         'naps_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
      ],
      
      'KNET' =>[
         'knet_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
      ],

      'Visa Checkout' =>[
         'visa_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
         'visa_integration_type' => [
            'label'=> __('aps_integration_type'),
            'type' => 'dropdown',
            'values'=> ['redirection'=>__('aps_redirection'),'hosted_checkout'=>__('aps_hosted_checkout')],
         ],
         'visa_api_key' => [
            'label'=> __('aps_api_key'),
            'type' => 'text',
         ],
         'visa_profile_id' => [
            'label'=> __('aps_profile_id'),
            'type' => 'text',
         ],
      ],
      
      __('aps_installments') =>[
         'installments_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
         'installments_integration_type' => [
            'label'=> __('aps_integration_type'),
            'type' => 'dropdown',
            'values'=> [
               'redirection'=>__('aps_redirection'),
               'standard_checkout'=>__('aps_standard_checkout'),
               'hosted_checkout'=>__('aps_hosted_checkout'),
               'embedded_hosted_checkout' => __('aps_embedded_hosted_checkout')
            ],
         ],
         'installments_purchase_limit_sar' => [
            'label'=> __('aps_installments_minimum_purchase_limit_sar'),
            'type' => 'number',
            'default' => '1000'
         ],
         'installments_purchase_limit_aed' => [
            'label'=> __('aps_installments_minimum_purchase_limit_aed'),
            'type' => 'number',
            'default' => '1000'
         ],
         'installments_purchase_limit_egp' => [
            'label'=> __('aps_installments_minimum_purchase_limit_egp'),
            'type' => 'number',
            'default' => '1000'
         ],
         'installments_show_issuer_name' => [
            'label'=> __('aps_show_issuer_name'),
            'type' => 'checkbox',
         ],
         'installments_show_issuer_logo' => [
            'label'=> __('aps_show_issuer_logo'),
            'type' => 'checkbox',
         ], 
      ],
      
      'ValU' =>[
         'valu_enabled' => [
            'label'=> __('aps_enabled'),
            'type' => 'checkbox',
         ],
         'valu_purchase_limit_egp' => [
            'label'=> __('aps_valu_minimum_purchase_limit_egp'),
            'type' => 'number',
            'default' => '500'
         ],
      ],
   ];

   return $list;
}

// INSTALL / UNISNTALL
function fn_amazon_payment_services_install(){

   db_query("ALTER TABLE `?:orders` ADD `aps_reference` varchar(100) NULL, ADD `aps_gateway` varchar(30) NULL");
   
   db_query("DROP TABLE IF EXISTS `?:aps_card_tokens`");
   db_query("CREATE TABLE `?:aps_card_tokens` (
     `card_id` int(11) NOT NULL AUTO_INCREMENT,
     `user_id` int(11) NOT NULL,
     `payment_id` int(11) NOT NULL,
     `gateway` varchar(20) DEFAULT NULL,
     `token_name` varchar(100) NOT NULL,
     `type` varchar(30) NOT NULL,
     `card_number` varchar(30) NOT NULL,
     `expiry_date` char(4) NOT NULL,
     `default` char(1) NOT NULL DEFAULT 'N',
     `timestamp` int(16) NOT NULL,
     PRIMARY KEY (`card_id`),
     UNIQUE KEY `token_name` (`token_name`),
     KEY `user_id` (`user_id`),
     KEY `user_id_token_name` (`user_id`,`token_name`)
   )");

   db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s OR processor_script = ?s", "aps.php","amazon_payment_services.php");

   if( db_query("INSERT INTO ?:payment_processors (`processor`, `processor_script`, `processor_template`, `admin_template`, `callback`, `type`, `addon`) VALUES ('Amazon Payment Services', 'aps.php', 'addons/amazon_payment_services/views/orders/components/payments/aps.tpl', 'amazon_payment_services.tpl', 'Y', 'P', 'amazon_payment_services')") ){

      $payment_method = [
         'payment' => 'Amazon Payment Services',
         'company_id' => Registry::get('runtime.company_id'),
         'processor_id' => (int)db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s", "aps.php"),
         'usergroup_ids' => 0,
         'processor_params' => []
      ];

      fn_update_payment($payment_method, 0);
   }

}

function fn_amazon_payment_services_uninstall(){

   db_query("ALTER TABLE `?:orders` DROP `aps_reference`, DROP `aps_gateway`");
   db_query("DROP TABLE IF EXISTS `?:aps_card_tokens`");
   
   $processer_id = (int)db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s", "aps.php");
   if( $processer_id ){
      $payment_ids = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id = ?i",$processer_id);
      foreach($payment_ids as $payment_id){
         fn_delete_payment($payment_id);
      }
   }

   db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s OR processor_script = ?s", "aps.php","amazon_payment_services.php");
}