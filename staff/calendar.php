<?php require __DIR__.'/../includes/bootstrap.php';staff_required();$date=$_GET['date']??next_wednesday();if(!is_wednesday($date)||$date<date('Y-m-d'))$date=next_wednesday();ensure_wednesday_slots($pdo,$date);[$open,$close,$slotMinutes]=appointment_schedule($pdo);
$q=$pdo->prepare("SELECT s.*,COALESCE(a.appointment_no,la.appointment_no) appointment_no,COALESCE(a.status,la.status) status,COALESCE(a.purpose,la.purpose) purpose,COALESCE(m.full_name,u.full_name) full_name,COALESCE(m.phone,u.phone) phone,COALESCE(a.adjusted_start_time,la.adjusted_start_time,s.start_time) display_start,COALESCE(a.adjusted_end_time,la.adjusted_end_time,s.end_time) display_end,CASE WHEN la.id IS NOT NULL THEN 'Church Leader' WHEN a.id IS NOT NULL THEN 'Member' ELSE '' END person_type FROM appointment_slots s LEFT JOIN appointments a ON a.slot_id=s.id AND a.status IN('pending','confirmed') LEFT JOIN members m ON m.id=a.member_id LEFT JOIN leader_appointments la ON la.slot_id=s.id AND la.status IN('pending','confirmed') LEFT JOIN users u ON u.id=la.user_id WHERE s.appointment_date=? AND s.start_time>=? AND s.end_time<=? AND TIME_TO_SEC(TIMEDIFF(s.end_time,s.start_time))=1800 ORDER BY s.start_time");$q->execute([$date,$open.':00',$close.':00']);$rows=$q->fetchAll();$page_title='Calendar';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Wednesday Calendar</h1>
                    <p>One screen for the full pastor schedule.</p>
                </div>
            </div>
        </header>
        <div class="content">
            <div class="cardx p-4 mb-4">
                <h3 class="section-title">
                    <?=e(fmt_date($date))?>
                </h3><input id="d" class="form-control mt-3" style="max-width:280px" type="date" value="<?=e($date)?>">
            </div>
            <div class="row g-3">
                <?php foreach($rows as $r):?>
                <div class="col-md-6 col-xl-4">
                    <div class="calendar-day">
                        <div class="d-flex justify-content-between"><b>
                                <?=e(fmt_time($r['display_start']))?>
                            </b><span
                                class="badge <?= $r['appointment_no']?'text-bg-warning':($r['availability']==='available'?'text-bg-success':'text-bg-secondary')?>">
                                <?=$r['appointment_no']?e(ucfirst($r['status'])):ucfirst($r['availability'])?>
                            </span></div>
                        <?php if($r['appointment_no']):?>
                        <hr><b>
                            <?=e($r['full_name'])?>
                        </b>
                        <div class="small text-muted">
                            <?=e($r['phone'])?>
                        </div>
                        <div class="small mt-2">
                            <?=e($r['purpose'])?>
                        </div>
                        <?php else:?>
                        <div class="small text-muted mt-3">No member booked.</div>
                        <?php endif;?>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </main>
</div>
<script>d.addEventListener('change', e => {let x = new Date(e.target.value + 'T00:00:00'); if (x.getDay() != 3) {alert('Select Wednesday.'); return } location.href = '?date=' + e.target.value});</script>
<?php require __DIR__.'/../includes/footer.php';?>