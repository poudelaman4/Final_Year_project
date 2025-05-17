<?php
// admin/api/fetch_sales_summary.php - Fetches sales summary data (Total Revenue, Items Sold, Transactions, Customers)

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons with database timestamps.
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
require_once '../../includes/db_connection.php'; // NOTE THE UPDATED PATH

// --- Check Database Connection ---
if ($link === false) {
    // Log the connection error
    error_log('DB Error (fetch_sales_summary.php): Could not connect to database: ' . mysqli_connect_error());
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit(); // Stop script execution
}
error_log('DB Info (fetch_sales_summary.php): Database connection successful.'); // Log successful connection
// --- End Check Database Connection ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Initialize response with default values
$response = [
    'success' => true, // Assume success initially, set to false if any error occurs
    'message' => 'Sales summary fetched successfully.', // Default success message
    'summary' => [
        'total_revenue' => 0,
        'total_items_sold' => 0,
        'total_transactions' => 0,
        'total_customers' => 0
        // new_customers and repeat_customers are more complex, will add later
    ]
];

// Variables to hold fetched data
$totalRevenue = 0;
$totalItemsSold = 0;
$totalTransactions = 0;
$totalCustomers = 0;


// Get date range parameters from the GET request
// Use FILTER_UNSAFE_RAW for dates as FILTER_SANITIZE_STRING can strip characters needed for validation
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);

// --- Debug Log for Raw Input Dates ---
// This will show the date strings EXACTLY as received from the frontend
error_log("DEBUG: Raw Input Dates - Start: " . ($startDate ?? 'NULL') . ", End: " . ($endDate ?? 'NULL')); // Handle null for logging
// --- End Debug Log for Raw Input Dates ---

$startDateTime = null;
$endDateTime = null;
$bindParams = []; // Array to hold parameters for binding
$bindParamTypes = ""; // String to hold types for binding
$dateWhereClause = ""; // SQL WHERE clause for date filtering

// Check if dates were provided by the frontend (i.e., not an 'alltime' request)
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
     // Validate date format (YYYY-MM-DD) and if they are valid dates
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
         $response['success'] = false; // Set success to false on validation error
         $response['message'] = 'Invalid date format provided.';
         error_log('Validation Error (fetch_sales_summary.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
         // Send error response and exit
         echo json_encode($response);
         exit();
    }

    // Add time component to make date range inclusive up to the end of the day on endDate
    // Ensure dates are treated in the correct timezone before adding time components
    // Using DateTime objects with the explicitly set timezone is more reliable
    try {
        $timezone = new DateTimeZone(date_default_timezone_get()); // Use the timezone set at the top

        $start_dt_obj = new DateTime($startDate, $timezone);
        $end_dt_obj = new DateTime($endDate, $timezone);

        $start_dt_obj->setTime(0, 0, 0); // Set time to beginning of start date
        $end_dt_obj->setTime(23, 59, 59); // Set time to end of end date

        $startDateTime = $start_dt_obj->format('Y-m-d H:i:s');
        $endDateTime = $end_dt_obj->format('Y-m-d H:i:s');

    } catch (Exception $e) {
         $response['success'] = false; // Set success to false on date processing error
         $response['message'] = 'Error processing date range: ' . $e->getMessage();
         error_log('Date Processing Error (fetch_sales_summary.php): ' . $e->getMessage());
         echo json_encode($response);
         exit();
    }


    // Set up parameters for binding and the WHERE clause for date filtering
    $bindParams[] = $startDateTime;
    $bindParams[] = $endDateTime;
    $bindParamTypes = "ss"; // Both parameters are strings

    // Use the BETWEEN clause for date filtering
    $dateWhereClause = "WHERE transaction_time BETWEEN ? AND ?";

    // --- Debug Log for Dates Used in Query ---
    error_log("DEBUG: Fetch Sales Summary Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);
    // --- End Debug Log for Dates Used in Query ---

} else {
     // If dates are NOT provided (or are empty strings), fetch for all time
     error_log("DEBUG: No dates provided from frontend. Fetching sales summary for All Time.");
     // No where clause needed for all time, bindParams and bindParamTypes remain empty
     // The queries will select all rows
}


// --- Fetch Sales Summary Data (Adjusted to use dynamic WHERE clause and binding) ---

