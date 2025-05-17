<?php
// This script receives a food_id via POST and adds/updates it in the session cart

// Start the session
session_start();

// Include database connection file (needed for validation if we implement it later)
// The path is correct because this file is in the root, and includes/ is also in the root
require_once './includes/db_connection.php';

// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the food_id from the POST data
    // filter_input is a safe way to get data from POST/GET
    $food_id = filter_input(INPUT_POST, 'food_id', FILTER_SANITIZE_NUMBER_INT);

    // Validate the food_id: check if it's a number and not empty
    if ($food_id === false || $food_id === null || $food_id === '') {
        // If the ID is invalid, send an error response
        echo json_encode(['success' => false, 'message' => 'Invalid food item ID received.']);
        exit; // Stop script execution
    }

    // --- Optional: You could add a database query here to verify the food_id exists and is available ---
    // $sql_check = "SELECT food_id FROM food WHERE food_id = ? AND is_available = 1";
    // ... prepare, bind, execute, check num_rows ... if no rows, send error and exit.
    // Using the $link variable from db_connection.php

    // Initialize the cart in session if it doesn't exist yet
    // The cart will be an associative array: [food_id => quantity]
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add the food item to the cart or increase its quantity
    // Check if the food_id is already a key in the $_SESSION['cart'] array
    if (isset($_SESSION['cart'][$food_id])) {
        // Item is already in the cart, increase quantity by 1
        $_SESSION['cart'][$food_id]++;
    } else {
        // Item is not in the cart, add it with initial quantity 1
        $_SESSION['cart'][$food_id] = 1;
    }

    // Send a success response back to the JavaScript on the frontend
    // Include the current cart content in the response for debugging/verification if needed
    echo json_encode(['success' => true, 'message' => 'Item added to cart!', 'cart_contents' => $_SESSION['cart']]);

} else {
    // If the request method is not POST, send an error response
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// The script ends here. The browser only receives the JSON output.
// mysqli_close($link); // Optional: Close DB connection

?>