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
    $stmt = $conn->prepare("SELECT ADMINISTRATOR_FIRST_NAME_EN, ADMINISTRATOR_LAST_NAME_EN, ADMINISTRATOR_FIRST_NAME_AR, ADMINISTRATOR_LAST_NAME_AR FROM administrator WHERE USER_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($LANG === 'ar' && !empty($row['ADMINISTRATOR_FIRST_NAME_AR'])) {
            $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME_AR']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME_AR']);
        } else {
            $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME_EN']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME_EN']);
        }
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('student_records'); ?> - <?php echo t('app_name'); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .admin-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* --- Modern Search Bar --- */
        .search-container-wrapper {
            position: sticky;
            top: 1rem;
            z-index: 40;
            margin-bottom: 2.5rem;
        }

        .search-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1rem;
            padding: 0.75rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        [data-theme='dark'] .search-glass {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-icon-inline {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }

        [dir="rtl"] .search-icon-inline {
            left: auto;
            right: 1rem;
        }

        input[type="text"]#searchInput {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: none;
            background: transparent;
            font-size: 1.1rem;
            color: var(--text-primary);
            outline: none;
        }

        [dir="rtl"] input[type="text"]#searchInput {
            padding: 0.75rem 2.75rem 0.75rem 1rem;
        }

        .search-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* --- Cards & Sections --- */
        .glass-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        /* --- Student Hero Header --- */
        .student-hero {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }

        .student-profile-img-container {
            position: relative;
            width: 160px;
            height: 160px;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 4px solid var(--surface-color);
        }

        .student-profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .loader {
            display: none;
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .loader.active {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .spinner {
            border: 4px solid var(--border-color);
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        .student-basic-info h1 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-primary);
        }

        .student-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            background: var(--background-color);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .badge-primary {
            background: rgba(99, 102, 241, 0.1);
            color: #4f46e5;
            border-color: rgba(99, 102, 241, 0.2);
        }

        /* --- Info Grid (Less Labels, More Info) --- */
        .info-minimal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-cell {
            padding: 1rem;
            border-radius: 0.75rem;
            background: rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        [data-theme='dark'] .info-cell {
            background: rgba(255, 255, 255, 0.03);
        }

        .info-cell:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        .cell-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .cell-value {
            display: block;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* --- Records Styling --- */
        .records-timeline {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .record-card {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1.5rem;
            padding: 1.25rem;
            background: var(--background-color);
            border-radius: 0.75rem;
            border-inline-start: 4px solid #ef4444; /* Default for absences */
            transition: transform 0.2s ease;
        }

        .record-card.observation {
            border-inline-start-color: #3b82f6;
        }

        .record-card:hover {
            transform: translateX(4px);
        }

        [dir="rtl"] .record-card:hover {
            transform: translateX(-4px);
        }

        .record-time-box {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            padding-inline-end: 1rem;
            border-inline-end: 1px solid var(--border-color);
        }

        .record-day {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .record-month {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            font-weight: 700;
        }

        .record-content h4 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .record-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .record-note-bubble {
            background: var(--surface-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem 1rem 1rem 1rem;
            font-size: 0.9rem;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            position: relative;
        }

        [dir="rtl"] .record-note-bubble {
            border-radius: 1rem 0.5rem 1rem 1rem;
        }

        /* --- Buttons --- */
        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            gap: 0.5rem;
        }

        .btn-modern-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-modern-secondary {
            background: var(--surface-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .btn-modern:active {
            transform: translateY(0);
        }

        /* --- Date Filters Glass --- */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .filter-item label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: block;
        }

        .filter-item input[type="date"] {
            border: 1px solid var(--border-color);
            padding: 0.6rem 1rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-primary);
            font-weight: 600;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
            cursor: pointer;
            min-width: 160px;
        }

        [data-theme='dark'] .filter-item input[type="date"] {
            background: rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .filter-item input[type="date"]:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: var(--surface-color);
        }

        /* --- Suggestions (Autocomplete) --- */
        #suggestionsContainer {
            margin-top: -1.5rem;
            margin-bottom: 2rem;
            background: var(--surface-color);
            border-radius: 0 0 1rem 1rem;
            border: 1px solid var(--border-color);
            border-top: none;
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .student-grid-minimal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .student-mini-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid transparent;
        }

        .student-mini-card:hover {
            background: rgba(99, 102, 241, 0.05);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .mini-avatar {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .mini-info-name {
            font-weight: 600;
            font-size: 0.95rem;
            display: block;
        }

        .mini-info-serial {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .student-hero {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
            }
            .record-card {
                grid-template-columns: 1fr;
            }
            .record-time-box {
                flex-direction: row;
                justify-content: flex-start;
                border-inline-end: none;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 0.5rem;
                gap: 0.5rem;
            }
        }

        .tabs-container { display: flex; gap: 2rem; align-items: flex-start; height: calc(100vh - 220px); min-height: 400px; }
        .tabs-list { display: flex; flex-direction: column; gap: 0.5rem; width: 280px; flex-shrink: 0; position: sticky; top: 2rem; max-height: calc(100vh - 240px); overflow-y: auto; overflow-x: hidden; }
        .tab-button { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.3s ease; text-align: left; font-weight: 700; color: var(--text-secondary); width: 100%; font-family: inherit; font-size: 0.95rem; }
        .tab-button:hover { background: rgba(111, 66, 193, 0.05); border-color: var(--primary-color); color: var(--primary-color); transform: translateX(5px); }
        .tab-button.active { background: var(--primary-color); color: white; border-color: var(--primary-color); box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3); }
        .tab-button::after { content: '▸'; opacity: 0.5; transition: transform 0.3s ease; }
        .tab-button.active::after { transform: rotate(90deg); opacity: 1; }
        .tab-content-container { flex-grow: 1; min-width: 0; }
        .tab-panel { display: none; animation: fadeIn 0.4s ease; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md); overflow-y: auto; max-height: 100%; }
        .tab-panel.active { display: block; }

        [dir="rtl"] .tab-button { text-align: right; }
        [dir="rtl"] .tab-button:hover { transform: translateX(-5px); }

        /* Collapsible sub-tabs */
        .tab-button.collapsible { position: relative; }
        .tab-button.collapsible::before {
            content: '▾';
            position: absolute;
            left: 0.75rem;
            transition: transform 0.3s ease;
            opacity: 0.6;
        }
        .tab-button.collapsible.expanded::before {
            transform: rotate(180deg);
        }
        [dir="rtl"] .tab-button.collapsible::before {
            left: auto;
            right: 0.75rem;
        }

        .sub-tabs-list {
            display: none;
            flex-direction: column;
            gap: 0.35rem;
            margin-top: 0.35rem;
            padding-left: 1rem;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .sub-tabs-list.expanded {
            display: flex;
            max-height: 500px;
            padding-bottom: 0.25rem;
        }

        .sub-tab-button {
            display: flex;
            align-items: center;
            padding: 0.65rem 1rem 0.65rem 1.5rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            width: 100%;
            font-family: inherit;
            font-size: 0.85rem;
        }
        .sub-tab-button:hover {
            background: rgba(111, 66, 193, 0.08);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .sub-tab-button.active {
            background: rgba(111, 66, 193, 0.15);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        [dir="rtl"] .sub-tab-button {
            text-align: right;
            padding-left: 1rem;
            padding-right: 1.5rem;
        }

        @media (max-width: 992px) {
            .tabs-container { flex-direction: column; }
            .tabs-list { width: 100%; position: static; flex-direction: row; overflow-x: auto; padding-bottom: 0.5rem; }
            .tab-button { white-space: nowrap; width: auto; }
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

            <!-- Modern Search Interface -->
            <div class="search-container-wrapper">
                <div class="search-glass">
                    <div class="search-input-wrapper">
                        <span class="search-icon-inline">🔍</span>
                        <input type="text" id="searchInput" placeholder="<?php echo t('student_search_placeholder'); ?>" autocomplete="off">
                    </div>
                    <div class="search-actions">
                        <button class="btn-modern btn-modern-secondary" onclick="clearSearch()">
                            <span>✕</span> <?php echo t('clear'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div id="suggestionsContainer" style="display: none;">
                <div class="student-grid-minimal" id="suggestionsList"></div>
            </div>

            <div class="loader" id="loader" style="display: none;">
                <div class="spinner"></div>
                <p><?php echo t('loading_student_records'); ?></p>
            </div>

            <div class="results-section" id="resultsSection">
                <div id="studentInfo" style="display: none;"></div>

                <div class="glass-card" id="dateFilterSection" style="display:none;">
                    <div class="filters-bar">
                        <div class="filter-item">
                            <label><?php echo t('start_date'); ?></label>
                            <input type="date" id="startDate">
                        </div>
                        <div class="filter-item">
                            <label><?php echo t('end_date'); ?></label>
                            <input type="date" id="endDate">
                        </div>
                        <div style="flex-grow: 1;"></div>
                        <button class="btn-modern btn-modern-secondary" id="btnGenerateReport" onclick="generateReport()" style="display:none;">
                            📄 <?php echo t('generate_report') ?: 'Generate Report'; ?>
                        </button>
                    </div>
                </div>

                <div class="tabs-container" id="recordsTabs" style="display:none;">
                    <div class="tabs-list" id="recordsTabsList">
                        <button type="button" class="tab-button active" data-tab-id="tab-absences" data-view="absences">⚠️ <?php echo t('absences'); ?></button>
                        <button type="button" class="tab-button" data-tab-id="tab-observations" data-view="observations">📝 <?php echo t('observations'); ?></button>
                        <button type="button" class="tab-button collapsible" data-tab-id="tab-fullInfo" data-view="fullInfo">📄 <?php echo t('btn_full_information'); ?></button>
                        <div class="sub-tabs-list" id="fullInfoSubTabs">
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-personal">👤 <?php echo t('step_personal_details'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-academic">🎓 <?php echo t('step_academic_info'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-family">👪 <?php echo t('step_family_info'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-addresses">📍 <?php echo t('step_addresses'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-uniforms">🪖 <?php echo t('uniforms'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-documents">📄 <?php echo t('step_other_details'); ?></button>
                            <button type="button" class="sub-tab-button" data-sub-tab="subtab-emergency">🚨 <?php echo t('step_emergency_contact'); ?></button>
                        </div>
                        <button type="button" class="tab-button" data-tab-id="tab-punishments" data-view="punishments">⚠️ <?php echo t('punishments') ?: 'Punishments'; ?></button>
                        <button type="button" class="tab-button" data-tab-id="tab-rewards" data-view="rewards">🌟 <?php echo t('rewards') ?: 'Rewards'; ?></button>
                    </div>

                    <div class="tab-content-container">
                        <div id="tab-absences" class="tab-panel active"><div id="absencesContainer"></div></div>
                        <div id="tab-observations" class="tab-panel"><div id="observationsContainer"></div></div>
                        <div id="tab-fullInfo" class="tab-panel">
                            <div id="fullInfoContainer">
                                <div id="subtab-personal" class="sub-tab-content"></div>
                                <div id="subtab-academic" class="sub-tab-content" style="display:none;"></div>
                                <div id="subtab-family" class="sub-tab-content" style="display:none;"></div>
                                <div id="subtab-addresses" class="sub-tab-content" style="display:none;"></div>
                                <div id="subtab-uniforms" class="sub-tab-content" style="display:none;"></div>
                                <div id="subtab-documents" class="sub-tab-content" style="display:none;"></div>
                                <div id="subtab-emergency" class="sub-tab-content" style="display:none;"></div>
                            </div>
                        </div>
                        <div id="tab-punishments" class="tab-panel"><div id="punishmentsContainer"></div></div>
                        <div id="tab-rewards" class="tab-panel"><div id="rewardsContainer"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
        const T = <?php echo json_encode($T); ?>;
        const t = (key) => T[key] || key;

        const isArabic = <?php echo $LANG === 'ar' ? 'true' : 'false'; ?>;

        let selectedStudent = null;
        let allStudents = [];
        let autoSearchDebounceTimer = null;
        let lastAutoSearchKey = null;

        const escapeHtmlAttr = (value) => {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        };

        const isImageFilename = (value) => typeof value === 'string' && /\.(jpe?g|png|gif|webp)$/i.test(value);
        const resolvePhotoUrl = (value) => {
            if (!value) return null;
            value = String(value).replace(/\\/g, '/').replace(/^\/+/, '');
            if (value.startsWith('data:') || value.startsWith('http')) return value;
            if (value.includes('/')) return value;
            if (isImageFilename(value)) return `resources/photos/students/${value}`;
            return `data:image/jpeg;base64,${value}`;
        };

        function formatDate(dateStr, type = 'full') {
            const date = new Date(dateStr);
            if (isNaN(date)) return dateStr;
            
            const options = type === 'day' ? { day: '2-digit' } : 
                          type === 'month' ? { month: 'short' } :
                          { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            
            return date.toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', options);
        }

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
                suggestionsList.innerHTML = matches.slice(0, 15).map(student => {
                    const photoUrl = student.photo ? resolvePhotoUrl(student.photo) : 'assets/placeholder-student.png';
                    
                    return `
                        <div class="student-mini-card" data-serial="${escapeHtmlAttr(student.serial_number)}" data-first="${escapeHtmlAttr(student.first_name)}" data-last="${escapeHtmlAttr(student.last_name)}">
                            <img class="mini-avatar" src="${photoUrl}" onerror="this.src='assets/placeholder-student.png';">
                            <div class="mini-info">
                                <span class="mini-info-name">${student.first_name} ${student.last_name}</span>
                                <span class="mini-info-serial">${student.serial_number}</span>
                            </div>
                        </div>
                    `;
                }).join('');
                document.getElementById('suggestionsContainer').style.display = 'block';
            } else {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });

        document.getElementById('suggestionsList').addEventListener('click', function(e) {
            const card = e.target.closest('.student-mini-card');
            if (!card) return;
            selectStudent(card.dataset.serial, card.dataset.first, card.dataset.last);
        });

        function selectStudent(serialNumber, firstName, lastName) {
            selectedStudent = { serialNumber, firstName, lastName };
            lastAutoSearchKey = null;
            document.getElementById('searchInput').value = firstName + ' ' + lastName;
            document.getElementById('suggestionsContainer').style.display = 'none';

            document.getElementById('dateFilterSection').style.display = 'block';
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
                    displayResults(data.student, data.absences, data.observations, data.punishments || [], data.rewards || []);
                    document.getElementById('resultsSection').classList.add('active');
                } else {
                    showError(data.message || t('msg_failed_load_records'));
                }
            } catch (error) {
                document.getElementById('loader').classList.remove('active');
                showError(t('msg_error_searching') + error.message);
            }
        }

        function switchTab(evt, tabId, view) {
            const tabsRoot = document.getElementById('recordsTabs');
            if (!tabsRoot) return;

            const tabPanels = tabsRoot.querySelectorAll('.tab-panel');
            const tabButtons = tabsRoot.querySelectorAll('.tab-button');

            tabPanels.forEach(panel => panel.classList.remove('active'));
            tabButtons.forEach(btn => btn.classList.remove('active'));

            const targetPanel = document.getElementById(tabId);
            if (targetPanel) targetPanel.classList.add('active');

            let activeButton = null;
            if (evt && evt.currentTarget) {
                activeButton = evt.currentTarget;
            } else {
                activeButton = tabsRoot.querySelector(`.tab-button[data-tab-id="${tabId}"]`);
            }
            if (activeButton) {
                activeButton.classList.add('active');
                if (window.innerWidth <= 992) {
                    activeButton.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }

            if (view) {
                switchView(view);
            }
        }

        function toggleCollapsibleTab(btn) {
            if (!btn || !btn.classList.contains('collapsible')) return;
            const subTabsList = btn.nextElementSibling;
            if (!subTabsList || !subTabsList.classList.contains('sub-tabs-list')) return;

            const isExpanded = btn.classList.contains('expanded');

            if (isExpanded) {
                btn.classList.remove('expanded');
                subTabsList.classList.remove('expanded');
            } else {
                btn.classList.add('expanded');
                subTabsList.classList.add('expanded');
            }
        }

        function switchSubTab(subTabId) {
            const fullInfoContainer = document.getElementById('fullInfoContainer');
            if (!fullInfoContainer) return;

            const subContents = fullInfoContainer.querySelectorAll('.sub-tab-content');
            const subButtons = document.querySelectorAll('.sub-tab-button');

            subContents.forEach(content => content.style.display = 'none');
            subButtons.forEach(btn => btn.classList.remove('active'));

            const targetContent = document.getElementById(subTabId);
            if (targetContent) targetContent.style.display = 'block';

            const activeBtn = document.querySelector(`.sub-tab-button[data-sub-tab="${subTabId}"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }

        function switchView(view) {
            const absencesContainer = document.getElementById('absencesContainer');
            const observationsContainer = document.getElementById('observationsContainer');
            const fullInfoContainer = document.getElementById('fullInfoContainer');
            const punishmentsContainer = document.getElementById('punishmentsContainer');
            const rewardsContainer = document.getElementById('rewardsContainer');

            if (absencesContainer) absencesContainer.style.display = view === 'absences' ? 'block' : 'none';
            if (observationsContainer) observationsContainer.style.display = view === 'observations' ? 'block' : 'none';
            if (fullInfoContainer) fullInfoContainer.style.display = view === 'fullInfo' ? 'block' : 'none';
            if (punishmentsContainer) punishmentsContainer.style.display = view === 'punishments' ? 'block' : 'none';
            if (rewardsContainer) rewardsContainer.style.display = view === 'rewards' ? 'block' : 'none';
        }

        function displayResults(student, absences, observations, punishments, rewards) {
            document.getElementById('recordsTabs').style.display = 'flex';
            document.getElementById('btnGenerateReport').style.display = 'inline-flex';
            switchTab(null, 'tab-absences', 'absences');

            const studentPhotoUrl = student.photo ? resolvePhotoUrl(student.photo) : 'assets/placeholder-student.png';
            
            const studentInfoHtml = `
                <div class="glass-card">
                    <div class="student-hero">
                        <div class="student-profile-img-container">
                            <img src="${studentPhotoUrl}" class="student-profile-img" onerror="this.src='assets/placeholder-student.png';">
                        </div>
                        <div class="student-basic-info">
                            <div class="student-badges">
                                <span class="badge badge-primary">${student.section_name}</span>
                                <span class="badge">${student.serial_number}</span>
                                <span class="badge">${student.category_name}</span>
                            </div>
                            <h1>${student.first_name} ${student.last_name}</h1>
                            <div class="info-minimal-grid">
                                <div class="info-cell">
                                    <span class="cell-label">${t('grade_level')}</span>
                                    <span class="cell-value">${student.grade || 'N/A'}</span>
                                </div>
                                <div class="info-cell">
                                    <span class="cell-label">${t('label_birth_date')}</span>
                                    <span class="cell-value">${student.birth_date || 'N/A'}</span>
                                </div>
                                <div class="info-cell">
                                    <span class="cell-label">${t('label_personal_address')}</span>
                                    <span class="cell-value">${student.personal_address || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('studentInfo').innerHTML = studentInfoHtml;
            document.getElementById('studentInfo').style.display = 'block';

            // --- Render Absences Timeline ---
            let absencesHtml = '<div class="records-timeline">';
            if (absences.length === 0) {
                absencesHtml += `<div class="glass-card" style="text-align:center; color:var(--text-secondary); padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🍃</div>
                    <p>${t('no_absences_period') || t('no_records_found')}</p>
                </div>`;
            } else {
                absences.sort((a, b) => new Date(b.absence_date_and_time) - new Date(a.absence_date_and_time)).forEach(record => {
                    const dateObj = new Date(record.absence_date_and_time);
                    const day = dateObj.getDate();
                    const month = dateObj.toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', { month: 'short' });
                    
                    absencesHtml += `
                        <div class="record-card">
                            <div class="record-time-box">
                                <span class="record-day">${day}</span>
                                <span class="record-month">${month}</span>
                            </div>
                            <div class="record-content">
                                <div class="record-meta">
                                    ⚠️ ${t('absence')} 
                                    • ${formatDate(record.absence_date_and_time, 'time')} 
                                </div>
                                <h4>${record.absence_motif || t('not_specified')}</h4>
                                ${record.absence_observation ? `
                                    <div class="record-note-bubble">
                                        ${record.absence_observation}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
            }
            absencesHtml += '</div>';
            document.getElementById('absencesContainer').innerHTML = absencesHtml;

            // --- Render Observations Timeline ---
            let observationsHtml = '<div class="records-timeline">';
            if (observations.length === 0) {
                observationsHtml += `<div class="glass-card" style="text-align:center; color:var(--text-secondary); padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🍃</div>
                    <p>${t('no_observations_period') || t('no_records_found')}</p>
                </div>`;
            } else {
                observations.sort((a, b) => new Date(b.observation_date_and_time) - new Date(a.observation_date_and_time)).forEach(record => {
                    const dateObj = new Date(record.observation_date_and_time);
                    const day = dateObj.getDate();
                    const month = dateObj.toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', { month: 'short' });
                    
                    observationsHtml += `
                        <div class="record-card observation">
                            <div class="record-time-box">
                                <span class="record-day">${day}</span>
                                <span class="record-month">${month}</span>
                            </div>
                            <div class="record-content">
                                <div class="record-meta">
                                    📝 ${t('observation')} 
                                    • ${formatDate(record.observation_date_and_time, 'time')} 
                                    ${record.teacher_name ? '• ' + record.teacher_name : ''}
                                </div>
                                <h4>${record.observation_motif || t('not_specified')}</h4>
                                ${record.observation_note ? `
                                    <div class="record-note-bubble">
                                        ${record.observation_note}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
            }
            observationsHtml += '</div>';
            document.getElementById('observationsContainer').innerHTML = observationsHtml;

            // --- Punishments Tab Content (full history within date range) ---
            let punHtml = `
                <div class="glass-card">
                    <h3 style="margin-top:0; margin-bottom:0.75rem;">⚠️ ${t('punishments') || 'Punishments'}</h3>
            `;
            if (punishments.length === 0) {
                punHtml += `<div style="color:var(--text-secondary); font-size:0.9rem;">${t('no_punishments_period') || t('no_records_found')}</div>`;
            } else {
                punHtml += `<div class="records-timeline">`;
                punishments.forEach(p => {
                    const dateObj = new Date(p.punishment_date_and_time);
                    const day = dateObj.getDate();
                    const month = dateObj.toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', { month: 'short' });
                    punHtml += `
                        <div class="record-card">
                            <div class="record-time-box">
                                <span class="record-day">${day}</span>
                                <span class="record-month">${month}</span>
                            </div>
                            <div class="record-content">
                                <div class="record-meta">
                                    ⚠️ ${t('punishment') || 'Punishment'} • ${formatDate(p.punishment_date_and_time, 'time')} • ${p.secretary_name || ''}
                                </div>
                                <h4>${p.punishment_label || t('not_specified')}</h4>
                                ${p.punishment_note ? `
                                    <div class="record-note-bubble">
                                        ${p.punishment_note}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                punHtml += `</div>`;
            }
            punHtml += `</div>`;
            document.getElementById('punishmentsContainer').innerHTML = punHtml;

            // --- Rewards Tab Content (full history within date range) ---
            let rewHtml = `
                <div class="glass-card">
                    <h3 style="margin-top:0; margin-bottom:0.75rem;">🌟 ${t('rewards') || 'Rewards'}</h3>
            `;
            if (rewards.length === 0) {
                rewHtml += `<div style="color:var(--text-secondary); font-size:0.9rem;">${t('no_rewards_period') || t('no_records_found')}</div>`;
            } else {
                rewHtml += `<div class="records-timeline">`;
                rewards.forEach(r => {
                    const dateObj = new Date(r.reward_date_and_time);
                    const day = dateObj.getDate();
                    const month = dateObj.toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', { month: 'short' });
                    rewHtml += `
                        <div class="record-card observation">
                            <div class="record-time-box">
                                <span class="record-day">${day}</span>
                                <span class="record-month">${month}</span>
                            </div>
                            <div class="record-content">
                                <div class="record-meta">
                                    🌟 ${t('reward') || 'Reward'} • ${formatDate(r.reward_date_and_time, 'time')} • ${r.secretary_name || ''}
                                </div>
                                <h4>${r.reward_label || t('not_specified')}</h4>
                                ${r.reward_note ? `
                                    <div class="record-note-bubble">
                                        ${r.reward_note}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                rewHtml += `</div>`;
            }
            rewHtml += `</div>`;
            document.getElementById('rewardsContainer').innerHTML = rewHtml;

            renderFullInfo(student);
        }

        function renderFullInfo(s) {
            const createSection = (title, content) => `
                <div class="glass-card">
                    <h3 style="margin-top:0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; color: var(--text-primary);">${title}</h3>
                    <div class="info-minimal-grid">
                        ${content}
                    </div>
                </div>
            `;
            
            const cell = (label, value) => `
                <div class="info-cell">
                    <span class="cell-label">${label}</span>
                    <span class="cell-value">${value || t('not_available') || 'N/A'}</span>
                </div>
            `;

            // 1. Personal Details
            document.getElementById('subtab-personal').innerHTML = createSection('👤 ' + t('step_personal_details'), `
                ${cell(t('label_first_name_en'), s.first_name)}
                ${cell(t('label_last_name_en'), s.last_name)}
                ${cell(t('label_first_name_ar'), s.first_name_ar)}
                ${cell(t('label_last_name_ar'), s.last_name_ar)}
                ${cell(t('label_sex'), t(s.sex.toLowerCase()))}
                ${cell(t('label_birth_date'), s.birth_date)}
                ${cell(t('label_blood_type'), s.blood_type)}
                ${cell(t('label_phone'), s.personal_phone)}
                ${cell(t('label_height'), s.height_cm + ' cm')}
                ${cell(t('label_weight'), s.weight_kg + ' kg')}
                ${cell(t('label_is_foreign'), t(s.is_foreign.toLowerCase()))}
            `);

            // 2. Academic
            document.getElementById('subtab-academic').innerHTML = createSection('🎓 ' + t('step_academic_info'), `
                ${cell(t('label_speciality'), s.speciality)}
                ${cell(t('label_academic_level'), s.academic_level)}
                ${cell(t('label_academic_average'), s.academic_average)}
                ${cell(t('label_bac_number'), s.bac_number)}
                ${cell(t('category'), s.category_name)}
                ${cell(t('grade_level'), s.grade)}
                ${cell(t('army'), s.army_name)}
            `);

            // 3. Parents & Family
            document.getElementById('subtab-family').innerHTML = createSection('👪 ' + t('step_family_info'), `
                ${cell(t('label_father_name_en'), s.father_name_en)}
                ${cell(t('label_father_name_ar'), s.father_name_ar)}
                ${cell(t('label_father_prof_en'), s.father_profession)}
                ${cell(t('label_father_prof_ar'), s.father_profession_ar)}
                
                ${cell(t('label_mother_name_en'), s.mother_name_en)}
                ${cell(t('label_mother_name_ar'), s.mother_name_ar)}
                ${cell(t('label_mother_prof_en'), s.mother_profession)}
                ${cell(t('label_mother_prof_ar'), s.mother_profession_ar)}
                
                ${cell(t('label_orphan_status'), t('orphan_' + s.orphans_status.toLowerCase()))}
                ${cell(t('label_parents_situation'), t(s.parents_situation.toLowerCase()))}
                
                ${cell(t('label_siblings_count'), s.siblings_count)}
                ${cell(t('label_sisters_count'), s.sisters_count)}
                ${cell(t('label_order_among_siblings'), s.order_among_siblings)}
            `);

            // 4. Addresses
            document.getElementById('subtab-addresses').innerHTML = createSection('📍 ' + t('step_addresses'), `
                <div class="info-cell" style="grid-column: span 2;">
                    <span class="cell-label">${t('label_birth_place_address')}</span>
                    <span class="cell-value">${s.birth_place || 'N/A'}</span>
                </div>
                <div class="info-cell" style="grid-column: span 2;">
                    <span class="cell-label">${t('label_personal_address')}</span>
                    <span class="cell-value">${s.personal_address || 'N/A'}</span>
                </div>
            `);
            
            // 5. Uniforms
            let uniformsHtml = '';
            if (s.uniforms) {
                 uniformsHtml += createSection('🪖 ' + t('combat_outfit'), `
                    ${cell(t('1st_outfit_number') + '/' + t('1st_outfit_size'), s.uniforms.combat.outfit1)}
                    ${cell(t('2nd_outfit_number') + '/' + t('2nd_outfit_size'), s.uniforms.combat.outfit2)}
                    ${cell(t('combat_shoe_size'), s.uniforms.combat.shoe)}
                `);

                 uniformsHtml += createSection('👔 ' + t('parade_uniform'), `
                    ${cell(t('summer_jacket_size'), s.uniforms.parade.summer_jacket)}
                    ${cell(t('winter_jacket_size'), s.uniforms.parade.winter_jacket)}
                    ${cell(t('summer_trousers_size'), s.uniforms.parade.summer_trousers)}
                    ${cell(t('winter_trousers_size'), s.uniforms.parade.winter_trousers)}
                    ${cell(t('summer_shirt_size'), s.uniforms.parade.summer_shirt)}
                    ${cell(t('winter_shirt_size'), s.uniforms.parade.winter_shirt)}
                    ${cell(t('summer_hat_size'), s.uniforms.parade.summer_hat)}
                    ${cell(t('winter_hat_size'), s.uniforms.parade.winter_hat)}
                    ${s.sex === 'Female' ? cell(t('summer_skirt_size'), s.uniforms.parade.summer_skirt) : ''}
                    ${s.sex === 'Female' ? cell(t('winter_skirt_size'), s.uniforms.parade.winter_skirt) : ''}
                `);
            } else {
                uniformsHtml = `<div class="glass-card" style="text-align:center; color:var(--text-secondary); padding: 2rem;">${t('not_available') || 'N/A'}</div>`;
            }
            document.getElementById('subtab-uniforms').innerHTML = uniformsHtml;

            // 6. Documents & Misc
            document.getElementById('subtab-documents').innerHTML = createSection('📄 ' + t('step_other_details'), `
                ${cell(t('label_id_card_num'), s.id_card_num)}
                ${cell(t('label_birth_cert_num'), s.birth_cert_num)}
                ${cell(t('label_school_sub_card'), s.school_sub_card)}
                ${cell(t('label_laptop_serial'), s.laptop_serial)}
                ${cell(t('label_postal_account'), s.postal_account)}
                ${cell(t('label_mil_necklace'), s.mil_necklace)}
            `);

            // 7. Emergency Contact
            let emergencyHtml = '';
            if (s.emergency_contact) {
                emergencyHtml = createSection('🚨 ' + t('step_emergency_contact'), `
                    ${cell(t('contact_name_en'), (s.emergency_contact.first_name_en || '') + ' ' + (s.emergency_contact.last_name_en || ''))}
                    ${cell(t('contact_name_ar'), (s.emergency_contact.first_name_ar || '') + ' ' + (s.emergency_contact.last_name_ar || ''))}
                    ${cell(isArabic ? t('label_relation_ar') : t('label_relation_en'), isArabic ? s.emergency_contact.relation_ar : s.emergency_contact.relation_en)}
                    ${cell(t('label_contact_phone'), s.emergency_contact.phone)}
                    ${s.emergency_contact.consulate_number ? cell(t('label_consulate_number'), s.emergency_contact.consulate_number) : ''}
                    <div class="info-cell" style="grid-column: span 2;">
                        <span class="cell-label">${t('contact_address')}</span>
                        <span class="cell-value">${s.emergency_contact.address || 'N/A'}</span>
                    </div>
                `);
            } else {
                emergencyHtml = `<div class="glass-card" style="text-align:center; color:var(--text-secondary); padding: 2rem;">${t('not_available') || 'N/A'}</div>`;
            }
            document.getElementById('subtab-emergency').innerHTML = emergencyHtml;

            // Set first sub-tab as active
            switchSubTab('subtab-personal');
        }

        function generateReport() {
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

            // Open the report in a new window/tab with cache-busting timestamp
            const timestamp = Date.now();
            const url = `generate_report_tcpdf.php?serial_number=${encodeURIComponent(selectedStudent.serialNumber)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}&_t=${timestamp}`;
            window.open(url, '_blank');
        }

        function clearSearch() {
            // Clear inputs
            document.getElementById('searchInput').value = '';
            
            // Hide and clear UI sections
            document.getElementById('resultsSection').classList.remove('active');
            document.getElementById('suggestionsContainer').style.display = 'none';
            document.getElementById('studentInfo').style.display = 'none';
            document.getElementById('studentInfo').innerHTML = '';
            document.getElementById('absencesContainer').innerHTML = '';
            document.getElementById('observationsContainer').innerHTML = '';
            document.getElementById('fullInfoContainer').innerHTML = '';
            document.getElementById('recordsTabs').style.display = 'none';
            document.getElementById('dateFilterSection').style.display = 'none';
            document.getElementById('btnGenerateReport').style.display = 'none';
            
            // Reset state
            selectedStudent = null;
            lastAutoSearchKey = null;
            
            // Re-initialize dates but don't trigger search
            initializeDates();
        }

        document.getElementById('startDate').addEventListener('change', scheduleAutoSearch);
        document.getElementById('endDate').addEventListener('change', scheduleAutoSearch);

        window.addEventListener('load', function() {
            initializeDates();
            fetchAllStudents();

            const tabsList = document.getElementById('recordsTabsList');
            if (tabsList) {
                tabsList.addEventListener('click', function(e) {
                    const btn = e.target.closest('.tab-button');
                    if (btn) {
                        // Handle collapsible tab click
                        if (btn.classList.contains('collapsible')) {
                            toggleCollapsibleTab(btn);
                        }
                        const tabId = btn.getAttribute('data-tab-id');
                        const view = btn.getAttribute('data-view');
                        if (tabId && view) {
                            switchTab({ currentTarget: btn }, tabId, view);
                        }
                        return;
                    }

                    // Handle sub-tab click
                    const subBtn = e.target.closest('.sub-tab-button');
                    if (subBtn) {
                        const subTabId = subBtn.getAttribute('data-sub-tab');
                        if (subTabId) {
                            switchSubTab(subTabId);
                        }
                    }
                });
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchInput') && !e.target.closest('#suggestionsContainer')) {
                document.getElementById('suggestionsContainer').style.display = 'none';
            }
        });
    </script>
</body>
</html>
