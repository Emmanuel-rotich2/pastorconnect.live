<?php $a=staff($pdo);$currentPage=basename($_SERVER['PHP_SELF']);function admin_nav_active($p):string{global $currentPage;return in_array($currentPage,(array)$p,true)?'active':'';}?>
<aside class="sidebar" id="sidebar">
<div class="brand"><div class="brand-logo"><img src="/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div><div><strong>FGCK Joyland</strong><small>Administrator</small></div></div>
<div class="sidebar-label">ADMINISTRATION</div><nav>
<a href="/admin/dashboard" class="<?=admin_nav_active('dashboard.php')?>"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
<a href="/admin/users" class="<?=admin_nav_active('users.php')?>"><i class="bi bi-person-gear"></i><span>Staff Access</span></a>
<a href="/admin/leader_register" class="<?=admin_nav_active('leader_register.php')?>"><i class="bi bi-person-plus"></i><span>Register Pastor / Leader</span></a>
<a href="/admin/members" class="<?=admin_nav_active('members.php')?>"><i class="bi bi-people"></i><span>Member Administration</span></a>
<a href="/admin/profile" class="<?=admin_nav_active('profile.php')?>"><i class="bi bi-person-circle"></i><span>Administrator Profile</span></a>
</nav>
<div class="sidebar-label mt-3">OVERSIGHT</div><nav>
<a href="/admin/email" class="<?=admin_nav_active('email.php')?>"><i class="bi bi-envelope-check"></i><span>Email Health</span></a>
  <a href="/admin/logins" class="<?=admin_nav_active('logins.php')?>"><i class="bi bi-shield-check"></i><span>Security & Login Audit</span></a>
<a href="/admin/appointments" class="<?=admin_nav_active('appointments.php')?>"><i class="bi bi-calendar-check"></i><span>Appointment Oversight</span></a>
</nav>
<div class="sidebar-label mt-3">SYSTEM</div><nav>
<a href="/auth/logout?role=admin" onclick="return confirm('Are you sure you want to logout from the Administrator Portal?');"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></a>
</nav>
</aside>
