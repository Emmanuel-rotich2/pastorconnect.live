<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';

/*
|--------------------------------------------------------------------------
| Redirect Already Logged-In Members
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['member_id'])) {
    redirect('/member/dashboard');
}

/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';
$registration_success = false;
$membership_no = '';

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $n   = trim($_POST['full_name'] ?? '');
    $p   = trim($_POST['phone'] ?? '');
    $em  = trim($_POST['email'] ?? '');
    $g   = trim($_POST['gender'] ?? '');
    $pw  = $_POST['password'] ?? '';
    $cpw = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        strlen($n) < 3 ||
        strlen($p) < 7 ||
        !filter_var($em, FILTER_VALIDATE_EMAIL) ||
        strlen($pw) < 8 ||
        !preg_match('/[A-Za-z]/', $pw) ||
        !preg_match('/[0-9]/', $pw)
    ) {

        $error =
            'Please provide valid registration details. ' .
            'Your password must contain at least 8 characters, ' .
            'including at least one letter and one number.';

    } elseif ($pw !== $cpw) {

        $error =
            'The passwords do not match. Please check and try again.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Generate Membership Number
            |--------------------------------------------------------------------------
            */

            $mem =
                'FGCK-' .
                date('Y') .
                '-' .
                strtoupper(bin2hex(random_bytes(3)));

            /*
            |--------------------------------------------------------------------------
            | Create Member
            |--------------------------------------------------------------------------
            */

            $q = $pdo->prepare(
                'INSERT INTO members
                (
                    membership_no,
                    full_name,
                    phone,
                    email,
                    gender,
                    password_hash
                )
                VALUES (?, ?, ?, ?, ?, ?)'
            );

            $q->execute([
                $mem,
                $n,
                $p,
                $em,
                $g,
                password_hash($pw, PASSWORD_DEFAULT)
            ]);

            /*
            |--------------------------------------------------------------------------
            | Secure Session
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION['member_id'] = (int) $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Membership Number
            |--------------------------------------------------------------------------
            */

            $membership_no = $mem;

            /*
            |--------------------------------------------------------------------------
            | Activity Log
            |--------------------------------------------------------------------------
            */

            log_activity(
                $pdo,
                $_SESSION['member_id'],
                null,
                'registration',
                'Member account created'
            );

            /*
            |--------------------------------------------------------------------------
            | Welcome Email
            |--------------------------------------------------------------------------
            */

            try {

                $loginUrl = rtrim(FGCK_RUNTIME_APP_URL, '/') . '/auth/login';

                $welcomeBody =
                    $welcomeBody =
    '<div style="font-family:Arial,Helvetica,sans-serif;color:#17263a;line-height:1.7;">' .

    '<p style="font-size:16px;margin-top:0;">' .
    'Praise The Lord <strong>' . e($n) . '</strong>,' .
    '</p>' .

    '<p>' .
    'Welcome to <strong>FGCK Makutano West Joyland</strong>! ' .
    'We are delighted to have you join our church family. ' .
    'Your member account has been created successfully.' .
    '</p>' .

    '<div style="margin:24px 0;padding:20px;background:#f4f8fc;border:1px solid #dbe8f3;border-radius:14px;text-align:center;">' .

    '<div style="font-size:11px;color:#738195;text-transform:uppercase;letter-spacing:1px;margin-bottom:7px;">' .
    'Your Membership Number' .
    '</div>' .

    '<div style="font-size:22px;font-weight:800;color:#1261a0;letter-spacing:1px;">' .
    e($mem) .
    '</div>' .

    '</div>' .

    '<p>' .
    'You can now access your Member Portal using the email address and password you selected during registration.' .
    '</p>' .

    '<div style="text-align:center;margin:28px 0;">' .

    '<a href="' . e($loginUrl) . '" ' .
    'style="display:inline-block;padding:13px 23px;background:#1261a0;color:#ffffff;text-decoration:none;border-radius:9px;font-weight:700;">' .
    'Open Member Portal' .
    '</a>' .

    '</div>' .

    '<p style="font-size:13px;color:#738195;">' .
    'May God richly bless you as you continue to grow, serve and walk with Him.' .
    '</p>' .

    '<div style="margin:26px 0 8px;padding:16px 18px;background:#fff8e6;border-left:4px solid #d9a514;border-radius:8px;text-align:center;">' .

    '<p style="margin:0;color:#17263a;font-size:14px;font-style:italic;font-weight:700;">' .
    'As members of FGCK Joyland, we are' .
    '<br>' .
    '<span style="color:#1261a0;font-size:17px;font-weight:800;letter-spacing:.3px;">' .
    '“Perfected To Influence The World”' .
    '</span>' .
    '</p>' .

    '</div>' .

    '</div>';

                if (!send_system_email(
                    $em,
                    $n,
                    'Welcome to FGCK Makutano West Joyland',
                    $welcomeBody
                )) {

                    email_log(
                        'WELCOME EMAIL FAILED for newly registered member ' .
                        $em .
                        ' | Membership: ' .
                        $mem
                    );
                }

            } catch (Throwable $emailException) {

                email_log(
                    'WELCOME EMAIL EXCEPTION for ' .
                    $em .
                    ': ' .
                    $emailException->getMessage()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $registration_success = true;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $error =
                    'That email address or phone number is already registered. ' .
                    'Please use different details or sign in to your existing account.';

            } else {

                $error =
                    'Registration could not be completed at this time. ' .
                    'Please try again.';
            }
        }
    }
}

