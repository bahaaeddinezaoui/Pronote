<?php
// test_photo_upload.php - Test script for photo upload functionality
session_start();
require_once __DIR__ . '/lang/i18n.php';

// Check if user is logged in as Secretary
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.html");
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

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo'])) {
    $upload_dir = __DIR__ . '/resources/photos/';
    $file_extension = strtolower(pathinfo($_FILES['test_photo']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $unique_filename = 'test_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($_FILES['test_photo']['tmp_name'], $upload_path)) {
            $photo_path = 'resources/photos/' . $unique_filename;
            $message = "Photo uploaded successfully! Path: " . $photo_path;
            $msg_type = "success";
        } else {
            $message = "Failed to move uploaded file.";
            $msg_type = "error";
        }
    } else {
        $message = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        $msg_type = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #6f42c1; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #5d3fa3; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .photo-preview { margin-top: 20px; max-width: 300px; }
        .photo-preview img { max-width: 100%; border-radius: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Photo Upload Test</h1>
    
    <?php if ($message): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="test_photo">Select Photo:</label>
            <input type="file" name="test_photo" id="test_photo" accept="image/*" required>
        </div>
        <button type="submit" class="btn">Upload Photo</button>
    </form>
    
    <div class="photo-preview">
        <h3>Uploaded Photos:</h3>
        <?php
        $photos_dir = __DIR__ . '/resources/photos/';
        if (is_dir($photos_dir)) {
            $files = scandir($photos_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && strpos($file, 'test_') === 0) {
                    echo '<img src="resources/photos/' . htmlspecialchars($file) . '" alt="' . htmlspecialchars($file) . '">';
                }
            }
        }
        ?>
    </div>
    
    <p><a href="secretary_home.php">← Back to Secretary Home</a></p>
</body>
</html>
