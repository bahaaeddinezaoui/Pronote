<?php
// Ensure role is set
$role = trim((string)($_SESSION['role'] ?? ''));
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = $_GET['tab'] ?? '';

// Determine Home Link
$home_link = 'index.php';
if ($role === 'Admin') $home_link = 'admin_home.php';
if ($role === 'Superuser') $home_link = 'superuser_dashboard.php';
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
        <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <span class="toggle-icon">&gt;</span>
        </button>
        <div class="app-name">📚 <?php echo t('app_name'); ?></div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo $home_link; ?>" id="navHome" class="sidebar-link <?php echo ($current_page == basename($home_link)) ? 'active' : ''; ?>">
            <span class="icon">🏠</span>
            <span class="text" data-tooltip="<?php echo t('nav_home'); ?>"><?php echo t('nav_home'); ?></span>
        </a>

        <?php if ($role === 'Admin'): ?>
            <a href="admin_dashboard.php" id="navSearchSessions" class="sidebar-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <span class="icon">🔍</span>
                <span class="text" data-tooltip="<?php echo t('nav_search'); ?>"><?php echo t('nav_search'); ?></span>
            </a>
            <a href="admin_search_student.php" id="navStudentRecords" class="sidebar-link <?php echo ($current_page == 'admin_search_student.php') ? 'active' : ''; ?>">
                <span class="icon">📂</span>
                <span class="text" data-tooltip="<?php echo t('nav_student_records'); ?>"><?php echo t('nav_student_records'); ?></span>
            </a>
            <a href="admin_weekly_program.php" id="navWeeklyProgram" class="sidebar-link <?php echo ($current_page == 'admin_weekly_program.php') ? 'active' : ''; ?>">
                <span class="icon">📄</span>
                <span class="text" data-tooltip="<?php echo t('nav_weekly_program'); ?>"><?php echo t('nav_weekly_program'); ?></span>
            </a>
        <?php endif; ?>

        <?php if ($role === 'Superuser'): ?>
            <a href="superuser_upload_weekly_program.php" id="navSuperuserWeeklyProgramUpload" class="sidebar-link <?php echo ($current_page == 'superuser_upload_weekly_program.php') ? 'active' : ''; ?>">
                <span class="icon">📤</span>
                <span class="text" data-tooltip="<?php echo t('nav_upload_weekly_program'); ?>"><?php echo t('nav_upload_weekly_program'); ?></span>
            </a>
            <a href="superuser_change_password.php" id="navSuperuserChangePassword" class="sidebar-link <?php echo ($current_page == 'superuser_change_password.php') ? 'active' : ''; ?>">
                <span class="icon">🔑</span>
                <span class="text" data-tooltip="<?php echo t('change_password'); ?>"><?php echo t('change_password'); ?></span>
            </a>
        <?php endif; ?>

        <?php if ($role === 'Teacher'): ?>
            <a href="fill_form.php?tab=absences" id="navAbsences" class="sidebar-link <?php echo ($current_page == 'fill_form.php' && ($current_tab == 'absences' || $current_tab == '')) ? 'active' : ''; ?>">
                <span class="icon">📅</span>
                <span class="text" data-tooltip="<?php echo t('absences'); ?>"><?php echo t('absences'); ?></span>
            </a>
            <a href="fill_form.php?tab=observations" id="navObservations" class="sidebar-link <?php echo ($current_page == 'fill_form.php' && $current_tab == 'observations') ? 'active' : ''; ?>">
                <span class="icon">📝</span>
                <span class="text" data-tooltip="<?php echo t('observations'); ?>"><?php echo t('observations'); ?></span>
            </a>
        <?php endif; ?>
        
        <?php if ($role === 'Secretary'): ?>
            <div class="nav-section-title"><?php echo t('nav_student_mgmt') ?: 'Student Management'; ?></div>
             <a href="insert_student.php" id="navInsertStudent" class="sidebar-link <?php echo ($current_page == 'insert_student.php') ? 'active' : ''; ?>">
                <span class="icon">➕</span>
                <span class="text" data-tooltip="<?php echo t('insert_student'); ?>"><?php echo t('insert_student'); ?></span>
            </a>

            <a href="secretary_edit_student.php" id="navEditStudent" class="sidebar-link <?php echo ($current_page == 'secretary_edit_student.php') ? 'active' : ''; ?>">
                <span class="icon">✏️</span>
                <span class="text" data-tooltip="<?php echo t('edit_student'); ?>"><?php echo t('edit_student'); ?></span>
            </a>

            <div class="nav-section-title"><?php echo t('nav_disciplinary') ?: 'Disciplinary'; ?></div>
            <a href="secretary_punishes_student.php" id="navPunishStudent" class="sidebar-link <?php echo ($current_page == 'secretary_punishes_student.php') ? 'active' : ''; ?>">
                <span class="icon">⚠️</span>
                <span class="text" data-tooltip="<?php echo t('nav_punish_student'); ?>"><?php echo t('nav_punish_student'); ?></span>
            </a>

            <a href="secretary_rewards_student.php" id="navRewardStudent" class="sidebar-link <?php echo ($current_page == 'secretary_rewards_student.php') ? 'active' : ''; ?>">
                <span class="icon">🏆</span>
                <span class="text" data-tooltip="<?php echo t('nav_reward_student'); ?>"><?php echo t('nav_reward_student'); ?></span>
            </a>
            
            <div class="nav-section-title"><?php echo t('nav_personal') ?: 'Personal'; ?></div>
        <?php endif; ?>

        <a href="profile.php" id="navProfile" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <span class="icon">👤</span>
            <span class="text" data-tooltip="<?php echo t('nav_profile'); ?>"><?php echo t('nav_profile'); ?></span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="lang-switcher-wrapper" id="navLanguage">
            <?php include __DIR__ . '/lang/switcher.php'; ?>
        </div>
        

        <a href="options.php" id="navOptions" class="sidebar-link">
            <span class="icon">⚙️</span>
            <span class="text" data-tooltip="<?php echo t('nav_options'); ?>"><?php echo t('nav_options'); ?></span>
        </a>

        <a href="#" id="restartTutorial" class="sidebar-link">
            <span class="icon">❔</span>
            <span class="text" data-tooltip="<?php echo t('nav_tutorial'); ?>"><?php echo t('nav_tutorial'); ?></span>
        </a>

        <a href="#" id="themeToggleBtn" class="sidebar-link">
            <span class="icon" id="themeToggleIcon">🌙</span>
            <span class="text" id="themeToggleText" data-tooltip="<?php echo t('theme_toggle'); ?>"><?php echo t('theme_toggle'); ?></span>
        </a>

        <a href="changelog.php" id="viewChangelogHistory" class="sidebar-link <?php echo ($current_page == 'changelog.php') ? 'active' : ''; ?>">
            <span class="icon">📜</span>
            <span class="text" data-tooltip="<?php echo ($lang === 'ar') ? 'سجل التغييرات' : 'Changelog'; ?>"><?php echo ($lang === 'ar') ? 'سجل التغييرات' : 'Changelog'; ?></span>
        </a>

        <a href="logout.php" id="navLogout" class="sidebar-link logout-btn <?php if ($role === 'Teacher' && !empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) echo 'disabled'; ?>" 
           <?php if ($role === 'Teacher' && !empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) echo 'onclick="return false;" title="' . t('logout_disabled_during_onboarding') . '"'; ?>>
            <span class="icon">🚪</span>
            <span class="text" data-tooltip="<?php echo t('nav_logout'); ?>"><?php echo t('nav_logout'); ?></span>
        </a>
    </div>

