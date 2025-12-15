<?php
// admin_dashboard.php - Admin view to see sessions by date and time slot
session_start();
date_default_timezone_set('Africa/Algiers');

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
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
    $stmt = $conn->prepare("SELECT ADMINISTRATOR_FIRST_NAME, ADMINISTRATOR_LAST_NAME FROM administrator WHERE USER_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME']);
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Admin Dashboard</title>
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
        
        .navbar-admin {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-admin a {
            text-decoration: none;
            color: #6b7280;
            padding: 0.5rem 1rem;
            margin-left: 1rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .navbar-admin a:hover {
            background: #f3f4f6;
            color: #4f46e5;
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

        /* Notification styles */
        .notification-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            min-width: 24px;
            height: 24px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .notification-bell {
            cursor: pointer;
            font-size: 20px;
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .notifications-panel {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 400px;
            max-height: 500px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .notifications-panel.active {
            display: block;
        }

        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .notification-item:hover {
            background-color: #fde68a;
        }

        .notification-item.new {
            background-color: #dbeafe;
            border-left-color: #3b82f6;
            font-weight: 500;
        }

        .notification-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .notification-item-student {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .notification-item-time {
            font-size: 12px;
            color: #6b7280;
        }

        .notification-item-details {
            font-size: 13px;
            color: #374151;
            margin-top: 4px;
        }

        .notification-empty {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
        }

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
    </style>
</head>
<body>

<div class="navbar-admin">
    <div style="font-family: sans-serif; display:flex; align-items:center; justify-content:space-between; width:100%;">
        <a href="admin_dashboard.php" style="text-decoration:none; color:inherit;">Home</a>
        <div style="display:flex; align-items:center; gap:20px; margin-left:auto;">
            <div class="notification-bell" id="notificationBell" onclick="toggleNotificationsPanel()">
                🔔
                <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
                <div class="notifications-panel" id="notificationsPanel">
                    <div style="padding:12px; border-bottom:1px solid #e5e7eb; font-weight:600; background:#f9fafb;">
                        New Observations
                    </div>
                    <div id="notificationsContent"></div>
                </div>
            </div>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="admin-container">
    <div class="filters-section">
        <h2>Search Study Sessions</h2>
        
        <div class="filter-group">
            <label for="session_date">Select Date:</label>
            <input type="date" id="session_date" value="<?php echo $current_date; ?>">
        </div>
        
        <div class="filter-group">
            <label for="time_slot">Select Time Slot:</label>
            <select id="time_slot">
                <option value="">-- All Time Slots --</option>
                <?php foreach ($time_slots as $slot): ?>
                    <option value="<?php echo htmlspecialchars($slot['start'] . '|' . $slot['end']); ?>">
                        <?php echo htmlspecialchars($slot['value']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button class="search-btn" onclick="searchSessions()">🔍 Search Sessions</button>
    </div>
    
    <div class="sessions-section">
        <h2>Study Sessions</h2>
        <div id="sessions_container">
            <p class="no-data">Please select a date and click "Search Sessions" to view study sessions.</p>
        </div>
    </div>
</div>

<div id="session_modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="session_modal_title">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="session_modal_title" style="margin:0; font-size:18px; color:#6f42c1;">Session details</h3>
            <button class="modal-close" aria-label="Close" onclick="closeSessionModal()">&times;</button>
        </div>
        <div id="modal_body"></div>
    </div>
</div>

<script>
let latestSessions = [];
let newNotifications = [];

function toggleNotificationsPanel() {
    const panel = document.getElementById('notificationsPanel');
    if (panel) {
        panel.classList.toggle('active');
    }
}

function markObservationRead(observationId) {
    fetch('mark_observation_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ observation_id: observationId })
    }).catch(err => console.error('Error marking observation as read:', err));
}

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
    
    if (newNotifications.length > 0) {
        countBadge.textContent = newNotifications.length;
        countBadge.style.display = 'flex';
        
        let html = '';
        newNotifications.forEach((notif, idx) => {
            html += `<div class="notification-item new" onclick="handleNotificationClick(${idx})">
                <div class="notification-item-header">
                    <span class="notification-item-student">${notif.student_name}</span>
                    <span class="notification-item-time">${notif.observation_time}</span>
                </div>
                <div class="notification-item-details">
                    <div><strong>Teacher:</strong> ${notif.teacher_name}</div>
                    <div><strong>Session:</strong> ${notif.session_date} (${notif.session_time})</div>
                    <div><strong>Motif:</strong> ${notif.motif}</div>
                </div>
            </div>`;
        });
        content.innerHTML = html;
    } else {
        countBadge.style.display = 'none';
        content.innerHTML = '<div class="notification-empty">No new observations</div>';
    }
}

function handleNotificationClick(notifIndex) {
    const notif = newNotifications[notifIndex];
    if (!notif) return;

    // Close notifications panel
    const panel = document.getElementById('notificationsPanel');
    if (panel) panel.classList.remove('active');

    // Fetch this specific session to get fresh data (including the new observation)
    // We reuse searchSessions logic but pointing to a specific ID
    fetch('get_admin_sessions.php?number=' + encodeURIComponent(notif.session_id))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.sessions && data.sessions.length > 0) {
                // Update our latestSessions list with this fresh session 
                // (or just use it directly, but updating list keeps state consistent if we view others)
                const freshSession = data.sessions[0];
                
                // Check if it's already in our list, if so replace it, otherwise add it
                const existingIdx = latestSessions.findIndex(s => s.session_id == freshSession.session_id);
                if (existingIdx >= 0) {
                    latestSessions[existingIdx] = freshSession;
                    openSessionModal(existingIdx);
                } else {
                    latestSessions.push(freshSession);
                    openSessionModal(latestSessions.length - 1);
                }
            } else {
                alert('Session details could not be loaded.');
            }
        })
        .catch(err => {
            console.error('Error fetching session details:', err);
            alert('Error loading session details.');
        });
}

// Close notifications panel when clicking outside
document.addEventListener('click', function(event) {
    const notifBell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationsPanel');
    
    if (notifBell && panel && !notifBell.contains(event.target)) {
        panel.classList.remove('active');
    }
});

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
    html += '<div class="session-info-item"><strong>Teacher:</strong> ' + session.teacher_name + '</div>';
    html += '<div class="session-info-item"><strong>Class:</strong> ' + (session.class_name || 'N/A') + '</div>';
    html += '<div class="session-info-item"><strong>Date:</strong> ' + session.session_date + '</div>';
    html += '<div class="session-info-item"><strong>Time:</strong> ' + session.start_time + ' - ' + session.end_time + '</div>';
    html += '</div>';

    if (session.sections && session.sections.length > 0) {
        html += '<div class="sections-list">';
        html += '<h4>Sections:</h4>';
        html += '<ul style="margin: 0; padding-left: 20px;">';
        session.sections.forEach(sec => {
            html += '<li>' + sec + '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }

    if (session.absences && session.absences.length > 0) {
        html += '<div class="absences-list">';
        html += '<h4>Absences (' + session.absences.length + '):</h4>';
        html += '<table class="detail-table">';
        html += '<tr><th>Student</th><th>Time</th><th>Motif</th><th>Observation</th></tr>';
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
        html += '<div class="absences-list"><p class="no-data">No absences recorded</p></div>';
    }

    if (session.observations && session.observations.length > 0) {
        html += '<div class="observations-list">';
        html += '<h4>Observations (' + session.observations.length + '):</h4>';
        html += '<table class="detail-table">';
        html += '<tr><th>Student</th><th>Teacher</th><th>Time</th><th>Motif</th><th>Note</th></tr>';
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
        html += '<div class="observations-list"><p class="no-data">No observations recorded</p></div>';
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
        return;
    }
    
    container.innerHTML = '<p class="loading">Loading sessions...</p>';
    
    let url = 'get_admin_sessions.php?date=' + encodeURIComponent(date);
    if (timeSlot) {
        url += '&time_slot=' + encodeURIComponent(timeSlot);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<p class="no-data">Error: ' + (data.message || 'Unknown error') + '</p>';
                return;
            }
            
            if (!data.sessions || data.sessions.length === 0) {
                container.innerHTML = '<p class="no-data">No sessions found for the selected date/time.</p>';
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
        })
        .catch(err => {
            console.error('Error fetching sessions:', err);
            container.innerHTML = '<p class="no-data">Error loading sessions. Please try again.</p>';
        });
}

// Auto-search on page load with today's date
window.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure the date input is properly set
    setTimeout(function() {
        searchSessions();
        fetchNotifications();
        
        // Fetch notifications every 5 seconds for real-time updates
        setInterval(fetchNotifications, 5000);
    }, 100);
});
</script>

</body>
</html>