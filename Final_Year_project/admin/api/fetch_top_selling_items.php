<?php
// admin/api/fetch_top_selling_items.php - Fetches top selling items data with updated table/column names and fixed date column

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons.
// Choose a timezone identifier from https://www.php.net/manual/en/timezones.php
// 'Asia/Kathmandu' is used here as an example for Nepal Time (UTC+5:45)
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here


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
// Path: From admin/api/ UP two levels (../../) THEN into includes/
require_once '../../includes/db_connection.php'; // Ensure this path is correct and it creates $link

// --- Check Database Connection ---
// Use $link as defined in your db_connection.php
if ($link === false) {
    // Log the connection error
    error_log('DB Error (fetch_top_selling_items.php): Could not connect to database: ' . mysqli_connect_error());
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit(); // Stop script execution
}
// --- End Check Database Connection ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = [
    'success' => false, // Default to false, set to true if data is fetched
    'message' => 'Error fetching top selling items.',
    'top_items' => [] // Array to hold top selling items data
];

// Get date range and category parameters from the GET request
// Note: Category filter is currently not used on the dashboard, but kept for sales.php
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING); // Optional category filter

$startDateTime = null;
$endDateTime = null;
$bindParams = [];
$bindParamTypes = "";
$filterClauses = []; // Array to hold individual filter conditions


// Add the mandatory status filter first
$filterClauses[] = "t.status = 'success'";

// Determine date range and add to filter clauses
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
     // Validate date format
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
         $response['message'] = 'Invalid date format provided.';
         error_log('Validation Error (fetch_top_selling_items.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
         echo json_encode($response);
         exit();
    }

    // Add time component for inclusive range
    try {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $start_dt_obj = new DateTime($startDate, $timezone);
        $end_dt_obj = new DateTime($endDate, $timezone);
        $start_dt_obj->setTime(0, 0, 0);
        $end_dt_obj->setTime(23, 59, 59);
        $startDateTime = $start_dt_obj->format('Y-m-d H:i:s');
        $endDateTime = $end_dt_obj->format('Y-m-d H:i:s');
    } catch (Exception $e) {
         $response['message'] = 'Error processing date range: ' . $e->getMessage();
         error_log('Date Processing Error (fetch_top_selling_items.php): ' . $e->getMessage());
         echo json_encode($response);
         exit();
    }

    // Add date filter to clauses
    $filterClauses[] = "t.transaction_time BETWEEN ? AND ?";
    $bindParams[] = $startDateTime;
    $bindParamTypes .= "s";
    $bindParams[] = $endDateTime;
    $bindParamTypes .= "s";

    error_log("DEBUG: Fetch Top Items Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);

} else {
     // If no dates, the status filter is the only one initially
     error_log("DEBUG: No dates provided for top items. Fetching for All Time.");
}

// Determine category filter and add to filter clauses
if ($category !== null && $category !== '' && strtolower($category) !== 'all') {
    $filterClauses[] = "f.category = ?";
    $bindParams[] = $category;
    $bindParamTypes .= "s";
     error_log("DEBUG: Category filter applied: " . $category);
} else {
     error_log("DEBUG: No category filter applied.");
}

// Combine all filter clauses with AND, starting with WHERE if there are clauses
$whereClause = "";
if (!empty($filterClauses)) {
    $whereClause = "WHERE " . implode(" AND ", $filterClauses);
}


// Query to fetch top selling items by revenue
// Join transaction, transaction_item, and food tables
// Group by food item and sum the revenue (quantity * unit_price)
$sqlTopItems = "
    SELECT
        f.food_id,
        f.name,
        f.image_path,
        f.category,
        COALESCE(SUM(ti.quantity * ti.unit_price), 0) AS total_revenue, -- CORRECTED: Calculate revenue per item
        COALESCE(SUM(ti.quantity), 0) AS total_quantity_sold -- Also fetch total quantity sold
    FROM
        transaction_item ti
    JOIN
        transaction t ON ti.txn_id = t.txn_id
    JOIN
        food f ON ti.food_id = f.food_id
    " . $whereClause . " -- Use the combined WHERE clause
    GROUP BY
        f.food_id, f.name, f.image_path, f.category -- Group by item details
    ORDER BY
        total_revenue DESC, total_quantity_sold DESC -- Order by revenue, then quantity
    LIMIT 10"; // Limit to top 10 items


error_log("DB Info (fetch_top_selling_items.php): Preparing Top Items query: " . $sqlTopItems);

if ($stmt = mysqli_prepare($link, $sqlTopItems)) {
     error_log("DB Info (fetch_top_selling_items.php): Top Items query prepared successfully.");

     // --- Binding using the unpacking operator (...) ---
     // The types string needs to match the number and types of parameters in $bindParams
     // If no date or category filter, $bindParamTypes will be empty.
     // If date filter, $bindParamTypes will be 'ss'.
     // If category filter, $bindParamTypes will be 's'.
     // If both, $bindParamTypes will be 'sss'.
     $types = $bindParamTypes; // Use the dynamically built type string

     // Bind parameters using the unpacking operator (...) - Requires PHP 5.6+
     // This correctly passes the elements of the $bindParams array as separate arguments by value
     if (!empty($bindParams)) {
          // Use call_user_func_array for compatibility with older PHP versions if needed
          mysqli_stmt_bind_param($stmt, $types, ...$bindParams);
          // For older PHP versions:
          // call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $bindParams));
     }
     // If $bindParams is empty, no binding is needed here


    error_log("DB Info (fetch_top_selling_items.php): Executing Top Items query.");
    if (mysqli_stmt_execute($stmt)) {
        error_log("DB Info (fetch_top_selling_items.php): Top Items query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_top_selling_items.php): Top Items get_result successful.");
            $topItemsData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Ensure numeric values are treated as numbers
                $row['total_revenue'] = (float)$row['total_revenue'];
                $row['total_quantity_sold'] = (int)$row['total_quantity_sold'];
                $topItemsData[] = $row;
            }
            error_log("DEBUG: Top Items Raw Result Count: " . count($topItemsData)); // Log number of rows fetched
            // error_log("DEBUG: Top Items Raw Data: " . print_r($topItemsData, true)); // Log actual data (can be verbose)

            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Top selling items data fetched successfully.';
            $response['top_items'] = $topItemsData;

        } else {
             error_log('DB Error (fetch_top_selling_items.php): Top Items get_result failed: ' . mysqli_stmt_error($stmt));
             $response['message'] = 'Database error getting top items result: ' . mysqli_stmt_error($stmt);
             $response['success'] = false; // Set success to false on failure
        }
    } else {
        error_log('DB Error (fetch_top_selling_items.php): Top Items execute: ' . mysqli_stmt_error($stmt));
         $response['message'] = 'Database error fetching top items: ' . mysqli_stmt_error($stmt);
         $response['success'] = false; // Set success to false on failure
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_top_selling_items.php): Top Items prepare: ' . mysqli_error($link));
     $response['message'] = 'Database error preparing top items query: ' . mysqli_error($link);
     $response['success'] = false; // Set success to false on failure
}


// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); } // Use $link

// Send the JSON response back to the frontend
error_log("DEBUG: Final Response (fetch_top_selling_items.php): " . json_encode($response));
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>
