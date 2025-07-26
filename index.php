<?php 
    ob_start(); // Start output buffering for gzip compression
    require("functions.php");
    global $connection;
    
    // Session is already started in functions_new.php
    
    // Has the form been filled
    if (isset($_POST['login']) && isset($_POST['password'])){
        $login = $_POST['login'];
        $password = $_POST['password'];
        
        if (is_valid($login, $password)) {
            // Valid user - get user info using prepared statements
            $stmt = mysqli_prepare($connection, "SELECT user_id, first_name, last_name FROM users WHERE username = ? AND is_active = 1");
            mysqli_stmt_bind_param($stmt, "s", $login);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_array($result);
            
            if ($row) {
                // Store session variables
                $_SESSION['uid'] = $row['user_id'];
                $_SESSION['admin'] = $row['first_name'] . " " . $row['last_name'];
                $_SESSION['sid'] = session_id();
                
                // Update last login
                $update_stmt = mysqli_prepare($connection, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
                mysqli_stmt_bind_param($update_stmt, "i", $row['user_id']);
                mysqli_stmt_execute($update_stmt);
                
                header("Location: dologin.php?op=dashboard");
                exit();
            }
        } else {
            $error_message = "Invalid username or password. Please try again.";
        }
    }
include("includes/header.php");
?>

<!--====================->
<!-- Login Screen and rules ---->

<br>
<?php if (isset($error_message)): ?>
<div class="alert alert-danger" role="alert">
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<form method=POST action="index.php">
<table  width=100%  CellPadding=0 CellSpacing=10 border=0>
<tr>
    <td align=center valign=top width=30%>
    <!-- code for login box -->

    <table width=100% CellPadding=0 CellSpacing=0 border=1 bordercolor=#DFDFDF>
    <tr><td>
    <table width=100% CellPadding=2 CellSpacing=0>
    <tr><td COLSPAN=2 align=left bgcolor=#DFDFDF>🏥 Hospital Memo System - Sign In</td></td>
    <tr><td align=left>Username</td><td><input type=text name=login size=28 required></td></tr>
    <tr><td align=left>Password</td><td><input type=password name=password size=28 required></td></tr>
    <tr><td COLSPAN=2 align=right><input type=submit name=op value=Login class="btn btn-primary"></td></tr>
    </table>
    </td></tr>
    </table>
</form>

    <!-- end of code for login box-->
    </td>

    <td align=center valign=top width=70%>
    <!-- code for rules and regulation -->

    <table height=100% width=80% CellPadding=0 CellSpacing=0 border=1 bordercolor=#DFDFDF>
    <tr><td>
    <table width=100% CellPadding=2 CellSpacing=0>
    <tr><td align=left valign=top>
    Instructions
    <ul>
        <li>Only Authorized Personnel Allowed
        <li>Make Sure Cookies Are Enabled 
        <li>Make Sure You Are Using The Right Pair Of Login/Password
    </ul>    
    </td></tr>
    </table>
    </td></tr>
    </table>
 
    <!-- end of code for rules and regulation -->
    </td>
</tr>
</table>
<?php showFooter(); ?>