$page_title = 'Create Member Account';

require __DIR__ . '/../includes/header.php';

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

:root {
    --fgck-primary: #1261a0;
    --fgck-primary-dark: #0b4777;
    --fgck-primary-light: #2f8fd1;

    --fgck-navy: #071a33;
    --fgck-navy-2: #0d2d4d;

    --fgck-gold: #f4c95d;
    --fgck-gold-light: #ffe7a3;

    --fgck-text: #17263a;
    --fgck-muted: #718197;

    --fgck-border: #dce6ef;
    --fgck-soft: #f5f9fd;

    --fgck-danger: #b42318;
    --fgck-success: #087443;

    --fgck-shadow:
        0 35px 90px rgba(7, 26, 51, .15),
        0 10px 30px rgba(7, 26, 51, .07);
}


/* ==========================================================================
   PAGE
   ========================================================================== */

.fgck-register-page {

    min-height: calc(100vh - 70px);

    padding: 42px 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    position: relative;

    overflow: hidden;

    background:
        radial-gradient(
            circle at 7% 10%,
            rgba(18, 97, 160, .12),
            transparent 28%
        ),
        radial-gradient(
            circle at 94% 88%,
            rgba(244, 201, 93, .15),
            transparent 28%
        ),
        linear-gradient(
            135deg,
            #f9fcff 0%,
            #eef5fb 100%
        );
}


/* Decorative circles */

.fgck-register-page::before,
.fgck-register-page::after {

    content: "";

    position: absolute;

    border-radius: 50%;

    pointer-events: none;
}

.fgck-register-page::before {

    width: 560px;
    height: 560px;

    right: -310px;
    top: -320px;

    border: 1px solid rgba(18, 97, 160, .10);

    box-shadow:
        0 0 0 80px rgba(18, 97, 160, .018),
        0 0 0 160px rgba(18, 97, 160, .012);
}

.fgck-register-page::after {

    width: 440px;
    height: 440px;

    left: -280px;
    bottom: -300px;

    border: 1px solid rgba(244, 201, 93, .20);
}


/* ==========================================================================
   MAIN CARD
   ========================================================================== */

.fgck-register-card {

    width: 100%;
    max-width: 1160px;

    display: grid;

    grid-template-columns: 400px minmax(0, 1fr);

    overflow: hidden;

    border-radius: 30px;

    border: 1px solid rgba(255, 255, 255, .9);

    background: #ffffff;

    box-shadow: var(--fgck-shadow);

    position: relative;

    z-index: 2;

    animation: fgckCardIn .65s ease both;
}

@keyframes fgckCardIn {

    from {
        opacity: 0;
        transform: translateY(18px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* ==========================================================================
   LEFT BRAND PANEL
   ========================================================================== */

.fgck-brand {

    position: relative;

    overflow: hidden;

    padding: 50px 38px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    text-align: center;

    color: #ffffff;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(93, 183, 245, .24),
            transparent 28%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(244, 201, 93, .15),
            transparent 30%
        ),
        linear-gradient(
            145deg,
            #06182e 0%,
            #0b3154 48%,
            #1261a0 100%
        );
}


/* Decorative rings */

.fgck-brand::before {

    content: "";

    position: absolute;

    width: 390px;
    height: 390px;

    left: -225px;
    top: -235px;

    border: 1px solid rgba(93, 183, 245, .18);

    border-radius: 50%;
}

.fgck-brand::after {

    content: "";

    position: absolute;

    width: 450px;
    height: 450px;

    right: -285px;
    bottom: -275px;

    border: 1px solid rgba(244, 201, 93, .18);

    border-radius: 50%;
}


/* Brand content */

.fgck-brand-content {

    position: relative;

    z-index: 2;
}


/* ==========================================================================
   LOGO
   ========================================================================== */

.fgck-logo {

    width: 120px;
    height: 120px;

    margin: 0 auto 22px;

    padding: 12px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #edf6ff
        );

    box-shadow:
        0 20px 45px rgba(0, 0, 0, .26),
        0 0 0 7px rgba(255, 255, 255, .06);

    animation: fgckLogoFloat 5s ease-in-out infinite;
}

@keyframes fgckLogoFloat {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-5px);
    }
}

