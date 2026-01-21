<?php
// fill_form.php (merged absences + observations) – updated to include CLASS selector
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// --- DATABASE CONNECTION SETUP ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    if (isset($_GET['action']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "DB connection failed"]);
        exit;
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Helper: nextId (same as before)
function nextId($conn, $table, $column)
{
    $result = $conn->query("SELECT MAX($column) AS max_id FROM $table");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}

/* ------------------------------
   AJAX endpoints (unchanged)
   - search_students (GET)
   - submit_observation (POST, action=submit_observation)
   ------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'search_students') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit;
    }
    $query = trim($_GET['query'] ?? '');
    if ($query === '') {
        echo json_encode([]);
        exit;
    }

    // If a study session is active in the session, restrict students to the
    // sections linked to that study session and exclude students who are
    // marked absent for that same study session. Otherwise fall back to the
    // previous behavior (students taught by this teacher).
    $study_session_id = $_SESSION['current_study_session_id'] ?? 0;
    if (!empty($study_session_id)) {
        $sql = "
            SELECT DISTINCT s.STUDENT_SERIAL_NUMBER, s.STUDENT_FIRST_NAME_EN, s.STUDENT_LAST_NAME_EN, s.STUDENT_FIRST_NAME_AR, s.STUDENT_LAST_NAME_AR
            FROM student s
            INNER JOIN section se ON s.SECTION_ID = se.SECTION_ID
            INNER JOIN studies_in si ON se.SECTION_ID = si.SECTION_ID AND si.STUDY_SESSION_ID = ?
            WHERE (s.STUDENT_FIRST_NAME_EN LIKE ? OR s.STUDENT_LAST_NAME_EN LIKE ? 
                   OR s.STUDENT_FIRST_NAME_AR LIKE ? OR s.STUDENT_LAST_NAME_AR LIKE ?)
            AND NOT EXISTS (
                SELECT 1 FROM student_gets_absent sga
                INNER JOIN absence a ON sga.ABSENCE_ID = a.ABSENCE_ID AND a.STUDY_SESSION_ID = ?
                WHERE sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
            )
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $like = '%' . $query . '%';
        $stmt->bind_param('isssss', $study_session_id, $like, $like, $like, $like, $study_session_id);
    } else {
        $sql = "
            SELECT DISTINCT s.STUDENT_SERIAL_NUMBER, s.STUDENT_FIRST_NAME_EN, s.STUDENT_LAST_NAME_EN, s.STUDENT_FIRST_NAME_AR, s.STUDENT_LAST_NAME_AR
            FROM student s
            WHERE (s.STUDENT_FIRST_NAME_EN LIKE ? OR s.STUDENT_LAST_NAME_EN LIKE ?
                   OR s.STUDENT_FIRST_NAME_AR LIKE ? OR s.STUDENT_LAST_NAME_AR LIKE ?)
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $like = '%' . $query . '%';
        $stmt->bind_param('ssss', $like, $like, $like, $like);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $label = $r['STUDENT_FIRST_NAME_EN'] . ' ' . $r['STUDENT_LAST_NAME_EN'];
        if (!empty($r['STUDENT_FIRST_NAME_AR'])) {
            $label .= ' (' . $r['STUDENT_FIRST_NAME_AR'] . ' ' . $r['STUDENT_LAST_NAME_AR'] . ')';
        }
        $out[] = [
            'serial' => $r['STUDENT_SERIAL_NUMBER'],
            'first_name' => $r['STUDENT_FIRST_NAME_EN'],
            'last_name' => $r['STUDENT_LAST_NAME_EN'],
            'label' => $label
        ];
    }
    echo json_encode($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_observation') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Teacher not logged in.']);
        exit;
    }

    $student_serial = trim($_POST['student_serial'] ?? '');
    $motif = trim($_POST['motif'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($student_serial === '' || $motif === '') {
        echo json_encode(['success' => false, 'message' => 'Missing student or motif.']);
        exit;
    }

    $s = $conn->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
    $s->bind_param('i', $_SESSION['user_id']);
    $s->execute();
    $r = $s->get_result();
    if ($r->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Teacher record not found.']);
        exit;
    }
    $teacher_serial = $r->fetch_assoc()['TEACHER_SERIAL_NUMBER'];

    // Get the current study session ID from the session, default to 0 if not set
    $study_session_id = $_SESSION['current_study_session_id'] ?? 0;

    $conn->autocommit(false);
    try {
        $obs_id = nextId($conn, 'observation', 'OBSERVATION_ID');

        $stmtObs = $conn->prepare("INSERT INTO observation (OBSERVATION_ID, STUDY_SESSION_ID) VALUES (?, ?)");
        $stmtObs->bind_param('ii', $obs_id, $study_session_id);
        $stmtObs->execute();

        $stmtInsert = $conn->prepare("
            INSERT INTO teacher_makes_an_observation_for_a_student
            (STUDENT_SERIAL_NUMBER, OBSERVATION_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_ID, OBSERVATION_DATE_AND_TIME, OBSERVATION_MOTIF, OBSERVATION_NOTE)
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        $stmtInsert->bind_param('sisiss', $student_serial, $obs_id, $teacher_serial, $study_session_id, $motif, $note);
        $ok = $stmtInsert->execute();

        if (!$ok) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error saving observation.']);
            exit;
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Observation recorded successfully.']);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

/* ------------------------------
   NORMAL PAGE RENDER: prepare variables used in HTML (existing logic)
   ------------------------------ */

