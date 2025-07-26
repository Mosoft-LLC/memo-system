<?php
    ob_start();
    include('encode.php');
    include("functions.php");
    
    // Check if user is logged in using session variables
    if (is_loggedin($_SESSION['sid'] ?? '', session_id())) {
        setHeader(); 
    } else { 
        header("Location: index.php");
        exit();
    }
    
    // Get operation from POST or GET
    $op = $_REQUEST['op'] ?? 'dashboard';
    $memo_id = $_REQUEST['memo_id'] ?? '';
    $id = $_REQUEST['id'] ?? '';
    $uid = $_SESSION['uid'] ?? '';
    
    // Handle operations
    if ($op == "acknowledge_memo") { acknowledgeMemo($_POST['memo_id'], $uid); }
    if ($op == "logout") { logout(); exit; }
?>
<br>
<table width=100% CellPadding=2 CellSpacing=4 border=0>
<tr>
    <td align=left valign=top width=25%>
    <!-- code for menu etc -->
    <?php  genMenu($uid) ?>
    <!--- end of code for menu etc -->
    </td>
    <td align=left valign=top width=75%>
    <!-- code for main display area -->
<?php
    // Handle different operations using switch for better organization
    switch($op) {
        case "dashboard":
        default:
            showDashboard($uid);
            break;
            
        case "inbox":
            showInbox($uid);
            break;
            
        case "compose":
            showCompose($uid);
            break;
            
        case "sent":
            showSentMemos($uid);
            break;
            
        case "view_memo":
            viewMemo($memo_id, $uid);
            break;
            
        case "listcontacts":
            listContacts($uid);
            break;
            
        case "adminusers":
            $username = $_REQUEST['username'] ?? '';
            $fname = $_REQUEST['fname'] ?? '';
            $lname = $_REQUEST['lname'] ?? '';
            $password = $_REQUEST['password'] ?? '';
            $action = $_REQUEST['action'] ?? 0;
            adminusers($username, $fname, $lname, $password, $action);
            break;
            
        case "manage_departments":
            manageDepartments($uid);
            break;
            
        case "manage_categories":
            manageCategories($uid);
            break;
            
        case "manage_roles":
            manageRoles($uid);
            break;
            
        case "system_reports":
            showSystemReports($uid);
            break;
            
        case "acknowledge_memo":
            acknowledgeMemo($_POST['memo_id'], $uid);
            break;
            
        case "forward_memo":
            forwardMemo($_REQUEST['memo_id'], $uid);
            break;
            
        case "generate_pdf":
            // Clean PDF generation - clear any output buffers and headers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            require_once 'pdf_generator.php';
            $memo_id = $_REQUEST['memo_id'] ?? 0;
            if ($memo_id > 0) {
                $pdf_result = generateMemoPDF($memo_id, $uid);
                if ($pdf_result['success']) {
                    // Redirect to download page with success message
                    header("Location: dologin.php?op=pdf_success&memo_id=$memo_id&file=" . urlencode($pdf_result['filename']));
                    exit();
                } else {
                    // Redirect to error page
                    header("Location: dologin.php?op=pdf_error&memo_id=$memo_id&error=" . urlencode($pdf_result['message']));
                    exit();
                }
            } else {
                header("Location: dologin.php?op=pdf_error&memo_id=0&error=" . urlencode("Invalid memo ID"));
                exit();
            }
            break;
            
        case "pdf_success":
            $memo_id = $_REQUEST['memo_id'] ?? 0;
            $filename = $_REQUEST['file'] ?? '';
            echo '<div class="container-fluid">';
            echo '<div class="alert alert-success">';
            echo '<h4>📄 PDF Generated Successfully!</h4>';
            echo '<p>Your memo has been converted to PDF format.</p>';
            echo '<div class="d-flex gap-2">';
            echo '<a href="dologin.php?op=download_pdf&file=' . urlencode($filename) . '" target="_blank" class="btn btn-primary">📥 Download PDF</a>';
            echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo_id . '" class="btn btn-secondary">← Back to Memo</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            break;
            
        case "pdf_error":
            $memo_id = $_REQUEST['memo_id'] ?? 0;
            $error_message = $_REQUEST['error'] ?? 'Unknown error';
            echo '<div class="container-fluid">';
            echo '<div class="alert alert-danger">';
            echo '<h4>❌ PDF Generation Failed</h4>';
            echo '<p>Error: ' . htmlspecialchars($error_message) . '</p>';
            if ($memo_id > 0) {
                echo '<a href="dologin.php?op=view_memo&memo_id=' . $memo_id . '" class="btn btn-secondary">← Back to Memo</a>';
            } else {
                echo '<a href="dologin.php?op=dashboard" class="btn btn-secondary">← Back to Dashboard</a>';
            }
            echo '</div>';
            echo '</div>';
            break;
            break;
            
        case "download_pdf":
            // Clean download - clear any output buffers and headers before serving file
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            require_once 'pdf_generator.php';
            $filename = $_REQUEST['file'] ?? $_REQUEST['filename'] ?? '';
            if (!empty($filename)) {
                downloadMemoPDF($filename, $uid);
                // downloadMemoPDF will exit, so this line won't be reached
            } else {
                http_response_code(400);
                die('Invalid filename.');
            }
            break;
            
        case "get_role_data":
            // AJAX endpoint to get role data for editing
            header('Content-Type: application/json');
            global $connection;
            $role_id = $_REQUEST['role_id'] ?? 0;
            
            if ($role_id > 0) {
                $stmt = mysqli_prepare($connection, "SELECT * FROM user_roles WHERE role_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $role_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $role = mysqli_fetch_assoc($result);
                
                if ($role) {
                    echo json_encode(['success' => true, 'role' => $role]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Role not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
            }
            exit; // Don't render the rest of the page for AJAX
    }
?>

    <!-- end of code for main display -->
    </td>
</tr>
</table>
<?php showFooter(); ?>