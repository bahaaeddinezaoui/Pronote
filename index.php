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
    $stmt = $conn->prepare("SELECT USER_ID, PASSWORD_HASH, ROLE FROM USER_ACCOUNT WHERE USERNAME = ?");
    $stmt->bind_param("s", $username);

    // 4. Execute the Query
    $stmt->execute();
    $stmt->store_result();

    // 5. Check if a user with that username exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        // 6. Verify the Password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            $redirect = 'admin_home.php';
            if ($role === 'Teacher') {
                $redirect = 'teacher_home.php';
            } elseif ($role === 'Secretary') {
                $redirect = 'secretary_home.php';
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