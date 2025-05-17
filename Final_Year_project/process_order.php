<?php
// process_order.php - Processes the order from the session cart (in Project Root)

// Start the session. NO output before this line.
session_start();

// Include database connection
require_once './includes/db_connection.php'; // Path is correct for root files

// Set the response header to indicate JSON content.
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred during order processing.', 'new_balance' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
        $response['message'] = 'Order failed: User not logged in.';
        error_log('process_order.php failed: User not logged in.');
        echo json_encode($response); exit;
    }

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $response['message'] = 'Order failed: Your cart is empty.';
        echo json_encode($response); exit;
    }

    $student_id = (int)$_SESSION['student_id'];
    $cart = $_SESSION['cart'];
    $order_total = 0;
    $items_for_transaction = [];
    $food_ids_in_cart = array_keys($cart);

    // --- Recalculate Total and Fetch Item Details from DB ---
    if (!empty($food_ids_in_cart)) {
        $placeholders = implode(',', array_fill(0, count($food_ids_in_cart), '?'));
        $sql_fetch_items = "SELECT food_id, price FROM food WHERE food_id IN ($placeholders)";
        if ($stmt_fetch = mysqli_prepare($link, $sql_fetch_items)) {
            $types = str_repeat('i', count($food_ids_in_cart));
            if (!mysqli_stmt_bind_param($stmt_fetch, $types, ...$food_ids_in_cart)) {
                 $response['message'] = 'Database error: Could not bind parameters for item fetch.'; error_log('DB Error: bind params item fetch: ' . mysqli_stmt_error($stmt_fetch)); echo json_encode($response); exit;
            }
            if (mysqli_stmt_execute($stmt_fetch)) {
                $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                if (mysqli_num_rows($result_fetch) !== count($food_ids_in_cart)) {
                     $response['message'] = 'Order failed: Error validating items in your cart.'; error_log('DB Error: Mismatch cart items vs DB.'); echo json_encode($response); exit;
                }
                $items_data = mysqli_fetch_all($result_fetch, MYSQLI_ASSOC);
                foreach ($items_data as $item) {
                    $food_id = $item['food_id']; $price_at_time = (float)$item['price']; $quantity = (int)$cart[$food_id];
                    $items_for_transaction[] = [ 'food_id' => $food_id, 'quantity' => $quantity, 'price_at_time' => $price_at_time ];
                    $order_total += $price_at_time * $quantity;
                }
            } else { $response['message'] = 'Database error: Could not execute item fetch query.'; error_log('DB Error: execute item fetch: ' . mysqli_stmt_error($stmt_fetch)); echo json_encode($response); exit; }
            mysqli_stmt_close($stmt_fetch);
        } else { $response['message'] = 'Database error: Could not prepare item fetch query.'; error_log('DB Error: prepare item fetch: ' . mysqli_error($link)); echo json_encode($response); exit; }
    } else { $response['message'] = 'Order failed: Your cart is empty after item validation.'; echo json_encode($response); exit; }
    // --- End Recalculate Total ---

    // --- Start Database Transaction ---
    mysqli_begin_transaction($link);

    // --- Fetch NFC Card ID and Current Balance ---
    $sql_get_card = "SELECT nfc_id, current_balance FROM nfc_card WHERE student_id = ?";
    if ($stmt_card = mysqli_prepare($link, $sql_get_card)) {
        mysqli_stmt_bind_param($stmt_card, "i", $student_id);
        if (mysqli_stmt_execute($stmt_card)) {
            $result_card = mysqli_stmt_get_result($stmt_card);
            if (mysqli_num_rows($result_card) === 1) {
                $card_info = mysqli_fetch_assoc($result_card);
                $nfc_id = $card_info['nfc_id'];
                $current_balance = (float)$card_info['current_balance'];
                if ($current_balance < $order_total) {
                    mysqli_rollback($link); $response['message'] = 'Insufficient balance. Please recharge your card.'; $response['new_balance'] = number_format($current_balance, 2); error_log("Order Failed: Insufficient balance. Student ID: " . $student_id . ", total: " . $order_total . ", current balance: " . $current_balance); echo json_encode($response); exit;
                }
            } else { mysqli_rollback($link); $response['message'] = 'Order failed: Could not find unique NFC card for user.'; error_log('DB Error: NFC card not unique/found. Student ID: ' . $student_id); echo json_encode($response); exit; }
        } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not execute NFC card fetch.'; error_log('DB Error: execute card fetch: ' . mysqli_stmt_error($stmt_card)); echo json_encode($response); exit; }
        mysqli_stmt_close($stmt_card);
    } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not prepare NFC card fetch query.'; error_log('DB Error: prepare card fetch: ' . mysqli_error($link)); echo json_encode($response); exit; }
    // --- End Fetch NFC Card ---

    // --- Insert into Transaction Table ---
    $sql_insert_transaction = "INSERT INTO `transaction` (`student_id`, `nfc_id`, `total_amount`, `transaction_time`, `status`) VALUES (?, ?, ?, NOW(), 'success')";
    if ($stmt_transaction = mysqli_prepare($link, $sql_insert_transaction)) {
        mysqli_stmt_bind_param($stmt_transaction, "isd", $student_id, $nfc_id, $order_total);
        if (mysqli_stmt_execute($stmt_transaction)) {
            $transaction_id = mysqli_insert_id($link); // Get the ID of the newly inserted transaction
            mysqli_stmt_close($stmt_transaction);
            if (!$transaction_id || $transaction_id <= 0) { mysqli_rollback($link); $response['message'] = 'Database error: Could not get transaction ID.'; error_log('DB Error: Could not get insert ID.'); echo json_encode($response); exit; }

            // --- Insert into Transaction_Item Table ---
            $sql_insert_item = "INSERT INTO `transaction_item` (`txn_id`, `food_id`, `quantity`, `unit_price`) VALUES (?, ?, ?, ?)";
            if ($stmt_item = mysqli_prepare($link, $sql_insert_item)) {
                $items_inserted_successfully = true;
                foreach ($items_for_transaction as $item) {
                    mysqli_stmt_bind_param($stmt_item, "iiid", $transaction_id, $item['food_id'], $item['quantity'], $item['price_at_time']);
                    if (!mysqli_stmt_execute($stmt_item)) {
                        $items_inserted_successfully = false; error_log('DB Error: Could not insert item ' . $item['food_id'] . ': ' . mysqli_stmt_error($stmt_item)); break;
                    }
                } mysqli_stmt_close($stmt_item);
                if ($items_inserted_successfully) {
                    // --- Update User Balance ---
                    $new_balance = $current_balance - $order_total;
                    $sql_update_balance = "UPDATE `nfc_card` SET `current_balance` = ? WHERE `nfc_id` = ?";
                    if ($stmt_update_balance = mysqli_prepare($link, $sql_update_balance)) {
                        mysqli_stmt_bind_param($stmt_update_balance, "ds", $new_balance, $nfc_id);
                        if (mysqli_stmt_execute($stmt_update_balance)) {
                            // --- SUCCESS! Order is complete and balance updated ---
                            mysqli_commit($link); // Commit the database transaction
                            unset($_SESSION['cart']); // Clear the cart after successful order

                            $response['success'] = true;
                            $response['message'] = 'Order accepted and balance updated.';
                            $response['new_balance'] = number_format($new_balance, 2);
                            error_log("Order Accepted. Txn ID: " . $transaction_id . ", Student ID: " . $student_id . ", Total: " . $order_total . ", New Balance: " . $new_balance);


                            // --- Log Successful Transaction Activity ---
                            // This code is placed AFTER the database transaction is committed
                            if (isset($link) && $link !== false) { // Check if database connection is valid
                                $activity_type = 'new_order'; // Or 'transaction_completed'
                                $description = "New order #TRN-" . $transaction_id . " completed by student ID " . $student_id . " for ₹" . number_format($order_total, 2) . "."; // Customize description
                                $admin_id = null; // This is a user action, not admin
                                $user_id = $student_id; // Associate with the student
                                $related_id = $transaction_id; // Associate with the transaction ID

                                $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, user_id, related_id) VALUES (NOW(), ?, ?, ?, ?, ?)";

                                if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                                    // Bind parameters: s = string, i = integer
                                    // ssiii - type string, description string, admin_id int (nullable), user_id int (nullable), related_id int (nullable)
                                    // Assuming admin_id, user_id, related_id are INT and nullable in your DB
                                    $bound_admin_id = $admin_id; // Will be null
                                    $bound_user_id = $user_id;
                                    $bound_related_id = $related_id;

                                    // Check parameter types based on your DB schema for activity_log
                                    // If admin_id, user_id, related_id are INT NULLABLE, 'iii' is correct.
                                    // If they are VARCHAR, use 'sss'. Adjust as needed.
                                    $param_types = "ssiii"; // Assuming INT NULLABLE for IDs

                                    mysqli_stmt_bind_param($stmt_log, $param_types, $activity_type, $description, $bound_admin_id, $bound_user_id, $bound_related_id);

                                    if (mysqli_stmt_execute($stmt_log)) {
                                        // Activity logged successfully
                                        error_log("Activity logged: " . $description);
                                    } else {
                                        // Error logging activity
                                        error_log("Error logging activity: " . mysqli_stmt_error($stmt_log));
                                    }
                                    mysqli_stmt_close($stmt_log);
                                } else {
                                    // Error preparing log statement
                                    error_log("Error preparing activity log query: " . mysqli_error($link));
                                }
                            } else {
                                // Database connection not available to log activity
                                error_log("Database connection not available to log activity after order.");
                            }
                            // --- End Log Successful Transaction Activity ---


                        } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not update balance.'; error_log('DB Error: update balance: ' . mysqli_stmt_error($stmt_update_balance)); }
                        mysqli_stmt_close($stmt_update_balance);
                    } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not prepare balance update.'; error_log('DB Error: prepare update balance: ' . mysqli_error($link)); }
                } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not insert all transaction items.'; }
            } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not prepare item insert.'; error_log('DB Error: prepare item insert: ' . mysqli_error($link)); }
        } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not execute transaction insert.'; error_log('DB Error: execute transaction insert: ' . mysqli_stmt_error($stmt_transaction)); }
    } else { mysqli_rollback($link); $response['message'] = 'Database error: Could not prepare transaction insert.'; error_log('DB Error: prepare transaction insert: ' . mysqli_error($link)); }
} else { $response['message'] = 'Invalid request method.'; error_log('process_order.php received non-POST request.'); }

// Close the database connection (optional, PHP does this at end of script)
if (isset($link)) {
    mysqli_close($link);
}

echo json_encode($response);
// Note: No closing PHP tag is intentional
?>
