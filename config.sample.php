<?php
/**
 * SAMPLE CONFIGURATION FILE for InfinityFree Deployment
 * 
 * INSTRUCTIONS:
 * 1. Rename this file to 'config.php'
 * 2. Update the database credentials with your InfinityFree database details
 * 3. Update the base URL with your actual domain
 * 4. Save the file on your server
 * 
 * SECURITY NOTE: This file contains sensitive information and should not be
 * committed to version control. It's excluded in .gitignore for security.
 */

// Security check to prevent direct access
if (preg_match("/config\.php/i", $_SERVER['PHP_SELF'])) {
    Header("Location: index.php");
    die();
}

// ===========================================
// FILE UPLOAD CONFIGURATION
// ===========================================
$attachmentpath = __DIR__ . DIRECTORY_SEPARATOR . "attachments" . DIRECTORY_SEPARATOR;

// ===========================================
// SITE CONFIGURATION
// ===========================================
$sitetitle = "MOSOFT MEMO SYSTEM";

// IMPORTANT: Replace with your actual InfinityFree domain
// Examples:
// - https://yourdomain.infinityfreeapp.com
// - https://yourdomain.42web.io  
// - https://yourdomain.rf.gd
// - Your custom domain if configured
$baseurl = "https://yourdomain.infinityfreeapp.com";

// ===========================================
// DATABASE CONFIGURATION - InfinityFree
// ===========================================

// IMPORTANT: Get these details from your InfinityFree control panel
// Go to: Control Panel > MySQL Databases

// Database hostname (usually one of these):
// - sql200.infinityfree.com
// - sql201.infinityfree.com  
// - sql202.infinityfree.com
// Check your control panel for the exact hostname
$dbhostname = "sql200.infinityfree.com";

// Your InfinityFree database username (format: if0_xxxxxxxx)
$dbusername = "if0_12345678";

// Your database password (from InfinityFree control panel)
$dbpassword = "your_database_password_here";

// Your database name (usually same as username with suffix)
// Format: if0_xxxxxxxx_memo (or whatever suffix you chose)
$dbname = "if0_12345678_memo";

// Table prefix (leave as is unless you changed it)
$tblprefix = "mars";

// ===========================================
// INFINITYFREE SPECIFIC SETTINGS
// ===========================================

// Increase memory limit for PDF generation (if allowed)
ini_set('memory_limit', '256M');

// Set max execution time for PDF operations
ini_set('max_execution_time', 300);

// Error reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Change to 0 for production

// ===========================================
// OPTIONAL: ADDITIONAL CONFIGURATION
// ===========================================

// Timezone setting
date_default_timezone_set('America/New_York'); // Change to your timezone

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

?>
