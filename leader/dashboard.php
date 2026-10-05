<?php
require __DIR__.'/../includes/bootstrap.php'; leader_required(); $leader=staff($pdo);
$published=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE user_id=".(int)$leader['id']." AND status='published'")->fetchColumn();
$drafts=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE user_id=".(int)$leader['id']." AND status='draft'")->fetchColumn();
$reads=(int)$pdo->query("SELECT COUNT(*) FROM announcement_reads ar JOIN announcements a ON a.id=ar.announcement_id WHERE a.user_id=".(int)$leader['id'])->fetchColumn();
$events=(int)$pdo->query("SELECT COUNT(*) FROM church_events WHERE user_id=".(int)$leader['id']." AND event_date>=CURDATE()")->fetchColumn();
$appointments=(int)$pdo->query("SELECT COUNT(*) FROM leader_appointments WHERE user_id=".(int)$leader['id']." AND status IN('pending','confirmed') AND EXISTS(SELECT 1 FROM appointment_slots s WHERE s.id=leader_appointments.slot_id AND s.appointment_date>=CURDATE())")->fetchColumn();
$pastorMessages=(int)$pdo->query("SELECT COUNT(*) FROM announcements a JOIN users u ON u.id=a.user_id WHERE u.role='pastor' AND a.status='published'")->fetchColumn();
$recent=$pdo->query("SELECT title,type,status,created_at FROM announcements WHERE user_id=".(int)$leader['id']." ORDER BY created_at DESC LIMIT 6")->fetchAll();
$page_title='Church Leader Portal'; require __DIR__.'/../includes/header.php';
?>
<div><?php require __DIR__.'/../includes/leader_sidebar.php'; ?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Church Leader Portal</h1><p>Communicate clearly, consistently and pastorally with the church.</p></div></div><span class="live-pill">Communications only</span></header>
<div class="content"><div class="welcome mb-4"><h2>Welcome, <?=e($leader['full_name'])?>.</h2><p>Connect with the Pastor, organize church events and communicate responsibly with the church community.</p><a class="btn btn-light fw-bold" href="/leader/appointments"><i class="bi bi-calendar-heart me-2"></i>Request an appointment</a></div>
<div class="row g-3 mb-4">
<div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-send"></i></div><div class="label">Published</div><div class="value"><?=$published?></div><div class="hint">Messages shared</div></div></div>
<div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-file-earmark"></i></div><div class="label">Drafts</div><div class="value"><?=$drafts?></div><div class="hint">Not yet published</div></div></div>
<div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-eye"></i></div><div class="label">Member reads</div><div class="value"><?=$reads?></div><div class="hint">Engagement recorded</div></div></div>
<div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-calendar-event"></i></div><div class="label">Upcoming events</div><div class="value"><?=$events?></div><div class="hint">Events you created</div></div></div>
</div>
<div class="row g-3 mb-4"><div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-calendar-heart"></i></div><div class="label">Pastor appointments</div><div class="value"><?=$appointments?></div><div class="hint">Pending / confirmed</div></div></div><div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-chat-heart"></i></div><div class="label">Pastor messages</div><div class="value"><?=$pastorMessages?></div><div class="hint">Available to you</div></div></div></div><div class="cardx p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="section-title mb-1">Your recent communications</h3><p class="text-muted small mb-0">A record of messages you have created.</p></div><a class="btn btn-primary btn-sm" href="/leader/communications">Manage messages</a></div>
<div class="table-responsive"><table class="table table-clean align-middle"><thead><tr><th>Message</th><th>Type</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach($recent as $r):?><tr><td><strong><?=e($r['title'])?></strong></td><td><?=e(ucwords(str_replace('_',' ',$r['type'])))?></td><td><?=e(ucfirst($r['status']))?></td><td><?=e(date('d M Y, g:i A',strtotime($r['created_at'])))?></td></tr><?php endforeach;?>
<?php if(!$recent):?><tr><td colspan="4" class="empty">No communications yet.</td></tr><?php endif;?></tbody></table></div></div>
</div></main></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
