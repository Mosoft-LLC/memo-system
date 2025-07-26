<?php
/**
 * Hospital Memo System - Administration Panel
 * Manage departments, users, roles, and system settings
 */

session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    header('Location: dologin.php');
    exit;
}

// Connect to database
$connection = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbname);
if (!$connection) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Check if user has admin role
$user_id = $_SESSION['user_id'];
$admin_check = mysqli_query($connection, "
    SELECT u.*, ur.role_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.role_id = ur.role_id 
    WHERE u.user_id = $user_id AND ur.role_name = 'Administrator'
");

if (mysqli_num_rows($admin_check) == 0) {
    die('Access denied. Administrator privileges required.');
}

$current_user = mysqli_fetch_assoc($admin_check);
$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add_department':
            $dept_name = mysqli_real_escape_string($connection, $_POST['dept_name']);
            $dept_code = mysqli_real_escape_string($connection, $_POST['dept_code']);
            $dept_head = intval($_POST['dept_head']);
            
            $query = "INSERT INTO departments (department_name, department_code, department_head, is_active) 
                     VALUES ('$dept_name', '$dept_code', $dept_head, 1)";
            if (mysqli_query($connection, $query)) {
                $success_msg = "Department added successfully!";
            } else {
                $error_msg = "Error adding department: " . mysqli_error($connection);
            }
            break;
            
        case 'update_user_role':
            $user_id = intval($_POST['user_id']);
            $role_id = intval($_POST['role_id']);
            $dept_id = intval($_POST['dept_id']);
            
            $query = "UPDATE users SET role_id = $role_id, department_id = $dept_id WHERE user_id = $user_id";
            if (mysqli_query($connection, $query)) {
                $success_msg = "User role updated successfully!";
            } else {
                $error_msg = "Error updating user role: " . mysqli_error($connection);
            }
            break;
            
        case 'add_user':
            $username = mysqli_real_escape_string($connection, $_POST['username']);
            $fullname = mysqli_real_escape_string($connection, $_POST['fullname']);
            $email = mysqli_real_escape_string($connection, $_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role_id = intval($_POST['role_id']);
            $dept_id = intval($_POST['dept_id']);
            
            $query = "INSERT INTO users (username, password, full_name, email, role_id, department_id, is_active, created_at) 
                     VALUES ('$username', '$password', '$fullname', '$email', $role_id, $dept_id, 1, NOW())";
            if (mysqli_query($connection, $query)) {
                $success_msg = "User added successfully!";
            } else {
                $error_msg = "Error adding user: " . mysqli_error($connection);
            }
            break;
    }
}

