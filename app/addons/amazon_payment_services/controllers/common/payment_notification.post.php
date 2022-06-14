<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if( $mode == 'aps_return' ){

		setcookie(Tygh::$app['session']->getName(), Tygh::$app['session']->getId(), [
		   'expires'  => time()+3600,
		   'path'     => ini_get('session.cookie_path'),
		   'domain'   => ini_get('session.cookie_domain'),
		   'samesite' => 'None',
		   'secure'   => true,
		   'httponly' => ini_get('session.cookie_httponly')
		]);
		
		fn_amazon_payment_services_handle_response('return','','','',filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)+filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING));
	}
}