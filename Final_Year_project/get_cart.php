<?php
// get_cart.php - Fetches cart contents (in Project Root)

// Start the session. NO output before this line.
session_start();

// Include database connection
require_once './includes/db_connection.php'; // Path is correct for root files

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error fetching cart.', 'cart_items' => [], 'cart_total' => 0.00];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart = $_SESSION['cart']; $food_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
    $sql = "SELECT food_id, name, price, image_path FROM food WHERE food_id IN ($placeholders)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        $types = str_repeat('i', count($food_ids));
        if (mysqli_stmt_bind_param($stmt, $types, ...$food_ids)) {
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt); $db_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $cart_items_details = []; $cart_total = 0;
                foreach ($db_items as $db_item) {
                    $food_id = $db_item['food_id']; $quantity = $cart[$food_id]; $price = (float)$db_item['price']; $subtotal = $price * $quantity;
                    $cart_items_details[] = ['food_id' => $food_id, 'name' => $db_item['name'], 'price' => $price, 'quantity' => $quantity, 'subtotal' => $subtotal, 'image_path' => $db_item['image_path']];
                    $cart_total += $subtotal;
                }
                $response['success'] = true; $response['message'] = 'Cart fetched.'; $response['cart_items'] = $cart_items_details; $response['cart_total'] = number_format($cart_total, 2);
            } else { $response['message'] = 'DB error executing cart fetch.'; error_log('DB Error: exec cart fetch: ' . mysqli_stmt_error($stmt)); }
        } else { $response['message'] = 'DB error preparing cart fetch.'; error_log('DB Error: prep cart fetch: ' . mysqli_error($link)); }
        mysqli_stmt_close($stmt);
    } else { $response['success'] = true; $response['message'] = 'Cart is empty.'; $response['cart_items'] = []; $response['cart_total'] = '0.00'; }
} else { $response['message'] = 'Invalid request method.'; error_log('get_cart.php non-GET'); }
echo json_encode($response);
// Note: No closing PHP tag is intentional