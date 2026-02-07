<?php
// logout.php
// require_once __DIR__ . '/../includes/session.php';


/* Unset all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Delete session cookie (extra security) */
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

/* Redirect to login page */
header("Location: login.php");
exit;
