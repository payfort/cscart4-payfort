<?php
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (!empty($_REQUEST['payment'])) {
    $payment = fn_basename($_REQUEST['payment']);
    if($payment == 'payfort_fort') {
        if(!empty($_REQUEST['merchant_reference'])) {
            $order_id = $_REQUEST['merchant_reference'];
            $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
            $processor_data = fn_get_payment_method_data($payment_id);
            $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_data['processor_id']);
            if($processor_script == 'payfort_fort_cc.php') {
                $_REQUEST['payment'] = 'payfort_fort_cc';
            }
            elseif($processor_script == 'payfort_fort_sadad.php') {
                $_REQUEST['payment'] = 'payfort_fort_sadad';
            }
            elseif($processor_script == 'payfort_fort_installments.php') {
                $_REQUEST['payment'] = 'payfort_fort_installments';
            }
            elseif($processor_script == 'payfort_fort_naps.php') {
                $_REQUEST['payment'] = 'payfort_fort_naps';
            }
        }
    }
}
