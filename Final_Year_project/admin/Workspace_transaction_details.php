<?php
// admin/fetch_transaction_details.php - Fetches items for a specific transaction

// Start the session
session_start();

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

$response = ['success' => false, 'message' => 'Error fetching transaction details.', 'items' => []];

// Check if transaction ID is provided in the request
$txn_id = filter_input(INPUT_GET, 'txn_id', FILTER_VALIDATE_INT); // Using GET for simplicity for now

if ($txn_id === false || $txn_id === null) {
    $response['message'] = 'Invalid transaction ID provided.';
     error_log('Validation Error (admin/fetch_transaction_details.php): Invalid txn_id: ' . ($txn_id ?? 'NULL'));
} else {
    // --- Fetch transaction items from the database ---
    // This query joins transaction_item with food to get item details
    $sql = "SELECT
                ti.item_id,
                ti.txn_id,
                ti.food_id,
                ti.quantity,
                ti.unit_price,
                ti.item_total, -- Item total is calculated in the DB based on your schema
                f.name AS food_name, -- Get food name from food table
                f.image_path AS food_image_path -- Get food image path from food table
            FROM
                transaction_item ti -- Corrected table name (transaction_item)
            JOIN -- Use JOIN as transaction_items should always have a food item and a transaction
                food f ON ti.food_id = f.food_id -- Corrected join table and columns
            WHERE
                ti.txn_id = ?"; // Filter by the specific transaction ID


    error_log("DEBUG: Fetch Transaction Details SQL query about to be prepared: " . $sql); // Log the SQL query


    if ($stmt = mysqli_prepare($link, $sql)) {

        error_log("DEBUG: Fetch Transaction Details: Statement prepared successfully."); // Log preparation success
        // Bind the transaction ID parameter
        mysqli_stmt_bind_param($stmt, "i", $txn_id); // 'i' for integer transaction ID
        error_log("DEBUG: Fetch Transaction Details: Bound txn_id: " . $txn_id); // Log bound parameter


        if (mysqli_stmt_execute($stmt)) {
             error_log("DEBUG: Fetch Transaction Details: Statement executed successfully."); // Log execution success
            $result = mysqli_stmt_get_result($stmt);

            if ($result) { // Check if get_result was successful
                 error_log("DEBUG: Fetch Transaction Details: Got result set.");
                if (mysqli_num_rows($result) > 0) {
                    // Fetch all results as an associative array
                    $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
                     error_log("DEBUG: Fetch Transaction Details: Found " . count($items) . " items."); // Log item count

                    $response['success'] = true;
                    $response['message'] = 'Transaction details fetched successfully.';
                    $response['items'] = $items;
                } else {
                    // Success, but no items found for this transaction ID
                     error_log("DEBUG: Fetch Transaction Details: No items found for txn_id: " . $txn_id);
                    $response['success'] = true;
                    $response['message'] = 'No items found for this transaction.';
                    $response['items'] = []; // Ensure items array is empty
                }

                mysqli_free_result($result); // Free result set memory

            } else {
                 $response['message'] = 'Database error getting result set for transaction items.';
                 error_log('DB Error (admin/fetch_transaction_details.php): get_result failed: ' . mysqli_stmt_error($stmt));
                 $response['message'] .= ' SQL Error: ' . mysqli_stmt_error($stmt); // Include SQL error
            }


        } else {
            $response['message'] = 'Database error executing transaction details fetch.';
            error_log('DB Error (admin/fetch_transaction_details.php): execute fetch: ' . mysqli_stmt_error($stmt));
             // Include SQL error in message for debugging (remove in production)
             $response['message'] .= ' SQL Error: ' . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt); // Close statement

    } else {
        $response['message'] = 'Database error preparing transaction details fetch.';
        error_log('DB Error (admin/fetch_transaction_details.php): prepare fetch: ' . mysqli_error($link));
         // Include SQL error in message for debugging (remove in production)
         $response['message'] .= ' SQL Error: ' . mysqli_error($link);
    }
}


// Close the database connection
if (isset($link)) {
    // mysqli_close($link); // It's often safer to omit this and let PHP close automatically
}

// Send the JSON response back to the frontend
error_log("--- Sending Final Response (fetch_transaction_details.php): " . json_encode($response) . " ---");
echo json_encode($response);

// Note: No closing PHP tag here is intentional. This prevents accidental whitespace.
?>