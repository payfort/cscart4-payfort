<?php
if ($mode == 'place_order') {
    if (!empty($_REQUEST['payment_id'])) {
        $payment_method_data = fn_get_payment_method_data($_REQUEST['payment_id']);
        $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $payment_method_data['processor_id']);
        if(in_array($processor_script, array('payfort_fort_cc.php', 'payfort_fort_sadad.php', 'payfort_fort_naps.php'))) {
            $cart = & $_SESSION['cart'];
            if(!empty($cart['failed_order_id']) || !empty($cart['processed_order_id'])) {
                $_order_ids = !empty($cart['failed_order_id']) ? $cart['failed_order_id'] : $cart['processed_order_id'];

                foreach ($_order_ids as $_order_id) {
                        fn_delete_order($_order_id);
                }
                $cart['rewrite_order_id'] = array();
                unset($cart['failed_order_id'], $cart['processed_order_id']);
            }
        }
    }
}
?>