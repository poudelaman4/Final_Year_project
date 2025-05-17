<?php
// admin/Workspace_products.php - Fetches product data for the admin product list

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// Check if admin_id is NOT set in the session or is empty
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

$response = ['success' => false, 'message' => 'Error fetching products.', 'products' => []];

// --- Fetch all products from the database ---
// **IMPORTANT:** Ensure 'created_at' is removed from this query if that column doesn't exist
$sql = "SELECT food_id, name, description, price, category, image_path, is_available FROM food ORDER BY food_id DESC"; // Corrected SQL query

if ($stmt = mysqli_prepare($link, $sql)) {

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $response['success'] = true;
            $response['message'] = 'Products fetched successfully.';
            $response['products'] = $products;
        } else {
            $response['success'] = true; // Still success, but no data
            $response['message'] = 'No products found.';
            $response['products'] = [];
        }

        mysqli_free_result($result);

    } else {
        $response['message'] = 'Database error executing product fetch.';
        error_log('DB Error (admin/Workspace_products.php): execute fetch: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

} else {
    $response['message'] = 'Database error preparing product fetch.';
    error_log('DB Error (admin/Workspace_products.php): prepare fetch: ' . mysqli_error($link));
}

if (isset($link)) {
    mysqli_close($link);
}

echo json_encode($response);
?>