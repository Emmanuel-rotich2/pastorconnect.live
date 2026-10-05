<?php
require __DIR__.'/../includes/bootstrap.php';
admin_required();
$admin=staff($pdo);
$msg='';$error='';$temporaryPassword='';
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
            $q=$pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');$q->execute([$email,(int)$admin['id']]);
            if($q->fetch()) throw new RuntimeException('That email address is already used by another account.');
            $pdo->prepare('UPDATE users SET full_name=?,email=?,phone=? WHERE id=?')->execute([$fullName,$email,$phone!==''?$phone:null,(int)$admin['id']]);
            log_activity($pdo,null,(int)$admin['id'],'admin_profile_updated','Updated administrator profile.');
            $msg='Administrator profile updated successfully.';
            $admin=staff($pdo);
        }elseif($action==='change_password'){
            $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
            if(!password_verify($current,(string)$admin['password_hash'])) throw new RuntimeException('Current password is incorrect.');
            if(strlen($new)<10) throw new RuntimeException('New password must contain at least 10 characters.');
            if($new!==$confirm) throw new RuntimeException('New passwords do not match.');
            if(password_verify($new,(string)$admin['password_hash'])) throw new RuntimeException('Choose a different password.');
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),(int)$admin['id']]);
            log_activity($pdo,null,(int)$admin['id'],'admin_password_changed','Administrator changed own password.');
            $msg='Your administrator password was changed successfully.';
        }elseif($action==='member_action'){
            $id=(int)($_POST['member_id']??0);$memberAction=(string)($_POST['member_action']??'');
            $q=$pdo->prepare('SELECT id,full_name,membership_no,email,status FROM members WHERE id=? LIMIT 1');$q->execute([$id]);$m=$q->fetch();
            if(!$m) throw new RuntimeException('Member not found.');
            if($memberAction==='toggle_status'){
                $new=$m['status']==='active'?'inactive':'active';$pdo->prepare('UPDATE members SET status=? WHERE id=?')->execute([$new,$id]);
                log_activity($pdo,null,(int)$admin['id'],'member_status_changed','Changed member '.$m['membership_no'].' status to '.$new.'.');$msg=$m['full_name'].' is now '.ucfirst($new).'.';
            }elseif($memberAction==='reset_password'){
                $temporaryPassword='Fgck'.date('Y').'!'.strtoupper(bin2hex(random_bytes(3)));
                $pdo->prepare("UPDATE members SET password_hash=?,status='active' WHERE id=?")->execute([password_hash($temporaryPassword,PASSWORD_DEFAULT),$id]);
                log_activity($pdo,null,(int)$admin['id'],'member_password_reset','Reset password for member '.$m['membership_no'].'.');
                if(filter_var($m['email'],FILTER_VALIDATE_EMAIL)){
                    $body='<p>Hello '.e($m['full_name']).',</p><p>Your FGCK Joyland member portal password has been reset by an administrator.</p><p><b>Membership number:</b> '.e($m['membership_no']).'<br><b>Temporary password:</b> <code>'.e($temporaryPassword).'</code></p><p>Please sign in and change your password immediately.</p>';
                    $sent=send_system_email($m['email'],$m['full_name'],'Your FGCK Joyland Member Password Was Reset',$body);
                    $msg=$sent?'Temporary password generated and emailed to the member.':'Temporary password generated, but email delivery failed. Check Email Health.';
                }else $msg='Temporary password generated, but this member has no valid email address.';
            }else throw new RuntimeException('Unknown member action.');
        }
    }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'The requested administrator action could not be completed.';}
}
$members=(int)$pdo->query("SELECT COUNT(*) FROM members WHERE status='active'")->fetchColumn();
$inactiveMembers=(int)$pdo->query("SELECT COUNT(*) FROM members WHERE status='inactive'")->fetchColumn();
$staff=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$inactiveStaff=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn();
$leaders=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='church_leader' AND status='active'")->fetchColumn();
$pastors=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='pastor' AND status='active'")->fetchColumn();
$today=(int)$pdo->query("SELECT (SELECT COUNT(*) FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE s.appointment_date=CURDATE() AND a.status IN('pending','confirmed'))+(SELECT COUNT(*) FROM leader_appointments la JOIN appointment_slots s ON s.id=la.slot_id WHERE s.appointment_date=CURDATE() AND la.status IN('pending','confirmed'))")->fetchColumn();
$pending=(int)$pdo->query("SELECT (SELECT COUNT(*) FROM appointments WHERE status='pending')+(SELECT COUNT(*) FROM leader_appointments WHERE status='pending')")->fetchColumn();
$failedLogins=(int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action='login_failed' AND created_at>=NOW()-INTERVAL 24 HOUR")->fetchColumn();
$logins=$pdo->query("SELECT l.*,u.full_name,u.role FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id WHERE l.action IN('staff_login','login_failed','member_login') ORDER BY l.created_at DESC LIMIT 12")->fetchAll();
$dashboardMembers=$pdo->query("SELECT id,membership_no,full_name,email,status,last_login_at FROM members ORDER BY created_at DESC LIMIT 8")->fetchAll();
$page_title='Administrator Dashboard';require __DIR__.'/../includes/header.php';?>
<div><?php require __DIR__.'/../includes/admin_sidebar.php';?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Administrator Dashboard</h1><p>Control access, people records, security and operational oversight from one administrative center.</p></div></div><span class="live-pill">Administration control center</span></header>
<div class="content"><div class="welcome mb-4"><h2>Welcome, <?=e($admin['full_name'])?>.</h2><p>This workspace is restricted to administrator responsibilities. Pastoral care, member ministry and church communications belong to their respective portals.</p></div>
<div class="row g-3 mb-4">
<?php foreach([['Active members',$members,'bi-people','/admin/members'],['Inactive members',$inactiveMembers,'bi-person-slash','/admin/members'],['Pastors',$pastors,'bi-person-badge','/admin/users'],['Church leaders',$leaders,'bi-megaphone','/admin/users'],['Active staff accounts',$staff,'bi-shield-check','/admin/users'],['Inactive staff',$inactiveStaff,'bi-person-lock','/admin/users'],["Today's appointments",$today,'bi-calendar-check','/admin/appointments'],['Failed logins · 24h',$failedLogins,'bi-shield-exclamation','/admin/logins']] as $k):?><div class="col-6 col-xl-3"><a href="<?=$k[3]?>" class="text-decoration-none"><div class="cardx kpi-modern h-100"><div class="icon"><i class="bi <?=$k[2]?>"></i></div><div class="label"><?=$k[0]?></div><div class="value"><?=$k[1]?></div><div class="hint">Open administration</div></div></a></div><?php endforeach;?>
</div>
<div class="row g-4">
<div class="col-xl-7"><div class="cardx p-4"><div class="analytics-head"><div><h3>Security & login audit</h3><p>Recent authentication events for administrator review.</p></div><a href="/admin/logins" class="btn btn-sm btn-outline-secondary">Open audit</a></div><div class="table-responsive"><table class="table table-clean"><thead><tr><th>Account</th><th>Role</th><th>Action</th><th>Time</th></tr></thead><tbody><?php foreach($logins as $r):?><tr><td><?=e($r['full_name']??'Unknown / unauthenticated')?></td><td><?=e($r['role']??'member')?></td><td><?=e($r['action'])?></td><td><?=e(date('d M Y, g:i A',strtotime($r['created_at'])))?></td></tr><?php endforeach;?><?php if(!$logins):?><tr><td colspan="4" class="empty">No login activity recorded.</td></tr><?php endif;?></tbody></table></div></div></div>
<div class="col-xl-5"><div class="cardx p-4"><h3 class="section-title">Administrator actions</h3><p class="text-muted small">These are administrative controls—not pastor or church-leader workflows.</p><div class="d-grid gap-2">
<a class="btn btn-primary" href="/admin/leader_register"><i class="bi bi-person-plus me-2"></i>Register Pastor / Church Leader</a>
<a class="btn btn-outline-primary" href="/admin/users"><i class="bi bi-person-gear me-2"></i>Manage Staff Access</a>
<a class="btn btn-outline-primary" href="/admin/members"><i class="bi bi-people me-2"></i>Manage Member Accounts</a>
<a class="btn btn-outline-primary" href="/admin/appointments"><i class="bi bi-calendar-check me-2"></i>Oversee Appointments</a>
<a class="btn btn-outline-primary" href="/admin/email"><i class="bi bi-envelope-check me-2"></i>Test Email Delivery</a>
<a class="btn btn-outline-primary" href="/admin/logins"><i class="bi bi-shield-check me-2"></i>Review Security Audit</a>
</div></div></div></div>
<div class="row g-4 mt-1">
<div class="col-xl-7"><div class="cardx p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="section-title mb-1">Administrator Profile</h3><p class="text-muted small mb-0">Update your profile or change your administrator password without leaving the dashboard.</p></div><span class="badge text-bg-dark">Admin only</span></div>
<?php if($msg):?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?=e($msg)?><?php if($temporaryPassword):?><br><strong>Temporary password:</strong> <code><?=e($temporaryPassword)?></code><?php endif;?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?=e($error)?></div><?php endif;?>
<form method="post" class="row g-3 mb-4"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update_profile"><div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?=e($admin['full_name'])?>" required></div><div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?=e($admin['username'])?>" disabled></div><div class="col-md-6"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?=e($admin['email'])?>" required></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=e($admin['phone']??'')?>"></div><div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Profile</button></div></form>
<form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="change_password"><div class="col-12"><h5 class="mb-0">Change administrator password</h5></div><div class="col-md-4"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required autocomplete="current-password"></div><div class="col-md-4"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" minlength="10" required autocomplete="new-password"></div><div class="col-md-4"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div><div class="col-12 text-end"><button class="btn btn-outline-primary"><i class="bi bi-key me-1"></i>Change Password</button></div></form>
</div></div>
<div class="col-xl-5"><div class="cardx p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="section-title mb-1">Member Administration</h3><p class="text-muted small mb-0">Quickly activate, deactivate or reset a member account.</p></div><a href="/admin/members" class="btn btn-sm btn-outline-primary">Open all</a></div>
<div class="table-responsive"><table class="table table-clean align-middle mb-0"><thead><tr><th>Member</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
<?php foreach($dashboardMembers as $r):?><tr><td><strong><?=e($r['full_name'])?></strong><br><small><?=e($r['membership_no'])?></small></td><td><span class="badge text-bg-<?=$r['status']==='active'?'success':'secondary'?>"><?=e(ucfirst($r['status']))?></span></td><td class="text-end"><div class="d-flex justify-content-end gap-1"><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="member_action"><input type="hidden" name="member_action" value="toggle_status"><input type="hidden" name="member_id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-<?=$r['status']==='active'?'danger':'success'?>" title="Change status"><?= $r['status']==='active'?'Off':'On' ?></button></form><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="member_action"><input type="hidden" name="member_action" value="reset_password"><input type="hidden" name="member_id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-primary" title="Reset password" onclick="return confirm('Reset this member password?')"><i class="bi bi-key"></i></button></form></div></td></tr><?php endforeach;?><?php if(!$dashboardMembers):?><tr><td colspan="3" class="empty">No member accounts found.</td></tr><?php endif;?></tbody></table></div></div></div>
</div>
<div class="row g-4 mt-1"><div class="col-12"><div class="cardx p-4"><div class="d-flex gap-3 align-items-start"><div class="icon"><i class="bi bi-shield-lock"></i></div><div><h3 class="section-title mb-1">Role separation is active</h3><p class="text-muted mb-0">Administrator access is limited to administration, access control, security auditing, member-account administration and system oversight. Pastor and Church Leader functions remain in their own portals.</p></div></div></div></div></div>
</div></main></div>
<?php require __DIR__.'/../includes/footer.php';?>