.fgck-logo img {

    width: 100%;
    height: 100%;

    object-fit: contain;
}


/* ==========================================================================
   BRAND TYPOGRAPHY
   ========================================================================== */

.fgck-brand h1 {

    margin: 0;

    color: #ffffff;

    font-size: 29px;

    line-height: 1.2;

    font-weight: 850;

    letter-spacing: -.6px;
}

.fgck-brand h1 span {

    color: var(--fgck-gold);
}

.fgck-tagline {

    margin-top: 8px;

    color: var(--fgck-gold-light);

    font-size: 10px;

    font-weight: 900;

    letter-spacing: 2.3px;

    text-transform: uppercase;
}


.fgck-divider {

    width: 58px;
    height: 3px;

    margin: 15px auto 17px;

    border-radius: 99px;

    background:
        linear-gradient(
            90deg,
            #5db7f5,
            var(--fgck-gold)
        );
}


.fgck-brand p {

    max-width: 310px;

    margin: 0 auto 28px;

    color: rgba(255, 255, 255, .78);

    font-size: 13px;

    line-height: 1.75;
}


/* ==========================================================================
   BENEFITS
   ========================================================================== */

.fgck-benefits {

    display: grid;

    gap: 10px;

    text-align: left;

    max-width: 315px;

    margin: auto;
}

.fgck-benefit {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px 11px;

    border:
        1px solid
        rgba(255, 255, 255, .09);

    border-radius: 13px;

    background:
        rgba(255, 255, 255, .045);

    color:
        rgba(255, 255, 255, .91);

    font-size: 11px;

    transition:
        transform .25s ease,
        background .25s ease,
        border-color .25s ease;
}

.fgck-benefit:hover {

    transform: translateX(4px);

    background: rgba(255, 255, 255, .075);

    border-color:
        rgba(244, 201, 93, .25);
}


.fgck-benefit-icon {

    width: 32px;
    height: 32px;

    flex: 0 0 32px;

    display: grid;
    place-items: center;

    border-radius: 9px;

    color: var(--fgck-gold-light);

    background:
        rgba(244, 201, 93, .10);

    border:
        1px solid
        rgba(244, 201, 93, .20);
}


/* ==========================================================================
   FORM
   ========================================================================== */

.fgck-form {

    padding: 48px 58px 40px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    min-width: 0;
}


/* ==========================================================================
   HEADING
   ========================================================================== */

.fgck-eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 9px;

    color: var(--fgck-primary);

    font-size: 10px;

    font-weight: 900;

    letter-spacing: 1.7px;

    text-transform: uppercase;
}

.fgck-eyebrow::before {

    content: "";

    width: 23px;
    height: 2px;

    border-radius: 99px;

    background: var(--fgck-gold);
}


.fgck-heading h2 {

    margin: 0 0 8px;

    color: var(--fgck-text);

    font-size: 30px;

    font-weight: 850;

    letter-spacing: -.7px;
}

.fgck-heading p {

    margin: 0 0 25px;

    color: var(--fgck-muted);

    font-size: 12.5px;

    line-height: 1.7;
}


/* ==========================================================================
   ERROR
   ========================================================================== */

.fgck-error {

    display: flex;

    gap: 10px;

    align-items: flex-start;

    padding: 13px 15px;

    margin-bottom: 18px;

    border: 1px solid #ffd7d7;

    border-radius: 13px;

    background: #fff7f7;

    color: var(--fgck-danger);

    font-size: 12px;

    line-height: 1.55;
}

.fgck-error i {

    font-size: 16px;

    margin-top: 1px;
}


/* ==========================================================================
   FIELDS
   ========================================================================== */

.fgck-field {

    margin-bottom: 15px;
}

