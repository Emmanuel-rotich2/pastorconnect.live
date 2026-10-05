<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();
$staff=staff($pdo);

$filename='fgck_joyland_members_'.date('Y-m-d').'.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$out=fopen('php://output','w');
fputcsv($out,['Member Name','Phone Number','Email Address']);
$q=$pdo->query("SELECT full_name,phone,email FROM members WHERE status='active' ORDER BY full_name ASC");
while($row=$q->fetch(PDO::FETCH_ASSOC)){
    $safe=[];
    foreach([$row['full_name'],$row['phone'],$row['email']] as $value){
        $value=(string)$value;
        $safe[]=preg_match('/^[=+\-@]/',$value)?"'".$value:$value;
    }
    fputcsv($out,$safe);
}
fclose($out);
log_activity($pdo,null,(int)$staff['id'],'member_directory_downloaded','Downloaded the active member directory as CSV (name, phone and email).');
exit;
