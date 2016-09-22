<?php

use Tygh\Registry;

require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Util.php';
require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Config.php';
require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Language.php';
require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Helper.php';
require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Order.php';
require_once Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Payment.php';

Registry::get('class_loader')->addClassMap(array(
    'Payfort_Fort_Util'     => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Util.php',
    'Payfort_Fort_Config'   => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Config.php',
    'Payfort_Fort_Language' => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Language.php',
    'Payfort_Fort_Helper'   => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Helper.php',
    'Payfort_Fort_Order'    => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Order.php',
    'Payfort_Fort_Payment'  => Registry::get('config.dir.addons') . 'payfort_fort/lib/payfortFort/classes/Payment.php',
));
