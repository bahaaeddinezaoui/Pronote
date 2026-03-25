<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) exit;

// Helper to convert plain text newline-separated lists into HTML
if (!function_exists('renderChangelogLines')) {
    function renderChangelogLines($text) {
        if (empty(trim($text))) return '';
        $lines = explode("\n", $text);
        $html = '<ul class="changelog-list">';
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $html .= '<li>' . htmlspecialchars($line) . '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }
}

// Check for show_changelog trigger in URL
$auto_show = isset($_GET['show_changelog']);
$target_version = $_GET['show_changelog'] ?? null;

// DB details
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) exit;

if ($auto_show) {
    if ($target_version === '*' || empty($target_version)) {
        // Fetch the latest changelog
        $res = $conn->query("SELECT * FROM changelog ORDER BY RELEASE_DATE DESC, CHANGELOG_ID DESC LIMIT 1");
        $latest_changelog = $res->fetch_assoc();
    } else {
        // Fetch the specific version
        $q = $conn->prepare("SELECT * FROM changelog WHERE VERSION = ? LIMIT 1");
        $q->bind_param("s", $target_version);
        $q->execute();
        $latest_changelog = $q->get_result()->fetch_assoc();
    }
    $show_modal = ($latest_changelog !== null);
} else {
    // Get user's last seen changelog
    $stmt = $conn->prepare("SELECT LAST_SEEN_CHANGELOG_ID FROM user_account WHERE USER_ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_data = $res->fetch_assoc();
    $last_seen_id = $user_data['LAST_SEEN_CHANGELOG_ID'] ?? 0;
    $stmt->close();

    // Get the latest changelog
    $res = $conn->query("SELECT * FROM changelog ORDER BY CHANGELOG_ID DESC LIMIT 1");
    $latest_changelog = $res->fetch_assoc();
    $show_modal = ($latest_changelog && $latest_changelog['CHANGELOG_ID'] > $last_seen_id);
}
$conn->close();

if ($show_modal && basename($_SERVER['PHP_SELF']) !== 'changelog.php') {
    $lang = $_SESSION['lang'] ?? 'en';
    $title = ($lang === 'ar') ? $latest_changelog['TITLE_AR'] : $latest_changelog['TITLE_EN'];
    $content = ($lang === 'ar') ? $latest_changelog['CONTENT_AR'] : $latest_changelog['CONTENT_EN'];
    $version = $latest_changelog['VERSION'];
    $release_date = $latest_changelog['RELEASE_DATE'];
    $changelog_id = $latest_changelog['CHANGELOG_ID'];
    ?>
    <!-- Move modal to the very end of body to avoid layout pollution -->
    <div id="changelogModal" class="modal-overlay" style="display: flex;">
        <div class="modal-content changelog-modal">
            <div class="changelog-header">
                <div class="header-top">
                    <div class="version-badge">v<?php echo htmlspecialchars($version); ?></div>
                    <span class="release-date"><?php echo htmlspecialchars($release_date); ?></span>
                </div>
                <h2><?php echo htmlspecialchars($title); ?></h2>
            </div>
            <div class="changelog-body">
                <?php echo renderChangelogLines($content); ?>
            </div>
            <div class="changelog-footer">
                <button type="button" class="btn btn-secondary" onclick="viewFullHistory(<?php echo $changelog_id; ?>)">
                    <?php echo ($lang === 'ar') ? 'سجل التحديثات' : 'View History'; ?>
                </button>
                <button id="dismissChangelog" class="btn btn-primary" onclick="markChangelogRead(<?php echo $changelog_id; ?>)">
                    <?php echo ($lang === 'ar') ? 'فهمت' : 'Got it'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Ensure styles are isolated -->
    <style>
    #changelogModal.modal-overlay {
        position: fixed;
        top: 0; left: 0; 
        width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center; justify-content: center;
        z-index: 2147483647; /* Maximum possible z-index */
        animation: changelog_fadeIn 0.3s ease;
    }
    .changelog-modal {
        background: var(--bg-card, #ffffff);
        width: 90%; max-width: 600px;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.2);
        animation: changelog_slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: left;
        position: relative;
    }
    [dir="rtl"] .changelog-modal { text-align: right; }
    
    .changelog-header {
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border-color, #eee);
        padding-bottom: 15px;
    }
    .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .version-badge {
        background: var(--primary-color, #4f46e5);
        color: white !important;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .release-date { font-size: 0.85rem; color: var(--text-muted, #999); }
    .changelog-header h2 { margin: 0; font-size: 1.5rem; color: var(--text-primary, #1e293b); }
    .changelog-body {
        max-height: 350px;
        overflow-y: auto;
        margin-bottom: 30px;
        color: var(--text-secondary, #475569);
        line-height: 1.6;
    }
    .changelog-body ul { 
        padding-inline-start: 20px; 
        list-style-type: none;
    }
    .changelog-body li { 
        margin-bottom: 12px; 
        position: relative;
        padding-inline-start: 15px;
    }
    .changelog-body li::before {
        content: "•";
        position: absolute;
        left: 0;
        color: var(--primary-color, #4f46e5);
        font-weight: bold;
    }
    [dir="rtl"] .changelog-body li::before { left: auto; right: 0; }
    
    .changelog-footer { display: flex; justify-content: flex-end; gap: 12px; }
    .changelog-footer .btn { width: auto; font-size: 0.85rem; padding: 10px 18px; }

    @keyframes changelog_fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes changelog_slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Dark mode support */
    [data-theme="dark"] .changelog-modal {
        background: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }
    [data-theme="dark"] .changelog-header h2 { color: #f8fafc !important; }
    [data-theme="dark"] .changelog-body { color: #cbd5e1 !important; }
    </style>

    <script>
    function viewFullHistory(id) {
        // Mark as read and then navigate
        fetch('mark_changelog_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'changelog_id=' + id
        }).finally(() => {
            window.location.href = 'changelog.php';
        });
    }

    function markChangelogRead(id) {
        fetch('mark_changelog_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'changelog_id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('changelogModal');
                if (modal) modal.style.opacity = '0';
                setTimeout(() => { if (modal) modal.style.display = 'none'; }, 300);
            }
        })
        .catch(err => {
            console.error('Error marking changelog as read:', err);
            const modal = document.getElementById('changelogModal');
            if (modal) modal.style.display = 'none';
        });
    }

    // Attempt to move the modal to the body root to avoid nesting issues
    window.addEventListener('load', function() {
        const modal = document.getElementById('changelogModal');
        if (modal) {
            document.body.appendChild(modal);
        }
    });
    </script>
    <?php
}
?>
