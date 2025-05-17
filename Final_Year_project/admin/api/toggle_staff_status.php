<?php
// admin/api/toggle_staff_status.php - Backend API to activate/deactivate staff

// Set your default timezone
date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
$admin_id_logger = $_SESSION['admin_id']; // For activity logging
$admin_username_logger = $_SESSION['admin_username'] ?? 'Admin'; // For activity logging, ensure admin_username is set at login
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/api/ UP two levels (../../) THEN into includes/
require_once '../../includes/db_connection.php';

// --- Check Database Connection ---
if ($link === false) {
    error_log('DB Error (toggle_staff_status.php): Could not connect to database: ' . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
// --- End Check Database Connection ---

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An error occurred.'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// Get the JSON data sent from the frontend
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //Convert JSON into array

if (empty($input) || !isset($input['staff_id']) || !isset($input['action'])) {
    $response['message'] = 'Missing required parameters (staff_id, action).';
    echo json_encode($response);
    exit();
}

$staff_id_to_toggle = filter_var($input['staff_id'], FILTER_VALIDATE_INT);
$action = trim(htmlspecialchars($input['action'])); // 'activate' or 'deactivate'

if ($staff_id_to_toggle === false || $staff_id_to_toggle <= 0) {
    $response['message'] = 'Invalid Staff ID.';
    echo json_encode($response);
    exit();
}

if ($action !== 'activate' && $action !== 'deactivate') {
    $response['message'] = 'Invalid action specified.';
    echo json_encode($response);
    exit();
}

// Determine the new status
$new_status = ($action === 'activate') ? 1 : 0;
$action_past_tense = ($action === 'activate') ? 'activated' : 'deactivated';

// --- Prevent admin from deactivating themselves (optional but good practice) ---
if ($staff_id_to_toggle == $_SESSION['admin_id'] && $action === 'deactivate') {
    $response['message'] = 'You cannot deactivate your own account.';
    echo json_encode($response);
    exit();
}

// --- Update staff status ---
$sqlUpdateStatus = "UPDATE staff SET is_active = ? WHERE staff_id = ?";
if ($stmtUpdate = mysqli_prepare($link, $sqlUpdateStatus)) {
    mysqli_stmt_bind_param($stmtUpdate, "ii", $new_status, $staff_id_to_toggle);

    if (mysqli_stmt_execute($stmtUpdate)) {
        if (mysqli_stmt_affected_rows($stmtUpdate) > 0) {
            $response['success'] = true;
            $response['message'] = "Staff member successfully {$action_past_tense}.";

            // --- Log activity ---
            $staff_username_affected = "ID " . $staff_id_to_toggle; // Placeholder if username isn't readily available
            // Optional: Fetch the username of the affected staff for a more descriptive log
            $sqlFetchUsername = "SELECT username FROM staff WHERE staff_id = ?";
            if ($stmtFetch = mysqli_prepare($link, $sqlFetchUsername)) {
                mysqli_stmt_bind_param($stmtFetch, "i", $staff_id_to_toggle);
                if (mysqli_stmt_execute($stmtFetch)) {
                    $resultUser = mysqli_stmt_get_result($stmtFetch);
                    if ($userRow = mysqli_fetch_assoc($resultUser)) {
                        $staff_username_affected = "'" . $userRow['username'] . "'";
                    }
                }
                mysqli_stmt_close($stmtFetch);
            }

            $log_description = "Admin '{$admin_username_logger}' {$action_past_tense} staff member {$staff_username_affected}.";
            $sqlLog = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), ?, ?, ?, ?)";
            if ($stmtLog = mysqli_prepare($link, $sqlLog)) {
                $activity_type = "staff_{$action}"; // e.g., staff_activate or staff_deactivate
                mysqli_stmt_bind_param($stmtLog, "ssii", $activity_type, $log_description, $admin_id_logger, $staff_id_to_toggle);
                if (!mysqli_stmt_execute($stmtLog)) {
                    error_log("Failed to log activity (toggle_staff_status.php): " . mysqli_stmt_error($stmtLog));
                }
                mysqli_stmt_close($stmtLog);
            } else {
                 error_log("Failed to prepare activity log statement (toggle_staff_status.php): " . mysqli_error($link));
            }
            // --- End log activity ---

        } else {
            $response['message'] = "No changes made. Staff member might already be in the desired state or does not exist.";
             // Still consider it a success if no rows affected but query ran, or set success to false if strict change is expected.
            // For this use case, if the state is already set, it's not really an error.
            $response['success'] = true; // Or false, depending on how you want to treat this.
        }
    } else {
        $response['message'] = "Database error: Could not execute update. Details: " . mysqli_stmt_error($stmtUpdate);
        error_log("DB Execute Error (toggle_staff_status.php): " . mysqli_stmt_error($stmtUpdate));
    }
    mysqli_stmt_close($stmtUpdate);
} else {
    $response['message'] = "Database error: Could not prepare update statement. Details: " . mysqli_error($link);
    error_log("DB Prepare Error (toggle_staff_status.php): " . mysqli_error($link));
}

if (isset($link) && $link) {
    mysqli_close($link);
}

echo json_encode($response);
?>