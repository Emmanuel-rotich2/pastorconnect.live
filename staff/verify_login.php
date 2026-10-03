<?php

require __DIR__ . '/../includes/bootstrap.php';

if (empty($_SESSION['pending_staff_id'])) {
    redirect('/staff/login');
}

$pending_staff_id = (int) $_SESSION['pending_staff_id'];

if ($pending_staff_id <= 0) {
    unset($_SESSION['pending_staff_id']);
    redirect('/staff/login');
}
$error = '';
$success = false;

$otp_notice = $_SESSION['otp_notice'] ?? null;
unset($_SESSION['otp_notice']);

$pending_staff_id = (int) $_SESSION['pending_staff_id'];

$otp_resend_wait = 0;
try {
    $latestOtp = $pdo->prepare("SELECT created_at FROM staff_login_otps WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $latestOtp->execute([$pending_staff_id]);
    $latestCreatedAt = $latestOtp->fetchColumn();
    if ($latestCreatedAt) {
        $otp_resend_wait = max(0, 60 - (time() - strtotime((string) $latestCreatedAt)));
    }
} catch (Throwable $e) {
    $otp_resend_wait = 0;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {

        $error = 'Please enter the 6-digit verification code.';

    } else {

        /*
         * Get latest active OTP.
         */
        $q = $pdo->prepare("
            SELECT *
            FROM staff_login_otps
            WHERE user_id = ?
            AND used_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ");

        $q->execute([
            $pending_staff_id
        ]);

        $record = $q->fetch();


        if (!$record) {

            $error =
                'This verification code is no longer valid. Please request a new code.';

        } elseif (
            strtotime($record['expires_at']) < time()
        ) {

            $error =
                'Your verification code has expired. Please request a new code.';

        } elseif ((int)$record['attempts'] >= 5) {

            $error =
                'Too many incorrect attempts. Please request a new code.';

        } else {

            /*
             * Count attempt.
             */
            $pdo->prepare("
                UPDATE staff_login_otps
                SET attempts = attempts + 1
                WHERE id = ?
            ")->execute([
                $record['id']
            ]);


            /*
             * Verify OTP.
             */
            if (!password_verify(
                $otp,
                $record['otp_hash']
            )) {

                $remaining =
                    4 - (int)$record['attempts'];

                if ($remaining > 0) {

                    $error =
                        'Incorrect verification code. ' .
                        $remaining .
                        ' attempt(s) remaining.';

                } else {

                    $error =
                        'Incorrect verification code. Please request a new code.';
                }

            } else {

                /*
                 * Mark OTP as used.
                 */
                $pdo->prepare("
                    UPDATE staff_login_otps
                    SET used_at = NOW()
                    WHERE id = ?
                ")->execute([
                    $record['id']
                ]);


                /*
                 * Retrieve staff account.
                 */
                $userQuery = $pdo->prepare("
                    SELECT *
                    FROM users
                    WHERE id = ?
                    AND role IN ('pastor', 'admin')
                    AND status = 'active'
                    LIMIT 1
                ");

                $userQuery->execute([
                    $pending_staff_id
                ]);

                $u = $userQuery->fetch();


                if (!$u) {

                    unset(
                        $_SESSION['pending_staff_id']
                    );

                    $error =
                        'Your account could not be verified.';

                } else {

                    /*
                     * Create authenticated session.
                     */
                    session_regenerate_id(true);

                    $_SESSION['staff_id'] =
                        $u['id'];


                    /*
                     * Remove temporary login session.
                     */
                    unset(
                        $_SESSION['pending_staff_id']
                    );


                    /*
                     * Update last login.
                     */
                    $pdo->prepare("
                        UPDATE users
                        SET last_login_at = NOW()
                        WHERE id = ?
                    ")->execute([
                        $u['id']
                    ]);


                    /*
                     * Activity log.
                     */
                    log_activity(
                        $pdo,
                        null,
                        $u['id'],
                        'staff_login',
                        'Staff login with email verification'
                    );


                    /*
                     * Remove remaining OTPs.
                     */
                    $pdo->prepare("
                        DELETE FROM staff_login_otps
                        WHERE user_id = ?
                    ")->execute([
                        $u['id']
                    ]);


                    /*
                     * Authentication is complete.
                     *
                     * Show the success alert HERE, before the dashboard is loaded.
                     * The pastor must click the button before being redirected.
                     */
                    $dashboardUrl = '/staff/dashboard';
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Login Verified - FGCK Joyland</title>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <style>
                            body {
                                margin: 0;
                                min-height: 100vh;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: linear-gradient(135deg, #f8fbff, #eef5fc);
                                font-family: Arial, sans-serif;
                            }
                        </style>
                    </head>
                    <body>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: 'Verification Successful!',
                                html: '<div style="font-size:15px;line-height:1.7;color:#64748b;">Your identity has been verified successfully.<br><strong style="color:#2563eb;">Welcome to the FGCK Joyland Pastor Portal.</strong></div>',
                                icon: 'success',
                                confirmButtonText: '<i class="bi bi-speedometer2"></i> Continue to Dashboard',
                                confirmButtonColor: '#2563eb',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                customClass: {
                                    popup: 'login-success-popup',
                                    title: 'login-success-title'
                                }
                            }).then(function () {
                                window.location.href = <?= json_encode($dashboardUrl) ?>;
                            });
                        });
                    </script>
                    </body>
                    </html>
                    <?php
                    exit;
                }
            }
        }
    }
}

