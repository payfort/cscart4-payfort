<?php

use Tygh\Http;
use Tygh\Registry;

/**
 * @var array $processor_data
 * @var array $order_info
 * @var string $mode
 */
if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Return from payfort website
if (defined('PAYMENT_NOTIFICATION')) {
    $payment_method = PAYFORT_FORT_PAYMENT_METHOD_NAPS;
    $response_mode = 'online';
    $integration_type = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
    if ($mode == 'return') {
        $response_mode = 'offline';
    }
    
    if($mode == 'return' || $mode == 'responseOnline') {
        fn_payfort_fort_process_response($payment_method, $response_mode, $integration_type);
    }
    elseif ($mode == 'notify') {
        $order_id = $_REQUEST['order_id'];
        if (empty($auth['user_id']) && !empty($order_id)) {
            $auth['order_ids'][] = $order_id;
        }
        fn_order_placement_routines('route', $order_id, false);
    }
    
} else {
    fn_payfort_fort_process_request($order_id, $order_info, PAYFORT_FORT_PAYMENT_METHOD_NAPS);
}
exit;
