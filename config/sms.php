<?php

declare(strict_types=1);

$localConfig = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
$smsConfig = $localConfig['sms'] ?? [];

return [
    'enabled' => filter_var(getenv('SMS_ENABLED') ?: ($smsConfig['enabled'] ?? false), FILTER_VALIDATE_BOOLEAN),
    'environment' => getenv('SMS_ENVIRONMENT') ?: ($smsConfig['environment'] ?? 'sandbox'),
    'username' => getenv('SMS_USERNAME') ?: ($smsConfig['username'] ?? 'sandbox'),
    'api_key' => getenv('SMS_API_KEY') ?: ($smsConfig['api_key'] ?? ''),
    'sender_id' => getenv('SMS_SENDER_ID') ?: ($smsConfig['sender_id'] ?? ''),
];