$page_title = 'Verify Pastor Login';

require __DIR__ . '/../includes/header.php';

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>

    .otp-page {
        min-height: calc(100vh - 70px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 35px 15px;

        background:
            radial-gradient(
                circle at 10% 10%,
                rgba(37, 99, 235, .10),
                transparent 30%
            ),
            radial-gradient(
                circle at 90% 90%,
                rgba(15, 42, 85, .10),
                transparent 30%
            ),
            linear-gradient(
                135deg,
                #f8fafc,
                #eef4ff
            );
    }


    .otp-card {
        width: 100%;
        max-width: 500px;

        background: #fff;

        border-radius: 24px;

        overflow: hidden;

        box-shadow:
            0 25px 70px rgba(15, 42, 85, .15);
    }


    .otp-header {
        text-align: center;

        padding: 35px 25px;

        background:
            linear-gradient(
                135deg,
                #0f2a55,
                #2563eb
            );

        color: #fff;
    }


    .otp-icon {
        width: 70px;
        height: 70px;

        border-radius: 50%;

        background: rgba(255,255,255,.15);

        display: flex;
        align-items: center;
        justify-content: center;

        margin: 0 auto 15px;

        font-size: 28px;
    }


    .otp-header h3 {
        font-weight: 800;
        margin-bottom: 5px;
    }


    .otp-header p {
        margin: 0;
        opacity: .75;
    }


    .otp-body {
        padding: 40px;
    }


    .otp-description {
        text-align: center;

        color: #64748b;

        line-height: 1.7;

        margin-bottom: 25px;
    }


    .otp-input {
        height: 62px;

        border-radius: 14px;

        font-size: 26px;

        font-weight: 800;

        letter-spacing: 9px;

        text-align: center;

        background: #f8fafc;

        border: 1px solid #e2e8f0;
    }


    .otp-input:focus {
        border-color: #2563eb;

        box-shadow:
            0 0 0 4px rgba(37, 99, 235, .10);
    }


    .verify-button {
        height: 55px;

        border: 0;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                #1d4ed8,
                #3b82f6
            );

        color: #fff;

        font-weight: 800;

        transition: .2s ease;
    }


    .verify-button:hover {
        color: #fff;

        transform: translateY(-1px);

        box-shadow:
            0 10px 25px rgba(37, 99, 235, .20);
    }


    .login-success-popup {
        border-radius: 24px !important;
        padding: 30px !important;
    }


    .login-success-title {
        font-weight: 800 !important;
        color: #17221c !important;
    }


    .swal2-html-container {
        color: #64748b !important;
        line-height: 1.7 !important;
    }


    .swal2-timer-progress-bar {
        background: #198754 !important;
    }


    @media (max-width: 576px) {

        .otp-body {
            padding: 30px 22px;
        }

        .otp-input {
            font-size: 22px;
            letter-spacing: 7px;
        }

    }

