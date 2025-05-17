<?php
// authenticate.php - Student Authentication Logic (in Project Root)

// Start the session. NO output before this line.
session_start();

// Include database connection
require_once './includes/db_connection.php'; // Path is correct for root files


// Handle POST request from login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize user input
    $identifier = mysqli_real_escape_string($link, $_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Basic Input Validation ---
    if (empty($identifier) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both identifier and password.';
        header('Location: login.php'); // Redirect back to student login (in root)
        exit();
    }
    // --- End Input Validation ---


    // --- Find the student by identifier (Student ID or Email) ---
    $sql_find_student = "SELECT student_id, nfc_id, username, full_name FROM student WHERE student_id = ? OR student_email = ?"; // Added username and full_name

    if ($stmt_find_student = mysqli_prepare($link, $sql_find_student)) {
        mysqli_stmt_bind_param($stmt_find_student, "ss", $identifier, $identifier);

        if (mysqli_stmt_execute($stmt_find_student)) {
            $result_find_student = mysqli_stmt_get_result($stmt_find_student);

            if (mysqli_num_rows($result_find_student) === 1) {
                $student_info = mysqli_fetch_assoc($result_find_student);
                $student_id = $student_info['student_id'];
                $nfc_id = $student_info['nfc_id'];
                $username = $student_info['username']; // Get username
                $full_name = $student_info['full_name'];

                // --- Find the linked NFC Card and Password Hash ---
                if ($nfc_id) { // Check if nfc_id exists and is not NULL
                    $sql_get_hash = "SELECT password_hash FROM nfc_card WHERE nfc_id = ?";
                    if ($stmt_get_hash = mysqli_prepare($link, $sql_get_hash)) {
                        mysqli_stmt_bind_param($stmt_get_hash, "s", $nfc_id);
                        if (mysqli_stmt_execute($stmt_get_hash)) {
                            $result_get_hash = mysqli_stmt_get_result($stmt_get_hash);
                            if (mysqli_num_rows($result_get_hash) === 1) {
                                $card_info = mysqli_fetch_assoc($result_get_hash);
                                $stored_password_hash = $card_info['password_hash'];

                                // --- Verify the password ---
                                if (password_verify($password, $stored_password_hash)) {
                                    // --- LOGIN SUCCESS! ---
                                    $_SESSION['student_id'] = $student_id;
                                    $_SESSION['student_username'] = $username; // Store username  **ADDED THIS LINE**
                                    $_SESSION['student_name'] = $full_name;
                                    session_regenerate_id(true);
                                    unset($_SESSION['login_error']);

                                    header('Location: index.php'); // Redirect to index (in root)
                                    exit();
                                } else {
                                    // Password does not match
                                    $_SESSION['login_error'] = 'Invalid credentials.';
                                    error_log('Login Failed: Password mismatch for identifier: ' . $identifier);
                                }
                            } else {
                                // NFC card not found or multiple found (unexpected database state)
                                $_SESSION['login_error'] = 'Login failed: Could not retrieve linked card details.';
                                error_log('Login Failed: NFC card not found or multiple found for nfc_id: ' . $nfc_id . ' linked to student ID: ' . $student_id);
                            }
                            mysqli_stmt_close($stmt_get_hash);
                        } else {
                            $_SESSION['login_error'] = 'Database error during login.';
                            error_log('DB Error: Could not execute NFC card hash fetch: ' . mysqli_stmt_error($stmt_get_hash));
                        }
                    } else {
                        $_SESSION['login_error'] = 'Database error during login.';
                        error_log('DB Error: Could not prepare NFC card hash fetch query: ' . mysqli_error($link));
                    }
                } else {
                    // Student found, but no nfc_id linked
                    $_SESSION['login_error'] = 'Login failed: No linked card found for this student.';
                    error_log('Login Failed: Student found but no nfc_id linked for student ID: ' . $student_id);
                }
            } else {
                // No student found with that identifier, or multiple found
                $_SESSION['login_error'] = 'Invalid credentials.';
                error_log('Login Failed: Student not found or multiple found for identifier: ' . $identifier);
            }
            mysqli_stmt_close($stmt_find_student);
        } else {
            $_SESSION['login_error'] = 'Database error during login.';
            error_log('DB Error: Could not execute student fetch: ' . mysqli_stmt_error($stmt_find_student));
        }
    } else {
        $_SESSION['login_error'] = 'Database error during login.';
        error_log('DB Error: Could not prepare student fetch query: ' . mysqli_error($link));
    }
} else {
    // If the request method is not POST
    $_SESSION['login_error'] = 'Invalid request method.';
    error_log('authenticate.php received non-POST request.');
}

mysqli_close($link);

// Redirect back to the login page in case of any failure
header('Location: login.php'); // Redirect back to student login (in root)
exit();

// Note: No closing PHP tag is intentional
