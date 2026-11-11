
<?php

// Start the session if one isn't already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check whether the user is logged in
if (
    !isset($_SESSION["user_id"]) ||
    !is_numeric($_SESSION["user_id"]) ||
    (int) $_SESSION["user_id"] <= 0
) {
    // Redirect unauthenticated users to the sign-in page
    header("Location: signIn.php");
    exit;
}

