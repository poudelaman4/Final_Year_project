<?php
// admin/dashboard.php - Admin Dashboard (Coordinated Data Fetching for Charts)

// Set the default timezone (optional, but good practice)
// Choose a timezone identifier from https://www.php.net/manual/en/timezones.php
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// Check if admin_id is NOT set in the session or is empty
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // If not logged in as admin, redirect to the admin login page
    header('Location: login.php'); // login.php is in the same admin folder
    exit(); // Stop script execution
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // This file MUST correctly create the $link variable

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (like CSS) - Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// --- Fetch Data from Database (for top cards - PHP based) ---
// Note: This PHP fetching is kept from your original file.
// An alternative approach (used in the admin_sales_frontend immersive)
// is to fetch these stats via a dedicated API as well.

// Initialize variables to avoid undefined errors if queries fail
$total_sales = 0;
$items_sold = 0;
$total_unique_customers = 0; // Represents total unique customers for now
$avg_order_value = 0;

// Fetch Total Sales (Sum of total_amount from completed transactions)
// Using 'success' status based on your database schema enum
$sql_total_sales = "SELECT SUM(total_amount) AS total_revenue FROM transaction WHERE status = 'success'";
$result_total_sales = mysqli_query($link, $sql_total_sales); // Using $link

if ($result_total_sales) {
    $row_total_sales = mysqli_fetch_assoc($result_total_sales);
    $total_sales = $row_total_sales['total_revenue'] ?? 0;
} else {
    error_log("Error fetching total sales: " . mysqli_error($link)); // Using $link
}

// Fetch Total Items Sold (Sum of quantity from transaction_item for successful transactions)
// Joining transaction_item and transaction tables on txn_id
$sql_items_sold = "SELECT SUM(ti.quantity) AS total_items FROM transaction_item ti JOIN transaction t ON ti.txn_id = t.txn_id WHERE t.status = 'success'";
$result_items_sold = mysqli_query($link, $sql_items_sold); // Using $link

if ($result_items_sold) {
    $row_items_sold = mysqli_fetch_assoc($result_items_sold);
    $items_sold = $row_items_sold['total_items'] ?? 0;
} else {
    // FIX: Corrected the syntax error here - removed the extra double quote
    error_log("Error fetching total items sold: " . mysqli_error($link)); // Using $link
}

// Fetch Total Unique Customers (Count of distinct student_id in transaction table for successful transactions)
$sql_unique_customers = "SELECT COUNT(DISTINCT student_id) AS total_unique_customers FROM transaction WHERE student_id IS NOT NULL AND status = 'success'";
$result_unique_customers = mysqli_query($link, $sql_unique_customers); // Using $link

if ($result_unique_customers) {
    $row_unique_customers = mysqli_fetch_assoc($result_unique_customers);
    $total_unique_customers = $row_unique_customers['total_unique_customers'] ?? 0;
} else {
    error_log("Error fetching unique customers: " . mysqli_error($link)); // Using $link
}

// Calculate Avg. Order Value (Total Sales / Count of Successful Transactions)
$sql_successful_transactions_count = "SELECT COUNT(txn_id) AS successful_count FROM transaction WHERE status = 'success'";
$result_successful_transactions_count = mysqli_query($link, $sql_successful_transactions_count); // Using $link
$successful_transactions_count = 0;

if ($result_successful_transactions_count) {
    $row_successful_transactions_count = mysqli_fetch_assoc($result_successful_transactions_count);
    $successful_transactions_count = $row_successful_transactions_count['successful_count'] ?? 0;
} else {
     error_log("Error fetching successful transactions count: " . mysqli_error($link)); // Using $link
}

if ($successful_transactions_count > 0) {
    $avg_order_value = $total_sales / $successful_transactions_count;
} else {
    $avg_order_value = 0;
}

// --- End Fetch Data ---

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php // include "../includes/packages.php"; // Included above ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <style>
        /* Optional: Add any custom styles needed here */
        .chart-container {
            position: relative;
            height: 100%;
            width: 100%;
        }
         /* Style for the message displayed when no data or error in charts */
         .chart-message {
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             text-align: center;
             font-size: 1rem;
             color: #6b7280; /* gray-500 */
             z-index: 1; /* Ensure message is above canvas */
         }

         .dark .chart-message {
             color: #9ca3af; /* gray-400 */
         }

         .chart-message.error {
             color: #dc2626; /* red-600 */
         }

         .dark .chart-message.error {
             color: #ef4444; /* red-500 */
         }
     </style>
