<?php
// Start the session at the very top of the script
session_start();
require_once __DIR__ . '/lang/i18n.php';

// Ensure the script only runs when the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    // 1. Database Connection Details
    $servername = "localhost";
    $username_db = "root";
    $password_db = "08212001";
    $dbname = "edutrack";

    // Get user input from the POST request
    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Establish Database Connection
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => t('db_connection_failed')]);
        exit;
    }

    // 3. Prepare SQL Query with a Prepared Statement
    $stmt = $conn->prepare("SELECT USER_ID, PASSWORD_HASH, ROLE, LAST_LOGIN_AT FROM USER_ACCOUNT WHERE USERNAME = ?");
    $stmt->bind_param("s", $username);

    // 4. Execute the Query
    $stmt->execute();
    $stmt->store_result();

    // 5. Check if a user with that username exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role, $last_login_at);
        $stmt->fetch();

        // 6. Verify the Password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['last_login_at'] = $last_login_at;
            $_SESSION['needs_onboarding'] = false;

            $redirect = 'admin_home.php';
            if ($role === 'Teacher') {
                $needs_onboarding = false;

                $stmtTeacher = $conn->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
                if ($stmtTeacher) {
                    $uidT = (int)$user_id;
                    $stmtTeacher->bind_param('i', $uidT);
                    $stmtTeacher->execute();
                    $resTeacher = $stmtTeacher->get_result();
                    $rowTeacher = $resTeacher ? $resTeacher->fetch_assoc() : null;
                    $stmtTeacher->close();

                    if ($rowTeacher && !empty($rowTeacher['TEACHER_SERIAL_NUMBER'])) {
                        $teacherSerial = $rowTeacher['TEACHER_SERIAL_NUMBER'];
                        $stmtHas = $conn->prepare("SELECT 1 FROM TEACHES WHERE TEACHER_SERIAL_NUMBER = ? LIMIT 1");
                        if ($stmtHas) {
                            $stmtHas->bind_param('s', $teacherSerial);
                            $stmtHas->execute();
                            $resHas = $stmtHas->get_result();
                            $needs_onboarding = (!$resHas || $resHas->num_rows === 0);
                            $stmtHas->close();
                        }
                    }
                }

                if ($last_login_at !== null) {
                    $needs_onboarding = false;
                }

                if ($needs_onboarding) {
                    $_SESSION['needs_onboarding'] = true;
                    $redirect = 'teacher_onboarding.php';
                } else {
                    $redirect = 'teacher_home.php';
                }
            } elseif ($role === 'Secretary') {
                $redirect = 'secretary_home.php';
            }

            $upd = $conn->prepare("UPDATE USER_ACCOUNT SET LAST_LOGIN_AT = NOW() WHERE USER_ID = ?");
            if ($upd) {
                $uid = (int)$user_id;
                $upd->bind_param('i', $uid);
                $upd->execute();
                $upd->close();
            }

            echo json_encode(['success' => true, 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => t('invalid_credentials')]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => t('invalid_credentials')]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo t('access_denied');
}
?>