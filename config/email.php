<?php
declare(strict_types=1);

/**
 * FGCK Joyland
 * Email Configuration
 *
 * Supports:
 * - config/local.php
 * - HostAfrica environment variables
 * - Gmail SMTP
 */

$localConfig = [];

$localConfigFile = __DIR__ . '/local.php';

if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;
}

$emailConfig = $localConfig['email'] ?? [];

/*
|--------------------------------------------------------------------------
| Email enabled
|--------------------------------------------------------------------------
*/

$emailEnabledEnv = getenv('FGCK_EMAIL_ENABLED');

if ($emailEnabledEnv === false || $emailEnabledEnv === '') {
    $emailEnabled = (bool) ($emailConfig['enabled'] ?? true);
} else {
    $emailEnabled = filter_var(
        $emailEnabledEnv,
        FILTER_VALIDATE_BOOLEAN
    );
}

if (!defined('FGCK_RUNTIME_EMAIL_ENABLED')) {
    define(
        'FGCK_RUNTIME_EMAIL_ENABLED',
        $emailEnabled
    );
}

/*
|--------------------------------------------------------------------------
| SMTP Host
|--------------------------------------------------------------------------
*/

$smtpHost = getenv('FGCK_SMTP_HOST');

if ($smtpHost === false || $smtpHost === '') {
    $smtpHost = getenv('SMTP_HOST');
}

if ($smtpHost === false || $smtpHost === '') {
    $smtpHost = $emailConfig['smtp_host'] ?? 'smtp.gmail.com';
}

define(
    'FGCK_RUNTIME_SMTP_HOST',
    $smtpHost
);

/*
|--------------------------------------------------------------------------
| SMTP Port
|--------------------------------------------------------------------------
*/

$smtpPort = getenv('FGCK_SMTP_PORT');

if ($smtpPort === false || $smtpPort === '') {
    $smtpPort = getenv('SMTP_PORT');
}

if ($smtpPort === false || $smtpPort === '') {
    $smtpPort = $emailConfig['smtp_port'] ?? 587;
}

define(
    'FGCK_RUNTIME_SMTP_PORT',
    (int) $smtpPort
);

/*
|--------------------------------------------------------------------------
| SMTP Encryption
|--------------------------------------------------------------------------
*/

$smtpEncryption = getenv('FGCK_SMTP_ENCRYPTION');

if ($smtpEncryption === false || $smtpEncryption === '') {
    $smtpEncryption = getenv('SMTP_ENCRYPTION');
}

if ($smtpEncryption === false || $smtpEncryption === '') {
    $smtpEncryption = $emailConfig['smtp_encryption'] ?? 'tls';
}

define(
    'FGCK_RUNTIME_SMTP_ENCRYPTION',
    strtolower(trim($smtpEncryption))
);

/*
|--------------------------------------------------------------------------
| SMTP Username
|--------------------------------------------------------------------------
*/

$smtpUsername = getenv('FGCK_SMTP_USERNAME');

if ($smtpUsername === false || $smtpUsername === '') {
    $smtpUsername = getenv('SMTP_USERNAME');
}

if ($smtpUsername === false || $smtpUsername === '') {
    $smtpUsername = $emailConfig['smtp_username'] ?? 'fgckjoyland@gmail.com';
}

define(
    'FGCK_RUNTIME_SMTP_USERNAME',
    trim($smtpUsername)
);

/*
|--------------------------------------------------------------------------
| SMTP Password
|--------------------------------------------------------------------------
*/

$smtpPassword = getenv('FGCK_SMTP_PASSWORD');

if ($smtpPassword === false || $smtpPassword === '') {
    $smtpPassword = getenv('SMTP_PASSWORD');
}

if ($smtpPassword === false || $smtpPassword === '') {
    $smtpPassword = $emailConfig['smtp_password'] ?? 'aker dptw oosw vhar';
}

define(
    'FGCK_RUNTIME_SMTP_PASSWORD',
    trim($smtpPassword)
);

/*
|--------------------------------------------------------------------------
| From Email
|--------------------------------------------------------------------------
*/