.fgck-label {

    display: flex;

    justify-content: space-between;

    gap: 10px;

    margin: 0 0 7px;

    color: #34445b;

    font-size: 11px;

    font-weight: 800;
}

.fgck-required {

    color: #c0392b;
}


.fgck-input-wrap {

    position: relative;
}


/* Input icon */

.fgck-icon {

    position: absolute;

    left: 14px;
    top: 50%;

    transform: translateY(-50%);

    color: #8b9aaa;

    font-size: 15px;

    z-index: 2;

    pointer-events: none;

    transition: color .2s ease;
}


/* Inputs */

.fgck-input,
.fgck-select {

    width: 100%;

    height: 49px;

    border: 1px solid var(--fgck-border);

    border-radius: 12px;

    outline: none;

    background: #f8fbfe;

    color: var(--fgck-text);

    font-size: 12.5px;

    padding: 0 42px;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease,
        transform .2s ease;
}

.fgck-select {

    padding-right: 36px;

    cursor: pointer;
}

.fgck-input::placeholder {

    color: #a0acb9;
}


.fgck-input:hover,
.fgck-select:hover {

    background: #ffffff;

    border-color: #c4d3e0;
}


.fgck-input:focus,
.fgck-select:focus {

    background: #ffffff;

    border-color: var(--fgck-primary);

    box-shadow:
        0 0 0 4px rgba(18, 97, 160, .09);
}

.fgck-input:focus ~ .fgck-icon {

    color: var(--fgck-primary);
}


/* Password */

.fgck-password {

    padding-right: 50px;
}


.fgck-toggle {

    position: absolute;

    right: 7px;
    top: 50%;

    transform: translateY(-50%);

    width: 35px;
    height: 35px;

    border: 0;

    border-radius: 8px;

    background: transparent;

    color: #7d8b9a;

    display: grid;

    place-items: center;

    cursor: pointer;

    transition: .2s;
}

.fgck-toggle:hover {

    background: #edf6fc;

    color: var(--fgck-primary);
}


/* ==========================================================================
   GRID
   ========================================================================== */

.fgck-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 0 14px;
}

.fgck-grid .fgck-field {

    min-width: 0;
}


/* ==========================================================================
   PASSWORD STRENGTH
   ========================================================================== */

.fgck-strength {

    margin-top: 8px;
}

.fgck-strength-bar {

    height: 4px;

    width: 100%;

    overflow: hidden;

    border-radius: 99px;

    background: #e7edf3;
}

.fgck-strength-fill {

    width: 0;
    height: 100%;

    border-radius: 99px;

    background:
        linear-gradient(
            90deg,
            #1683d8,
            #5db7f5,
            #f4c95d
        );

    transition: width .25s ease;
}


.fgck-strength-text {

    margin-top: 6px;

    color: #8997a6;

    font-size: 9.5px;
}


.fgck-requirements {

    display: flex;

    flex-wrap: wrap;

    gap: 5px 13px;

    margin-top: 7px;
}

.fgck-requirement {

    color: #8a97a6;

    font-size: 9.5px;
}

.fgck-requirement.valid {

    color: var(--fgck-success);
}

.fgck-requirement i {

    margin-right: 3px;
}


/* ==========================================================================
   SUBMIT
   ========================================================================== */

.fgck-submit {

    width: 100%;

    height: 51px;

    margin-top: 3px;

    border: 0;

    border-radius: 12px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            var(--fgck-primary-dark),
            var(--fgck-primary),
            var(--fgck-primary-light)
        );

    font-size: 12.5px;

    font-weight: 850;

    letter-spacing: .1px;

    box-shadow:
        0 12px 28px
        rgba(18, 97, 160, .23);

    cursor: pointer;

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        filter .25s ease;
}

.fgck-submit:hover {

    transform: translateY(-2px);

    filter: brightness(1.04);

    box-shadow:
        0 16px 32px
        rgba(18, 97, 160, .30);
}

.fgck-submit:active {

    transform: translateY(0);
}

.fgck-submit:disabled {

    opacity: .72;

    cursor: not-allowed;

    transform: none;
}


/* ==========================================================================
   LOGIN / BACK
   ========================================================================== */

.fgck-login {

    text-align: center;

    margin-top: 18px;

    padding-top: 16px;

    border-top: 1px solid #edf1f5;

    color: #7b8795;

    font-size: 11px;
}

.fgck-login a,
.fgck-back a {

    color: var(--fgck-primary);

    font-weight: 850;

    text-decoration: none;
}

