<?php
// admin_search_student.php - Search for students and view their records
session_start();
date_default_timezone_set('Africa/Algiers');

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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student Records - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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

        .navbar-admin a.active {
            color: #6f42c1;
            font-weight: 600;
        }

        .search-section {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }

        .search-section h2 {
            margin: 0 0 15px 0;
            color: #6f42c1;
            font-size: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        select:focus {
            outline: none;
            border-color: #6f42c1;
            box-shadow: 0 0 3px rgba(111, 66, 193, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #6f42c1;
            color: white;
        }

        .btn-primary:hover {
            background: #5d3fa3;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.3);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .results-section {
            display: none;
        }

        .results-section.active {
            display: block;
        }

        .student-info {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }

        .student-info h3 {
            font-size: 20px;
            color: #6f42c1;
            margin: 0 0 15px 0;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #6f42c1;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .records-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .record-section {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }

        .record-section h4 {
            color: #6f42c1;
            margin: 0 0 15px 0;
            font-size: 16px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .records-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .record-item {
            background: #f9fafb;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 3px solid #6f42c1;
        }

        .record-item:last-child {
            margin-bottom: 0;
        }

        .record-date {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .record-motif {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .record-note {
            color: #374151;
            font-size: 13px;
            line-height: 1.5;
        }

        .empty-message {
            text-align: center;
            color: #6b7280;
            padding: 20px;
            background: #f9fafb;
            border-radius: 6px;
            font-size: 14px;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .error-message.active {
            display: block;
        }

        .success-message {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .success-message.active {
            display: block;
        }

        .loader {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .loader.active {
            display: block;
        }

        .spinner {
            border: 4px solid #e5e7eb;
            border-top: 4px solid #6f42c1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #suggestionsContainer {
            display: none;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #bbb;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            max-height: 300px;
            overflow-y: auto;
        }

        #suggestionsList {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #suggestionsList li {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        #suggestionsList li:hover {
            background-color: #f3f4f6;
            color: #6f42c1;
        }

        #suggestionsList li:last-child {
            border-bottom: none;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .records-container {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="navbar-admin">
    <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
        <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
        <div style="display:flex; align-items:center;">
            <a href="admin_home.php" class="navbar_buttons">Home</a>
            <a href="admin_dashboard.php" class="navbar_buttons">Search</a>
            <a href="admin_search_student.php" class="navbar_buttons active">Student Records</a>
            <a href="profile.php" class="navbar_buttons">Profile</a>
        </div>
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
            <a href="logout.php" class="navbar_buttons logout-btn">Logout</a>
        </div>
    </div>
</div>

<div class="admin-container">

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <div class="search-section">
                <h2>🔍 Search Student</h2>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="searchInput">Student Name (First or Last Name)</label>
                        <input type="text" id="searchInput" placeholder="Enter student name..." autocomplete="off">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="button-group">
                            <button class="btn btn-primary" onclick="searchStudent()">🔍 Search</button>
                            <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="suggestionsContainer" style="display: none; margin-bottom: 20px;">
                <div style="background: white; border: 1px solid #ddd; border-radius: 5px; max-height: 300px; overflow-y: auto;">
                    <ul id="suggestionsList" style="list-style: none; padding: 0; margin: 0;"></ul>
                </div>
            </div>

            <div class="loader" id="loader">
                <div class="spinner"></div>
                <p>Loading student records...</p>
            </div>

            <div class="results-section" id="resultsSection">
                <div id="studentInfo" style="display: none;"></div>
                <div id="recordsContainer"></div>
            </div>
        </div>
    </div>

    <script>
        let selectedStudent = null;
        let allStudents = [];

        // Initialize with today's date range
        function initializeDates() {
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

            document.getElementById('startDate').valueAsDate = thirtyDaysAgo;
            document.getElementById('endDate').valueAsDate = today;
        }

        // Fetch all students for autocomplete
        async function fetchAllStudents() {
            try {
                const response = await fetch('get_all_students.php');
                const data = await response.json();
                if (data.success) {
                    allStudents = data.students;
                }
            } catch (error) {
                console.error('Error fetching students:', error);
            }
        }

        // Handle search input with autocomplete
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = this.value.toLowerCase().trim();
            
            if (query.length < 2) {
                document.getElementById('suggestionsContainer').style.display = 'none';
                return;
            }

            const matches = allStudents.filter(s => 
                s.first_name.toLowerCase().includes(query) ||
                s.last_name.toLowerCase().includes(query) ||
                (s.first_name + ' ' + s.last_name).toLowerCase().includes(query)
            );

            if (matches.length > 0) {
                const suggestionsList = document.getElementById('suggestionsList');
                suggestionsList.innerHTML = matches.slice(0, 10).map(student => `
                    <li style="padding: 12px; border-bottom: 1px solid #eee; cursor: pointer;" 
                        onclick="selectStudent('${student.serial_number}', '${student.first_name}', '${student.last_name}')">
                        <strong>${student.first_name} ${student.last_name}</strong>
                        <br><small style="color: #999;">ID: ${student.serial_number}</small>
                    </li>
                `).join('');
                document.getElementById('suggestionsContainer').style.display = 'block';
            } else {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });

        function selectStudent(serialNumber, firstName, lastName) {
            selectedStudent = { serialNumber, firstName, lastName };
            document.getElementById('searchInput').value = firstName + ' ' + lastName;
            document.getElementById('suggestionsContainer').style.display = 'none';
        }

        function showError(message) {
            const errorMsg = document.getElementById('errorMessage');
            errorMsg.textContent = message;
            errorMsg.classList.add('active');
            setTimeout(() => errorMsg.classList.remove('active'), 5000);
        }

        function showSuccess(message) {
            const successMsg = document.getElementById('successMessage');
            successMsg.textContent = message;
            successMsg.classList.add('active');
            setTimeout(() => successMsg.classList.remove('active'), 5000);
        }

        async function searchStudent() {
            if (!selectedStudent) {
                showError('Please select a student from the suggestions');
                return;
            }

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) {
                showError('Please select both start and end dates');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                showError('Start date must be before end date');
                return;
            }

            document.getElementById('loader').classList.add('active');
            document.getElementById('resultsSection').classList.remove('active');

            try {
                const response = await fetch('get_student_records.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `serial_number=${encodeURIComponent(selectedStudent.serialNumber)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`
                });

                const data = await response.json();
                document.getElementById('loader').classList.remove('active');

                if (data.success) {
                    displayResults(data.student, data.absences, data.observations);
                    document.getElementById('resultsSection').classList.add('active');
                    showSuccess('Student records loaded successfully');
                } else {
                    showError(data.message || 'Failed to load student records');
                }
            } catch (error) {
                document.getElementById('loader').classList.remove('active');
                showError('Error searching student: ' + error.message);
            }
        }

        function displayResults(student, absences, observations) {
            // Display student info
            const studentInfoHtml = `
                <div class="student-info">
                    <h3>📋 ${student.first_name} ${student.last_name}</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Serial Number</div>
                            <div class="info-value">${student.serial_number}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Section</div>
                            <div class="info-value">${student.section_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Category</div>
                            <div class="info-value">${student.category_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Grade</div>
                            <div class="info-value">${student.grade || 'N/A'}</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('studentInfo').innerHTML = studentInfoHtml;
            document.getElementById('studentInfo').style.display = 'block';

            // Display records
            let recordsHtml = '<div class="records-container">';

            // Absences
            recordsHtml += `
                <div class="record-section">
                    <h4><span class="record-icon">⚠️</span>Absences (${absences.length})</h4>
                    ${absences.length > 0 ? `
                        <ul class="records-list">
                            ${absences.map(absence => `
                                <li class="record-item">
                                    <div class="record-date">${new Date(absence.absence_date_and_time).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
                                    <div class="record-motif">Motif: ${absence.absence_motif || 'Not specified'}</div>
                                    ${absence.absence_observation ? `<div class="record-note"><strong>Note:</strong> ${absence.absence_observation}</div>` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    ` : `<div class="empty-message">No absences recorded in this period</div>`}
                </div>
            `;

            // Observations
            recordsHtml += `
                <div class="record-section">
                    <h4><span class="record-icon">📝</span>Observations (${observations.length})</h4>
                    ${observations.length > 0 ? `
                        <ul class="records-list">
                            ${observations.map(obs => `
                                <li class="record-item">
                                    <div class="record-date">${new Date(obs.observation_date_and_time).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })} - ${obs.teacher_name}</div>
                                    <div class="record-motif">Motif: ${obs.observation_motif || 'Not specified'}</div>
                                    ${obs.observation_note ? `<div class="record-note"><strong>Note:</strong> ${obs.observation_note}</div>` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    ` : `<div class="empty-message">No observations recorded in this period</div>`}
                </div>
            `;

            recordsHtml += '</div>';
            document.getElementById('recordsContainer').innerHTML = recordsHtml;
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('resultsSection').classList.remove('active');
            document.getElementById('suggestionsContainer').style.display = 'none';
            selectedStudent = null;
            initializeDates();
        }

        // Initialize
        window.addEventListener('load', function() {
            initializeDates();
            fetchAllStudents();
            fetchNotifications(); // Initial fetch
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchInput') && !e.target.closest('#suggestionsContainer')) {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });

        /* --- Notification Logic --- */
        let newNotifications = [];

        function toggleNotificationsPanel() {
            const panel = document.getElementById('notificationsPanel');
            if (panel) {
                panel.classList.toggle('active');
            }
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
                newNotifications.forEach((notif) => {
                    html += `<div class="notification-item new">
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

        // Close notifications panel when clicking outside
        document.addEventListener('click', function(event) {
            const notifBell = document.getElementById('notificationBell');
            const panel = document.getElementById('notificationsPanel');
            
            if (notifBell && panel && !notifBell.contains(event.target)) {
                panel.classList.remove('active');
            }
        });

        // Refresh notifications every 30 seconds
        setInterval(fetchNotifications, 30000);
    </script>
</body>
</html>
