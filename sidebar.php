<?php
// Ensure role is set
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = $_GET['tab'] ?? '';

// Determine Home Link
$home_link = 'index.php';
if ($role === 'Admin') $home_link = 'admin_home.php';
if ($role === 'Teacher') {
    if (!empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) {
        $home_link = 'teacher_onboarding.php';
    } else {
        $home_link = 'teacher_home.php';
    }
}
if ($role === 'Secretary') $home_link = 'secretary_home.php';

?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="app-name">📚 <?php echo t('app_name'); ?></div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo $home_link; ?>" class="sidebar-link <?php echo ($current_page == basename($home_link)) ? 'active' : ''; ?>">
            <span class="icon">🏠</span>
            <span class="text"><?php echo t('nav_home'); ?></span>
        </a>

        <?php if ($role === 'Admin'): ?>
            <a href="admin_dashboard.php" class="sidebar-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <span class="icon">🔍</span>
                <span class="text"><?php echo t('nav_search'); ?></span>
            </a>
            <a href="admin_search_student.php" class="sidebar-link <?php echo ($current_page == 'admin_search_student.php') ? 'active' : ''; ?>">
                <span class="icon">📂</span>
                <span class="text"><?php echo t('nav_student_records'); ?></span>
            </a>
        <?php endif; ?>

        <?php if ($role === 'Teacher'): ?>
            <a href="fill_form.php?tab=absences" class="sidebar-link <?php echo ($current_page == 'fill_form.php' && ($current_tab == 'absences' || $current_tab == '')) ? 'active' : ''; ?>">
                <span class="icon">📅</span>
                <span class="text"><?php echo t('absences'); ?></span>
            </a>
            <a href="fill_form.php?tab=observations" class="sidebar-link <?php echo ($current_page == 'fill_form.php' && $current_tab == 'observations') ? 'active' : ''; ?>">
                <span class="icon">📝</span>
                <span class="text"><?php echo t('observations'); ?></span>
            </a>
        <?php endif; ?>
        
        <?php if ($role === 'Secretary'): ?>
             <a href="insert_student.php" class="sidebar-link <?php echo ($current_page == 'insert_student.php') ? 'active' : ''; ?>">
                <span class="icon">➕</span>
                <span class="text"><?php echo t('insert_student'); ?></span>
            </a>
        <?php endif; ?>

        <a href="profile.php" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <span class="icon">👤</span>
            <span class="text"><?php echo t('nav_profile'); ?></span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="lang-switcher-wrapper">
            <?php include __DIR__ . '/lang/switcher.php'; ?>
        </div>
        

        <a href="options.php" class="sidebar-link">
            <span class="icon">⚙️</span>
            <span class="text"><?php echo t('nav_options'); ?></span>
        </a>

        <a href="logout.php" class="sidebar-link logout-btn">
            <span class="icon">🚪</span>
            <span class="text"><?php echo t('nav_logout'); ?></span>
        </a>
    </div>

    <!-- Floating Notifications for Admin -->
    <?php if ($role === 'Admin'): ?>
        <div class="notification-container">
            <div class="notification-bell" id="notificationBell" onclick="toggleNotificationsPanel()">
                🔔
                <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
            </div>
            <div class="notifications-panel" id="notificationsPanel">
                <div style="padding:12px; border-bottom:1px solid #e5e7eb; font-weight:600; background:#f9fafb;">
                    <?php echo t('new_observations'); ?>
                </div>
                <div id="notificationsContent"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($role === 'Admin'): ?>
<script>
(function() {
    var T = <?php echo json_encode($T ?? []); ?>;
    let newNotifications = [];

    window.toggleNotificationsPanel = function() {
        const panel = document.getElementById('notificationsPanel');
        if (panel) {
            panel.classList.toggle('active');
        }
    };

    function fetchNotifications() {
        fetch('get_new_notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    newNotifications = data.notifications;
                    updateNotificationDisplay();
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    function updateNotificationDisplay() {
        const countBadge = document.getElementById('notificationCount');
        const content = document.getElementById('notificationsContent');
        
        if (!countBadge || !content) return;

        if (newNotifications.length > 0) {
            countBadge.textContent = newNotifications.length;
            countBadge.style.display = 'flex';
            
            let html = '';
            newNotifications.forEach((notif, idx) => {
                html += `<a class="notification-item new" href="admin_dashboard.php?session=${encodeURIComponent(notif.session_id)}" 
                             onclick="try{fetch('mark_observation_read.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({observation_id:${notif.observation_id}})});}catch(e){}">
                    <div class="notification-item-header">
                        <span class="notification-item-student">${notif.student_name}</span>
                        <span class="notification-item-time">${notif.observation_time}</span>
                    </div>
                    <div class="notification-item-details">
                        <div><strong>${T.teacher_label || 'Teacher'}:</strong> ${notif.teacher_name}</div>
                        <div><strong>${T.session_label || 'Session'}:</strong> ${notif.session_date} (${notif.session_time})</div>
                        <div><strong>${T.motif_label || 'Motif'}:</strong> ${notif.motif}</div>
                    </div>
                </a>`;
            });
            content.innerHTML = html;
        } else {
            countBadge.style.display = 'none';
            content.innerHTML = '<div class="notification-empty">' + (T.no_new_observations || 'No new observations') + '</div>';
        }
    }

    // Close notifications panel when clicking outside
    document.addEventListener('click', function(event) {
        const notifBell = document.getElementById('notificationBell');
        const panel = document.getElementById('notificationsPanel');
        
        if (notifBell && panel && !notifBell.contains(event.target)) {
            panel.classList.remove('active');
        }
    });

    // Fetch notifications on page load
    fetchNotifications();

    // Refresh notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
})();
</script>
<script src="effects.js"></script>
<?php endif; ?>
<?php if ($role !== 'Admin'): ?>
<script src="effects.js"></script>
<?php endif; ?>
