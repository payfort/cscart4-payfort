<?php
require (getenv('APS_ADDON_DIR') ?: __DIR__ . '/../../app/addons/amazon_payment_services') . '/AmazonPaymentServices/Logger.php';
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
$root = getenv('HOME') . '/.aki/tmp/aps-log-' . bin2hex(random_bytes(6)) . '/';
$secrets = [
    'card_security_code' => 'secret-cvv-998', 'card_number' => '4111111111111111',
    'expiry_date' => 'secret-expiry', 'card_holder_name' => 'Sensitive Person',
    'token_name' => 'secret-token', 'access_code' => 'secret-access',
    'request_sha_phrase' => 'secret-request', 'response_sha_phrase' => 'secret-response',
    'merchant_identifier' => 'secret-merchant', 'signature' => 'secret-signature',
    'customer_email' => 'private@example.invalid', 'customer_ip' => '192.0.2.42',
    'phone_number' => 'secret-phone', 'fort_id' => 'secret-fort', 'merchant_reference' => 'secret-reference',
    'unknown_future_secret' => 'secret-future', 'response_message' => 'gateway echoed secret-token',
];
try {
    foreach ([false, 'N', 0, null, 'false', '1', 1] as $flag) {
        $logger = new AmazonPaymentServices\Logger($flag, $root);
        check($logger->log('Request', $secrets, 'cc') === false, 'disabled logger wrote data');
        check(!file_exists($root), 'disabled logger created a directory');
    }
    echo "PASS logger disabled flags (7)\n";
    $logger = new AmazonPaymentServices\Logger(true, $root);
    $payload = ['Params' => $secrets + ['amount' => '12000', 'currency' => 'AED', 'command' => 'PURCHASE'],
        'Response' => ['data' => [$secrets, json_encode($secrets)], 'response_code' => '14000'],
        'Notify Url' => '/callback?token_name=secret-url', 'secret-key-name' => 'secret-value'];
    $original = $payload;
    check($logger->log('Request', $payload, 'cc') > 0, 'enabled logging failed');
    check($payload === $original, 'logger mutated payment request');
    check($logger->log('Response', json_encode($payload), 'cc') > 0, 'JSON logging failed');
    foreach (['token_name=secret-raw', '{"token_name":"secret-malformed"', (object)$secrets, null] as $raw) {
        $logger->log('Response', $raw, 'cc');
    }
    $logger->log('secret-event', ['status' => "14\nsecret-injection", 'amount' => 'secret-amount'], 'secret-type');
    $cycle = []; $cycle['data'] = &$cycle;
    $logger->log('Request', $cycle, 'cc');
    (new AmazonPaymentServices\Logger('Y', $root))->log('Response', ['response_code' => '14000'], 'cc');
    $file = $root . date('Y-m-d') . '.log';
    $text = file_get_contents($file);
    foreach (array_merge(array_values($secrets), ['secret-url', 'secret-key-name', 'secret-value', 'secret-raw', 'secret-malformed', 'secret-event', 'secret-injection', 'secret-type', 'secret-amount']) as $secret) {
        check(strpos($text, $secret) === false, 'secret leaked: ' . $secret);
    }
    check(strpos($text, '12000') !== false && strpos($text, '14000') !== false && strpos($text, 'AED') !== false, 'safe diagnostics lost');
    check(strpos($text, 'REDACTED') !== false, 'redaction not visible');
    $before = $text;
    (new AmazonPaymentServices\Logger(false, $root))->log('Request', $secrets, 'cc');
    check(file_get_contents($file) === $before, 'disabled logger appended to existing file');
    echo "PASS logger nested/JSON/raw/object/cycle redaction, safe diagnostics, unchanged inputs and existing-file protection\n";
} finally {
    foreach (glob($root . '*') ?: [] as $file) { unlink($file); }
    if (is_dir($root)) { rmdir($root); }
}
