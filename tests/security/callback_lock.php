<?php
// Runs the real callback handler with strict CS-Cart/database doubles, no network.
namespace AmazonPaymentServices {
    class APS {
        public function __construct($params) {}
        public function loadGateway($type, $cart, $force) { return new TestGateway(); }
    }
    class TestGateway {
        public $isJson = false;
        public function __call($name, $args) {}
        public function processResponse() {
            $GLOBALS['events'][] = 'process';
            if ($GLOBALS['scenario'] === 'exception') { throw new \RuntimeException('test exception'); }
            if ($GLOBALS['scenario'] === 'gateway_exit') { exit; }
            return ['success' => true, 'details' => ['order_status' => 'P']];
        }
    }
}
namespace {
    $scenario = $argv[1] ?? '';
    if ($scenario === '') {
        $cases = ['notify', 'return', 'paid_notify', 'paid_return', 'overlap', 'timeout', 'null', 'exception', 'gateway_exit', 'invalid', 'parent', 'mismatch'];
        foreach ($cases as $case) {
            passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $code);
            if ($code !== 0) { exit($code); }
        }
        exit;
    }
    define('BOOTSTRAP', true);
    define('DIR_ROOT', getenv('HOME') . '/.aki/tmp/aps-callback-tests');
    class Tygh { public static $app = ['session' => ['cart' => ['products' => [1]], 'auth' => []]]; }
    $events = []; $locked = false; $releases = 0; $reads = 0;
    function check($condition, $message) { if (!$condition) { throw new \RuntimeException($message); } }
    function __($key) { return $key; }
    function db_quote($sql) { return str_replace('?:', 'test_', $sql); }
    function db_get_field($sql, ...$args) {
        global $locked, $events, $releases, $scenario;
        if ($sql === 'SELECT DATABASE()') { return 'test_store'; }
        if (strpos($sql, 'GET_LOCK') !== false) {
            $events[] = 'acquire';
            check(strlen($args[0]) <= 64, 'lock name exceeds MySQL limit');
            if ($locked || $scenario === 'timeout') { return 0; }
            if ($scenario === 'null') { return null; }
            $locked = true; return '1';
        }
        if (strpos($sql, 'RELEASE_LOCK') !== false) {
            check($locked, 'released without ownership');
            $locked = false; $releases++; $events[] = 'release'; return 1;
        }
        throw new \RuntimeException('Unexpected SQL: ' . $sql);
    }
    function fn_get_order_info($id) {
        global $locked, $reads, $events, $scenario;
        check($locked, 'read occurred outside lock'); $reads++; $events[] = 'read';
        if ($scenario === 'overlap' && $reads === 1) {
            // Deliver the other callback at the vulnerable point, before any update.
            fn_amazon_payment_services_handle_response('notify', '00042', 'cc', 1, []);
            check($reads === 1, 'contending callback read stale order');
        }
        return ['status' => strpos($scenario, 'paid_') === 0 ? 'P' : 'O', 'is_parent_order' => $scenario === 'parent' ? 'Y' : 'N',
            'aps_gateway' => $scenario === 'mismatch' ? 'other' : 'cc', 'total' => 10, 'user_id' => 1,
            'ip_address' => '', 'email' => 'test@example.invalid', 'firstname' => 'Test', 'lastname' => '', 'phone' => '',
            'payment_method' => ['processor_params' => ['enabled' => 'Y'], 'payment_id' => 1]];
    }
    function db_get_array($sql, ...$args) { check($GLOBALS['locked'], 'child-order read outside lock'); return [['order_id' => 43]]; }
    function fn_check_payment_script($script, $id) { return true; }
    function fn_get_order_paid_statuses() { return ['P', 'C']; }
    function fn_set_notification(...$args) {}
    function fn_url($url) { return $url; }
    function fn_redirect($url) { exit; }
    function fn_update_order_payment_info($id, $details) { check($GLOBALS['locked'], 'payment write outside lock'); $GLOBALS['events'][] = 'payment'; }
    function fn_change_order_status($id, $status) { check($GLOBALS['locked'], 'status write outside lock'); $GLOBALS['events'][] = 'status'; }
    function fn_finish_payment($id, $details) { fn_update_order_payment_info($id, $details); fn_change_order_status($id, 'P'); }
    function fn_order_placement_routines(...$args) { exit; }
    require (getenv('APS_ADDON_DIR') ?: __DIR__ . '/../../app/addons/amazon_payment_services') . '/func.php';
    ob_start();
    // Register the verifier from this shutdown callback, after the add-on release callback.
    register_shutdown_function(function () {
        register_shutdown_function(function () {
            global $scenario, $locked, $reads, $releases, $events;
            ob_end_clean();
            $noLock = in_array($scenario, ['timeout', 'null', 'invalid']);
            check(!$locked, 'lock leaked at exit');
            check($releases === ($noLock ? 0 : 1), 'incorrect release count');
            check($reads === ($noLock ? 0 : 1), 'incorrect read count');
            if (in_array($scenario, ['timeout', 'null'])) { check(http_response_code() === 503, 'must signal retry'); }
            $processed = count(array_keys($events, 'process', true));
            $expected = in_array($scenario, ['notify', 'return', 'overlap', 'exception', 'gateway_exit']) ? 1 : 0;
            check($processed === $expected, 'incorrect processing count');
            if (in_array($scenario, ['notify', 'return', 'overlap'])) {
                check(array_search('status', $events) < array_search('release', $events), 'released before status update');
            }
            echo "PASS callback $scenario\n";
        });
    });
    try {
        fn_amazon_payment_services_handle_response(strpos($scenario, 'return') !== false ? 'return' : 'notify', $scenario === 'invalid' ? 0 : 42, 'cc', 1, []);
    } catch (\RuntimeException $e) {
        if ($scenario !== 'exception' || $e->getMessage() !== 'test exception') { throw $e; }
    }
}
