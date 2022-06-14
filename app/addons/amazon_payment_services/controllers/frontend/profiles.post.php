<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$is_update = !empty($auth['user_id']);

    if( $is_update && ( $mode == 'card_delete' || $mode == 'card_default' ) ){
    	
		$params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	
		$condition = db_quote("user_id = ?i AND card_id=?i",$auth['user_id'],(int)$params['card_id']);

		if( $mode == 'card_default' ){
			db_query("UPDATE ?:aps_card_tokens SET ?u WHERE user_id = ?i",['default'=>'N'],$auth['user_id']);
			db_query("UPDATE ?:aps_card_tokens SET ?u WHERE ".$condition,['default'=>'Y']);
		}

		if( $mode == 'card_delete' ){
			$card = db_get_row("SELECT * FROM ?:aps_card_tokens WHERE ".$condition);

			$error = fn_amazon_payment_services_delete_card($card);
			if( $error )
				fn_set_notification("E",__("error"),$error);
		}
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'profiles.payment_methods');
}

if( $mode == 'payment_methods' ){

	if (empty($auth['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
    }

    fn_add_breadcrumb(__('payment_methods'));

    $user_cards = db_get_array("SELECT card_id,type,card_number,expiry_date,`default` FROM ?:aps_card_tokens WHERE user_id = ?i ORDER BY timestamp ASC",$auth['user_id']);

    Tygh::$app['view']->assign('user_cards', $user_cards);
}
