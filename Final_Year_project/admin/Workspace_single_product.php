<?php
// admin/fetch_single_product.php - Fetches details for a single product

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct and $link variable is created

// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.', 'product' => null];

// Check if the request method is GET or POST and if food_id is provided
// We'll use GET or POST for flexibility, but GET is often suitable for fetching data.
if (($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') && isset($_REQUEST['food_id'])) {

    // --- Retrieve and Validate Input Data ---
    // Get the product ID and validate it as an integer
    $food_id = filter_input(INPUT_GET, 'food_id', FILTER_VALIDATE_INT);
    if ($food_id === false || $food_id === null) {
         // If not in GET, check POST (although GET is preferred for fetching)
         $food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT);
    }


    // Check if the food_id is valid
    if ($food_id === false || $food_id === null) {
        $response['message'] = 'Invalid product ID received.';
        error_log('admin/fetch_single_product.php failed: Invalid food_id: ' . ($_REQUEST['food_id'] ?? 'not set')); // Log the invalid input
    } else {
        // Validation passed, proceed to fetch from database

        // --- Fetch Product from Database ---
        // Using prepared statement
        // Make sure the table name 'food' and column names match your database exactly
        $sql_fetch = "SELECT food_id, name, description, price, category, image_path, is_available FROM food WHERE food_id = ?";

        if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
            // Bind parameter (integer: i)
            mysqli_stmt_bind_param($stmt_fetch, "i", $food_id);

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt_fetch)) {
                $result = mysqli_stmt_get_result($stmt_fetch);

                if (mysqli_num_rows($result) === 1) {
                    // Product found
                    $product = mysqli_fetch_assoc($result);
                    $response['success'] = true;
                    $response['message'] = 'Product fetched successfully!';
                    $response['product'] = $product;
                } else {
                    // Product not found (0 rows) or multiple found (>1 row, shouldn't happen with food_id as primary key)
                    $response['message'] = 'Product not found.';
                     error_log('admin/fetch_single_product.php failed: Product ID ' . $food_id . ' not found or multiple found.');
                }

                mysqli_free_result($result);

            } else {
                // Database execution error
                $response['message'] = 'Database error fetching product details.';
                error_log('DB Error (admin/fetch_single_product.php): execute fetch: ' . mysqli_stmt_error($stmt_fetch));
            }

            mysqli_stmt_close($stmt_fetch); // Close statement

        } else {
            // Database preparation error
            $response['message'] = 'Database error preparing product fetch.';
            error_log('DB Error (admin/fetch_single_product.php): prepare fetch: ' . mysqli_error($link));
        }
    }

} else {
    // If request method is not GET/POST or food_id is not provided
    $response['message'] = 'Invalid request or missing product ID.';
     error_log('admin/fetch_single_product.php received invalid request.');
}

// Close the database connection
if (isset($link)) {
    mysqli_close($link);
}

// Send the JSON response
echo json_encode($response);

// Note: No closing PHP tag here is intentional
?>