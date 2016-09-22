<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
      //'pre_place_order'
        'get_payments_post'
);

require_once dirname(__FILE__) . '/lib/payfortFort/init.php';