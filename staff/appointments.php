<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();
$staff=staff($pdo);$msg='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$id=(int)($_POST['id']??0);$kind=(string)($_POST['kind']??'member');$action=(string)($_POST['action']??'');
    try{
        $table=$kind==='leader'?'leader_appointments':'appointments';
        if(!in_array($kind,['member','leader'],true))throw new RuntimeException('Invalid appointment source.');
        if($action==='adjust_time'){
            $start=trim((string)($_POST['start_time']??''));$end=trim((string)($_POST['end_time']??''));
            if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$start)||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$end))throw new RuntimeException('Enter a valid start and end time.');
            $pdo->beginTransaction();
            if($kind==='member'){
                $q=$pdo->prepare("SELECT a.*,m.full_name,m.id AS member_id,s.appointment_date,s.start_time AS slot_start,s.end_time AS slot_end FROM appointments a JOIN members m ON m.id=a.member_id JOIN appointment_slots s ON s.id=a.slot_id WHERE a.id=? FOR UPDATE");$q->execute([$id]);$a=$q->fetch();
            }else{
                $q=$pdo->prepare("SELECT la.*,u.full_name,s.appointment_date,s.start_time AS slot_start,s.end_time AS slot_end FROM leader_appointments la JOIN users u ON u.id=la.user_id JOIN appointment_slots s ON s.id=la.slot_id WHERE la.id=? FOR UPDATE");$q->execute([$id]);$a=$q->fetch();
            }
            if(!$a)throw new RuntimeException('Appointment not found.');
            if(!in_array($a['status'],['pending','confirmed'],true))throw new RuntimeException('Only pending or confirmed appointments can be adjusted.');
            if($a['appointment_date']<date('Y-m-d')||!is_wednesday($a['appointment_date']))throw new RuntimeException('Only future Wednesday appointments can be adjusted.');
            $startTs=strtotime($a['appointment_date'].' '.$start);$endTs=strtotime($a['appointment_date'].' '.$end);if($endTs<=$startTs)throw new RuntimeException('End time must be after start time.');
            $mins=(int)(($endTs-$startTs)/60);if($mins<5||$mins>120||((int)date('i',$startTs)%5)!==0||((int)date('i',$endTs)%5)!==0)throw new RuntimeException('Use a duration of 5–120 minutes in 5-minute increments.');
            $open=setting($pdo,'opening_time','09:00');$close='15:00';if($start<$open||$end>$close)throw new RuntimeException("Adjusted time must stay between $open and $close.");
            $otherMember="SELECT a.id FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE a.id<>? AND a.status IN('pending','confirmed') AND s.appointment_date=? AND COALESCE(a.adjusted_start_time,s.start_time) < ? AND COALESCE(a.adjusted_end_time,s.end_time) > ?";
            $otherLeader="SELECT la.id FROM leader_appointments la JOIN appointment_slots s ON s.id=la.slot_id WHERE la.id<>? AND la.status IN('pending','confirmed') AND s.appointment_date=? AND COALESCE(la.adjusted_start_time,s.start_time) < ? AND COALESCE(la.adjusted_end_time,s.end_time) > ?";
            $q=$pdo->prepare($otherMember);$q->execute([$kind==='member'?$id:0,$a['appointment_date'],$end.':00',$start.':00']);if($q->fetch())throw new RuntimeException('The new time overlaps another member appointment.');
            $q=$pdo->prepare($otherLeader);$q->execute([$kind==='leader'?$id:0,$a['appointment_date'],$end.':00',$start.':00']);if($q->fetch())throw new RuntimeException('The new time overlaps another Church Leader appointment.');
            $oldStart=$a['adjusted_start_time']?:$a['slot_start'];$oldEnd=$a['adjusted_end_time']?:$a['slot_end'];
            $pdo->prepare("UPDATE $table SET adjusted_start_time=?,adjusted_end_time=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$start.':00',$end.':00',$id]);
            if($kind==='member'){rebuild_day_queue($pdo,$a['appointment_date'],$id);notify_member($pdo,(int)$a['member_id'],'Appointment time adjusted',$a['appointment_no'].': your appointment has changed to '.fmt_time($start).' – '.fmt_time($end).'.','info');log_activity($pdo,null,$staff['id'],'appointment_time_adjusted',$a['appointment_no'].' '.$oldStart.'-'.$oldEnd.' -> '.$start.'-'.$end);}
            else{notify_user($pdo,(int)$a['user_id'],'Appointment time adjusted',$a['appointment_no'].': your appointment has changed to '.fmt_time($start).' – '.fmt_time($end).'.','info');log_activity($pdo,null,$staff['id'],'leader_appointment_time_adjusted',$a['appointment_no'].' '.$oldStart.'-'.$oldEnd.' -> '.$start.'-'.$end);}
            $pdo->commit();
            if($kind==='member')email_member_time_change($pdo,$id,$oldStart,$oldEnd);else email_leader_appointment_status($pdo,$id,'confirmed');
            $msg='Appointment time updated successfully.';
        }else{
            $map=['confirm'=>['confirmed','Appointment confirmed.','success'],'decline'=>['declined','Appointment declined.','warning'],'complete'=>['completed','Appointment completed.','success'],'cancel'=>['cancelled','Appointment cancelled.','warning'],'no_show'=>['no_show','Appointment marked as no-show.','warning']];
            if(!isset($map[$action]))throw new RuntimeException('Unknown appointment action.');[$status,$text,$type]=$map[$action];
            if($kind==='member'){$q=$pdo->prepare("SELECT a.*,m.id AS member_id FROM appointments a JOIN members m ON m.id=a.member_id WHERE a.id=?");$q->execute([$id]);$a=$q->fetch();}
            else{$q=$pdo->prepare("SELECT la.*,u.id AS user_id FROM leader_appointments la JOIN users u ON u.id=la.user_id WHERE la.id=?");$q->execute([$id]);$a=$q->fetch();}
            if(!$a)throw new RuntimeException('Appointment not found.');
            $allowed=['pending'=>['confirmed','declined','cancelled'],'confirmed'=>['completed','no_show','cancelled']];if(!in_array($status,$allowed[$a['status']]??[],true))throw new RuntimeException('That appointment cannot be moved to the selected status.');
            $pdo->beginTransaction();$fields='status=?';$params=[$status];if($status==='confirmed')$fields.=',confirmed_at=NOW()';if($status==='completed')$fields.=',completed_at=NOW()';if($status==='cancelled')$fields.=',cancelled_at=NOW()';$params[]=$id;$pdo->prepare("UPDATE $table SET $fields WHERE id=?")->execute($params);
            if($kind==='member'){notify_member($pdo,(int)$a['member_id'],'Appointment update',$a['appointment_no'].': '.$text,$type);log_activity($pdo,null,$staff['id'],'appointment_status',$a['appointment_no'].' -> '.$status);}
            else{notify_user($pdo,(int)$a['user_id'],'Pastor appointment update',$a['appointment_no'].': '.$text,$type);log_activity($pdo,null,$staff['id'],'leader_appointment_status',$a['appointment_no'].' -> '.$status);}
            $pdo->commit();
            if($kind==='member')email_member_status_update($pdo,$id,$status);else email_leader_appointment_status($pdo,$id,$status);
            $msg=$text;
        }
