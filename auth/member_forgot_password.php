<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';

$error = '';
$success = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $emailValue = strtolower(trim((string)($_POST['email'] ?? '')));

    if (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM members WHERE LOWER(email)=LOWER(?) AND status='active' LIMIT 1");
            $stmt->execute([$emailValue]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                // Do not disclose whether an email address is registered.
                $success = 'If an active member account exists for that email, a verification code will be sent shortly.';
            } else {
                $cooldown = $pdo->prepare('SELECT requested_at FROM member_password_resets WHERE member_id=? LIMIT 1');
                $cooldown->execute([(int)$member['id']]);
                $last = $cooldown->fetchColumn();
                if ($last && (time() - strtotime((string)$last)) < 60) {
                    $wait = 60 - (time() - strtotime((string)$last));
                    $error = 'Please wait ' . $wait . ' seconds before requesting another code.';
                } else {
                    $code = (string)random_int(100000, 999999);
                    $hash = password_hash($code, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', time() + 600);
                    $save = $pdo->prepare("INSERT INTO member_password_resets (member_id, code_hash, expires_at, attempts, verified_at, requested_at) VALUES (?, ?, ?, 0, NULL, NOW()) ON DUPLICATE KEY UPDATE code_hash=VALUES(code_hash), expires_at=VALUES(expires_at), attempts=0, verified_at=NULL, requested_at=NOW()");
                    $save->execute([(int)$member['id'], $hash, $expires]);

                    $name = trim((string)$member['full_name']) ?: 'Member';
                    $safeName = e($name);
                    $body = '
<div style="font-family:Arial,sans-serif;color:#172033;line-height:1.7">
  <h2 style="color:#2563eb;margin-top:0">Member Password Reset</h2>
  <p>Dear ' . $safeName . ',</p>
  <p>We received a request to reset your FGCK Joyland member portal password. Enter the verification code below to continue.</p>
  <div style="margin:24px 0;text-align:center">
    <div style="display:inline-block;padding:18px 28px;background:#eff6ff;border:2px dashed #2563eb;border-radius:14px">
      <div style="font-size:11px;color:#68758a;font-weight:700;letter-spacing:1px">YOUR VERIFICATION CODE</div>
      <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#2563eb">' . $code . '</div>
    </div>
  </div>
  <p>This code expires in <strong>10 minutes</strong> and can only be used once.</p>
  <p style="font-size:12px;color:#68758a">If you did not request this password reset, you can safely ignore this email.</p>
</div>';

                    $sent = send_system_email((string)$member['email'], $name, 'FGCK Joyland - Member Password Reset Code', $body);
                    if (!$sent) {
                        $pdo->prepare('DELETE FROM member_password_resets WHERE member_id=?')->execute([(int)$member['id']]);
                        error_log('FGCK MEMBER PASSWORD RESET: verification email failed for member ID ' . (int)$member['id']);
                        $error = 'We could not send the verification email. Please try again later or contact the church office.';
                    } else {
                        $_SESSION['member_password_reset_id'] = (int)$member['id'];
                        $_SESSION['member_password_reset_verified'] = false;
                        redirect('/auth/member_verify_reset');
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('FGCK MEMBER FORGOT PASSWORD ERROR: ' . $e->getMessage());
            $error = 'We could not process your request right now. Please try again later.';
        }
    }
}

$page_title = 'Member Forgot Password';
require __DIR__ . '/../includes/header.php';
?>
<style>
.member-recovery{min-height:calc(100vh - 70px);display:flex;align-items:center;justify-content:center;padding:30px 15px;background:linear-gradient(135deg,#f8fbff,#eef5fc)}
.member-recovery-card{width:100%;max-width:510px;background:#fff;padding:42px;border-radius:24px;box-shadow:0 22px 65px rgba(15,42,85,.12);border:1px solid #e5edf5}
.member-recovery-icon{width:76px;height:76px;border-radius:22px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;font-size:30px;margin:0 auto 20px}
.member-recovery h1{text-align:center;font-size:27px;font-weight:800;color:#172033;margin:0 0 9px}.member-recovery .intro{text-align:center;color:#68758a;font-size:14px;line-height:1.7;margin-bottom:25px}
.member-recovery label{font-size:13px;font-weight:750;color:#344158;margin-bottom:8px}.member-recovery input{border:1px solid #d9e4ee;border-radius:12px;padding:13px 14px}.member-recovery input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.member-recovery .submit-btn{width:100%;border:0;border-radius:12px;padding:14px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-weight:800;margin-top:18px}.member-recovery a{color:#2563eb;text-decoration:none;font-weight:700}
@media(max-width:520px){.member-recovery-card{padding:30px 22px}}
</style>
<div class="member-recovery"><div class="member-recovery-card">
  <div class="member-recovery-icon"><i class="bi bi-envelope-lock-fill"></i></div>
  <h1>Forgot your password?</h1>
  <p class="intro">Enter the email address linked to your FGCK Joyland member account. We will send you a 6-digit verification code.</p>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success" role="status"><?= e($success) ?></div><?php endif; ?>
  <form method="post" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="mb-3"><label for="email" class="form-label">Registered email address</label><input class="form-control" type="email" id="email" name="email" value="<?= e($emailValue) ?>" placeholder="you@example.com" autocomplete="email" required></div>
    <button class="submit-btn" type="submit"><i class="bi bi-send me-2"></i>Send Verification Code</button>
  </form>
  <div class="text-center mt-4"><a href="/auth/login?role=member"><i class="bi bi-arrow-left me-1"></i>Back to Member Login</a></div>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
