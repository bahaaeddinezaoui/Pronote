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
$password_db = "";
$dbname = "test_class_edition";

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('search'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* Standardize look & feel */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
        }

        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem 2rem;
        }
        
        .filters-section, .sessions-section {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .filters-section {
            margin-bottom: 1.5rem;
        }
        
        .filters-section h2, .sessions-section h2 {
            margin-top: 0;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }
        
        .filter-group {
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-group label {
            min-width: 150px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-group input, .filter-group select {
            padding: 0.6rem 0.75rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            font-size: 0.95rem;
            background: var(--background-color);
            transition: all 0.2s;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            background: var(--surface-color);
        }
        
        .search-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .search-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .sessions-section {
            margin-top: 0.5rem;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .session-entry {
            margin-bottom: 0;
        }
        
        .session-btn {
            width: 100%;
            text-align: left;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: #fff;
            border: none;
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
        }
        
        [dir="rtl"] .session-btn { text-align: right; }

        .session-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .session-btn small {
            display: block;
            margin-top: 0.25rem;
            font-weight: 400;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        .session-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow-sm);
        }
        
        .session-card h3 {
            margin: 0 0 0.75rem 0;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
            font-size: 1.125rem;
        }
        
        .session-details {
            display: none;
            margin-top: 0.5rem;
        }
        
        .session-details.active {
            display: block;
        }

        /* Modal */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5); /* Slate 900 blur effect */
            backdrop-filter: blur(4px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        .modal-backdrop.active { display: flex; }
        .modal-card {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            padding: 2rem;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-close {
            background: var(--bg-tertiary);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
        }
        .modal-close:hover { background: var(--border-color); color: var(--text-primary); }

        /* Modal text accents */
        .modal-card h4 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.1rem;
        }
        .modal-card .session-info-item strong {
            color: var(--primary-color);
        }
        
        .session-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .session-info-item {
            font-size: 14px;
            color: #444;
        }
        
        .session-info-item strong {
            color: #1f1f1f;
        }
        
        .sections-list, .absences-list, .observations-list {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e6e6e6;
        }
        
        .sections-list h4, .absences-list h4, .observations-list h4 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-size: 15px;
        }
        
        .absence-item, .observation-item {
            padding: 8px;
            margin: 5px 0;
            background: #fffdf5;
            border-left: 4px solid #ffb74d;
            border-radius: 4px;
        }
        
        .observation-item {
            background: #f1f8ff;
            border-left-color: #64b5f6;
        }
        
        .no-data {
            color: #888;
            font-style: italic;
            padding: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #666;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .detail-table th, .detail-table td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
        }
        
        .detail-table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .detail-table tr:nth-child(even) {
            background-color: #f7f9fc;
        }

        /* Notification styles removed (centralized in sidebar.php) */

        /* Highlight new observations in modal */
        .observation-item.new-observation {
            background: linear-gradient(90deg, #fef08a, #fde68a);
            border-left-color: #eab308;
            box-shadow: 0 0 8px rgba(234, 179, 8, 0.3);
        }

        .detail-table tr.new-observation-row {
            background-color: #fef08a !important;
            box-shadow: inset 0 0 6px rgba(234, 179, 8, 0.3);
        }

        .detail-table tr.new-observation-row:nth-child(even) {
            background-color: #fef08a !important;
        }

        /* Absence Summary Styles */
        .absence-summary-section {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-top: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .absence-summary-section h2 {
            margin: 0 0 1.25rem 0;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.25rem;
        }

        .absence-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .absence-stat-card {
            background: var(--background-color);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all 0.2s;
        }
        
        .absence-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-light);
            box-shadow: var(--shadow-sm);
        }

        .absence-stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 0.25rem 0;
        }

        .absence-stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .absence-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .absence-details-table th, .absence-details-table td {
            border: 1px solid var(--border-color);
            padding: 1rem;
            text-align: left;
        }
        
        [dir="rtl"] .absence-details-table th,
        [dir="rtl"] .absence-details-table td { text-align: right; }

        .absence-details-table th {
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color);
        }

        .absence-details-table tr:hover {
            background-color: var(--bg-tertiary);
        }

        .absence-motif-badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .no-absences-message {
            padding: 3rem;
            text-align: center;
            color: var(--text-secondary);
            background: var(--background-color);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--border-color);
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="admin-container">
    <div class="filters-section">
        <h2><?php echo t('search_study_sessions'); ?></h2>
        
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
        
        <button class="search-btn" onclick="searchSessions()"><?php echo t('search_sessions'); ?></button>
    </div>
    
    <div class="sessions-section">
        <h2><?php echo t('study_sessions'); ?></h2>
        <div id="sessions_container">
            <p class="no-data"><?php echo t('please_select_date_search'); ?></p>
        </div>
    </div>

    <div class="absence-summary-section" id="absenceSummarySection" style="display:none;">
        <h2><?php echo t('absence_summary'); ?></h2>
        <div id="absenceSummaryContainer"></div>
    </div>
