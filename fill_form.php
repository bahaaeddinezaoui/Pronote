<?php
// fill_form.php (merged absences + observations) – updated to include CLASS selector
session_start();
date_default_timezone_set('Africa/Algiers');

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
            SELECT DISTINCT s.STUDENT_SERIAL_NUMBER, s.STUDENT_FIRST_NAME, s.STUDENT_LAST_NAME
            FROM student s
            INNER JOIN section se ON s.SECTION_ID = se.SECTION_ID
            INNER JOIN studies_in si ON se.SECTION_ID = si.SECTION_ID AND si.STUDY_SESSION_ID = ?
            WHERE (s.STUDENT_FIRST_NAME LIKE ? OR s.STUDENT_LAST_NAME LIKE ?)
            AND NOT EXISTS (
                SELECT 1 FROM student_gets_absent sga
                INNER JOIN absence a ON sga.ABSENCE_ID = a.ABSENCE_ID AND a.STUDY_SESSION_ID = ?
                WHERE sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
            )
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $like = '%' . $query . '%';
        $stmt->bind_param('isss', $study_session_id, $like, $like, $study_session_id);
    } else {
        $sql = "
            SELECT DISTINCT s.STUDENT_SERIAL_NUMBER, s.STUDENT_FIRST_NAME, s.STUDENT_LAST_NAME
            FROM student s
            INNER JOIN section se ON s.SECTION_ID = se.SECTION_ID
            INNER JOIN studies st ON se.SECTION_ID = st.SECTION_ID
            INNER JOIN teaches th ON st.MAJOR_ID = th.MAJOR_ID
            INNER JOIN teacher t ON th.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
            WHERE t.USER_ID = ? AND (s.STUDENT_FIRST_NAME LIKE ? OR s.STUDENT_LAST_NAME LIKE ?)
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $like = '%' . $query . '%';
        $stmt->bind_param('iss', $_SESSION['user_id'], $like, $like);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[] = [
            'serial' => $r['STUDENT_SERIAL_NUMBER'],
            'first_name' => $r['STUDENT_FIRST_NAME'],
            'last_name' => $r['STUDENT_LAST_NAME'],
            'label' => $r['STUDENT_FIRST_NAME'] . ' ' . $r['STUDENT_LAST_NAME']
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
            T.TEACHER_FIRST_NAME, 
            T.TEACHER_LAST_NAME,
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
        $logged_in_teacher_name = htmlspecialchars($row['TEACHER_FIRST_NAME']) . ' ' . htmlspecialchars($row['TEACHER_LAST_NAME']);
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
            $session_status_message = "Session already exists for this slot. You can only record observations.";
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
        SELECT DISTINCT C.CATEGORY_ID, C.CATEGORY_NAME
        FROM TEACHER T
        INNER JOIN TEACHES TH ON T.TEACHER_SERIAL_NUMBER = TH.TEACHER_SERIAL_NUMBER
        INNER JOIN MAJOR M ON TH.MAJOR_ID = M.MAJOR_ID
        INNER JOIN STUDIES SD ON M.MAJOR_ID = SD.MAJOR_ID
        INNER JOIN SECTION SE ON SD.SECTION_ID = SE.SECTION_ID
        INNER JOIN CATEGORY C ON SE.CATEGORY_ID = C.CATEGORY_ID
        WHERE T.TEACHER_SERIAL_NUMBER = ?
    ");
    $sql_categories->bind_param("s", $logged_in_teacher_serial);
    $sql_categories->execute();
    $result_categories = $sql_categories->get_result();

    if ($result_categories->num_rows > 0) {
        while ($row = $result_categories->fetch_assoc()) {
            $teacher_categories[] = [
                'id' => $row['CATEGORY_ID'],
                'name' => $row['CATEGORY_NAME']
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
$class_q = $conn->query("SELECT CLASS_ID, CLASS_NAME FROM class ORDER BY CLASS_NAME");
if ($class_q) {
    while ($r = $class_q->fetch_assoc()) {
        $classes[] = ['id' => $r['CLASS_ID'], 'name' => $r['CLASS_NAME']];
    }
}

// Render HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title>Fill Form (Absences & Observations)</title>
    <style>
        .tab-buttons { display:none; } /* Hidden as moved to navbar */
        .form-section { display:none; }
        .form-section.active { display:block; }
        .suggestions-list { position: absolute; background:#fff; border:1px solid #ccc; max-height:150px; overflow:auto; width:100%; z-index:1000; padding:0; margin:0; list-style:none; }
        .suggestions-list li { padding:6px; cursor:pointer; }
        .suggestions-list li:hover { background:#eee; }
        .navbar_buttons.active {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>

<div class="parent">
    <div class="div1" id="navbar">
        <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
            <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="teacher_home.php" id="home" class="navbar_buttons">Home</a>
                
                <?php if (!$existing_session_found): ?>
                    <a href="?tab=absences" id="tabAbs" class="navbar_buttons <?php echo $show_abs_initially ? 'active' : ''; ?>">Absences</a>
                <?php else: ?>
                    <a href="#" class="navbar_buttons" style="opacity:0.5; cursor:not-allowed;">Absences (Done)</a>
                <?php endif; ?>
                <a href="?tab=observations" id="tabObs" class="navbar_buttons <?php echo $show_obs_initially ? 'active' : ''; ?>">Observations</a>
    
                <a href="profile.php" class="navbar_buttons">Profile</a>
            </div>
            <a href="logout.php" class="navbar_buttons logout-btn" style="margin-left:auto;">Logout</a>
        </div>
    </div>

    <div class="div2" id="left_side">
        <fieldset id="form_fill">
            <legend id="form_legend">Absentees and Observations</legend>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <?php if ($existing_session_found): ?>
                    <div style="color: #d97706; font-weight: bold; font-size: 0.9em;">
                        ⚠️ <?php echo $session_status_message; ?>
                    </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <!-- Tab buttons moved to navbar -->
            </div>

            <!-- ABSENCES -->
            <div id="absences_section" class="form-section <?php echo $show_abs_initially ? 'active' : ''; ?>">
                <form id="absence_main_form" method="POST">
                    <input type="hidden" name="teacher_serial_number" value="<?php echo $logged_in_teacher_serial; ?>">
                    
                    <input type="hidden" name="session_date" value="<?php echo $server_date_value; ?>">
                    <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($selected_slot_value); ?>">
                    <!-- NEW: Class selector -->
                    <div>
                        <label for="class_select">Select Class:</label>
                        <select id="class_select" name="class_id" required>
                            <option value="">-- Select a Class --</option>
                            <?php
                            if (!empty($classes)) {
                                foreach ($classes as $c) {
                                    echo '<option value="' . htmlspecialchars($c['id']) . '">' . htmlspecialchars($c['name']) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>No classes found</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label for="categories">Select category to teach:</label>
                        <select name="categories" id="categories" required onchange="loadMajors()">
                            <option value="" disabled selected>Choose a category</option>
                            <?php
                            if (!empty($teacher_categories)) {
                                foreach ($teacher_categories as $category) {
                                    echo '<option value="' . htmlspecialchars($category['id']) . '">' . htmlspecialchars($category['name']) . '</option>';
                                }
                            } else {
                                if (empty($logged_in_teacher_serial)) {
                                    echo '<option value="" disabled>Please log in as a teacher</option>';
                                } else {
                                    echo '<option value="" disabled>No categories found for this teacher</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label for="sections">Select major to teach:</label>
                        <select name="sections" id="sections" required onchange="loadSections()">
                            <option value="" disabled selected>Choose a category first</option>
                        </select>
                    </div>

                    <div id="select_sections"></div>

                    <div id="student_table_container">
                        <div id="stats_container" style="margin-bottom:15px; display:none; width: 100%; gap: 15px;">
                            <div style="flex: 1; text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <span id="total_students" style="display:block; font-size: 2.5rem; font-weight: 800; color: #4f46e5; line-height: 1.1; margin-bottom: 4px;">0</span>
                                <span style="display:block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Total Students</span>
                            </div>
                            <div style="flex: 1; text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <span id="presentees" style="display:block; font-size: 2.5rem; font-weight: 800; color: #10b981; line-height: 1.1; margin-bottom: 4px;">0</span>
                                <span style="display:block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Presentees</span>
                            </div>
                            <div style="flex: 1; text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <span id="absentees" style="display:block; font-size: 2.5rem; font-weight: 800; color: #ef4444; line-height: 1.1; margin-bottom: 4px;">0</span>
                                <span style="display:block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Absentees</span>
                            </div>
                        </div>
                        <table id="student_table">
                            <thead>
                                <tr>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Motif</th>
                                    <th>Observation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <div id="add_row_container" style="margin-top:10px; display:none;">
                            <button type="button" onclick="addStudentRow()">➕ Add Student</button>
                        </div>


                    </div>

                    <div style="margin-top:12px;">
                        <button type="submit" id="submit_button">Submit Absences</button>
                    </div>
                </form>
            </div>

            <!-- OBSERVATIONS -->
            <div id="observations_section" class="form-section <?php echo $existing_session_found ? 'active' : ''; ?>">
                <h3>Record an Observation (one student)</h3>

                <div style="position:relative; max-width:420px;">
                    <label for="obs_student_input">Student (type first or last name):</label><br>
                    <input type="text" id="obs_student_input" placeholder="Search student..." autocomplete="off" style="width:100%;">
                    <ul id="obs_suggestions" class="suggestions-list" style="display:none;"></ul>
                    <input type="hidden" id="obs_student_serial" name="obs_student_serial">
                </div>

                <div style="margin-top:8px;">
                    <label for="obs_motif">Motif (max 30 chars)</label><br>
                    <input type="text" id="obs_motif" maxlength="30" style="width:100%;">
                </div>

                <div style="margin-top:8px;">
                    <label for="obs_note">Note (optional)</label><br>
                    <textarea id="obs_note" maxlength="256" rows="4" style="width:100%;"></textarea>
                </div>

                <div style="margin-top:10px;">
                    <button type="button" id="obs_submit_btn">Submit Observation</button>
                </div>

                <div id="obs_response" style="margin-top:8px; font-weight:bold;"></div>
            </div>

        </fieldset>
    </div>

</div>

<script>
// ===== Absence JS (kept) =====
function loadMajors() {
    const categorySelect = document.getElementById('categories');
    const majorSelect = document.getElementById('sections');
    const sectionContainer = document.getElementById('select_sections');
    const studentTableBody = document.querySelector('#student_table tbody');
    const categoryId = categorySelect.value;

    majorSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';
    sectionContainer.innerHTML = '';
    studentTableBody.innerHTML = '';

    if (!categoryId) return;

    fetch('get_majors.php?category_id=' + encodeURIComponent(categoryId) + '&teacher_serial=<?php echo $logged_in_teacher_serial; ?>')
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
        alert('⚠️ You cannot have more absentees than the total number of students.');
        tableBody.lastElementChild?.remove();
        return updateAbsenteesAndPresentees();
    }

    absenteesLabel.textContent = absentees;
    presenteesLabel.textContent = Math.max(total - absentees, 0);
}

function loadSections() {
    const majorSelect = document.getElementById('sections');
    const sectionContainer = document.getElementById('select_sections');
    const studentTableBody = document.querySelector('#student_table tbody');
    const majorId = majorSelect.value;

    sectionContainer.innerHTML = '<p>Loading sections...</p>';
    studentTableBody.innerHTML = '';

    if (!majorId) return;

    fetch('get_sections.php?major_id=' + encodeURIComponent(majorId) + '&teacher_serial=<?php echo $logged_in_teacher_serial; ?>')
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
                sectionContainer.innerHTML = '<p>No sections found for this major.</p>';
            }
        })
        .catch(() => sectionContainer.innerHTML = '<p>Error loading sections.</p>');
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
    statsContainer.style.display = 'flex';

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
        alert('⚠️ All students in the selected section(s) are already marked. You cannot add more absentees.');
        return;
    }

    const tableBody = document.querySelector('#student_table tbody');

    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="last_name[]" class="last_name" readonly></td>
        <td>
            <div style="position: relative; width: 100%;">
                <input type="text" name="first_name[]" class="first_name" oninput="searchStudent(this)" placeholder="Search student...">
                <div class="suggestions"></div>
            </div>
        </td>
        <td><input type="text" name="motif[]" ></td>
        <td><input type="text" name="observation[]" ></td>
        <td>
            <button type="button" onclick="editStudentRow(this)">Edit</button>
            <button type="button" onclick="removeStudentRow(this)">Delete</button>
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
        ul.style = 'position:absolute; list-style:none; margin:0; padding:4px; background:#fff; border:1px solid #ccc; width:100%; max-height:140px; overflow:auto; z-index:999';
        data.students.forEach(student => {
            const li = document.createElement('li');
            li.textContent = student.first_name + ' ' + student.last_name;
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
                    alert('⚠️ This student is already added to the table.');
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

    if (!serial) { resp.textContent = 'Please select a student from suggestions.'; return; }
    if (!motif) { resp.textContent = 'Motif is required.'; return; }

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
            alert(obj.message || 'Error saving observation.');
        }
    })
    .catch(err => {
        console.error('Observation submit error', err);
        alert('Error contacting server.');
    });
});

// ===== Tabs =====
<?php if (!$existing_session_found): ?>
document.getElementById('tabAbs').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('absences_section').classList.add('active');
    document.getElementById('observations_section').classList.remove('active');
    this.classList.add('active');
    document.getElementById('tabObs').classList.remove('active');
});
<?php endif; ?>

document.getElementById('tabObs').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('observations_section').classList.add('active');
    document.getElementById('absences_section').classList.remove('active');
    this.classList.add('active');
    const absBtn = document.getElementById('tabAbs');
    if(absBtn) absBtn.classList.remove('active');
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
    submitBtn.textContent = 'Submitting...';
    
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
            
            // Switch to Observations tab
            document.getElementById('tabObs').click();
        } else {
            alert(data.message || 'Error submitting form.');
        }
    })
    .catch(err => {
        console.error('Error submitting absence form:', err);
        alert('Error submitting form. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Absences';
    });
});
</script>

</body>
</html>

<?php
$conn->close();
?>