.fgck-login a:hover,
.fgck-back a:hover {

    text-decoration: underline;
}


.fgck-back {

    text-align: center;

    margin-top: 10px;
}

.fgck-back a {

    color: #8794a3;

    font-size: 10.5px;
}


/* ==========================================================================
   SECURITY
   ========================================================================== */

.fgck-security {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 5px;

    margin-top: 13px;

    color: #9aa7b5;

    font-size: 9.5px;
}

.fgck-security i {

    color: var(--fgck-primary);
}


/* ==========================================================================
   SUCCESS POPUP
   ========================================================================== */

.joyland-registration-popup {

    border-radius: 24px !important;

    padding: 32px !important;

    max-width: 450px !important;

    box-shadow:
        0 30px 80px
        rgba(7, 26, 51, .25) !important;
}

.joyland-registration-popup .swal2-title {

    color: var(--fgck-text) !important;

    font-size: 24px !important;

    font-weight: 850 !important;
}

.joyland-registration-popup .swal2-timer-progress-bar {

    background:
        linear-gradient(
            90deg,
            var(--fgck-primary),
            #5db7f5,
            var(--fgck-gold)
        ) !important;
}


/* ==========================================================================
   TABLET
   ========================================================================== */

@media (max-width: 900px) {

    .fgck-register-card {

        max-width: 680px;

        grid-template-columns: 1fr;
    }

    .fgck-brand {

        padding: 38px 28px;
    }

    .fgck-benefits {

        display: none;
    }

    .fgck-form {

        padding: 40px 42px 34px;
    }
}


/* ==========================================================================
   MOBILE
   ========================================================================== */

@media (max-width: 575px) {

    .fgck-register-page {

        min-height: auto;

        padding: 12px 8px;
    }

    .fgck-register-card {

        border-radius: 22px;
    }

    .fgck-brand {

        padding: 30px 20px;
    }

    .fgck-logo {

        width: 88px;
        height: 88px;

        padding: 9px;

        margin-bottom: 16px;
    }

    .fgck-brand h1 {

        font-size: 23px;
    }

    .fgck-tagline {

        font-size: 9px;

        letter-spacing: 1.8px;
    }

    .fgck-brand p {

        font-size: 12px;

        margin-bottom: 0;
    }

    .fgck-form {

        padding: 30px 20px 26px;
    }

    .fgck-heading h2 {

        font-size: 25px;
    }

    .fgck-heading p {

        font-size: 12px;

        margin-bottom: 22px;
    }

    .fgck-grid {

        grid-template-columns: 1fr;

        gap: 0;
    }

    .fgck-input,
    .fgck-select {

        height: 48px;
    }

    .fgck-submit {

        height: 50px;
    }
}


/* ==========================================================================
   VERY SMALL PHONES
   ========================================================================== */

@media (max-width: 360px) {

    .fgck-form {

        padding: 25px 15px;
    }

    .fgck-brand {

        padding: 26px 15px;
    }

    .fgck-brand h1 {

        font-size: 21px;
    }

    .fgck-heading h2 {

        font-size: 23px;
    }
}


/* ==========================================================================
   REDUCED MOTION
   ========================================================================== */

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {

        scroll-behavior: auto !important;

        animation: none !important;

        transition: none !important;
    }
}

</style>

<div class="fgck-register-page">

