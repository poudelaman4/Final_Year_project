<?php
// index.php - Main student menu page (in Project Root)

// Start the session first. NO output before this line.
session_start(); // <--- Make sure this is the very first line

// --- REQUIRE STUDENT LOGIN ---
// Check if student_id is NOT set in the session or is empty
if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    // If not logged in as student, redirect to the student login page (in root)
    header('Location: login.php'); // login.php is in the root
    exit(); // Stop script execution
}
// --- END REQUIRE STUDENT LOGIN ---

// Include database connection after session check
require_once './includes/db_connection.php'; // Path is correct for root files

// Check database connection (redundant if db_connection.php dies, but doesn't hurt)
if (!$link || mysqli_connect_errno()) {
     error_log("index.php: Database connection failed: ".mysqli_connect_error());
     // Consider a redirect to a maintenance page here if needed
}

// Initialize variables for header display (will be fetched below)
$student_name = "Guest"; // Default if not found
$student_balance = "N/A"; // Default display

// Fetch student name and balance if logged in
$student_id = (int)$_SESSION['student_id']; // Get student ID from session
error_log("DEBUG: student_id from session: " . $student_id);

// Fetch student name from 'student' table
$sql_student = "SELECT full_name FROM student WHERE student_id = ?";
if ($stmt_student = mysqli_prepare($link, $sql_student)) {
    mysqli_stmt_bind_param($stmt_student, "i", $student_id);
    if (mysqli_stmt_execute($stmt_student)) {
        $result_student = mysqli_stmt_get_result($stmt_student);
        if (mysqli_num_rows($result_student) > 0) {
             if ($student_info = mysqli_fetch_assoc($result_student)) {
                $student_name = htmlspecialchars($student_info['full_name']);
            }
        } else {
             error_log("Warning: Student ID " . $student_id . " found in session but not in student table.");
             // Optional: Log out user if their record is missing/invalid
        }
    } else {
         error_log("DB Error: Could not execute student name fetch in index.php: " . mysqli_stmt_error($stmt_student));
    }
    mysqli_stmt_close($stmt_student);
} else {
     error_log("DB Error: Could not prepare student name fetch query in index.php: " . mysqli_error($link));
}


// Fetch student's balance from the 'nfc_card' table (linked by student_id)
$sql_balance = "SELECT current_balance FROM nfc_card WHERE student_id = ?";
 if ($stmt_balance = mysqli_prepare($link, $sql_balance)) {
    mysqli_stmt_bind_param($stmt_balance, "i", $student_id);
    if (mysqli_stmt_execute($stmt_balance)) {
        $result_balance = mysqli_stmt_get_result($stmt_balance);
        if (mysqli_num_rows($result_balance) === 1) {
             if ($balance_info = mysqli_fetch_assoc($result_balance)) {
                $student_balance = number_format((float)$balance_info['current_balance'], 2);
            }
        } else if (mysqli_num_rows($result_balance) > 1) {
             error_log("Warning: Multiple NFC cards found for student_id: " . $student_id . " in index.php.");
             $student_balance = "Error";
        }
        else {
             error_log("Warning: No NFC card found for student_id: " . $student_id . " in index.php.");
             $student_balance = "N/A";
             // Optional: Prevent ordering if no card found
        }
    } else {
         error_log("DB Error: Could not execute balance fetch in index.php: " . mysqli_stmt_error($stmt_balance));
         $student_balance = "Error";
    }
    mysqli_stmt_close($stmt_balance);
 } else {
      error_log("DB Error: Could not prepare balance fetch query in index.php: " . mysqli_error($link));
      $student_balance = "Error";
 }

// Check if student_id is set in the session to determine student login status for HTML elements
// This variable is used for the "Add" button disabled state and the "Please log in" messages
// It's redundant with the check at the top, but used specifically in the HTML section
$is_student_logged_in = isset($_SESSION['student_id']) && !empty($_SESSION['student_id']); // <--- This variable IS USED IN THE HTML


// Prepare and execute the SELECT statement to fetch available food items
$sql = "SELECT food_id, name, description, price, image_path, category, is_available FROM food WHERE is_available = 1 ORDER BY category, name";