<<<<<<< HEAD
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'The appointment could not be updated.';}
=======
    } else {
        $map = [
            'confirm'  => ['confirmed','Appointment confirmed.','success'],
            'decline'  => ['declined','Appointment declined.','warning'],
            'complete' => ['completed','Appointment completed.','success'],
            'cancel'   => ['cancelled','Appointment cancelled.','warning'],
            'no_show'  => ['no_show','Appointment marked as no-show.','warning']
        ];
        $allowed = [
            'pending'   => ['confirmed','declined','cancelled'],
            'confirmed' => ['completed','no_show','cancelled']
        ];

        if (isset($map[$action])) {
            [$status,$text,$type] = $map[$action];
            $q = $pdo->prepare("SELECT a.*, m.id AS member_id FROM appointments a JOIN members m ON m.id=a.member_id WHERE a.id=?");
            $q->execute([$id]);
            $a = $q->fetch();

            if (!$a) {
                $error = 'Appointment not found.';
            } elseif (!in_array($status, $allowed[$a['status']] ?? [], true)) {
                $error = 'That appointment cannot be moved to the selected status.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $fields = 'status=?';
                    $params = [$status];
                    if ($status === 'confirmed') $fields .= ',confirmed_at=NOW()';
                    if ($status === 'completed') $fields .= ',completed_at=NOW()';
                    if ($status === 'cancelled') $fields .= ",cancelled_by='pastor',cancelled_at=NOW()";
                    $params[] = $id;
                    $pdo->prepare("UPDATE appointments SET $fields WHERE id=?")->execute($params);
                    notify_member($pdo,(int)$a['member_id'],'Appointment update',$a['appointment_no'].': '.$text,$type);
                    notify_user($pdo,(int)$staff['id'],'Appointment updated',$a['appointment_no'].' has been changed to '.str_replace('_',' ',$status).'.','info');
                    log_activity($pdo,null,$staff['id'],'appointment_status',$a['appointment_no'].' -> '.$status);
                    $pdo->commit();

                    // SMS is an additional notification and never blocks the database update.
                    try {
                        $memberSms = $pdo->prepare("SELECT full_name, phone FROM members WHERE id=? LIMIT 1");
                        $memberSms->execute([(int)$a['member_id']]);
                        $memberRow = $memberSms->fetch();
                        if ($memberRow && !empty($memberRow['phone'])) {
                            $smsText = 'Praise the Lord ' . $memberRow['full_name'] . '. FGCK Joyland: your appointment ' . $a['appointment_no'] . ' has been ' . str_replace('_',' ',$status) . '. Please log in to your member portal for details.';
                            send_sms((string)$memberRow['phone'], $smsText);
                        }
                    } catch (Throwable $smsError) {
                        sms_log('APPOINTMENT STATUS SMS ERROR: ' . $smsError->getMessage());
                    }

                    // The portal notification is immediate; email is an additional notification.
                    email_member_status_update($pdo, $id, $status);
                    $msg = $text;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'The appointment could not be updated.';
                }
            }
        }
    }
