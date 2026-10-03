<?php
$s=$staff??staff($pdo);
$currentStaffPage=basename($_SERVER['PHP_SELF']);
function staff_nav_active($pages): string {
    global $currentStaffPage;
    return in_array($currentStaffPage,(array)$pages,true) ? 'active' : '';
}
$pendingMessages=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='draft'")->fetchColumn();
$upcomingEventCount=upcoming_event_count($pdo);
$uq=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $uq->execute([(int)($s['id']??0)]); $staffUnread=(int)$uq->fetchColumn();
?>
<aside class="sidebar staff-sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo"><img src="/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div>
        <div><b>JOYLAND</b></div>
    </div>



    <div class="sidebar-label">PASTOR PORTAL</div>
    <nav>
        <a href="/staff/dashboard" class="<?=staff_nav_active('dashboard.php')?>"><i class="bi bi-grid"></i><span>Dashboard</span></a>
        <a href="/staff/appointments" class="<?=staff_nav_active('appointments.php')?>"><i class="bi bi-calendar-check"></i><span>Appointments</span></a>
        <a href="/staff/notifications" class="<?=staff_nav_active('notifications.php')?>"><i class="bi bi-bell"></i><span>Notifications</span><?php if($staffUnread): ?><span class="nav-count soft"><?=$staffUnread?></span><?php endif; ?></a>
        <a href="/staff/calendar" class="<?=staff_nav_active('calendar.php')?>"><i class="bi bi-calendar3"></i><span>Live Calendar</span></a>
        <a href="/staff/availability" class="<?=staff_nav_active('availability.php')?>"><i class="bi bi-clock"></i><span>Slot Availability</span></a>
    </nav>

    <div class="sidebar-label mt-3">MEMBER ENGAGEMENT</div>
    <nav>
        <a href="/staff/communications" class="<?=staff_nav_active('communications.php')?>">
            <i class="bi bi-megaphone"></i><span>Communications</span>
            <?php if($pendingMessages): ?><span class="nav-count soft"><?=$pendingMessages?></span><?php endif; ?>
        </a>
        <a href="/staff/events" class="<?=staff_nav_active('events.php')?>">
            <i class="bi bi-calendar2-heart"></i><span>Church Events</span>
            <?php if($upcomingEventCount): ?><span class="nav-count soft"><?=$upcomingEventCount?></span><?php endif; ?>
        </a>
        <a href="/staff/members" class="<?=staff_nav_active('members.php')?>"><i class="bi bi-people"></i><span>Members</span></a>
        <a href="/staff/reports" class="<?=staff_nav_active('reports.php')?>"><i class="bi bi-bar-chart"></i><span>Reports & Analytics</span></a>
    </nav>

    <div class="sidebar-label mt-3">SYSTEM</div>
    <nav>
        <a href="/staff/logout" class="staff-system-logout" id="pastorLogoutBtn" aria-label="Logout">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
        <a href="/staff/change_password" class="<?=staff_nav_active('change_password.php')?>">
            <i class="bi bi-shield-lock"></i><span>Change Password</span>
        </a>
        <a href="/staff/settings" class="<?=staff_nav_active('settings.php')?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>
    </nav>

</aside>

    <script>
document.getElementById('pastorLogoutBtn')?.addEventListener('click', function (event) {
    event.preventDefault();
    const logoutUrl = this.href;

    if (typeof Swal === 'undefined') {
        if (window.confirm('Are you sure you want to sign out of the Pastor Portal?')) {
            window.location.href = logoutUrl;
        }
        return;
    }

    Swal.fire({
        title: 'Sign out of Pastor Portal?',
        html: '<div style="color:#66758a;font-size:14px;line-height:1.7">Your current session will be securely closed.<br><strong style="color:#123b5d">FGCK Makutano West Joyland</strong></div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Sign Out',
        cancelButtonText: 'Stay Signed In',
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        customClass: {
            popup: 'pastor-logout-popup',
            confirmButton: 'pastor-logout-confirm',
            cancelButton: 'pastor-logout-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) window.location.href = logoutUrl;
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
