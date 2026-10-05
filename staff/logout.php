<?php
<<<<<<< HEAD
$_GET['role'] = 'pastor';
require __DIR__ . '/../auth/logout.php';
=======
require __DIR__ . '/../includes/bootstrap.php';

// Revoke the short-lived trusted-login token when the pastor explicitly logs out.
$trustedCookie = $_COOKIE['fgck_staff_trusted'] ?? '';
if ($trustedCookie !== '' && preg_match('/^[a-f0-9]{64}$/', $trustedCookie)) {
    $pdo->prepare("DELETE FROM staff_trusted_logins WHERE token_hash = ?")
        ->execute([hash('sha256', $trustedCookie)]);
}

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off');
setcookie('fgck_staff_trusted', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Clear all pastor authentication/verification session values.
unset(
    $_SESSION['staff_id'],
    $_SESSION['pending_staff_id'],
    $_SESSION['staff_otp_verified']
);

session_regenerate_id(true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102a43">
    <title>Signed Out | FGCK Joyland</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at top,#eaf5fb 0,#f7fafc 42%,#eef3f7 100%);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033}
        .logout-card{width:min(480px,100%);background:#fff;border:1px solid #e5edf3;border-radius:28px;padding:38px 30px;text-align:center;box-shadow:0 24px 70px rgba(15,42,67,.14)}
        .logo-wrap{width:92px;height:92px;margin:0 auto 20px;border-radius:25px;padding:12px;background:#fff;border:1px solid #e3edf4;box-shadow:0 12px 28px rgba(15,42,67,.10)}
        .logo-wrap img{width:100%;height:100%;object-fit:contain}
        .success-icon{width:66px;height:66px;margin:0 auto 17px;border-radius:50%;display:grid;place-items:center;background:#eaf8f0;color:#17824b;font-size:29px}
        h1{font-size:25px;font-weight:850;margin:0 0 9px}
        .brand{color:#1261a0;font-weight:800}
        p{color:#66758a;font-size:14px;line-height:1.7;margin:0}
        .redirect-box{margin-top:22px;padding:12px 15px;border-radius:13px;background:#f5f9fc;border:1px solid #e2edf5;color:#728396;font-size:12px}
        .progress{height:5px;margin-top:10px;border-radius:99px;background:#e9eff4;overflow:hidden}
        .progress-bar{height:100%;width:100%;background:#1261a0;animation:shrink 3s linear forwards;transform-origin:left}
        @keyframes shrink{from{width:100%}to{width:0}}
        .home-link{display:inline-flex;align-items:center;gap:7px;margin-top:19px;color:#1261a0;text-decoration:none;font-size:13px;font-weight:700}
        .home-link:hover{text-decoration:underline}
    </style>
</head>
<body>
    <main class="logout-card">
        <div class="logo-wrap"><img src="/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div>
        <div class="success-icon"><i class="bi bi-check2-circle"></i></div>
        <h1>Signed Out Successfully</h1>
        <p><span class="brand">FGCK Joyland Pastor Portal</span><br>Your session has been securely closed. Thank you for serving the ministry.</p>
        <div class="redirect-box">Returning to the FGCK Joyland portal in 3 seconds...<div class="progress"><div class="progress-bar"></div></div></div>
        <a class="home-link" href="/"><i class="bi bi-house-door"></i> Return to FGCK Joyland</a>
    </main>
    <script>
    setTimeout(() => { window.location.href = '/'; }, 3000);
    </script>
</body>
</html>
>>>>>>> 3095147f1de9cc3fe682800509762cfc6ea2edc1
