<?php
// admin/authenticate.php - Handles admin login form submission with Activity Logging

// Start the session. NO output before this line.
session_start();

// Include database connection
require_once '../includes/db_connection.php'; // Path goes UP one directory to root, then into includes

// Handle POST request from admin login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize user input
    $username = mysqli_real_escape_string($link, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Basic Input Validation ---
    if (empty($username) || empty($password)) {
        $_SESSION['admin_login_error'] = 'Please enter both username and password.';
        header('Location: login.php'); // Redirect back to admin login (in same folder)
        exit();
    }
    // --- End Input Validation ---


    // --- Find the admin user by username ---
    $sql_find_admin = "SELECT staff_id, username, password_hash, role, is_active FROM staff WHERE username = ?";

    if ($stmt_find_admin = mysqli_prepare($link, $sql_find_admin)) {
        mysqli_stmt_bind_param($stmt_find_admin, "s", $username);

        if (mysqli_stmt_execute($stmt_find_admin)) {
            $result_find_admin = mysqli_stmt_get_result($stmt_find_admin);

            if (mysqli_num_rows($result_find_admin) === 1) {
                $admin_info = mysqli_fetch_assoc($result_find_admin);
                $staff_id = $admin_info['staff_id'];
                $stored_password_hash = $admin_info['password_hash'];
                $is_active = $admin_info['is_active'];

                // --- Check if admin is active ---
                if (!$is_active) {
                     $_SESSION['admin_login_error'] = 'Your account is not active.';
                     error_log('Admin Login Failed: Account inactive for username: ' . $username);
                }
                // --- Verify the password ---
                else if (password_verify($password, $stored_password_hash)) {
                    // --- ADMIN LOGIN SUCCESS! ---
                    $_SESSION['admin_id'] = $staff_id;
                    $_SESSION['admin_username'] = $admin_info['username'];
                    $_SESSION['admin_role'] = $admin_info['role'];

                    session_regenerate_id(true);
                    unset($_SESSION['admin_login_error']);

                    // --- Log Successful Admin Login Activity ---
                    if (isset($link) && $link !== false) {
                        $activity_type = 'admin_login';
                        $description = "Admin '" . mysqli_real_escape_string($link, $username) . "' logged in successfully.";
                        $admin_id = $staff_id; // The admin who logged in
                        $user_id = null; // Not related to a student user
                        $related_id = null; // Not related to a specific record like product or transaction

                        $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, user_id, related_id) VALUES (NOW(), ?, ?, ?, ?, ?)";
                        if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                            // Use 's' for string types, 'i' for integer (admin_id), 'i' for user_id (null), 'i' for related_id (null)
                            // Note: If user_id and related_id are INT in DB and nullable, passing null works. If NOT NULL, pass 0 or a placeholder.
                            mysqli_stmt_bind_param($stmt_log, "ssiii", $activity_type, $description, $admin_id, $user_id, $related_id);
                            mysqli_stmt_execute($stmt_log); // Execute without strict error checking here
                            mysqli_stmt_close($stmt_log);
                        } else {
                            error_log("Error preparing activity log query for admin login: " . mysqli_error($link));
                        }
                    }
                    // --- End Log Successful Admin Login Activity ---


                    // Redirect to the admin dashboard (in the same admin folder)
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Password does not match
                    $_SESSION['admin_login_error'] = 'Invalid credentials.';
                     error_log('Admin Login Failed: Password mismatch for username: ' . $username);
                }

            } else {
                // No admin user found or multiple found
                $_SESSION['admin_login_error'] = 'Invalid credentials.';
                 error_log('Admin Login Failed: User not found or multiple found for username: ' . $username);
            }
             mysqli_stmt_close($stmt_find_admin);

        } else {
            $_SESSION['admin_login_error'] = 'Database error during login.';
            error_log('DB Error: execute admin fetch: ' . mysqli_stmt_error($stmt_find_admin));
        }
    } else {
         $_SESSION['admin_login_error'] = 'Database error during login.';
         error_log('DB Error: prepare admin fetch: ' . mysqli_error($link));
    }

} else {
    // If the request method is not POST
    $_SESSION['admin_login_error'] = 'Invalid request method.';
     error_log('admin_authenticate.php received non-POST request.');
}

mysqli_close($link);

// Redirect back to the admin login page in case of any failure
header('Location: login.php'); // Redirect back to admin login (in same folder)
exit();

// Note: No closing PHP tag is intentional
?>
