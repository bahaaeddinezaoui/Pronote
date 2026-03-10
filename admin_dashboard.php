<?php
// admin_dashboard.php - Admin view to see sessions by date and time slot
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin info
$admin_name = "Admin";
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT ADMINISTRATOR_FIRST_NAME_EN, ADMINISTRATOR_LAST_NAME_EN FROM administrator WHERE USER_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME_EN']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME_EN']);
    }
    $stmt->close();
}

// Default to today's date
$current_date = date('Y-m-d');
$current_date_display = date('d/m/Y');

// Time slots
$time_slots = [
    ["value" => "08:00 - 10:00", "start" => "08:00", "end" => "10:00"],
    ["value" => "10:00 - 12:00", "start" => "10:00", "end" => "12:00"],
    ["value" => "13:00 - 14:30", "start" => "13:00", "end" => "14:30"],
    ["value" => "14:30 - 16:00", "start" => "14:30", "end" => "16:00"]
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('search'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        [data-theme='dark'] :root {
            --glass-bg: rgba(30, 41, 59, 0.85);
            --glass-border: rgba(255, 255, 255, 0.1);
            --surface-color: #1e293b;
            --background-color: #0f172a;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .dashboard-header {
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin: 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Glass Cards --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        [data-theme='dark'] .glass-card {
            background: rgba(30, 41, 59, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme='dark'] .stat-card {
            background: #1e293b;
        }

        [data-theme='dark'] .modern-table td {
            border-top-color: #334155;
        }

        [data-theme='dark'] .modal-close {
            background: #334155;
        }


        [data-theme='dark'] .filter-group input, 
        [data-theme='dark'] .filter-group select {
            background: #1e293b;
            color: #f8fafc;
        }

        [data-theme='dark'] .session-card-mini {
            background: #1e293b;
        }

        [data-theme='dark'] .modern-table th {
            background: rgba(255, 255, 255, 0.03);
        }

        [data-theme='dark'] .modal-header {
            background: #1e293b;
        }

        [data-theme='dark'] .modal-body {
            background: #0f172a;
        }


        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Fix search button text/icon color in light mode if needed, but ensure it's visible */
        .btn-modern span {
            color: inherit;
        }

        [data-theme='dark'] .empty-state {
            color: var(--text-secondary);
        }

        [data-theme='dark'] .section-header {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }


        .section-icon {
            font-size: 1.5rem;
        }

        /* --- Modern Filters --- */
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .filter-group input, .filter-group select {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease;
            outline: none;
        }

        .filter-group input:focus, .filter-group select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            gap: 0.6rem;
            height: 48px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
        }

        /* --- Sessions Grid --- */
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .session-card-mini {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .session-card-mini:hover {
            border-color: #6366f1;
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .session-card-mini::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .session-header-mini {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .session-id-badge {
            font-size: 0.75rem;
            font-weight: 800;
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            padding: 0.25rem 0.6rem;
            border-radius: 0.5rem;
        }

        .session-time {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .session-info-mini h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .session-meta-mini {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            gap: 1rem;
        }

        /* --- Absence Summary --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #4f46e5;
        }

        .modern-table-wrapper {
            overflow-x: auto;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .modern-table th {
            background: rgba(0, 0, 0, 0.02);
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .modern-table td {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .modern-table tr:hover {
            background: rgba(99, 102, 241, 0.02);
        }

        .badge-motif {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 0.75rem;
        }

        /* --- Modal --- */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-backdrop.active {
            display: flex;
            opacity: 1;
        }

        .modal-card {
            background: var(--surface-color);
            border-radius: 1.5rem;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-backdrop.active .modal-card {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--background-color);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #ef4444;
            color: white;
        }

        /* --- Empty States --- */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        @media (max-width: 768px) {
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .modal-card { border-radius: 0; max-height: 100vh; }
        }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="admin-container">
    <div class="dashboard-header">
        <h1><?php echo t('admin_dashboard'); ?></h1>
    </div>

    <div class="glass-card">
        <div class="section-header">
            <span class="section-icon">🔍</span>
            <h2><?php echo t('search_study_sessions'); ?></h2>
        </div>
        
        <div class="filters-grid">
            <div class="filter-group">
                <label for="session_date"><?php echo t('select_date'); ?></label>
                <input type="date" id="session_date" value="<?php echo $current_date; ?>">
            </div>
            
            <div class="filter-group">
                <label for="time_slot"><?php echo t('select_time_slot'); ?></label>
                <select id="time_slot">
                    <option value=""><?php echo t('all_time_slots'); ?></option>
                    <?php foreach ($time_slots as $slot): ?>
                        <option value="<?php echo htmlspecialchars($slot['start'] . '|' . $slot['end']); ?>">
                            <?php echo htmlspecialchars($slot['value']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button class="btn-modern btn-primary" id="adminSearchSessionsBtn" onclick="searchSessions()">
                <span>🔍</span> <?php echo t('search_sessions'); ?>
            </button>
        </div>
    </div>
    
    <div class="glass-card">
        <div class="section-header">
            <span class="section-icon">📅</span>
            <h2><?php echo t('study_sessions'); ?></h2>
        </div>
        <div id="sessions_container">
            <div class="empty-state">
                <span class="empty-state-icon">📂</span>
                <p><?php echo t('please_select_date_search'); ?></p>
            </div>
        </div>
    </div>

    <div class="glass-card" id="absenceSummarySection" style="display:none;">
        <div class="section-header">
            <span class="section-icon">📊</span>
            <h2><?php echo t('absence_summary'); ?></h2>
        </div>
        <div id="absenceSummaryContainer"></div>
    </div>
</div>

<div id="session_modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="session_modal_title">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="session_modal_title" style="margin:0; font-size:1.1rem; font-weight:700; color:var(--text-primary);"><?php echo t('session_details'); ?></h3>
            <button class="modal-close" aria-label="<?php echo t('close'); ?>" onclick="closeSessionModal()">&times;</button>
        </div>
        <div class="modal-body" id="modal_body"></div>
    </div>
</div>
</div>

</div>
</div>
</div>

<script>
var T = <?php echo json_encode($T); ?>;
let latestSessions = [];

function markObservationRead(observationId) {
    fetch('mark_observation_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ observation_id: observationId })
    }).catch(err => console.error('Error marking observation as read:', err));
}

function closeSessionModal() {
    const modal = document.getElementById('session_modal');
    if (modal) modal.classList.remove('active');
}

function openSessionModal(index) {
    const session = latestSessions[index];
    if (!session) return;
    const modal = document.getElementById('session_modal');
    const body = document.getElementById('modal_body');
    if (!modal || !body) return;

    let html = '';
    html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem;">';
    html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">';
    html += '<h3 style="margin:0; color:var(--primary-color); font-weight:800;">Session #' + session.session_id + '</h3>';
    html += '<span class="session-id-badge">' + session.session_date + '</span>';
    html += '</div>';

    html += '<div class="info-minimal-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">';
    html += '<div class="info-cell"><span class="cell-label">' + (T.teacher_label||'Teacher') + '</span><span class="cell-value">' + session.teacher_name + '</span></div>';
    html += '<div class="info-cell"><span class="cell-label">' + (T.class_label||'Class') + '</span><span class="cell-value">' + (session.class_name || 'N/A') + '</span></div>';
    html += '<div class="info-cell"><span class="cell-label">' + (T.time||'Time') + '</span><span class="cell-value">' + session.start_time + ' - ' + session.end_time + '</span></div>';
    html += '</div>';
    html += '</div>';

    if (session.sections && session.sections.length > 0) {
        html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem;">';
        html += '<h4 style="margin-top:0; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-secondary);">' + (T.sections||'Sections') + '</h4>';
        html += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';
        session.sections.forEach(sec => {
            html += '<span class="badge" style="background:rgba(99, 102, 241, 0.1); color:#4f46e5; border:1px solid rgba(99, 102, 241, 0.2); padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.85rem; font-weight: 600;">' + sec + '</span>';
        });
        html += '</div>';
        html += '</div>';
    }

    if (session.absences && session.absences.length > 0) {
        html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem;">';
        html += '<h4 style="margin-top:0; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-secondary);">' + (T.absences_count || 'Absences').replace('%s', session.absences.length) + '</h4>';
        html += '<div class="modern-table-wrapper">';
        html += '<table class="modern-table">';
        html += '<thead><tr><th>' + (T.student||'Student') + '</th><th>' + (T.time||'Time') + '</th><th>' + (T.motif_label||'Motif') + '</th><th>' + (T.observation||'Observation') + '</th></tr></thead>';
        html += '<tbody>';
        session.absences.forEach(abs => {
            html += '<tr>';
            html += '<td><strong>' + abs.student_name + '</strong></td>';
            html += '<td>' + abs.absence_time + '</td>';
            html += '<td><span class="badge-motif">' + abs.motif + '</span></td>';
            html += '<td>' + (abs.observation || '-') + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        html += '</div>';
    } else {
        html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem; text-align:center;"><p class="no-data">' + (T.no_absences_recorded||'No absences recorded') + '</p></div>';
    }

    if (session.observations && session.observations.length > 0) {
        html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem;">';
        html += '<h4 style="margin-top:0; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-secondary);">' + (T.observations_count || 'Observations').replace('%s', session.observations.length) + '</h4>';
        html += '<div class="modern-table-wrapper">';
        html += '<table class="modern-table">';
        html += '<thead><tr><th>' + (T.student||'Student') + '</th><th>' + (T.teacher||'Teacher') + '</th><th>' + (T.time||'Time') + '</th><th>' + (T.motif_label||'Motif') + '</th><th>' + (T.note||'Note') + '</th></tr></thead>';
        html += '<tbody>';
        session.observations.forEach(obs => {
            const rowStyle = obs.is_new_for_admin ? 'style="background:rgba(79, 70, 229, 0.05); border-left: 4px solid #4f46e5;"' : '';
            html += '<tr ' + rowStyle + '>';
            html += '<td><strong>' + obs.student_name + '</strong></td>';
            html += '<td>' + obs.teacher_name + '</td>';
            html += '<td>' + obs.observation_time + '</td>';
            html += '<td><span class="badge" style="background:rgba(59, 130, 246, 0.1); color:#3b82f6; border:none; padding: 0.2rem 0.6rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700;">' + obs.motif + '</span></td>';
            html += '<td>' + obs.note + '</td>';
            html += '</tr>';
            if (obs.is_new_for_admin) markObservationRead(obs.observation_id);
        });
        html += '</tbody></table></div>';
        html += '</div>';
    } else {
        html += '<div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.5rem; text-align:center;"><p class="no-data">' + (T.no_observations_recorded||'No observations recorded') + '</p></div>';
    }

    body.innerHTML = html;
    modal.classList.add('active');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSessionModal();
});

document.addEventListener('click', (e) => {
    const modal = document.getElementById('session_modal');
    if (!modal) return;
    if (e.target === modal) closeSessionModal();
});

function searchSessions() {
    const dateInput = document.getElementById('session_date');
    const date = dateInput ? dateInput.value : '';
    const timeSlotSelect = document.getElementById('time_slot');
    const timeSlot = timeSlotSelect ? timeSlotSelect.value : '';
    const container = document.getElementById('sessions_container');
    
    if (!date) {
        container.innerHTML = '<div class="empty-state"><span class="empty-state-icon">📆</span><p>' + (T.msg_please_select_date || 'Please select a date.') + '</p></div>';
        document.getElementById('absenceSummarySection').style.display = 'none';
        return;
    }
    
    container.innerHTML = '<div class="empty-state"><span class="empty-state-icon">⏳️</span><p>' + (T.loading_sessions || 'Loading sessions...') + '</p></div>';
    
    let url = 'get_admin_sessions.php?date=' + encodeURIComponent(date);
    if (timeSlot) {
        url += '&time_slot=' + encodeURIComponent(timeSlot);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div class="empty-state"><span class="empty-state-icon">🚫</span><p>Error: ' + (data.message || 'Unknown error') + '</p></div>';
                document.getElementById('absenceSummarySection').style.display = 'none';
                return;
            }
            
            if (!data.sessions || data.sessions.length === 0) {
                container.innerHTML = '<div class="empty-state"><span class="empty-state-icon">📂</span><p>' + (T.no_sessions_found || 'No sessions found for the selected date/time.') + '</p></div>';
                document.getElementById('absenceSummarySection').style.display = 'none';
                return;
            }
            latestSessions = data.sessions;
            
            let html = '<div class="sessions-grid">';
            data.sessions.forEach((session, idx) => {
                html += '<div class="session-card-mini" onclick="openSessionModal(' + idx + ')">';
                html += '<div class="session-header-mini">';
                html += '<span class="session-id-badge">#' + session.session_id + '</span>';
                html += '<span class="session-time">' + session.start_time + ' - ' + session.end_time + '</span>';
                html += '</div>';
                html += '<div class="session-info-mini">';
                html += '<h3>' + (session.class_name || 'No Class Name') + '</h3>';
                html += '</div>';
                html += '<div class="session-meta-mini">';
                html += '<span>👤 ' + session.teacher_name + '</span>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            
            container.innerHTML = html;

            // Fetch and display absence summary
            fetchAbsenceSummary(date, timeSlot);
        })
        .catch(err => {
            console.error('Error fetching sessions:', err);
            container.innerHTML = '<div class="empty-state"><span class="empty-state-icon">🚫</span><p>' + (T.error_loading_sessions || 'Error loading sessions. Please try again.') + '</p></div>';
            document.getElementById('absenceSummarySection').style.display = 'none';
        });
}

function fetchAbsenceSummary(date, timeSlot) {
    let url = 'get_absence_summary.php?date=' + encodeURIComponent(date);
    if (timeSlot) {
        url += '&time_slot=' + encodeURIComponent(timeSlot);
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayAbsenceSummary(data);
            } else {
                console.error('Error fetching absence summary:', data.message);
                document.getElementById('absenceSummarySection').style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Error fetching absence summary:', err);
            document.getElementById('absenceSummarySection').style.display = 'none';
        });
}

function displayAbsenceSummary(data) {
    const section = document.getElementById('absenceSummarySection');
    const container = document.getElementById('absenceSummaryContainer');

    if (!data.absences || data.absences.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding: 2rem;"><span class="empty-state-icon">✅</span><p>' + (T.no_absences_for_date || 'No absences recorded for this selection') + '</p></div>';
        section.style.display = 'block';
        return;
    }

    const totalAbsences = data.absences.length;
    const uniqueStudents = new Set(data.absences.map(a => a.student_name)).size;
    const motifsCount = {};
    data.absences.forEach(a => {
        const motif = a.motif || 'Unknown';
        motifsCount[motif] = (motifsCount[motif] || 0) + 1;
    });
    const mostCommonMotif = Object.keys(motifsCount).length > 0 
        ? Object.keys(motifsCount).reduce((a, b) => motifsCount[a] > motifsCount[b] ? a : b)
        : 'N/A';

    let html = '';
    html += '<div class="stats-grid">';
    html += '<div class="stat-card"><span class="stat-label">' + (T.total_absences||'Total Absences') + '</span><span class="stat-value">' + totalAbsences + '</span></div>';
    html += '<div class="stat-card"><span class="stat-label">' + (T.students_absent||'Students Absent') + '</span><span class="stat-value">' + uniqueStudents + '</span></div>';
    html += '<div class="stat-card"><span class="stat-label">' + (T.most_common_motif||'Top Motif') + '</span><span class="stat-value" style="font-size:1.25rem;">' + mostCommonMotif + '</span></div>';
    html += '</div>';

    html += '<div class="modern-table-wrapper">';
    html += '<table class="modern-table">';
    html += '<thead><tr><th>' + (T.student_name||'Student Name') + '</th><th>' + (T.date||'Date') + '</th><th>' + (T.time||'Time') + '</th><th>' + (T.motif_label||'Motif') + '</th><th>' + (T.observation||'Observation') + '</th></tr></thead>';
    html += '<tbody>';
    data.absences.forEach(absence => {
        html += '<tr><td><strong>' + absence.student_name + '</strong></td><td>' + absence.absence_date + '</td><td>' + absence.absence_time + '</td><td><span class="badge-motif">' + absence.motif + '</span></td><td>' + (absence.observation || '-') + '</td></tr>';
    });
    html += '</tbody></table></div>';

    container.innerHTML = html;
    section.style.display = 'block';
}

// Auto-search on page load with today's date
window.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure the date input is properly set
    setTimeout(function() {
        const params = new URLSearchParams(window.location.search);
        const sessionParam = params.get('session');
        if (sessionParam) {
            fetch('get_admin_sessions.php?number=' + encodeURIComponent(sessionParam))
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && Array.isArray(data.sessions) && data.sessions.length > 0) {
                        latestSessions = data.sessions;
                        openSessionModal(0);
                    } else {
                        searchSessions();
                    }
                })
                .catch(() => searchSessions());
        } else {
            searchSessions();
        }
        // Notifications are handled by sidebar.php
    }, 100);
});
</script>

</body>
</html>
