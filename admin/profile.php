<?php
require __DIR__.'/../includes/bootstrap.php';
admin_required();
$admin=staff($pdo); $msg=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action']??'');
    try{
        if($action==='update_profile'){
            $fullName=trim((string)($_POST['full_name']??''));
            $email=trim((string)($_POST['email']??''));
            $phone=trim((string)($_POST['phone']??''));
            if($fullName==='') throw new RuntimeException('Full name is required.');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
            $q=$pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
            $q->execute([$email,(int)$admin['id']]);
            if($q->fetch()) throw new RuntimeException('That email address is already used by another account.');
            $pdo->prepare('UPDATE users SET full_name=?,email=?,phone=? WHERE id=?')->execute([$fullName,$email,$phone!==''?$phone:null,(int)$admin['id']]);
            log_activity($pdo,null,(int)$admin['id'],'admin_profile_updated','Updated administrator profile.');
            $msg='Administrator profile updated successfully.'; $admin=staff($pdo);
        }elseif($action==='change_password'){
            $current=(string)($_POST['current_password']??'');
            $new=(string)($_POST['new_password']??'');
            $confirm=(string)($_POST['confirm_password']??'');
            if(!password_verify($current,(string)$admin['password_hash'])) throw new RuntimeException('Current password is incorrect.');
            if(strlen($new)<10) throw new RuntimeException('New password must contain at least 10 characters.');
            if($new!==$confirm) throw new RuntimeException('New passwords do not match.');
            if(password_verify($new,(string)$admin['password_hash'])) throw new RuntimeException('Choose a different password.');
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),(int)$admin['id']]);
            log_activity($pdo,null,(int)$admin['id'],'admin_password_changed','Administrator changed own password.');
            $msg='Your administrator password was changed successfully.';
        }
    }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'The requested profile action could not be completed.';}
}
$page_title='Administrator Profile'; require __DIR__.'/../includes/header.php'; ?>
<div><?php require __DIR__.'/../includes/admin_sidebar.php'; ?><main class="main">
<header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Administrator Profile</h1><p>Manage your administrator account and password securely.</p></div></div><a href="/admin/dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></header>
<div class="content"><div class="row g-4 justify-content-center">
<div class="col-xl-9">
<?php if($msg):?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?=e($error)?></div><?php endif;?>
<div class="cardx p-4 mb-4"><div class="d-flex align-items-center gap-3 mb-4"><div class="icon"><i class="bi bi-person-circle"></i></div><div><h3 class="section-title mb-1">My Administrator Details</h3><p class="text-muted mb-0">Only your name, contact details and password can be changed here.</p></div></div>
<form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update_profile">
<div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?=e($admin['full_name'])?>" required></div>
<div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?=e($admin['username'])?>" disabled></div>
<div class="col-md-6"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?=e($admin['email'])?>" required></div>
<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=e($admin['phone']??'')?>"></div>
<div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Profile</button></div></form></div>
<div class="cardx p-4"><div class="d-flex align-items-center gap-3 mb-4"><div class="icon"><i class="bi bi-key"></i></div><div><h3 class="section-title mb-1">Change Password</h3><p class="text-muted mb-0">Enter your current password before choosing a new one.</p></div></div>
<form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="change_password">
<div class="col-md-4"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required autocomplete="current-password"></div>
<div class="col-md-4"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" minlength="10" required autocomplete="new-password"><small class="text-muted">Minimum 10 characters.</small></div>
<div class="col-md-4"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div>
<div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i>Change Password</button></div></form></div>
</div></div></div></main></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