```
<main
    class="fgck-register-card"
    aria-label="FGCK Joyland member registration"
>

    <!-- ==============================================================
         BRAND PANEL
         ============================================================== -->

    <section class="fgck-brand">

        <div class="fgck-brand-content">

            <div class="fgck-logo">

                <img
                    src="/assets/images/full_gospel_churches_logo.png"
                    alt="FGCK Joyland Church Logo"
                >

            </div>


            <h1>
                Join <span>FGCK Joyland</span>
            </h1>

            <div class="fgck-tagline">
                Perfected to Influence
            </div>


            <div class="fgck-divider"></div>


            <p>
                Create your secure member account and stay connected
                with the ministry through the FGCK Joyland digital
                portal.
            </p>


            <div class="fgck-benefits">

                <div class="fgck-benefit">

                    <span class="fgck-benefit-icon">
                        <i class="bi bi-calendar-check"></i>
                    </span>

                    <span>
                        Book pastoral appointments with ease
                    </span>

                </div>


                <div class="fgck-benefit">

                    <span class="fgck-benefit-icon">
                        <i class="bi bi-person-heart"></i>
                    </span>

                    <span>
                        Stay connected with pastoral ministry
                    </span>

                </div>


                <div class="fgck-benefit">

                    <span class="fgck-benefit-icon">
                        <i class="bi bi-stars"></i>
                    </span>

                    <span>
                        Be part of a connected church community
                    </span>

                </div>

            </div>

        </div>

    </section>


    <!-- ==============================================================
         REGISTRATION FORM
         ============================================================== -->

    <section class="fgck-form">


        <div class="fgck-heading">

            <div class="fgck-eyebrow">
                Perfected to Influence
            </div>

            <h2>
                Create Your Account
            </h2>

            <p>
                Enter your details below to become a member of
                the FGCK Joyland digital community.
            </p>

        </div>


        <?php if ($error): ?>

            <div
                class="fgck-error"
                role="alert"
            >

                <i class="bi bi-exclamation-circle-fill"></i>

                <div>
                    <?= e($error) ?>
                </div>

            </div>

        <?php endif; ?>


        <form
            method="post"
            id="registrationForm"
            novalidate
        >

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- FULL NAME -->

            <div class="fgck-field">

                <label
                    class="fgck-label"
                    for="fullName"
                >

                    <span>
                        Full Name
                        <span class="fgck-required">*</span>
                    </span>

                </label>


                <div class="fgck-input-wrap">

                    <i class="bi bi-person fgck-icon"></i>

                    <input
                        id="fullName"
                        class="fgck-input"
                        name="full_name"
                        type="text"
                        placeholder="Enter your full name"
                        value="<?= e($_POST['full_name'] ?? '') ?>"
                        autocomplete="name"
                        minlength="3"
                        required
                    >

                </div>

            </div>


            <!-- PHONE + GENDER -->

            <div class="fgck-grid">


                <div class="fgck-field">

                    <label
                        class="fgck-label"
                        for="phone"
                    >

                        <span>
                            Phone Number
                            <span class="fgck-required">*</span>
                        </span>

                    </label>


                    <div class="fgck-input-wrap">

                        <i class="bi bi-telephone fgck-icon"></i>

                        <input
                            id="phone"
                            class="fgck-input"
                            name="phone"
                            type="tel"
                            placeholder="e.g. 0712345678"
                            value="<?= e($_POST['phone'] ?? '') ?>"
                            autocomplete="tel"
                            minlength="7"
                            required
                        >

                    </div>

                </div>


                <div class="fgck-field">

                    <label
                        class="fgck-label"
                        for="gender"
                    >

                        <span>
                            Gender
                        </span>

                    </label>


                    <div class="fgck-input-wrap">

                        <i class="bi bi-people fgck-icon"></i>

                        <select
                            id="gender"
                            class="fgck-select"
                            name="gender"
                        >

                            <option value="">
                                Prefer not to say
                            </option>

                            <option
                                value="Male"
                                <?= (
                                    ($_POST['gender'] ?? '') === 'Male'
                                ) ? 'selected' : '' ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= (
                                    ($_POST['gender'] ?? '') === 'Female'
                                ) ? 'selected' : '' ?>
                            >
                                Female
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- EMAIL -->

            <div class="fgck-field">

                <label
                    class="fgck-label"
                    for="email"
                >

                    <span>
                        Email Address
                        <span class="fgck-required">*</span>
                    </span>

                </label>


                <div class="fgck-input-wrap">

                    <i class="bi bi-envelope fgck-icon"></i>

                    <input
                        id="email"
                        class="fgck-input"
                        type="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="fgck-field">

                <label
                    class="fgck-label"
                    for="password"
                >

                    <span>
                        Create Password
                        <span class="fgck-required">*</span>
                    </span>

                </label>


                <div class="fgck-input-wrap">

                    <i class="bi bi-lock fgck-icon"></i>

                    <input
                        id="password"
                        class="fgck-input fgck-password"
                        type="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Create a secure password"
                        required
                    >


                    <button
                        type="button"
                        class="fgck-toggle"
                        id="passwordToggle"
                        aria-label="Show password"
                    >

                        <i
                            class="bi bi-eye"
                            id="passwordIcon"
                        ></i>

                    </button>

                </div>


                <div class="fgck-strength">

                    <div class="fgck-strength-bar">

                        <div
                            class="fgck-strength-fill"
                            id="passwordStrengthFill"
                        ></div>

                    </div>


                    <div
                        class="fgck-strength-text"
                        id="passwordStrengthText"
                    >
                        Use at least 8 characters, including a
                        letter and a number.
                    </div>


                    <div class="fgck-requirements">

                        <span
                            class="fgck-requirement"
                            id="lengthRequirement"
                        >
                            <i class="bi bi-circle"></i>
                            8+ characters
                        </span>


                        <span
                            class="fgck-requirement"
                            id="letterRequirement"
                        >
                            <i class="bi bi-circle"></i>
                            Letter
                        </span>


                        <span
                            class="fgck-requirement"
                            id="numberRequirement"
                        >
                            <i class="bi bi-circle"></i>
                            Number
                        </span>

                    </div>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="fgck-field">

                <label
                    class="fgck-label"
                    for="confirmPassword"
                >

                    <span>
                        Confirm Password
                        <span class="fgck-required">*</span>
                    </span>

                </label>


                <div class="fgck-input-wrap">

                    <i class="bi bi-shield-lock fgck-icon"></i>

                    <input
                        id="confirmPassword"
                        class="fgck-input fgck-password"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        required
                    >


                    <button
                        type="button"
                        class="fgck-toggle"
                        id="confirmPasswordToggle"
                        aria-label="Show password"
                    >

                        <i
                            class="bi bi-eye"
                            id="confirmPasswordIcon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="fgck-submit"
                id="registerButton"
            >

                <span id="registerButtonContent">

                    <i class="bi bi-person-plus-fill me-2"></i>

                    Create Member Account

                </span>

            </button>

        </form>


        <!-- LOGIN -->

        <div class="fgck-login">

            Already registered?

            <a href="/auth/login">
                Sign in to your account
            </a>

        </div>


        <!-- BACK -->

        <div class="fgck-back">

            <a href="/">

                <i class="bi bi-arrow-left me-1"></i>

                Back to FGCK Joyland Portal

            </a>

        </div>


        <!-- SECURITY -->

        <div class="fgck-security">

            <i class="bi bi-shield-lock-fill"></i>

            Secure registration • Your password is protected

        </div>


    </section>

</main>
```