>>>>>>> 3095147f1de9cc3fe682800509762cfc6ea2edc1
}

$filter=$_GET['status']??'all';$valid=['all','pending','confirmed','completed','cancelled','declined','no_show'];if(!in_array($filter,$valid,true))$filter='all';
$memberSql="SELECT a.id,a.appointment_no,a.purpose,a.status,a.adjusted_start_time,a.adjusted_end_time,m.full_name,m.phone,m.email,m.id AS member_id,s.appointment_date,s.start_time AS slot_start,s.end_time AS slot_end,COALESCE(a.adjusted_start_time,s.start_time) display_start,COALESCE(a.adjusted_end_time,s.end_time) display_end,'member' kind FROM appointments a JOIN members m ON m.id=a.member_id JOIN appointment_slots s ON s.id=a.slot_id";
$leaderSql="SELECT la.id,la.appointment_no,la.purpose,la.status,la.adjusted_start_time,la.adjusted_end_time,u.full_name,u.phone,u.email,u.id AS user_id,s.appointment_date,s.start_time AS slot_start,s.end_time AS slot_end,COALESCE(la.adjusted_start_time,s.start_time) display_start,COALESCE(la.adjusted_end_time,s.end_time) display_end,'leader' kind FROM leader_appointments la JOIN users u ON u.id=la.user_id JOIN appointment_slots s ON s.id=la.slot_id";
$memberParams=[];$leaderParams=[];if($filter!=='all'){$memberSql.=' WHERE a.status=?';$memberParams[]=$filter;$leaderSql.=' WHERE la.status=?';$leaderParams[]=$filter;}
$memberSql.=' ORDER BY s.appointment_date DESC,display_start DESC LIMIT 300';$leaderSql.=' ORDER BY s.appointment_date DESC,display_start DESC LIMIT 300';
$q=$pdo->prepare($memberSql);$q->execute($memberParams);$rows=$q->fetchAll();$q=$pdo->prepare($leaderSql);$rows=array_merge($rows,($q->execute($leaderParams)?$q->fetchAll():[]));usort($rows,function($a,$b){$x=($a['appointment_date'].' '.$a['display_start']);$y=($b['appointment_date'].' '.$b['display_start']);return $x===$y?0:($x>$y?-1:1);});
$page_title='Appointments';require __DIR__.'/../includes/header.php';
?>
<div><?php require __DIR__.'/../includes/staff_sidebar.php';?><main class="main"><header class="topbar"><div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Appointments</h1><p>Manage member and Church Leader requests, confirmations and appointment times.</p></div></div><span class="live-pill">Pastoral operations</span></header><div class="content"><div class="cardx p-4">
<?php if($msg):?><div class="alert alert-success small"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger small"><?=e($error)?></div><?php endif;?><div class="d-flex gap-2 flex-wrap mb-3"><?php foreach($valid as $f):?><a class="btn btn-sm <?=($filter===$f?'btn-primary':'btn-outline-secondary')?>" href="?status=<?=$f?>"><?=ucwords(str_replace('_',' ',$f))?></a><?php endforeach;?></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Person</th><th>Type</th><th>Appointment</th><th>Purpose</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $a):?><tr><td><b><?=e($a['full_name'])?></b><br><small><?=e($a['phone']?:$a['email'])?></small></td><td><span class="badge text-bg-<?=$a['kind']==='leader'?'primary':'secondary'?>"><?= $a['kind']==='leader'?'Church Leader':'Member' ?></span></td><td><?=e(fmt_date($a['appointment_date']))?><br><strong><?=e(fmt_time($a['display_start']))?> – <?=e(fmt_time($a['display_end']))?></strong><?php if($a['adjusted_start_time']):?><br><small class="text-primary">Adjusted</small><?php endif;?><br><small><?=e($a['appointment_no'])?></small></td><td><?=e($a['purpose'])?></td><td><?=e(ucwords(str_replace('_',' ',$a['status'])))?></td><td><?php if(in_array($a['status'],['pending','confirmed'],true)):?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$a['id']?>"><input type="hidden" name="kind" value="<?=$a['kind']?>"><?php if($a['status']==='pending'):?><button name="action" value="confirm" class="btn btn-sm btn-success">Confirm</button><button name="action" value="decline" class="btn btn-sm btn-outline-danger">Decline</button><button name="action" value="cancel" class="btn btn-sm btn-outline-secondary">Cancel</button><?php else:?><button name="action" value="complete" class="btn btn-sm btn-primary">Complete</button><button name="action" value="no_show" class="btn btn-sm btn-outline-secondary">No-show</button><button name="action" value="cancel" class="btn btn-sm btn-outline-danger">Cancel</button><?php endif;?></form><button type="button" class="btn btn-sm btn-outline-primary mt-1 adjust-btn" data-bs-toggle="modal" data-bs-target="#adjustModal" data-id="<?=$a['id']?>" data-kind="<?=$a['kind']?>" data-person="<?=e($a['full_name'])?>" data-start="<?=e(substr($a['display_start'],0,5))?>" data-end="<?=e(substr($a['display_end'],0,5))?>"><i class="bi bi-clock-history"></i> Adjust Time</button><?php else:?><span class="text-muted">—</span><?php endif;?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="empty">No appointments found.</td></tr><?php endif;?></tbody></table></div></div></div></main></div>
<div class="modal fade" id="adjustModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4 shadow"><form method="post" id="adjustForm"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" id="adjustId"><input type="hidden" name="kind" id="adjustKind"><input type="hidden" name="action" value="adjust_time"><div class="modal-header"><div><h5 class="modal-title">Adjust Appointment Time</h5><div class="small text-muted" id="adjustPerson"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-6"><label class="form-label">Start time</label><input type="time" class="form-control" name="start_time" id="adjustStart" step="300" required></div><div class="col-6"><label class="form-label">End time</label><input type="time" class="form-control" name="end_time" id="adjustEnd" step="300" required></div></div><div class="small text-muted mt-3">The person will receive a portal notification and email after the change.</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save New Time</button></div></form></div></div></div>
<script>(function(){document.querySelectorAll('.adjust-btn').forEach(b=>b.addEventListener('click',()=>{document.getElementById('adjustId').value=b.dataset.id;document.getElementById('adjustKind').value=b.dataset.kind;document.getElementById('adjustPerson').textContent=b.dataset.person+' • Current: '+b.dataset.start+' – '+b.dataset.end;document.getElementById('adjustStart').value=b.dataset.start;document.getElementById('adjustEnd').value=b.dataset.end;}));})();</script>
<?php require __DIR__.'/../includes/footer.php';?>