$food_items = [];
$no_items_message = "";

if ($stmt = mysqli_prepare($link, $sql)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $food_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $no_items_message = "No food items are currently available.";
        }
    } else {
        $no_items_message = "Error loading menu. Please try again later.";
        error_log("DB Error: Error executing food fetch query on index.php: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $no_items_message = "Error preparing food fetch query.";
    error_log("DB Error: Error preparing food fetch query on index.php: " . mysqli_error($link));
}

// Close connection (optional - PHP closes automatically at the end of the script)
// mysqli_close($link);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Canteen Menu</title>

    <?php
    // --- Include CSS/JS libraries (packages.php) ---
    // Make sure your packages.php includes the Tailwind CSS link with the CORRECT PATH!
    include "./includes/packages.php"; // Path is correct for root files
    ?>
</head>
<body class="bg-gray-100 dark:bg-gray-900"> <?php
    // --- Include Header HTML ---
    // Ensure includes/header.php uses the $is_student_logged_in, $is_admin_logged_in, $student_name, $student_balance variables
    include "./includes/header.php"; // Path is correct for root files
    ?>

    <div id="cart-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
        Item added to cart!
    </div>
    <section class="text-gray-600 body-font mt-16" id="food-items-container">
        <div class="container px-5 py-8 mx-auto">
            <div class="flex flex-wrap -m-4">

                <?php
                // --- Display Food Items or Message ---
                if (!empty($food_items)) {
                    // Loop through each food item
                    foreach ($food_items as $food_item) {
                        // Determine category tag color
                        $category_tag_color = 'bg-gray-500';
                        $category_text = htmlspecialchars($food_item['category'] ?? '');

                        if (strtolower($food_item['category'] ?? '') === 'veg') { $category_tag_color = 'bg-green-500'; }
                        elseif (strtolower($food_item['category'] ?? '') === 'non-veg') { $category_tag_color = 'bg-red-500'; }
                        ?>
                        <div class="lg:w-1/4 md:w-1/2 p-4 w-full">
                            <div class="bg-white dark:bg-gray-700 rounded-lg overflow-hidden shadow-lg h-full flex flex-col">
                                <a class="block relative h-48 rounded-t-lg overflow-hidden" href="#">
                                     <img alt="<?= htmlspecialchars($food_item['name'] ?? ''); ?>" class="object-cover object-center w-full h-full block"
                                         src="<?= !empty($food_item['image_path']) ? htmlspecialchars($food_item['image_path']) : 'images/placeholder.jpg'; ?>">
                                 <?php if (!empty($category_text)): ?>
                                     <span class="absolute top-2 left-2 <?php echo $category_tag_color; ?> text-white text-xs font-semibold px-2 py-1 rounded-full z-10">
                                         <?php echo $category_text; ?>
                                     </span>
                                 <?php endif; ?>
                                </a>
                                <div class="p-6 flex-grow">
                                    <h3 class="text-gray-500 text-xs tracking-widest title-font mb-1">
                                        <?= htmlspecialchars(ucfirst(strtolower($food_item['category'] ?? ''))); ?>
                                    </h3>
                                    <h2 class="text-gray-900 dark:text-white title-font text-lg font-medium mb-2">
                                        <?= htmlspecialchars($food_item['name'] ?? ''); ?>
                                    </h2>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        ₹<?= htmlspecialchars(number_format((float)($food_item['price'] ?? 0), 2)); ?>
                                    </p>
                                </div>
                                <div class="p-6 pt-0">
                                     <?php $is_logged_in_for_button = isset($_SESSION['student_id']) && !empty($_SESSION['student_id']); ?>
                                     <button class="w-full text-white bg-indigo-500 border-0 py-2 px-6 focus:outline-none hover:bg-indigo-600 rounded add-to-cart-btn" data-food-id="<?= htmlspecialchars($food_item['food_id'] ?? ''); ?>" <?= $is_logged_in_for_button ? '' : 'disabled'; ?>>+ Add</button>
                                     <?php if (!$is_logged_in_for_button): ?>
                                         <p class="text-red-500 text-center text-sm mt-1">Please log in to add items.</p>
                                     <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Display the message if no food items were found
                    ?>
                    <div class="w-full text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 text-lg"><?= htmlspecialchars($no_items_message ?? ''); ?></p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <?php
    // --- Include Footer HTML ---
    include "./includes/footer.php"; // Path is correct for root files
    ?>

    <div id="panel-overlay" class="fixed top-0 left-0 w-full h-full bg-black opacity-50 z-40 hidden"></div>

    <div id="side-cart-panel" class="fixed top-0 left-full h-full w-1/5 bg-white dark:bg-gray-800 shadow-lg z-50 overflow-y-auto transition-transform duration-300 ease-in-out">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Cart</h2>
        </div>
        <div id="cart-items-list" class="p-4 space-y-4">
            <p class="text-gray-500 dark:text-gray-400">Cart is empty.</p> </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <span class="text-base font-semibold text-gray-900 dark:text-white">Total:</span>
                <span id="cart-total" class="text-base font-semibold text-gray-900 dark:text-white">₹0.00</span>
            </div>
            <button id="cancel-cart-btn" class="w-full mb-2 px-4 py-2 text-sm font-medium text-center text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-800">Cancel</button>
            <button id="confirm-order-btn" class="w-full px-4 py-2 text-sm font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500" <?= $is_student_logged_in ? '' : 'disabled'; ?>>Confirm Order</button> <?php if (!$is_student_logged_in): ?> <p class="text-red-500 text-center text-sm mt-1">Please log in to order.</p>
             <?php endif; ?>
        </div>
    </div>

    <script>
         // Helper function for HTML escaping (used in JS)
         function htmlspecialchars(str) {
             if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
             else if (str === null || str === undefined) { return ''; }
             const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
             return str.replace(/[&<>"']/g, function(m) { return map[m]; });
         }

        document.addEventListener('DOMContentLoaded', function () {
            const cartConfirmation = document.getElementById('cart-confirmation');
            const sideCartPanel = document.getElementById('side-cart-panel');
            const panelOverlay = document.getElementById('panel-overlay');
            const openCartPanelBtn = document.getElementById('open-cart-panel');
            const openCartPanelBtnMobile = document.getElementById('open-cart-panel-mobile');
            const cancelCartBtn = document.getElementById('cancel-cart-btn');
            const cartItemsList = document.getElementById('cart-items-list');
            const cartTotalSpan = document.getElementById('cart-total');
            const confirmOrderBtn = document.getElementById('confirm-order-btn');
            const studentBalanceSpan = document.getElementById('student-balance');
            const studentBalanceSpanMobile = document.getElementById('student-balance-mobile');

            function showConfirmation(message = 'Action successful!', color = 'green') {
                if (!cartConfirmation) return;
                cartConfirmation.textContent = message;
                cartConfirmation.classList.remove('bg-green-500', 'bg-red-500');
                cartConfirmation.classList.add(`bg-${color}-500`);
                cartConfirmation.classList.remove('hidden'); cartConfirmation.classList.add('block');
                setTimeout(() => { cartConfirmation.classList.add('opacity-100'); }, 10);
                setTimeout(() => {
                    cartConfirmation.classList.remove('opacity-100');
                    setTimeout(() => { cartConfirmation.classList.remove('block'); cartConfirmation.classList.add('hidden'); }, 500);
                }, 2500);
            }

            function openCartPanel() {
                if (!sideCartPanel || !panelOverlay) return;
                // Ensure panel starts off-screen immediately before transition
                sideCartPanel.classList.add('left-full');
                 // Remove any width classes that might be left from previous states
                 sideCartPanel.classList.remove('left-4/5', 'left-1/4', 'w-1/5', 'w-3/4'); // Remove potential width classes


                 // Determine the correct width class based on screen size
                 let panelPositionClass = 'left-full'; // Default to off-screen position
                 let panelElementWidthClass = 'w-1/5'; // Default width for the element itself
                 if (window.innerWidth < 768) { // Mobile breakpoint
                     panelPositionClass = 'left-1/4'; // Position: takes up 3/4 of the screen from the left
                     panelElementWidthClass = 'w-3/4'; // Width: element is 3/4 screen width
                 } else { // Desktop breakpoint
                     panelPositionClass = 'left-4/5'; // Position: takes up 1/5 of the screen from the left (panel starts at 80%)
                      panelElementWidthClass = 'w-1/5'; // Width: element is 1/5 screen width
                 }

                setTimeout(() => {
                     // Apply the width class BEFORE sliding in
                     sideCartPanel.classList.add(panelElementWidthClass);
                     sideCartPanel.classList.remove('left-full'); // Remove the off-screen class
                     sideCartPanel.classList.add(panelPositionClass); // Add the class to slide in

                    panelOverlay.classList.remove('hidden');
                    fetchAndDisplayCartItems();
                }, 50); // Small delay to allow class changes before transition
            }

            function closeCartPanel() {
                if (!sideCartPanel || !panelOverlay) return;
                sideCartPanel.classList.remove('left-4/5', 'left-1/4'); // Remove slide-in position classes
                sideCartPanel.classList.add('left-full'); // Move off-screen
                 // Optional: Remove width class after transition ends, or keep it. Removing might be cleaner.
                 // setTimeout(() => { sideCartPanel.classList.remove('w-1/5', 'w-3/4'); }, 300); // Adjust delay to match transition duration

                panelOverlay.classList.add('hidden');
            }


            async function fetchAndDisplayCartItems() {
                if (!cartItemsList || !cartTotalSpan) return;
                 cartItemsList.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading cart...</p>'; // Show loading state
                 if (cartTotalSpan) cartTotalSpan.textContent = '₹0.00'; // Reset total while loading

                try {
                    // Ensure the request method is explicitly GET for clarity
                    const response = await fetch('./get_cart.php', { method: 'GET' }); // <--- Explicitly set method

                    if (!response.ok) { // Check for HTTP errors (like 404, 500)
                         const errorText = await response.text();
                         console.error('HTTP Error fetching cart:', response.status, response.statusText, errorText);
                         // Display a user-friendly error in the cart panel
                         cartItemsList.innerHTML = `<p class="text-red-500">Error loading cart data (${response.status})</p>`;
                         if (cartTotalSpan) cartTotalSpan.textContent = '₹0.00';
                         return; // Stop here if HTTP error
                    }

                    // Check if the response is valid JSON
                    const text = await response.text(); // Read response as text first
                    try {
                         const data = JSON.parse(text); // Attempt to parse as JSON

                        if (data.success) {
                            cartItemsList.innerHTML = ''; // Clear loading message

                            if (data.cart_items && data.cart_items.length > 0) {
                                data.cart_items.forEach(item => {
                                     // Ensure item properties are not null or undefined before escaping
                                     const foodId = item.food_id != null ? htmlspecialchars(item.food_id) : '';
                                     const name = item.name != null ? htmlspecialchars(item.name) : 'Unknown Item';
                                     const price = item.price != null ? parseFloat(item.price).toFixed(2) : '0.00';
                                     const quantity = item.quantity != null ? htmlspecialchars(item.quantity) : '0';
                                     const subtotal = item.subtotal != null ? parseFloat(item.subtotal).toFixed(2) : '0.00';
                                     const imagePath = item.image_path != null && item.image_path !== '' ? htmlspecialchars(item.image_path) : 'images/placeholder.jpg';


                                    const itemHtml = `
                                        <div class="flex items-center space-x-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                                            <img src="${imagePath}" alt="${name}" class="w-12 h-12 object-cover rounded-md">
                                            <div class="flex-grow"><h4 class="text-sm font-medium text-gray-900 dark:text-white">${name}</h4><p class="text-sm text-gray-500 dark:text-gray-400">₹${price}</p></div>
                                            <div class="flex items-center space-x-2">
                                                 <button class="text-gray-600 dark:text-gray-400 hover:text-gray-900 focus:outline-none minus-item" data-food-id="${foodId}">-</button>
                                                 <span class="font-semibold text-gray-900 dark:text-white">${quantity}</span>
                                                 <button class="text-gray-600 dark:text-gray-400 hover:text-gray-900 focus:outline-none plus-item" data-food-id="${foodId}">+</button>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">₹${subtotal}</span>
                                                <button class="text-red-600 hover:text-red-800 focus:outline-none text-sm remove-item" data-food-id="${foodId}">Remove</button>
                                            </div>
                                        </div>`;
                                        cartItemsList.innerHTML += itemHtml;
                                });
                            } else {
                                // If cart is empty (success: true, but items array is empty or null)
                                cartItemsList.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Cart is empty.</p>'; // Correct empty message
                            }

                            if (cartTotalSpan) { cartTotalSpan.textContent = `₹${htmlspecialchars(parseFloat(data.cart_total || 0).toFixed(2))}`; }

                        } else {
                            // Backend returned success: false (e.g., database error fetching items)
                            console.error('Backend Error fetching cart:', data.message);
                            cartItemsList.innerHTML = `<p class="text-red-500">Error: ${htmlspecialchars(data.message || 'Unknown error from backend')}</p>`; // Display backend error message
                            if (cartTotalSpan) cartTotalSpan.textContent = '₹0.00';
                        }
                    } catch (jsonError) {
                        // Handle JSON parsing errors (if the response wasn't valid JSON)
                        console.error('JSON Parse Error:', jsonError, 'Response Text:', text);
                         cartItemsList.innerHTML = '<p class="text-red-500">Error processing cart data (invalid response)</p>';
                         if (cartTotalSpan) cartTotalSpan.textContent = '₹0.00';
                    }

                } catch (fetchError) {
                    // Handle errors that occur during the fetch process itself (network issues)
                    console.error('Fetch Error:', fetchError);
                    cartItemsList.innerHTML = '<p class="text-red-500">Error loading cart (network issue)</p>'; // Generic fetch error message
                    if (cartTotalSpan) cartTotalSpan.textContent = '₹0.00';
                }
            }

             async function updateCartItemQuantity(foodId, action) {
                 // Simple check to ensure foodId is valid before sending request
                 if (foodId === '' || foodId === null || typeof foodId === 'undefined') {
                     console.error('Attempted to update quantity with invalid food ID.');
                     showConfirmation('Error: Invalid item ID.', 'red');
                     return; // Stop the function if foodId is invalid
                 }
                 // Disable buttons temporarily to prevent multiple clicks
                 const buttons = cartItemsList.querySelectorAll(`button[data-food-id="${foodId}"]`);
                 buttons.forEach(button => button.disabled = true);

                 try {
                     // Construct form data to send food_id and action
                     const postData = new FormData();
                     postData.append('food_id', foodId);
                     postData.append('action', action);

                     // Send a POST request to update_quantity.php
                     const response = await fetch('./update_quantity.php', { // Path is correct for root
                         method: 'POST',
                         body: postData
                     });

                     // Check for HTTP errors
                     if (!response.ok) {
                         const errorText = await response.text();
                         console.error('HTTP Error updating quantity:', response.status, response.statusText, errorText);
                         showConfirmation('Failed to update cart (HTTP Error)!', 'red'); // Show user feedback
                         return; // Stop if HTTP error
                     }

                     // Parse the JSON response
                    const text = await response.text(); // Read response as text first
                    try {
                         const data = JSON.parse(text); // Attempt to parse as JSON

                         if (data.success) {
                             // If successful, refresh the cart display
                             fetchAndDisplayCartItems();
                             // Optional: Show a subtle success confirmation
                             // showConfirmation(data.message || 'Cart updated!', 'green');
                         } else {
                             // If backend reported failure
                             showConfirmation(data.message || 'Failed to update cart!', 'red'); // Show backend error message
                             console.error('Backend Error updating quantity:', data.message);
                         }
                    } catch (jsonError) {
                        // Handle JSON parsing errors
                        console.error('JSON Parse Error on quantity update:', jsonError, 'Response Text:', text);
                        showConfirmation('Error processing cart update (invalid response).', 'red');
                    }

                 } catch (fetchError) {
                     // Handle network or fetch errors
                     console.error('Fetch Error updating quantity:', fetchError);
                     showConfirmation('Error updating cart (network issue)', 'red'); // Show generic error
                 } finally {
                     // Re-enable buttons regardless of success/failure
                     buttons.forEach(button => button.disabled = false);
                 }
             }


            // Event listeners for Cart Panel Toggling buttons
            if (openCartPanelBtn) { openCartPanelBtn.addEventListener('click', openCartPanel); }
            if (openCartPanelBtnMobile) { openCartPanelBtnMobile.addEventListener('click', openCartPanel); }
            if (cancelCartBtn) {
                cancelCartBtn.addEventListener('click', async function() {
                     // Disable button temporarily
                     cancelCartBtn.disabled = true;
                     cancelCartBtn.textContent = 'Clearing...';

                    try {
                        const response = await fetch('./clear_cart.php');
                        if (!response.ok) { const errorText = await response.text(); console.error('HTTP Error clearing cart:', response.status, response.statusText, errorText); throw new Error('Failed to clear cart'); }

                        const text = await response.text(); // Read response as text first
                        try {
                            const data = JSON.parse(text); // Attempt to parse as JSON

                            if (data.success) {
                                fetchAndDisplayCartItems();
                                setTimeout(closeCartPanel, 100);
                                showConfirmation(data.message || 'Cart cleared!', 'green');
                            } else {
                                showConfirmation(data.message || 'Failed to clear cart!', 'red');
                                console.error('Backend Error clearing cart:', data.message);
                            }
                        } catch (jsonError) {
                            console.error('JSON Parse Error on clear cart:', jsonError, 'Response Text:', text);
                             showConfirmation('Error processing clear cart (invalid response).', 'red');
                        }
                    } catch (error) {
                        console.error('Fetch Error clearing cart:', error);
                        showConfirmation('Error clearing cart', 'red');
                    } finally {
                       // Re-enable button
                       cancelCartBtn.disabled = false;
                       cancelCartBtn.textContent = 'Cancel';
                    }
                });
            }
            if (panelOverlay) { panelOverlay.addEventListener('click', closeCartPanel); }

            // Event listeners for Cart Item Controls (using Delegation)
            // This listens for clicks on the cartItemsList div and checks if the clicked element is a button
            if (cartItemsList) { // Ensure the cart list element exists
                cartItemsList.addEventListener('click', function(event) {
                    const target = event.target; // The element that was actually clicked

                    // Get the food-id from the button's data attribute
                    const foodId = target.dataset.foodId;

                    // Check if a foodId was found and if the clicked element has one of the action classes
                    if (foodId) {
                        if (target.classList.contains('plus-item')) {
                            updateCartItemQuantity(foodId, 'increase');
                        } else if (target.classList.contains('minus-item')) {
                            updateCartItemQuantity(foodId, 'decrease');
                        } else if (target.classList.contains('remove-item')) {
                             // Optional: Add a confirmation dialog before removing
                             // if (confirm('Are you sure you want to remove this item?')) {
                                 updateCartItemQuantity(foodId, 'remove');
                             // }
                        }
                    }
                });
            }

            // Event listener for Add to Cart buttons (using Delegation)
             const foodItemsContainer = document.getElementById('food-items-container');
             if (foodItemsContainer) { // Ensure the food items container exists
                 foodItemsContainer.addEventListener('click', function(event) {
                     const target = event.target; // The element that was actually clicked
                     // Check if the clicked element is the 'add-to-cart-btn'
                     if (target.classList && target.classList.contains('add-to-cart-btn')) {
                         // Check if the button is disabled (user not logged in)
                         if (target.disabled) {
                              showConfirmation('Please log in to add items.', 'red');
                              return; // Stop if disabled
                         }
                         const foodId = target.dataset.foodId; // Get food ID from the button
                         if (!foodId) { console.error('JS Error: Food ID not found for add button.'); showConfirmation('Internal error: Food ID missing.', 'red'); return; }
                         const postData = new FormData(); postData.append('food_id', foodId);

                         // Temporarily disable the clicked button
                         target.disabled = true;
                         target.textContent = 'Adding...';

                         fetch('./add_to_cart.php', { method: 'POST', body: postData }) // Path is correct for root
                         .then(response => {
                             if (!response.ok) { return response.text().then(text => { console.error('HTTP Error adding item:', response.status, response.statusText, text); throw new Error('Network response was not ok: ' + response.statusText); }); }
                             return response.json();
                         })
                         .then(data => {
                             if (data.success) {
                                  showConfirmation(data.message || 'Item added!');
                                  // Optional: Refresh cart display after adding if panel is open
                                  // if (!sideCartPanel.classList.contains('left-full')) {
                                  //     fetchAndDisplayCartItems();
                                  // }
                             }
                             else { console.error('Backend Error: Failed to add item:', data.message); showConfirmation(data.message || 'Failed to add item!', 'red'); }
                         })
                         .catch(error => { console.error('Fetch Error: Error adding item to cart:', error); showConfirmation('Error adding item. Check console for details.', 'red'); })
                         .finally(() => {
                             // Re-enable the button
                             target.disabled = false;
                              target.textContent = '+ Add'; // Reset button text
                         });
                     }
                 });
             }

            // Confirm order button event listener
            if (confirmOrderBtn) {
                confirmOrderBtn.addEventListener('click', async function() {
                    const currentTotalText = cartTotalSpan ? cartTotalSpan.textContent.replace('₹', '') : '0.00';
                    const currentTotal = parseFloat(currentTotalText) || 0;
                    if (currentTotal <= 0) { showConfirmation('Your cart is empty!', 'red'); console.warn('Attempted to confirm an empty cart.'); return; }

                    confirmOrderBtn.disabled = true;
                    confirmOrderBtn.textContent = 'Processing...';

                    try {
                        const response = await fetch('./process_order.php', { method: 'POST' }); // Path is correct for root
                        if (!response.ok) { const errorText = await response.text(); console.error('HTTP Error processing order:', response.status, response.statusText, errorText); throw new Error('Network response was not ok or PHP error occurred.'); }

                        // Read response as text first to handle potential non-JSON errors from PHP
                        const text = await response.text();
                        try {
                            const data = JSON.parse(text); // Attempt to parse as JSON

                            if (data.success) {
                                console.log('Order processed successfully:', data.message);
                                showConfirmation(data.message || 'Order placed!', 'green');
                                closeCartPanel(); // Close cart after successful order
                                fetchAndDisplayCartItems(); // Refresh cart (should be empty)
                                // Update balance displayed in header
                                if (data.new_balance !== null && typeof data.new_balance !== 'undefined') {
                                    const formattedBalance = parseFloat(data.new_balance).toFixed(2);
                                    if (studentBalanceSpan) studentBalanceSpan.textContent = formattedBalance;
                                    if (studentBalanceSpanMobile) studentBalanceSpanMobile.textContent = formattedBalance;
                                }
                            } else {
                                console.error('Order failed:', data.message);
                                showConfirmation(data.message || 'Order failed!', 'red');
                                // Update balance even on failure if provided (e.g., insufficient funds)
                                if (data.new_balance !== null && typeof data.new_balance !== 'undefined') {
                                    const formattedBalance = parseFloat(data.new_balance).toFixed(2);
                                    if (studentBalanceSpan) studentBalanceSpan.textContent = formattedBalance;
                                    if (studentBalanceSpanMobile) studentBalanceSpanMobile.textContent = formattedBalance;
                                }
                            }
                        } catch (jsonError) {
                           // Handle JSON parsing errors
                           console.error('JSON Parse Error on order confirmation:', jsonError, 'Response Text:', text);
                           showConfirmation('An error occurred processing order (invalid response).', 'red');
                        }
                    } catch (fetchError) {
                       console.error('Fetch Error: Error confirming order:', fetchError);
                       showConfirmation('An error occurred. Please try again.', 'red');
                    }
                    finally {
                       confirmOrderBtn.disabled = false; // Re-enable button
                       confirmOrderBtn.textContent = 'Confirm Order'; // Reset button text
                    }
                });
            }

             // Initial fetch to display cart when panel is opened
             // Note: fetchAndDisplayCartItems is called when openCartPanel is triggered
        });
    </script>
</body>
</html>
<?php // Note: No closing PHP tag is intentional ?>