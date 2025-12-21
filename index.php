<?php
// Start the session at the very top of the script
session_start();

// Ensure the script only runs when the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Database Connection Details
    $servername = "localhost";
    $username_db = "root";
    $password_db = "";
    $dbname = "test_class_edition";

    // Get user input from the POST request
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // 2. Establish Database Connection
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 3. Prepare SQL Query with a Prepared Statement
    // Fetch password hash, role, AND the USER_ID
    $stmt = $conn->prepare("SELECT USER_ID, PASSWORD_HASH, ROLE FROM USER_ACCOUNT WHERE USERNAME = ?");
    $stmt->bind_param("s", $username);

    // 4. Execute the Query
    $stmt->execute();
    $stmt->store_result();

    // 5. Check if a user with that username exists
    if ($stmt->num_rows > 0) {
        // Bind the result variables
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        // 6. Verify the Password
        if (password_verify($password, $hashed_password)) {
            // Password is correct! Now check the role.

            // *** STORE USER_ID IN SESSION ***
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            // 7. Role-Based Action
            if ($role === 'Teacher') {
                // Redirect teacher to fill_form.php
                header("Location: teacher_home.php");
                exit();
            } elseif ($role === 'Admin') {
                // Redirect admin to admin_dashboard.php
                header("Location: admin_home.php");
                exit();
            } else {
                // For users with unrecognized roles
                echo "Login successful, but you do not have the appropriate access rights.";
            }
        } else {
            // Invalid password
            echo "Invalid username or password.";
        }
    } else {
        // User not found
        echo "Invalid username or password.";
    }

    // 8. Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // If someone tries to access login.php directly without POST
    echo "Access denied.";
}
?>