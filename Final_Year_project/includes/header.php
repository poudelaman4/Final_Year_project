<?php
/**
 * includes/header.php - Site-wide header navigation
 *
 * This file provides the header section for the web application.  It handles
 * conditional display of navigation elements based on user authentication (student or admin)
 * and retrieves user-specific data for display.
 */

// Ensure session is started by the including file.  This file should NOT call session_start().
if (session_status() === PHP_SESSION_NONE) {
    // IMPORTANT:  Throw an exception or trigger_error() and trigger_error() and stop execution if session isn't started
    // You MUST handle this in the files that include header.php
    trigger_error("Session must be started before including header.php", E_USER_ERROR);
    //  OR, if you want to be less strict (but it's NOT recommended):
    //  session_start();  // REMOVE THIS LINE
}


// Determine user login status.  These variables should be set by the including script.
$is_student_logged_in = isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
$is_admin_logged_in   = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);

// Define default values to prevent "Undefined variable" errors.  Use null coalescing.
//  *** CHANGED THIS LINE TO USE username ***
$student_name    = isset($_SESSION['student_username']) ? $_SESSION['student_username'] : 'Guest';
$student_balance = $_SESSION['student_balance'] ?? 'N/A';
$admin_username  = $_SESSION['admin_username'] ?? 'Admin';
$admin_role      = $_SESSION['admin_role'] ?? 'Role';

// Define server-relative paths.
$student_profile_url = '/scps/Final_Year_Project/student/profile.php';
$index_url           = '/scps/Final_Year_Project/index.php';
$login_url           = '/scps/Final_Year_Project/login.php';
$admin_dashboard_url = '/scps/Final_Year_Project/admin/dashboard.php';
$admin_login_url     = '/scps/Final_Year_Project/admin/login.php';
$logout_url          = '/scps/Final_Year_Project/logout.php';
$admin_logout_url    = '/scps/Final_Year_Project/admin/logout.php';
$images_url          = '/scps/Final_Year_Project/images/your_logo_path.png';


/**
 * Outputs the main navigation menu.
 */
function output_navigation(
    bool $is_student_logged_in,
    bool $is_admin_logged_in,
    string $student_name,
    string $student_balance,
    string $admin_username,
    string $admin_role,
    string $student_profile_url,
    string $index_url,
    string $login_url,
    string $admin_dashboard_url,
    string $admin_login_url,
    string $logout_url,
    string $admin_logout_url,
    string $images_url
) {
?>
    <nav class="bg-white dark:bg-gray-900 fixed w-full z-20 top-0 start-0 border-b border-gray-200 dark:border-gray-600">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="<?= $is_student_logged_in ? $index_url : ($is_admin_logged_in ? $admin_dashboard_url : $login_url); ?>" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="<?= $images_url ?>" class="h-8" alt="Site Logo">
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">United Technical Khaja Ghar</span>
            </a>
            <div class="hidden md:flex items-center space-x-4 rtl:space-x-reverse">
                <?php if ($is_student_logged_in): ?>
                    <span class="text-gray-700 dark:text-white font-bold">Hi!<a href="<?= $student_profile_url ?>"> <?= htmlspecialchars($student_name); ?></a></span>
                    <span class="text-gray-900 dark:text-white">Balance: ₹<span id="student-balance"><?= htmlspecialchars($student_balance); ?></span></span>
                    <button id="open-cart-panel" type="button" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Pay
                    </button>
                    <a href="<?= $logout_url ?>" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Logout</a>
                <?php elseif ($is_admin_logged_in): ?>
                    <span class="text-gray-700 dark:text-white font-bold">Admin: <?= htmlspecialchars($admin_username); ?></span>
                    <span class="text-gray-900 dark:text-white">(<?= htmlspecialchars($admin_role); ?>)</span>
                    <?php if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php' || basename(dirname($_SERVER['PHP_SELF'])) !== 'admin'): ?>
                        <a href="<?= $admin_dashboard_url ?>" class="text-white bg-purple-600 hover:bg-purple-700 focus:ring-4 focus:outline-none focus:ring-purple-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Dashboard</a>
                    <?php endif; ?>
                    <a href="<?= $admin_logout_url ?>" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Logout</a>
                <?php else: ?>
                    <a href="<?= $login_url ?>" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Student Login</a>
                    <a href="<?= $admin_login_url ?>" class="ml-2 text-gray-700 dark:text-white border border-gray-700 dark:border-gray-300 hover:bg-gray-700 hover:text-white font-medium rounded-lg text-sm px-4 py-2 text-center">Admin Login</a>
                <?php endif; ?>
            </div>
            <div class="flex md:hidden items-center">
                <button data-collapse-toggle="navbar-sticky" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-sticky" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>
            <div class="items-center justify-between hidden w-full md:hidden md:w-auto md:order-1" id="navbar-sticky">
                <ul class="flex flex-col p-4 md:p-0 mt-4 font-medium border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <?php if ($is_student_logged_in): ?>
                        <li><span class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold">Hi! <a href="<?= $student_profile_url ?>"><?= htmlspecialchars($student_name); ?></a></span></li>
                        <li><span class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold">Balance: ₹<span id="student-balance-mobile"><?= htmlspecialchars($student_balance); ?></span></span></li>
                        <li>
                            <button id="open-cart-panel-mobile" type="button" class="w-full text-left relative inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="w-5 h-5 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Pay
                            </button>
                        </li>
                        <li><a href="<?= $logout_url ?>" class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold bg-red-600 hover:bg-red-800 text-white">Logout</a></li>
                    <?php elseif ($is_admin_logged_in): ?>
                        <li><span class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold">Admin: <?= htmlspecialchars($admin_username); ?></span></li>
                        <li><span class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold">(<?= htmlspecialchars($admin_role); ?>)</span></li>
                        <?php if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php' || basename(dirname($_SERVER['PHP_SELF'])) !== 'admin'): ?>
                            <li><a href="<?= $admin_dashboard_url ?>" class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold bg-purple-600 hover:bg-purple-700 text-white">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="<?= $admin_logout_url ?>" class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold bg-red-600 hover:bg-red-800 text-white">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= $login_url ?>" class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold bg-indigo-600 hover:bg-indigo-700 text-white">Student Login</a></li>
                        <li><a href="<?= $admin_login_url ?>" class="block py-2 px-3 text-gray-900 rounded dark:text-white font-bold border border-gray-700 dark:border-gray-300 hover:bg-gray-700 hover:text-white">Admin Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
<?php
}

// Call the function to output the navigation.
output_navigation(
    $is_student_logged_in,
    $is_admin_logged_in,
    $student_name,
    $student_balance,
    $admin_username,
    $admin_role,
    $student_profile_url,
    $index_url,
    $login_url,
    $admin_dashboard_url,
    $admin_login_url,
    $logout_url,
    $admin_logout_url,
    $images_url
);

?>