</div>

<!-- Floating Notifications for Admin (kept outside sidebar so position:fixed anchors to viewport) -->
<?php if ($role === 'Admin'): ?>
    <div class="notification-container">
        <div class="notification-bell" id="notificationBell" onclick="toggleNotificationsPanel()">
            🔔
            <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
        </div>
        <div class="notifications-panel" id="notificationsPanel">
            <div class="notifications-header">
                <span><?php echo t('new_observations'); ?></span>
                <button class="clear-all-btn" id="clearAllNotifications" onclick="clearAllNotifications(event)"><?php echo t('clear_all_notifications'); ?></button>
            </div>
            <div id="notificationsContent"></div>
        </div>
    </div>
<?php endif; ?>

<script>
(function(){
    window.EduTrackTutorialConfig = {
        userId: <?php echo json_encode((string)($_SESSION['user_id'] ?? '')); ?>,
        role: <?php echo json_encode((string)($role ?? '')); ?>,
        lang: <?php echo json_encode((string)($LANG ?? 'en')); ?>,
        t: <?php echo json_encode($T ?? []); ?>
    };

    function bindRestartTutorial() {
        var btn = document.getElementById('restartTutorial');
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.EduTrackTutorial && typeof window.EduTrackTutorial.restart === 'function') {
                window.EduTrackTutorial.restart();
            } else if (typeof window.startTutorial === 'function') {
                window.startTutorial(true);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindRestartTutorial);
    } else {
        bindRestartTutorial();
    }
})();
</script>

