<?php
// admin/transaction.php - Admin Transaction Management (Frontend Page)

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

// Include database connection (Needed here for potential future server-side rendering or data checks,
// although the current data fetching is via AJAX)
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct and uses $link

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (like CSS, Flowbite, Tailwind) - Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// Include Admin Header HTML - ENSURE THIS LINE APPEARS ONLY ONCE IN THIS FILE
// Ensure admin_header.php exists in includes/ folder
// Path: From admin/ UP to root (../) THEN into includes/admin_header.php
include '../includes/admin_header.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Transactions</title>
    </head>

<body class="bg-gray-100 font-sans dark:bg-gray-900">

    <section id="transactions" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Transaction Management</h2>
            </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Transaction ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Customer</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Date & Time</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Total Amount</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Total Items</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"> Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody" class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        <tr class="text-center py-4">
                            <td colspan="7" class="px-6 py-4 text-gray-500 dark:text-gray-400">Loading transactions...</td> </tr>
                    </tbody>
                </table>
            </div>
            </div>
    </section>

    <?php // include "../includes/footer.php"; // Include footer if you have one ?>

    <div id="transactionDetailsModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Transaction Details #<span id="modalTransactionId"></span>
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="transactionDetailsModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 6 6-6M7 7l6 6-6-6Z"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div id="modalTransactionItems" class="p-4 md:p-5 space-y-4">
                   <p class="text-gray-500 dark:text-gray-400">Loading items...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // --- DOM Element References ---
        const transactionsTableBody = document.getElementById('transactionsTableBody');
        const transactionDetailsModal = document.getElementById('transactionDetailsModal');
        const modalTransactionIdSpan = document.getElementById('modalTransactionId');
        const modalTransactionItemsDiv = document.getElementById('modalTransactionItems');

        // --- Function to Fetch and Display Transactions (Existing) ---
        function fetchAndDisplayTransactions() {
            // Show loading message
            transactionsTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">Loading transactions...</td></tr>'; // colspan 7


            fetch('fetch_transactions.php') // Calls the backend script for transaction list
                .then(response => {
                    if (!response.ok) {
                         return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                    }
                    return response.json();
                })
                .then(data => {
                    transactionsTableBody.innerHTML = ''; // Clear loading message

                    if (data.success) {
                        if (data.transactions && data.transactions.length > 0) {
                            data.transactions.forEach(transaction => {
                                const row = document.createElement('tr');
                                row.classList.add('bg-white', 'border-b', 'dark:bg-gray-800', 'dark:border-gray-700', 'hover:bg-gray-50', 'dark:hover:bg-gray-600');

                                // Ensure column order and data access matches the JSON structure from fetch_transactions.php
                                // Backend sends: txn_id, student_id, customer_username, total_amount, transaction_date, status, total_items
                                row.innerHTML = `
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        ${htmlspecialchars(transaction.txn_id)}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        ${htmlspecialchars(transaction.customer_username || 'N/A')}
                                    </td>
                                     <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        ${htmlspecialchars(transaction.transaction_date || 'N/A')}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        Rs. ${htmlspecialchars(parseFloat(transaction.total_amount).toFixed(2))}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        ${htmlspecialchars(transaction.total_items)}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-xs font-medium ${transaction.status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : (transaction.status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300')}">
                                            ${htmlspecialchars(transaction.status || 'Unknown')}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline view-transaction-btn" data-id="${htmlspecialchars(transaction.txn_id)}">View</a>
                                    </td>
                                `;
                                transactionsTableBody.appendChild(row);
                            });

                        } else {
                             transactionsTableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">${htmlspecialchars(data.message || 'No transactions found.')}</td></tr>`; // colspan 7
                        }
                    } else {
                         transactionsTableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-600 dark:text-red-500">Error: ${htmlspecialchars(data.message || 'Failed to fetch transactions.')}</td></tr>`; // colspan 7
                         console.error('Backend Error:', data.message);
                         if(data.message && data.message.includes('Unauthorized')) {
                              setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                         }
                    }
                })
                .catch(error => {
                    transactionsTableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-600 dark:text-red-500">Network Error: ${htmlspecialchars(error.message)}</td></tr>`; // colspan 7
                    console.error('Fetch error:', error);
                });
        }

        // --- Function to Fetch and Display Transaction Details in Modal (UPDATED with Tailwind Styling) ---
        function viewTransactionDetails(txnId) {
            // Set the transaction ID in the modal title
            modalTransactionIdSpan.textContent = txnId;
            // Clear previous items and show loading message
            modalTransactionItemsDiv.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading items...</p>';

            // Show the modal using Tailwind/Flowbite approach (hide/show classes)
            transactionDetailsModal.classList.remove('hidden');
            transactionDetailsModal.classList.add('flex'); // Use flex to center it easily


            fetch(`Workspace_transaction_details.php?txn_id=${txnId}`) // Call the new backend script
                .then(response => {
                    if (!response.ok) {
                         return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                    }
                    return response.json();
                })
                .then(data => {
                    modalTransactionItemsDiv.innerHTML = ''; // Clear loading message

                    if (data.success) {
                        if (data.items && data.items.length > 0) {
                            data.items.forEach(item => {
                                const itemElement = document.createElement('div');
                                // Using Tailwind classes for item styling
                                itemElement.classList.add('flex', 'items-center', 'pb-3', 'mb-3', 'border-b', 'border-gray-200', 'dark:border-gray-600', 'last:border-b-0', 'last:mb-0', 'last:pb-0');

                                // Assuming image_path in food table is relative path like images/food.jpg
                                // Adjust path if necessary if your images folder is not directly under project root
                                const imageUrl = `../${htmlspecialchars(item.food_image_path || 'images/placeholder.jpg')}`;


                                itemElement.innerHTML = `
                                    <img src="${imageUrl}" alt="${htmlspecialchars(item.food_name)}" class="w-14 h-14 object-cover rounded-md mr-4">
                                    <div class="flex-grow"> 
                                        <div class="font-semibold text-gray-900 dark:text-white">${htmlspecialchars(item.food_name)}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Qty: ${htmlspecialchars(item.quantity)} | Unit Price: Rs. ${htmlspecialchars(parseFloat(item.unit_price).toFixed(2))}
                                        </div>
                                         <div class="text-sm text-gray-800 dark:text-gray-200 font-medium">
                                             Item Total: Rs. ${htmlspecialchars(parseFloat(item.item_total).toFixed(2))}
                                         </div>
                                    </div>
                                `;
                                modalTransactionItemsDiv.appendChild(itemElement);
                            });
                        } else {
                            modalTransactionItemsDiv.innerHTML = `<p class="text-gray-500 dark:text-gray-400">${htmlspecialchars(data.message || 'No items found for this transaction.')}</p>`;
                        }
                    } else {
                        modalTransactionItemsDiv.innerHTML = `<p class="text-red-600 dark:text-red-500">Error: ${htmlspecialchars(data.message || 'Failed to fetch item details.')}</p>`;
                        console.error('Backend Error (Details):', data.message);
                    }
                })
                .catch(error => {
                     modalTransactionItemsDiv.innerHTML = `<p class="text-red-600 dark:text-red-500">Network Error: ${htmlspecialchars(error.message)}</p>`;
                    console.error('Fetch error (Details):', error);
                });
        }


        // Helper function for HTML escaping (good for preventing XSS)
         // This function should match the one used in product.php and other pages.
          function htmlspecialchars(str) {
              if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
              else if (str === null || str === undefined) { return ''; }
              const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
              return str.replace(/[&<>"']/g, function(m) { return map[m]; });
          }


        // --- Event Listeners ---

        // Call the function to load transactions when the page is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            fetchAndDisplayTransactions(); // Call the function to load transactions initially

            // --- Event Delegation for View Buttons ---
            // Listen for clicks on the table body (more efficient than adding listeners to each button)
            transactionsTableBody.addEventListener('click', function(event) {
                // Check if the clicked element or its parent is a 'View' button link
                 // Use closest to handle clicks on the link itself or elements inside it if any
                const viewButton = event.target.closest('.view-transaction-btn');
                if (viewButton) {
                    event.preventDefault(); // Prevent the default link behavior
                    const txnId = viewButton.dataset.id; // Get the transaction ID from the data-id attribute
                    if (txnId) {
                        viewTransactionDetails(txnId); // Call the function to show details in modal
                    }
                }
            });

            // --- Modal Close Buttons ---
            // Get all elements that have data-modal-hide attribute targeting this modal
            document.querySelectorAll('[data-modal-hide="transactionDetailsModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    transactionDetailsModal.classList.add('hidden'); // Hide the modal
                    transactionDetailsModal.classList.remove('flex'); // Remove flex display
                });
            });


            // --- Close Modal when clicking outside of modal content (Optional) ---
            // This might interfere with Flowbite's own modal logic if you're using their JS
            /*
            window.addEventListener('click', function(event) {
                if (event.target === transactionDetailsModal) {
                    transactionDetailsModal.classList.add('hidden'); // Hide the modal
                    transactionDetailsModal.classList.remove('flex'); // Remove flex display
                }
            });
            */
        });

    </script>

</body>

</html>
<?php
// Close the database connection at the end of the script
// Note: Closing the connection here is only effective if the script reaches this point
// after all execution. For simple scripts, omitting the closing PHP tag is safer.
if (isset($link)) {
    // mysqli_close($link); // It's often safer to omit this and let PHP close automatically
}
?>