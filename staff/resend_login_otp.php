<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/send_staff_otp.php';

if (!empty($_SESSION['staff_id'])) {
    redirect('/staff/dashboard');
}

if (empty($_SESSION['pending_staff_id'])) {
    redirect('/staff/login');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/staff/verify_login');
}

verify_csrf();

$pendingStaffId = (int) $_SESSION['pending_staff_id'];

try {
    $q = $pdo->prepare("SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1");
    $q->execute([$pendingStaffId]);
    $user = $q->fetch();

    if (!$user || $user['status'] !== 'active' || empty($user['email']) || !in_array($user['role'], ['pastor', 'admin'], true)) {
        unset($_SESSION['pending_staff_id']);
        $_SESSION['otp_notice'] = [
            'type' => 'error',
            'message' => 'Your login session is no longer valid. Please start again.'
        ];
        redirect('/staff/login');
    }

    // Prevent OTP flooding. A new code can be requested once every 60 seconds.
    $latest = $pdo->prepare("SELECT created_at FROM staff_login_otps WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $latest->execute([$pendingStaffId]);
    $latestCreated = $latest->fetchColumn();

    if ($latestCreated) {
        $elapsed = time() - strtotime((string) $latestCreated);
        if ($elapsed < 60) {
            $remaining = 60 - max(0, $elapsed);
            $_SESSION['otp_notice'] = [
                'type' => 'warning',
                'message' => 'Please wait ' . $remaining . ' second' . ($remaining === 1 ? '' : 's') . ' before requesting another code.'
            ];
            redirect('/staff/verify_login');
        }
    }

    $otp = (string) random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 300);

    // Only one live code is kept for this login attempt.
    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare("DELETE FROM staff_login_otps WHERE user_id = ?");
        $delete->execute([$pendingStaffId]);

        $insert = $pdo->prepare("INSERT INTO staff_login_otps (user_id, otp_hash, expires_at, attempts, used_at) VALUES (?, ?, ?, 0, NULL)");
        $insert->execute([$pendingStaffId, $otpHash, $expiresAt]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    if (!send_staff_login_otp((string) $user['email'], $otp)) {
        $pdo->prepare("DELETE FROM staff_login_otps WHERE user_id = ?")->execute([$pendingStaffId]);
        $_SESSION['otp_notice'] = [
            'type' => 'error',
            'message' => 'We could not send the new verification code. Please check the email service configuration and try again.'
        ];
        redirect('/staff/verify_login');
    }

    $_SESSION['otp_notice'] = [
        'type' => 'success',
        'message' => 'A new verification code has been sent to your registered email address. It expires in 5 minutes.'
    ];
    $_SESSION['otp_sent_at'] = time();

    log_activity($pdo, null, $pendingStaffId, 'staff_login_otp_resent', 'Staff login verification code resent by email');
    redirect('/staff/verify_login');
} catch (Throwable $e) {
    error_log('FGCK staff OTP resend error: ' . $e->getMessage());
    $_SESSION['otp_notice'] = [
        'type' => 'error',
        'message' => 'We could not process the resend request right now. Please try again.'
    ];
    redirect('/staff/verify_login');
}