// Query for Total Revenue
$sqlTotalRevenue = "SELECT COALESCE(SUM(total_amount), 0) AS total_revenue FROM transaction " . $dateWhereClause; // Append WHERE clause, use COALESCE for 0 if no rows
error_log("DB Info (fetch_sales_summary.php): Preparing Total Revenue query: " . $sqlTotalRevenue);
if ($stmt = mysqli_prepare($link, $sqlTotalRevenue)) {
     error_log("DB Info (fetch_sales_summary.php): Total Revenue query prepared successfully.");
     // Bind parameters only if the date clause is used
    if (!empty($bindParams)) {
         error_log("DB Info (fetch_sales_summary.php): Binding parameters for Total Revenue query: " . implode(', ', $bindParams));
         // Use call_user_func_array to bind parameters dynamically (PHP 5.6+ recommended)
         // array_merge requires PHP 5.6+ for the unpacking operator (...) or you can use older methods
         mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams); // Requires PHP 5.6+ for ...$bindParams
         // For older PHP versions:
         // call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bindParamTypes], $bindParams));
    }

    error_log("DB Info (fetch_sales_summary.php): Executing Total Revenue query.");
    if (mysqli_stmt_execute($stmt)) {
        error_log("DB Info (fetch_sales_summary.php): Total Revenue query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_sales_summary.php): Total Revenue get_result successful.");
             if ($row = mysqli_fetch_assoc($result)) { // Check if result is valid before fetching
                 $totalRevenue = $row['total_revenue']; // Use the fetched value (COALESCE handles null to 0)
                 error_log("DEBUG: Total Revenue Raw Result: " . print_r($row, true)); // Log the fetched row data
             } else {
                  // This happens if query executed but returned no rows (e.g., COUNT(*) on empty table)
                  // COALESCE should handle this, so totalRevenue remains 0
                  error_log('DB Info (fetch_sales_summary.php): Total Revenue fetch assoc returned no rows.');
             }
             mysqli_free_result($result); // Free result set memory if valid
        } else {
             error_log('DB Error (fetch_sales_summary.php): Total Revenue get_result failed: ' . mysqli_stmt_error($stmt));
             $response['success'] = false; // Set success to false on get_result failure
             $response['message'] = 'Database error getting total revenue result: ' . mysqli_stmt_error($stmt);
        }
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Revenue execute: ' . mysqli_stmt_error($stmt));
         $response['success'] = false; // Set success to false on query execution failure
         $response['message'] = 'Database error fetching total revenue: ' . mysqli_stmt_error($stmt); // Add error detail
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Revenue prepare: ' . mysqli_error($link));
     $response['success'] = false; // Set success to false on query preparation failure
     $response['message'] = 'Database error preparing total revenue query: ' . mysqli_error($link); // Add error detail
}

// --- Query for Total Items Sold ---
// Join transaction_item with transaction to filter by transaction_time
$sqlTotalItemsSold = "SELECT COALESCE(SUM(ti.quantity), 0) AS total_items_sold FROM transaction_item ti JOIN transaction t ON ti.txn_id = t.txn_id " . $dateWhereClause; // Append WHERE clause
error_log("DB Info (fetch_sales_summary.php): Preparing Total Items Sold query: " . $sqlTotalItemsSold);
if ($stmt = mysqli_prepare($link, $sqlTotalItemsSold)) {
     error_log("DB Info (fetch_sales_summary.php): Total Items Sold query prepared successfully.");
    // Bind parameters only if the date clause is used
    if (!empty($bindParams)) {
         error_log("DB Info (fetch_sales_summary.php): Binding parameters for Total Items Sold query: " . implode(', ', $bindParams));
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams); // Requires PHP 5.6+
    }
    error_log("DB Info (fetch_sales_summary.php): Executing Total Items Sold query.");
    if (mysqli_stmt_execute($stmt)) {
         error_log("DB Info (fetch_sales_summary.php): Total Items Sold query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_sales_summary.php): Total Items Sold get_result successful.");
            if ($row = mysqli_fetch_assoc($result)) { // Check if result is valid
                $totalItemsSold = $row['total_items_sold'];
                error_log("DEBUG: Total Items Sold Raw Result: " . print_r($row, true));
            } else {
                 error_log('DB Info (fetch_sales_summary.php): Total Items Sold fetch assoc returned no rows.');
            }
            if ($result) mysqli_free_result($result);
        } else {
             error_log('DB Error (fetch_sales_summary.php): Total Items Sold get_result failed: ' . mysqli_stmt_error($stmt));
             $response['success'] = false;
             $response['message'] = 'Database error getting total items sold result: ' . mysqli_stmt_error($stmt);
        }
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Items Sold execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total items sold: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Items Sold prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total items sold query.';
}