// Get system statistics
$stats = [];
$stats['total_users'] = mysqli_fetch_array(mysqli_query($connection, "SELECT COUNT(*) FROM users WHERE is_active = 1"))[0];
$stats['total_departments'] = mysqli_fetch_array(mysqli_query($connection, "SELECT COUNT(*) FROM departments WHERE is_active = 1"))[0];
$stats['total_memos'] = mysqli_fetch_array(mysqli_query($connection, "SELECT COUNT(*) FROM memos"))[0];
$stats['pending_memos'] = mysqli_fetch_array(mysqli_query($connection, "
    SELECT COUNT(DISTINCT m.memo_id) 
    FROM memos m 
    JOIN memo_distribution md ON m.memo_id = md.memo_id 
    LEFT JOIN memo_receipts mr ON md.distribution_id = mr.distribution_id 
    WHERE mr.receipt_id IS NULL AND md.is_active = 1
"))[0];

include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h3>🏥 Admin Panel</h3>
            <p>Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?></p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="?page=departments" class="nav-item <?php echo $page === 'departments' ? 'active' : ''; ?>">
                🏢 Departments
            </a>
            <a href="?page=users" class="nav-item <?php echo $page === 'users' ? 'active' : ''; ?>">
                👥 Users
            </a>
            <a href="?page=roles" class="nav-item <?php echo $page === 'roles' ? 'active' : ''; ?>">
                🎭 Roles
            </a>
            <a href="?page=memos" class="nav-item <?php echo $page === 'memos' ? 'active' : ''; ?>">
                📋 Memos
            </a>
            <a href="?page=reports" class="nav-item <?php echo $page === 'reports' ? 'active' : ''; ?>">
                📈 Reports
            </a>
            <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                ⚙️ Settings
            </a>
        </nav>
    </div>
    
    <div class="admin-content">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php
        switch ($page) {
            case 'dashboard':
                ?>
                <div class="admin-header">
                    <h1>📊 Dashboard</h1>
                    <p>Hospital Memo System Overview</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_departments']; ?></h3>
                            <p>Departments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_memos']; ?></h3>
                            <p>Total Memos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card urgent">
                        <div class="stat-icon">⏰</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_memos']; ?></h3>
                            <p>Pending Acknowledgments</p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h3>Recent Activity</h3>
                        <?php
                        $recent_activity = mysqli_query($connection, "
                            SELECT m.subject, m.created_at, u.full_name as sender_name, 
                                   mp.priority_name, mp.priority_color
                            FROM memos m 
                            JOIN users u ON m.sender_id = u.user_id 
                            JOIN memo_priorities mp ON m.priority_id = mp.priority_id 
                            ORDER BY m.created_at DESC 
                            LIMIT 10
                        ");
                        
                        while ($activity = mysqli_fetch_assoc($recent_activity)) {
                            echo '<div class="activity-item">';
                            echo '<span class="priority-badge" style="background: ' . $activity['priority_color'] . '">' . $activity['priority_name'] . '</span>';
                            echo '<div class="activity-content">';
                            echo '<strong>' . htmlspecialchars($activity['subject']) . '</strong><br>';
                            echo 'by ' . htmlspecialchars($activity['sender_name']) . ' • ' . date('M j, Y g:i A', strtotime($activity['created_at']));
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3>Department Distribution</h3>
                        <?php
                        $dept_stats = mysqli_query($connection, "
                            SELECT d.department_name, COUNT(u.user_id) as user_count
                            FROM departments d 
                            LEFT JOIN users u ON d.department_id = u.department_id AND u.is_active = 1
                            WHERE d.is_active = 1
                            GROUP BY d.department_id, d.department_name
                            ORDER BY user_count DESC
                        ");
                        
                        while ($dept = mysqli_fetch_assoc($dept_stats)) {
                            $percentage = $stats['total_users'] > 0 ? round(($dept['user_count'] / $stats['total_users']) * 100) : 0;
                            echo '<div class="dept-stat">';
                            echo '<div class="dept-info">';
                            echo '<span class="dept-name">' . htmlspecialchars($dept['department_name']) . '</span>';
                            echo '<span class="dept-count">' . $dept['user_count'] . ' users</span>';
                            echo '</div>';
                            echo '<div class="dept-bar">';
                            echo '<div class="dept-progress" style="width: ' . $percentage . '%"></div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php
                break;
                
            case 'departments':
                ?>
                <div class="admin-header">
                    <h1>🏢 Department Management</h1>
                    <button class="btn btn-primary" onclick="toggleForm('add-dept-form')">+ Add Department</button>
                </div>
                
                <div id="add-dept-form" class="form-container" style="display: none;">
                    <h3>Add New Department</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_department">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department Name:</label>
                                <input type="text" name="dept_name" required>
                            </div>
                            <div class="form-group">
                                <label>Department Code:</label>
                                <input type="text" name="dept_code" required maxlength="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Department Head:</label>
                            <select name="dept_head">
                                <option value="0">No Head Assigned</option>
                                <?php
                                $users = mysqli_query($connection, "SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
                                while ($user = mysqli_fetch_assoc($users)) {
                                    echo '<option value="' . $user['user_id'] . '">' . htmlspecialchars($user['full_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Add Department</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm('add-dept-form')">Cancel</button>
                    </form>
                </div>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Code</th>
                                <th>Head</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $departments = mysqli_query($connection, "
                                SELECT d.*, u.full_name as head_name,
                                       COUNT(uu.user_id) as user_count
                                FROM departments d 
                                LEFT JOIN users u ON d.department_head = u.user_id 
                                LEFT JOIN users uu ON d.department_id = uu.department_id AND uu.is_active = 1
                                GROUP BY d.department_id
                                ORDER BY d.department_name
                            ");
                            
                            while ($dept = mysqli_fetch_assoc($departments)) {
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($dept['department_name']) . '</strong></td>';
                                echo '<td><span class="badge">' . htmlspecialchars($dept['department_code']) . '</span></td>';
                                echo '<td>' . ($dept['head_name'] ? htmlspecialchars($dept['head_name']) : 'Not assigned') . '</td>';
                                echo '<td>' . $dept['user_count'] . '</td>';
                                echo '<td><span class="status-badge ' . ($dept['is_active'] ? 'active' : 'inactive') . '">' . 
                                     ($dept['is_active'] ? 'Active' : 'Inactive') . '</span></td>';
                                echo '<td>';
                                echo '<a href="?page=departments&action=edit&id=' . $dept['department_id'] . '" class="btn btn-sm">Edit</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;
                
            case 'users':
                ?>
                <div class="admin-header">
                    <h1>👥 User Management</h1>
                    <button class="btn btn-primary" onclick="toggleForm('add-user-form')">+ Add User</button>
                </div>
                
                <div id="add-user-form" class="form-container" style="display: none;">
                    <h3>Add New User</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username:</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Full Name:</label>
                                <input type="text" name="fullname" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Password:</label>
                                <input type="password" name="password" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department:</label>
                                <select name="dept_id" required>
                                    <?php
                                    $departments = mysqli_query($connection, "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
                                    while ($dept = mysqli_fetch_assoc($departments)) {
                                        echo '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Role:</label>
                                <select name="role_id" required>
                                    <?php
                                    $roles = mysqli_query($connection, "SELECT role_id, role_name FROM user_roles ORDER BY role_name");
                                    while ($role = mysqli_fetch_assoc($roles)) {
                                        echo '<option value="' . $role['role_id'] . '">' . htmlspecialchars($role['role_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Add User</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm('add-user-form')">Cancel</button>
                    </form>
                </div>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users = mysqli_query($connection, "
                                SELECT u.*, d.department_name, ur.role_name
                                FROM users u 
                                LEFT JOIN departments d ON u.department_id = d.department_id 
                                LEFT JOIN user_roles ur ON u.role_id = ur.role_id 
                                ORDER BY u.full_name
                            ");
                            
                            while ($user = mysqli_fetch_assoc($users)) {
                                echo '<tr>';
                                echo '<td>';
                                echo '<div class="user-info">';
                                echo '<strong>' . htmlspecialchars($user['full_name']) . '</strong><br>';
                                echo '<small>@' . htmlspecialchars($user['username']) . '</small>';
                                echo '</div>';
                                echo '</td>';
                                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                                echo '<td>' . htmlspecialchars($user['department_name'] ?? 'Not assigned') . '</td>';
                                echo '<td><span class="role-badge">' . htmlspecialchars($user['role_name'] ?? 'No role') . '</span></td>';
                                echo '<td>' . ($user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never') . '</td>';
                                echo '<td><span class="status-badge ' . ($user['is_active'] ? 'active' : 'inactive') . '">' . 
                                     ($user['is_active'] ? 'Active' : 'Inactive') . '</span></td>';
                                echo '<td>';
                                echo '<a href="?page=users&action=edit&id=' . $user['user_id'] . '" class="btn btn-sm">Edit</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                break;
                
            default:
                echo '<div class="admin-header">';
                echo '<h1>Page Under Construction</h1>';
                echo '<p>This section is being developed. Please check back later.</p>';
                echo '</div>';
                break;
        }
        ?>
    </div>
</div>

<style>
.admin-container {
    display: flex;
    min-height: calc(100vh - 120px);
    gap: 0;
}

.admin-sidebar {
    width: 280px;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6b 100%);
    color: white;
    padding: 0;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 30px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
}

.sidebar-header p {
    margin: 0;
    opacity: 0.8;
    font-size: 14px;
}

.sidebar-nav {
    padding: 20px 0;
}

.nav-item {
    display: block;
    padding: 15px 25px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.nav-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.nav-item.active {
    background: rgba(255,255,255,0.15);
    border-left-color: #4caf50;
    font-weight: 600;
}

.admin-content {
    flex: 1;
    padding: 30px;
    background: #f8f9fa;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.admin-header h1 {
    margin: 0;
    color: #2c5aa0;
    font-size: 28px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.urgent {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.stat-icon {
    font-size: 40px;
    opacity: 0.8;
}

.stat-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
}

.stat-content p {
    margin: 5px 0 0 0;
    opacity: 0.8;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.dashboard-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dashboard-card h3 {
    margin: 0 0 20px 0;
    color: #2c5aa0;
    font-size: 18px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.priority-badge {
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    min-width: 60px;
    text-align: center;
}

.activity-content {
    flex: 1;
    font-size: 14px;
}

.dept-stat {
    margin-bottom: 15px;
}

.dept-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.dept-name {
    font-weight: 600;
}

.dept-count {
    color: #666;
    font-size: 14px;
}

.dept-bar {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.dept-progress {
    height: 100%;
    background: linear-gradient(90deg, #2c5aa0, #4caf50);
    transition: width 0.3s ease;
}

.form-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c5aa0;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.data-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #2c5aa0;
    border-bottom: 1px solid #dee2e6;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.badge {
    background: #e9ecef;
    color: #495057;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.role-badge {
    background: #e7f3ff;
    color: #0056b3;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.user-info strong {
    color: #2c5aa0;
}

.btn.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
function toggleForm(formId) {
    const form = document.getElementById(formId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
mysqli_close($connection);
include 'includes/footer.php';
?>
