<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Always show the unified sign-in screen. An existing session must never
// hijack a new login attempt (for example, an administrator session must not
// force the next pastor/member login back to /admin/dashboard).
// A successful login below replaces the previous role session cleanly.

$error = '';
$login_success = false;
$destination = '/';
$selected_role = strtolower(trim((string)($_GET['role'] ?? '')));
$allowed_roles = ['member','pastor','church_leader','admin'];
if (!in_array($selected_role, $allowed_roles, true)) {
    $selected_role = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        // Start every submitted login as a clean authentication attempt.
        // Preserve only the CSRF token; remove any previous member/staff role
        // so a failed attempt cannot leave the browser authenticated as another
        // user and a successful attempt cannot inherit stale role state.
        $csrf = $_SESSION['csrf'] ?? null;
        $_SESSION = [];
        if (is_string($csrf) && $csrf !== '') {
            $_SESSION['csrf'] = $csrf;
        }
        session_regenerate_id(true);

        $identity = trim((string)($_POST['identity'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($identity === '' || $password === '') {
            throw new RuntimeException('Please enter your email, username or membership number and your password.');
        }

        // Staff: username, email or phone. When the visitor selected a role
        // card, constrain the lookup to that role. This prevents an old/wrong
        // role (especially admin) from hijacking the Pastor login flow.
        $u = false;
        if ($selected_role !== 'member') {
            if ($selected_role !== '') {
                $q = $pdo->prepare("SELECT * FROM users
                    WHERE status='active' AND role=?
                      AND (username=? OR email=? OR phone=?)
                    LIMIT 1");
                $q->execute([$selected_role, $identity, $identity, $identity]);
            } else {
                $q = $pdo->prepare("SELECT * FROM users
                    WHERE status='active'
                      AND (username=? OR email=? OR phone=?)
                    LIMIT 1");
                $q->execute([$identity, $identity, $identity]);
            }
            $u = $q->fetch();
        }

        if ($u && password_verify($password, $u['password_hash'])) {
            $_SESSION['staff_id'] = (int)$u['id'];
            $_SESSION[staff_session_key((string)$u['role'])] = (int)$u['id'];
            // Do NOT unset another role here. Members, Pastors, Leaders and
            // Administrators can remain signed in simultaneously.
            unset($_SESSION['pending_staff_id'], $_SESSION['staff_otp_verified']);

            $pdo->prepare("UPDATE users SET last_login_at=NOW() WHERE id=?")->execute([(int)$u['id']]);
            log_activity($pdo, null, (int)$u['id'], 'staff_login', 'Unified portal login as '.$u['role']);

            $destination = $u['role']==='admin' ? '/admin/dashboard'
                : ($u['role']==='church_leader' ? '/leader/dashboard' : '/staff/dashboard');
            $login_success = true;
        } else {
            // Member: email, phone or membership number. Only attempt the
            // member table when the visitor chose Member or did not choose a
            // role. A Pastor login can therefore never fall through to another
            // account type and receive the wrong portal.
            $m = false;
            if ($selected_role === '' || $selected_role === 'member') {
                $q = $pdo->prepare("SELECT * FROM members
                    WHERE status='active'
                      AND (email=? OR phone=? OR membership_no=?)
                    LIMIT 1");
                $q->execute([$identity, $identity, $identity]);
                $m = $q->fetch();
            }

            if ($m && password_verify($password, $m['password_hash'])) {
                $_SESSION['member_id'] = (int)$m['id'];
                // Do NOT unset staff role sessions. A member can be signed in
                // at the same time as a Pastor, Leader or Administrator.
                unset($_SESSION['pending_staff_id'], $_SESSION['staff_otp_verified']);

                $pdo->prepare("UPDATE members SET last_login_at=NOW() WHERE id=?")->execute([(int)$m['id']]);
                log_activity($pdo, (int)$m['id'], null, 'member_login', 'Unified portal login');
                $destination = '/member/dashboard';
                $login_success = true;
            } else {
                // Do not reveal whether a particular account exists.
                $error = 'We could not sign you in. Please check your login details and try again.';
                log_activity($pdo, null, null, 'login_failed', 'Failed unified login attempt');
            }
        }
    } catch (Throwable $e) {
        error_log('FGCK UNIFIED LOGIN ERROR: '.$e->getMessage());
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'We could not complete your sign-in. Please try again.';
    }
}

$page_title='Sign In';
require __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.unified-auth{min-height:calc(100vh - 70px);display:grid;place-items:center;padding:34px 18px;background:radial-gradient(circle at 10% 10%,rgba(139,92,246,.16),transparent 30%),radial-gradient(circle at 90% 90%,rgba(246,196,83,.14),transparent 28%),linear-gradient(135deg,#faf8ff,#f3f0ff)}
.unified-card{width:min(1040px,100%);display:grid;grid-template-columns:.9fr 1.1fr;overflow:hidden;background:#fff;border:1px solid #e4edf5;border-radius:30px;box-shadow:0 30px 90px rgba(15,42,67,.15)}
.unified-brand{padding:56px 44px;color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;background:radial-gradient(circle at 15% 10%,rgba(196,181,253,.20),transparent 28%),radial-gradient(circle at 90% 90%,rgba(246,196,83,.16),transparent 28%),linear-gradient(145deg,#100b2e,#241254 55%,#5b21b6);position:relative;overflow:hidden}
.unified-brand:before{content:"";position:absolute;width:380px;height:380px;border:1px solid rgba(255,255,255,.12);border-radius:50%;top:-210px;left:-180px}
.unified-logo{width:116px;height:116px;border-radius:28px;background:#fff;padding:13px;box-shadow:0 18px 35px rgba(0,0,0,.2);position:relative}
.unified-logo img{width:100%;height:100%;object-fit:contain}
.unified-brand h1{font-size:30px;font-weight:850;margin:24px 0 9px;position:relative}.unified-brand p{max-width:340px;color:rgba(255,255,255,.8);line-height:1.7;position:relative}.unified-badge{margin-top:22px;border:1px solid rgba(244,201,93,.38);background:rgba(244,201,93,.1);padding:10px 16px;border-radius:999px;font-weight:750;color:#ffe7a3;position:relative}
.unified-form{padding:56px 62px;background:rgba(255,255,255,.98)}.eyebrow{text-transform:uppercase;letter-spacing:1.8px;font-size:11px;font-weight:900;color:#6d28d9}.unified-form h2{font-size:32px;font-weight:850;color:#172033;margin:7px 0}.muted{color:#68758a;line-height:1.65;font-size:14px}.login-alert{padding:13px 15px;border-radius:13px;background:#fff5f5;border:1px solid #ffd9d9;color:#a61b1b;font-size:13px;margin:20px 0}
.field{margin:18px 0}.field label{display:block;font-size:13px;font-weight:750;color:#344158;margin-bottom:8px}.field input{width:100%;border:1px solid #d9e4ee;border-radius:13px;padding:13px 15px;font-size:14px;outline:none}.field input:focus{border-color:#8b5cf6;box-shadow:0 0 0 4px rgba(37,99,235,.08)}
.login-btn{width:100%;border:0;border-radius:13px;padding:14px;background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:#fff;font-weight:800;font-size:14px}.login-btn:hover{background:linear-gradient(135deg,#5b21b6,#7c3aed)}.security{margin-top:17px;padding:12px 14px;background:#f4f8fc;border:1px solid #e2ebf3;border-radius:12px;color:#68758a;font-size:12px}.security i{color:#2563eb}.role-strip{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.role-strip span{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;background:#f7fafc;border:1px solid #e5edf4;color:#657286;font-size:11px;font-weight:700}.role-strip i{color:#2563eb}.help-links{display:flex;justify-content:space-between;gap:12px;margin-top:18px;font-size:13px}.help-links a{color:#2563eb;text-decoration:none;font-weight:700}
@media(max-width:800px){.unified-card{grid-template-columns:1fr}.unified-brand{padding:38px 28px}.unified-form{padding:38px 25px}}
</style>
<div class="unified-auth">
 <div class="unified-card">
 
<section class="unified-brand text-center">

    <!-- Church Logo -->
    <div class="unified-logo">
        <img
            src="/assets/images/full_gospel_churches_logo.png"
            alt="Full Gospel Churches of Kenya - FGCK Joyland"
        >
    </div>

    <!-- Church Name -->
    <h1 class="unified-title">
        FGCK <span>Joyland</span>
    </h1>

    <!-- Motto -->
    <div class="unified-motto">
        <span class="motto-line"></span>
        <span>Perfected to Influence</span>
        <span class="motto-line"></span>
    </div>

    <!-- Welcome Message -->
    <p class="unified-description">
        A trusted digital home for the FGCK Joyland family —
        connecting people, strengthening fellowship, nurturing care,
        and making ministry and service easier for everyone.
    </p>

    <!-- Church Mission Badge -->
    <div class="unified-badge">
        <i class="bi bi-heart-fill me-2"></i>
        Connecting Hearts. Strengthening Faith. Serving God.
    </div>

</section>


<style>

/* =========================================================
   FGCK JOYLAND — UNIFIED BRAND
   ========================================================= */


.unified-description {
    max-width: 570px;

    margin: 0 auto 22px;

    color: rgba(255, 255, 255, 0.88);

    font-size: 15px;

    line-height: 1.75;

    font-weight: 400;
}


/* =========================================================
   CHURCH MISSION BADGE
   ========================================================= */



</style>

  <section class="unified-form">
   <span class="eyebrow">Perfected to Influence</span><h2>Welcome back</h2>
   <p class="muted">Use one account to access the church platform. Your role determines the portal and services available to you </p>
   <?php if($error): ?><div class="login-alert"><i class="bi bi-exclamation-circle-fill me-2"></i><?=e($error)?></div><?php endif; ?>
   <form method="post" autocomplete="on">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <div class="field"><label for="identity">Email, username, phone or membership number</label><input id="identity" name="identity" value="<?=e($_POST['identity']??'')?>" autocomplete="username" required></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="login-btn" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>Sign in</button>
    <div class="security"><i class="bi bi-shield-lock-fill me-1"></i> Protected access · automatic role routing · designed for members, pastors, leaders and administrators.</div>
    <div class="role-strip"><span><i class="bi bi-person-check"></i> Members</span><span><i class="bi bi-heart"></i> Pastors</span><span><i class="bi bi-megaphone"></i> Leaders</span><span><i class="bi bi-speedometer2"></i> Admins</span></div>
   </form>
   <div class="help-links"><a href="/auth/register"><i class="bi bi-person-plus me-1"></i>New to the church portal? Register</a><a href="/"><i class="bi bi-house me-1"></i>Church home</a></div>
  </section>
 </div>
</div>
<?php if($login_success): ?>
<script>
Swal.fire({icon:'success',title:'Sign In Successful',text:'Welcome back to FGCK Joyland. Taking you to your portal…',confirmButtonText:'Continue',confirmButtonColor:'#6d28d9',timer:2200,timerProgressBar:true,allowOutsideClick:false}).then(function(){window.location.href=<?=json_encode($destination)?>;});
setTimeout(function(){window.location.href=<?=json_encode($destination)?>;},2500);
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
