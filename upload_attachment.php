<?php
/**
 * File Upload Handler for Memo Attachments
 * Hospital Memo System
 */

session_start();
require_once("config.php");
require_once("functions.php");

// Security check - user must be logged in
if (!is_loggedin($_SESSION['sid'] ?? '', session_id())) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['attachment'];
$memo_id = $_POST['memo_id'] ?? 0;
$uid = $_SESSION['uid'] ?? 0;

// Validate memo ownership or admin privileges
$user_info = getUserInfo($uid);
$memo_check = mysqli_query($connection, "SELECT sender_id FROM memos WHERE memo_id = $memo_id");
$memo = mysqli_fetch_assoc($memo_check);

if (!$memo || ($memo['sender_id'] != $uid && !$user_info['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// File validation
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
$max_size = 10 * 1024 * 1024; // 10MB

$file_info = pathinfo($file['name']);
$extension = strtolower($file_info['extension'] ?? '');

if (!in_array($extension, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_extensions)]);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size: 10MB']);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = 'attachments/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$unique_name = uniqid() . '_' . time() . '.' . $extension;
$file_path = $upload_dir . $unique_name;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Save to database
    $stmt = mysqli_prepare($connection, "
        INSERT INTO memo_attachments (memo_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "isssisi", 
        $memo_id, 
        $unique_name, 
        $file['name'], 
        $file_path, 
        $file['size'], 
        $file['type'], 
        $uid
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $attachment_id = mysqli_insert_id($connection);
        
        echo json_encode([
            'success' => true,
            'attachment_id' => $attachment_id,
            'filename' => $file['name'],
            'size' => $file['size']
        ]);
    } else {
        // Clean up file if database insert failed
        unlink($file_path);
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . mysqli_error($connection)]);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
}
?>
