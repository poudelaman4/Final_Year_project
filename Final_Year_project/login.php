<?php
session_start();

// Include database connection
require_once './includes/db_connection.php';

// Define server-relative paths
$login_url = '/scps/Final_Year_Project/login.php';
$admin_login_url = '/scps/Final_Year_Project/admin/login.php';
$images_url          = '/scps/Final_Year_Project/images/your_logo_path.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <?php include './includes/packages.php'; ?>
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background-color: #f3f4f6;
        }
        .login-container { 
            width: 100%; 
            max-width: 400px; 
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="login-container bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Student Login</h2>
        <?php
            if (isset($_SESSION['login_error'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Error:</strong>
                        <span class="block sm:inline">' . $_SESSION['login_error'] . '</span>
                      </div>';
                unset($_SESSION['login_error']);
            }
        ?>
        <form action="authenticate.php" method="POST">
            <div class="mb-4">
                <label for="identifier" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Student ID / Email:</label>
                <input type="text" id="identifier" name="identifier" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password:</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Log In
            </button>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't have an account? <a href="signup.php" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Sign up</a>
            </p>
        </form>
        <div class="mt-6 text-center">
            <a href="<?= $admin_login_url ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Are you an admin?</a>
        </div>
    </div>
    
</body>
</html>
