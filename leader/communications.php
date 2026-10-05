<?php
require __DIR__.'/../includes/bootstrap.php'; leader_required(); $leader=staff($pdo); $msg=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $action=$_POST['action']??'';
 try{
  if($action==='create'){
   $title=trim((string)($_POST['title']??'')); $message=trim((string)($_POST['message']??''));
   $type=(string)($_POST['type']??'announcement'); $priority=(string)($_POST['priority']??'normal'); $status=(string)($_POST['status']??'published');
   if($title===''||mb_strlen($title)>180) throw new RuntimeException('Please provide a message title up to 180 characters.');
   if($message==='') throw new RuntimeException('Please write the message.');
   $allowedT=['announcement','prayer','pastoral','urgent','reminder']; $allowedP=['normal','important','urgent'];
   if(!in_array($type,$allowedT,true)||!in_array($priority,$allowedP,true)||!in_array($status,['draft','published'],true)) throw new RuntimeException('Invalid communication options.');
   $publishedAt=$status==='published'?date('Y-m-d H:i:s'):null;
   $pdo->beginTransaction();
   $q=$pdo->prepare("INSERT INTO announcements(user_id,title,message,type,priority,audience,status,published_at) VALUES(?,?,?,?,?,'all',?,?)");
   $q->execute([(int)$leader['id'],$title,$message,$type,$priority,$status,$publishedAt]);
   $id=(int)$pdo->lastInsertId();
   if($status==='published'){
    $nt=$priority==='urgent'?'danger':($type==='prayer'||$type==='pastoral'?'info':'success');
    $recipients=notify_all_members($pdo,$title,$message,$nt);
    $pdo->commit();
    $emails=email_members_about_announcement($pdo,$id);
    log_activity($pdo,null,(int)$leader['id'],'leader_announcement_published','Published leader announcement #'.$id.' to '.$recipients.' member(s). Emails sent: '.$emails);
    $msg='Message published and member notifications created; email notifications were processed.';
   }else{$pdo->commit();log_activity($pdo,null,(int)$leader['id'],'leader_announcement_draft','Saved leader announcement draft #'.$id);$msg='Draft saved.';}
  } elseif($action==='archive'){
   $id=(int)($_POST['id']??0); $q=$pdo->prepare("UPDATE announcements SET status='archived' WHERE id=? AND user_id=? AND status<>'archived'");$q->execute([$id,(int)$leader['id']]);$msg=$q->rowCount()?'Message archived.':'Message not found.';
  }
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'The communication could not be completed.';}
}
$q=$pdo->prepare("SELECT a.*,(SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id=a.id) read_count FROM announcements a WHERE a.user_id=? ORDER BY a.created_at DESC LIMIT 30");$q->execute([(int)$leader['id']]);$rows=$q->fetchAll();
$page_title='Messages & Notices';require __DIR__.'/../includes/header.php';
?>
<div><?php require __DIR__.'/../includes/leader_sidebar.php';?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Messages & Notices</h1><p>Publish clear, timely communication to church members.</p></div></div><span class="live-pill">Member communications</span></header>
<div class="content"><?php if($msg):?><div class="alert alert-success"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="row g-4"><div class="col-xl-5"><div class="cardx p-4"><h3 class="section-title">Create a message</h3><p class="text-muted small">Leaders can communicate announcements, prayer points, reminders and notices.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create">
<label class="form-label">Title</label><input class="form-control mb-3" name="title" maxlength="180" required>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Type</label><select name="type" class="form-select"><option value="announcement">Announcement</option><option value="prayer">Prayer</option><option value="pastoral">Pastoral</option><option value="reminder">Reminder</option><option value="urgent">Urgent</option></select></div><div class="col-md-6"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div></div>
<label class="form-label mt-3">Message</label><textarea class="form-control mb-3" name="message" rows="9" required></textarea><div class="d-flex gap-2"><button class="btn btn-outline-secondary" name="status" value="draft">Save draft</button><button class="btn btn-primary" name="status" value="published"><i class="bi bi-send me-1"></i>Publish & notify</button></div></form></div></div>
<div class="col-xl-7"><div class="cardx p-4"><h3 class="section-title">Your communication history</h3><div class="table-responsive"><table class="table table-clean align-middle"><thead><tr><th>Message</th><th>Reads</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?=e($r['title'])?></strong><br><small class="text-muted"><?=e(ucfirst($r['type']))?> · <?=e(date('d M Y, g:i A',strtotime($r['created_at'])))?></small></td><td><?=$r['read_count']?></td><td><?=e(ucfirst($r['status']))?></td><td><?php if($r['status']!=='archived'):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-secondary">Archive</button></form><?php endif;?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4" class="empty">No messages yet.</td></tr><?php endif;?></tbody></table></div></div></div></div></div></main></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
