<?php
// clear_cart.php - Clears the session cart (in Project Root)

// Start the session. NO output before this line.
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error clearing cart.'];

if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
    $response['success'] = true;
    $response['message'] = 'Cart cleared successfully.';
} else {
    $response['success'] = true;
    $response['message'] = 'Cart was already empty.';
}
echo json_encode($response);
// Note: No closing PHP tag is intentional