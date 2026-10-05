<?php
require __DIR__.'/../includes/bootstrap.php';
leader_required();
$leader=staff($pdo); $msg=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        if(($_POST['action']??'')!=='book') throw new RuntimeException('Unknown appointment action.');
        $date=trim((string)($_POST['appointment_date']??''));
        $sid=(int)($_POST['slot_id']??0);
        $purpose=trim((string)($_POST['purpose']??''));
        $other=trim((string)($_POST['other_purpose']??''));
        $notes=trim((string)($_POST['notes']??''));
        if(setting($pdo,'office_status','available')!=='available') throw new RuntimeException("The pastor's office is currently closed. Please check again later.");
        $dt=DateTime::createFromFormat('Y-m-d',$date);
        if(!$dt || $dt->format('Y-m-d')!==$date || !is_wednesday($date) || $date<date('Y-m-d')) throw new RuntimeException('Please select a valid future Wednesday.');
        if($sid<=0) throw new RuntimeException('Please select an available appointment slot.');
        if($purpose==='Other'){ if($other==='') throw new RuntimeException('Please specify the purpose of the appointment.'); $purpose='Other: '.$other; }
        if($purpose==='' || mb_strlen($purpose)>120) throw new RuntimeException('Please provide a valid appointment purpose up to 120 characters.');
        ensure_wednesday_slots($pdo,$date);
        $pdo->beginTransaction();
        [$open,$close,$minutes]=appointment_schedule($pdo);
        $q=$pdo->prepare("SELECT s.* FROM appointment_slots s WHERE s.id=? AND s.appointment_date=? AND s.availability='available' AND s.start_time>=? AND s.end_time<=? FOR UPDATE");
        $q->execute([$sid,$date,$open.':00',$close.':00']); $slot=$q->fetch();
        if(!$slot) throw new RuntimeException('That appointment slot is unavailable.');
        $q=$pdo->prepare("SELECT id FROM appointments WHERE slot_id=? AND status IN('pending','confirmed') FOR UPDATE");$q->execute([$sid]);
        if($q->fetch()) throw new RuntimeException('That slot has already been booked. Please choose another time.');
        $q=$pdo->prepare("SELECT id FROM leader_appointments WHERE slot_id=? AND status IN('pending','confirmed') FOR UPDATE");$q->execute([$sid]);
        if($q->fetch()) throw new RuntimeException('That slot has already been booked. Please choose another time.');
        $q=$pdo->prepare("SELECT la.id FROM leader_appointments la JOIN appointment_slots s ON s.id=la.slot_id WHERE la.user_id=? AND la.status IN('pending','confirmed') AND s.appointment_date>=CURDATE() LIMIT 1 FOR UPDATE");$q->execute([(int)$leader['id']]);
        if($q->fetch()) throw new RuntimeException('You already have an upcoming appointment with the Pastor.');
        $ref='FGCK-LDR-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        $q=$pdo->prepare("INSERT INTO leader_appointments(appointment_no,user_id,slot_id,purpose,notes) VALUES(?,?,?,?,?)");
        $q->execute([$ref,(int)$leader['id'],$sid,$purpose,$notes]);
        $id=(int)$pdo->lastInsertId();
        notify_all_pastors($pdo,'New Church Leader appointment request',$leader['full_name'].' requested an appointment for '.fmt_date($date).' at '.fmt_time($slot['start_time']).'. Reference: '.$ref,'warning');
        log_activity($pdo,null,(int)$leader['id'],'leader_appointment_created','Created leader appointment '.$ref.'.');
        $pdo->commit();
        $emailCount=email_pastors_about_leader_booking($pdo,$id);
        $msg='Appointment request submitted successfully. The Pastor has been notified'.($emailCount?' by email.':'.');
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $error=$e instanceof RuntimeException?$e->getMessage():'The appointment could not be submitted.'; }
}