// --- Query for Total Transactions ---
$sqlTotalTransactions = "SELECT COALESCE(COUNT(*), 0) AS total_transactions FROM transaction " . $dateWhereClause; // Append WHERE clause
error_log("DB Info (fetch_sales_summary.php): Preparing Total Transactions query: " . $sqlTotalTransactions);
if ($stmt = mysqli_prepare($link, $sqlTotalTransactions)) {
     error_log("DB Info (fetch_sales_summary.php): Total Transactions query prepared successfully.");
    // Bind parameters only if the date clause is used
    if (!empty($bindParams)) {
         error_log("DB Info (fetch_sales_summary.php): Binding parameters for Total Transactions query: " . implode(', ', $bindParams));
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams);
    }
    error_log("DB Info (fetch_sales_summary.php): Executing Total Transactions query.");
    if (mysqli_stmt_execute($stmt)) {
         error_log("DB Info (fetch_sales_summary.php): Total Transactions query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_sales_summary.php): Total Transactions get_result successful.");
            if ($row = mysqli_fetch_assoc($result)) { // Check if result is valid
                $totalTransactions = $row['total_transactions'];
                error_log("DEBUG: Total Transactions Raw Result: " . print_r($row, true));
            } else {
                 error_log('DB Info (fetch_sales_summary.php): Total Transactions fetch assoc returned no rows.');
            }
            if ($result) mysqli_free_result($result);
        } else {
             error_log('DB Error (fetch_sales_summary.php): Total Transactions get_result failed: ' . mysqli_stmt_error($stmt));
             $response['success'] = false;
             $response['message'] = 'Database error getting total transactions result: ' . mysqli_stmt_error($stmt);
        }
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Transactions execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total transactions: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Transactions prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total transactions query.';
}

// --- Query for Total Customers (Unique Students who made a transaction) ---
// Note: This counts unique student_ids within the transaction table for the period.
// If a student makes multiple transactions in the period, they are counted once.
$sqlTotalCustomers = "SELECT COALESCE(COUNT(DISTINCT student_id), 0) AS total_customers FROM transaction " . $dateWhereClause; // Append WHERE clause
error_log("DB Info (fetch_sales_summary.php): Preparing Total Customers query: " . $sqlTotalCustomers);
if ($stmt = mysqli_prepare($link, $sqlTotalCustomers)) {
     error_log("DB Info (fetch_sales_summary.php): Total Customers query prepared successfully.");
    // Bind parameters only if the date clause is used
    if (!empty($bindParams)) {
         error_log("DB Info (fetch_sales_summary.php): Binding parameters for Total Customers query: " . implode(', ', $bindParams));
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams);
    }
    error_log("DB Info (fetch_sales_summary.php): Executing Total Customers query.");
    if (mysqli_stmt_execute($stmt)) {
         error_log("DB Info (fetch_sales_summary.php): Total Customers query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_sales_summary.php): Total Customers get_result successful.");
            if ($row = mysqli_fetch_assoc($result)) { // Check if result is valid
                $totalCustomers = $row['total_customers'];
                error_log("DEBUG: Total Customers Raw Result: " . print_r($row, true));
            } else {
                 error_log('DB Info (fetch_sales_summary.php): Total Customers fetch assoc returned no rows.');
            }
            if ($result) mysqli_free_result($result);
        } else {
             error_log('DB Error (fetch_sales_summary.php): Total Customers get_result failed: ' . mysqli_stmt_error($stmt));
             $response['success'] = false;
             $response['message'] = 'Database error getting total customers result: ' . mysqli_stmt_error($stmt);
        }
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Customers execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total customers: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Customers prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total customers query.';
}


// --- Final Response ---
// If $response['success'] is still true at this point, it means ALL queries prepared and executed successfully.
// If any query failed during preparation or execution, $response['success'] would have been set to false and the message updated.
if ($response['success'] === true) {
     // Populate the summary data into the response structure ONLY if all queries succeeded
     $response['summary'] = [
         'total_revenue' => $totalRevenue,
         'total_items_sold' => $totalItemsSold,
         'total_transactions' => $totalTransactions,
         'total_customers' => $totalCustomers
     ];
     // The success message is already set to 'Sales summary fetched successfully.' by default
     // or updated based on whether dates were provided.
      $response['message'] = ($startDate !== null && $endDate !== null) ? 'Sales summary fetched successfully for date range.' : 'Sales summary for all time fetched successfully.';

} else {
     // If $response['success'] is false, keep the error message and the default 0s in summary.
     // The message was set during the failed query block.
     if (!isset($response['message']) || $response['message'] === 'Error fetching sales summary.') {
          $response['message'] = 'An error occurred while fetching sales data.'; // Fallback message if no specific error was set
     }
     // Summary remains 0s as initialized
}

// --- Log Final Response ---
error_log("DEBUG: Final Response (fetch_sales_summary.php): " . json_encode($response)); // Log the final JSON response
// --- End Log Final Response ---


// Close the database connection (Already handled at the end if needed)
// It's often safer to omit mysqli_close and let PHP close automatically,
// especially in short scripts like this.

// Send the JSON response back to the frontend
echo json_encode($response);

// Note: No closing PHP tag here is intentional. This prevents accidental whitespace.
?>
