<?php
// admin/fetch_transactions.php - Fetches transaction data for the admin transaction list (Corrected for User's DB Schema)

// Start the session
session_start();

// --- ADD LOGGING HERE ---
// These logs will help us see what data is received
// You can remove these logs once transactions are loading correctly
error_log("--- fetch_transactions.php received request ---");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("--- END RECEIVED DATA LOGS ---");
// --- END LOGGING ---


// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Send a JSON error response if not logged in
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit(); // Stop script execution
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // This file MUST correctly create the $link variable

// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error fetching transactions.', 'transactions' => []];

// --- Fetch transaction data from the database ---
// Corrected query using table and column names from smart_canteen (2).sql and live DB checks
// Removed payment_method as it doesn't exist in the transaction table per user confirmation.
$sql = "SELECT
            t.txn_id, -- Corrected transaction ID column name
            t.student_id, -- Corrected user/student ID column name
            s.username AS customer_username, -- Corrected user/student table name and get username (ensure username column exists in student table)
            t.total_amount,
            t.transaction_time AS transaction_date, -- Corrected date column name from txn_date to transaction_time and alias for frontend
            t.status, -- Status column exists
            COUNT(ti.item_id) AS total_items -- Corrected order item table name and item ID column name
        FROM
            transaction t -- Corrected transaction table name (singular)
        LEFT JOIN -- Use LEFT JOIN to include transactions even if student_id is NULL or student doesn't exist
            student s ON t.student_id = s.student_id -- Corrected join table (student) and columns (student_id)
        LEFT JOIN -- Use LEFT JOIN to include transactions even if they have no items (shouldn't happen normally)
            transaction_item ti ON t.txn_id = ti.txn_id -- Corrected join table (transaction_item) and join column (txn_id)
        GROUP BY -- Group results by transaction so we get one row per transaction
            t.txn_id,
            t.student_id,
            s.username, -- Group by s.username as well (ensure username column exists in student table)
            t.total_amount,
            t.transaction_time, -- Corrected grouping by transaction_time
            t.status -- Group by status
            -- Removed payment_method from GROUP BY
        ORDER BY
            t.transaction_time DESC"; // Order by the correct date column name transaction_time


// --- Add logging before preparing the SQL query ---
// This log will show the exact query string being passed to mysqli_prepare
error_log("DEBUG: Fetch Transactions SQL query about to be prepared: " . $sql);
// --- END Logging before prepare ---


if ($stmt = mysqli_prepare($link, $sql)) {

    error_log("DEBUG: Fetch Transactions: Statement prepared successfully."); // Log preparation success
    // No parameters to bind for this initial fetch

    if (mysqli_stmt_execute($stmt)) {
         error_log("DEBUG: Fetch Transactions: Statement executed successfully."); // Log execution success
        $result = mysqli_stmt_get_result($stmt);

        if ($result) { // Check if get_result was successful
             error_log("DEBUG: Fetch Transactions: Got result set.");
            if (mysqli_num_rows($result) > 0) {
                // Fetch all results as an associative array
                $transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);
                 error_log("DEBUG: Fetch Transactions: Found " . count($transactions) . " rows."); // Log row count

                $response['success'] = true;
                $response['message'] = 'Transactions fetched successfully.';
                $response['transactions'] = $transactions;
            } else {
                // Still success, but no data found
                 error_log("DEBUG: Fetch Transactions: No rows found.");
                $response['success'] = true;
                $response['message'] = 'No transactions found.';
                $response['transactions'] = [];
            }

            mysqli_free_result($result); // Free result set memory

        } else {
             $response['message'] = 'Database error getting result set for transactions.';
             error_log('DB Error (admin/fetch_transactions.php): get_result failed: ' . mysqli_stmt_error($stmt));
             // Include SQL error in message for debugging (remove in production)
             $response['message'] .= ' SQL Error: ' . mysqli_stmt_error($stmt);
        }


    } else {
        $response['message'] = 'Database error preparing transaction fetch.';
        error_log('DB Error (admin/fetch_transactions.php): prepare fetch: ' . mysqli_error($link));
     // Include SQL error in message for debugging (remove in production)
     $response['message'] .= ' SQL Error: ' . mysqli_error($link);
    }

    mysqli_stmt_close($stmt); // Close statement

} else {
    $response['message'] = 'Database error preparing transaction fetch.';
    error_log('DB Error (admin/fetch_transactions.php): prepare fetch: ' . mysqli_error($link));
     // Include SQL error in message for debugging (remove in production)
     $response['message'] .= ' SQL Error: ' . mysqli_error($link);
}

// Close the database connection
if (isset($link)) {
    // mysqli_close($link); // It's often safer to omit this and let PHP close automatically
}

// Send the JSON response back to the frontend
error_log("--- Sending Final Response (fetch_transactions.php): " . json_encode($response) . " ---");
echo json_encode($response);

// Note: No closing PHP tag here is intentional. This prevents accidental whitespace.
?>