</div>

<?php if ($registration_success): ?>

<script>

Swal.fire({

    icon: 'success',

    title: 'Welcome to FGCK Joyland!',

    html: `

        <div style="
            margin-top:8px;
            line-height:1.7;
        ">

            <div style="
                width:70px;
                height:70px;
                margin:0 auto 15px;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                background:#edf7ff;
                color:#1261a0;
                font-size:29px;
                box-shadow:0 8px 20px rgba(18,97,160,.10);
            ">

                <i class="bi bi-person-check-fill"></i>

            </div>


            <strong style="
                color:#1261a0;
                font-size:18px;
                display:block;
                margin-bottom:6px;
            ">
                Account Created Successfully
            </strong>


            <p style="
                margin:0 0 12px;
                color:#66768a;
                font-size:13px;
            ">
                Your FGCK Joyland member account is ready.
            </p>


            <div style="
                display:inline-block;
                padding:9px 15px;
                border-radius:9px;
                background:#f1f8fe;
                border:1px solid #d9ecfa;
                color:#1261a0;
                font-size:12px;
                font-weight:800;
            ">

                Membership No:
                <?= e($membership_no) ?>

            </div>


            <small style="
                display:block;
                margin-top:13px;
                color:#9aa8b6;
                font-size:11px;
            ">

                Opening your Member Dashboard...

            </small>

        </div>

    `,

    showConfirmButton: false,

    timer: 3500,

    timerProgressBar: true,

    allowOutsideClick: false,

    allowEscapeKey: false,

    customClass: {
        popup: 'joyland-registration-popup'
    }

}).then(() => {

    window.location.href = '/member/dashboard';

});

</script>

<?php endif; ?>

