<?php
require __DIR__.'/../includes/bootstrap.php';
admin_required();
$admin=staff($pdo);
$msg=''; $error=''; $temporaryPassword='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action']??'');
    $id=(int)($_POST['id']??0);
    try{
        if($action==='create_admin'){
            $fullName=trim((string)($_POST['full_name']??''));
            $username=trim((string)($_POST['username']??''));
            $email=trim((string)($_POST['email']??''));
            $password=(string)($_POST['password']??'');
            if($fullName===''||$username===''||$email===''||$password==='') throw new RuntimeException('Complete all administrator fields.');
            if(!preg_match('/^[A-Za-z0-9._-]{3,80}$/',$username)) throw new RuntimeException('Username must be 3-80 characters and use only letters, numbers, dots, underscores or hyphens.');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid administrator email address.');
            if(strlen($password)<10) throw new RuntimeException('Administrator passwords must contain at least 10 characters.');
            $check=$pdo->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
            $check->execute([$username,$email]);
            if($check->fetch()) throw new RuntimeException('That username or email is already in use.');
            $pdo->prepare("INSERT INTO users(username,full_name,email,password_hash,role,status) VALUES(?,?,?,?,'admin','active')")
                ->execute([$username,$fullName,$email,password_hash($password,PASSWORD_DEFAULT)]);
            log_activity($pdo,null,(int)$admin['id'],'administrator_created','Created administrator account '.$username.'.');
            $msg='Administrator account '.$fullName.' was created successfully.';
        }else{
            if($id<=0) throw new RuntimeException('Invalid staff account.');
            $q=$pdo->prepare("SELECT id,username,full_name,email,role,status FROM users WHERE id=? LIMIT 1");
            $q->execute([$id]);
            $target=$q->fetch();
            if(!$target) throw new RuntimeException('Staff account not found.');

            if($action==='toggle_status'){
            if($id===(int)$admin['id']) throw new RuntimeException('You cannot deactivate your own administrator account.');
            $newStatus=$target['status']==='active'?'inactive':'active';
            $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$newStatus,$id]);
            log_activity($pdo,null,(int)$admin['id'],'staff_status_changed','Changed '.$target['username'].' status to '.$newStatus.'.');
            $msg=$target['full_name'].' is now '.ucfirst($newStatus).'.';
        }elseif($action==='reset_password'){
            if($id===(int)$admin['id']) throw new RuntimeException('Use your own password-change process for the administrator account.');
            $temporaryPassword='Fgck'.date('Y').'!'.strtoupper(bin2hex(random_bytes(3)));
            $pdo->prepare("UPDATE users SET password_hash=?, status='active' WHERE id=?")->execute([password_hash($temporaryPassword,PASSWORD_DEFAULT),$id]);
            log_activity($pdo,null,(int)$admin['id'],'staff_password_reset','Reset password for '.$target['username'].'.');
            $msg='Temporary password generated for '.$target['full_name'].'. Share it securely and require a password change after sign-in.';
            }else{
                throw new RuntimeException('Unknown administrator action.');
            }
        }
    }catch(Throwable $e){
        $error=$e instanceof RuntimeException?$e->getMessage():'The administrator action could not be completed.';
    }
}

$rows=$pdo->query("SELECT id,username,full_name,email,phone,role,status,last_login_at,created_at FROM users ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'pastor' THEN 2 WHEN 'church_leader' THEN 3 ELSE 4 END,full_name")->fetchAll();
$page_title='Staff Access Management';
require __DIR__.'/../includes/header.php';
?>
<div><?php require __DIR__.'/../includes/admin_sidebar.php';?><main class="main">
<header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Staff Access Management</h1><p>Control staff access, account status and credentials. Administrative controls only.</p></div></div><span class="live-pill">Access control</span></header>
<div class="content">
<?php if($msg):?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?=e($error)?></div><?php endif;?>
<?php if($temporaryPassword):?><div class="alert alert-warning"><strong>Temporary password:</strong> <code><?=e($temporaryPassword)?></code><br><small>Store/share this securely. It is shown only now.</small></div><?php endif;?>
<div class="cardx p-4 mb-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="section-title mb-1">Add another administrator</h3><p class="text-muted small mb-0">Create a separate administrator account for trusted administrative staff. Administrators receive access to this control center only.</p></div><span class="badge text-bg-dark">Admin only</span></div>
<form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create_admin"><div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" required maxlength="150"></div><div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username" required maxlength="80" pattern="[A-Za-z0-9._-]{3,80}"></div><div class="col-md-6"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" required maxlength="150"></div><div class="col-md-6"><label class="form-label">Initial password</label><input class="form-control" type="password" name="password" required minlength="10" autocomplete="new-password"><small class="text-muted">Minimum 10 characters.</small></div><div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit" onclick="return confirm('Create this administrator account?')"><i class="bi bi-shield-plus me-1"></i>Create Administrator</button></div></form></div>
<div class="cardx p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="section-title mb-1">Staff accounts</h3><p class="text-muted small mb-0">Administrators can activate/deactivate staff and reset staff credentials. The administrator account is protected from self-deactivation/reset.</p></div><a class="btn btn-outline-primary" href="/admin/leader_register"><i class="bi bi-person-plus me-1"></i>Register Pastor / Leader</a></div>
<div class="table-responsive"><table class="table table-clean align-middle"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr>
<td><strong><?=e($r['full_name'])?></strong><br><small><?=e($r['email'])?></small></td>
<td><?=e($r['username'])?></td><td><?=e(ucwords(str_replace('_',' ',$r['role'])))?></td>
<td><span class="badge text-bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?=e(ucfirst($r['status']))?></span></td>
<td><?=e($r['last_login_at']?date('d M Y, g:i A',strtotime($r['last_login_at'])):'Never')?></td>
<td class="text-end"><div class="d-flex flex-wrap justify-content-end gap-2">
<?php if((int)$r['id']!==(int)$admin['id']):?>
<form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-<?= $r['status']==='active'?'danger':'success' ?>" onclick="return confirm('Change this account status?')"><i class="bi bi-<?= $r['status']==='active'?'person-slash':'person-check' ?> me-1"></i><?= $r['status']==='active'?'Deactivate':'Activate' ?></button></form>
<form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-primary" onclick="return confirm('Generate a new temporary password for this account?')"><i class="bi bi-key me-1"></i>Reset password</button></form>
<?php else:?><span class="text-muted small"><i class="bi bi-lock me-1"></i>Current administrator</span><?php endif;?>
</div></td></tr><?php endforeach;?>
</tbody></table></div></div>
</div></main></div>
<?php require __DIR__.'/../includes/footer.php';?>