</head>

<body class="bg-gray-100 font-sans dark:bg-gray-900"> <?php include '../includes/admin_header.php'; // Include header ONCE here ?>


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="admin-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>


        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400"> Total Sales (Success)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white"> ₹<?php echo number_format($total_sales, 2); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500 dark:text-gray-400"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400"> Total Items Sold (Success Orders)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white"> <?php echo number_format($items_sold); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500 dark:text-gray-400"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400"> Total Unique Customers
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white"> <?php echo number_format($total_unique_customers); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500 dark:text-gray-400"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400"> Avg. Order Value (Success Orders)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white"> ₹<?php echo number_format($avg_order_value, 2); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500 dark:text-gray-400"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"> <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-white">Sales Overview (Monthly)</h2> <div class="h-80 chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"> <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-white">Top Selling Products</h2> <div class="h-80 chart-container">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"> <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"> <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Orders</h2> </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700" id="recentOrdersList"> <div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading recent orders...</div> </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"> <a href="transaction.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-600">View all orders</a> </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"> <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"> <h2 class="text-lg font-medium text-gray-900 dark:text-white">Activity Feed</h2> </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700" id="activityFeedList"> <div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading activity feed...</div> </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"> <a href="activity.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-600">View all activity</a> </div>
            </div>
        </div>
    </main>

    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-8"> <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">&copy; 2023 Your Company. All rights reserved.</p> </div>
    </footer>

    <script>
         // Helper function to show confirmation message (reused pattern)
        const adminConfirmation = document.getElementById('admin-confirmation');
        function showAdminConfirmation(message = 'Success!', color = 'green') {
            if (!adminConfirmation) return;
            adminConfirmation.textContent = message;
            adminConfirmation.className = `fixed top-5 left-1/2 -translate-x-1/2 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform bg-${color}-500 block`; // Reset classes
            setTimeout(() => { adminConfirmation.classList.add('opacity-100'); }, 10);
            setTimeout(() => {
                adminConfirmation.classList.remove('opacity-100');
                setTimeout(() => { adminConfirmation.classList.remove('block'); adminConfirmation.classList.add('hidden'); }, 500);
            }, 3000); // Show for 3 seconds
        }

        // Check for dark mode class on html element
        function isDarkMode() {
            return document.documentElement.classList.contains('dark');
        }

         // Helper to format currency
         function formatCurrency(amount) {
              const number = parseFloat(amount) || 0;
              return `${number.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
          }

          // Helper to format number
           function formatNumber(number) {
               const num = parseFloat(number) || 0;
               return num.toLocaleString('en-IN');
           }

         // Helper to format date and time
         function formatDateTime(dateTimeStr) {
              if (!dateTimeStr) return 'N/A';
             try {
                 const date = new Date(dateTimeStr);
                 // Ensure date is a valid Date object
                 if (isNaN(date.getTime())) {
                     console.error("Invalid date provided to formatDateTime:", dateTimeStr);
                     return 'Invalid Date';
                 }
                 // Format to 'D M Y, h:i A' (e.g., 9 May 2025, 10:15 AM)
                 const options = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
                 return date.toLocaleDateString('en-IN', options);
             } catch (e) {
                 console.error("Error formatting date/time:", dateTimeStr, e);
                 return 'Invalid Date';
             }
         }

        // FIX: Added htmlspecialchars helper function from sales.php
        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        // Chart Instances - Keep global or in a scope accessible by initCharts
        let salesChart, productsChart;

        // Variables to hold fetched data
        let dashboardSalesData = [];
        let dashboardTopProductsData = [];

        // Flags to track data fetching completion
        let salesDataFetched = false;
        let topProductsDataFetched = false;


        // Initialize charts (call this AFTER data is loaded and stored)
         function initCharts() { // Removed parameters, uses global/scoped data
             // Destroy existing charts if they exist to prevent duplicates
            if (salesChart) salesChart.destroy();
            if (productsChart) productsChart.destroy();

             const chartBaseOptions = {
                 responsive: true,
                 maintainAspectRatio: false,
                  plugins: {
                      legend: {
                          position: 'top',
                           labels: {
                                color: isDarkMode() ? '#9ca3af' : '#6b7280' // Dark mode text color
                            }
                       },
                       tooltip: { // Add tooltip colors for dark mode
                           backgroundColor: isDarkMode() ? '#374151' : '#fff',
                           titleColor: isDarkMode() ? '#9ca3af' : '#6b7280',
                           bodyColor: isDarkMode() ? '#9ca3af' : '#6b7280',
                           borderColor: isDarkMode() ? '#4b5563' : '#e5e7eb',
                           borderWidth: 1
                       }
                  },
                   scales: { // Base scale options
                       y: {
                           beginAtZero: true,
                            ticks: { color: isDarkMode() ? '#9ca3af' : '#6b7280' }, // Dark mode text color
                            grid: { color: isDarkMode() ? '#4b5563' : '#e5e7eb' } // Dark mode grid color
                       },
                        x: {
                           ticks: { color: isDarkMode() ? '#9ca3af' : '#6b7280' }, // Dark mode text color
                            grid: { color: isDarkMode() ? '#4b5563' : '#e5e7eb' } // Dark mode grid color
                        }
                   }
             };


            // Sales Chart (Monthly Overview)
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) { // Added null check
                 // Ensure a canvas element exists in the container
                 let salesCanvas = document.getElementById('salesChart');
                 let salesChartContainer = salesCanvas ? salesCanvas.parentElement : null;
                 if (!salesCanvas || !salesChartContainer) {
                     console.error('Sales Chart container or canvas not found for initialization!');
                 } else {
                      // Remove any existing messages before drawing chart
                      salesChartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());


                       // Check if there's data to display
                       if (dashboardSalesData && dashboardSalesData.length > 0) {
                            if (salesCanvas) salesCanvas.style.display = 'block'; // Show canvas

                            const salesChartOptions = JSON.parse(JSON.stringify(chartBaseOptions)); // Deep copy
                            salesChartOptions.scales.y.title = { display: true, text: 'Sales (₹)', color: isDarkMode() ? '#9ca3af' : '#6b7280' };
                            salesChartOptions.scales.y1 = { // Secondary Y-axis for Items Sold
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false, color: isDarkMode() ? '#4b5563' : '#e5e7eb' },
                                title: { display: true, text: 'Items Sold', color: isDarkMode() ? '#9ca3af' : '#6b7280' }
                            };

                           salesChart = new Chart(salesCtx, {
                               type: 'line',
                               data: {
                                   // Ensure labels are correct based on API response structure
                                   labels: dashboardSalesData.map(item => `${item.period}`), // Use stored data
                                   datasets: [{
                                       label: 'Total Sales',
                                       // Ensure data mapping is correct based on API response structure
                                       data: dashboardSalesData.map(item => item.revenue ?? 0), // Use stored data
                                        backgroundColor: isDarkMode() ? 'rgba(99, 102, 241, 0.1)' : 'rgba(79, 70, 229, 0.05)', // Tailwind indigo-500
                                        borderColor: isDarkMode() ? 'rgba(99, 102, 241, 1)' : 'rgba(79, 70, 229, 1)',
                                       borderWidth: 2,
                                       tension: 0.1,
                                       fill: true,
                                        yAxisID: 'y'
                                   }, {
                                       label: 'Items Sold',
                                       // Ensure data mapping is correct based on API response structure
                                       data: dashboardSalesData.map(item => item.items_sold ?? 0), // Use stored data
                                        backgroundColor: isDarkMode() ? 'rgba(52, 211, 153, 0.1)' : 'rgba(16, 185, 129, 0.05)',
                                        borderColor: isDarkMode() ? 'rgba(52, 211, 153, 1)' : 'rgba(16, 185, 129, 1)',
                                       borderWidth: 2,
                                       tension: 0.1,
                                       fill: true,
                                       yAxisID: 'y1'
                                   }]
                               },
                               options: salesChartOptions
                           });
                       } else {
                           // No data found, display message
                            if (salesCanvas) salesCanvas.style.display = 'none'; // Hide canvas
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = 'No sales data found for this period.';
                            noDataMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
                            salesChartContainer.appendChild(noDataMessage);
                       }
                 }
            }


            // Products Chart (Top Selling)
            const productsCtx = document.getElementById('productsChart');
             if (productsCtx) { // Added null check
                 // Ensure a canvas element exists in the container
                 let productsCanvas = document.getElementById('productsChart');
                 let productsChartContainer = productsCanvas ? productsCanvas.parentElement : null;

                 if (!productsCanvas || !productsChartContainer) {
                      console.error('Products Chart container or canvas not found for initialization!');
                 } else {
                      // Remove any existing messages before drawing chart
                      productsChartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());

                      // Check if there's data to display
                      if (dashboardTopProductsData && dashboardTopProductsData.length > 0) {
                           if (productsCanvas) productsCanvas.style.display = 'block'; // Show canvas

                           const productsChartOptions = JSON.parse(JSON.stringify(chartBaseOptions)); // Deep copy
                           productsChartOptions.plugins.legend.display = false; // Hide legend for bar chart
                           productsChartOptions.scales.y.title = { display: true, text: 'Revenue (₹)', color: isDarkMode() ? '#9ca3af' : '#6b7280' }; // Charting Revenue

                           productsChart = new Chart(productsCtx, {
                               type: 'bar',
                               data: {
                                   // Ensure labels are correct based on API response structure
                                   labels: dashboardTopProductsData.map(item => htmlspecialchars(item.name)), // Use stored data
                                   datasets: [{
                                       label: 'Revenue',
                                       // Ensure data mapping is correct based on API response structure
                                       data: dashboardTopProductsData.map(item => item.total_revenue ?? 0), // Use stored data
                                       backgroundColor: [
                                            isDarkMode() ? 'rgba(99, 102, 241, 0.7)' : 'rgba(79, 70, 229, 0.7)',
                                            isDarkMode() ? 'rgba(52, 211, 153, 0.7)' : 'rgba(16, 185, 129, 0.7)',
                                            isDarkMode() ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)',
                                            isDarkMode() ? 'rgba(251, 191, 36, 0.7)' : 'rgba(245, 158, 11, 0.7)',
                                            isDarkMode() ? 'rgba(252, 165, 165, 0.7)' : 'rgba(239, 68, 68, 0.7)'
                                       ],
                                       borderColor: [
                                            isDarkMode() ? 'rgba(99, 102, 241, 1)' : 'rgba(79, 70, 229, 1)',
                                            isDarkMode() ? 'rgba(52, 211, 153, 1)' : 'rgba(16, 185, 129, 1)',
                                            isDarkMode() ? 'rgba(96, 165, 250, 1)' : 'rgba(59, 130, 246, 1)',
                                            isDarkMode() ? 'rgba(251, 191, 36, 1)' : 'rgba(245, 158, 11, 1)',
                                            isDarkMode() ? 'rgba(252, 165, 165, 0.7)' : 'rgba(239, 68, 68, 0.7)'
                                       ],
                                       borderWidth: 1
                                   }]
                               },
                               options: productsChartOptions
                           });
                       } else {
                           // No data found, display message
                            if (productsCanvas) productsCanvas.style.display = 'none'; // Hide canvas
                           const noDataMessage = document.createElement('p');
                           noDataMessage.textContent = 'No top selling items found for this period.';
                           noDataMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
                           productsChartContainer.appendChild(noDataMessage);
                       }
                      }
            }
        }

        // --- Fetch Data Functions (Using APIs) ---

         // Function to check if all required data is fetched and initialize charts
         function checkAndInitCharts() {
             if (salesDataFetched && topProductsDataFetched) {
                 initCharts(); // Initialize charts using the stored global data
             }
         }


         // Fetch Revenue Trend (Modified to store data and set flag)
         async function fetchRevenueTrend(granularity = 'monthly') {
              const chartContainer = document.getElementById('salesChart').parentElement; // Corrected ID to salesChart
              if (!chartContainer) {
                   console.error('Sales Chart container not found!');
                   salesDataFetched = true; // Consider fetched even on error to not block other charts
                   checkAndInitCharts();
                   return;
              }

              // Ensure a canvas element exists before adding loading message
              let salesCanvas = document.getElementById('salesChart');
              if (!salesCanvas) {
                  chartContainer.innerHTML = '<canvas id="salesChart"></canvas>';
                  salesCanvas = document.getElementById('salesChart');
              }

              // Remove previous messages and add loading message
              chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());
              const loadingMessage = document.createElement('p');
              loadingMessage.textContent = 'Loading Sales Overview...';
              loadingMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
              chartContainer.appendChild(loadingMessage);

              // Hide the canvas while loading
              if(salesCanvas) salesCanvas.style.display = 'none';


              const today = new Date();
              const options = { timeZone: 'Asia/Kathmandu' };
              const todayString = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
              let startDate, endDate = todayString;

              switch (granularity) {
                  case 'daily':
                       const thirtyDaysAgo = new Date(today);
                       thirtyDaysAgo.setDate(today.getDate() - 30);
                       startDate = thirtyDaysAgo.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                       break;
                  case 'weekly':
                       const twelveWeeksAgo = new Date(today);
                       twelveWeeksAgo.setDate(today.getDate() - 12 * 7);
                       startDate = twelveWeeksAgo.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                       break;
                  case 'monthly':
                       const twelveMonthsAgo = new Date(today);
                       twelveMonthsAgo.setMonth(today.getMonth() - 12);
                       startDate = twelveMonthsAgo.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                       granularity = 'monthly';
                       break;
                  default:
                       const defaultMonthsAgo = new Date(today);
                       defaultMonthsAgo.setMonth(today.getMonth() - 12);
                       startDate = defaultMonthsAgo.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                       granularity = 'monthly';
                       break;
              }


             const queryParams = new URLSearchParams();
             queryParams.append('startDate', startDate);
             queryParams.append('endDate', endDate);
             queryParams.append('granularity', granularity);


             try {
                 const response = await fetch(`./api/fetch_revenue_trend.php?${queryParams.toString()}`);

                 // Remove loading message
                 if (loadingMessage) loadingMessage.remove();


                 if (!response.ok) {
                       console.error('fetchRevenueTrend: HTTP Error:', response.status, response.statusText);
                       showAdminConfirmation(`HTTP Error fetching revenue trend: ${response.status} ${response.statusText}`, 'red');
                        dashboardSalesData = []; // Clear data on error
                        salesDataFetched = true; // Mark as fetched (with error)
                        checkAndInitCharts(); // Attempt to initialize charts
                        // Ensure the container has a canvas element for future use and add error message
                        if (chartContainer) {
                            let salesCanvas = document.getElementById('salesChart');
                            if(salesCanvas) salesCanvas.style.display = 'none'; // Hide canvas
                             const errorMessage = document.createElement('p');
                             errorMessage.textContent = `Network Error loading trend data: ${response.status} ${response.statusText}`;
                             errorMessage.classList.add('chart-message', 'error');
                             chartContainer.appendChild(errorMessage);
                        }
                        return;
                  }

                  const data = await response.json();

                 if (data.success && data.trend && data.trend.length > 0) {
                       dashboardSalesData = data.trend; // Store data
                       showAdminConfirmation(data.message || 'Revenue trend data fetched successfully.', 'green');
                  } else {
                      // Backend reported success but no data found.
                      console.warn('fetchRevenueTrend: Backend reported no revenue trend data:', data.message);
                       dashboardSalesData = []; // Store empty data
                       showAdminConfirmation(data.message || 'No revenue trend data found for this period.', 'orange');

                       // Ensure the container has a canvas element for future use and add 'No data' message
                       if (chartContainer) {
                           let salesCanvas = document.getElementById('salesChart');
                           if(salesCanvas) salesCanvas.style.display = 'none'; // Hide canvas
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = htmlspecialchars(data.message || 'No data found');
                            noDataMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
                            chartContainer.appendChild(noDataMessage);
                       }
                  }
             } catch (error) {
                 console.error('fetchRevenueTrend: Fetch Error:', error);
                 showAdminConfirmation('Error fetching revenue trend!', 'red');
                  dashboardSalesData = []; // Clear data on error
                  // Ensure the container has a canvas element for future use and add error message
                  if (chartContainer) {
                      let salesCanvas = document.getElementById('salesChart');
                      if(salesCanvas) salesCanvas.style.display = 'none'; // Hide canvas
                       const errorMessage = document.createElement('p');
                       errorMessage.textContent = 'Network Error loading trend data.';
                       errorMessage.classList.add('chart-message', 'error');
                       chartContainer.appendChild(errorMessage);
                  }
             }
             // Always set flag and check for chart initialization after fetch completes (success or fail)
             salesDataFetched = true;
             checkAndInitCharts();
         }

         // Fetch Top Selling Items (Modified to store data and set flag)
          async function fetchTopSellingItems() { // Removed date range and category params for dashboard default fetch
               const chartContainer = document.getElementById('productsChart').parentElement; // Corrected ID to productsChart
                if (!chartContainer) {
                    console.error('Top Items Chart container not found!');
                    topProductsDataFetched = true; // Consider fetched even on error
                    checkAndInitCharts();
                    return;
                }

                // Ensure a canvas element exists before adding loading message
                let topItemsCanvas = document.getElementById('productsChart'); // Corrected ID
                if (!topItemsCanvas) {
                    // Clear any existing content (like previous messages) and add the canvas
                    chartContainer.innerHTML = '<canvas id="productsChart"></canvas>';
                    topItemsCanvas = document.getElementById('productsChart'); // Get the newly added canvas
                }

                // Remove previous messages and add loading message
                chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());
                 const loadingMessage = document.createElement('p');
                 loadingMessage.textContent = 'Loading Top Selling Products...';
                 loadingMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
                 chartContainer.appendChild(loadingMessage);

                 // Hide the canvas while loading
                 if(topItemsCanvas) topItemsCanvas.style.display = 'none';


              // For the dashboard, fetch top items for the CURRENT period (e.g., current month)
              const today = new Date();
              const options = { timeZone: 'Asia/Kathmandu' };

              // Get current month's start and end dates
              const currentMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
              const currentMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0); // Day 0 of next month is last day of current month

              // Format dates to 'YYYY-MM-DD' for the API
              const startDate = currentMonthStart.toLocaleDateString('en-CA', options).replace(/\//g, '-');
              const endDate = currentMonthEnd.toLocaleDateString('en-CA', options).replace(/\//g, '-');
              const category = 'all'; // Dashboard doesn't filter by category


              const queryParams = new URLSearchParams();
              queryParams.append('startDate', startDate);
              queryParams.append('endDate', endDate);
              queryParams.append('category', category);


              try {
                  const response = await fetch(`./api/fetch_top_selling_items.php?${queryParams.toString()}`);

                  // Remove loading message
                  if (loadingMessage) loadingMessage.remove();


                  if (!response.ok) {
                       console.error('fetchTopSellingItems: HTTP Error:', response.status, response.statusText);
                       showAdminConfirmation(`HTTP Error fetching top selling items: ${response.status} ${response.statusText}`, 'red');
                       dashboardTopProductsData = []; // Clear data on error
                       topProductsDataFetched = true; // Mark as fetched (with error)
                       checkAndInitCharts(); // Attempt to initialize charts
                        // Clear existing chart
                        if (productsChart) { productsChart.destroy(); productsChart = null; } // Corrected variable name
                        // Ensure the container has a canvas element for future use and add error message
                        if (chartContainer) {
                            let topItemsCanvas = document.getElementById('productsChart'); // Corrected ID
                            if(topItemsCanvas) topItemsCanvas.style.display = 'none'; // Hide canvas
                             const errorMessage = document.createElement('p');
                             errorMessage.textContent = `Network Error loading top items: ${response.status} ${response.statusText}`;
                             errorMessage.classList.add('chart-message', 'error');
                             chartContainer.appendChild(errorMessage);
                        }
                        return;
                  }

                  const data = await response.json();

                  if (data.success && data.top_items && data.top_items.length > 0) {
                       console.log('fetchTopSellingItems: Data found. Updating UI.'); // Log data found
                       dashboardTopProductsData = data.top_items; // Store data
                       showAdminConfirmation(data.message || 'Top selling items data fetched successfully.', 'green');

                  } else {
                      // Backend reported success but no data found.
                      console.warn('fetchTopSellingItems: Backend reported no top selling items for this filter:', data.message);
                      dashboardTopProductsData = []; // Store empty data
                      showAdminConfirmation(data.message || 'No top selling items found for this period/category.', 'orange');

                       // Ensure the container has a canvas element for future use and add 'No data' message
                       if (chartContainer) {
                           let topItemsCanvas = document.getElementById('productsChart'); // Corrected ID
                           // FIX: Ensure canvas is hidden when showing 'No data' message
                           if(topItemsCanvas) topItemsCanvas.style.display = 'none'; // Hide canvas
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = htmlspecialchars(data.message || 'No data found');
                            noDataMessage.classList.add('chart-message', 'text-gray-500', 'dark:text-gray-400');
                            chartContainer.appendChild(noDataMessage);
                       }
                       // Note: updateTopItemsTable is not on dashboard.php, so we don't call it here.

                  }
               } catch (error) {
                  console.error('fetchTopSellingItems: Fetch Error:', error);
                  showAdminConfirmation('Error fetching top selling items!', 'red');
                  dashboardTopProductsData = []; // Clear data on error
                   // Clear existing chart
                   if (productsChart) {
                       productsChart.destroy();
                       productsChart = null;
                   }
                   // Ensure the container has a canvas element for future use and add error message
                   if (chartContainer) {
                       let topItemsCanvas = document.getElementById('productsChart'); // Corrected ID
                       // FIX: Ensure canvas is hidden when showing error message
                       if(topItemsCanvas) topItemsCanvas.style.display = 'none'; // Hide canvas
                        const errorMessage = document.createElement('p');
                        errorMessage.textContent = 'Network Error loading top items.';
                        errorMessage.classList.add('chart-message', 'error');
                        chartContainer.appendChild(errorMessage);
                   }
                   // Note: updateTopItemsTable is not on dashboard.php, so we don't call it here.
              }
              // Always set flag and check for chart initialization after fetch completes (success or fail)
              topProductsDataFetched = true;
              checkAndInitCharts();
         }


         // --- Fetch Recent Orders (Using API) ---
         async function fetchRecentOrders() {
             // Get the element reference INSIDE the DOMContentLoaded listener
             const recentOrdersListEl = document.getElementById('recentOrdersList');
             if (!recentOrdersListEl) {
                 console.error("Recent Orders List element not found!");
                 return;
             }

             recentOrdersListEl.innerHTML = '<div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading recent orders...</div>'; // Show loading state

             try {
                 const response = await fetch('./api/fetch_recent_orders.php');
                 const data = await response.json();

                 if (response.ok && data.success && data.recent_orders && data.recent_orders.length > 0) {
                     updateRecentOrdersList(data.recent_orders);
                 } else {
                     // FIX: Ensure correct message is displayed if data.success is true but activity_feed is empty
                     const message = data.message || 'No recent orders found.';
                     recentOrdersListEl.innerHTML = `<div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${htmlspecialchars(message)}</div>`;
                     if (!data.success) {
                          showAdminConfirmation(data.message || 'Failed to fetch recent orders.', 'red');
                     } else {
                          showAdminConfirmation(data.message || 'No recent orders found.', 'orange');
                     }
                 }
             } catch (error) {
                 recentOrdersListEl.innerHTML = `<div class="px-6 py-4 text-center text-red-600 dark:text-red-500">Error loading recent orders.</div>`;
                 showAdminConfirmation('Error fetching recent orders!', 'red');
             }
         }

         // --- Update Recent Orders List ---
         function updateRecentOrdersList(orders) {
              // Get the element reference INSIDE the DOMContentLoaded listener
             const recentOrdersListEl = document.getElementById('recentOrdersList');
             if (!recentOrdersListEl) {
                 console.error("Recent Orders List element not found for update!");
                 return;
             }

             recentOrdersListEl.innerHTML = ''; // Clear existing list

             if (orders && orders.length > 0) {
                 orders.forEach(order => {
                      const orderElement = `
                         <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                             <div class="flex items-center justify-between">
                                 <div class="flex items-center">
                                      <div class="ml-0">
                                          <div class="text-sm font-medium text-gray-900 dark:text-white">#TRN-${htmlspecialchars(order.transaction_id || 'N/A')}</div>
                                          <div class="text-sm text-gray-500 dark:text-gray-400">${formatNumber(order.total_items_count || 0)} item(s) • ₹${formatCurrency(order.total_amount || 0)}</div>
                                      </div>
                                 </div>
                                 <div class="text-sm text-gray-500 dark:text-gray-400">${htmlspecialchars(order.formatted_date || 'N/A')}</div>
                             </div>
                         </div>
                     `;
                     recentOrdersListEl.innerHTML += orderElement;
                 });
             } else {
                 recentOrdersListEl.innerHTML = `
                      <div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No recent orders found.</div>
                 `;
             }
         }

         // --- Fetch Activity Feed (Using API) ---
         async function fetchActivityFeed() {
              // Get the element reference INSIDE the DOMContentLoaded listener
             const activityFeedListEl = document.getElementById('activityFeedList');
             if (!activityFeedListEl) {
                  console.error("Activity Feed List element not found!");
                 return;
             }

             activityFeedListEl.innerHTML = '<div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading activity feed...</div>'; // Show loading state

             try {
                 const response = await fetch('./api/fetch_activity_feed.php');
                 const data = await response.json();

                 if (response.ok && data.success && data.activity_feed && data.activity_feed.length > 0) {
                     updateActivityFeedList(data.activity_feed);
                 } else {
                     // FIX: Ensure correct message is displayed if data.success is true but activity_feed is empty
                     const message = data.message || 'No recent activity found.';
                     activityFeedListEl.innerHTML = `<div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${htmlspecialchars(message)}</div>`;
                      if (!data.success) {
                           showAdminConfirmation(data.message || 'Failed to fetch activity feed.', 'red');
                      } else {
                           showAdminConfirmation(data.message || 'No recent activity found.', 'orange');
                      }
                 }
             } catch (error) {
                 // FIX: Ensure correct error message is displayed on fetch error
                 activityFeedListEl.innerHTML = `<div class="px-6 py-4 text-center text-red-600 dark:text-red-500">Error loading activity feed.</div>`;
                 showAdminConfirmation('Error fetching activity feed!', 'red');
             }
         }

         // --- Update Activity Feed List ---
         function updateActivityFeedList(activity) {
              // Get the element reference INSIDE the DOMContentLoaded listener
             const activityFeedListEl = document.getElementById('activityFeedList');
              if (!activityFeedListEl) {
                  console.error("Activity Feed List element not found for update!");
                 return;
             }

             activityFeedListEl.innerHTML = ''; // Clear existing list

             if (activity && activity.length > 0) {
                 activity.forEach(entry => {
                      // FIX: Ensure correct data keys are used as per fetch_activity_feed.php response
                      const description = entry.description || 'N/A'; // Assuming 'description' key
                      const formattedTimestamp = entry.formatted_timestamp || 'N/A'; // Assuming 'formatted_timestamp' key

                      const activityElement = `
                           <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                               <div class="flex space-x-3">
                                   <div class="min-w-0 flex-1">
                                       <p class="text-sm text-gray-800 dark:text-gray-300">${htmlspecialchars(description)}</p>
                                       <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${htmlspecialchars(formattedTimestamp)}</p>
                                   </div>
                               </div>
                           </div>
                       `;
                       activityFeedListEl.innerHTML += activityElement;
                  });
              } else {
                   activityFeedListEl.innerHTML = `
                       <div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No recent activity.</div>
                  `;
              }
         }


         // --- Event Listeners ---
         function setupEventListeners() {
             // Add event listeners for revenue trend buttons (if you add them back to the dashboard)
             // If you add buttons to change granularity on the dashboard, uncomment and adapt this:
             /*
             const dashboardRevenueTrendButtons = document.getElementById('dashboard-revenue-trend-buttons'); // Assuming a different ID for dashboard buttons
             if(dashboardRevenueTrendButtons) {
                 dashboardRevenueTrendButtons.addEventListener('click', function(event) {
                     const target = event.target;
                     if (target.tagName === 'BUTTON' && target.dataset.granularity) {
                         dashboardRevenueTrendButtons.querySelectorAll('button').forEach(btn => {
                              btn.classList.remove('bg-indigo-100', 'text-indigo-800', 'dark:bg-indigo-700', 'dark:text-white');
                             btn.classList.add('bg-gray-100', 'text-gray-800', 'dark:bg-gray-600', 'dark:text-gray-200', 'dark:hover:bg-gray-700');
                         });
                         target.classList.add('bg-indigo-100', 'text-indigo-800', 'dark:bg-indigo-700', 'dark:text-white');
                           target.classList.remove('bg-gray-100', 'text-gray-800', 'dark:bg-gray-600', 'dark:text-gray-200', 'dark:hover:bg-gray-700');

                         const granularity = target.dataset.granularity;
                         fetchRevenueTrend(granularity); // Fetch trend with selected granularity
                     }
                 });
             }
             */

             // No other specific event listeners needed on the dashboard index for now.
             // 'View all orders' and 'View all activity' are simple links.
         }


        // Initialize on DOM Ready
        document.addEventListener('DOMContentLoaded', function () {
             // Initial placeholder setup while data is loading
             initCharts(); // Initializes empty charts or shows loading messages

             setupEventListeners(); // Setup event listeners

             // Fetch data for each section
             // Sales Summary is done by PHP on page load

             // Fetch and display initial revenue trend (e.g., monthly)
             fetchRevenueTrend('monthly');

             // Fetch and display top selling items (will use default period in API)
             fetchTopSellingItems();

             // Fetch and display recent orders
             fetchRecentOrders();

             // Fetch and display activity feed
             fetchActivityFeed();
        });
    </script>
</body>

</html>
<?php
// Close the database connection at the end of the script
// Use $link instead of $conn
if (isset($link)) {
    mysqli_close($link);
}
?>
