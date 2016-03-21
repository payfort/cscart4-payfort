<?php
use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && $_REQUEST['addon'] == 'payfort_fort' && (!empty($_REQUEST['payfort_fort_settings']))) {
        $payfort_fort_settings = isset($_REQUEST['payfort_fort_settings']) ? $_REQUEST['payfort_fort_settings'] : array();
        fn_update_payfort_fort_settings($payfort_fort_settings);
    }
}

if ($mode == 'update') {
    if ($_REQUEST['addon'] == 'payfort_fort') {
        Tygh::$app['view']->assign('payfort_fort_settings', fn_get_payfort_fort_settings());
    }
}
