<?php
// admin_search_student.php - Search for students and view their records
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('student_records'); ?> - <?php echo t('app_name'); ?></title>
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

        .student-photo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 50%;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            width: 140px;
            height: 140px;
            margin: 0 auto;
        }

        .student-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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
            background: #f8f9fa;
            border: 1px solid #bbb;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
        }

        #suggestionsList {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .student-card {
            position: relative;
            width: 100%;
            height: 220px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .student-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 30px rgba(111, 66, 193, 0.35);
        }

        .student-card-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }

        .student-card-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .student-card-bg.placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .student-card-bg.placeholder::after {
            content: '👤';
            font-size: 60px;
            opacity: 0.5;
        }

        .student-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 15px 15px;
            background: linear-gradient(to top, 
                rgba(0, 0, 0, 0.9) 0%, 
                rgba(0, 0, 0, 0.7) 40%, 
                rgba(0, 0, 0, 0.3) 70%,
                transparent 100%);
        }

        .student-card-name {
            font-weight: 700;
            color: #ffffff;
            font-size: 15px;
            margin-bottom: 6px;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .student-card-id {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
            background: rgba(111, 66, 193, 0.8);
            padding: 4px 10px;
            border-radius: 12px;
            font-family: monospace;
            display: inline-block;
            backdrop-filter: blur(4px);
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

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="admin-container">

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <div class="search-section">
                <h2>🔍 <?php echo t('search_students'); ?></h2>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="searchInput"><?php echo t('search_student_name_label'); ?></label>
                        <input type="text" id="searchInput" placeholder="<?php echo t('student_search_placeholder'); ?>" autocomplete="off">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <div class="button-group">
                            <button class="btn btn-secondary" onclick="clearSearch()"><?php echo t('clear'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="suggestionsContainer">
                <ul id="suggestionsList"></ul>
            </div>

            <div class="loader" id="loader">
                <div class="spinner"></div>
                <p><?php echo t('loading_student_records'); ?></p>
            </div>

            <div class="results-section" id="resultsSection" style="margin-top: 20px;">
                <div id="studentInfo" style="display: none;"></div>

                <div class="record-section" id="dateFilterSection" style="display:none; margin-bottom: 20px;">
                    <h4>🗓️ <?php echo t('select_date'); ?></h4>
                    <div class="form-row" style="margin-bottom: 0;">
                        <div class="form-group">
                            <label for="startDate"><?php echo t('start_date'); ?></label>
                            <input type="date" id="startDate">
                        </div>
                        <div class="form-group">
                            <label for="endDate"><?php echo t('end_date'); ?></label>
                            <input type="date" id="endDate">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="button-group">
                                <button class="btn btn-secondary" onclick="clearSearch()"><?php echo t('clear'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-toggles" id="viewToggles" style="display:none; margin: 20px 0; gap: 10px;">
                    <button class="btn btn-primary" id="btnRecords" onclick="switchView('records')"><?php echo t('btn_student_records'); ?></button>
                    <button class="btn btn-secondary" id="btnFullInfo" onclick="switchView('fullInfo')"><?php echo t('btn_full_information'); ?></button>
                </div>

                <div id="recordsContainer"></div>
                <div id="fullInfoContainer" style="display: none;"></div>
            </div>
    </div>
</div>
</div>
</div>

    </div>
</div>
</div>

<script>
        const T = <?php echo json_encode($T); ?>;
        const t = (key) => T[key] || key;

        let selectedStudent = null;
        let allStudents = [];
        let autoSearchDebounceTimer = null;
        let lastAutoSearchKey = null;

        const isImageFilename = (value) => typeof value === 'string' && /\.(jpe?g|png|gif|webp)$/i.test(value);
        const resolvePhotoUrl = (value) => {
            if (!value) return null;
            value = String(value).replace(/\\/g, '/').replace(/^\/+/, '');
            if (value.startsWith('data:') || value.startsWith('http')) return value;
            if (value.includes('/')) return value;
            if (isImageFilename(value)) return `resources/photos/students/${value}`;
            return `data:image/jpeg;base64,${value}`;
        };

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
                (s.first_name + ' ' + s.last_name).toLowerCase().includes(query) ||
                s.serial_number.toLowerCase().includes(query)
            );

            if (matches.length > 0) {
                const suggestionsList = document.getElementById('suggestionsList');
                suggestionsList.innerHTML = matches.slice(0, 12).map(student => {
                    let bgStyle = '';
                    let bgClass = 'placeholder';

                    const photoUrl = student.photo ? resolvePhotoUrl(student.photo) : null;
                    const photoImg = photoUrl
                        ? `<img class="student-card-img" src="${photoUrl}" onerror="this.onerror=null;this.src='assets/placeholder-student.png';" />`
                        : '';
                    
                    if (student.photo) {
                        bgStyle = `style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"`;
                    }
                    
                    return `
                        <li class="student-card" onclick="selectStudent('${student.serial_number}', '${student.first_name}', '${student.last_name}')">
                            <div class="student-card-bg ${bgClass}" ${bgStyle}></div>
                            ${photoImg}
                            <div class="student-card-overlay">
                                <div class="student-card-name">${student.first_name} ${student.last_name}</div>
                                <div class="student-card-id">${student.serial_number}</div>
                            </div>
                        </li>
                    `;
                }).join('');
                document.getElementById('suggestionsContainer').style.display = 'block';
            } else {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });

        function selectStudent(serialNumber, firstName, lastName) {
            selectedStudent = { serialNumber, firstName, lastName };
            document.getElementById('searchInput').value = firstName + ' ' + lastName;
            document.getElementById('suggestionsContainer').style.display = 'none';

            const dateFilterSection = document.getElementById('dateFilterSection');
            if (dateFilterSection) {
                dateFilterSection.style.display = 'block';
            }

            const startDateEl = document.getElementById('startDate');
            if (startDateEl && !startDateEl.value) {
                startDateEl.focus();
            }

            scheduleAutoSearch();
        }

        function scheduleAutoSearch() {
            if (autoSearchDebounceTimer) {
                clearTimeout(autoSearchDebounceTimer);
            }
            autoSearchDebounceTimer = setTimeout(() => {
                tryAutoSearch();
            }, 200);
        }

        async function tryAutoSearch() {
            if (!selectedStudent) return;

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) return;

            if (new Date(startDate) > new Date(endDate)) {
                showError(t('msg_start_before_end'));
                return;
            }

            const searchKey = `${selectedStudent.serialNumber}|${startDate}|${endDate}`;
            if (lastAutoSearchKey === searchKey) return;
            lastAutoSearchKey = searchKey;

            await searchStudent();
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
                showError(t('msg_select_student_suggestion'));
                return;
            }

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) {
                showError(t('msg_select_both_dates'));
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                showError(t('msg_start_before_end'));
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
                    showSuccess(t('msg_records_loaded_success'));
                } else {
                    showError(data.message || t('msg_failed_load_records'));
                }
            } catch (error) {
                document.getElementById('loader').classList.remove('active');
                showError(t('msg_error_searching') + error.message);
            }
        }

        let currentStudentData = null;

        function switchView(view) {
            const btnRecords = document.getElementById('btnRecords');
            const btnFullInfo = document.getElementById('btnFullInfo');
            const recordsContainer = document.getElementById('recordsContainer');
            const fullInfoContainer = document.getElementById('fullInfoContainer');
            const dateFilterSection = document.getElementById('dateFilterSection');

            if (view === 'records') {
                btnRecords.className = 'btn btn-primary';
                btnFullInfo.className = 'btn btn-secondary';
                recordsContainer.style.display = 'grid'; // Restore grid layout
                fullInfoContainer.style.display = 'none';
                if (dateFilterSection) {
                    dateFilterSection.style.display = 'block';
                    if (dateFilterSection.parentElement !== recordsContainer) {
                        recordsContainer.insertAdjacentElement('afterbegin', dateFilterSection);
                    }
                }
            } else {
                btnRecords.className = 'btn btn-secondary';
                btnFullInfo.className = 'btn btn-primary';
                recordsContainer.style.display = 'none';
                fullInfoContainer.style.display = 'block';
                if (dateFilterSection) {
                    dateFilterSection.style.display = 'none';
                }
            }
        }

        function displayResults(student, absences, observations) {
            currentStudentData = student;
            
            // Show toggles
            document.getElementById('viewToggles').style.display = 'flex';
            
            // Default to records view
            switchView('records');

            // Display student info header
            const studentPhotoUrl = student.photo ? resolvePhotoUrl(student.photo) : null;
            const studentInfoHtml = `
                <div class="student-info">
                    <h3>📋 ${student.first_name} ${student.last_name}</h3>
                    <div class="info-grid" style="grid-template-columns: 140px 1fr 1fr; gap: 20px; align-items: start;">
                        <div class="info-item" style="grid-row: span 2; border-left-color: #10b981; padding: 15px;">
                            <div class="info-label">${t('student_photo')}</div>
                            <div class="student-photo-box" style="width: 110px; height: 110px; margin: 0;">
                                <img src="${studentPhotoUrl || 'assets/placeholder-student.png'}" onerror="this.onerror=null;this.src='assets/placeholder-student.png';" alt="${t('student_photo')}">
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">${t('serial_number')}</div>
                            <div class="info-value">${student.serial_number}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">${t('section')}</div>
                            <div class="info-value">${student.section_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">${t('category')}</div>
                            <div class="info-value">${student.category_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">${t('grade_level')}</div>
                            <div class="info-value">${student.grade || 'N/A'}</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('studentInfo').innerHTML = studentInfoHtml;
            document.getElementById('studentInfo').style.display = 'block';

            // --- Render Records (Absences/Observations) ---
            let recordsHtml = ''; // Grid container is the parent div now

            // Absences
            recordsHtml += `
                <div class="record-section">
                    <h4><span class="record-icon">⚠️</span>${t('absences')} (${absences.length})</h4>
                    ${absences.length > 0 ? `
                        <ul class="records-list">
                            ${absences.map(absence => `
                                <li class="record-item">
                                    <div class="record-date">${new Date(absence.absence_date_and_time).toLocaleDateString('<?php echo $LANG === 'ar' ? 'ar-EG' : 'en-US'; ?>', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
                                    <div class="record-motif">${t('motif')}: ${absence.absence_motif || t('not_specified')}</div>
                                    ${absence.absence_observation ? `<div class="record-note"><strong>${t('observation')}:</strong> ${absence.absence_observation}</div>` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    ` : `<div class="empty-message">${t('no_absences_period')}</div>`}
                </div>
            `;

            // Observations
            recordsHtml += `
                <div class="record-section">
                    <h4><span class="record-icon">📝</span>${t('observations')} (${observations.length})</h4>
                    ${observations.length > 0 ? `
                        <ul class="records-list">
                            ${observations.map(obs => `
                                <li class="record-item">
                                    <div class="record-date">${new Date(obs.observation_date_and_time).toLocaleDateString('<?php echo $LANG === 'ar' ? 'ar-EG' : 'en-US'; ?>', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })} - ${obs.teacher_name}</div>
                                    <div class="record-motif">${t('motif')}: ${obs.observation_motif || t('not_specified')}</div>
                                    ${obs.observation_note ? `<div class="record-note"><strong>${t('observation')}:</strong> ${obs.observation_note}</div>` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    ` : `<div class="empty-message">${t('no_observations_period')}</div>`}
                </div>
            `;
            // Keep the grid structure logic for records
            const recordsContainer = document.getElementById('recordsContainer');
            const dateFilterSection = document.getElementById('dateFilterSection');
            recordsContainer.innerHTML = '';
            if (dateFilterSection) {
                dateFilterSection.style.display = 'block';
                recordsContainer.appendChild(dateFilterSection);
            }
            recordsContainer.insertAdjacentHTML('beforeend', recordsHtml);

            // --- Render Full Info ---
            renderFullInfo(student);
        }

        function renderFullInfo(s) {
            const createSection = (title, content) => `
                <div class="record-section" style="margin-bottom: 20px;">
                    <h4>${title}</h4>
                    <div class="info-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                        ${content}
                    </div>
                </div>
            `;
            
            const item = (label, value) => `
                <div class="info-item" style="border-left-color: #3b82f6;">
                    <div class="info-label">${label}</div>
                    <div class="info-value">${value || 'N/A'}</div>
                </div>
            `;

            let html = '';

             // 1. Personal Details
            html += createSection('👤 ' + t('step_personal_details'), `
                ${item(t('label_first_name_en'), s.first_name)}
                ${item(t('label_last_name_en'), s.last_name)}
                ${item(t('label_first_name_ar'), s.first_name_ar)}
                ${item(t('label_last_name_ar'), s.last_name_ar)}
                ${item(t('label_sex'), t(s.sex.toLowerCase()))}
                ${item(t('label_birth_date'), s.birth_date)}
                ${item(t('label_blood_type'), s.blood_type)}
                ${item(t('label_phone'), s.personal_phone)}
                ${item(t('label_height'), s.height_cm + ' cm')}
                ${item(t('label_weight'), s.weight_kg + ' kg')}
                ${item(t('label_is_foreign'), t(s.is_foreign.toLowerCase()))}
            `);

            // 2. Academic
            html += createSection('🎓 ' + t('step_academic_info'), `
                ${item(t('label_speciality'), s.speciality)}
                ${item(t('label_academic_level'), s.academic_level)}
                ${item(t('label_academic_average'), s.academic_average)}
                ${item(t('label_bac_number'), s.bac_number)}
                ${item(t('category'), s.category_name)}
                ${item(t('grade_level'), s.grade)}
                ${item(t('army'), s.army_name)}
            `);

            // 3. Parents & Family
            html += createSection('👪 ' + t('step_family_info'), `
                ${item(t('label_father_name_en'), s.father_name_en)}
                ${item(t('label_father_name_ar'), s.father_name_ar)}
                ${item(t('label_father_prof_en'), s.father_profession)}
                ${item(t('label_father_prof_ar'), s.father_profession_ar)}
                
                ${item(t('label_mother_name_en'), s.mother_name_en)}
                ${item(t('label_mother_name_ar'), s.mother_name_ar)}
                ${item(t('label_mother_prof_en'), s.mother_profession)}
                ${item(t('label_mother_prof_ar'), s.mother_profession_ar)}
                
                ${item(t('label_orphan_status'), t('orphan_' + s.orphans_status.toLowerCase()))}
                ${item(t('label_parents_situation'), t(s.parents_situation.toLowerCase()))}
                
                ${item(t('label_siblings_count'), s.siblings_count)}
                ${item(t('label_sisters_count'), s.sisters_count)}
                ${item(t('label_order_among_siblings'), s.order_among_siblings)}
            `);

            // 4. Addresses
            html += createSection('📍 ' + t('step_addresses'), `
                <div style="grid-column: span 2;">
                    ${item(t('label_birth_place_address'), s.birth_place)}
                </div>
                <div style="grid-column: span 2;">
                    ${item(t('label_personal_address'), s.personal_address)}
                </div>
            `);
            
            // 5. Uniforms
            if (s.uniforms) {
                 html += createSection('🪖 ' + t('combat_outfit'), `
                    ${item(t('1st_outfit_number') + '/' + t('1st_outfit_size'), s.uniforms.combat.outfit1)}
                    ${item(t('2nd_outfit_number') + '/' + t('2nd_outfit_size'), s.uniforms.combat.outfit2)}
                    ${item(t('combat_shoe_size'), s.uniforms.combat.shoe)}
                `);

                 html += createSection('👔 ' + t('parade_uniform'), `
                    ${item(t('summer_jacket_size'), s.uniforms.parade.summer_jacket)}
                    ${item(t('winter_jacket_size'), s.uniforms.parade.winter_jacket)}
                    ${item(t('summer_trousers_size'), s.uniforms.parade.summer_trousers)}
                    ${item(t('winter_trousers_size'), s.uniforms.parade.winter_trousers)}
                    ${item(t('summer_shirt_size'), s.uniforms.parade.summer_shirt)}
                    ${item(t('winter_shirt_size'), s.uniforms.parade.winter_shirt)}
                    ${item(t('summer_hat_size'), s.uniforms.parade.summer_hat)}
                    ${item(t('winter_hat_size'), s.uniforms.parade.winter_hat)}
                    ${s.sex === 'Female' ? item(t('summer_skirt_size'), s.uniforms.parade.summer_skirt) : ''}
                    ${s.sex === 'Female' ? item(t('winter_skirt_size'), s.uniforms.parade.winter_skirt) : ''}
                `);
            }

            // 6. Documents & Misc
            html += createSection('📄 ' + t('step_other_details'), `
                ${item(t('label_id_card_num'), s.id_card_num)}
                ${item(t('label_birth_cert_num'), s.birth_cert_num)}
                ${item(t('label_school_sub_card'), s.school_sub_card)}
                ${item(t('label_laptop_serial'), s.laptop_serial)}
                ${item(t('label_postal_account'), s.postal_account)}
                ${item(t('label_mil_necklace'), s.mil_necklace)}
            `);
            
            // 7. Emergency Contact
            if (s.emergency_contact) {
                html += createSection('🚨 ' + t('step_emergency_contact'), `
                    ${item(t('contact_name_en'), (s.emergency_contact.first_name_en || '') + ' ' + (s.emergency_contact.last_name_en || ''))}
                    ${item(t('contact_name_ar'), (s.emergency_contact.first_name_ar || '') + ' ' + (s.emergency_contact.last_name_ar || ''))}
                    ${item(t('label_relation_en'), s.emergency_contact.relation_en)}
                    ${item(t('label_relation_ar'), s.emergency_contact.relation_ar)}
                    ${item(t('label_contact_phone'), s.emergency_contact.phone)}
                    ${s.emergency_contact.consulate_number ? item(t('label_consulate_number'), s.emergency_contact.consulate_number) : ''}
                    <div style="grid-column: span 2;">
                        ${item(t('contact_address'), s.emergency_contact.address)}
                    </div>
                `);
            }

            document.getElementById('fullInfoContainer').innerHTML = html;
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('resultsSection').classList.remove('active');
            document.getElementById('suggestionsContainer').style.display = 'none';
            selectedStudent = null;
            const dateFilterSection = document.getElementById('dateFilterSection');
            if (dateFilterSection) {
                dateFilterSection.style.display = 'none';
            }
            initializeDates();
        }

        document.getElementById('startDate').addEventListener('change', scheduleAutoSearch);
        document.getElementById('endDate').addEventListener('change', scheduleAutoSearch);

        // Initialize
        window.addEventListener('load', function() {
            initializeDates();
            fetchAllStudents();
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchInput') && !e.target.closest('#suggestionsContainer')) {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });


    </script>
</body>
</html>