</div>

<div id="session_modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="session_modal_title">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="session_modal_title" style="margin:0; font-size:18px; color:var(--primary-color);"><?php echo t('session_details'); ?></h3>
            <button class="modal-close" aria-label="<?php echo t('close'); ?>" onclick="closeSessionModal()">&times;</button>
        </div>
        <div id="modal_body"></div>
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
    html += '<div class="session-card">';
    html += '<h3>Session #' + session.session_id + '</h3>';

    html += '<div class="session-info">';
    html += '<div class="session-info-item"><strong>' + (T.teacher_label||'Teacher') + ':</strong> ' + session.teacher_name + '</div>';
    html += '<div class="session-info-item"><strong>' + (T.class_label||'Class') + ':</strong> ' + (session.class_name || 'N/A') + '</div>';
    html += '<div class="session-info-item"><strong>' + (T.date||'Date') + ':</strong> ' + session.session_date + '</div>';
    html += '<div class="session-info-item"><strong>' + (T.time||'Time') + ':</strong> ' + session.start_time + ' - ' + session.end_time + '</div>';
    html += '</div>';

    if (session.sections && session.sections.length > 0) {
        html += '<div class="sections-list">';
        html += '<h4>' + (T.sections||'Sections') + '</h4>';
        html += '<ul style="margin: 0; padding-left: 20px;">';
        session.sections.forEach(sec => {
            html += '<li>' + sec + '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }

    if (session.absences && session.absences.length > 0) {
        html += '<div class="absences-list">';
        html += '<h4>' + (T.absences_count || 'Absences').replace('%s', session.absences.length) + '</h4>';
        html += '<table class="detail-table">';
        html += '<tr><th>' + (T.student||'Student') + '</th><th>' + (T.time||'Time') + '</th><th>' + (T.motif_label||'Motif') + '</th><th>' + (T.observation||'Observation') + '</th></tr>';
        session.absences.forEach(abs => {
            html += '<tr>';
            html += '<td>' + abs.student_name + '</td>';
            html += '<td>' + abs.absence_time + '</td>';
            html += '<td>' + abs.motif + '</td>';
            html += '<td>' + abs.observation + '</td>';
            html += '</tr>';
        });
        html += '</table>';
        html += '</div>';
    } else {
        html += '<div class="absences-list"><p class="no-data">' + (T.no_absences_recorded||'No absences recorded') + '</p></div>';
    }

    if (session.observations && session.observations.length > 0) {
        html += '<div class="observations-list">';
        html += '<h4>' + (T.observations_count || 'Observations').replace('%s', session.observations.length) + '</h4>';
        html += '<table class="detail-table">';
        html += '<tr><th>' + (T.student||'Student') + '</th><th>' + (T.teacher||'Teacher') + '</th><th>' + (T.time||'Time') + '</th><th>' + (T.motif_label||'Motif') + '</th><th>' + (T.note||'Note') + '</th></tr>';
        session.observations.forEach(obs => {
            const isNew = obs.is_new_for_admin ? 'class="new-observation-row"' : '';
            html += '<tr ' + isNew + '>';
            html += '<td>' + obs.student_name + '</td>';
            html += '<td>' + obs.teacher_name + '</td>';
            html += '<td>' + obs.observation_time + '</td>';
            html += '<td>' + obs.motif + '</td>';
            html += '<td>' + obs.note + '</td>';
            html += '</tr>';
            // Mark observation as read
            if (obs.is_new_for_admin) {
                markObservationRead(obs.observation_id);
            }
        });
        html += '</table>';
        html += '</div>';
    } else {
        html += '<div class="observations-list"><p class="no-data">' + (T.no_observations_recorded||'No observations recorded') + '</p></div>';
    }

    html += '</div>'; // session-card

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
        container.innerHTML = '<p class="no-data">' + (T.msg_please_select_date || 'Please select a date.') + '</p>';
        document.getElementById('absenceSummarySection').style.display = 'none';
        return;
    }
    
    container.innerHTML = '<p class="loading">' + (T.loading_sessions || 'Loading sessions...') + '</p>';
    
    let url = 'get_admin_sessions.php?date=' + encodeURIComponent(date);
    if (timeSlot) {
        url += '&time_slot=' + encodeURIComponent(timeSlot);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<p class="no-data">Error: ' + (data.message || 'Unknown error') + '</p>';
                document.getElementById('absenceSummarySection').style.display = 'none';
                return;
            }
            
            if (!data.sessions || data.sessions.length === 0) {
                container.innerHTML = '<p class="no-data">' + (T.no_sessions_found || 'No sessions found for the selected date/time.') + '</p>';
                document.getElementById('absenceSummarySection').style.display = 'none';
                return;
            }
            latestSessions = data.sessions;
            
            let html = '<div class="sessions-grid">';
            data.sessions.forEach((session, idx) => {
                html += '<div class="session-entry">';
                html += '<button type="button" class="session-btn" aria-expanded="false" onclick="openSessionModal(' + idx + ')">';
                html += 'Session #' + session.session_id + ' • ' + session.session_date + ' • ' + session.start_time + ' - ' + session.end_time;
                html += '<small>Teacher: ' + session.teacher_name + ' | Class: ' + (session.class_name || 'N/A') + '</small>';
                html += '</button>';
                html += '</div>';
            });
            html += '</div>'; // sessions-grid
            
            container.innerHTML = html;

            // Fetch and display absence summary
            fetchAbsenceSummary(date, timeSlot);
        })
        .catch(err => {
            console.error('Error fetching sessions:', err);
            container.innerHTML = '<p class="no-data">' + (T.error_loading_sessions || 'Error loading sessions. Please try again.') + '</p>';
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
        container.innerHTML = '<div class="no-absences-message">' + (T.no_absences_for_date || '✓ No absences recorded for the selected date and time') + '</div>';
        section.style.display = 'block';
        return;
    }

    // Calculate statistics
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
    
    // Stats cards
    html += '<div class="absence-stats-grid">';
    html += '<div class="absence-stat-card">';
    html += '<div class="absence-stat-label">' + (T.total_absences||'Total Absences') + '</div>';
    html += '<div class="absence-stat-number">' + totalAbsences + '</div>';
    html += '</div>';
    
    html += '<div class="absence-stat-card">';
    html += '<div class="absence-stat-label">' + (T.students_absent||'Students Absent') + '</div>';
    html += '<div class="absence-stat-number">' + uniqueStudents + '</div>';
    html += '</div>';
    
    html += '<div class="absence-stat-card">';
    html += '<div class="absence-stat-label">' + (T.most_common_motif||'Most Common Motif') + '</div>';
    html += '<div class="absence-stat-number" style="font-size: 16px; overflow: hidden; text-overflow: ellipsis;">' + mostCommonMotif + '</div>';
    html += '</div>';
    html += '</div>';

    // Detailed absence table
    html += '<table class="absence-details-table">';
    html += '<thead><tr>';
    html += '<th>' + (T.student_name||'Student Name') + '</th>';
    html += '<th>' + (T.date||'Date') + '</th>';
    html += '<th>' + (T.time||'Time') + '</th>';
    html += '<th>' + (T.motif_label||'Motif') + '</th>';
    html += '<th>' + (T.observation||'Observation') + '</th>';
    html += '</tr></thead>';
    html += '<tbody>';
    
    data.absences.forEach(absence => {
        html += '<tr>';
        html += '<td><strong>' + absence.student_name + '</strong></td>';
        html += '<td>' + absence.absence_date + '</td>';
        html += '<td>' + absence.absence_time + '</td>';
        html += '<td><span class="absence-motif-badge">' + absence.motif + '</span></td>';
        html += '<td>' + (absence.observation || '-') + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';

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
