<?php
require __DIR__.'/../includes/bootstrap.php'; admin_required(); $admin=staff($pdo);$msg='';$error='';$sent=false;$health=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $postAction=(string)($_POST['email_action']??'send_test');
        if($postAction==='connection_test'){
            $health=email_health_check();
            if($health['connected']) $msg='Email transport health check passed: '.$health['message']; else throw new RuntimeException($health['message']);
        }else{
            $to=trim((string)($_POST['to']??$admin['email']));if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid test recipient email.');
        $body='<p>This is a live email delivery test from the FGCK Joyland Administrator Control Center.</p><p>If you received this message, the portal SMTP/email path is working for this recipient.</p><p><b>Test time:</b> '.e(date('Y-m-d H:i:s')).'</p>';
        $sent=send_system_email($to,$admin['full_name'],'FGCK Joyland Email Delivery Test',$body);
        log_activity($pdo,null,$admin['id'],'email_test',$sent?'Successful email test to '.$to.'.':'Failed email test to '.$to.'.');
        if($sent)$msg='The email was accepted by the configured delivery service. Check the recipient inbox/spam folder.';else throw new RuntimeException('The email delivery test failed. Review the email log and SMTP settings.');
        }
    }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'The email test could not be completed.';}
}
$health=$health??['connected'=>false,'message'=>'Click “Test SMTP Connection” to perform a live transport check.'];
$logPath=__DIR__.'/../storage/email.log';$log=is_file($logPath)?file($logPath,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES):[];$log=array_slice($log?:[], -80);
$page_title='Email Health';require __DIR__.'/../includes/header.php';?>
<div><?php require __DIR__.'/../includes/admin_sidebar.php';?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Email Health</h1><p>Test and audit the email delivery path used by member, pastor and leader notifications.</p></div></div><span class="live-pill">Notification delivery</span></header><div class="content"><div class="row g-4"><div class="col-xl-5"><div class="cardx p-4"><h3 class="section-title">Send a live test</h3><p class="text-muted small">This uses the same SMTP/email service used by registration, appointments and church communications.</p><?php if($msg):?><div class="alert alert-success"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="email_action" value="send_test"><label class="form-label">Test recipient</label><input class="form-control mb-3" type="email" name="to" value="<?=e($admin['email'])?>" required><button class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Test Email</button></form><form method="post" class="mt-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="email_action" value="connection_test"><button class="btn btn-outline-primary"><i class="bi bi-plug me-1"></i>Test SMTP Connection</button></form><div class="mt-4 p-3 rounded border"><div class="d-flex justify-content-between"><strong>Transport status</strong><span class="badge text-bg-<?=$health['connected']?'success':'warning'?>"><?=$health['connected']?'Healthy':'Needs attention'?></span></div><small class="text-muted d-block mt-2"><?=e($health['message'])?></small><small class="text-muted d-block mt-2">SMTP host: <?=e((string)FGCK_RUNTIME_SMTP_HOST)?> · Port: <?=e((string)FGCK_RUNTIME_SMTP_PORT)?> · Encryption: <?=e((string)FGCK_RUNTIME_SMTP_ENCRYPTION)?></small></div><div class="alert alert-info small mt-4 mb-0">A successful test means the SMTP service accepted the message. Actual inbox delivery can still be affected by recipient spam filtering or provider policy.</div></div></div><div class="col-xl-7"><div class="cardx p-4"><h3 class="section-title">Recent email log</h3><pre style="max-height:520px;overflow:auto;white-space:pre-wrap;font-size:11px;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px"><?=e(implode("\n",$log) ?: 'No email activity has been logged yet.')?></pre></div></div></div></div></main></div>
<?php require __DIR__.'/../includes/footer.php';?>