<?php if ($role === 'Admin'): ?>
<script>
(function() {
    var T = <?php echo json_encode($T ?? []); ?>;
    let newNotifications = [];
    const NOTIF_SEEN_KEY = 'edutrack_admin_seen_notif_ids';
    const NOTIF_BOOTSTRAP_KEY = 'edutrack_admin_notif_bootstrap_done';
    let audioUnlocked = false;

    function safeParseJsonArray(raw) {
        try {
            const v = JSON.parse(raw);
            return Array.isArray(v) ? v : [];
        } catch (e) {
            return [];
        }
    }

    function getSeenIds() {
        return new Set(safeParseJsonArray(sessionStorage.getItem(NOTIF_SEEN_KEY) || '[]').map(String));
    }

    function setSeenIds(idsSet) {
        try {
            sessionStorage.setItem(NOTIF_SEEN_KEY, JSON.stringify(Array.from(idsSet)));
        } catch (e) {}
    }

    function isBootstrapped() {
        return sessionStorage.getItem(NOTIF_BOOTSTRAP_KEY) === 'true';
    }

    function setBootstrapped() {
        try {
            sessionStorage.setItem(NOTIF_BOOTSTRAP_KEY, 'true');
        } catch (e) {}
    }

    function unlockAudio() {
        if (audioUnlocked) return;
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            gain.gain.value = 0.0001;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.02);
            audioUnlocked = true;
        } catch (e) {
            // ignore
        }
    }

    function playNotificationChime() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const now = ctx.currentTime;

            function tone(freq, start, dur, peak) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, start);
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(peak, start + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(start);
                osc.stop(start + dur + 0.02);
            }

            // Soft 2-tone chime
            tone(880, now + 0.00, 0.14, 0.06);
            tone(1175, now + 0.10, 0.18, 0.05);
        } catch (e) {
            // ignore autoplay/security errors
        }
    }

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
                    try {
                        const seen = getSeenIds();
                        const incomingIds = new Set((newNotifications || []).map(n => String(n.observation_id)));

                        // First successful load: bootstrap seen IDs, don't chime.
                        if (!isBootstrapped()) {
                            incomingIds.forEach(id => seen.add(id));
                            setSeenIds(seen);
                            setBootstrapped();
                        } else {
                            let hasNew = false;
                            incomingIds.forEach(id => {
                                if (!seen.has(id)) hasNew = true;
                                seen.add(id);
                            });
                            setSeenIds(seen);
                            if (hasNew) {
                                if (audioUnlocked) {
                                    playNotificationChime();
                                } else {
                                    // Try anyway; browser may block until first interaction.
                                    playNotificationChime();
                                }
                            }
                        }
                    } catch (e) {}
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
                html += `<div class="notification-item new" data-id="${notif.observation_id}">
                    <a href="admin_dashboard.php?session=${encodeURIComponent(notif.session_id)}" 
                             onclick="markAsRead(${notif.observation_id})">
                        <div class="notification-item-header">
                            <span class="notification-item-student">${notif.student_name}</span>
                            <span class="notification-item-time">${notif.observation_time}</span>
                        </div>
                        <div class="notification-item-details">
                            <div class="notification-item-meta">
                                <span class="notification-item-meta-teacher">👤 ${notif.teacher_name}</span>
                                <span class="notification-item-meta-sep">•</span>
                                <span class="notification-item-meta-session">🗓️ ${notif.session_date} ${notif.session_time ? `(${notif.session_time})` : ''}</span>
                            </div>
                            <div class="notification-item-motif">${notif.motif}</div>
                        </div>
                    </a>
                    <button class="clear-notif-btn" onclick="clearNotification(event, ${notif.observation_id})" title="${T.clear_notification || 'Clear'}">
                        ✕
                    </button>
                </div>`;
            });
            content.innerHTML = html;
        } else {
            countBadge.style.display = 'none';
            content.innerHTML = '<div class="notification-empty">' + (T.no_new_observations || 'No new observations') + '</div>';
            const clearAllBtn = document.getElementById('clearAllNotifications');
            if (clearAllBtn) clearAllBtn.style.display = 'none';
        }
    }

    window.markAsRead = function(id) {
        try {
            fetch('mark_observation_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ observation_id: id })
            });
        } catch (e) {}
    };

    window.clearNotification = function(event, id) {
        event.preventDefault();
        event.stopPropagation();
        
        const item = event.target.closest('.notification-item');
        if (item) {
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            setTimeout(() => {
                newNotifications = newNotifications.filter(n => n.observation_id != id);
                updateNotificationDisplay();
            }, 300);
        }

        fetch('mark_observation_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ observation_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to clear notification:', data.message);
                fetchNotifications(); // Refresh on error
            }
        });
    };

    window.clearAllNotifications = function(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        const content = document.getElementById('notificationsContent');
        if (content) {
            content.style.opacity = '0.5';
            content.style.pointerEvents = 'none';
        }

        fetch('mark_all_observations_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                newNotifications = [];
                updateNotificationDisplay();
                
                // Show a small toast or just let it empty out
                if (window.EduTrackEffects && typeof window.EduTrackEffects.showToast === 'function') {
                    window.EduTrackEffects.showToast(T.notifications_cleared || 'Notifications cleared', 'success');
                }
            } else {
                console.error('Failed to clear all notifications:', data.message);
            }
            if (content) {
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';
            }
        })
        .catch(err => {
            console.error('Error clearing all notifications:', err);
            if (content) {
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';
            }
        });
    };

    // Close notifications panel when clicking outside
    document.addEventListener('click', function(event) {
        unlockAudio();
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
<script src="effects.js?v=2"></script>
<?php endif; ?>
<?php if ($role !== 'Admin'): ?>
<script src="effects.js?v=2"></script>
<?php endif; ?>

<script>
// Collapsible Sidebar Functionality
(function() {
    'use strict';
    
    // Storage key for sidebar state
    const SIDEBAR_STATE_KEY = 'edutrack_sidebar_collapsed';
    
    // Get DOM elements
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar || !toggleBtn) return;
    
    // Load saved state
    function loadSidebarState() {
        try {
            const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        } catch (e) {
            console.warn('Could not load sidebar state:', e);
        }
    }
    
    // Save state
    function saveSidebarState(isCollapsed) {
        try {
            localStorage.setItem(SIDEBAR_STATE_KEY, isCollapsed.toString());
        } catch (e) {
            console.warn('Could not save sidebar state:', e);
        }
    }
    
    // Toggle sidebar
    function toggleSidebar() {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        saveSidebarState(isCollapsed);
        
        // Emit custom event for other components
        window.dispatchEvent(new CustomEvent('sidebarToggle', { 
            detail: { collapsed: isCollapsed } 
        }));
    }
    
    // Handle keyboard shortcut (Ctrl/Cmd + B)
    function handleKeyboardShortcut(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
    }
    
    // Initialize
    function init() {
        loadSidebarState();
        
        // Bind toggle button click
        toggleBtn.addEventListener('click', toggleSidebar);
        
        // Bind keyboard shortcut
        document.addEventListener('keydown', handleKeyboardShortcut);
        
        // Handle window resize for responsive behavior
        function handleResize() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize(); // Check initial size
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php include __DIR__ . '/changelog_modal.php'; ?>