$fromEmail = getenv('FGCK_EMAIL_FROM');

if ($fromEmail === false || $fromEmail === '') {
    $fromEmail = getenv('EMAIL_FROM_EMAIL');
}

if ($fromEmail === false || $fromEmail === '') {
    $fromEmail = $emailConfig['from_email'] ?? $smtpUsername;
}

define(
    'FGCK_RUNTIME_EMAIL_FROM',
    trim($fromEmail)
);

/*
|--------------------------------------------------------------------------
| From Name
|--------------------------------------------------------------------------
*/

$fromName = getenv('FGCK_EMAIL_FROM_NAME');

if ($fromName === false || $fromName === '') {
    $fromName = getenv('EMAIL_FROM_NAME');
}

if ($fromName === false || $fromName === '') {
    $fromName = $emailConfig['from_name']
        ?? 'FGCK Joyland Appointment System';
}

define(
    'FGCK_RUNTIME_EMAIL_FROM_NAME',
    trim($fromName)
);

/*
|--------------------------------------------------------------------------
| Reply-To
|--------------------------------------------------------------------------
*/

$replyTo = getenv('FGCK_EMAIL_REPLY_TO');

if ($replyTo === false || $replyTo === '') {
    $replyTo = getenv('EMAIL_REPLY_TO');
}

if ($replyTo === false || $replyTo === '') {
    $replyTo = $emailConfig['reply_to']
        ?? $fromEmail;
}

define(
    'FGCK_RUNTIME_EMAIL_REPLY_TO',
    trim($replyTo)
);

/*
|--------------------------------------------------------------------------
| Application URL
|--------------------------------------------------------------------------
*/

$appUrl = getenv('FGCK_APP_URL');

if ($appUrl === false || $appUrl === '') {
    $appUrl = getenv('EMAIL_BASE_URL');
}

if ($appUrl === false || $appUrl === '') {
    $appUrl = $emailConfig['base_url']
        ?? 'https://pastorconnect.live';
}

define(
    'FGCK_RUNTIME_APP_URL',
    rtrim(trim($appUrl), '/')
);


/*
|--------------------------------------------------------------------------
| Backward-compatible EMAIL_* constants
|--------------------------------------------------------------------------
*/

if (!defined('EMAIL_ENABLED')) {
    define(
        'EMAIL_ENABLED',
        FGCK_RUNTIME_EMAIL_ENABLED
    );
}

if (!defined('EMAIL_SMTP_HOST')) {
    define(
        'EMAIL_SMTP_HOST',
        FGCK_RUNTIME_SMTP_HOST
    );
}

if (!defined('EMAIL_SMTP_PORT')) {
    define(
        'EMAIL_SMTP_PORT',
        FGCK_RUNTIME_SMTP_PORT
    );
}

if (!defined('EMAIL_SMTP_ENCRYPTION')) {
    define(
        'EMAIL_SMTP_ENCRYPTION',
        FGCK_RUNTIME_SMTP_ENCRYPTION
    );
}

if (!defined('EMAIL_SMTP_USERNAME')) {
    define(
        'EMAIL_SMTP_USERNAME',
        FGCK_RUNTIME_SMTP_USERNAME
    );
}

if (!defined('EMAIL_SMTP_PASSWORD')) {
    define(
        'EMAIL_SMTP_PASSWORD',
        FGCK_RUNTIME_SMTP_PASSWORD
    );
}

if (!defined('EMAIL_FROM_EMAIL')) {
    define(
        'EMAIL_FROM_EMAIL',
        FGCK_RUNTIME_EMAIL_FROM
    );
}

if (!defined('EMAIL_FROM_NAME')) {
    define(
        'EMAIL_FROM_NAME',
        FGCK_RUNTIME_EMAIL_FROM_NAME
    );
}

if (!defined('EMAIL_REPLY_TO')) {
    define(
        'EMAIL_REPLY_TO',
        FGCK_RUNTIME_EMAIL_REPLY_TO
    );
}

if (!defined('EMAIL_BASE_URL')) {
    define(
        'EMAIL_BASE_URL',
        FGCK_RUNTIME_APP_URL
    );
}