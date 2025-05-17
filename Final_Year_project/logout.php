<?php
// logout.php - Handles logging the student out (in Project Root)

// Start the session. NO output before this line.
session_start();

// Unset specific student session variables
unset($_SESSION['student_id']);
// Keep other session variables like admin_id if they should persist

// If you want to destroy the ENTIRE session (both student and admin), use the commented code below instead:
// $_SESSION = array(); // Clear the $_SESSION array
// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }
// session_destroy();


// Redirect to the student login page (in root)
header('Location: login.php');
exit();

// Note: No closing PHP tag is intentional