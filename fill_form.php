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
            (STUDENT_SERIAL_NUMBER, OBSERVATION_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_ID, OBSERVATION_DATE_AND_TIME, OBSERVATION_MOTIF, OBSERVATION_NOTE, IS_NEW_FOR_ADMIN)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, 1)
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
        .tab-buttons { display:flex; gap:8px; margin-bottom:12px; }
        .tab-buttons button { padding:6px 10px; border-radius:6px; border:1px solid #bbb; background:#f2f2f2; cursor:pointer; }
        .tab-buttons button.active { background:#007bff; color:white; border-color:#007bff; }
        .form-section { display:none; }
        .form-section.active { display:block; }
        .suggestions-list { position: absolute; background:#fff; border:1px solid #ccc; max-height:150px; overflow:auto; width:100%; z-index:1000; padding:0; margin:0; list-style:none; }
        .suggestions-list li { padding:6px; cursor:pointer; }
        .suggestions-list li:hover { background:#eee; }
    </style>
</head>
<body>

<div class="parent">
    <div class="div1" id="navbar">
        <div style="font-family: sans-serif; display:flex; align-items:center; justify-content:space-between; width:100%;">
            <a href="#" id="home" class="navbar_buttons">Home</a>
            <a href="logout.php" class="navbar_buttons" style="margin-left:auto;">Logout</a>
        </div>
    </div>

    <div class="div2" id="left_side">
        <fieldset id="form_fill">
            <legend id="form_legend">Absentees and Observations</legend>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                
                <div>
                    <strong>Teacher:</strong>
                    <input type="text" id="teacher_name_display" value="<?php echo $logged_in_teacher_name; ?>" disabled style="background:#eee; border:0; padding:2px 6px;">
                </div>
                
                <div>
                    <div class="tab-buttons">
                        <button id="tabAbs" class="active">Absences</button>
                        <button id="tabObs">Observations</button>
                    </div>
                </div>
            </div>

            <!-- ABSENCES -->
            <div id="absences_section" class="form-section active">
                <form id="absence_main_form" method="POST">
                    <input type="hidden" name="teacher_serial_number" value="<?php echo $logged_in_teacher_serial; ?>">
                    
                    <div id="div_select_date_and_time">
                        <label for="date_display">Current Date:</label>
                        <input type="text" id="date_display" value="<?php echo $server_date_display; ?>" disabled style="background-color:#eee;">
                        <input type="hidden" name="session_date" value="<?php echo $server_date_value; ?>">
                    </div>
                    

                    
                    <div id="select_time_slot">
                        <label for="time_slot_display">Selected time slot:</label>
                        <input type="text" id="time_slot_display" value="<?php echo htmlspecialchars($display_time_message); ?>" readonly style="background:#eee;">
                        <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($selected_slot_value); ?>">
                    </div>
                    

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
                        <table id="student_table">
                            <thead>
                                <tr>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Motif</th>
                                    <th>Observation</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <div id="add_row_container" style="margin-top:10px; display:none;">
                            <button type="button" onclick="addStudentRow()">➕ Add Student</button>
                        </div>

                        <div id="stats_container" style="margin-top:10px; display:none;">
                            <p><strong>Total Students:</strong> <span id="total_students">0</span></p>
                            <p><strong>Presentees:</strong> <span id="presentees">0</span></p>
                            <p><strong>Absentees:</strong> <span id="absentees">0</span></p>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <button type="submit" id="submit_button">Submit Absences</button>
                    </div>
                </form>
            </div>

            <!-- OBSERVATIONS -->
            <div id="observations_section" class="form-section">
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
                sectionContainer.innerHTML = '<label>Select Sections:</label><br>';
                data.sections.forEach(section => {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = section.id;
                    checkbox.id = 'section_' + section.id;
                    checkbox.addEventListener('change', loadStudents);

                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = ' ' + section.name;

                    sectionContainer.appendChild(checkbox);
                    sectionContainer.appendChild(label);
                    sectionContainer.appendChild(document.createElement('br'));
                });
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
    statsContainer.style.display = 'block';

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
            <input type="text" name="first_name[]" class="first_name" oninput="searchStudent(this)" placeholder="Search student...">
            <div class="suggestions" style="position:relative;"></div>
        </td>
        <td><input type="text" name="motif[]" ></td>
        <td><input type="text" name="observation[]" ></td>
    `;
    tableBody.appendChild(row);
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
            resp.style.color = 'green';
            resp.textContent = obj.message || 'Observation saved.';
            document.getElementById('obs_student_input').value = '';
            document.getElementById('obs_student_serial').value = '';
            document.getElementById('obs_motif').value = '';
            document.getElementById('obs_note').value = '';
        } else {
            resp.style.color = 'red';
            resp.textContent = obj.message || 'Error saving observation.';
        }
    })
    .catch(err => {
        console.error('Observation submit error', err);
        resp.style.color = 'red';
        resp.textContent = 'Error contacting server.';
    });
});

// ===== Tabs =====
document.getElementById('tabAbs').addEventListener('click', function() {
    document.getElementById('absences_section').classList.add('active');
    document.getElementById('observations_section').classList.remove('active');
    this.classList.add('active');
    document.getElementById('tabObs').classList.remove('active');
});
document.getElementById('tabObs').addEventListener('click', function() {
    document.getElementById('observations_section').classList.add('active');
    document.getElementById('absences_section').classList.remove('active');
    this.classList.add('active');
    document.getElementById('tabAbs').classList.remove('active');
});

// ===== Absence Form Submit Handler (AJAX) =====
document.getElementById('absence_main_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
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