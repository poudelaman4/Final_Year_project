<?php
// update_quantity.php - Updates item quantity in the session cart

// Start the session. NO output before this line.
session_start();

// We don't strictly need db_connection here unless validating food_id against DB,
// but good practice to include if session involves user ID.
// require_once './includes/db_connection.php'; // Uncomment if needed

// Set response header to JSON
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Error updating cart item quantity.'
];

// Check if cart exists and food_id and action are provided via POST
if (isset($_SESSION['cart']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT); // Safely get and validate food_id
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // Safely get action

    // Validate inputs
    if ($food_id !== false && $food_id !== null && in_array($action, ['increase', 'decrease', 'remove'])) {

        // Ensure the item exists in the cart before trying to modify it
        if (isset($_SESSION['cart'][$food_id])) {
            switch ($action) {
                case 'increase':
                    $_SESSION['cart'][$food_id]++;
                    $response['success'] = true;
                    $response['message'] = 'Quantity increased.';
                    break;

                case 'decrease':
                    // Decrease quantity, but remove item if quantity drops to 0 or less
                    $_SESSION['cart'][$food_id]--;
                    if ($_SESSION['cart'][$food_id] <= 0) {
                        unset($_SESSION['cart'][$food_id]); // Remove the item from the cart
                        $response['message'] = 'Item removed.';
                    } else {
                         $response['message'] = 'Quantity decreased.';
                    }
                    $response['success'] = true;
                    break;

                case 'remove':
                    // Remove the item completely
                    unset($_SESSION['cart'][$food_id]);
                    $response['success'] = true;
                    $response['message'] = 'Item removed.';
                    break;
            }

        } else {
             $response['message'] = 'Item not found in cart.';
             error_log('update_quantity.php failed: Item food_id ' . $food_id . ' not found in session cart.');
        }

    } else {
        $response['message'] = 'Invalid food ID or action.';
         error_log('update_quantity.php failed: Invalid input - food_id: ' . $food_id . ', action: ' . $action);
    }

} else {
    $response['message'] = 'Cart not found in session or invalid request method.';
     error_log('update_quantity.php failed: Cart not in session or not POST request.');
}

echo json_encode($response);

// Note: No closing PHP tag here is intentional