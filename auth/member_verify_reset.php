<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$memberId = (int)($_SESSION['member_password_reset_id'] ?? 0);
if (!$memberId) redirect('/auth/member_forgot_password');

$error = '';
$member = false;
try {
    $q = $pdo->prepare("SELECT m.id, m.email, r.code_hash, r.expires_at, r.attempts, r.verified_at FROM members m JOIN member_password_resets r ON r.member_id=m.id WHERE m.id=? AND m.status='active' LIMIT 1");
    $q->execute([$memberId]);
    $member = $q->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('FGCK MEMBER RESET VERIFY DB ERROR: ' . $e->getMessage());
    http_response_code(500);
    exit('Password recovery is not configured yet. Please contact the church administrator.');
}
if (!$member || empty($member['code_hash']) || strtotime((string)$member['expires_at']) < time()) {
    unset($_SESSION['member_password_reset_id'], $_SESSION['member_password_reset_verified']);
    redirect('/auth/member_forgot_password?expired=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = trim((string)($_POST['code'] ?? ''));
    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Enter the 6-digit verification code sent to your email.';
    } elseif ((int)$member['attempts'] >= 5) {
        $error = 'You have used all verification attempts. Please request a new code.';
    } elseif (!password_verify($code, (string)$member['code_hash'])) {
        $attempts = (int)$member['attempts'] + 1;
        $pdo->prepare('UPDATE member_password_resets SET attempts=? WHERE member_id=?')->execute([$attempts, $memberId]);
        $error = $attempts >= 5 ? 'Too many incorrect attempts. Please request a new code.' : 'The code is incorrect. ' . (5 - $attempts) . ' attempt(s) remaining.';
    } else {
        $pdo->prepare('UPDATE member_password_resets SET verified_at=NOW() WHERE member_id=?')->execute([$memberId]);
        $_SESSION['member_password_reset_verified'] = true;
        redirect('/auth/member_reset_password');
    }
}

$maskedEmail = (string)$member['email'];
$parts = explode('@', $maskedEmail, 2);
if (count($parts) === 2) $maskedEmail = substr($parts[0], 0, 1) . str_repeat('*', max(3, strlen($parts[0]) - 1)) . '@' . $parts[1];
$page_title = 'Verify Password Reset';
require __DIR__ . '/../includes/header.php';
?>
<style>
.member-verify{min-height:calc(100vh - 70px);display:flex;align-items:center;justify-content:center;padding:30px 15px;background:linear-gradient(135deg,#f8fbff,#eef5fc)}.member-verify-card{width:100%;max-width:500px;background:#fff;padding:42px;border-radius:24px;box-shadow:0 22px 65px rgba(15,42,85,.12);border:1px solid #e5edf5}.member-verify-icon{width:76px;height:76px;border-radius:22px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;font-size:30px;margin:0 auto 20px}.member-verify h1{text-align:center;font-size:26px;font-weight:800;color:#172033}.member-verify p{text-align:center;color:#68758a;font-size:14px;line-height:1.7}.member-verify .destination{text-align:center;background:#f4f8fc;border-radius:11px;padding:11px;margin:20px 0;color:#1d4ed8;font-weight:750}.member-verify input{text-align:center;font-size:25px;font-weight:800;letter-spacing:9px;padding:13px;border-radius:12px}.member-verify button{width:100%;border:0;border-radius:12px;padding:14px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-weight:800;margin-top:18px}.member-verify a{color:#2563eb;text-decoration:none;font-weight:700}@media(max-width:520px){.member-verify-card{padding:30px 22px}}
</style>
<div class="member-verify"><div class="member-verify-card">
  <div class="member-verify-icon"><i class="bi bi-shield-lock-fill"></i></div>
  <h1>Verify your email</h1><p>Enter the 6-digit code we sent to the email address below.</p>
  <div class="destination"><i class="bi bi-envelope-check me-1"></i><?= e($maskedEmail) ?></div>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input class="form-control" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" aria-label="Six digit verification code" required autofocus><button type="submit"><i class="bi bi-check-circle me-2"></i>Verify Code</button></form>
  <div class="text-center mt-4"><a href="/auth/member_forgot_password">Request a new code</a></div>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
