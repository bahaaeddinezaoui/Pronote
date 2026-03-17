<?php
// secretary_rewards_student.php - Secretary assigns rewards to students
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'] ?? '';
if ($role !== 'Secretary') {
    header("Location: index.php");
    exit;
}

// Database connection
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get secretary data
$user_id = (int)$_SESSION['user_id'];
$secretary_name = "Secretary";
$secretary_id = null;

$stmt = $conn->prepare("SELECT SECRETARY_ID, SECRETARY_FIRST_NAME_EN, SECRETARY_LAST_NAME_EN, SECRETARY_FIRST_NAME_AR, SECRETARY_LAST_NAME_AR FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $secretary_id = (int)$row['SECRETARY_ID'];
    if ($LANG === 'ar' && !empty($row['SECRETARY_FIRST_NAME_AR'])) {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_AR'] . ' ' . $row['SECRETARY_LAST_NAME_AR']);
    } else {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_EN'] . ' ' . $row['SECRETARY_LAST_NAME_EN']);
    }
}
$stmt->close();

// AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!$secretary_id) {
        echo json_encode(['success' => false, 'message' => 'Secretary record not found.']);
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'get_history') {
        $serial = trim($_POST['serial_number'] ?? '');
        if ($serial === '') {
            echo json_encode(['success' => false, 'message' => 'Missing serial number.']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT 
                srs.REWARD_ID,
                srs.REWARD_SUGGESTED_AT,
                srs.REWARD_START_DATE,
                srs.REWARD_END_DATE,
                srs.REWARD_NOTE,
                rt.REWARD_LABEL_EN,
                rt.REWARD_LABEL_AR,
                rt.REWARD_DURATION,
                sec.SECRETARY_FIRST_NAME_EN,
                sec.SECRETARY_LAST_NAME_EN,
                sec.SECRETARY_FIRST_NAME_AR,
                sec.SECRETARY_LAST_NAME_AR
            FROM secretary_rewards_student srs
            JOIN reward_type rt ON srs.REWARD_TYPE_ID = rt.REWARD_TYPE_ID
            JOIN secretary sec ON srs.SECRETARY_ID = sec.SECRETARY_ID
            WHERE srs.STUDENT_SERIAL_NUMBER = ?
            ORDER BY srs.REWARD_SUGGESTED_AT DESC
            LIMIT 50
        ");
        $stmt->bind_param("s", $serial);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'history' => $rows]);
        exit;
    }

    if ($action === 'add_reward') {
        $serial = trim($_POST['serial_number'] ?? '');
        $type_id = isset($_POST['reward_type_id']) ? (int)$_POST['reward_type_id'] : 0;
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($serial === '' || $type_id <= 0 || $start_date === '' || $end_date === '') {
            echo json_encode(['success' => false, 'message' => t('error_missing_fields') ?: 'Missing required fields.']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO secretary_rewards_student
                (STUDENT_SERIAL_NUMBER, SECRETARY_ID, REWARD_TYPE_ID, REWARD_SUGGESTED_AT, REWARD_START_DATE, REWARD_END_DATE, REWARD_NOTE)
            VALUES (?, ?, ?, NOW(), ?, ?, ?)
        ");
        $stmt->bind_param("siissss", $serial, $secretary_id, $type_id, $start_date, $end_date, $note);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => t('reward_added_success') ?: 'Reward added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => t('error_saving_reward') ?: 'Failed to add reward.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'add_reward_bulk') {
        $serials_raw = (string)($_POST['serial_numbers'] ?? '');
        $type_id = isset($_POST['reward_type_id']) ? (int)$_POST['reward_type_id'] : 0;
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $note = trim($_POST['note'] ?? '');

        $serials = array_values(array_unique(array_filter(array_map('trim', explode(',', $serials_raw)), function ($v) {
            return $v !== '';
        }))));

        if (count($serials) < 1 || $type_id <= 0 || $start_date === '' || $end_date === '') {
            echo json_encode(['success' => false, 'message' => t('error_missing_fields') ?: 'Missing required fields.']);
            exit;
        }

        if (count($serials) > 200) {
            echo json_encode(['success' => false, 'message' => t('error_too_many_students') ?: 'Too many students selected.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO secretary_rewards_student (STUDENT_SERIAL_NUMBER, SECRETARY_ID, REWARD_TYPE_ID, REWARD_SUGGESTED_AT, REWARD_START_DATE, REWARD_END_DATE, REWARD_NOTE) VALUES (?, ?, ?, NOW(), ?, ?, ?)");

            $failed = [];
            foreach ($serials as $serial) {
                $stmt->bind_param('siissss', $serial, $secretary_id, $type_id, $start_date, $end_date, $note);
                if (!$stmt->execute()) {
                    $failed[] = $serial;
                }
            }
            $stmt->close();

            if (!empty($failed)) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => t('error_failed_some_students_reward') ?: 'Failed to add reward for some students.', 'failed' => $failed]);
                exit;
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => t('reward_added_success') ?: 'Reward added successfully.']);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => t('error_saving_reward') ?: 'Failed to add reward.']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => t('unknown_action') ?: 'Unknown action.']);
    exit;
}

