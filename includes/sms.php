<?php

declare(strict_types=1);

function sms_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/../config/sms.php';
    }
    return $config;
}

function sms_log(string $message): void
{
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($dir . '/sms.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function normalize_sms_phone(string $phone): string
{
    $phone = preg_replace('/[^0-9+]/', '', trim($phone));
    if (str_starts_with($phone, '+')) return $phone;
    if (str_starts_with($phone, '254')) return '+' . $phone;
    if (str_starts_with($phone, '0')) return '+254' . substr($phone, 1);
    return $phone;
}

function send_sms(string $phone, string $message): bool
{
    $config = sms_config();
    if (empty($config['enabled'])) return false;
    if (empty($config['api_key'])) {
        sms_log('SMS skipped: API key is not configured.');
        return false;
    }

    $phone = normalize_sms_phone($phone);
    if ($phone === '') return false;

    $sandbox = strtolower((string)$config['environment']) !== 'live';
    $endpoint = $sandbox
        ? 'https://api.sandbox.africastalking.com/version1/messaging'
        : 'https://api.africastalking.com/version1/messaging';

    $fields = [
        'username' => $sandbox ? 'sandbox' : (string)$config['username'],
        'to' => $phone,
        'message' => $message,
    ];
    if (!$sandbox && !empty($config['sender_id'])) {
        $fields['from'] = $config['sender_id'];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'apiKey: ' . $config['api_key'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        sms_log('SMS failed. HTTP=' . $httpCode . ' Error=' . $curlError . ' Response=' . substr((string)$response, 0, 1000));
        return false;
    }

    sms_log('SMS accepted. To=' . $phone . ' HTTP=' . $httpCode . ' Response=' . substr((string)$response, 0, 1000));
    return true;
}
