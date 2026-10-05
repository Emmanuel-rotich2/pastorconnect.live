<?php
$l=staff($pdo);
$currentPage=basename($_SERVER['PHP_SELF']);
function leader_nav_active($pages):string{global $currentPage;return in_array($currentPage,(array)$pages,true)?'active':'';}
?>
<aside class="sidebar" id="sidebar">
 <div class="brand"><div class="brand-logo"><img src="/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div><div><strong>FGCK Joyland</strong><small>Church Leader Portal</small></div></div>
 <div class="sidebar-label">PASTORAL & COMMUNICATIONS</div>
 <nav>
  <a href="/leader/dashboard" class="<?=leader_nav_active('dashboard.php')?>"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
  <a href="/leader/appointments" class="<?=leader_nav_active('appointments.php')?>"><i class="bi bi-calendar-heart"></i><span>Appointments with Pastor</span></a>
  <a href="/leader/messages" class="<?=leader_nav_active('messages.php')?>"><i class="bi bi-chat-heart"></i><span>Pastor Messages</span></a>
  <a href="/leader/communications" class="<?=leader_nav_active('communications.php')?>"><i class="bi bi-megaphone"></i><span>Messages to Members</span></a>
  <a href="/leader/events" class="<?=leader_nav_active('events.php')?>"><i class="bi bi-calendar2-heart"></i><span>Church Events</span></a>
 </nav>
 <div class="sidebar-label mt-3">ACCOUNT</div>
 <nav>
  <a href="/leader/notifications" class="<?=leader_nav_active('notifications.php')?>"><i class="bi bi-bell"></i><span>Notifications</span></a>
 </nav>
 <nav>
  <a href="/leader/change_password" class="<?=leader_nav_active('change_password.php')?>"><i class="bi bi-shield-lock"></i><span>Change Password</span></a>
  <a href="/auth/logout?role=church_leader" onclick="return confirm('Are you sure you want to logout from the Church Leader Portal?');"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></a>
 </nav>
</aside>
