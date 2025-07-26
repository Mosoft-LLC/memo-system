<?php
// PDF Download Handler
session_start();
require_once 'config.php';
require_once 'functions.php';

// Security check - user must be logged in
if (!is_loggedin($_SESSION['sid'] ?? '', session_id())) {
    http_response_code(403);
    die('Access denied. Please login first.');
}

$uid = $_SESSION['uid'] ?? 0;
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('No file specified.');
}

// Sanitize filename
$filename = basename($filename);
$file_path = __DIR__ . DIRECTORY_SEPARATOR . 'pdf_exports' . DIRECTORY_SEPARATOR . $filename;

if (!file_exists($file_path)) {
    http_response_code(404);
    die('PDF file not found.');
}

// Extract memo number from filename for access control
preg_match('/memo_([^_]+)_/', $filename, $matches);
if (!$matches[1]) {
    http_response_code(403);
    die('Invalid PDF file.');
}

$memo_number = $matches[1];

// Verify user has access to this memo
$access_check = mysqli_query($connection, "
    SELECT m.memo_id 
    FROM memos m 
    LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
    WHERE m.memo_number = '$memo_number' 
    AND (m.sender_id = $uid OR mr.user_id = $uid)
");

if (mysqli_num_rows($access_check) == 0) {
    http_response_code(403);
    die('Access denied.');
}

// Serve the file
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($file_path);
exit();
?>
