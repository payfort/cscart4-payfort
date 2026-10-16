<?php
error_reporting(E_ALL & ~E_DEPRECATED);
define('DIR_ROOT', getenv('HOME') . '/.aki/tmp/aps-token-tests');
define('PRODUCT_NAME', 'CS-Cart'); define('PRODUCT_VERSION', 'test'); define('PRODUCT_EDITION', 'test');
define('CART_PRIMARY_CURRENCY', 'AED'); define('CART_SECONDARY_CURRENCY', 'AED'); define('DESCR_SL', 'en'); define('AREA', 'C');
function __($key) { return $key; }
function fn_url($url, ...$args) { return 'https://shop.example.invalid/' . $url; }
$assertions = 0;
function check($ok, $message) { $GLOBALS['assertions']++; if (!$ok) { throw new RuntimeException($message); } }
function fn_update_order_payment_info(...$args) {}
$tokens = ['alice-token' => 1, 'bob-token' => 2];
function db_get_field($sql, ...$args) {
    global $tokens;
    check(strpos($sql, 'BINARY token_name = ?s') !== false, 'token comparison must be exact');
    if (strpos($sql, 'SELECT card_id') === 0) { return isset($tokens[$args[1]]) && $tokens[$args[1]] === $args[0] ? 42 : false; }
    if (strpos($sql, 'SELECT user_id') === 0) { return $tokens[$args[0]] ?? null; }
    throw new RuntimeException('Unexpected SQL: ' . $sql);
}
$addon = getenv('APS_ADDON_DIR') ?: __DIR__ . '/../../app/addons/amazon_payment_services';
require $addon . '/AmazonPaymentServices/Logger.php';
foreach (['Gateway', 'Cc', 'Installments'] as $class) { require $addon . '/AmazonPaymentServices/Gateways/' . $class . '.php'; }
trait NoNetwork {
    public $calls = [];
    public $saved = [];
    public function httpCall($method, $params) {
        $this->calls[] = $params;
        return ['status' => '14', 'response_code' => '14000', 'response_message' => 'Success', 'amount' => '1000', 'currency' => 'AED', 'command' => 'PURCHASE'];
    }
    public function saveCardToken($data, $update = false) { $this->saved[] = $data; }
}
class TestCc extends AmazonPaymentServices\Gateways\Cc { use NoNetwork; }
class TestInstallments extends AmazonPaymentServices\Gateways\Installments { use NoNetwork; }
function gateway($class = 'TestCc', $user = 2, $integration = 'hosted_checkout') {
    $g = new $class('Card', ['enabled' => 'Y', 'integration_type' => $integration, 'debug_mode' => 'N', 'enable_tokenization' => 'Y',
        'merchant_identifier' => 'MERCHANT', 'access_code' => 'ACCESS', 'request_sha_phrase' => 'request-test', 'response_sha_phrase' => 'response-test',
        'sha_type' => 'sha256', 'command' => 'PURCHASE']);
    $g->setUserId($user); $g->setOrderId(42); $g->setAmount(10); $g->setCurrency('AED'); $g->setReference('ORDER42');
    $g->setOrderDetails(['order_id' => 42, 'user_id' => $user, 'aps_reference' => 'ORDER42']);
    $g->setCustomerInfo(['customer_ip' => '127.0.0.1', 'customer_email' => 'test@example.invalid', 'customer_name' => 'Test']);
    return $g;
}
function fresh($g, $changes = []) {
    $p = array_merge(['token_name' => 'fresh-token', 'merchant_reference' => 'ORDER42', 'merchant_identifier' => 'MERCHANT', 'access_code' => 'ACCESS',
        'response_code' => '18000', 'status' => '18', 'response_message' => 'Success', 'remember_me' => 'NO', 'language' => 'en'], $changes);
    $p['signature'] = $g->generateSignature($p, true); return $p;
}
foreach (['TestCc', 'TestInstallments'] as $class) {
    foreach (['hosted_checkout', 'standard_checkout', 'redirection', 'embedded_hosted_checkout'] as $integration) {
        foreach (['alice-token', 'missing-token', 'BOB-TOKEN', ' ', [], null] as $token) {
            $g = gateway($class, 2, $integration);
            $g->setPaymentData(['token_name' => $token]);
            $result = $g->processRequest();
            check(!$result['success'] && $result['params'] === [], 'unauthorized request built');
            $g->processActions($result);
            check(count($g->calls) === 0, 'denied token reached API');
            $result = $g->tokenRequest('ORDER42', $token);
            check(!$result['success'] && $result['params'] === [], 'tokenRequest accepted unauthorized token');
        }
        $g = gateway($class, 2, $integration); $g->setPaymentData(['token_name' => ' bob-token ']);
        $r = $g->processRequest(); check($r['success'] && $r['params']['token_name'] === 'bob-token', 'owned token denied');
        check($g->tokenRequest('ORDER42', 'bob-token')['success'], 'owned tokenRequest denied');
        $g = gateway($class, 0, $integration); $g->setPaymentData(['token_name' => 'bob-token']);
        check(!$g->processRequest()['success'] && !$g->tokenRequest('ORDER42', 'bob-token')['success'], 'guest used saved card');
        $g = gateway($class, 0, $integration); check($g->processRequest()['success'], 'new card without token field denied');
        $g->setPaymentData(['token_name' => '']);
        check($g->processRequest()['success'], 'empty new-card radio value rejected');
    }
    echo "PASS $class saved-token ownership across 4 integration types\n";
}
foreach ([0, 2] as $user) {
    $g = gateway('TestCc', $user); $p = fresh($g); $g->processToken($p);
    check(count($g->calls) === 1, 'fresh guest/new card denied');
    $g = gateway('TestCc', $user); $g->config['enable_tokenization'] = 'N'; $g->processToken(fresh($g));
    check(count($g->calls) === 1, 'fresh card with saving disabled denied');
}
echo "PASS signed fresh token for guests and customers, including saving disabled\n";
foreach ([['merchant_reference' => 'OTHER_ORDER'], ['merchant_identifier' => 'OTHER_MERCHANT'], ['access_code' => 'OTHER_ACCESS'],
    ['token_name' => 'alice-token'], ['response_code' => '14000', 'status' => '14'], ['status' => '14']] as $change) {
    foreach (['TestCc', 'TestInstallments'] as $class) {
        $g = gateway($class); $p = fresh($g, $change); $g->processToken($p);
        check(!$g->calls, 'invalid fresh token charged');
        if (($change['response_code'] ?? '') !== '14000') {
            $g->processResponse($p); check(!$g->calls && !$g->saved, 'invalid token reached response hook/save');
        }
    }
}
$g = gateway(); $p = fresh($g); $p['signature'] = 'forged'; $g->processToken($p); $g->processResponse($p); check(!$g->calls && !$g->saved, 'bad signature accepted');
$g = gateway(); $g->order_info['aps_reference'] = ''; $g->processToken(fresh($g)); check(!$g->calls, 'missing reference accepted');
$g = gateway(); $g->setUserId(1); $g->processToken(fresh($g)); check(!$g->calls, 'callback user mismatch accepted');
$g = gateway(); $g->config['response_sha_phrase'] = ''; $g->processToken(fresh($g)); check(!$g->calls, 'empty SHA sentinel accepted');
$g = gateway(); $g->processResponse(fresh($g)); check(count($g->calls) === 1, 'valid CC tokenization response failed');
$g = gateway('TestInstallments'); $g->processResponse(fresh($g)); check(count($g->calls) === 1, 'valid installments response failed');
foreach (['TestCc', 'TestInstallments'] as $class) {
    $g = gateway($class); $g->processActions(['success' => false, 'error' => 'denied', 'params' => ['token_name' => 'alice-token']]);
    check(!$g->calls, 'failed response actions called gateway');
}
echo "PASS callback binding, signatures, ownership conflicts, action guards and valid fresh-token flows\n";
echo "PASS $assertions token assertions (including strict database checks)\n";
