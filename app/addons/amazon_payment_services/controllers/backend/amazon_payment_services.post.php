<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if( $mode == 'action' ){

	$params = (array)filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+(array)filter_input_array(INPUT_POST,FILTER_SANITIZE_STRING);
	
	$action= !empty($params['action']) ? strtolower(trim($params['action'])) : '';
	$order_id = !empty($params['order_id']) ? (int)$params['order_id'] : 0;
	$amount = !empty($params['amount']) ? floatval(filter_var($params['amount'],FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) : 0;

	list($success,$error) = fn_amazon_payment_services_order_actions($order_id,$action,$amount); 

	if( $success )
		fn_set_notification('N',__("notice"),__("aps_order_{$action}_success"));
	else
		fn_set_notification('E',__("error"),$error);

	return array(CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id='.$order_id);
}

if( $mode == 'logs' ){

	$params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

	$logger = new \AmazonPaymentServices\Logger();

	$files = $logger->getFiles();

	$name = !empty($params['date']) ? trim(urldecode($params['date'])) : (!empty($files) ? trim($files[0]) : '');

	$content = $logger->readFile($name);

	Tygh::$app['view']->assign('files', $files);
	Tygh::$app['view']->assign('file_slc', $name);
	Tygh::$app['view']->assign('content', $content);
}