$dates=[];$d=new DateTimeImmutable('next wednesday'); for($i=0;$i<8;$i++){ $dates[]=$d->format('Y-m-d'); $d=$d->modify('+7 days'); }
$selected=(string)($_GET['date']??($dates[0]??date('Y-m-d'))); if(!in_array($selected,$dates,true))$selected=$dates[0];
$slots=schedule_slots($pdo,$selected);
$q=$pdo->prepare("SELECT la.*,s.appointment_date,COALESCE(la.adjusted_start_time,s.start_time) AS start_time,COALESCE(la.adjusted_end_time,s.end_time) AS end_time FROM leader_appointments la JOIN appointment_slots s ON s.id=la.slot_id WHERE la.user_id=? ORDER BY s.appointment_date DESC,start_time DESC LIMIT 20");$q->execute([(int)$leader['id']]);$mine=$q->fetchAll();
$page_title='Appointments with Pastor';require __DIR__.'/../includes/header.php';
?>
<div><?php require __DIR__.'/../includes/leader_sidebar.php';?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Appointments with Pastor</h1><p>Request a private conversation with the Pastor and track the response.</p></div></div><span class="live-pill">Pastoral access</span></header><div class="content">
<?php if($msg):?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?=e($error)?></div><?php endif;?>
<div class="row g-4"><div class="col-xl-5"><div class="cardx p-4"><h3 class="section-title">Request an appointment</h3><p class="text-muted small">Appointments use the Pastor's published Wednesday availability. A request remains pending until the Pastor confirms it.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="book"><div class="mb-3"><label class="form-label">Wednesday</label><select name="appointment_date" class="form-select" onchange="location='?date='+this.value"><?php foreach($dates as $date):?><option value="<?=$date?>" <?=$date===$selected?'selected':''?>><?=e(fmt_date($date))?></option><?php endforeach;?></select></div><div class="mb-3"><label class="form-label">Available time</label><select name="slot_id" class="form-select" required><option value="">Select a time</option><?php foreach($slots as $s):?><option value="<?=$s['id']?>" <?=$s['booked']?'disabled':''?>><?=e(fmt_time($s['start_time']).' – '.fmt_time($s['end_time']))?><?=$s['booked']?' — Booked':''?></option><?php endforeach;?></select></div><div class="mb-3"><label class="form-label">Purpose</label><select name="purpose" id="purpose" class="form-select" required><option value="">Select purpose</option><option>Pastoral counselling</option><option>Ministry discussion</option><option>Leadership guidance</option><option>Prayer</option><option>Church planning</option><option value="Other">Other</option></select></div><div class="mb-3" id="otherWrap" style="display:none"><label class="form-label">Please specify</label><input name="other_purpose" class="form-control" maxlength="110"></div><div class="mb-3"><label class="form-label">Notes <span class="text-muted">(optional)</span></label><textarea name="notes" class="form-control" rows="4" maxlength="2000"></textarea></div><button class="btn btn-primary w-100"><i class="bi bi-calendar-plus me-2"></i>Request Appointment</button></form></div></div>
<div class="col-xl-7"><div class="cardx p-4"><h3 class="section-title">Your appointment history</h3><div class="table-responsive"><table class="table table-clean align-middle"><thead><tr><th>Date & time</th><th>Purpose</th><th>Status</th><th>Reference</th></tr></thead><tbody><?php foreach($mine as $a):?><tr><td><strong><?=e(fmt_date($a['appointment_date']))?></strong><br><?=e(fmt_time($a['start_time']).' – '.fmt_time($a['end_time']))?></td><td><?=e($a['purpose'])?></td><td><span class="badge text-bg-<?=in_array($a['status'],['confirmed','completed'],true)?'success':($a['status']==='pending'?'warning':($a['status']==='declined'?'danger':'secondary'))?>"><?=e(ucwords(str_replace('_',' ',$a['status'])))?></span></td><td><small><?=e($a['appointment_no'])?></small></td></tr><?php endforeach;?><?php if(!$mine):?><tr><td colspan="4" class="empty">No appointments yet.</td></tr><?php endif;?></tbody></table></div></div></div></div></div></main></div>
<script>document.getElementById('purpose')?.addEventListener('change',function(){document.getElementById('otherWrap').style.display=this.value==='Other'?'block':'none';});</script>
<?php require __DIR__.'/../includes/footer.php';?>