// Dates/time
$server_date_display = date('d/m/Y');
$server_date_value = date('Y-m-d');
$current_time = date('H:i');

// Teacher info
$logged_in_teacher_name = "N/A - Login required";
$logged_in_teacher_serial = "";
$debug_mode = true;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_teacher_name = $conn->prepare("
        SELECT 
            T.TEACHER_FIRST_NAME_EN, 
            T.TEACHER_LAST_NAME_EN,
            T.TEACHER_SERIAL_NUMBER
        FROM TEACHER T
        INNER JOIN USER_ACCOUNT U ON T.USER_ID = U.USER_ID
        WHERE U.USER_ID = ?
    ");
    $sql_teacher_name->bind_param("i", $user_id);
    $sql_teacher_name->execute();
    $result_teacher_name = $sql_teacher_name->get_result();

    if ($result_teacher_name->num_rows > 0) {
        $row = $result_teacher_name->fetch_assoc();
        $logged_in_teacher_name = htmlspecialchars($row['TEACHER_FIRST_NAME_EN']) . ' ' . htmlspecialchars($row['TEACHER_LAST_NAME_EN']);
        $logged_in_teacher_serial = htmlspecialchars($row['TEACHER_SERIAL_NUMBER']);
    } else {
        if ($debug_mode) error_log("Teacher not found for USER_ID: " . $user_id);
    }
    $sql_teacher_name->close();
} else {
    if ($debug_mode) error_log("Session user_id not set - user not logged in");
}

// Time slot logic
function is_current_slot($slot_start, $slot_end, $current_time)
{
    $current_ts = strtotime($current_time);
    $start_ts = strtotime($slot_start);
    $end_ts = strtotime($slot_end);
    return ($current_ts >= $start_ts && $current_ts < $end_ts);
}
$time_slots = [
    ["value" => "08:00 - 10:00", "start" => "08:00", "end" => "10:00"],
    ["value" => "10:00 - 12:00", "start" => "10:00", "end" => "12:00"],
    ["value" => "13:00 - 14:30", "start" => "13:00", "end" => "14:30"],
    ["value" => "14:30 - 16:00", "start" => "14:30", "end" => "16:00"]
];
$selected_slot_value = '';
$slot_found = false;
foreach ($time_slots as $slot) {
    if (is_current_slot($slot['start'], $slot['end'], $current_time)) {
        $selected_slot_value = $slot['value'];
        $slot_found = true;
        break;
    }
}
$display_time_message = $slot_found ? $selected_slot_value : 'N/A (Outside teaching hours: ' . $current_time . ')';

