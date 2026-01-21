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
        /* Match fill_form look & feel */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f9fafb;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
        }
        
        .filters-section, .sessions-section {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #bbb;
        }

        .filters-section {
            margin-bottom: 20px;
        }
        
        .filters-section h2, .sessions-section h2 {
            margin-top: 0;
            font-weight: 600;
            color: #1f1f1f;
        }
        
        .filter-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .filter-group label {
            min-width: 150px;
            font-weight: 600;
            color: #444;
        }
        
        .filter-group input, .filter-group select {
            padding: 9px 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            font-size: 14px;
            background: #f2f2f2;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #6f42c1;
            box-shadow: 0 0 0 2px rgba(111,66,193,0.15);
            background: #fff;
        }
        
        .search-btn {
            background-color: #6f42c1;
            color: white;
            padding: 10px 18px;
            border: 1px solid #6f42c1;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: transform 0.12s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-1px);
        }
        
        .sessions-section {
            margin-top: 10px;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: start;
        }

        .session-entry {
            margin-bottom: 12px;
        }
        
        .session-btn {
            width: 100%;
            text-align: left;
            background: linear-gradient(90deg, #6f42c1, #8c63d9);
            color: #fff;
            border: 1px solid #6f42c1;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(111,66,193,0.18);
            transition: transform 0.12s ease, box-shadow 0.2s ease;
        }
        
        .session-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(111,66,193,0.22);
        }
        
        .session-btn small {
            display: block;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .session-card {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }
        
        .session-card h3 {
            margin: 0 0 10px 0;
            color: #6f42c1;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            font-size: 18px;
        }
        
        .session-details {
            display: none;
            margin-top: 8px;
        }
        
        .session-details.active {
            display: block;
        }

        /* Modal */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 18px;
        }
        .modal-backdrop.active { display: flex; }
        .modal-card {
            background: #fff;
            border-radius: 10px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 12px 30px rgba(0,0,0,0.18);
            border: 1px solid #bbb;
            padding: 18px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #666;
        }
        .modal-close:hover { color: #000; }

        /* Modal text accents */
        .modal-card h4 {
            color: #6f42c1;
        }
        .modal-card .session-info-item strong {
            color: #6f42c1;
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
            color: #1f5fbf;
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
            background-color: #6f42c1;
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
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #bbb;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }

        .absence-summary-section h2 {
            margin: 0 0 15px 0;
            font-weight: 600;
            color: #1f1f1f;
        }

        .absence-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .absence-stat-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #fcd34d;
            text-align: center;
        }

        .absence-stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #d97706;
            margin: 5px 0;
        }

        .absence-stat-label {
            font-size: 12px;
            color: #92400e;
            font-weight: 600;
        }

        .absence-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        .absence-details-table th, .absence-details-table td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: left;
        }

        .absence-details-table th {
            background-color: #f59e0b;
            color: white;
            font-weight: 600;
        }

        .absence-details-table tr:nth-child(even) {
            background-color: #fffbeb;
        }

        .absence-details-table tr:hover {
            background-color: #fef3c7;
        }

        .absence-motif-badge {
            display: inline-block;
            background: #fed7aa;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .no-absences-message {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            background: #fafafa;
            border-radius: 8px;
            border: 1px dashed #e5e7eb;
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
            <h3 id="session_modal_title" style="margin:0; font-size:18px; color:#6f42c1;"><?php echo t('session_details'); ?></h3>
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
        container.innerHTML = '<p class="no-data">Please select a date.</p>';
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
        searchSessions();
        // Notifications are handled by sidebar.php
    }, 100);
});
</script>

</body>
</html>