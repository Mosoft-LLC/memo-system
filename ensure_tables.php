<?php
/**
 * Database Update Script - Auto-create missing memo_comments table
 * This script will automatically create the memo_comments table if it doesn't exist
 */

require_once 'config.php';

// Create database connection
$connection = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbname);
if (!$connection) {
    die('Database connection failed: ' . mysqli_connect_error());
}

/**
 * Check if memo_comments table exists and create it if missing
 */
function ensureMemoCommentsTable($connection) {
    // Check if table exists
    $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'memo_comments'");
    
    if (mysqli_num_rows($check_table) == 0) {
        // Table doesn't exist, create it
        $create_table_sql = "
        CREATE TABLE `memo_comments` (
          `comment_id` int NOT NULL AUTO_INCREMENT,
          `memo_id` int NOT NULL,
          `user_id` int NOT NULL,
          `comment_text` text NOT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`comment_id`),
          KEY `idx_memo_id` (`memo_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_created_at` (`created_at`),
          KEY `idx_memo_comments_memo_user` (`memo_id`, `user_id`),
          KEY `idx_memo_comments_created` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ";
        
        if (mysqli_query($connection, $create_table_sql)) {
            error_log("memo_comments table created successfully");
            return true;
        } else {
            error_log("Error creating memo_comments table: " . mysqli_error($connection));
            return false;
        }
    }
    
    // Table already exists
    return true;
}

// Auto-create table when this file is included
ensureMemoCommentsTable($connection);
?>