</style>


<div class="otp-page">

    <div class="otp-card">


        <!-- HEADER -->

        <div class="otp-header">

            <div class="otp-icon">

                <i class="bi bi-shield-lock-fill"></i>

            </div>


            <h3>
                Verify Your Login
            </h3>


            <p>
                FGCK Joyland Pastor Portal
            </p>

        </div>



        <!-- BODY -->

        <div class="otp-body">


            <div class="otp-description">

                <p class="mb-2">

                    A verification code has been
                    sent to your registered email.

                </p>


                <p class="small mb-0">

                    The code expires in
                    <strong>5 minutes</strong>.

                </p>

            </div>



            <?php if ($error): ?>

                <div
                    class="alert alert-danger"
                    style="border-radius:12px;"
                >

                    <i
                        class="bi bi-exclamation-circle-fill me-2"
                    ></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>



            <?php if ($otp_notice): ?>
                <div class="otp-notice otp-notice-<?=e($otp_notice['type'] ?? 'info')?>">
                    <i class="bi <?=($otp_notice['type'] ?? '') === 'success' ? 'bi-check-circle-fill' : (($otp_notice['type'] ?? '') === 'warning' ? 'bi-hourglass-split' : 'bi-exclamation-circle-fill')?>"></i>
                    <span><?=e($otp_notice['message'] ?? '')?></span>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <div class="mb-4">

                    <label
                        class="form-label fw-bold"
                    >

                        Verification Code

                    </label>


                    <input
                        type="text"
                        name="otp"
                        class="form-control otp-input"
                        maxlength="6"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        pattern="[0-9]{6}"
                        required
                        autofocus
                    >

                </div>



                <button
                    type="submit"
                    class="btn verify-button w-100"
                >

                    <i
                        class="bi bi-shield-check me-2"
                    ></i>

                    Verify & Continue

                </button>

            </form>



            <div class="otp-resend-wrap">
                <form method="post" action="/staff/resend_login_otp" id="resendOtpForm">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <button type="submit" class="otp-resend-btn" id="resendOtpBtn" <?= $otp_resend_wait > 0 ? 'disabled' : '' ?>>
                        <span class="otp-resend-icon"><i class="bi bi-arrow-repeat"></i></span>
                        <span class="otp-resend-copy">
                            <strong id="resendOtpLabel"><?= $otp_resend_wait > 0 ? 'Resend available soon' : 'Resend verification code' ?></strong>
                            <small id="resendOtpTimer"><?= $otp_resend_wait > 0 ? 'Please wait ' . $otp_resend_wait . 's' : 'We will send a fresh 6-digit code to your email.' ?></small>
                        </span>
                    </button>
                </form>
            </div>



            <div class="text-center mt-3">

                <a
                    href="/staff/login"
                    class="text-muted text-decoration-none small"
                >

                    <i
                        class="bi bi-arrow-left me-1"
                    ></i>

                    Back to Login

                </a>

            </div>


        </div>

    </div>

</div>



<script>
(function () {
    let remaining = <?= (int)$otp_resend_wait ?>;
    const button = document.getElementById('resendOtpBtn');
    const label = document.getElementById('resendOtpLabel');
    const timer = document.getElementById('resendOtpTimer');
    if (!button || !label || !timer || remaining <= 0) return;
    const tick = () => {
        if (remaining <= 0) {
            button.disabled = false;
            button.classList.remove('is-disabled');
            label.textContent = 'Resend verification code';
            timer.textContent = 'We will send a fresh 6-digit code to your email.';
            return;
        }
        button.disabled = true;
        button.classList.add('is-disabled');
        label.textContent = 'Resend available soon';
        timer.textContent = 'Please wait ' + remaining + 's';
        remaining -= 1;
        setTimeout(tick, 1000);
    };
    tick();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>