<script>

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | Password Visibility
    |--------------------------------------------------------------------------
    */

    function setupPasswordToggle(
        inputId,
        buttonId,
        iconId
    ) {

        const input =
            document.getElementById(inputId);

        const button =
            document.getElementById(buttonId);

        const icon =
            document.getElementById(iconId);


        if (!input || !button || !icon) {
            return;
        }


        button.addEventListener(
            'click',
            function () {

                const isHidden =
                    input.type === 'password';


                input.type =
                    isHidden ? 'text' : 'password';


                icon.className =
                    isHidden
                        ? 'bi bi-eye-slash'
                        : 'bi bi-eye';


                button.setAttribute(
                    'aria-label',
                    isHidden
                        ? 'Hide password'
                        : 'Show password'
                );

            }
        );

    }


    setupPasswordToggle(
        'password',
        'passwordToggle',
        'passwordIcon'
    );


    setupPasswordToggle(
        'confirmPassword',
        'confirmPasswordToggle',
        'confirmPasswordIcon'
    );


    /*
    |--------------------------------------------------------------------------
    | Password Strength
    |--------------------------------------------------------------------------
    */

    const password =
        document.getElementById('password');

    const confirmPassword =
        document.getElementById('confirmPassword');

    const strengthFill =
        document.getElementById(
            'passwordStrengthFill'
        );

    const strengthText =
        document.getElementById(
            'passwordStrengthText'
        );

    const lengthRequirement =
        document.getElementById(
            'lengthRequirement'
        );

    const letterRequirement =
        document.getElementById(
            'letterRequirement'
        );

    const numberRequirement =
        document.getElementById(
            'numberRequirement'
        );


    function setRequirement(
        element,
        valid
    ) {

        if (!element) return;


        const icon =
            element.querySelector('i');


        element.classList.toggle(
            'valid',
            valid
        );


        if (icon) {

            icon.className =
                valid
                    ? 'bi bi-check-circle-fill'
                    : 'bi bi-circle';
        }

    }


    function updatePasswordStrength() {

        if (!password) return;


        const value =
            password.value;


        const hasLength =
            value.length >= 8;

        const hasLetter =
            /[A-Za-z]/.test(value);

        const hasNumber =
            /[0-9]/.test(value);

        const hasSpecial =
            /[^A-Za-z0-9]/.test(value);

        const isLong =
            value.length >= 12;


        setRequirement(
            lengthRequirement,
            hasLength
        );

        setRequirement(
            letterRequirement,
            hasLetter
        );

        setRequirement(
            numberRequirement,
            hasNumber
        );


        let score = 0;


        if (hasLength) score++;

        if (hasLetter) score++;

        if (hasNumber) score++;

        if (hasSpecial || isLong) score++;


        const widths = [
            0,
            25,
            50,
            75,
            100
        ];


        strengthFill.style.width =
            widths[score] + '%';


        if (!value) {

            strengthText.textContent =
                'Use at least 8 characters, including a letter and a number.';

        } else if (score <= 1) {

            strengthText.textContent =
                'Password is weak.';

        } else if (score === 2) {

            strengthText.textContent =
                'Password is getting stronger.';

        } else if (score === 3) {

            strengthText.textContent =
                'Good password.';

        } else {

            strengthText.textContent =
                'Strong password.';

        }

    }


    if (password) {

        password.addEventListener(
            'input',
            updatePasswordStrength
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    function updateConfirmState() {

        if (!confirmPassword || !password) {
            return;
        }


        if (!confirmPassword.value) {

            confirmPassword.style.borderColor = '';

            return;
        }


        confirmPassword.style.borderColor =
            password.value === confirmPassword.value
                ? '#1683d8'
                : '#dc3545';

    }


    if (confirmPassword) {

        confirmPassword.addEventListener(
            'input',
            updateConfirmState
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Form Submission
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            'registrationForm'
        );

    const button =
        document.getElementById(
            'registerButton'
        );

    const buttonContent =
        document.getElementById(
            'registerButtonContent'
        );


    if (form) {

        form.addEventListener(
            'submit',
            function (event) {


                if (!form.checkValidity()) {

                    event.preventDefault();

                    form.reportValidity();

                    return;
                }


                if (
                    password &&
                    confirmPassword &&
                    password.value !==
                    confirmPassword.value
                ) {

                    event.preventDefault();


                    Swal.fire({

                        icon: 'warning',

                        title: 'Passwords Do Not Match',

                        text:
                            'Please make sure both password fields contain the same password.',

                        confirmButtonText:
                            'Check Passwords',

                        confirmButtonColor:
                            '#1261a0'

                    });


                    confirmPassword.focus();

                    return;
                }


                if (button) {

                    button.disabled = true;

                }


                if (buttonContent) {

                    buttonContent.innerHTML = `

                        <span
                            class="spinner-border spinner-border-sm me-2"
                            role="status"
                            aria-hidden="true"
                        ></span>

                        Creating Your Account...

                    `;

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Initial Focus
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const fullName =
                document.getElementById(
                    'fullName'
                );


            if (
                fullName &&
                !fullName.value
            ) {

                fullName.focus();

            }

        }
    );

})();

</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
