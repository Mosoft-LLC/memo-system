<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (preg_match("/functions\.php/i", $_SERVER['PHP_SELF'])) {
    Header("Location: index.php");
    die();
}

require_once("config.php");
require_once("encode.php");

// Database connection using mysqli
$connection = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbname);
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($connection, "utf8");

// Ensure required tables exist
require_once("ensure_tables.php");

/**
 * Hospital Memo System Functions
 * Updated for new database structure
 */

// Function to validate user login
function is_valid($username, $password) {
    global $connection;
    $stmt = mysqli_prepare($connection, "SELECT user_id, password FROM users WHERE username = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Check if password matches (supports both old MD5 and new hashed passwords)
        if (password_verify($password, $row['password']) || md5($password) === $row['password']) {
            return true;
        }
    }
    return false;
}

// Function to check if user is logged in
function is_loggedin($stored_sid, $current_sid) {
    return !empty($stored_sid) && $stored_sid === $current_sid && isset($_SESSION['uid']);
}

// Function to get user information
function getUserInfo($user_id) {
    global $connection;
    $stmt = mysqli_prepare($connection, "
        SELECT u.*, d.department_name, d.code as department_code, ur.role_name, ur.can_send_memos, ur.can_receive_memos
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN user_roles ur ON u.role_id = ur.role_id
        WHERE u.user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// Function to generate memo number
function generateMemoNumber() {
    global $connection;
    $year = date('Y');
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM memos WHERE YEAR(created_at) = $year");
    $count = mysqli_fetch_assoc($result)['count'] + 1;
    return "MEMO-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Function to set header
function setHeader() {
    global $baseurl, $sitetitle;
    include("includes/header.php");
}

// Function to display error messages
function error($message) {
    echo '<div class="alert alert-danger">' . $message . '</div>';
}

// Function to show back button
function back() {
    echo '<br><button onclick="history.back()" class="btn btn-secondary">Go Back</button>';
}

// Function to logout
function logout() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Function to open table (for legacy compatibility)
function OpenTable() {
    echo '<div class="card"><div class="card-body">';
}

// Function to close table (for legacy compatibility)
function CloseTable() {
    echo '</div></div>';
}

// Function to generate navigation menu with enhanced UI/UX
function genMenu($uid) {
    global $connection;
    
    $user_info = getUserInfo($uid);
    $is_admin = $user_info['is_admin'] ?? 0;
    $can_send = $user_info['can_send_memos'] ?? false;
    $role_name = $user_info['role_name'] ?? 'User';
    
    echo '<div class="card border-0 shadow-sm">';
    echo '<div class="card-header bg-gradient" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-light)); border: none;">';
    echo '<h5 class="mb-0 text-white d-flex align-items-center gap-2">';
    echo '<i class="fas fa-hospital-user"></i>';
    echo '<span>Navigation Menu</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body p-0">';
    
    // Enhanced user info section
    echo '<div class="user-profile-section p-4 bg-light border-bottom">';
    echo '<div class="d-flex align-items-center gap-3">';
    echo '<div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 50%; font-weight: 600; font-size: 1.2rem;">';
    echo strtoupper(substr($user_info['first_name'], 0, 1) . substr($user_info['last_name'], 0, 1));
    echo '</div>';
    echo '<div class="flex-grow-1">';
    echo '<h6 class="mb-1 fw-semibold text-dark">' . htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']) . '</h6>';
    echo '<div class="text-muted small mb-1">';
    echo '<i class="fas fa-building me-1"></i>' . htmlspecialchars($user_info['department_name'] ?? 'No Department');
    echo '</div>';
    echo '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">';
    echo '<i class="fas fa-user-tag me-1"></i>' . htmlspecialchars($role_name);
    echo '</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<nav class="nav flex-column">';
    
    // Main navigation items with enhanced styling
    echo '<div class="nav-section">';
    echo '<h6 class="nav-section-title px-4 py-2 mb-0 text-muted text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Main Menu</h6>';
    
    echo '<a href="dologin.php?op=dashboard" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
    echo '<i class="fas fa-tachometer-alt text-primary" style="width: 18px;"></i>';
    echo '<span class="fw-medium">Dashboard</span>';
    echo '<span class="badge bg-light text-dark ms-auto small">Home</span>';
    echo '</a>';
    
    echo '<a href="dologin.php?op=inbox" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
    echo '<i class="fas fa-inbox text-info" style="width: 18px;"></i>';
    echo '<span class="fw-medium">Inbox</span>';
    // Add unread count badge
    $unread_result = mysqli_query($connection, "SELECT COUNT(*) as count FROM memo_recipients WHERE user_id = $uid AND is_read = 0");
    $unread_count = mysqli_fetch_assoc($unread_result)['count'];
    if ($unread_count > 0) {
        echo '<span class="badge bg-danger ms-auto">' . $unread_count . '</span>';
    }
    echo '</a>';
    
    // Conditional menu items for sending
    if ($can_send) {
        echo '<a href="dologin.php?op=compose" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-edit text-success" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Compose Memo</span>';
        echo '<span class="badge bg-success-subtle text-success ms-auto small">New</span>';
        echo '</a>';
        
        echo '<a href="dologin.php?op=sent" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-paper-plane text-warning" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Sent Memos</span>';
        echo '</a>';
    }
    
    echo '<a href="dologin.php?op=listcontacts" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
    echo '<i class="fas fa-users text-secondary" style="width: 18px;"></i>';
    echo '<span class="fw-medium">Staff Directory</span>';
    echo '</a>';
    
    echo '</div>';
    
    // Admin section with enhanced styling
    if ($is_admin) {
        echo '<div class="nav-section border-top">';
        echo '<h6 class="nav-section-title px-4 py-2 mb-0 text-muted text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">';
        echo '<i class="fas fa-shield-alt me-1"></i>Administration';
        echo '</h6>';
        
        echo '<a href="dologin.php?op=adminusers" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-user-plus text-primary" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Manage Users</span>';
        echo '</a>';
        
        echo '<a href="dologin.php?op=manage_departments" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-building text-info" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Departments</span>';
        echo '</a>';
        
        echo '<a href="dologin.php?op=manage_categories" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-tags text-success" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Categories</span>';
        echo '</a>';
        
        echo '<a href="dologin.php?op=manage_roles" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-user-shield text-warning" style="width: 18px;"></i>';
        echo '<span class="fw-medium">User Roles</span>';
        echo '</a>';
        
        echo '<a href="dologin.php?op=system_reports" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 hover-bg-light">';
        echo '<i class="fas fa-chart-bar text-danger" style="width: 18px;"></i>';
        echo '<span class="fw-medium">Analytics</span>';
        echo '</a>';
        
        echo '</div>';
    }
    
    // System section
    echo '<div class="nav-section border-top mt-auto">';
    echo '<a href="dologin.php?op=logout" class="nav-link text-decoration-none py-3 px-4 d-flex align-items-center gap-3 text-danger hover-bg-danger-light">';
    echo '<i class="fas fa-sign-out-alt" style="width: 18px;"></i>';
    echo '<span class="fw-medium">Sign Out</span>';
    echo '</a>';
    echo '</div>';
    
    echo '</nav>';
    echo '</div>';
    echo '</div>';
    
    // Add custom CSS for enhanced navigation
    echo '<style>
        .nav-section-title {
            background: rgba(108, 117, 125, 0.05);
        }
        .hover-bg-light:hover {
            background-color: rgba(13, 71, 161, 0.04) !important;
            transform: translateX(2px);
            transition: all 0.2s ease;
        }
        .hover-bg-danger-light:hover {
            background-color: rgba(244, 67, 54, 0.04) !important;
        }
        .nav-link {
            border: none;
            transition: all 0.2s ease;
        }
        .avatar-circle {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-profile-section {
            position: relative;
        }
        .user-profile-section::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
        }
    </style>';
}

// Function to display enhanced dashboard with better UI/UX
function showDashboard($uid) {
    global $connection;
    
    echo '<div class="container-fluid fade-in">';
    echo '<div class="row align-items-center mb-4">';
    echo '<div class="col">';
    echo '<h2 class="display-6 fw-bold text-dark mb-1 d-flex align-items-center gap-2">';
    echo '<i class="fas fa-tachometer-alt text-primary"></i>';
    echo '<span>Dashboard Overview</span>';
    echo '</h2>';
    echo '<p class="text-muted mb-0">Welcome back! Here\'s what\'s happening with your memos today.</p>';
    echo '</div>';
    echo '<div class="col-auto">';
    echo '<div class="text-muted small">';
    echo '<i class="fas fa-calendar-alt me-1"></i>';
    echo date('l, F j, Y g:i A');
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Enhanced statistics cards
    echo '<div class="row g-4 mb-5">';
    
    // Unread memos
    $unread_result = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM memo_recipients mr 
        WHERE mr.user_id = $uid AND mr.is_read = 0
    ");
    $unread_count = mysqli_fetch_assoc($unread_result)['count'];
    
    echo '<div class="col-xl-3 col-md-6">';
    echo '<div class="card border-0 shadow-sm h-100 stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
    echo '<div class="card-body text-white d-flex align-items-center">';
    echo '<div class="flex-grow-1">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<i class="fas fa-envelope text-white-50 me-2" style="font-size: 1.2rem;"></i>';
    echo '<h6 class="text-white-50 mb-0 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Unread Memos</h6>';
    echo '</div>';
    echo '<div class="d-flex align-items-baseline">';
    echo '<h2 class="display-4 fw-bold mb-0">' . $unread_count . '</h2>';
    if ($unread_count > 0) {
        echo '<span class="ms-2 badge bg-light text-dark">New</span>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="stat-icon">';
    echo '<i class="fas fa-envelope-open" style="font-size: 2.5rem; opacity: 0.2;"></i>';
    echo '</div>';
    echo '</div></div></div>';
    
    // Total received
    $total_result = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM memo_recipients mr 
        WHERE mr.user_id = $uid
    ");
    $total_count = mysqli_fetch_assoc($total_result)['count'];
    
    echo '<div class="col-xl-3 col-md-6">';
    echo '<div class="card border-0 shadow-sm h-100 stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">';
    echo '<div class="card-body text-white d-flex align-items-center">';
    echo '<div class="flex-grow-1">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<i class="fas fa-inbox text-white-50 me-2" style="font-size: 1.2rem;"></i>';
    echo '<h6 class="text-white-50 mb-0 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Received</h6>';
    echo '</div>';
    echo '<h2 class="display-4 fw-bold mb-0">' . $total_count . '</h2>';
    echo '</div>';
    echo '<div class="stat-icon">';
    echo '<i class="fas fa-download" style="font-size: 2.5rem; opacity: 0.2;"></i>';
    echo '</div>';
    echo '</div></div></div>';
    
    // Sent memos
    $sent_result = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM memos 
        WHERE sender_id = $uid
    ");
    $sent_count = mysqli_fetch_assoc($sent_result)['count'];
    
    echo '<div class="col-xl-3 col-md-6">';
    echo '<div class="card border-0 shadow-sm h-100 stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">';
    echo '<div class="card-body text-white d-flex align-items-center">';
    echo '<div class="flex-grow-1">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<i class="fas fa-paper-plane text-white-50 me-2" style="font-size: 1.2rem;"></i>';
    echo '<h6 class="text-white-50 mb-0 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Sent Memos</h6>';
    echo '</div>';
    echo '<h2 class="display-4 fw-bold mb-0">' . $sent_count . '</h2>';
    echo '</div>';
    echo '<div class="stat-icon">';
    echo '<i class="fas fa-share" style="font-size: 2.5rem; opacity: 0.2;"></i>';
    echo '</div>';
    echo '</div></div></div>';
    
    // Pending acknowledgments
    $pending_result = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM memo_recipients mr 
        JOIN memos m ON mr.memo_id = m.memo_id 
        WHERE mr.user_id = $uid AND m.requires_acknowledgment = 1 AND mr.acknowledged_at IS NULL
    ");
    $pending_count = mysqli_fetch_assoc($pending_result)['count'];
    
    echo '<div class="col-xl-3 col-md-6">';
    echo '<div class="card border-0 shadow-sm h-100 stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">';
    echo '<div class="card-body text-white d-flex align-items-center">';
    echo '<div class="flex-grow-1">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<i class="fas fa-clock text-white-50 me-2" style="font-size: 1.2rem;"></i>';
    echo '<h6 class="text-white-50 mb-0 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending Acks</h6>';
    echo '</div>';
    echo '<div class="d-flex align-items-baseline">';
    echo '<h2 class="display-4 fw-bold mb-0">' . $pending_count . '</h2>';
    if ($pending_count > 0) {
        echo '<span class="ms-2 badge bg-light text-dark">Action Needed</span>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="stat-icon">';
    echo '<i class="fas fa-hourglass-half" style="font-size: 2.5rem; opacity: 0.2;"></i>';
    echo '</div>';
    echo '</div></div></div>';
    
    echo '</div>'; // End stats row
    
    // Content sections
    echo '<div class="row g-4">';
    
    // Recent memos section
    echo '<div class="col-lg-8">';
    echo '<div class="card border-0 shadow-sm h-100">';
    echo '<div class="card-header bg-white border-bottom-0 py-3">';
    echo '<div class="d-flex align-items-center justify-content-between">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-history text-primary"></i>';
    echo '<span>Recent Activity</span>';
    echo '</h5>';
    echo '<a href="dologin.php?op=inbox" class="btn btn-sm btn-outline-primary">View All</a>';
    echo '</div>';
    echo '</div>';
    echo '<div class="card-body p-0">';
    
    $recent_result = mysqli_query($connection, "
        SELECT m.*, u.first_name, u.last_name, mc.category_name, mr.is_read, mr.read_at
        FROM memos m 
        JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        JOIN users u ON m.sender_id = u.user_id 
        LEFT JOIN memo_categories mc ON m.category_id = mc.category_id 
        WHERE mr.user_id = $uid 
        ORDER BY m.created_at DESC 
        LIMIT 5
    ");
    
    if (mysqli_num_rows($recent_result) > 0) {
        echo '<div class="list-group list-group-flush">';
        $index = 0;
        while ($memo = mysqli_fetch_assoc($recent_result)) {
            $index++;
            $read_status = $memo['is_read'] ? 'read' : 'unread';
            $priority_badge = getPriorityBadge($memo['priority']);
            
            echo '<div class="list-group-item border-0 py-3 ' . ($memo['is_read'] ? '' : 'bg-light-subtle') . '">';
            echo '<div class="d-flex align-items-start">';
            
            // Memo index
            echo '<div class="badge bg-light text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: 600;">';
            echo $index;
            echo '</div>';
            
            echo '<div class="flex-grow-1 min-w-0">';
            echo '<div class="d-flex align-items-center justify-content-between mb-2">';
            echo '<h6 class="mb-0 text-truncate ' . ($memo['is_read'] ? 'text-dark' : 'fw-bold text-dark') . '">';
            echo htmlspecialchars($memo['subject']);
            echo '</h6>';
            echo '<div class="ms-2">' . $priority_badge . '</div>';
            echo '</div>';
            
            echo '<p class="text-muted mb-2 small">' . htmlspecialchars(substr($memo['content'], 0, 120)) . '...</p>';
            
            echo '<div class="d-flex align-items-center justify-content-between">';
            echo '<div class="text-muted small d-flex align-items-center gap-2">';
            echo '<i class="fas fa-user"></i>';
            echo '<span>From: ' . htmlspecialchars($memo['first_name'] . ' ' . $memo['last_name']) . '</span>';
            echo '<span>•</span>';
            echo '<i class="fas fa-calendar"></i>';
            echo '<span>' . date('M j, g:i A', strtotime($memo['created_at'])) . '</span>';
            echo '</div>';
            
            echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo['memo_id'] . '" class="btn btn-sm btn-primary">View</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-5">';
        echo '<i class="fas fa-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>';
        echo '<h5 class="text-muted mt-3">No memos yet</h5>';
        echo '<p class="text-muted">New messages will appear here when they arrive.</p>';
        echo '</div>';
    }
    
    echo '</div></div></div>';
    
    // Quick actions sidebar
    echo '<div class="col-lg-4">';
    echo '<div class="card border-0 shadow-sm h-100">';
    echo '<div class="card-header bg-white border-bottom-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-bolt text-warning"></i>';
    echo '<span>Quick Actions</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    echo '<div class="d-grid gap-3">';
    echo '<a href="dologin.php?op=compose" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2 py-3">';
    echo '<i class="fas fa-plus-circle"></i>';
    echo '<span>Compose New Memo</span>';
    echo '</a>';
    
    echo '<a href="dologin.php?op=inbox" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 py-2">';
    echo '<i class="fas fa-inbox"></i>';
    echo '<span>View Inbox</span>';
    if ($unread_count > 0) {
        echo '<span class="badge bg-danger ms-auto">' . $unread_count . '</span>';
    }
    echo '</a>';
    
    echo '<a href="dologin.php?op=listcontacts" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 py-2">';
    echo '<i class="fas fa-address-book"></i>';
    echo '<span>Staff Directory</span>';
    echo '</a>';
    echo '</div>';
    
    // System status
    echo '<hr class="my-4">';
    echo '<h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">System Status</h6>';
    echo '<div class="small">';
    echo '<div class="d-flex align-items-center justify-content-between mb-2">';
    echo '<span class="text-muted">Connection</span>';
    echo '<span class="badge bg-success-subtle text-success">Online</span>';
    echo '</div>';
    echo '<div class="d-flex align-items-center justify-content-between mb-2">';
    echo '<span class="text-muted">Last Login</span>';
    echo '<span class="text-dark">' . date('M j, g:i A') . '</span>';
    echo '</div>';
    echo '<div class="d-flex align-items-center justify-content-between">';
    echo '<span class="text-muted">System Version</span>';
    echo '<span class="text-dark">v2.0</span>';
    echo '</div>';
    echo '</div>';
    
    echo '</div></div></div>';
    echo '</div>'; // End content row
    echo '</div>'; // End container
    
    // Custom CSS for dashboard
    echo '<style>
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            position: relative;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        }
        .stat-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }
        .bg-light-subtle {
            background-color: rgba(13, 71, 161, 0.02) !important;
        }
        .priority-pulse {
            animation: priorityPulse 2s infinite;
        }
        @keyframes priorityPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .badge-priority-urgent {
            background: linear-gradient(135deg, #FF5252, #D32F2F);
            color: white;
            border: 1px solid rgba(211, 47, 47, 0.3);
        }
        .badge-priority-high {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
            border: 1px solid rgba(245, 124, 0, 0.3);
        }
        .badge-priority-normal {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            border: 1px solid rgba(25, 118, 210, 0.3);
        }
        .badge-priority-low {
            background: linear-gradient(135deg, #9E9E9E, #616161);
            color: white;
            border: 1px solid rgba(97, 97, 97, 0.3);
        }
    </style>';
}

// Function to get enhanced priority badge with better readability
function getPriorityBadge($priority) {
    global $connection;
    
    // Try to get priority info from database
    $stmt = mysqli_prepare($connection, "SELECT * FROM memo_priorities WHERE name = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $priority);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $priority_info = mysqli_fetch_assoc($result);
        
        if ($priority_info) {
            $response_time = $priority_info['response_time_hours'];
            $badge_class = 'badge-priority-normal';
            $icon = 'fas fa-circle-info';
            $pulse_class = '';
            
            switch ($priority) {
                case 'urgent': 
                    $badge_class = 'badge-priority-urgent';
                    $icon = 'fas fa-exclamation-triangle';
                    $pulse_class = 'priority-pulse';
                    break;
                case 'high': 
                    $badge_class = 'badge-priority-high';
                    $icon = 'fas fa-exclamation-circle';
                    break;
                case 'normal': 
                    $badge_class = 'badge-priority-normal';
                    $icon = 'fas fa-circle-info';
                    break;
                case 'low': 
                    $badge_class = 'badge-priority-low';
                    $icon = 'fas fa-clock';
                    break;
            }
            
            return '<span class="badge ' . $badge_class . ' ' . $pulse_class . ' d-inline-flex align-items-center gap-1" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    title="Expected response: ' . $response_time . ' hours"
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">' . 
                   '<i class="' . $icon . '" style="font-size: 0.7rem;"></i>' .
                   '<span>' . ucfirst($priority_info['name']) . '</span>' .
                   '</span>';
        }
    }
    
    // Enhanced fallback with consistent styling
    switch ($priority) {
        case 'urgent':
            return '<span class="badge badge-priority-urgent priority-pulse d-inline-flex align-items-center gap-1" 
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 0.7rem;"></i>
                    <span>Urgent</span>
                    </span>';
        case 'high':
            return '<span class="badge badge-priority-high d-inline-flex align-items-center gap-1" 
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 0.7rem;"></i>
                    <span>High</span>
                    </span>';
        case 'normal':
            return '<span class="badge badge-priority-normal d-inline-flex align-items-center gap-1" 
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">
                    <i class="fas fa-circle-info" style="font-size: 0.7rem;"></i>
                    <span>Normal</span>
                    </span>';
        case 'low':
            return '<span class="badge badge-priority-low d-inline-flex align-items-center gap-1" 
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">
                    <i class="fas fa-clock" style="font-size: 0.7rem;"></i>
                    <span>Low</span>
                    </span>';
        default:
            return '<span class="badge badge-priority-normal d-inline-flex align-items-center gap-1" 
                    style="font-size: 0.75rem; padding: 0.5rem 0.75rem; font-weight: 500; border-radius: 20px;">
                    <i class="fas fa-circle-info" style="font-size: 0.7rem;"></i>
                    <span>Normal</span>
                    </span>';
    }
}

// Function to show user inbox
function showInbox($uid) {
    global $connection;
    
    echo '<div class="container-fluid">';
    echo '<h2>📥 My Inbox</h2>';
    
    $result = mysqli_query($connection, "
        SELECT m.*, u.first_name, u.last_name, mc.category_name, mr.is_read, mr.read_at, mr.acknowledged_at
        FROM memos m 
        JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        JOIN users u ON m.sender_id = u.user_id 
        LEFT JOIN memo_categories mc ON m.category_id = mc.category_id 
        WHERE mr.user_id = $uid 
        ORDER BY m.created_at DESC
    ");
    
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover">';
        echo '<thead class="table-dark">';
        echo '<tr>';
        echo '<th>Status</th>';
        echo '<th>Subject</th>';
        echo '<th>From</th>';
        echo '<th>Category</th>';
        echo '<th>Priority</th>';
        echo '<th>Date</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($memo = mysqli_fetch_assoc($result)) {
            $read_class = $memo['is_read'] ? '' : 'table-warning';
            
            echo '<tr class="' . $read_class . '">';
            
            // Status
            echo '<td>';
            if (!$memo['is_read']) {
                echo '<span class="badge bg-primary">New</span>';
            } else {
                echo '<span class="badge bg-success">Read</span>';
            }
            if ($memo['requires_acknowledgment'] && !$memo['acknowledged_at']) {
                echo '<br><span class="badge bg-warning mt-1">Ack Required</span>';
            }
            echo '</td>';
            
            // Subject
            echo '<td><strong>' . htmlspecialchars($memo['subject']) . '</strong></td>';
            
            // From
            echo '<td>' . htmlspecialchars($memo['first_name'] . ' ' . $memo['last_name']) . '</td>';
            
            // Category
            echo '<td>' . htmlspecialchars($memo['category_name'] ?? 'General') . '</td>';
            
            // Priority
            echo '<td>' . getPriorityBadge($memo['priority']) . '</td>';
            
            // Date
            echo '<td>' . date('M j, Y g:i A', strtotime($memo['created_at'])) . '</td>';
            
            // Actions
            echo '<td>';
            echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo['memo_id'] . '" class="btn btn-sm btn-primary">View</a>';
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">No memos in your inbox yet.</div>';
    }
    
    echo '</div>';
}

// Function to add new users (enhanced for new structure)
function adminusers($username, $fname, $lname, $password, $action) {
    global $connection;
    
    if ($action == 1) {
        // Verify and commit to database
        $flag = 0;
        $error = "";
        
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $role_id = !empty($_POST['role_id']) ? $_POST['role_id'] : null;
        $position = trim($_POST['position'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        if (empty($username)) { $error .= "<li>Username not provided"; $flag = 1; }
        if (empty($fname)) { $error .= "<li>First name not provided"; $flag = 1; }
        if (empty($lname)) { $error .= "<li>Last name not provided"; $flag = 1; }
        if (empty($password) || strlen($password) < 8) { $error .= "<li>Password should be at least 8 characters"; $flag = 1; }
        
        // Check if username already exists
        $check_stmt = mysqli_prepare($connection, "SELECT user_id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
            $error .= "<li>Username already exists"; $flag = 1;
        }
        
        if ($flag) {
            error("ERROR: The following error(s) occurred:<ul>$error</ul>");
            back();
            return;
        }
        
        // Hash password properly
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = mysqli_prepare($connection, "
            INSERT INTO users (username, password, first_name, last_name, department_id, role_id, position, email, phone, employee_id, is_admin, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        mysqli_stmt_bind_param($stmt, "ssssiissssi", $username, $hashed_password, $fname, $lname, $department_id, $role_id, $position, $email, $phone, $employee_id, $is_admin);
        
        if (mysqli_stmt_execute($stmt)) {
            echo '<div class="alert alert-success">';
            echo '<h4>✅ User Added Successfully!</h4>';
            echo '<strong>Username:</strong> ' . htmlspecialchars($username) . '<br>';
            echo '<strong>Name:</strong> ' . htmlspecialchars($fname . ' ' . $lname) . '<br>';
            if ($department_id) {
                $dept_result = mysqli_query($connection, "SELECT department_name FROM departments WHERE department_id = $department_id");
                $dept = mysqli_fetch_assoc($dept_result);
                echo '<strong>Department:</strong> ' . htmlspecialchars($dept['department_name']) . '<br>';
            }
            if ($role_id) {
                $role_result = mysqli_query($connection, "SELECT role_name FROM user_roles WHERE role_id = $role_id");
                $role = mysqli_fetch_assoc($role_result);
                echo '<strong>Role:</strong> ' . htmlspecialchars($role['role_name']) . '<br>';
            }
            echo '</div>';
            echo '<a href="dologin.php?op=listcontacts" class="btn btn-primary">View All Users</a> ';
            echo '<a href="dologin.php?op=adminusers" class="btn btn-success">Add Another User</a>';
        } else {
            error("Error adding user: " . mysqli_error($connection));
        }
    } else {
        // Show the enhanced form
        echo '<div class="container-fluid">';
        echo '<h2>👤 Add New User</h2>';
        echo '<div class="row">';
        echo '<div class="col-md-8">';
        echo '<div class="card">';
        echo '<div class="card-body">';
        
        echo '<form method="post" action="dologin.php">';
        echo '<input type="hidden" name="op" value="adminusers">';
        echo '<input type="hidden" name="action" value="1">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Username *</label>';
        echo '<input type="text" name="username" class="form-control" required maxlength="50">';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Employee ID</label>';
        echo '<input type="text" name="employee_id" class="form-control" maxlength="20" placeholder="e.g., EMP001">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">First Name *</label>';
        echo '<input type="text" name="fname" class="form-control" required maxlength="50">';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Last Name *</label>';
        echo '<input type="text" name="lname" class="form-control" required maxlength="50">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Department</label>';
        echo '<select name="department_id" class="form-select">';
        echo '<option value="">Select Department</option>';
        $dept_result = mysqli_query($connection, "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name");
        while ($dept = mysqli_fetch_assoc($dept_result)) {
            echo '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Role</label>';
        echo '<select name="role_id" class="form-select">';
        echo '<option value="">Select Role</option>';
        $role_result = mysqli_query($connection, "SELECT * FROM user_roles ORDER BY role_name");
        while ($role = mysqli_fetch_assoc($role_result)) {
            echo '<option value="' . $role['role_id'] . '">' . htmlspecialchars($role['role_name']) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Position/Title</label>';
        echo '<input type="text" name="position" class="form-control" maxlength="100" placeholder="e.g., Senior Nurse, Cardiologist">';
        echo '</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Email</label>';
        echo '<input type="email" name="email" class="form-control" maxlength="100">';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Phone</label>';
        echo '<input type="text" name="phone" class="form-control" maxlength="20">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Password *</label>';
        echo '<input type="password" name="password" class="form-control" required minlength="8">';
        echo '<div class="form-text">Password must be at least 8 characters long.</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<div class="form-check">';
        echo '<input class="form-check-input" type="checkbox" name="is_admin" id="is_admin">';
        echo '<label class="form-check-label" for="is_admin">';
        echo '🔐 Administrator privileges';
        echo '</label>';
        echo '<div class="form-text">Administrators can manage users, departments, and system settings.</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<button type="submit" class="btn btn-primary">Add User</button>';
        echo '<a href="dologin.php?op=listcontacts" class="btn btn-secondary ms-2">Cancel</a>';
        
        echo '</form>';
        echo '</div></div></div>';
        
        // Quick help
        echo '<div class="col-md-4">';
        echo '<div class="card">';
        echo '<div class="card-header"><h6>💡 Quick Help</h6></div>';
        echo '<div class="card-body">';
        echo '<h6>User Roles:</h6>';
        
        $help_roles = mysqli_query($connection, "SELECT role_name, description, can_send_memos, can_receive_memos FROM user_roles ORDER BY role_name");
        while ($role = mysqli_fetch_assoc($help_roles)) {
            echo '<div class="mb-2">';
            echo '<strong>' . htmlspecialchars($role['role_name']) . '</strong><br>';
            if ($role['description']) {
                echo '<small class="text-muted">' . htmlspecialchars($role['description']) . '</small><br>';
            }
            if ($role['can_send_memos']) {
                echo '<span class="badge bg-success me-1">📤 Send</span>';
            }
            if ($role['can_receive_memos']) {
                echo '<span class="badge bg-info">📥 Receive</span>';
            }
            echo '</div><hr>';
        }
        
        echo '</div></div></div>';
        echo '</div></div>';
    }
}

// Function to list contacts/users
function listContacts($uid) {
    global $connection;
    
    echo '<div class="container-fluid">';
    echo '<h2>👥 Staff Directory</h2>';
    
    $result = mysqli_query($connection, "
        SELECT u.*, d.department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        WHERE u.is_active = 1 
        ORDER BY u.last_name, u.first_name
    ");
    
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="row">';
        
        while ($user = mysqli_fetch_assoc($result)) {
            echo '<div class="col-md-6 col-lg-4 mb-3">';
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo '<h6 class="card-title">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</h6>';
            echo '<p class="card-text">';
            echo '<strong>Username:</strong> ' . htmlspecialchars($user['username']) . '<br>';
            if ($user['department_name']) {
                echo '<strong>Department:</strong> ' . htmlspecialchars($user['department_name']) . '<br>';
            }
            if ($user['position']) {
                echo '<strong>Position:</strong> ' . htmlspecialchars($user['position']) . '<br>';
            }
            if ($user['email']) {
                echo '<strong>Email:</strong> ' . htmlspecialchars($user['email']) . '<br>';
            }
            if ($user['is_admin']) {
                echo '<span class="badge bg-warning">Administrator</span>';
            }
            echo '</p>';
            echo '<a href="dologin.php?op=compose&to_user=' . $user['user_id'] . '" class="btn btn-sm btn-primary">Send Memo</a>';
            echo '</div></div></div>';
        }
        
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">No users found.</div>';
    }
    
    echo '</div>';
}

// Function to show compose memo form
function showCompose($uid) {
    global $connection;
    
    // Check if user can send memos
    $user_info = getUserInfo($uid);
    if (!$user_info['can_send_memos']) {
        echo '<div class="container-fluid">';
        echo '<div class="alert alert-warning">';
        echo '<h4>⚠️ Access Restricted</h4>';
        echo 'Your role (' . htmlspecialchars($user_info['role_name']) . ') does not have permission to send memos.';
        echo '<br><br>Please contact your administrator if you need sending privileges.';
        echo '</div>';
        echo '<a href="dologin.php?op=dashboard" class="btn btn-secondary">← Back to Dashboard</a>';
        echo '</div>';
        return;
    }
    
    echo '<div class="container-fluid">';
    echo '<h2>✏️ Compose New Memo</h2>';
    
    if ($_POST['action'] ?? '' === 'send_memo') {
        // Process memo sending with enhanced distribution
        $subject = $_POST['subject'] ?? '';
        $content = $_POST['content'] ?? '';
        $category_id = $_POST['category_id'] ?? 1;
        $priority = $_POST['priority'] ?? 'normal';
        $recipients = $_POST['recipients'] ?? [];
        $recipient_departments = $_POST['recipient_departments'] ?? [];
        $recipient_roles = $_POST['recipient_roles'] ?? [];
        $requires_ack = isset($_POST['requires_acknowledgment']) ? 1 : 0;
        $is_confidential = isset($_POST['is_confidential']) ? 1 : 0;
        $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
        
        if (!empty($subject) && !empty($content) && (!empty($recipients) || !empty($recipient_departments) || !empty($recipient_roles))) {
            $memo_number = generateMemoNumber();
            
            // Get sender's department
            $sender_dept_id = $user_info['department_id'];
            
            // Insert memo with enhanced fields
            $stmt = mysqli_prepare($connection, "
                INSERT INTO memos (memo_number, subject, content, sender_id, sender_department_id, category_id, priority, requires_acknowledgment, is_confidential, expiration_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')
            ");
            mysqli_stmt_bind_param($stmt, "sssiiissis", $memo_number, $subject, $content, $uid, $sender_dept_id, $category_id, $priority, $requires_ack, $is_confidential, $expiration_date);
            
            if (mysqli_stmt_execute($stmt)) {
                $memo_id = mysqli_insert_id($connection);
                
                // Enhanced distribution system
                $total_recipients = 0;
                
                // Individual user recipients
                foreach ($recipients as $recipient_id) {
                    $dist_stmt = mysqli_prepare($connection, "INSERT INTO memo_distribution (memo_id, recipient_type, recipient_id) VALUES (?, 'USER', ?)");
                    mysqli_stmt_bind_param($dist_stmt, "ii", $memo_id, $recipient_id);
                    mysqli_stmt_execute($dist_stmt);
                    $distribution_id = mysqli_insert_id($connection);
                    
                    // Create receipt record
                    $receipt_stmt = mysqli_prepare($connection, "INSERT INTO memo_receipts (distribution_id, received_by_user_id, received_at, status_id) VALUES (?, ?, NOW(), 2)");
                    mysqli_stmt_bind_param($receipt_stmt, "ii", $distribution_id, $recipient_id);
                    mysqli_stmt_execute($receipt_stmt);
                    
                    // Also add to legacy memo_recipients for backward compatibility
                    $legacy_stmt = mysqli_prepare($connection, "INSERT INTO memo_recipients (memo_id, user_id) VALUES (?, ?)");
                    mysqli_stmt_bind_param($legacy_stmt, "ii", $memo_id, $recipient_id);
                    mysqli_stmt_execute($legacy_stmt);
                    
                    $total_recipients++;
                }
                
                // Department recipients
                foreach ($recipient_departments as $dept_id) {
                    $dist_stmt = mysqli_prepare($connection, "INSERT INTO memo_distribution (memo_id, recipient_type, recipient_id) VALUES (?, 'DEPARTMENT', ?)");
                    mysqli_stmt_bind_param($dist_stmt, "ii", $memo_id, $dept_id);
                    mysqli_stmt_execute($dist_stmt);
                    $distribution_id = mysqli_insert_id($connection);
                    
                    // Get all users in department and create receipt records
                    $dept_users_result = mysqli_query($connection, "
                        SELECT u.user_id FROM users u 
                        JOIN user_roles ur ON u.role_id = ur.role_id 
                        WHERE u.department_id = $dept_id AND u.is_active = 1 AND ur.can_receive_memos = 1
                    ");
                    while ($dept_user = mysqli_fetch_assoc($dept_users_result)) {
                        $receipt_stmt = mysqli_prepare($connection, "INSERT INTO memo_receipts (distribution_id, received_by_user_id, received_at, status_id) VALUES (?, ?, NOW(), 2)");
                        mysqli_stmt_bind_param($receipt_stmt, "ii", $distribution_id, $dept_user['user_id']);
                        mysqli_stmt_execute($receipt_stmt);
                        
                        // Legacy compatibility
                        $legacy_stmt = mysqli_prepare($connection, "INSERT IGNORE INTO memo_recipients (memo_id, user_id) VALUES (?, ?)");
                        mysqli_stmt_bind_param($legacy_stmt, "ii", $memo_id, $dept_user['user_id']);
                        mysqli_stmt_execute($legacy_stmt);
                        
                        $total_recipients++;
                    }
                }
                
                // Role recipients
                foreach ($recipient_roles as $role_id) {
                    $dist_stmt = mysqli_prepare($connection, "INSERT INTO memo_distribution (memo_id, recipient_type, recipient_id) VALUES (?, 'ROLE', ?)");
                    mysqli_stmt_bind_param($dist_stmt, "ii", $memo_id, $role_id);
                    mysqli_stmt_execute($dist_stmt);
                    $distribution_id = mysqli_insert_id($connection);
                    
                    // Get all users with role and create receipt records
                    $role_users_result = mysqli_query($connection, "
                        SELECT u.user_id FROM users u 
                        JOIN user_roles ur ON u.role_id = ur.role_id 
                        WHERE u.role_id = $role_id AND u.is_active = 1 AND ur.can_receive_memos = 1
                    ");
                    while ($role_user = mysqli_fetch_assoc($role_users_result)) {
                        $receipt_stmt = mysqli_prepare($connection, "INSERT INTO memo_receipts (distribution_id, received_by_user_id, received_at, status_id) VALUES (?, ?, NOW(), 2)");
                        mysqli_stmt_bind_param($receipt_stmt, "ii", $distribution_id, $role_user['user_id']);
                        mysqli_stmt_execute($receipt_stmt);
                        
                        // Legacy compatibility
                        $legacy_stmt = mysqli_prepare($connection, "INSERT IGNORE INTO memo_recipients (memo_id, user_id) VALUES (?, ?)");
                        mysqli_stmt_bind_param($legacy_stmt, "ii", $memo_id, $role_user['user_id']);
                        mysqli_stmt_execute($legacy_stmt);
                        
                        $total_recipients++;
                    }
                }
                
                // Auto-generate PDF after successful memo sending
                require_once 'pdf_generator.php';
                $pdf_result = autoGeneratePDFAfterSend($memo_id, $uid);
                
                echo '<div class="alert alert-success">';
                echo '<h4>✅ Memo Sent Successfully!</h4>';
                echo '<strong>Memo Number:</strong> ' . htmlspecialchars($memo_number) . '<br>';
                echo '<strong>Recipients:</strong> ' . $total_recipients . ' users<br>';
                echo '<strong>Priority:</strong> ' . getPriorityBadge($priority) . '<br>';
                if ($is_confidential) {
                    echo '<strong>Classification:</strong> <span class="badge bg-danger">🔒 Confidential</span><br>';
                }
                if ($expiration_date) {
                    echo '<strong>Expires:</strong> ' . date('M j, Y', strtotime($expiration_date));
                }
                
                // Show PDF generation status
                if ($pdf_result['success']) {
                    echo '<br><strong>📄 PDF Generated:</strong> <a href="' . $pdf_result['download_url'] . '" target="_blank" class="btn btn-sm btn-outline-primary ms-2">📥 Download PDF</a>';
                } else {
                    echo '<br><strong>⚠️ PDF Generation:</strong> <span class="text-warning">Failed - ' . htmlspecialchars($pdf_result['message']) . '</span>';
                }
                
                // Handle file attachments
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    $upload_dir = 'attachments/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $uploaded_files = 0;
                    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
                    $max_size = 10 * 1024 * 1024; // 10MB
                    
                    foreach ($_FILES['attachments']['name'] as $key => $filename) {
                        if (!empty($filename) && $_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_info = pathinfo($filename);
                            $extension = strtolower($file_info['extension'] ?? '');
                            
                            if (in_array($extension, $allowed_extensions) && $_FILES['attachments']['size'][$key] <= $max_size) {
                                $unique_name = uniqid() . '_' . time() . '.' . $extension;
                                $file_path = $upload_dir . $unique_name;
                                
                                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $file_path)) {
                                    $attach_stmt = mysqli_prepare($connection, "
                                        INSERT INTO memo_attachments (memo_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    
                                    mysqli_stmt_bind_param($attach_stmt, "isssisi", 
                                        $memo_id, $unique_name, $filename, $file_path, 
                                        $_FILES['attachments']['size'][$key], $_FILES['attachments']['type'][$key], $uid
                                    );
                                    
                                    if (mysqli_stmt_execute($attach_stmt)) {
                                        $uploaded_files++;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($uploaded_files > 0) {
                        echo '<br><strong>Attachments:</strong> ' . $uploaded_files . ' file(s) uploaded';
                    }
                }
                
                echo '</div>';
                echo '<a href="dologin.php?op=dashboard" class="btn btn-primary">Return to Dashboard</a>';
                echo '</div>';
                return;
            } else {
                echo '<div class="alert alert-danger">Error sending memo. Please try again.</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Please fill in all required fields and select at least one recipient.</div>';
        }
    }
    
    // Show enhanced compose form with modern UI/UX
    echo '<div class="container-fluid fade-in">';
    echo '<div class="row justify-content-center">';
    echo '<div class="col-lg-10 col-xl-8">';
    
    // Header section
    echo '<div class="d-flex align-items-center mb-4">';
    echo '<div class="me-3">';
    echo '<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">';
    echo '<i class="fas fa-pen text-white"></i>';
    echo '</div>';
    echo '</div>';
    echo '<div>';
    echo '<h2 class="display-6 fw-bold text-dark mb-1">Compose New Memo</h2>';
    echo '<p class="text-muted mb-0">Send important information to your colleagues</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<form method="post" action="dologin.php?op=compose" enctype="multipart/form-data" class="needs-validation" novalidate>';
    echo '<input type="hidden" name="action" value="send_memo">';
    
    // Main content card
    echo '<div class="card border-0 shadow-sm mb-4">';
    echo '<div class="card-body p-4">';
    
    echo '<div class="row g-4">';
    
    // Subject
    echo '<div class="col-12">';
    echo '<label for="subject" class="form-label fw-semibold text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-tag text-primary"></i>';
    echo '<span>Subject</span>';
    echo '<span class="text-danger">*</span>';
    echo '</label>';
    echo '<input type="text" name="subject" class="form-control form-control-lg" id="subject" required maxlength="255" placeholder="Enter memo subject...">';
    echo '<div class="invalid-feedback">Please provide a subject for your memo.</div>';
    echo '</div>';
    
    // Content
    echo '<div class="col-12">';
    echo '<label for="content" class="form-label fw-semibold text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-align-left text-primary"></i>';
    echo '<span>Message Content</span>';
    echo '<span class="text-danger">*</span>';
    echo '</label>';
    echo '<textarea name="content" class="form-control" id="content" rows="8" required placeholder="Type your message here..."></textarea>';
    echo '<div class="invalid-feedback">Please enter the memo content.</div>';
    echo '<div class="form-text text-muted mt-2">';
    echo '<i class="fas fa-info-circle me-1"></i>';
    echo 'Tip: Use clear and concise language. Consider using bullet points for better readability.';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End row
    echo '</div></div>'; // End main content card
    
    // Options and recipients in a row
    echo '<div class="row g-4 mb-4">';
    
    // Options card
    echo '<div class="col-lg-6">';
    echo '<div class="card border-0 shadow-sm h-100">';
    echo '<div class="card-header bg-light border-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-cogs text-primary"></i>';
    echo '<span>Options</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    // Category and Priority
    echo '<div class="row g-3 mb-3">';
    echo '<div class="col-12">';
    echo '<label for="category_id" class="form-label fw-medium text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-folder text-info"></i>';
    echo '<span>Category</span>';
    echo '</label>';
    echo '<select name="category_id" class="form-select" id="category_id">';
    $cat_result = mysqli_query($connection, "SELECT * FROM memo_categories WHERE is_active = 1 ORDER BY category_name");
    while ($cat = mysqli_fetch_assoc($cat_result)) {
        echo '<option value="' . $cat['category_id'] . '">' . htmlspecialchars($cat['category_name']) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div class="col-12">';
    echo '<label for="priority" class="form-label fw-medium text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-exclamation-triangle text-warning"></i>';
    echo '<span>Priority Level</span>';
    echo '</label>';
    echo '<select name="priority" class="form-select" id="priority">';
    // Use priority table if available, fallback to hardcoded
    $priority_result = mysqli_query($connection, "SELECT * FROM memo_priorities ORDER BY response_time_hours ASC");
    if (mysqli_num_rows($priority_result) > 0) {
        while ($priority = mysqli_fetch_assoc($priority_result)) {
            $selected = $priority['name'] == 'normal' ? 'selected' : '';
            $emoji = $priority['name'] == 'urgent' ? '🔴' : ($priority['name'] == 'high' ? '🟡' : ($priority['name'] == 'low' ? '⚪' : '🔵'));
            echo '<option value="' . $priority['name'] . '" ' . $selected . '>';
            echo $emoji . ' ' . ucfirst($priority['name']);
            echo ' (' . $priority['response_time_hours'] . 'h response)</option>';
        }
    } else {
        // Fallback
        echo '<option value="low">⚪ Low Priority</option>';
        echo '<option value="normal" selected>🔵 Normal Priority</option>';
        echo '<option value="high">🟡 High Priority</option>';
        echo '<option value="urgent">🔴 Urgent Priority</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '</div>';
    
    // Checkboxes
    echo '<div class="row g-3 mb-3">';
    echo '<div class="col-12">';
    echo '<div class="form-check form-switch">';
    echo '<input class="form-check-input" type="checkbox" name="requires_acknowledgment" id="requires_ack">';
    echo '<label class="form-check-label fw-medium text-dark d-flex align-items-center gap-2" for="requires_ack">';
    echo '<i class="fas fa-check-circle text-success"></i>';
    echo '<span>Require Acknowledgment</span>';
    echo '</label>';
    echo '<div class="form-text text-muted">Recipients must acknowledge receipt</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-12">';
    echo '<div class="form-check form-switch">';
    echo '<input class="form-check-input" type="checkbox" name="is_confidential" id="is_confidential">';
    echo '<label class="form-check-label fw-medium text-dark d-flex align-items-center gap-2" for="is_confidential">';
    echo '<i class="fas fa-lock text-warning"></i>';
    echo '<span>Mark as Confidential</span>';
    echo '</label>';
    echo '<div class="form-text text-muted">Contains sensitive information</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Expiration and attachments
    echo '<div class="mb-3">';
    echo '<label for="expiration_date" class="form-label fw-medium text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-calendar-times text-secondary"></i>';
    echo '<span>Expiration Date</span>';
    echo '</label>';
    echo '<input type="date" name="expiration_date" class="form-control" id="expiration_date" min="' . date('Y-m-d') . '">';
    echo '<div class="form-text text-muted">Leave blank if memo does not expire</div>';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label for="attachments" class="form-label fw-medium text-dark d-flex align-items-center gap-2">';
    echo '<i class="fas fa-paperclip text-secondary"></i>';
    echo '<span>File Attachments</span>';
    echo '</label>';
    echo '<input type="file" name="attachments[]" class="form-control" id="attachments" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif">';
    echo '<div class="form-text text-muted mt-2">';
    echo '<i class="fas fa-info-circle me-1"></i>';
    echo 'Allowed: PDF, Office docs, images. Max 10MB per file.';
    echo '</div>';
    echo '</div>';
    
    echo '</div></div></div>';
    
    // Recipients card
    echo '<div class="col-lg-6">';
    echo '<div class="card border-0 shadow-sm h-100">';
    echo '<div class="card-header bg-light border-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-users text-primary"></i>';
    echo '<span>Recipients</span>';
    echo '<span class="text-danger">*</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body p-0">';
    
    // Bootstrap tabs for different recipient types
    echo '<ul class="nav nav-pills nav-fill border-bottom" role="tablist">';
    echo '<li class="nav-item"><a class="nav-link active rounded-0 border-0" data-bs-toggle="pill" href="#users-tab"><i class="fas fa-user me-1"></i>Users</a></li>';
    echo '<li class="nav-item"><a class="nav-link rounded-0 border-0" data-bs-toggle="pill" href="#departments-tab"><i class="fas fa-building me-1"></i>Departments</a></li>';
    echo '<li class="nav-item"><a class="nav-link rounded-0 border-0" data-bs-toggle="pill" href="#roles-tab"><i class="fas fa-user-tag me-1"></i>Roles</a></li>';
    echo '</ul>';
    
    echo '<div class="tab-content p-3">';
    
    // Individual users tab
    echo '<div class="tab-pane fade show active" id="users-tab">';
    echo '<div class="recipients-scroll" style="max-height: 300px; overflow-y: auto;">';
    $user_result = mysqli_query($connection, "
        SELECT u.*, d.department_name, ur.role_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN user_roles ur ON u.role_id = ur.role_id
        WHERE u.is_active = 1 AND u.user_id != $uid AND ur.can_receive_memos = 1
        ORDER BY d.department_name, u.last_name, u.first_name
    ");
    
    $current_dept = '';
    while ($user = mysqli_fetch_assoc($user_result)) {
        if ($user['department_name'] != $current_dept) {
            if ($current_dept != '') echo '</div>';
            $current_dept = $user['department_name'] ?? 'No Department';
            echo '<h6 class="mt-3 mb-2 text-primary fw-semibold border-bottom pb-1">' . htmlspecialchars($current_dept) . '</h6>';
            echo '<div>';
        }
        
        echo '<div class="form-check mb-2">';
        echo '<input class="form-check-input" type="checkbox" name="recipients[]" value="' . $user['user_id'] . '" id="user_' . $user['user_id'] . '">';
        echo '<label class="form-check-label d-flex align-items-center gap-2 w-100" for="user_' . $user['user_id'] . '">';
        echo '<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 600;">';
        echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
        echo '</div>';
        echo '<div class="flex-grow-1">';
        echo '<div class="fw-medium text-dark">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</div>';
        echo '<small class="text-muted">' . htmlspecialchars($user['role_name']) . '</small>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
    }
    echo '</div></div></div>';
    
    // Departments tab
    echo '<div class="tab-pane fade" id="departments-tab">';
    echo '<div class="recipients-scroll" style="max-height: 300px; overflow-y: auto;">';
    $dept_result = mysqli_query($connection, "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name");
    while ($dept = mysqli_fetch_assoc($dept_result)) {
        // Count users in department
        $count_result = mysqli_query($connection, "
            SELECT COUNT(*) as count FROM users u 
            JOIN user_roles ur ON u.role_id = ur.role_id 
            WHERE u.department_id = " . $dept['department_id'] . " AND u.is_active = 1 AND ur.can_receive_memos = 1
        ");
        $user_count = mysqli_fetch_assoc($count_result)['count'];
        
        echo '<div class="form-check mb-3">';
        echo '<input class="form-check-input" type="checkbox" name="recipient_departments[]" value="' . $dept['department_id'] . '" id="dept_' . $dept['department_id'] . '">';
        echo '<label class="form-check-label d-flex align-items-center gap-2 w-100" for="dept_' . $dept['department_id'] . '">';
        echo '<i class="fas fa-building text-primary"></i>';
        echo '<div class="flex-grow-1">';
        echo '<div class="fw-medium text-dark">' . htmlspecialchars($dept['department_name']) . '</div>';
        if ($dept['code']) {
            echo '<small class="text-muted">Code: ' . htmlspecialchars($dept['code']) . '</small><br>';
        }
        echo '<small class="text-info"><i class="fas fa-users me-1"></i>' . $user_count . ' recipients</small>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
    }
    echo '</div></div>';
    
    // Roles tab
    echo '<div class="tab-pane fade" id="roles-tab">';
    echo '<div class="recipients-scroll" style="max-height: 300px; overflow-y: auto;">';
    $role_result = mysqli_query($connection, "SELECT * FROM user_roles WHERE can_receive_memos = 1 ORDER BY role_name");
    while ($role = mysqli_fetch_assoc($role_result)) {
        // Count users with role
        $count_result = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role_id = " . $role['role_id'] . " AND is_active = 1");
        $user_count = mysqli_fetch_assoc($count_result)['count'];
        
        echo '<div class="form-check mb-3">';
        echo '<input class="form-check-input" type="checkbox" name="recipient_roles[]" value="' . $role['role_id'] . '" id="role_' . $role['role_id'] . '">';
        echo '<label class="form-check-label d-flex align-items-center gap-2 w-100" for="role_' . $role['role_id'] . '">';
        echo '<i class="fas fa-user-tag text-primary"></i>';
        echo '<div class="flex-grow-1">';
        echo '<div class="fw-medium text-dark">' . htmlspecialchars($role['role_name']) . '</div>';
        if ($role['description']) {
            echo '<small class="text-muted">' . htmlspecialchars($role['description']) . '</small><br>';
        }
        echo '<small class="text-info"><i class="fas fa-users me-1"></i>' . $user_count . ' recipients</small>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
    }
    echo '</div></div>';
    
    echo '</div>'; // End tab content
    echo '</div></div></div>';
    
    echo '</div>'; // End recipients/options row
    
    // Action buttons
    echo '<div class="card border-0 shadow-sm">';
    echo '<div class="card-body p-4">';
    echo '<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">';
    
    echo '<div class="d-flex align-items-center gap-2 text-muted">';
    echo '<i class="fas fa-lightbulb text-warning"></i>';
    echo '<span>Review your memo before sending to ensure accuracy</span>';
    echo '</div>';
    
    echo '<div class="d-flex gap-2">';
    echo '<a href="dologin.php?op=dashboard" class="btn btn-outline-secondary px-4">';
    echo '<i class="fas fa-arrow-left me-1"></i>Cancel';
    echo '</a>';
    echo '<button type="submit" class="btn btn-primary px-4">';
    echo '<i class="fas fa-paper-plane me-1"></i>Send Memo';
    echo '</button>';
    echo '</div>';
    
    echo '</div>';
    echo '</div></div>';
    
    echo '</form>';
    echo '</div></div></div>';
    
    // Enhanced CSS and JavaScript
    echo '<style>
        .recipients-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .recipients-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .recipients-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .recipients-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        .form-switch .form-check-input:checked {
            background-color: var(--bs-success);
            border-color: var(--bs-success);
        }
        .nav-pills .nav-link.active {
            background-color: var(--bs-primary) !important;
        }
        .nav-pills .nav-link {
            color: var(--bs-primary);
        }
        .nav-pills .nav-link:hover {
            background-color: rgba(13, 71, 161, 0.1);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 71, 161, 0.25);
        }
    </style>';
    
    echo '<script>
        // Form validation
        (function() {
            "use strict";
            window.addEventListener("load", function() {
                var forms = document.getElementsByClassName("needs-validation");
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener("submit", function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add("was-validated");
                    }, false);
                });
            }, false);
        })();
        
        // Character counter for content
        document.addEventListener("DOMContentLoaded", function() {
            const contentTextarea = document.getElementById("content");
            const charCounter = document.createElement("div");
            charCounter.className = "form-text text-muted mt-1";
            charCounter.innerHTML = `Characters: <span id="charCount">0</span> | Recommended: 200-1000`;
            contentTextarea.parentNode.appendChild(charCounter);
            
            contentTextarea.addEventListener("input", function() {
                document.getElementById("charCount").textContent = this.value.length;
            });
            
            // File size validation
            document.getElementById("attachments").addEventListener("change", function() {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const files = this.files;
                let oversizedFiles = [];
                
                for (let file of files) {
                    if (file.size > maxSize) {
                        oversizedFiles.push(file.name);
                    }
                }
                
                if (oversizedFiles.length > 0) {
                    alert("The following files exceed the 10MB limit: " + oversizedFiles.join(", "));
                    this.value = "";
                }
            });
        });
    </script>';
}

// Function to show sent memos
function showSentMemos($uid) {
    global $connection;
    
    echo '<div class="container-fluid">';
    echo '<h2>📤 Sent Memos</h2>';
    
    $result = mysqli_query($connection, "
        SELECT m.*, mc.category_name,
               COUNT(mr.recipient_id) as total_recipients,
               SUM(CASE WHEN mr.is_read = 1 THEN 1 ELSE 0 END) as read_count,
               SUM(CASE WHEN mr.acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) as ack_count
        FROM memos m 
        LEFT JOIN memo_categories mc ON m.category_id = mc.category_id 
        LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        WHERE m.sender_id = $uid 
        GROUP BY m.memo_id 
        ORDER BY m.created_at DESC
    ");
    
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover">';
        echo '<thead class="table-dark">';
        echo '<tr>';
        echo '<th>Memo #</th>';
        echo '<th>Subject</th>';
        echo '<th>Category</th>';
        echo '<th>Priority</th>';
        echo '<th>Recipients</th>';
        echo '<th>Status</th>';
        echo '<th>Date</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($memo = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            
            // Memo number
            echo '<td><code>' . htmlspecialchars($memo['memo_number']) . '</code></td>';
            
            // Subject
            echo '<td><strong>' . htmlspecialchars($memo['subject']) . '</strong></td>';
            
            // Category
            echo '<td>' . htmlspecialchars($memo['category_name'] ?? 'General') . '</td>';
            
            // Priority
            echo '<td>' . getPriorityBadge($memo['priority']) . '</td>';
            
            // Recipients
            echo '<td>';
            echo 'Total: ' . $memo['total_recipients'] . '<br>';
            echo 'Read: ' . $memo['read_count'] . '<br>';
            if ($memo['requires_acknowledgment']) {
                echo 'Ack: ' . $memo['ack_count'];
            }
            echo '</td>';
            
            // Status
            echo '<td>';
            echo '<span class="badge bg-success">Sent</span>';
            if ($memo['requires_acknowledgment'] && $memo['ack_count'] < $memo['total_recipients']) {
                echo '<br><span class="badge bg-warning mt-1">Pending Acks</span>';
            }
            echo '</td>';
            
            // Date
            echo '<td>' . date('M j, Y g:i A', strtotime($memo['created_at'])) . '</td>';
            
            // Actions
            echo '<td>';
            echo '<div class="btn-group" role="group">';
            echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo['memo_id'] . '" class="btn btn-sm btn-primary">View</a>';
            echo '<a href="dologin.php?op=generate_pdf&memo_id=' . $memo['memo_id'] . '" class="btn btn-sm btn-outline-danger" title="Generate PDF">';
            echo '<i class="fas fa-file-pdf"></i>';
            echo '</a>';
            echo '</div>';
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">You haven\'t sent any memos yet.</div>';
    }
    
    echo '</div>';
}

// Function to view a specific memo
function viewMemo($memo_id, $uid) {
    global $connection;
    
    echo '<div class="container-fluid fade-in">';
    
    $stmt = mysqli_prepare($connection, "
        SELECT m.*, u.first_name, u.last_name, mc.category_name, mc.color,
               mr.is_read, mr.read_at, mr.acknowledged_at, d.department_name as sender_dept
        FROM memos m 
        JOIN users u ON m.sender_id = u.user_id 
        LEFT JOIN memo_categories mc ON m.category_id = mc.category_id 
        LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id AND mr.user_id = ?
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE m.memo_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $uid, $memo_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $memo = mysqli_fetch_assoc($result);
    
    if (!$memo) {
        echo '<div class="alert alert-danger d-flex align-items-center">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo '<div>Memo not found or you don\'t have permission to view it.</div>';
        echo '</div>';
        echo '</div>';
        return;
    }
    
    // Mark as read if recipient
    if ($memo['is_read'] == 0) {
        $update_stmt = mysqli_prepare($connection, "UPDATE memo_recipients SET is_read = 1, read_at = NOW() WHERE memo_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ii", $memo_id, $uid);
        mysqli_stmt_execute($update_stmt);
    }
    
    // Header with back button
    echo '<div class="d-flex align-items-center justify-content-between mb-4">';
    echo '<div class="d-flex align-items-center gap-3">';
    echo '<a href="dologin.php?op=inbox" class="btn btn-outline-secondary">';
    echo '<i class="fas fa-arrow-left me-1"></i>Back to Inbox';
    echo '</a>';
    echo '<div>';
    echo '<h1 class="display-6 fw-bold text-dark mb-0">Memo Details</h1>';
    echo '<p class="text-muted mb-0">Memo #' . htmlspecialchars($memo['memo_number']) . '</p>';
    echo '</div>';
    echo '</div>';
    
    // Status indicators
    echo '<div class="d-flex align-items-center gap-2">';
    echo getPriorityBadge($memo['priority']);
    if ($memo['is_confidential']) {
        echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">';
        echo '<i class="fas fa-lock me-1"></i>Confidential';
        echo '</span>';
    }
    if ($memo['requires_acknowledgment'] && !$memo['acknowledged_at']) {
        echo '<span class="badge bg-warning-subtle text-warning border border-warning-subtle pulse">';
        echo '<i class="fas fa-clock me-1"></i>Requires Acknowledgment';
        echo '</span>';
    } elseif ($memo['acknowledged_at']) {
        echo '<span class="badge bg-success-subtle text-success border border-success-subtle">';
        echo '<i class="fas fa-check-circle me-1"></i>Acknowledged';
        echo '</span>';
    }
    echo '</div>';
    echo '</div>';
    
    echo '<div class="row g-4">';
    
    // Main memo content
    echo '<div class="col-lg-8">';
    
    // Memo header card
    echo '<div class="card border-0 shadow-sm mb-4">';
    echo '<div class="card-header bg-gradient text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
    echo '<h4 class="mb-0 d-flex align-items-center gap-2">';
    echo '<i class="fas fa-file-text"></i>';
    echo '<span>' . htmlspecialchars($memo['subject']) . '</span>';
    echo '</h4>';
    echo '</div>';
    echo '<div class="card-body p-4">';
    
    // Memo metadata
    echo '<div class="row g-3 mb-4">';
    echo '<div class="col-md-6">';
    echo '<div class="d-flex align-items-center gap-2 mb-2">';
    echo '<i class="fas fa-user text-primary"></i>';
    echo '<span class="fw-medium">From:</span>';
    echo '<span>' . htmlspecialchars($memo['first_name'] . ' ' . $memo['last_name']) . '</span>';
    echo '</div>';
    if ($memo['sender_dept']) {
        echo '<div class="d-flex align-items-center gap-2 mb-2">';
        echo '<i class="fas fa-building text-primary"></i>';
        echo '<span class="fw-medium">Department:</span>';
        echo '<span>' . htmlspecialchars($memo['sender_dept']) . '</span>';
        echo '</div>';
    }
    if ($memo['category_name']) {
        echo '<div class="d-flex align-items-center gap-2 mb-2">';
        echo '<i class="fas fa-folder text-primary"></i>';
        echo '<span class="fw-medium">Category:</span>';
        echo '<span class="badge bg-primary-subtle text-primary">' . htmlspecialchars($memo['category_name']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<div class="d-flex align-items-center gap-2 mb-2">';
    echo '<i class="fas fa-calendar text-primary"></i>';
    echo '<span class="fw-medium">Date Sent:</span>';
    echo '<span>' . date('l, F j, Y g:i A', strtotime($memo['created_at'])) . '</span>';
    echo '</div>';
    if ($memo['due_date']) {
        echo '<div class="d-flex align-items-center gap-2 mb-2">';
        echo '<i class="fas fa-calendar-times text-warning"></i>';
        echo '<span class="fw-medium">Due Date:</span>';
        echo '<span class="text-warning fw-medium">' . date('F j, Y', strtotime($memo['due_date'])) . '</span>';
        echo '</div>';
    }
    if ($memo['expiration_date']) {
        echo '<div class="d-flex align-items-center gap-2 mb-2">';
        echo '<i class="fas fa-hourglass-end text-danger"></i>';
        echo '<span class="fw-medium">Expires:</span>';
        echo '<span class="text-danger">' . date('F j, Y', strtotime($memo['expiration_date'])) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    
    echo '</div></div>';
    
    // Memo content card
    echo '<div class="card border-0 shadow-sm mb-4">';
    echo '<div class="card-header bg-light border-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-align-left text-primary"></i>';
    echo '<span>Message Content</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body p-4">';
    echo '<div class="memo-content" style="line-height: 1.8; font-size: 1.05rem;">';
    echo nl2br(htmlspecialchars($memo['content']));
    echo '</div>';
    echo '</div></div>';
    
    // Attachments section
    $attachments_result = mysqli_query($connection, "
        SELECT * FROM memo_attachments 
        WHERE memo_id = $memo_id 
        ORDER BY created_at
    ");
    
    if (mysqli_num_rows($attachments_result) > 0) {
        echo '<div class="card border-0 shadow-sm mb-4">';
        echo '<div class="card-header bg-light border-0 py-3">';
        echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
        echo '<i class="fas fa-paperclip text-primary"></i>';
        echo '<span>Attachments</span>';
        echo '<span class="badge bg-primary ms-auto">' . mysqli_num_rows($attachments_result) . '</span>';
        echo '</h5>';
        echo '</div>';
        echo '<div class="card-body p-0">';
        echo '<div class="list-group list-group-flush">';
        
        while ($attachment = mysqli_fetch_assoc($attachments_result)) {
            $file_size = round($attachment['file_size'] / 1024, 1); // KB
            if ($file_size > 1024) {
                $file_size = round($file_size / 1024, 1) . ' MB';
            } else {
                $file_size .= ' KB';
            }
            
            $file_ext = strtolower(pathinfo($attachment['original_filename'], PATHINFO_EXTENSION));
            $file_icon = getFileIcon($file_ext);
            
            echo '<div class="list-group-item border-0 py-3 d-flex align-items-center justify-content-between">';
            echo '<div class="d-flex align-items-center gap-3">';
            echo '<div class="text-primary" style="font-size: 1.5rem;">' . $file_icon . '</div>';
            echo '<div>';
            echo '<div class="fw-medium text-dark">' . htmlspecialchars($attachment['original_filename']) . '</div>';
            echo '<small class="text-muted">';
            echo '<i class="fas fa-weight me-1"></i>' . $file_size . ' • ';
            echo '<i class="fas fa-clock me-1"></i>Uploaded ' . date('M j, Y g:i A', strtotime($attachment['created_at']));
            echo '</small>';
            echo '</div>';
            echo '</div>';
            echo '<a href="download_attachment.php?id=' . $attachment['attachment_id'] . '" class="btn btn-outline-primary d-flex align-items-center gap-2">';
            echo '<i class="fas fa-download"></i>';
            echo '<span>Download</span>';
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div></div></div>';
    }
    
    // Acknowledgment section
    if ($memo['requires_acknowledgment'] && !$memo['acknowledged_at']) {
        echo '<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">';
        echo '<div class="me-3">';
        echo '<i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.5rem;"></i>';
        echo '</div>';
        echo '<div class="flex-grow-1">';
        echo '<h6 class="mb-1 fw-bold">Acknowledgment Required</h6>';
        echo '<p class="mb-2">This memo requires your acknowledgment. Please confirm that you have read and understood it.</p>';
        echo '<form method="post" action="dologin.php?op=acknowledge_memo">';
        echo '<input type="hidden" name="memo_id" value="' . $memo_id . '">';
        echo '<button type="submit" class="btn btn-warning">';
        echo '<i class="fas fa-check me-1"></i>Acknowledge Receipt';
        echo '</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    } elseif ($memo['acknowledged_at']) {
        echo '<div class="alert alert-success border-0 shadow-sm d-flex align-items-center">';
        echo '<div class="me-3">';
        echo '<i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>';
        echo '</div>';
        echo '<div>';
        echo '<h6 class="mb-1 fw-bold">Acknowledged</h6>';
        echo '<p class="mb-0">You acknowledged this memo on ' . date('l, F j, Y \a\t g:i A', strtotime($memo['acknowledged_at'])) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    
    // Action buttons
    echo '<div class="card border-0 shadow-sm">';
    echo '<div class="card-body p-4">';
    echo '<div class="d-flex flex-wrap gap-2">';
    
    // Forward button
    echo '<a href="dologin.php?op=forward_memo&memo_id=' . $memo_id . '" class="btn btn-outline-primary d-flex align-items-center gap-2">';
    echo '<i class="fas fa-share"></i>';
    echo '<span>Forward</span>';
    echo '</a>';
    
    // Reply button
    if ($memo['sender_id'] != $uid) {
        echo '<a href="dologin.php?op=compose&reply_to=' . $memo_id . '" class="btn btn-outline-secondary d-flex align-items-center gap-2">';
        echo '<i class="fas fa-reply"></i>';
        echo '<span>Reply</span>';
        echo '</a>';
    }
    
    // PDF Generation button
    echo '<a href="dologin.php?op=generate_pdf&memo_id=' . $memo_id . '" class="btn btn-outline-success d-flex align-items-center gap-2">';
    echo '<i class="fas fa-file-pdf"></i>';
    echo '<span>Generate PDF</span>';
    echo '</a>';
    
    // Print button
    echo '<button onclick="window.print()" class="btn btn-outline-info d-flex align-items-center gap-2">';
    echo '<i class="fas fa-print"></i>';
    echo '<span>Print</span>';
    echo '</button>';
    
    echo '</div>';
    echo '</div></div>';
    
    echo '</div>'; // End main content column
    
    // Sidebar
    echo '<div class="col-lg-4">';
    
    // PDF Downloads section
    require_once 'pdf_utils.php';
    $existing_pdfs = getExistingMemoPDFs($memo['memo_number']);
    if (!empty($existing_pdfs)) {
        echo '<div class="card border-0 shadow-sm mb-4">';
        echo '<div class="card-header bg-light border-0 py-3">';
        echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
        echo '<i class="fas fa-file-pdf text-danger"></i>';
        echo '<span>Available PDFs</span>';
        echo '<span class="badge bg-danger ms-auto">' . count($existing_pdfs) . '</span>';
        echo '</h5>';
        echo '</div>';
        echo '<div class="card-body p-0">';
        echo '<div class="list-group list-group-flush">';
        
        foreach ($existing_pdfs as $pdf) {
            echo '<div class="list-group-item border-0 py-3 d-flex align-items-center justify-content-between">';
            echo '<div>';
            echo '<div class="fw-medium text-dark">📄 PDF Document</div>';
            echo '<small class="text-muted">Generated: ' . date('M j, Y g:i A', $pdf['created']) . '</small><br>';
            echo '<small class="text-muted">Size: ' . round($pdf['size'] / 1024, 2) . ' KB</small>';
            echo '</div>';
            echo '<a href="' . $pdf['download_url'] . '" class="btn btn-sm btn-outline-danger">';
            echo '<i class="fas fa-download me-1"></i>Download';
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div></div>';
    }
    
    // Recipients and status
    echo '<div class="card border-0 shadow-sm mb-4">';
    echo '<div class="card-header bg-light border-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-users text-primary"></i>';
    echo '<span>Distribution Status</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body p-0">';
    
    $recipients_result = mysqli_query($connection, "
        SELECT u.first_name, u.last_name, u.position, d.department_name,
               mr.is_read, mr.read_at, mr.acknowledged_at
        FROM memo_recipients mr 
        JOIN users u ON mr.user_id = u.user_id 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        WHERE mr.memo_id = $memo_id 
        ORDER BY u.last_name, u.first_name
    ");
    
    if (mysqli_num_rows($recipients_result) > 0) {
        echo '<div class="list-group list-group-flush">';
        while ($recipient = mysqli_fetch_assoc($recipients_result)) {
            echo '<div class="list-group-item border-0 py-3">';
            echo '<div class="d-flex align-items-start gap-3">';
            echo '<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; font-size: 0.8rem; font-weight: 600;">';
            echo strtoupper(substr($recipient['first_name'], 0, 1) . substr($recipient['last_name'], 0, 1));
            echo '</div>';
            echo '<div class="flex-grow-1">';
            echo '<div class="fw-medium text-dark">' . htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) . '</div>';
            if ($recipient['position']) {
                echo '<small class="text-muted">' . htmlspecialchars($recipient['position']) . '</small><br>';
            }
            if ($recipient['department_name']) {
                echo '<small class="text-muted">' . htmlspecialchars($recipient['department_name']) . '</small><br>';
            }
            
            echo '<div class="mt-2">';
            if ($recipient['is_read']) {
                echo '<span class="badge bg-success-subtle text-success">';
                echo '<i class="fas fa-check me-1"></i>Read';
                echo '</span>';
                if ($recipient['read_at']) {
                    echo '<br><small class="text-muted">' . date('M j, g:i A', strtotime($recipient['read_at'])) . '</small>';
                }
            } else {
                echo '<span class="badge bg-warning-subtle text-warning">';
                echo '<i class="fas fa-clock me-1"></i>Unread';
                echo '</span>';
            }
            
            if ($memo['requires_acknowledgment']) {
                echo '<br>';
                if ($recipient['acknowledged_at']) {
                    echo '<span class="badge bg-info-subtle text-info">';
                    echo '<i class="fas fa-check-circle me-1"></i>Acknowledged';
                    echo '</span>';
                } else {
                    echo '<span class="badge bg-secondary-subtle text-secondary">';
                    echo '<i class="fas fa-hourglass-half me-1"></i>Pending Ack';
                    echo '</span>';
                }
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-3 text-muted">';
        echo '<i class="fas fa-user-slash mb-2" style="font-size: 2rem; opacity: 0.3;"></i>';
        echo '<p>No recipients found</p>';
        echo '</div>';
    }
    
    echo '</div></div>';
    
    // Comments section
    echo '<div class="card border-0 shadow-sm">';
    echo '<div class="card-header bg-light border-0 py-3">';
    echo '<h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">';
    echo '<i class="fas fa-comments text-primary"></i>';
    echo '<span>Comments</span>';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    // Show success message if comment was just added
    if (isset($_GET['comment_added']) && $_GET['comment_added'] == '1') {
        echo '<div class="alert alert-success border-0 alert-dismissible fade show">';
        echo '<i class="fas fa-check me-2"></i>Comment added successfully!';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
    
    // Handle new comment submission
    if ($_POST['action'] ?? '' === 'add_comment') {
        $comment_text = trim($_POST['comment_text'] ?? '');
        if (!empty($comment_text)) {
            try {
                $comment_stmt = mysqli_prepare($connection, "
                    INSERT INTO memo_comments (memo_id, user_id, comment, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                
                if ($comment_stmt) {
                    mysqli_stmt_bind_param($comment_stmt, "iis", $memo_id, $uid, $comment_text);
                    
                    if (mysqli_stmt_execute($comment_stmt)) {
                        mysqli_stmt_close($comment_stmt);
                        // Use POST-Redirect-GET pattern to prevent resubmission
                        header("Location: dologin.php?op=view_memo&memo_id=" . $memo_id . "&comment_added=1");
                        exit();
                    } else {
                        echo '<div class="alert alert-danger border-0">';
                        echo '<i class="fas fa-exclamation-triangle me-2"></i>Error adding comment. Please try again.';
                        echo '</div>';
                    }
                    mysqli_stmt_close($comment_stmt);
                } else {
                    echo '<div class="alert alert-danger border-0">';
                    echo '<i class="fas fa-exclamation-triangle me-2"></i>Database error. Please contact administrator.';
                    echo '</div>';
                }
            } catch (Exception $e) {
                error_log("Comment error: " . $e->getMessage());
                echo '<div class="alert alert-danger border-0">';
                echo '<i class="fas fa-exclamation-triangle me-2"></i>Comment system temporarily unavailable.';
                echo '</div>';
            }
        }
    }
    
    // Show existing comments
    try {
        $comments_result = mysqli_query($connection, "
            SELECT mc.*, u.first_name, u.last_name, u.position, d.department_name
            FROM memo_comments mc 
            JOIN users u ON mc.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id 
            WHERE mc.memo_id = $memo_id 
            ORDER BY mc.created_at ASC
        ");
        
        if ($comments_result && mysqli_num_rows($comments_result) > 0) {
        echo '<div class="comments-list mb-3" style="max-height: 300px; overflow-y: auto;">';
        while ($comment = mysqli_fetch_assoc($comments_result)) {
            echo '<div class="d-flex gap-2 mb-3">';
            echo '<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 0.75rem; font-weight: 600;">';
            echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1));
            echo '</div>';
            echo '<div class="flex-grow-1">';
            echo '<div class="bg-light rounded-3 p-3">';
            echo '<div class="d-flex justify-content-between align-items-start mb-1">';
            echo '<div>';
            echo '<div class="fw-medium text-dark">' . htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) . '</div>';
            if ($comment['position']) {
                echo '<small class="text-muted">' . htmlspecialchars($comment['position']) . '</small>';
            }
            echo '</div>';
            echo '<small class="text-muted">' . date('M j, Y g:i A', strtotime($comment['created_at'])) . '</small>';
            echo '</div>';
            echo '<p class="mb-0" style="line-height: 1.6;">' . nl2br(htmlspecialchars($comment['comment'])) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        } else {
            echo '<div class="text-center py-3 text-muted">';
            echo '<i class="fas fa-comment-slash mb-2" style="font-size: 2rem; opacity: 0.3;"></i>';
            echo '<p>No comments yet. Be the first to comment!</p>';
            echo '</div>';
        }
    } catch (Exception $e) {
        error_log("Comments display error: " . $e->getMessage());
        echo '<div class="text-center py-3 text-muted">';
        echo '<i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem; opacity: 0.3;"></i>';
        echo '<p>Comments temporarily unavailable.</p>';
        echo '</div>';
    }
    
    // Add comment form
    echo '<form method="post" class="mt-3">';
    echo '<input type="hidden" name="op" value="view_memo">';
    echo '<input type="hidden" name="memo_id" value="' . htmlspecialchars($memo_id) . '">';
    echo '<input type="hidden" name="action" value="add_comment">';
    echo '<div class="mb-3">';
    echo '<textarea name="comment_text" class="form-control" rows="3" placeholder="Add your comment..." required></textarea>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary d-flex align-items-center gap-2">';
    echo '<i class="fas fa-comment"></i>';
    echo '<span>Add Comment</span>';
    echo '</button>';
    echo '</form>';
    
    echo '</div></div>';
    echo '</div>'; // End sidebar
    echo '</div>'; // End row
    
    echo '</div>'; // End container
    
    // Custom CSS for memo view
    echo '<style>
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .comments-list::-webkit-scrollbar {
            width: 6px;
        }
        .comments-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .comments-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .memo-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        @media print {
            .btn, .card-header, .comments-list, form, .alert {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>';
}

// Helper function to get file icons
function getFileIcon($extension) {
    $icons = [
        'pdf' => '<i class="fas fa-file-pdf text-danger"></i>',
        'doc' => '<i class="fas fa-file-word text-primary"></i>',
        'docx' => '<i class="fas fa-file-word text-primary"></i>',
        'xls' => '<i class="fas fa-file-excel text-success"></i>',
        'xlsx' => '<i class="fas fa-file-excel text-success"></i>',
        'ppt' => '<i class="fas fa-file-powerpoint text-warning"></i>',
        'pptx' => '<i class="fas fa-file-powerpoint text-warning"></i>',
        'txt' => '<i class="fas fa-file-alt text-secondary"></i>',
        'jpg' => '<i class="fas fa-file-image text-info"></i>',
        'jpeg' => '<i class="fas fa-file-image text-info"></i>',
        'png' => '<i class="fas fa-file-image text-info"></i>',
        'gif' => '<i class="fas fa-file-image text-info"></i>',
        'zip' => '<i class="fas fa-file-archive text-dark"></i>',
        'rar' => '<i class="fas fa-file-archive text-dark"></i>'
    ];
    
    return $icons[$extension] ?? '<i class="fas fa-file text-muted"></i>';
}

// Department Management Interface
function manageDepartments($uid) {
    global $connection;
    
    // Check admin permissions
    $user_info = getUserInfo($uid);
    if (!$user_info['is_admin']) {
        echo '<div class="container-fluid">';
        echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="container-fluid">';
    echo '<h2>🏢 Manage Departments</h2>';
    
    // Handle form submissions
    if ($_POST['action'] ?? '' === 'add_department') {
        $dept_name = trim($_POST['department_name'] ?? '');
        $dept_code = trim($_POST['code'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $head_user_id = !empty($_POST['head_user_id']) ? $_POST['head_user_id'] : null;
        
        if (!empty($dept_name)) {
            $stmt = mysqli_prepare($connection, "INSERT INTO departments (department_name, code, location, head_user_id, is_active) VALUES (?, ?, ?, ?, 1)");
            mysqli_stmt_bind_param($stmt, "sssi", $dept_name, $dept_code, $location, $head_user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Department added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error adding department: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Department name is required.</div>';
        }
    }
    
    if ($_POST['action'] ?? '' === 'update_department') {
        $dept_id = $_POST['department_id'];
        $dept_name = trim($_POST['department_name'] ?? '');
        $dept_code = trim($_POST['code'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $head_user_id = !empty($_POST['head_user_id']) ? $_POST['head_user_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($dept_name)) {
            $stmt = mysqli_prepare($connection, "UPDATE departments SET department_name = ?, code = ?, location = ?, head_user_id = ?, is_active = ? WHERE department_id = ?");
            mysqli_stmt_bind_param($stmt, "sssiii", $dept_name, $dept_code, $location, $head_user_id, $is_active, $dept_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Department updated successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error updating department: ' . mysqli_error($connection) . '</div>';
            }
        }
    }
    
    // Add department form
    echo '<div class="row mb-4">';
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>➕ Add New Department</h5></div>';
    echo '<div class="card-body">';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="add_department">';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Department Name *</label>';
    echo '<input type="text" name="department_name" class="form-control" required maxlength="100">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Department Code</label>';
    echo '<input type="text" name="code" class="form-control" maxlength="10" placeholder="e.g., CARD, EMRG">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Location</label>';
    echo '<input type="text" name="location" class="form-control" maxlength="100" placeholder="e.g., Building A, Floor 2">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Department Head</label>';
    echo '<select name="head_user_id" class="form-select">';
    echo '<option value="">Select Department Head</option>';
    $users_result = mysqli_query($connection, "SELECT user_id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name, first_name");
    while ($user = mysqli_fetch_assoc($users_result)) {
        echo '<option value="' . $user['user_id'] . '">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<button type="submit" class="btn btn-primary">Add Department</button>';
    echo '</form>';
    
    echo '</div></div></div>';
    
    // Department listing
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>📋 Current Departments</h5></div>';
    echo '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
    
    $dept_result = mysqli_query($connection, "
        SELECT d.*, u.first_name, u.last_name,
               (SELECT COUNT(*) FROM users WHERE department_id = d.department_id AND is_active = 1) as user_count
        FROM departments d 
        LEFT JOIN users u ON d.head_user_id = u.user_id 
        ORDER BY d.department_name
    ");
    
    while ($dept = mysqli_fetch_assoc($dept_result)) {
        $status_class = $dept['is_active'] ? 'border-success' : 'border-secondary';
        
        echo '<div class="card mb-2 ' . $status_class . '">';
        echo '<div class="card-body p-2">';
        echo '<h6 class="card-title mb-1">' . htmlspecialchars($dept['department_name']);
        if ($dept['code']) {
            echo ' <small class="text-muted">(' . htmlspecialchars($dept['code']) . ')</small>';
        }
        echo '</h6>';
        
        if ($dept['location']) {
            echo '<small class="text-muted">📍 ' . htmlspecialchars($dept['location']) . '</small><br>';
        }
        if ($dept['first_name']) {
            echo '<small class="text-info">👤 Head: ' . htmlspecialchars($dept['first_name'] . ' ' . $dept['last_name']) . '</small><br>';
        }
        echo '<small class="text-secondary">👥 ' . $dept['user_count'] . ' users</small>';
        
        if (!$dept['is_active']) {
            echo '<br><span class="badge bg-secondary">Inactive</span>';
        }
        
        echo '<div class="mt-2">';
        echo '<button class="btn btn-sm btn-outline-primary" onclick="editDepartment(' . $dept['department_id'] . ')">Edit</button>';
        echo '</div>';
        
        echo '</div></div>';
    }
    
    echo '</div></div></div>';
    echo '</div>';
    
    echo '</div>';
}

// Category Management Interface
function manageCategories($uid) {
    global $connection;
    
    // Check admin permissions
    $user_info = getUserInfo($uid);
    if (!$user_info['is_admin']) {
        echo '<div class="container-fluid">';
        echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="container-fluid">';
    echo '<h2>🏷️ Manage Categories</h2>';
    
    // Handle form submissions
    if ($_POST['action'] ?? '' === 'add_category') {
        $cat_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        
        if (!empty($cat_name)) {
            $stmt = mysqli_prepare($connection, "INSERT INTO memo_categories (category_name, description, color, is_active) VALUES (?, ?, ?, 1)");
            mysqli_stmt_bind_param($stmt, "sss", $cat_name, $description, $color);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Category added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error adding category: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Category name is required.</div>';
        }
    }
    
    if ($_POST['action'] ?? '' === 'update_category') {
        $cat_id = $_POST['category_id'];
        $cat_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($cat_name)) {
            $stmt = mysqli_prepare($connection, "UPDATE memo_categories SET category_name = ?, description = ?, color = ?, is_active = ? WHERE category_id = ?");
            mysqli_stmt_bind_param($stmt, "sssii", $cat_name, $description, $color, $is_active, $cat_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Category updated successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error updating category: ' . mysqli_error($connection) . '</div>';
            }
        }
    }
    
    // Add category form
    echo '<div class="row mb-4">';
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>➕ Add New Category</h5></div>';
    echo '<div class="card-body">';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="add_category">';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Category Name *</label>';
    echo '<input type="text" name="category_name" class="form-control" required maxlength="50">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Description</label>';
    echo '<textarea name="description" class="form-control" rows="3" maxlength="255"></textarea>';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Color</label>';
    echo '<input type="color" name="color" class="form-control form-control-color" value="#007bff">';
    echo '</div>';
    
    echo '<button type="submit" class="btn btn-primary">Add Category</button>';
    echo '</form>';
    
    echo '</div></div></div>';
    
    // Category listing
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>📋 Current Categories</h5></div>';
    echo '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
    
    $cat_result = mysqli_query($connection, "
        SELECT mc.*, 
               (SELECT COUNT(*) FROM memos WHERE category_id = mc.category_id) as memo_count
        FROM memo_categories mc 
        ORDER BY mc.category_name
    ");
    
    while ($cat = mysqli_fetch_assoc($cat_result)) {
        $status_class = $cat['is_active'] ? 'border-success' : 'border-secondary';
        
        echo '<div class="card mb-2 ' . $status_class . '">';
        echo '<div class="card-body p-2">';
        
        echo '<div class="d-flex align-items-center mb-1">';
        echo '<span class="badge me-2" style="background-color: ' . htmlspecialchars($cat['color']) . '; width: 20px; height: 20px;">&nbsp;</span>';
        echo '<h6 class="card-title mb-0">' . htmlspecialchars($cat['category_name']) . '</h6>';
        echo '</div>';
        
        if ($cat['description']) {
            echo '<p class="card-text small mb-1">' . htmlspecialchars($cat['description']) . '</p>';
        }
        
        echo '<small class="text-secondary">📝 ' . $cat['memo_count'] . ' memos</small>';
        
        if (!$cat['is_active']) {
            echo '<br><span class="badge bg-secondary">Inactive</span>';
        }
        
        echo '<div class="mt-2">';
        echo '<button class="btn btn-sm btn-outline-primary" onclick="editCategory(' . $cat['category_id'] . ')">Edit</button>';
        echo '</div>';
        
        echo '</div></div>';
    }
    
    echo '</div></div></div>';
    echo '</div>';
    
    echo '</div>';
}

// Role Management Interface
function manageRoles($uid) {
    global $connection;
    
    // Check admin permissions
    $user_info = getUserInfo($uid);
    if (!$user_info['is_admin']) {
        echo '<div class="container-fluid">';
        echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="container-fluid">';
    echo '<h2>🎭 Manage User Roles</h2>';
    
    // Handle form submissions
    if ($_POST['action'] ?? '' === 'add_role') {
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $can_send = isset($_POST['can_send_memos']) ? 1 : 0;
        $can_receive = isset($_POST['can_receive_memos']) ? 1 : 0;
        
        if (!empty($role_name)) {
            $stmt = mysqli_prepare($connection, "INSERT INTO user_roles (role_name, description, can_send_memos, can_receive_memos) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssii", $role_name, $description, $can_send, $can_receive);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Role added successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error adding role: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Role name is required.</div>';
        }
    }
    
    if ($_POST['action'] ?? '' === 'update_role') {
        $role_id = $_POST['role_id'] ?? 0;
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $can_send = isset($_POST['can_send_memos']) ? 1 : 0;
        $can_receive = isset($_POST['can_receive_memos']) ? 1 : 0;
        
        if (!empty($role_name) && $role_id > 0) {
            $stmt = mysqli_prepare($connection, "UPDATE user_roles SET role_name = ?, description = ?, can_send_memos = ?, can_receive_memos = ? WHERE role_id = ?");
            mysqli_stmt_bind_param($stmt, "ssiii", $role_name, $description, $can_send, $can_receive, $role_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">Role updated successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error updating role: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Role name and valid role ID are required.</div>';
        }
    }
    
    // Add role form
    echo '<div class="row mb-4">';
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>➕ Add New Role</h5></div>';
    echo '<div class="card-body">';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="add_role">';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Role Name *</label>';
    echo '<input type="text" name="role_name" class="form-control" required maxlength="50" placeholder="e.g., Doctor, Nurse, Admin">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Description</label>';
    echo '<textarea name="description" class="form-control" rows="3" maxlength="255" placeholder="Brief description of the role"></textarea>';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<h6>Permissions:</h6>';
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="can_send_memos" id="can_send" checked>';
    echo '<label class="form-check-label" for="can_send">Can send memos</label>';
    echo '</div>';
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="can_receive_memos" id="can_receive" checked>';
    echo '<label class="form-check-label" for="can_receive">Can receive memos</label>';
    echo '</div>';
    echo '</div>';
    
    echo '<button type="submit" class="btn btn-primary">Add Role</button>';
    echo '</form>';
    
    echo '</div></div></div>';
    
    // Role listing
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>📋 Current Roles</h5></div>';
    echo '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
    
    $role_result = mysqli_query($connection, "
        SELECT ur.*, 
               (SELECT COUNT(*) FROM users WHERE role_id = ur.role_id AND is_active = 1) as user_count
        FROM user_roles ur 
        ORDER BY ur.role_name
    ");
    
    while ($role = mysqli_fetch_assoc($role_result)) {
        echo '<div class="card mb-2 border-primary">';
        echo '<div class="card-body p-2">';
        
        echo '<h6 class="card-title mb-1">' . htmlspecialchars($role['role_name']) . '</h6>';
        
        if ($role['description']) {
            echo '<p class="card-text small mb-1">' . htmlspecialchars($role['description']) . '</p>';
        }
        
        echo '<div class="mb-2">';
        if ($role['can_send_memos']) {
            echo '<span class="badge bg-success me-1">📤 Send</span>';
        }
        if ($role['can_receive_memos']) {
            echo '<span class="badge bg-info me-1">📥 Receive</span>';
        }
        echo '</div>';
        
        echo '<small class="text-secondary">👥 ' . $role['user_count'] . ' users</small>';
        
        echo '<div class="mt-2">';
        echo '<button class="btn btn-sm btn-outline-primary" ';
        echo 'onclick="editRole(' . $role['role_id'] . ')" ';
        echo 'data-role-id="' . $role['role_id'] . '" ';
        echo 'data-role-name="' . htmlspecialchars($role['role_name']) . '" ';
        echo 'data-description="' . htmlspecialchars($role['description'] ?? '') . '" ';
        echo 'data-can-send="' . $role['can_send_memos'] . '" ';
        echo 'data-can-receive="' . $role['can_receive_memos'] . '">Edit</button>';
        echo '</div>';
        
        echo '</div></div>';
    }
    
    echo '</div></div></div>';
    echo '</div>';
    
    // Edit Role Modal
    echo '<div class="modal fade" id="editRoleModal" tabindex="-1">';
    echo '<div class="modal-dialog">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title">✏️ Edit Role</h5>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    echo '</div>';
    echo '<form method="post" id="editRoleForm">';
    echo '<div class="modal-body">';
    echo '<input type="hidden" name="action" value="update_role">';
    echo '<input type="hidden" name="role_id" id="edit_role_id">';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Role Name *</label>';
    echo '<input type="text" name="role_name" id="edit_role_name" class="form-control" required maxlength="50">';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Description</label>';
    echo '<textarea name="description" id="edit_description" class="form-control" rows="3" maxlength="255"></textarea>';
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<h6>Permissions:</h6>';
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="can_send_memos" id="edit_can_send">';
    echo '<label class="form-check-label" for="edit_can_send">Can send memos</label>';
    echo '</div>';
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="can_receive_memos" id="edit_can_receive">';
    echo '<label class="form-check-label" for="edit_can_receive">Can receive memos</label>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
    echo '<button type="submit" class="btn btn-primary">Update Role</button>';
    echo '</div>';
    echo '</form>';
    echo '</div></div></div>';
    
    // JavaScript for role editing
    echo '<script>
        function editRole(roleId, buttonElement) {
            console.log("EditRole called with ID:", roleId);
            
            // Try to get the button element if not passed
            if (!buttonElement) {
                buttonElement = event.target;
            }
            
            // Show modal first
            const modalElement = document.getElementById("editRoleModal");
            if (modalElement) {
                // Try Bootstrap modal first
                if (typeof bootstrap !== "undefined") {
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    // Fallback: just show the modal div
                    modalElement.style.display = "block";
                    modalElement.classList.add("show");
                }
                
                // Try to load from data attributes first (immediate fallback)
                if (buttonElement && buttonElement.dataset) {
                    console.log("Using data attributes");
                    document.getElementById("edit_role_id").value = buttonElement.dataset.roleId || "";
                    document.getElementById("edit_role_name").value = buttonElement.dataset.roleName || "";
                    document.getElementById("edit_description").value = buttonElement.dataset.description || "";
                    document.getElementById("edit_can_send").checked = buttonElement.dataset.canSend === "1";
                    document.getElementById("edit_can_receive").checked = buttonElement.dataset.canReceive === "1";
                    return; // Skip AJAX if data attributes work
                }
                
                // Clear form for AJAX loading
                document.getElementById("edit_role_id").value = "";
                document.getElementById("edit_role_name").value = "Loading...";
                document.getElementById("edit_description").value = "";
                document.getElementById("edit_can_send").checked = false;
                document.getElementById("edit_can_receive").checked = false;
            }
            
            // Get role data via AJAX as fallback
            fetch("dologin.php?op=get_role_data&role_id=" + roleId, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }
                return response.text(); // Get as text first to debug
            })
            .then(text => {
                console.log("Raw response:", text);
                try {
                    const data = JSON.parse(text);
                    console.log("Parsed data:", data);
                    
                    if (data.success) {
                        document.getElementById("edit_role_id").value = data.role.role_id;
                        document.getElementById("edit_role_name").value = data.role.role_name;
                        document.getElementById("edit_description").value = data.role.description || "";
                        document.getElementById("edit_can_send").checked = data.role.can_send_memos == "1";
                        document.getElementById("edit_can_receive").checked = data.role.can_receive_memos == "1";
                    } else {
                        alert("Error loading role data: " + data.message);
                        document.getElementById("edit_role_name").value = "";
                    }
                } catch (e) {
                    console.error("JSON Parse Error:", e);
                    alert("Invalid response from server. Please check console for details.");
                    document.getElementById("edit_role_name").value = "";
                }
            })
            .catch(error => {
                console.error("Fetch Error:", error);
                alert("Error loading role data: " + error.message + ". Please try again.");
                document.getElementById("edit_role_name").value = "";
            });
        }
        
        // Also add form submission handler and modal close handlers
        document.addEventListener("DOMContentLoaded", function() {
            const editForm = document.getElementById("editRoleForm");
            if (editForm) {
                editForm.addEventListener("submit", function(e) {
                    const roleName = document.getElementById("edit_role_name").value.trim();
                    if (!roleName || roleName === "Loading...") {
                        e.preventDefault();
                        alert("Please enter a valid role name.");
                        return false;
                    }
                });
            }
            
            // Add close button handlers for non-Bootstrap fallback
            const closeButtons = document.querySelectorAll("[data-bs-dismiss=\'modal\']");
            closeButtons.forEach(button => {
                button.addEventListener("click", function() {
                    const modal = document.getElementById("editRoleModal");
                    if (modal) {
                        if (typeof bootstrap !== "undefined") {
                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) modalInstance.hide();
                        } else {
                            modal.style.display = "none";
                            modal.classList.remove("show");
                        }
                    }
                });
            });
        });
    </script>';
    
    echo '</div>';
}

// System Reports Interface
function showSystemReports($uid) {
    global $connection;
    
    // Check admin permissions
    $user_info = getUserInfo($uid);
    if (!$user_info['is_admin']) {
        echo '<div class="container-fluid">';
        echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="container-fluid">';
    echo '<h2>📊 System Reports & Analytics</h2>';
    
    // Summary statistics
    echo '<div class="row mb-4">';
    
    // Total users
    $user_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE is_active = 1"))['count'];
    echo '<div class="col-md-3">';
    echo '<div class="card bg-primary text-white">';
    echo '<div class="card-body text-center">';
    echo '<h3>' . $user_count . '</h3>';
    echo '<p>👥 Active Users</p>';
    echo '</div></div></div>';
    
    // Total departments
    $dept_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM departments WHERE is_active = 1"))['count'];
    echo '<div class="col-md-3">';
    echo '<div class="card bg-success text-white">';
    echo '<div class="card-body text-center">';
    echo '<h3>' . $dept_count . '</h3>';
    echo '<p>🏢 Departments</p>';
    echo '</div></div></div>';
    
    // Total memos this month
    $memo_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM memos WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"))['count'];
    echo '<div class="col-md-3">';
    echo '<div class="card bg-info text-white">';
    echo '<div class="card-body text-center">';
    echo '<h3>' . $memo_count . '</h3>';
    echo '<p>📝 Memos This Month</p>';
    echo '</div></div></div>';
    
    // Pending acknowledgments
    $pending_acks = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM memo_recipients mr JOIN memos m ON mr.memo_id = m.memo_id WHERE m.requires_acknowledgment = 1 AND mr.acknowledged_at IS NULL"))['count'];
    echo '<div class="col-md-3">';
    echo '<div class="card bg-warning text-dark">';
    echo '<div class="card-body text-center">';
    echo '<h3>' . $pending_acks . '</h3>';
    echo '<p>⏳ Pending Acknowledgments</p>';
    echo '</div></div></div>';
    
    echo '</div>';
    
    // Charts and detailed reports
    echo '<div class="row">';
    
    // Memo activity by department
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>📈 Memo Activity by Department</h5></div>';
    echo '<div class="card-body">';
    
    $dept_activity = mysqli_query($connection, "
        SELECT d.department_name, COUNT(m.memo_id) as memo_count
        FROM departments d 
        LEFT JOIN users u ON d.department_id = u.department_id 
        LEFT JOIN memos m ON u.user_id = m.sender_id 
        WHERE d.is_active = 1 
        GROUP BY d.department_id 
        ORDER BY memo_count DESC
    ");
    
    echo '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>Department</th><th>Memos Sent</th></tr></thead>';
    echo '<tbody>';
    
    while ($dept = mysqli_fetch_assoc($dept_activity)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($dept['department_name']) . '</td>';
        echo '<td><span class="badge bg-primary">' . $dept['memo_count'] . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
    echo '</div></div></div>';
    
    // Recent system activity
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>🕒 Recent System Activity</h5></div>';
    echo '<div class="card-body">';
    
    $recent_activity = mysqli_query($connection, "
        SELECT m.subject, u.first_name, u.last_name, m.created_at, 
               COUNT(mr.recipient_id) as recipient_count
        FROM memos m 
        JOIN users u ON m.sender_id = u.user_id 
        LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        GROUP BY m.memo_id 
        ORDER BY m.created_at DESC 
        LIMIT 10
    ");
    
    echo '<div style="max-height: 300px; overflow-y: auto;">';
    while ($activity = mysqli_fetch_assoc($recent_activity)) {
        echo '<div class="d-flex justify-content-between align-items-center border-bottom py-2">';
        echo '<div>';
        echo '<strong>' . htmlspecialchars($activity['subject']) . '</strong><br>';
        echo '<small class="text-muted">By ' . htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) . '</small>';
        echo '</div>';
        echo '<div class="text-end">';
        echo '<small>' . date('M j, g:i A', strtotime($activity['created_at'])) . '</small><br>';
        echo '<span class="badge bg-secondary">' . $activity['recipient_count'] . ' recipients</span>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div></div></div>';
    echo '</div>';
    
    // Priority distribution
    echo '<div class="row mt-4">';
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>⚡ Priority Distribution</h5></div>';
    echo '<div class="card-body">';
    
    $priority_stats = mysqli_query($connection, "
        SELECT priority, COUNT(*) as count 
        FROM memos 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY priority 
        ORDER BY count DESC
    ");
    
    echo '<div class="row">';
    while ($priority = mysqli_fetch_assoc($priority_stats)) {
        $badge = getPriorityBadge($priority['priority']);
        echo '<div class="col-6 mb-2">';
        echo '<div class="d-flex justify-content-between">';
        echo '<div>' . $badge . '</div>';
        echo '<div><strong>' . $priority['count'] . '</strong></div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div></div></div>';
    
    // User engagement
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>👤 User Engagement (Last 30 Days)</h5></div>';
    echo '<div class="card-body">';

    $user_engagement = mysqli_query($connection, "
    SELECT * FROM (
        SELECT 
            u.first_name, 
            u.last_name, 
            d.department_name,
            COUNT(DISTINCT m.memo_id) AS sent_count,
            COUNT(DISTINCT mr.memo_id) AS received_count
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN memos m ON u.user_id = m.sender_id AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN memo_recipients mr ON u.user_id = mr.user_id 
        LEFT JOIN memos m2 ON mr.memo_id = m2.memo_id AND m2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE u.is_active = 1 
        GROUP BY u.user_id
    ) AS user_activity
    WHERE sent_count > 0 OR received_count > 0
    ORDER BY (sent_count + received_count) DESC 
    LIMIT 10
    ");
    
    echo '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>User</th><th>Sent</th><th>Received</th></tr></thead>';
    echo '<tbody>';
    
    while ($user = mysqli_fetch_assoc($user_engagement)) {
        echo '<tr>';
        echo '<td>';
        echo '<strong>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</strong><br>';
        echo '<small class="text-muted">' . htmlspecialchars($user['department_name'] ?? 'No Dept') . '</small>';
        echo '</td>';
        echo '<td><span class="badge bg-success">' . $user['sent_count'] . '</span></td>';
        echo '<td><span class="badge bg-info">' . $user['received_count'] . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
    echo '</div></div></div>';
    echo '</div>';
    
    echo '</div>';
}

// Function to acknowledge a memo
function acknowledgeMemo($memo_id, $uid) {
    global $connection;
    
    $stmt = mysqli_prepare($connection, "UPDATE memo_recipients SET acknowledged_at = NOW() WHERE memo_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $memo_id, $uid);
    
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="alert alert-success">Memo acknowledged successfully!</div>';
        echo '<script>setTimeout(function() { window.location.href = "dologin.php?op=view_memo&memo_id=' . $memo_id . '"; }, 2000);</script>';
    } else {
        echo '<div class="alert alert-danger">Error acknowledging memo. Please try again.</div>';
    }
}

// Function to forward a memo
function forwardMemo($memo_id, $uid) {
    global $connection;
    
    // Check if user has access to this memo
    $access_check = mysqli_query($connection, "
        SELECT m.*, mr.user_id 
        FROM memos m 
        LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        WHERE m.memo_id = $memo_id 
        AND (m.sender_id = $uid OR mr.user_id = $uid)
    ");
    
    if (mysqli_num_rows($access_check) == 0) {
        echo '<div class="alert alert-danger">Access denied or memo not found.</div>';
        return;
    }
    
    $memo = mysqli_fetch_assoc($access_check);
    
    if ($_POST['action'] ?? '' === 'send_forward') {
        $forward_to = $_POST['forward_to'] ?? [];
        $forward_note = trim($_POST['forward_note'] ?? '');
        
        if (!empty($forward_to)) {
            foreach ($forward_to as $recipient_id) {
                // Log the forwarding action
                $forward_stmt = mysqli_prepare($connection, "
                    INSERT INTO memo_forwarding (original_memo_id, forwarded_by, forwarded_to, forwarded_at, forwarding_note) 
                    VALUES (?, ?, ?, NOW(), ?)
                ");
                mysqli_stmt_bind_param($forward_stmt, "iiis", $memo_id, $uid, $recipient_id, $forward_note);
                mysqli_stmt_execute($forward_stmt);
                
                // Add recipient to memo_recipients if not already there
                $check_recipient = mysqli_query($connection, "SELECT recipient_id FROM memo_recipients WHERE memo_id = $memo_id AND user_id = $recipient_id");
                if (mysqli_num_rows($check_recipient) == 0) {
                    $recipient_stmt = mysqli_prepare($connection, "INSERT INTO memo_recipients (memo_id, user_id, is_forwarded) VALUES (?, ?, 1)");
                    mysqli_stmt_bind_param($recipient_stmt, "ii", $memo_id, $recipient_id);
                    mysqli_stmt_execute($recipient_stmt);
                }
            }
            
            echo '<div class="alert alert-success">Memo forwarded successfully to ' . count($forward_to) . ' recipient(s)!</div>';
            echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo_id . '" class="btn btn-primary">Back to Memo</a>';
            return;
        } else {
            echo '<div class="alert alert-warning">Please select at least one recipient.</div>';
        }
    }
    
    // Show forward form
    echo '<div class="container-fluid">';
    echo '<h2>📤 Forward Memo</h2>';
    
    echo '<div class="row">';
    echo '<div class="col-md-8">';
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5>Original Memo: ' . htmlspecialchars($memo['subject']) . '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="send_forward">';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Forward to: *</label>';
    echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px;">';
    
    // List users by department
    $users_result = mysqli_query($connection, "
        SELECT u.*, d.department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        WHERE u.is_active = 1 AND u.user_id != $uid 
        ORDER BY d.department_name, u.last_name, u.first_name
    ");
    
    $current_dept = '';
    while ($user = mysqli_fetch_assoc($users_result)) {
        if ($user['department_name'] != $current_dept) {
            if ($current_dept != '') echo '</div>';
            $current_dept = $user['department_name'] ?? 'No Department';
            echo '<h6 class="mt-2 text-primary">' . htmlspecialchars($current_dept) . '</h6>';
            echo '<div>';
        }
        
        echo '<div class="form-check">';
        echo '<input class="form-check-input" type="checkbox" name="forward_to[]" value="' . $user['user_id'] . '" id="user_' . $user['user_id'] . '">';
        echo '<label class="form-check-label" for="user_' . $user['user_id'] . '">';
        echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        if ($user['position']) {
            echo '<br><small class="text-muted">' . htmlspecialchars($user['position']) . '</small>';
        }
        echo '</label>';
        echo '</div>';
    }
    echo '</div></div>';
    
    echo '<div class="mb-3">';
    echo '<label class="form-label">Forwarding Note (Optional)</label>';
    echo '<textarea name="forward_note" class="form-control" rows="3" placeholder="Add a note explaining why you\'re forwarding this memo..."></textarea>';
    echo '</div>';
    
    echo '<button type="submit" class="btn btn-primary">📤 Forward Memo</button>';
    echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo_id . '" class="btn btn-secondary ms-2">Cancel</a>';
    
    echo '</form>';
    echo '</div></div></div>';
    
    // Show memo preview
    echo '<div class="col-md-4">';
    echo '<div class="card">';
    echo '<div class="card-header"><h6>📄 Memo Preview</h6></div>';
    echo '<div class="card-body">';
    echo '<h6>' . htmlspecialchars($memo['subject']) . '</h6>';
    echo '<p class="small">' . htmlspecialchars(substr($memo['content'], 0, 200)) . '...</p>';
    echo '<small class="text-muted">Created: ' . date('M j, Y', strtotime($memo['created_at'])) . '</small>';
    echo '</div></div></div>';
    
    echo '</div></div>';
}

// Additional functions will be added as needed...

// Function to display footer
function showFooter() {
    echo '<footer class="text-center mt-4">';
    echo '<p class="text-muted">© ' . date('Y') . ' Hospital Memo System. All rights reserved.</p>';
    echo '</footer>';
    
    // Bootstrap JavaScript - required for modals, dropdowns, etc.
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    
    echo '</body>';
    echo '</html>';
}


