<?php

require_once __DIR__ . '/email.php';

/**
 * Send pastor login verification code.
 */
function send_staff_login_otp(string $email, string $otp): bool
{
    $body = '<p>A login attempt was made on your FGCK Joyland Pastor Portal account.</p>
        <p style="margin:25px 0;padding:20px;background:#eff6ff;border-radius:8px;color:#2563eb;font-size:34px;font-weight:800;letter-spacing:8px;text-align:center">'
        . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</p>
        <p>This verification code will expire in <strong>5 minutes</strong>.</p>
        <p>If you did not attempt to sign in, you can safely ignore this email.
        
<p >

<b style="color:red; font-size:14px;front-style:italic;">
As members of FGCK Makutano West Joyland, we are
Perfected To Influence The World

</p>
';

    return send_system_email(
        $email,
        '',
        'FGCK Joyland Login Verification Code',
        $body
    );
    
}
