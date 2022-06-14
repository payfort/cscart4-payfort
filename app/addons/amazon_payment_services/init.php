<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'prepare_checkout_payment_methods',
    'update_payment_pre',
    'order_placement_routines',
    'get_order_info'
);