// Load reward types for select
$reward_types = [];
$res = $conn->query("SELECT REWARD_TYPE_ID, REWARD_LABEL_EN, REWARD_LABEL_AR, REWARD_DURATION FROM reward_type ORDER BY REWARD_LABEL_EN");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $reward_types[] = $r;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('secretary_rewards_student_title') ?: 'Assign Rewards'; ?> - <?php echo t('app_name'); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .page-container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }
        .page-header h1 { margin: 0 0 0.25rem; font-size: 1.75rem; }
        .subtext { color: var(--text-secondary); margin-bottom: 1.5rem; }

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
            margin-bottom: 1.5rem;
        }
        [data-theme='dark'] .search-glass {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .search-input-wrapper { position: relative; flex-grow: 1; }
        .search-icon-inline {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }
        [dir="rtl"] .search-icon-inline { left: auto; right: 1rem; }
        #searchInput {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: none;
            background: transparent;
            font-size: 1rem;
            color: var(--text-primary);
            outline: none;
        }
        [dir="rtl"] #searchInput { padding: 0.75rem 2.75rem 0.75rem 1rem; }

        .student-grid-minimal { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; }
        .student-mini-card {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0.9rem; border-radius: 0.75rem;
            cursor: pointer; border: 1px solid transparent;
        }
        .student-mini-card:hover {
            background: rgba(34,197,94,0.05);
            border-color: rgba(34,197,94,0.3);
        }
        .mini-avatar { width: 40px; height: 40px; border-radius: 0.5rem; object-fit: cover; }
        .mini-info-name { font-weight: 600; font-size: 0.9rem; }
        .mini-info-serial { font-size: 0.75rem; color: var(--text-secondary); }

        .glass-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-top: 1rem;
        }

        .btn-modern {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.55rem 1.1rem;
            border-radius: 0.75rem;
            font-weight: 600; font-size: 0.9rem;
            cursor: pointer; border: none; gap: 0.4rem;
            transition: all 0.2s ease;
        }
        .btn-modern-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
        }
        .btn-modern-secondary {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .btn-modern:hover { transform: translateY(-1px); filter: brightness(1.05); }

        .history-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .history-table th, .history-table td {
            padding: 0.6rem 0.4rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        .history-table th { text-align: left; color: var(--text-secondary); font-weight: 600; }
        [dir="rtl"] .history-table th { text-align: right; }

        .badge-pill {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            background: rgba(34,197,94,0.1);
            color: #166534;
        }

        .empty-state { text-align: center; padding: 1.5rem 0.5rem; color: var(--text-secondary); }

        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.4);
            display: none; align-items: center; justify-content: center;
            z-index: 60;
        }
        .modal-backdrop.active { display: flex; }
        .modal-card {
            background: var(--surface-color);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            max-width: 480px;
            width: 100%;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .modal-title { font-weight: 700; font-size: 1.1rem; }
        .modal-close { cursor: pointer; border: none; background: transparent; font-size: 1.2rem; }

        .form-group { margin-bottom: 0.9rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-size: 0.85rem; color: var(--text-secondary); }
        .form-group select, .form-group textarea {
            width: 100%; padding: 0.5rem 0.6rem;
            border-radius: 0.5rem; border: 1px solid var(--border-color);
            background: var(--background-color); color: var(--text-primary);
            font-size: 0.9rem;
        }
        .form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }

        .status-bar { margin-top: 1rem; min-height: 1.5rem; font-size: 0.9rem; }
        .status-error { color: #b91c1c; }
        .status-success { color: #15803d; }

        .bulk-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: var(--text-primary);
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .bulk-chip button {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            color: var(--text-secondary);
            padding: 0;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-container">
            <div class="page-header">
                <h1><?php echo t('secretary_rewards_student_title') ?: 'Assign Rewards to Students'; ?></h1>
                <p class="subtext">
                    <?php echo t('secretary_rewards_student_subtitle') ?: 'Search for a student, review reward history, and add a new reward.'; ?>
                </p>
            </div>

            <div id="statusGlobal" class="status-bar"></div>

            <div class="search-glass">
                <div class="search-input-wrapper">
                    <span class="search-icon-inline">🔍</span>
                    <input type="text" id="searchInput"
                           placeholder="<?php echo t('student_search_placeholder') ?: 'Search by serial number or name...'; ?>"
                           autocomplete="off">
                </div>
                <button class="btn-modern btn-modern-secondary" id="btnClearSearch">✕ <?php echo t('clear'); ?></button>
            </div>

            <div id="suggestionsContainer" style="display:none; margin-bottom:1rem;">
                <div class="student-grid-minimal" id="suggestionsList"></div>
            </div>

            <div id="bulkBar" class="glass-card" style="display:none; margin-top:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-weight:700; margin-bottom:0.25rem;"><?php echo t('selected_students') ?: 'Selected students'; ?></div>
                        <div id="bulkChips" style="display:flex; flex-wrap:wrap; gap:0.5rem;"></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <button class="btn-modern btn-modern-primary" id="btnOpenBulkModal" type="button">🏆 <?php echo t('bulk_add_reward') ?: 'Bulk reward'; ?></button>
                        <button class="btn-modern btn-modern-secondary" id="btnClearBulk" type="button">✕ <?php echo t('clear'); ?></button>
                    </div>
                </div>
            </div>

            <div id="studentSection" style="display:none;">
                <div class="glass-card" id="studentInfo"></div>
                <div class="glass-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <h3 style="margin:0;"><?php echo t('recent_rewards') ?: 'Recent rewards'; ?></h3>
                        <button class="btn-modern btn-modern-primary" id="btnOpenModal">
                            🌟 <?php echo t('add_reward') ?: 'Add reward'; ?>
                        </button>
                    </div>
                    <div id="historyContainer" style="margin-top:0.75rem;"></div>
                </div>
            </div>

            <div class="modal-backdrop" id="rewardModal">
                <div class="modal-card">
                    <div class="modal-header">
                        <div class="modal-title"><?php echo t('add_reward') ?: 'Add reward'; ?></div>
                        <button class="modal-close" type="button" id="btnCloseModal">&times;</button>
                    </div>
                    <form id="rewardForm">
                        <div class="form-group">
                            <label for="rewardType"><?php echo t('reward_type') ?: 'Reward type'; ?></label>
                            <select id="rewardType" name="reward_type_id" required>
                                <option value=""><?php echo t('select_option') ?: 'Select...'; ?></option>
                                <?php foreach ($reward_types as $rt): ?>
                                    <option value="<?php echo (int)$rt['REWARD_TYPE_ID']; ?>" data-duration="<?php echo (int)$rt['REWARD_DURATION']; ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $LANG === 'ar' && !empty($rt['REWARD_LABEL_AR'])
                                                ? $rt['REWARD_LABEL_AR']
                                                : $rt['REWARD_LABEL_EN']
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rewardStartDate"><?php echo t('start_date') ?: 'Start date'; ?></label>
                            <input type="date" id="rewardStartDate" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="rewardEndDate"><?php echo t('end_date') ?: 'End date'; ?></label>
                            <input type="date" id="rewardEndDate" name="end_date" readonly>
                        </div>
                        <div class="form-group">
                            <label for="rewardNote"><?php echo t('note') ?: 'Note'; ?></label>
                            <textarea id="rewardNote" name="note" rows="3"
                                      placeholder="<?php echo t('optional_note') ?: 'Optional note...'; ?>"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-modern btn-modern-secondary" id="btnCancelModal">
                                <?php echo t('cancel'); ?>
                            </button>
                            <button type="submit" class="btn-modern btn-modern-primary">
                                <?php echo t('save'); ?>
                            </button>
                        </div>
                        <div id="statusModal" class="status-bar"></div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const T = <?php echo json_encode($T); ?>;
    const t = (key) => T[key] || key;

    let allStudents = [];
    let selectedStudent = null;
    let selectedStudents = [];
    let modalMode = 'single';

    function renderBulkBar() {
        const bar = document.getElementById('bulkBar');
        const chips = document.getElementById('bulkChips');
        if (!bar || !chips) return;

        if (!selectedStudents.length) {
            chips.innerHTML = '';
            bar.style.display = 'none';
            return;
        }

        chips.innerHTML = selectedStudents.map(s => {
            const label = `${escapeHtmlAttr(s.first)} ${escapeHtmlAttr(s.last)} (${escapeHtmlAttr(s.serial)})`;
            return `
                <span class="bulk-chip">
                    <span>${label}</span>
                    <button type="button" data-remove-serial="${escapeHtmlAttr(s.serial)}">×</button>
                </span>
            `;
        }).join('');

        bar.style.display = 'block';
    }

    function addSelectedStudent(serial, first, last) {
        const exists = selectedStudents.some(s => s.serial === serial);
        if (!exists) selectedStudents.push({ serial, first, last });
        renderBulkBar();
    }

    function removeSelectedStudent(serial) {
        selectedStudents = selectedStudents.filter(s => s.serial !== serial);
        if (selectedStudent && selectedStudent.serial === serial) {
            selectedStudent = null;
            document.getElementById('studentSection').style.display = 'none';
        }
        renderBulkBar();
    }

    function clearBulkSelection() {
        selectedStudents = [];
        selectedStudent = null;
        document.getElementById('studentSection').style.display = 'none';
        renderBulkBar();
    }

    function escapeHtmlAttr(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function showStatus(id, message, type) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = message || '';
        el.className = 'status-bar ' + (type === 'error' ? 'status-error' : type === 'success' ? 'status-success' : '');
    }

    async function fetchAllStudents() {
        try {
            const res = await fetch('get_all_students.php');
            const data = await res.json();
            if (data.success) {
                allStudents = data.students || [];
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderSuggestions(query) {
        const container = document.getElementById('suggestionsContainer');
        const list = document.getElementById('suggestionsList');
        if (!query || query.length < 2) {
            container.style.display = 'none';
            return;
        }
        const q = query.toLowerCase();
        const matches = allStudents.filter(s =>
            (s.serial_number || '').toLowerCase().includes(q) ||
            (s.first_name || '').toLowerCase().includes(q) ||
            (s.last_name || '').toLowerCase().includes(q) ||
            ((s.first_name + ' ' + s.last_name).toLowerCase().includes(q))
        );
        if (!matches.length) {
            container.style.display = 'none';
            return;
        }
        list.innerHTML = matches.slice(0, 20).map(s => `
            <div class="student-mini-card"
                 data-serial="${escapeHtmlAttr(s.serial_number)}"
                 data-first="${escapeHtmlAttr(s.first_name)}"
                 data-last="${escapeHtmlAttr(s.last_name)}">
                <img class="mini-avatar" src="${escapeHtmlAttr(s.photo || 'assets/placeholder-student.png')}"
                     onerror="this.src='assets/placeholder-student.png';">
                <div>
                    <div class="mini-info-name">${escapeHtmlAttr(s.first_name)} ${escapeHtmlAttr(s.last_name)}</div>
                    <div class="mini-info-serial">${escapeHtmlAttr(s.serial_number)}</div>
                </div>
            </div>
        `).join('');
        container.style.display = 'block';
    }

    function selectStudent(serial, first, last) {
        selectedStudent = {serial, first, last};
        addSelectedStudent(serial, first, last);
        document.getElementById('searchInput').value = first + ' ' + last + ' (' + serial + ')';
        document.getElementById('suggestionsContainer').style.display = 'none';
        document.getElementById('studentSection').style.display = 'block';
        document.getElementById('studentInfo').innerHTML = `
            <div>
                <h2 style="margin:0 0 0.25rem;">${escapeHtmlAttr(first)} ${escapeHtmlAttr(last)}</h2>
                <div style="color:var(--text-secondary); font-size:0.9rem;">
                    <?php echo t('student_serial_number') ?: 'Serial number'; ?>:
                    <strong>${escapeHtmlAttr(serial)}</strong>
                </div>
            </div>
        `;
        loadHistory();
    }

    async function loadHistory() {
        if (!selectedStudent) return;
        showStatus('statusGlobal', '', '');
        const container = document.getElementById('historyContainer');
        container.innerHTML = '<?php echo t('loading'); ?>...';

        const formData = new URLSearchParams();
        formData.set('action', 'get_history');
        formData.set('serial_number', selectedStudent.serial);

        try {
            const res = await fetch('secretary_rewards_student.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            });
            const data = await res.json();
            if (!data.success) {
                showStatus('statusGlobal', data.message || 'Failed to load history.', 'error');
                container.innerHTML = '<div class="empty-state">—</div>';
                return;
            }
            const rows = data.history || [];
            if (!rows.length) {
                container.innerHTML = '<div class="empty-state"><?php echo t('no_records_found') ?: 'No rewards yet.'; ?></div>';
                return;
            }
            container.innerHTML = `
                <table class="history-table">
                    <thead>
                        <tr>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo t('start_date') ?: 'Start'; ?></th>
                            <th><?php echo t('end_date') ?: 'End'; ?></th>
                            <th><?php echo t('reward_type'); ?></th>
                            <th><?php echo t('note'); ?></th>
                            <th><?php echo t('secretary'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(r => `
                            <tr>
                                <td>${escapeHtmlAttr(r.REWARD_SUGGESTED_AT || '')}</td>
                                <td>${escapeHtmlAttr(r.REWARD_START_DATE || '')}</td>
                                <td>${escapeHtmlAttr(r.REWARD_END_DATE || '')}</td>
                                <td><span class="badge-pill">${
                                    escapeHtmlAttr(<?php echo $LANG === 'ar' ? 'r.REWARD_LABEL_AR' : 'r.REWARD_LABEL_EN'; ?> || '')
                                }</span></td>
                                <td>${escapeHtmlAttr(r.REWARD_NOTE || '')}</td>
                                <td>${
                                    escapeHtmlAttr(r.SECRETARY_FIRST_NAME_EN || r.SECRETARY_FIRST_NAME_AR || '') + ' ' +
                                    escapeHtmlAttr(r.SECRETARY_LAST_NAME_EN || r.SECRETARY_LAST_NAME_AR || '')
                                }</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (e) {
            console.error(e);
            showStatus('statusGlobal', '<?php echo t('msg_error_loading') ?: 'Error loading history.'; ?>', 'error');
            container.innerHTML = '<div class="empty-state">—</div>';
        }
    }

    function openModal() {
        if (!selectedStudent) {
            showStatus('statusGlobal', '<?php echo t('msg_select_student_suggestion') ?: 'Please select a student first.'; ?>', 'error');
            return;
        }
        modalMode = 'single';
        document.getElementById('rewardModal').classList.add('active');
        showStatus('statusModal', '', '');
    }

    function openBulkModal() {
        if (!selectedStudents.length) {
            showStatus('statusGlobal', '<?php echo t('msg_select_student_suggestion') ?: 'Please select a student first.'; ?>', 'error');
            return;
        }
        modalMode = 'bulk';
        document.getElementById('rewardModal').classList.add('active');
        showStatus('statusModal', '', '');
    }
    function closeModal() {
        document.getElementById('rewardModal').classList.remove('active');
    }

    function calculateEndDate() {
        const typeSelect = document.getElementById('rewardType');
        const startDateInput = document.getElementById('rewardStartDate');
        const endDateInput = document.getElementById('rewardEndDate');
        
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const duration = parseInt(selectedOption?.dataset?.duration || 0);
        const startDate = startDateInput.value;

        if (startDate && duration > 0) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + duration);
            endDateInput.value = date.toISOString().split('T')[0];
        } else {
            endDateInput.value = '';
        }
    }

    document.getElementById('rewardType').addEventListener('change', calculateEndDate);
    document.getElementById('rewardStartDate').addEventListener('change', calculateEndDate);

    async function submitReward(e) {
        e.preventDefault();
        if (modalMode === 'single' && !selectedStudent) {
            showStatus('statusModal', '<?php echo t('msg_select_student_suggestion') ?: 'Please select a student first.'; ?>', 'error');
            return;
        }
        if (modalMode === 'bulk' && !selectedStudents.length) {
            showStatus('statusModal', '<?php echo t('msg_select_student_suggestion') ?: 'Please select a student first.'; ?>', 'error');
            return;
        }
        const typeId = document.getElementById('rewardType').value;
        const startDate = document.getElementById('rewardStartDate').value;
        const endDate = document.getElementById('rewardEndDate').value;
        if (!typeId) {
            showStatus('statusModal', '<?php echo t('msg_select_required') ?: 'Please select a reward type.'; ?>', 'error');
            return;
        }
        const note = document.getElementById('rewardNote').value.trim();

        const formData = new URLSearchParams();
        if (modalMode === 'bulk') {
            formData.set('action', 'add_reward_bulk');
            formData.set('serial_numbers', selectedStudents.map(s => s.serial).join(','));
        } else {
            formData.set('action', 'add_reward');
            formData.set('serial_number', selectedStudent.serial);
        }
        formData.set('reward_type_id', typeId);
        formData.set('start_date', startDate);
        formData.set('end_date', endDate);
        formData.set('note', note);

        showStatus('statusModal', '<?php echo t('saving'); ?>...', '');
        try {
            const res = await fetch('secretary_rewards_student.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            });
            const data = await res.json();
            if (!data.success) {
                showStatus('statusModal', data.message || t('error_saving_reward'), 'error');
                return;
            }
            showStatus('statusModal', data.message || t('saved'), 'success');
            showStatus('statusGlobal', data.message || t('reward_added_success'), 'success');
            document.getElementById('rewardForm').reset();
            await loadHistory();
            setTimeout(closeModal, 700);
        } catch (e2) {
            console.error(e2);
            showStatus('statusModal', t('error_saving_reward'), 'error');
        }
    }

    document.getElementById('searchInput').addEventListener('input', (e) => {
        renderSuggestions(e.target.value);
    });
    document.getElementById('btnClearSearch').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        document.getElementById('suggestionsContainer').style.display = 'none';
        document.getElementById('studentSection').style.display = 'none';
        selectedStudent = null;
        showStatus('statusGlobal', '', '');
    });
    document.getElementById('suggestionsList').addEventListener('click', (e) => {
        const card = e.target.closest('.student-mini-card');
        if (!card) return;
        selectStudent(card.dataset.serial, card.dataset.first, card.dataset.last);
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#searchInput') && !e.target.closest('#suggestionsContainer')) {
            document.getElementById('suggestionsContainer').style.display = 'none';
        }
    });

    document.getElementById('btnOpenModal').addEventListener('click', openModal);
    document.getElementById('btnOpenBulkModal').addEventListener('click', openBulkModal);
    document.getElementById('btnClearBulk').addEventListener('click', clearBulkSelection);
    document.getElementById('btnCloseModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelModal').addEventListener('click', closeModal);
    document.getElementById('rewardForm').addEventListener('submit', submitReward);

    document.getElementById('bulkChips').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-remove-serial]');
        if (!btn) return;
        removeSelectedStudent(btn.getAttribute('data-remove-serial'));
    });

    window.addEventListener('load', fetchAllStudents);
</script>
</body>
</html>