// ----------------------
// NEW: Check if session exists for this teacher/date/time
// ----------------------
$existing_session_found = false;
$existing_session_id = 0;
// Default mode message
$session_status_message = "";

if ($slot_found && !empty($logged_in_teacher_serial)) {
    // Determine start/end from selected slot
    $parts = explode(' - ', $selected_slot_value);
    if (count($parts) === 2) {
        $st = trim($parts[0]);
        $et = trim($parts[1]);
        
        $chk = $conn->prepare("SELECT STUDY_SESSION_ID FROM study_session WHERE TEACHER_SERIAL_NUMBER = ? AND STUDY_SESSION_DATE = ? AND STUDY_SESSION_START_TIME = ? AND STUDY_SESSION_END_TIME = ?");
        $chk->bind_param("ssss", $logged_in_teacher_serial, $server_date_value, $st, $et);
        $chk->execute();
        $res = $chk->get_result();
        if ($res->num_rows > 0) {
            $existing_session_found = true;
            $existing_session_id = $res->fetch_assoc()['STUDY_SESSION_ID'];
            // Store in session for usage in submit_observation
            $_SESSION['current_study_session_id'] = $existing_session_id;
            $session_status_message = t('session_exists_note');
        }
                $chk->close();
    }
}

// Determine which tab to show initially
$tab_param = $_GET['tab'] ?? '';
$show_obs_initially = $existing_session_found || ($tab_param === 'observations');
$show_abs_initially = !$show_obs_initially;


