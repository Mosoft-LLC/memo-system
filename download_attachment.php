<?php
/**
 * File Download Handler for Memo Attachments
 * Hospital Memo System
 */

session_start();
require_once("config.php");
require_once("functions.php");

// Security check - user must be logged in
if (!is_loggedin($_SESSION['sid'] ?? '', session_id())) {
    header("Location: index.php");
    exit;
}

$attachment_id = $_GET['id'] ?? 0;
$uid = $_SESSION['uid'] ?? 0;

if (!$attachment_id) {
    die("Invalid attachment ID");
}

// Get attachment info and verify access
$stmt = mysqli_prepare($connection, "
    SELECT ma.*, m.sender_id 
    FROM memo_attachments ma 
    JOIN memos m ON ma.memo_id = m.memo_id 
    LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
    WHERE ma.attachment_id = ? 
    AND (m.sender_id = ? OR mr.user_id = ? OR ? IN (SELECT user_id FROM users WHERE is_admin = 1))
");

mysqli_stmt_bind_param($stmt, "iiii", $attachment_id, $uid, $uid, $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attachment = mysqli_fetch_assoc($result);

if (!$attachment) {
    die("Attachment not found or access denied");
}

$file_path = $attachment['file_path'];

if (!file_exists($file_path)) {
    die("File not found on server");
}

// Set appropriate headers for download
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, must-revalidate');

// Output file
readfile($file_path);

// Log download activity
mysqli_query($connection, "
    INSERT INTO memo_attachments_log (attachment_id, downloaded_by, downloaded_at) 
    VALUES ($attachment_id, $uid, NOW())
");

exit;
?>
