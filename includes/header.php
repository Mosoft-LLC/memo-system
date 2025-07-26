<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($sitetitle) ? htmlspecialchars($sitetitle) : 'Hospital Memo System'; ?></title>
    <meta name="description" content="Hospital Memo Distribution and Management System">
    <meta name="author" content="Hospital IT Department">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='15' fill='%230D47A1'/><rect x='8' y='6' width='16' height='20' rx='1' fill='white' stroke='%23e0e0e0' stroke-width='0.5'/><rect x='8' y='6' width='16' height='4' rx='1' fill='%2342A5F5'/><rect x='10' y='13' width='10' height='1' fill='%23333'/><rect x='10' y='15' width='12' height='1' fill='%23333'/><rect x='10' y='17' width='8' height='1' fill='%23333'/><rect x='10' y='19' width='11' height='1' fill='%23333'/><rect x='10' y='21' width='9' height='1' fill='%23333'/></svg>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS for enhanced UI/UX -->
    <style>
        :root {
            --primary-color: #0D47A1;
            --primary-light: #42A5F5;
            --secondary-color: #FF6B35;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --danger-color: #F44336;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --bg-light: #f8f9fa;
            --border-color: #dee2e6;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --border-radius: 8px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        
        /* Enhanced Header */
        .hospital-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1565C0 50%, var(--primary-light) 100%);
            color: white;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            padding: 1rem 0;
        }
        
        .system-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .system-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 400;
            margin-top: 0.25rem;
        }
        
        .header-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
        }
        
        /* Enhanced Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
            margin-bottom: 1rem;
        }
        
        h1 { font-size: 2.25rem; }
        h2 { font-size: 1.875rem; }
        h3 { font-size: 1.5rem; }
        h4 { font-size: 1.25rem; }
        h5 { font-size: 1.125rem; }
        h6 { font-size: 1rem; }
        
        /* Enhanced Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            background: white;
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        
        .card-header {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            font-weight: 600;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Enhanced Buttons */
        .btn {
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0B3D91;
            color: white;
        }
        
        /* Enhanced Badges */
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Priority Badges */
        .priority-urgent {
            background: linear-gradient(135deg, #FF5252, #D32F2F);
            color: white;
            animation: pulse 2s infinite;
        }
        
        .priority-high {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
        }
        
        .priority-normal {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
        }
        
        .priority-low {
            background: linear-gradient(135deg, #9E9E9E, #616161);
            color: white;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Enhanced Navigation */
        .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-link:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        /* Enhanced Tables */
        .table {
            font-size: 0.875rem;
        }
        
        .table th {
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 71, 161, 0.04);
        }
        
        /* Enhanced Forms */
        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
        }
        
        /* Enhanced Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #E8F5E8, #C8E6C9);
            color: #2E7D32;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            color: #EF6C00;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #FFEBEE, #FFCDD2);
            color: #C62828;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #E3F2FD, #BBDEFB);
            color: #1565C0;
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-new .status-dot { background: var(--primary-color); }
        .status-read .status-dot { background: var(--success-color); }
        .status-pending .status-dot { background: var(--warning-color); }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .system-title {
                font-size: 1.5rem;
            }
            
            .header-content {
                text-align: center;
            }
            
            .header-status {
                justify-content: center;
                margin-top: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }
        
        /* Animation utilities */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body>
    <!-- Enhanced Hospital Header -->
    <header class="hospital-header">
        <div class="container">
            <div class="header-content">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="system-title">
                            <i class="fas fa-hospital-user"></i>
                            <span><?php echo isset($sitetitle) ? htmlspecialchars($sitetitle) : 'Hospital Memo System'; ?></span>
                        </h1>
                        <div class="system-subtitle">
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure Medical Communication & Distribution Platform
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="header-status">
                            <?php if (isset($_SESSION['uid'])): ?>
                                <div class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <i class="fas fa-user-circle"></i>
                                        <span>Online</span>
                                    </div>
                                    <small class="opacity-75">
                                        <?php echo date('l, F j, Y g:i A'); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="container" style="margin-top: 20px;">

<script>
// Modern JavaScript for hospital memo system
'use strict';

function action(mode, id) {
    // Modern confirm dialogs with better UX
    switch(mode) {
        case 'deleteNews':
            if (confirm(`Delete News ID: ${id}?\n\nThis action cannot be undone.`)) {
                window.location.href = `dologin.php?op=deletenews&id=${id}`;
            }
            break;
            
        case 'deleteUser':
            if (id == 1) {
                alert('⚠️ System Administrator cannot be deleted!\n\nThis account is required for system maintenance.');
                return;
            }
            if (confirm(`Delete User ID: ${id}?\n\n⚠️ This will also delete all their memos and data.\nThis action cannot be undone.`)) {
                window.location.href = `dologin.php?op=deleteuser&id=${id}`;
            }
            break;
            
        case 'sendMemo':
            window.location.href = `dologin.php?op=compose&id=${id}`;
            break;
            
        case 'forwardMemo':
            window.location.href = `dologin.php?op=forward&memo_id=${id}`;
            break;
            
        case 'acknowledgeMemo':
            if (confirm('Mark this memo as acknowledged?')) {
                // AJAX call for acknowledgment (to be implemented)
                acknowledgeMemo(id);
            }
            break;
    }
}

function checkAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="msgid"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function uncheckAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="msgid"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

// Hospital-specific functions
function acknowledgeMemo(memoId) {
    // Placeholder for AJAX acknowledgment
    console.log(`Acknowledging memo ${memoId}`);
    // Implementation will be added in Phase 4
}

function showPriorityIndicator(priority) {
    const indicators = {
        'Urgent': '🔴',
        'High': '🟠', 
        'Normal': '🔵',
        'Low': '⚪'
    };
    return indicators[priority] || '⚪';
}

// Utility functions for the hospital system
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function showStatusBadge(status) {
    const badges = {
        'Draft': '📝',
        'Sent': '📤',
        'Received': '📥',
        'Read': '👁️',
        'Acknowledged': '✅',
        'Expired': '⏰',
        'Recalled': '↩️'
    };
    return badges[status] || '❓';
}

// Auto-refresh for real-time updates (optional)
function enableAutoRefresh(intervalMinutes = 5) {
    setInterval(() => {
        // Check for new memos without full page reload
        // Implementation will be added in Phase 6
        console.log('Checking for new memos...');
    }, intervalMinutes * 60 * 1000);
}

// Initialize hospital memo system features
document.addEventListener('DOMContentLoaded', function() {
    // Add modern form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add client-side validation here
        });
    });
    
    // Add tooltips for priority indicators
    const priorityElements = document.querySelectorAll('.priority-indicator');
    priorityElements.forEach(element => {
        element.title = element.dataset.priority + ' Priority';
    });
    
    // Auto-save drafts (to be implemented)
    const textareas = document.querySelectorAll('textarea[name="msg"]');
    textareas.forEach(textarea => {
        let saveTimeout;
        textarea.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                // Auto-save draft logic here
                console.log('Auto-saving draft...');
            }, 2000);
        });
    });
});

</script>
</script>