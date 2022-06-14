<?php

defined('BOOTSTRAP') or die('Access denied');

if( $mode == 'complete' ){
	
	$cart = &Tygh::$app['session']['cart'];
	if( !empty($cart['products']) && !empty($_REQUEST['order_id']) ){
		unset($cart['products']);
		fn_clear_cart($cart);
	}
}