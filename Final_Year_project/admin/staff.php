<?php
// admin/staff.php - Manage Admin/Staff Users

date_default_timezone_set('Asia/Kathmandu');
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// You might want to restrict access to this page based on admin role in the future
// For example, only 'super_administrator' can manage other admins.
// if ($_SESSION['admin_role'] !== 'administrator' && $_SESSION['admin_role'] !== 'super_administrator') {
//    $_SESSION['error_message'] = "You do not have permission to access this page.";
//    header('Location: dashboard.php');
//    exit();
// }


require_once '../includes/db_connection.php';
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

include '../includes/packages.php'; // Tailwind, Flowbite, etc.
include '../includes/admin_header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <?php // packages.php is included above ?>
    <style>
        .table-message {
            text-align: center;
            padding: 1.5rem;
            color: #6b7280; /* gray-500 */
        }
        .dark .table-message {
            color: #9ca3af; /* gray-400 */
        }
        .action-btn {
            margin-right: 0.5rem; /* Spacing between action buttons */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans dark:bg-gray-900">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="staff-action-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Staff Management</h1>
            <a href="add_staff.php" id="addStaffButton" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block mr-1 align-middle">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Staff
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Full Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Last Login</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" id="staffTableBody">
                        <tr>
                            <td colspan="6" class="table-message">Loading staff members...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700" id="staffPaginationContainer" style="display: none;">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="staffPrevMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"> Previous </button>
                    <button id="staffNextMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"> Next </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing <span class="font-medium" id="staffShowingFrom">0</span> to <span class="font-medium" id="staffShowingTo">0</span> of <span class="font-medium" id="staffTotalRecords">0</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="staffPrev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                            <span id="staffPageNumbers" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">...</span>
                            <button id="staffNext" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; // Standard footer ?>

    <script>
        const staffActionConfirmation = document.getElementById('staff-action-confirmation');
        function showStaffActionConfirmation(message = 'Success!', type = 'success') { // type can be 'success', 'error', 'warning'
            if (!staffActionConfirmation) return;
            staffActionConfirmation.textContent = message;
            
            let bgColor = 'bg-green-500'; // Default success
            if (type === 'error') bgColor = 'bg-red-500';
            else if (type === 'warning') bgColor = 'bg-yellow-500';

            staffActionConfirmation.className = `fixed top-5 left-1/2 -translate-x-1/2 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform ${bgColor} block`;
            setTimeout(() => { staffActionConfirmation.classList.add('opacity-100'); }, 10);
            setTimeout(() => {
                staffActionConfirmation.classList.remove('opacity-100');
                setTimeout(() => { staffActionConfirmation.classList.remove('block'); staffActionConfirmation.classList.add('hidden'); }, 500);
            }, 3000);
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr || dateTimeStr === '0000-00-00 00:00:00') return 'Never'; // Handle null or default zero datetime
            try {
                const date = new Date(dateTimeStr);
                if (isNaN(date.getTime())) { return 'Invalid Date'; }
                const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
                return date.toLocaleDateString('en-IN', options); // Adjust locale as needed
            } catch (e) {
                console.error("Error formatting date/time:", dateTimeStr, e);
                return 'Invalid Date';
            }
        }

        let staffCurrentPage = 1;
        let staffItemsPerPage = 10; // Or load from user preference
        let staffTotalRecords = 0;

        async function fetchStaffMembers(page = 1, itemsPerPage = 10, searchTerm = '') {
            const tableBody = document.getElementById('staffTableBody');
            const paginationContainer = document.getElementById('staffPaginationContainer');
            if (!tableBody || !paginationContainer) {
                console.error('Staff table body or pagination container not found!');
                return;
            }

            tableBody.innerHTML = `<tr><td colspan="6" class="table-message">Loading staff members...</td></tr>`;
            paginationContainer.style.display = 'none'; // Hide pagination while loading

            const queryParams = new URLSearchParams({
                page: page,
                itemsPerPage: itemsPerPage,
                // search: searchTerm // Uncomment if search is implemented
            });

            try {
                // TODO: Create this API endpoint: admin/api/fetch_staff.php
                const response = await fetch(`./api/fetch_staff.php?${queryParams.toString()}`);
                const data = await response.json();

                if (response.ok && data.success) {
                    staffTotalRecords = data.total_records || 0;
                    updateStaffTable(data.staff || []);
                    updateStaffPagination();
                    if (data.staff && data.staff.length > 0) {
                        paginationContainer.style.display = 'flex';
                    }
                    // showStaffActionConfirmation(data.message || 'Staff fetched.', 'success'); // Maybe too noisy for every fetch
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="table-message">${htmlspecialchars(data.message || 'No staff members found or error loading.')}</td></tr>`;
                    showStaffActionConfirmation(data.message || 'Could not fetch staff.', 'error');
                    staffTotalRecords = 0;
                    updateStaffPagination(); // Update to show 0 records
                }
            } catch (error) {
                console.error('fetchStaffMembers: Fetch Error:', error);
                tableBody.innerHTML = `<tr><td colspan="6" class="table-message error">Error loading staff members. Check console.</td></tr>`;
                showStaffActionConfirmation('Network error fetching staff!', 'error');
                staffTotalRecords = 0;
                updateStaffPagination();
            }
        }

        function updateStaffTable(staffList) {
            const tableBody = document.getElementById('staffTableBody');
            tableBody.innerHTML = ''; // Clear existing rows

            if (staffList && staffList.length > 0) {
                staffList.forEach(staff => {
                    const statusBadgeClass = staff.is_active == 1
                        ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'
                        : 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200';
                    const statusText = staff.is_active == 1 ? 'Active' : 'Inactive';

                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${htmlspecialchars(staff.full_name)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${htmlspecialchars(staff.username)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${htmlspecialchars(staff.role)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusBadgeClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDateTime(staff.last_login)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_staff.php?id=${staff.staff_id}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600 action-btn edit-staff-btn" data-staff-id="${staff.staff_id}" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                </a>
                                <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 action-btn delete-staff-btn" data-staff-id="${staff.staff_id}" data-staff-username="${htmlspecialchars(staff.username)}" title="${staff.is_active == 1 ? 'Deactivate' : 'Activate'}">
                                   ${staff.is_active == 1 ? 
                                     `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>` :
                                     `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`
                                   }
                                </button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
                 // Add event listeners for the newly created edit and delete buttons
                document.querySelectorAll('.delete-staff-btn').forEach(button => {
                    button.addEventListener('click', handleDeleteStaff);
                });
                // Edit buttons go to a new page, so no complex listener needed here unless using modals.

            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="table-message">No staff members found.</td></tr>`;
            }
        }


        function updateStaffPagination() {
            const totalPages = Math.ceil(staffTotalRecords / staffItemsPerPage);
            const showingFromEl = document.getElementById('staffShowingFrom');
            const showingToEl = document.getElementById('staffShowingTo');
            const totalRecordsEl = document.getElementById('staffTotalRecords');
            const prevBtn = document.getElementById('staffPrev');
            const nextBtn = document.getElementById('staffNext');
            const prevMobileBtn = document.getElementById('staffPrevMobile');
            const nextMobileBtn = document.getElementById('staffNextMobile');
            const pageNumbersSpan = document.getElementById('staffPageNumbers');
            const paginationContainer = document.getElementById('staffPaginationContainer');

            if (!paginationContainer) return;


            if (staffTotalRecords === 0) {
                if (showingFromEl) showingFromEl.textContent = '0';
                if (showingToEl) showingToEl.textContent = '0';
                paginationContainer.style.display = 'none';
            } else {
                const from = (staffCurrentPage - 1) * staffItemsPerPage + 1;
                const to = Math.min(staffCurrentPage * staffItemsPerPage, staffTotalRecords);
                if (showingFromEl) showingFromEl.textContent = from;
                if (showingToEl) showingToEl.textContent = to;
                paginationContainer.style.display = 'flex';
            }
            if (totalRecordsEl) totalRecordsEl.textContent = staffTotalRecords;


            if (prevBtn) prevBtn.disabled = staffCurrentPage <= 1;
            if (nextBtn) nextBtn.disabled = staffCurrentPage >= totalPages;
            if (prevMobileBtn) prevMobileBtn.disabled = staffCurrentPage <= 1;
            if (nextMobileBtn) nextMobileBtn.disabled = staffCurrentPage >= totalPages;

            if (pageNumbersSpan) {
                if (totalPages <= 1) {
                    pageNumbersSpan.textContent = '1';
                } else {
                    pageNumbersSpan.textContent = `Page ${staffCurrentPage} of ${totalPages}`;
                }
            }
        }

        function handleStaffPaginationClick(event) {
            const targetId = event.target.closest('button')?.id; // Ensure we get button id even if svg is clicked
            // const searchTerm = document.getElementById('staffSearch')?.value || ''; // if search implemented

            if (targetId === 'staffPrev' || targetId === 'staffPrevMobile') {
                if (staffCurrentPage > 1) {
                    staffCurrentPage--;
                    fetchStaffMembers(staffCurrentPage, staffItemsPerPage /*, searchTerm*/);
                }
            } else if (targetId === 'staffNext' || targetId === 'staffNextMobile') {
                const totalPages = Math.ceil(staffTotalRecords / staffItemsPerPage);
                if (staffCurrentPage < totalPages) {
                    staffCurrentPage++;
                    fetchStaffMembers(staffCurrentPage, staffItemsPerPage /*, searchTerm*/);
                }
            }
        }

        async function handleDeleteStaff(event) {
            const button = event.currentTarget;
            const staffId = button.dataset.staffId;
            const staffUsername = button.dataset.staffUsername;
            const isActive = button.title.toLowerCase().includes('deactivate'); // True if current action is to deactivate

            const actionText = isActive ? 'deactivate' : 'activate';
            const confirmAction = confirm(`Are you sure you want to ${actionText} staff member "${staffUsername}" (ID: ${staffId})?`);

            if (confirmAction) {
                try {
                    // TODO: Create this API endpoint: admin/api/toggle_staff_status.php or admin/api/delete_staff.php
                    const response = await fetch('./api/toggle_staff_status.php', { // Or delete_staff.php
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            staff_id: staffId,
                            action: actionText // Send 'deactivate' or 'activate'
                        }),
                    });
                    const data = await response.json();

                    if (response.ok && data.success) {
                        showStaffActionConfirmation(data.message || `Staff ${actionText}d successfully.`, 'success');
                        fetchStaffMembers(staffCurrentPage, staffItemsPerPage); // Refresh the list
                    } else {
                        showStaffActionConfirmation(data.message || `Failed to ${actionText} staff.`, 'error');
                    }
                } catch (error) {
                    console.error(`Error ${actionText}ing staff:`, error);
                    showStaffActionConfirmation(`Network error. Could not ${actionText} staff.`, 'error');
                }
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            fetchStaffMembers(staffCurrentPage, staffItemsPerPage);

            document.getElementById('staffPrev')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffNext')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffPrevMobile')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffNextMobile')?.addEventListener('click', handleStaffPaginationClick);

            // Event listener for search (if implemented)
            // const staffSearchInput = document.getElementById('staffSearch');
            // if (staffSearchInput) {
            //     let searchTimeout;
            //     staffSearchInput.addEventListener('input', function() {
            //         clearTimeout(searchTimeout);
            //         searchTimeout = setTimeout(() => {
            //             staffCurrentPage = 1;
            //             fetchStaffMembers(staffCurrentPage, staffItemsPerPage, this.value);
            //         }, 500); // Debounce search
            //     });
            // }
        });

    </script>
</body>
</html>
<?php
if (isset($link)) {
    mysqli_close($link);
}
?>