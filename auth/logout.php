<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$role = strtolower(trim((string)($_GET['role'] ?? $_POST['role'] ?? '')));
$valid = ['member', 'pastor', 'church_leader', 'admin'];

if ($role === 'member') {
    unset($_SESSION['member_id']);
} elseif (in_array($role, ['pastor', 'church_leader', 'admin'], true)) {
    $key = staff_session_key($role);
    $id = (int)($_SESSION[$key] ?? 0);
    unset($_SESSION[$key]);
    if ((int)($_SESSION['staff_id'] ?? 0) === $id) {
        unset($_SESSION['staff_id']);
    }
} else {
    // Legacy/global logout: explicitly requested when no role is supplied.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    setcookie('fgck_staff_trusted', '', time()-3600, '/');
    session_destroy();
}

header('Location: /?logged_out=1');
exit;
