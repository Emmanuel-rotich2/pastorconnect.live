<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$memberId = (int)($_SESSION['member_password_reset_id'] ?? 0);
if (!$memberId || empty($_SESSION['member_password_reset_verified'])) redirect('/auth/member_forgot_password');

$error = '';
$success = false;
$q = $pdo->prepare("SELECT r.verified_at, r.expires_at FROM member_password_resets r JOIN members m ON m.id=r.member_id WHERE r.member_id=? AND m.status='active' LIMIT 1");
$q->execute([$memberId]);
$reset = $q->fetch(PDO::FETCH_ASSOC);
if (!$reset || empty($reset['verified_at']) || strtotime((string)$reset['expires_at']) < time()) {
    unset($_SESSION['member_password_reset_id'], $_SESSION['member_password_reset_verified']);
    redirect('/auth/member_forgot_password?expired=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($password) < 8) $error = 'Your password must be at least 8 characters long.';
    elseif (!preg_match('/[A-Za-z]/', $password)) $error = 'Your password must contain at least one letter.';
    elseif (!preg_match('/\d/', $password)) $error = 'Your password must contain at least one number.';
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) $error = 'Your password must contain at least one special character.';
    elseif ($password !== $confirm) $error = 'The passwords do not match.';
    else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT r.verified_at, r.expires_at FROM member_password_resets r JOIN members m ON m.id=r.member_id WHERE r.member_id=? AND m.status='active' FOR UPDATE");
            $lock->execute([$memberId]);
            $current = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$current || empty($current['verified_at']) || strtotime((string)$current['expires_at']) < time()) {
                throw new RuntimeException('Your verification session has expired. Please request a new code.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE members SET password_hash=? WHERE id=? AND status='active'");
            $update->execute([$hash, $memberId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('We could not update your password. Please try again.');
            $pdo->prepare('DELETE FROM member_password_resets WHERE member_id=?')->execute([$memberId]);
            $pdo->commit();
            if (function_exists('log_activity')) {
                try { log_activity($pdo, $memberId, null, 'member_password_reset', 'Member password reset successfully'); }
                catch (Throwable $logError) { error_log('FGCK MEMBER RESET LOG ERROR: ' . $logError->getMessage()); }
            }
            unset($_SESSION['member_password_reset_id'], $_SESSION['member_password_reset_verified']);
            $success = true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('FGCK MEMBER PASSWORD UPDATE ERROR: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'We could not reset your password. Please try again.';
        }
    }
}

$page_title = 'Create New Password';
require __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.member-reset{min-height:calc(100vh - 70px);display:flex;align-items:center;justify-content:center;padding:30px 15px;background:linear-gradient(135deg,#f8fbff,#eef5fc)}.member-reset-card{width:100%;max-width:510px;background:#fff;padding:42px;border-radius:24px;box-shadow:0 22px 65px rgba(15,42,85,.12);border:1px solid #e5edf5}.member-reset-icon{width:76px;height:76px;border-radius:22px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;font-size:30px;margin:0 auto 20px}.member-reset h1{text-align:center;font-size:27px;font-weight:800;color:#172033;margin-bottom:9px}.member-reset .intro{text-align:center;color:#68758a;font-size:14px;line-height:1.7;margin-bottom:25px}.member-reset label{font-size:13px;font-weight:750;color:#344158;margin-bottom:8px}.member-reset input{border:1px solid #d9e4ee;border-radius:12px;padding:13px 14px}.member-reset input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}.member-reset button{width:100%;border:0;border-radius:12px;padding:14px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-weight:800;margin-top:18px}.member-reset .rules{font-size:12px;color:#68758a;background:#f5f8fc;padding:12px 14px;border-radius:10px;margin-top:15px}@media(max-width:520px){.member-reset-card{padding:30px 22px}}
</style>
<div class="member-reset"><div class="member-reset-card">
  <div class="member-reset-icon"><i class="bi bi-key-fill"></i></div>
  <h1>Create a new password</h1><p class="intro">Your email has been verified. Choose a new password for your FGCK Joyland member account.</p>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="new-password">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="mb-3"><label for="password">New password</label><input class="form-control" id="password" type="password" name="password" autocomplete="new-password" required></div>
    <div class="mb-2"><label for="confirm_password">Confirm new password</label><input class="form-control" id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required></div>
    <div class="rules"><i class="bi bi-shield-check me-1"></i>Use at least 8 characters, including a letter, a number and a special character.</div>
    <button type="submit"><i class="bi bi-check-circle me-2"></i>Reset Password</button>
  </form>
</div></div>
<?php if ($success): ?>
<script>
Swal.fire({icon:'success',title:'Password Reset Successfully',text:'Your member portal password has been updated. You will now be redirected to the login page.',confirmButtonText:'Go to Login',confirmButtonColor:'#2563eb',timer:2500,timerProgressBar:true,allowOutsideClick:false}).then(function(){window.location.href='/auth/login?role=member';});
setTimeout(function(){window.location.href='/auth/login?role=member';},2800);
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
