
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

    }


    elseif ($pw !== $cpw) {

        $error =
            'The passwords do not match. Please check and try again.';

    }


    else {

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
                strtoupper(
                    bin2hex(random_bytes(3))
                );


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

                password_hash(
                    $pw,
                    PASSWORD_DEFAULT
                )

            ]);


            /*
            |--------------------------------------------------------------------------
            | Secure Session
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);


            $_SESSION['member_id'] =
                (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Membership Number
            |--------------------------------------------------------------------------
            */

            $membership_no =
                $mem;


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
            |
            | Send the welcome email only after the member has been successfully
            | inserted into the database. Email failure must not undo a successful
            | registration; it is logged by the email subsystem for troubleshooting.
            */

            try {

                $loginUrl = rtrim(FGCK_RUNTIME_APP_URL, '/') . '/auth/login';

                $welcomeBody =
                    '<p style="font-size:16px; margin-top:0;">' .
                    'Praise The Lord <strong>' . e($n) . '</strong>,</p>' .

                    '<p>' .
                    'Welcome to <strong>FGCK Makutano West Joyland</strong>! ' .
                    'Your member account has been created successfully. We are delighted to have you join us.' .
                    '</p>' .

                    '<div style="margin:22px 0; padding:16px 18px; background:#f1f8fe; border:1px solid #d9ecfa; border-radius:12px;">' .
                    '<div style="font-size:12px; color:#66768a; margin-bottom:5px;">Your Membership Number</div>' .
                    '<div style="font-size:20px; font-weight:800; color:#1261a0; letter-spacing:.5px;">' . e($mem) . '</div>' .
                    '</div>' .

                    '<p>' .
                    'You can now access your Member Portal using the email address and password you chose during registration.' .
                    '</p>' .

                    '<p style="margin:25px 0;">' .
                    '<a href="https://pastorconnect.live" style="display:inline-block; padding:12px 20px; background:#1261a0; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">' .
                    'Open Member Portal' .
                    '</a>' .
                    '</p>' ;
                    
                    
                    
               
                if (!send_system_email(
                    $em,
                    $n,
                    'Welcome to FGCK Makutano West Joyland',
                    $welcomeBody
                )) {
                    email_log('WELCOME EMAIL FAILED for newly registered member ' . $em . ' | Membership: ' . $mem);
                }

            } catch (Throwable $emailException) {

                email_log(
                    'WELCOME EMAIL EXCEPTION for ' . $em . ': ' .
                    $emailException->getMessage()
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $registration_success =
                true;

        }


        catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $error =
                    'That email address or phone number is already registered. ' .
                    'Please use different details or sign in to your existing account.';

            }

            else {

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
:root{
    --fgck-blue:#6d28d9;
    --fgck-blue-2:#8b5cf6;
    --fgck-navy:#100b2e;
    --fgck-gold:#f6c453;
    --fgck-text:#17263a;
    --fgck-muted:#718197;
    --fgck-border:#dce6ef;
    --fgck-soft:#f5f9fd;
    --fgck-danger:#b42318;
    --fgck-success:#087443;
}

.fgck-register-page{
    min-height:calc(100vh - 70px);
    padding:34px 18px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at 8% 10%,rgba(22,131,216,.12),transparent 28%),
        radial-gradient(circle at 92% 88%,rgba(244,201,93,.13),transparent 28%),
        linear-gradient(135deg,#f8fbff 0%,#eef5fb 100%);
}

.fgck-register-page::before,
.fgck-register-page::after{
    content:"";
    position:absolute;
    border-radius:50%;
    pointer-events:none;
}
.fgck-register-page::before{
    width:520px;height:520px;
    right:-290px;top:-300px;
    border:1px solid rgba(18,97,160,.10);
    box-shadow:0 0 0 80px rgba(18,97,160,.018),
               0 0 0 160px rgba(18,97,160,.012);
}
.fgck-register-page::after{
    width:420px;height:420px;
    left:-260px;bottom:-280px;
    border:1px solid rgba(244,201,93,.18);
}

.fgck-register-card{
    width:100%;
    max-width:1120px;
    display:grid;
    grid-template-columns:390px minmax(0,1fr);
    overflow:hidden;
    border:1px solid rgba(255,255,255,.8);
    border-radius:28px;
    background:#fff;
    box-shadow:0 30px 80px rgba(7,26,51,.15),0 8px 24px rgba(7,26,51,.06);
    position:relative;
    z-index:2;
}

.fgck-brand{
    position:relative;
    overflow:hidden;
    padding:48px 38px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    color:#fff;
    text-align:center;
    background:
        radial-gradient(circle at 15% 12%,rgba(93,183,245,.25),transparent 28%),
        radial-gradient(circle at 90% 90%,rgba(244,201,93,.12),transparent 30%),
        radial-gradient(circle at 15% 10%,rgba(196,181,253,.20),transparent 28%),radial-gradient(circle at 90% 90%,rgba(246,196,83,.14),transparent 30%),linear-gradient(145deg,#100b2e 0%,#241254 45%,#5b21b6 100%);
}
.fgck-brand::before{
    content:"";
    position:absolute;
    width:370px;height:370px;
    left:-210px;top:-220px;
    border:1px solid rgba(93,183,245,.17);
    border-radius:50%;
}
.fgck-brand::after{
    content:"";
    position:absolute;
    width:430px;height:430px;
    right:-270px;bottom:-260px;
    border:1px solid rgba(244,201,93,.18);
    border-radius:50%;
}
.fgck-brand-content{position:relative;z-index:2}
.fgck-logo{
    width:118px;height:118px;
    margin:0 auto 22px;
    padding:12px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(145deg,#fff,#edf6ff);
    box-shadow:0 18px 45px rgba(0,0,0,.25),0 0 0 6px rgba(93,183,245,.07);
}
.fgck-logo img{width:100%;height:100%;object-fit:contain}
.fgck-brand h1{
    margin:0;
    font-size:27px;
    line-height:1.2;
    font-weight:850;
    letter-spacing:-.4px;
}
.fgck-brand h1 span{color:var(--fgck-gold)}
.fgck-divider{
    width:52px;height:3px;
    margin:14px auto 15px;
    border-radius:99px;
    background:linear-gradient(90deg,#5db7f5,var(--fgck-gold));
}
.fgck-brand p{
    max-width:300px;
    margin:0 auto 28px;
    color:rgba(255,255,255,.78);
    font-size:13px;
    line-height:1.75;
}
.fgck-benefits{
    display:grid;
    gap:10px;
    text-align:left;
    max-width:310px;
    margin:auto;
}
.fgck-benefit{
    display:flex;
    align-items:center;
    gap:11px;
    padding:9px 10px;
    border:1px solid rgba(255,255,255,.08);
    border-radius:12px;
    background:rgba(255,255,255,.045);
    color:rgba(255,255,255,.9);
    font-size:11px;
}
.fgck-benefit-icon{
    width:30px;height:30px;
    flex:0 0 30px;
    display:grid;place-items:center;
    border-radius:9px;
    color:var(--fgck-gold-light);
    background:rgba(244,201,93,.10);
    border:1px solid rgba(244,201,93,.20);
}

.fgck-form{
    padding:44px 56px 38px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    min-width:0;
}
.fgck-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:7px;
    margin-bottom:8px;
    color:var(--fgck-blue);
    font-size:10px;
    font-weight:850;
    letter-spacing:1.6px;
    text-transform:uppercase;
}
.fgck-eyebrow::before{
    content:"";
    width:22px;height:2px;
    border-radius:99px;
    background:var(--fgck-gold);
}
.fgck-heading h2{
    margin:0 0 7px;
    color:var(--fgck-text);
    font-size:29px;
    font-weight:850;
    letter-spacing:-.6px;
}
.fgck-heading p{
    margin:0 0 24px;
    color:var(--fgck-muted);
    font-size:12.5px;
    line-height:1.65;
}
.fgck-error{
    display:flex;
    gap:10px;
    align-items:flex-start;
    padding:12px 14px;
    margin-bottom:18px;
    border:1px solid #ffd7d7;
    border-radius:12px;
    background:#fff6f6;
    color:var(--fgck-danger);
    font-size:12px;
    line-height:1.5;
}
.fgck-error i{font-size:16px}

.fgck-field{margin-bottom:15px}
.fgck-label{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin:0 0 6px;
    color:#34445b;
    font-size:11px;
    font-weight:800;
}
.fgck-required{color:#c0392b}
.fgck-input-wrap{position:relative}
.fgck-icon{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#8b9aaa;
    font-size:15px;
    z-index:2;
    pointer-events:none;
    transition:.2s;
}
.fgck-input,
.fgck-select{
    width:100%;
    height:48px;
    border:1px solid var(--fgck-border);
    border-radius:11px;
    outline:none;
    background:#f8fbfe;
    color:var(--fgck-text);
    font-size:12.5px;
    padding:0 42px;
    transition:border-color .2s,box-shadow .2s,background .2s,transform .2s;
}
.fgck-select{padding-right:36px;cursor:pointer}
.fgck-input::placeholder{color:#a0acb9}
.fgck-input:hover,.fgck-select:hover{background:#fff;border-color:#c4d3e0}
.fgck-input:focus,.fgck-select:focus{
    background:#fff;
    border-color:var(--fgck-blue-2);
    box-shadow:0 0 0 4px rgba(22,131,216,.09);
}
.fgck-input:focus ~ .fgck-icon{color:var(--fgck-blue)}
.fgck-password{padding-right:48px}
.fgck-toggle{
    position:absolute;
    right:7px;top:50%;
    transform:translateY(-50%);
    width:35px;height:35px;
    border:0;border-radius:8px;
    background:transparent;
    color:#7d8b9a;
    display:grid;place-items:center;
    cursor:pointer;
    transition:.2s;
}
.fgck-toggle:hover{background:#eaf6ff;color:var(--fgck-blue)}

.fgck-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:0 14px;
}
.fgck-grid .fgck-field{min-width:0}

.fgck-strength{margin-top:7px}
.fgck-strength-bar{
    height:4px;
    width:100%;
    overflow:hidden;
    border-radius:99px;
    background:#e7edf3;
}
.fgck-strength-fill{
    width:0;height:100%;
    border-radius:99px;
    background:linear-gradient(90deg,#1683d8,#5db7f5);
    transition:width .25s ease;
}
.fgck-strength-text{
    margin-top:5px;
    color:#8997a6;
    font-size:9.5px;
}
.fgck-requirements{
    display:flex;
    flex-wrap:wrap;
    gap:5px 12px;
    margin-top:6px;
}
.fgck-requirement{
    color:#8a97a6;
    font-size:9.5px;
}
.fgck-requirement.valid{color:var(--fgck-success)}
.fgck-requirement i{margin-right:3px}

.fgck-submit{
    width:100%;
    height:50px;
    margin-top:3px;
    border:0;
    border-radius:11px;
    color:#fff;
    background:linear-gradient(135deg,var(--fgck-blue),var(--fgck-blue-2));
    font-size:12.5px;
    font-weight:850;
    box-shadow:0 10px 25px rgba(18,97,160,.22);
    transition:.25s;
}
.fgck-submit:hover{
    transform:translateY(-2px);
    box-shadow:0 15px 30px rgba(18,97,160,.29);
}
.fgck-submit:active{transform:translateY(0)}
.fgck-submit:disabled{opacity:.72;cursor:not-allowed;transform:none}

.fgck-login{
    text-align:center;
    margin-top:18px;
    padding-top:15px;
    border-top:1px solid #edf1f5;
    color:#7b8795;
    font-size:11px;
}
.fgck-login a,.fgck-back a{
    color:var(--fgck-blue);
    font-weight:850;
    text-decoration:none;
}
.fgck-login a:hover,.fgck-back a:hover{text-decoration:underline}
.fgck-back{
    text-align:center;
    margin-top:10px;
}
.fgck-back a{
    color:#8794a3;
    font-size:10.5px;
}
.fgck-security{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:5px;
    margin-top:13px;
    color:#9aa7b5;
    font-size:9.5px;
}
.fgck-security i{color:var(--fgck-blue)}

.joyland-registration-popup{
    border-radius:24px!important;
    padding:32px!important;
    max-width:450px!important;
    box-shadow:0 30px 80px rgba(7,26,51,.25)!important;
}
.joyland-registration-popup .swal2-title{
    color:var(--fgck-text)!important;
    font-size:24px!important;
    font-weight:850!important;
}
.joyland-registration-popup .swal2-timer-progress-bar{
    background:linear-gradient(90deg,var(--fgck-blue),#5db7f5,var(--fgck-gold))!important;
}

@media(max-width:900px){
    .fgck-register-card{max-width:650px;grid-template-columns:1fr}
    .fgck-brand{padding:35px 25px}
    .fgck-benefits{display:none}
    .fgck-form{padding:38px 38px 32px}
}
@media(max-width:575px){
    .fgck-register-page{padding:12px 9px}
    .fgck-register-card{border-radius:21px}
    .fgck-brand{padding:28px 20px}
    .fgck-logo{width:86px;height:86px;padding:9px;margin-bottom:16px}
    .fgck-brand h1{font-size:22px}
    .fgck-brand p{font-size:12px;margin-bottom:0}
    .fgck-form{padding:28px 20px 25px}
    .fgck-heading h2{font-size:24px}
    .fgck-grid{grid-template-columns:1fr;gap:0}
    .fgck-input,.fgck-select{height:47px}
}
@media(max-width:360px){
    .fgck-form{padding:24px 15px}
    .fgck-brand{padding:25px 15px}
}
@media(prefers-reduced-motion:reduce){
    *{scroll-behavior:auto!important;transition:none!important}
}
</style>

<div class="fgck-register-page">
    <main class="fgck-register-card" aria-label="FGCK Joyland member registration">

        <section class="fgck-brand">
            <div class="fgck-brand-content">
                <div class="fgck-logo">
                    <img src="/assets/images/full_gospel_churches_logo.png"
                         alt="FGCK Joyland Church Logo">
                </div>

                <h1>Join <span>FGCK Joyland</span></h1><div style="font-size:11px;letter-spacing:2.4px;font-weight:900;color:#ffe6a1;text-transform:uppercase;margin-top:6px;">Perfected to Influence</div>
                <div class="fgck-divider"></div>

                <p>
                    Create your secure member account and stay connected
                    with the ministry through the FGCK Joyland digital portal.
                </p>

                <div class="fgck-benefits">
                    <div class="fgck-benefit">
                        <span class="fgck-benefit-icon"><i class="bi bi-calendar-check"></i></span>
                        <span>Book pastoral appointments with ease</span>
                    </div>
                    <div class="fgck-benefit">
                        <span class="fgck-benefit-icon"><i class="bi bi-person-heart"></i></span>
                        <span>Stay connected with pastoral ministry</span>
                    </div>
                    <div class="fgck-benefit">
                        <span class="fgck-benefit-icon"><i class="bi bi-stars"></i></span>
                        <span>Be part of a connected church community</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="fgck-form">
            <div class="fgck-heading">
                <div class="fgck-eyebrow">Perfected to Influence</div>
                <h2>Create Your Account</h2>
                <p>Enter your details below to get started with the FGCK Joyland member portal.</p>
            </div>

            <?php if ($error): ?>
                <div class="fgck-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" id="registrationForm" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="fgck-field">
                    <label class="fgck-label" for="fullName">
                        <span>Full Name <span class="fgck-required">*</span></span>
                    </label>
                    <div class="fgck-input-wrap">
                        <i class="bi bi-person fgck-icon"></i>
                        <input id="fullName" class="fgck-input" name="full_name"
                               type="text" placeholder="Enter your full name"
                               value="<?= e($_POST['full_name'] ?? '') ?>"
                               autocomplete="name" minlength="3" required>
                    </div>
                </div>

                <div class="fgck-grid">
                    <div class="fgck-field">
                        <label class="fgck-label" for="phone">
                            <span>Phone Number <span class="fgck-required">*</span></span>
                        </label>
                        <div class="fgck-input-wrap">
                            <i class="bi bi-telephone fgck-icon"></i>
                            <input id="phone" class="fgck-input" name="phone"
                                   type="tel" placeholder="e.g. 0712345678"
                                   value="<?= e($_POST['phone'] ?? '') ?>"
                                   autocomplete="tel" minlength="7" required>
                        </div>
                    </div>

                    <div class="fgck-field">
                        <label class="fgck-label" for="gender">Gender</label>
                        <div class="fgck-input-wrap">
                            <i class="bi bi-people fgck-icon"></i>
                            <select id="gender" class="fgck-select" name="gender">
                                <option value="">Prefer not to say</option>
                                <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="fgck-field">
                    <label class="fgck-label" for="email">
                        <span>Email Address <span class="fgck-required">*</span></span>
                    </label>
                    <div class="fgck-input-wrap">
                        <i class="bi bi-envelope fgck-icon"></i>
                        <input id="email" class="fgck-input" type="email" name="email"
                               placeholder="Enter your email address"
                               value="<?= e($_POST['email'] ?? '') ?>"
                               autocomplete="email" required>
                    </div>
                </div>

                <div class="fgck-field">
                    <label class="fgck-label" for="password">
                        <span>Create Password <span class="fgck-required">*</span></span>
                    </label>
                    <div class="fgck-input-wrap">
                        <i class="bi bi-lock fgck-icon"></i>
                        <input id="password" class="fgck-input fgck-password"
                               type="password" name="password" minlength="8"
                               autocomplete="new-password"
                               placeholder="Create a secure password" required>
                        <button type="button" class="fgck-toggle" id="passwordToggle"
                                aria-label="Show password">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>
                    </div>

                    <div class="fgck-strength">
                        <div class="fgck-strength-bar">
                            <div class="fgck-strength-fill" id="passwordStrengthFill"></div>
                        </div>
                        <div class="fgck-strength-text" id="passwordStrengthText">
                            Use at least 8 characters, including a letter and a number.
                        </div>
                        <div class="fgck-requirements">
                            <span class="fgck-requirement" id="lengthRequirement">
                                <i class="bi bi-circle"></i>8+ characters
                            </span>
                            <span class="fgck-requirement" id="letterRequirement">
                                <i class="bi bi-circle"></i>Letter
                            </span>
                            <span class="fgck-requirement" id="numberRequirement">
                                <i class="bi bi-circle"></i>Number
                            </span>
                        </div>
                    </div>
                </div>

                <div class="fgck-field">
                    <label class="fgck-label" for="confirmPassword">
                        <span>Confirm Password <span class="fgck-required">*</span></span>
                    </label>
                    <div class="fgck-input-wrap">
                        <i class="bi bi-shield-lock fgck-icon"></i>
                        <input id="confirmPassword" class="fgck-input fgck-password"
                               type="password" name="confirm_password" minlength="8"
                               autocomplete="new-password"
                               placeholder="Confirm your password" required>
                        <button type="button" class="fgck-toggle" id="confirmPasswordToggle"
                                aria-label="Show password">
                            <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="fgck-submit" id="registerButton">
                    <span id="registerButtonContent">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        Create Member Account
                    </span>
                </button>
            </form>

            <div class="fgck-login">
                Already registered?
                <a href="/auth/login">Sign in to your account</a>
            </div>

            <div class="fgck-back">
                <a href="/">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back to FGCK Joyland Portal
                </a>
            </div>

            <div class="fgck-security">
                <i class="bi bi-shield-lock-fill"></i>
                Secure registration • Your password is encrypted
            </div>
        </section>
    </main>
</div>

<?php if ($registration_success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Welcome to FGCK Joyland!',
    html: `
        <div style="margin-top:8px;line-height:1.7">
            <div style="
                width:68px;height:68px;margin:0 auto 14px;border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                background:#eaf6ff;color:#1261a0;font-size:28px;">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <strong style="color:#1261a0;font-size:18px;display:block;margin-bottom:6px">
                Account Created Successfully
            </strong>
            <p style="margin:0 0 10px;color:#66768a;font-size:13px">
                Your FGCK Joyland member account is ready.
            </p>
            <div style="
                display:inline-block;padding:8px 14px;border-radius:9px;
                background:#f1f8fe;border:1px solid #d9ecfa;
                color:#1261a0;font-size:12px;font-weight:800;">
                Membership No: <?= e($membership_no) ?>
            </div>
            <small style="display:block;margin-top:12px;color:#9aa8b6;font-size:11px">
                Opening your Member Dashboard...
            </small>
        </div>
    `,
    showConfirmButton:false,
    timer:3500,
    timerProgressBar:true,
    allowOutsideClick:false,
    allowEscapeKey:false,
    customClass:{popup:'joyland-registration-popup'}
}).then(() => {
    window.location.href='/member/dashboard';
});

setTimeout(() => {
    window.location.href='/member/dashboard';
}, 3800);
</script>
<?php endif; ?>

<script>
(function () {
    'use strict';

    function setupPasswordToggle(inputId, buttonId, iconId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        const icon = document.getElementById(iconId);

        if (!input || !button || !icon) return;

        button.addEventListener('click', function () {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    }

    setupPasswordToggle('password', 'passwordToggle', 'passwordIcon');
    setupPasswordToggle('confirmPassword', 'confirmPasswordToggle', 'confirmPasswordIcon');

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const strengthFill = document.getElementById('passwordStrengthFill');
    const strengthText = document.getElementById('passwordStrengthText');

    const lengthRequirement = document.getElementById('lengthRequirement');
    const letterRequirement = document.getElementById('letterRequirement');
    const numberRequirement = document.getElementById('numberRequirement');

    function setRequirement(element, valid) {
        if (!element) return;
        const icon = element.querySelector('i');

        element.classList.toggle('valid', valid);

        if (icon) {
            icon.className = valid
                ? 'bi bi-check-circle-fill'
                : 'bi bi-circle';
        }
    }

    function updatePasswordStrength() {
        if (!password) return;

        const value = password.value;
        const hasLength = value.length >= 8;
        const hasLetter = /[A-Za-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[^A-Za-z0-9]/.test(value);
        const isLong = value.length >= 12;

        setRequirement(lengthRequirement, hasLength);
        setRequirement(letterRequirement, hasLetter);
        setRequirement(numberRequirement, hasNumber);

        let score = 0;
        if (hasLength) score++;
        if (hasLetter) score++;
        if (hasNumber) score++;
        if (hasSpecial || isLong) score++;

        const widths = [0, 25, 50, 75, 100];
        strengthFill.style.width = widths[score] + '%';

        if (!value) {
            strengthText.textContent =
                'Use at least 8 characters, including a letter and a number.';
        } else if (score <= 1) {
            strengthText.textContent = 'Password is weak.';
        } else if (score === 2) {
            strengthText.textContent = 'Password is getting stronger.';
        } else if (score === 3) {
            strengthText.textContent = 'Good password.';
        } else {
            strengthText.textContent = 'Strong password.';
        }
    }

    if (password) {
        password.addEventListener('input', updatePasswordStrength);
    }

    function updateConfirmState() {
        if (!confirmPassword || !password) return;

        if (!confirmPassword.value) {
            confirmPassword.style.borderColor = '';
            return;
        }

        confirmPassword.style.borderColor =
            password.value === confirmPassword.value ? '#1683d8' : '#dc3545';
    }

    if (confirmPassword) {
        confirmPassword.addEventListener('input', updateConfirmState);
    }

    const form = document.getElementById('registrationForm');
    const button = document.getElementById('registerButton');
    const buttonContent = document.getElementById('registerButtonContent');

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            if (password && confirmPassword && password.value !== confirmPassword.value) {
                event.preventDefault();

                Swal.fire({
                    icon:'warning',
                    title:'Passwords Do Not Match',
                    text:'Please make sure both password fields contain the same password.',
                    confirmButtonText:'Check Passwords',
                    confirmButtonColor:'#1261a0'
                });

                confirmPassword.focus();
                return;
            }

            if (button) button.disabled = true;

            if (buttonContent) {
                buttonContent.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2"
                          role="status" aria-hidden="true"></span>
                    Creating Account...
                `;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fullName = document.getElementById('fullName');
        if (fullName && !fullName.value) fullName.focus();
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
