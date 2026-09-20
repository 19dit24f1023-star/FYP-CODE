<?php
session_start();

/*
|--------------------------------------------------------------------------
| LOGOUT SECURITY CONTROLLER
|--------------------------------------------------------------------------
| Destroys active administration server context and securely clears cookies.
|--------------------------------------------------------------------------
*/

// 1. Unset all session array properties
$_SESSION = array();

// 2. Completely destroy the active browser session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Clear data block files on the host filesystem 
session_destroy();

// 4. Initialize a new isolated flash scope session for safe notification routing
session_start();
$_SESSION['login_notice'] = 'You have logged out successfully.';

// 5. Transfer client routing control structure back to standard access login index
header("Location: login.php");
exit();
?>