// Teacher categories (existing)
$teacher_categories = [];
if (!empty($logged_in_teacher_serial)) {
    $sql_categories = $conn->prepare("
        SELECT CATEGORY_ID, CATEGORY_NAME_EN
        FROM CATEGORY
        ORDER BY CATEGORY_NAME_EN
    ");
    $sql_categories->execute();
    $result_categories = $sql_categories->get_result();

    if ($result_categories->num_rows > 0) {
        while ($row = $result_categories->fetch_assoc()) {
            $teacher_categories[] = [
                'id' => $row['CATEGORY_ID'],
                'name' => $row['CATEGORY_NAME_EN']
            ];
        }
    } else {
        if ($debug_mode) error_log("No categories found for TEACHER_SERIAL_NUMBER: " . $logged_in_teacher_serial);
    }
    $sql_categories->close();
} else {
    if ($debug_mode) error_log("Teacher serial number is empty - cannot retrieve categories");
}

// ----------------------
// NEW: load classes to populate the Class dropdown
// ----------------------
$classes = [];
$class_q = $conn->query("SELECT CLASS_ID, CLASS_NAME_EN FROM class ORDER BY CLASS_NAME_EN");
if ($class_q) {
    while ($r = $class_q->fetch_assoc()) {
        $classes[] = ['id' => $r['CLASS_ID'], 'name' => $r['CLASS_NAME_EN']];
    }
}

// Render HTML
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('absentees_observations'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .form-section { display:none; animation: fadeIn 0.3s ease-out; }
        .form-section.active { display:block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .suggestions-list { 
            position: absolute; 
            background: #fff; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            max-height: 200px; 
            overflow: auto; 
            width: 100%; 
            z-index: 1000; 
            padding: 0.5rem 0; 
            margin-top: 0.25rem; 
            list-style: none; 
        }
        .suggestions-list li { 
            padding: 0.75rem 1rem; 
            cursor: pointer; 
            transition: background 0.2s;
            font-size: 0.95rem;
        }
        .suggestions-list li:hover { background: var(--bg-tertiary); color: var(--primary-color); }

        .navbar_buttons.active {
            background: var(--primary-color);
            color: white;
        }

        .card {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .section-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
        }

        .section-item:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .section-item input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            cursor: pointer;
        }

        .stats-card-mini {
            text-align: center;
            padding: 1.25rem;
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }

        .stats-card-mini:hover { transform: translateY(-2px); }

        .stats-value-mini {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.25rem;
        }

        .stats-label-mini {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: #fff;
            transition: all 0.2s;
        }

        .btn-edit { color: var(--primary-color); }
        .btn-edit:hover { background: var(--primary-light); border-color: var(--primary-color); }
        .btn-delete { color: var(--danger-color); }
        .btn-delete:hover { background: #fee2e2; border-color: var(--danger-color); }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span>📅</span> <?php echo t('absentees_observations'); ?>
                </h2>
                <?php if ($existing_session_found): ?>
                    <div class="status-badge status-warning" style="font-size: 0.85rem;">
                        ⚠️ <?php echo $session_status_message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ABSENCES -->
            <div id="absences_section" class="form-section <?php echo $show_abs_initially ? 'active' : ''; ?>">
                <form id="absence_main_form" method="POST">
                    <input type="hidden" name="teacher_serial_number" value="<?php echo $logged_in_teacher_serial; ?>">
                    
                    <input type="hidden" name="session_date" value="<?php echo $server_date_value; ?>">
                    <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($selected_slot_value); ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="class_select"><?php echo t('select_class'); ?></label>
                            <select id="class_select" name="class_id" class="form-input" required>
                                <option value=""><?php echo t('select_class_placeholder'); ?></option>
                                <?php
                                if (!empty($classes)) {
                                    foreach ($classes as $c) {
                                        echo '<option value="' . htmlspecialchars($c['id']) . '">' . htmlspecialchars($c['name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>' . t('no_classes_found') . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="categories"><?php echo t('select_category_to_teach'); ?></label>
                            <select name="categories" id="categories" class="form-input" required onchange="loadMajors()">
                                <option value="" disabled selected><?php echo t('choose_category'); ?></option>
                                <?php
                                if (!empty($teacher_categories)) {
                                    foreach ($teacher_categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category['id']) . '">' . htmlspecialchars($category['name']) . '</option>';
                                    }
                                } else {
                                    if (empty($logged_in_teacher_serial)) {
                                        echo '<option value="" disabled>' . t('please_login_as_teacher') . '</option>';
                                    } else {
                                        echo '<option value="" disabled>' . t('no_categories_found') . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="major_select"><?php echo t('select_major_to_teach'); ?></label>
                            <select name="major_id" id="major_select" class="form-input" required onchange="loadSections()">
                                <option value="" disabled selected><?php echo t('choose_category_first'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div id="select_sections"></div>

                    <div id="student_table_container">
                        <div id="stats_container" style="margin-bottom:1.5rem; display:none; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div class="stats-card-mini" style="border-top: 4px solid var(--primary-color);">
                                <span id="total_students" class="stats-value-mini" style="color: var(--primary-color);">0</span>
                                <span class="stats-label-mini"><?php echo t('total_students'); ?></span>
                            </div>
                            <div class="stats-card-mini" style="border-top: 4px solid var(--success-color);">
                                <span id="presentees" class="stats-value-mini" style="color: var(--success-color);">0</span>
                                <span class="stats-label-mini"><?php echo t('presentees'); ?></span>
                            </div>
                            <div class="stats-card-mini" style="border-top: 4px solid var(--danger-color);">
                                <span id="absentees" class="stats-value-mini" style="color: var(--danger-color);">0</span>
                                <span class="stats-label-mini"><?php echo t('absentees'); ?></span>
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="student_table" class="data-table">
                                <thead>
                                    <tr>
                                        <th><?php echo t('last_name'); ?></th>
                                        <th><?php echo t('first_name'); ?></th>
                                        <th><?php echo t('motif'); ?></th>
                                        <th><?php echo t('observation'); ?></th>
                                        <th style="width: 150px; text-align: center;"><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div id="add_row_container" style="margin-top:1.5rem; display:none;">
                            <button type="button" class="btn btn-primary" style="width: auto;" onclick="addStudentRow()">
                                <span>➕</span> <?php echo t('add_student'); ?>
                            </button>
                        </div>
                    </div>

                    <div style="margin-top:2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                        <button type="submit" id="submit_button" class="btn btn-primary"><?php echo t('submit_absences'); ?></button>
                    </div>
                </form>
            </div>

            <!-- OBSERVATIONS -->
            <div id="observations_section" class="form-section <?php echo $existing_session_found ? 'active' : ''; ?>">
                <h3 style="margin-top:0; margin-bottom:1.5rem; color:var(--text-primary); font-size:1.1rem; font-weight:600;">
                    <?php echo t('record_observation'); ?>
                </h3>

                <div class="form-row">
                    <div class="form-group" style="position:relative;">
                        <label class="form-label" for="obs_student_input"><?php echo t('student'); ?> (<?php echo t('first_name'); ?> / <?php echo t('last_name'); ?>)</label>
                        <input type="text" id="obs_student_input" class="form-input" placeholder="<?php echo t('student_search_placeholder'); ?>" autocomplete="off">
                        <ul id="obs_suggestions" class="suggestions-list" style="display:none;"></ul>
                        <input type="hidden" id="obs_student_serial" name="obs_student_serial">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="obs_motif"><?php echo t('motif_max'); ?></label>
                        <input type="text" id="obs_motif" class="form-input" maxlength="30">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="obs_note"><?php echo t('note_optional'); ?></label>
                    <textarea id="obs_note" class="form-input" maxlength="256" rows="4" style="resize: vertical;"></textarea>
                </div>

                <div style="margin-top:1.5rem;">
                    <button type="button" id="obs_submit_btn" class="btn btn-primary" style="width: auto; min-width: 200px;">
                        <?php echo t('submit_observation'); ?>
                    </button>
                </div>

                <div id="obs_response" style="margin-top:1rem; font-weight:600; color:var(--success-color);"></div>
            </div>

        </div>
    </div>
</div>

</div>
</div>
</div>

<script>
// ===== Global Translation Object =====
const T = <?php echo json_encode($T); ?>;

// ===== Absence JS (kept) =====
function loadMajors() {
    const categorySelect = document.getElementById('categories');
    const majorSelect = document.getElementById('major_select');
    const sectionContainer = document.getElementById('select_sections');
    const studentTableBody = document.querySelector('#student_table tbody');
    const categoryId = categorySelect.value;
    const teacherSerial = '<?php echo $logged_in_teacher_serial; ?>';

    majorSelect.innerHTML = '<option value="" disabled selected>' + (T.loading || 'Loading...') + '</option>';
    sectionContainer.innerHTML = '';
    studentTableBody.innerHTML = '';

    if (!categoryId) return;

    fetch('get_majors.php?category_id=' + encodeURIComponent(categoryId) + '&teacher_serial=' + encodeURIComponent(teacherSerial))
        .then(res => res.json())
        .then(data => {
            majorSelect.innerHTML = '<option value="" disabled selected>Choose a major</option>';
            if (data.success && data.majors.length > 0) {
                data.majors.forEach(major => {
                    const option = document.createElement('option');
                    option.value = major.id;
                    option.textContent = major.name;
                    majorSelect.appendChild(option);
                });
            } else {
                majorSelect.innerHTML = '<option value="" disabled>No majors found</option>';
            }
        })
        .catch(() => majorSelect.innerHTML = '<option>Error loading majors</option>');
}

function updateAbsenteesAndPresentees() {
    const total = parseInt(document.getElementById('total_students').textContent) || 0;
    const tableBody = document.querySelector('#student_table tbody');
    const absentees = tableBody.querySelectorAll('tr').length;
    const absenteesLabel = document.getElementById('absentees');
    const presenteesLabel = document.getElementById('presentees');

    if (absentees > total) {
        alert('⚠️ ' + (T.cannot_more_absentees_than_total || 'You cannot have more absentees than the total number of students.'));
        tableBody.lastElementChild?.remove();
        return updateAbsenteesAndPresentees();
    }

    absenteesLabel.textContent = absentees;
    presenteesLabel.textContent = Math.max(total - absentees, 0);
}

function loadSections() {
    const majorSelect = document.getElementById('major_select');
    const sectionContainer = document.getElementById('select_sections');
    const studentTableBody = document.querySelector('#student_table tbody');
    const majorId = majorSelect.value;

    sectionContainer.innerHTML = '<p>' + (T.loading_sections || 'Loading sections...') + '</p>';
    studentTableBody.innerHTML = '';

    if (!majorId) return;
    const teacherSerial = '<?php echo $logged_in_teacher_serial; ?>';

    fetch('get_sections.php?major_id=' + encodeURIComponent(majorId) + '&teacher_serial=' + encodeURIComponent(teacherSerial))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.sections.length > 0) {
                sectionContainer.innerHTML = '<label class="section-group-label">Select Sections:</label>';
                const gridDiv = document.createElement('div');
                gridDiv.className = 'section-grid';
                
                data.sections.forEach(section => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'section-item';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = section.id;
                    checkbox.id = 'section_' + section.id;
                    checkbox.name = 'sections[]';  // ✅ FIX: Added name attribute
                    checkbox.addEventListener('change', loadStudents);

                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = section.name;

                    wrapper.appendChild(checkbox);
                    wrapper.appendChild(label);
                    gridDiv.appendChild(wrapper);
                });
                sectionContainer.appendChild(gridDiv);
            } else {
                sectionContainer.innerHTML = '<p>' + (T.no_sections_found || 'No sections found for this major.') + '</p>';
            }
        })
        .catch(() => sectionContainer.innerHTML = '<p>' + (T.error_loading || 'Error loading sections.') + '</p>');
}

function loadStudents() {
    const checkedBoxes = document.querySelectorAll('#select_sections input[type="checkbox"]:checked');
    const addRowContainer = document.getElementById('add_row_container');
    const statsContainer = document.getElementById('stats_container');
    const totalStudentsLabel = document.getElementById('total_students');
    const presenteesLabel = document.getElementById('presentees');
    const absenteesLabel = document.getElementById('absentees');
    const tableBody = document.querySelector('#student_table tbody');

    tableBody.innerHTML = '';

    if (checkedBoxes.length === 0) {
        addRowContainer.style.display = 'none';
        statsContainer.style.display = 'none';
        return;
    }

    addRowContainer.style.display = 'block';
    statsContainer.style.display = 'grid';

    const sectionIds = Array.from(checkedBoxes).map(cb => cb.value);

    fetch('get_total_students.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'sections[]=' + sectionIds.join('&sections[]=')
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            totalStudentsLabel.textContent = data.total;
            updateAbsenteesAndPresentees();
        } else {
            totalStudentsLabel.textContent = '0';
            presenteesLabel.textContent = '0';
            absenteesLabel.textContent = '0';
        }
    })
    .catch(err => console.error('Error loading total students:', err));
}

function addStudentRow() {
    const checkedBoxes = document.querySelectorAll('#select_sections input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) return;

    const total = parseInt(document.getElementById('total_students').textContent) || 0;
    const currentAbsentees = document.querySelectorAll('#student_table tbody tr').length;

    if (currentAbsentees >= total) {
        alert('⚠️ ' + (T.all_students_marked || 'All students in the selected section(s) are already marked. You cannot add more absentees.'));
        return;
    }

    const tableBody = document.querySelector('#student_table tbody');

    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="last_name[]" class="last_name form-input" readonly></td>
        <td>
            <div style="position: relative; width: 100%;">
                <input type="text" name="first_name[]" class="first_name form-input" oninput="searchStudent(this)" placeholder="Search student...">
                <div class="suggestions"></div>
            </div>
        </td>
        <td><input type="text" name="motif[]" class="form-input"></td>
        <td><input type="text" name="observation[]" class="form-input"></td>
        <td style="text-align: center; white-space: nowrap;">
            <button type="button" class="action-btn btn-edit" onclick="editStudentRow(this)">Edit</button>
            <button type="button" class="action-btn btn-delete" onclick="removeStudentRow(this)">Delete</button>
        </td>
    `;
    tableBody.appendChild(row);
    updateAbsenteesAndPresentees();
}

function editStudentRow(btn) {
    const row = btn.closest('tr');
    if (!row) return;
    const firstInput = row.querySelector('.first_name');
    const lastInput = row.querySelector('.last_name');
    if (firstInput) firstInput.removeAttribute('readonly');
    if (lastInput) lastInput.removeAttribute('readonly');
    if (firstInput) firstInput.focus();
}

function removeStudentRow(btn) {
    const row = btn.closest('tr');
    if (!row) return;
    row.remove();
    updateAbsenteesAndPresentees();
}

function searchStudent(inputElement) {
    const checkedBoxes = document.querySelectorAll('#select_sections input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) return;

    const sectionIds = Array.from(checkedBoxes).map(cb => cb.value);
    const query = inputElement.value.trim();

    const suggestionBox = inputElement.nextElementSibling;
    suggestionBox.innerHTML = '';

    if (query.length < 1) return;

    fetch('search_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'query=' + encodeURIComponent(query) + '&sections[]=' + sectionIds.join('&sections[]=')
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success || !data.students) return;
        const ul = document.createElement('ul');
        ul.className = 'suggestions-list';
        data.students.forEach(student => {
            const li = document.createElement('li');
            li.textContent = student.label;
            li.onclick = () => {
                const firstName = student.first_name.trim();
                const lastName = student.last_name.trim();

                const existingRows = document.querySelectorAll('#student_table tbody tr');
                const alreadyExists = Array.from(existingRows).some(row => {
                    const f = row.querySelector('.first_name')?.value.trim();
                    const l = row.querySelector('.last_name')?.value.trim();
                    return f === firstName && l === lastName;
                });

                if (alreadyExists) {
                    alert('⚠️ ' + (T.student_already_added || 'This student is already added to the table.'));
                    suggestionBox.innerHTML = '';
                    inputElement.value = '';
                    const currentRow = inputElement.closest('tr');
                    const firstVal = currentRow.querySelector('.first_name')?.value.trim();
                    const lastVal = currentRow.querySelector('.last_name')?.value.trim();
                    if (!firstVal && !lastVal) currentRow.remove();
                    updateAbsenteesAndPresentees();
                    return;
                }

                inputElement.value = firstName;
                const lastNameInput = inputElement.closest('tr').querySelector('.last_name');
                lastNameInput.value = lastName;
                suggestionBox.innerHTML = '';
            };
            ul.appendChild(li);
        });
        suggestionBox.appendChild(ul);
    })
    .catch(err => console.error('Error searching student for absence row:', err));
}

// ===== Observation JS (single-student) =====
function setObservationSuggestionVisibility(show) {
    const el = document.getElementById('obs_suggestions');
    el.style.display = show ? 'block' : 'none';
}
let obsTimer = null;
document.getElementById('obs_student_input').addEventListener('input', function() {
    const q = this.value.trim();
    const suggestions = document.getElementById('obs_suggestions');
    suggestions.innerHTML = '';
    document.getElementById('obs_student_serial').value = '';

    if (q.length < 2) {
        setObservationSuggestionVisibility(false);
        return;
    }

    if (obsTimer) clearTimeout(obsTimer);
    obsTimer = setTimeout(() => {
        fetch('<?php echo basename(__FILE__); ?>?action=search_students&query=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(list => {
                suggestions.innerHTML = '';
                if (!Array.isArray(list) || list.length === 0) {
                    setObservationSuggestionVisibility(false);
                    return;
                }
                list.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.label;
                    li.style.cursor = 'pointer';
                    li.onclick = () => {
                        document.getElementById('obs_student_input').value = item.label;
                        document.getElementById('obs_student_serial').value = item.serial;
                        suggestions.innerHTML = '';
                        setObservationSuggestionVisibility(false);
                    };
                    suggestions.appendChild(li);
                });
                setObservationSuggestionVisibility(true);
            })
            .catch(err => {
                console.error('Observation search error', err);
                setObservationSuggestionVisibility(false);
            });
    }, 220);
});

document.getElementById('obs_submit_btn').addEventListener('click', function() {
    const serial = document.getElementById('obs_student_serial').value.trim();
    const motif = document.getElementById('obs_motif').value.trim();
    const note = document.getElementById('obs_note').value.trim();
    const resp = document.getElementById('obs_response');

    if (!serial) { resp.textContent = (T.please_select_student || 'Please select a student from suggestions.'); return; }
    if (!motif) { resp.textContent = (T.motif_required || 'Motif is required.'); return; }

    const fd = new FormData();
    fd.append('action', 'submit_observation');
    fd.append('student_serial', serial);
    fd.append('motif', motif);
    fd.append('note', note);

    fetch('<?php echo basename(__FILE__); ?>', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(obj => {
        if (obj.success) {
            alert(obj.message || 'Observation recorded successfully.');
            resp.textContent = ''; // Clear any previous text
            
            document.getElementById('obs_student_input').value = '';
            document.getElementById('obs_student_serial').value = '';
            document.getElementById('obs_motif').value = '';
            document.getElementById('obs_note').value = '';
        } else {
            alert(obj.message || (T.error_saving_observation || 'Error saving observation.'));
        }
    })
    .catch(err => {
        console.error('Observation submit error', err);
        alert(T.error_contacting_server || 'Error contacting server.');
    });
});

// ===== Absence Form Submit Handler (AJAX) =====
document.getElementById('absence_main_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rows = document.querySelectorAll('#student_table tbody tr');
    for (const row of rows) {
        const first = row.querySelector('.first_name')?.value.trim() || '';
        const last = row.querySelector('.last_name')?.value.trim() || '';
        if (!first || !last) {
            alert('Please fill first and last name for every added student row.');
            return;
        }
    }

    const formData = new FormData(this);
    const submitBtn = document.getElementById('submit_button');
    submitBtn.disabled = true;
    submitBtn.textContent = (T.submitting || 'Submitting...');
    
    fetch('submit_form.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Form submitted successfully!');
            
            // Clear the form
            document.getElementById('absence_main_form').reset();
            document.querySelector('#student_table tbody').innerHTML = '';
            document.getElementById('add_row_container').style.display = 'none';
            document.getElementById('stats_container').style.display = 'none';
            document.getElementById('select_sections').innerHTML = '';
            
            // Redirect to Observations view to continue workflow
            window.location.href = '<?php echo basename(__FILE__); ?>?tab=observations';
        } else {
            alert(data.message || (T.error_submitting || 'Error submitting form.'));
            if (data.message && String(data.message).toLowerCase().includes('session already exists')) {
                window.location.href = '<?php echo basename(__FILE__); ?>?tab=observations';
            }
        }
    })
    .catch(err => {
        console.error('Error submitting absence form:', err);
        alert(T.error_submitting_please_retry || 'Error submitting form. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = T.submit_absences || 'Submit Absences';
    });
});
</script>

</body>
</html>

<?php
$conn->close();
?>
