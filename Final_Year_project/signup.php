<?php
session_start();

// Include database connection
require_once './includes/db_connection.php';

$success_message = '';
$error_message = '';

// Form field values to retain on error
$full_name_val = $contact_number_val = $student_email_val = $parent_email_val = $username_val = $nfc_id_val = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name_val = trim(htmlspecialchars($_POST['full_name']));
    $contact_number_val = trim(htmlspecialchars($_POST['contact_number']));
    $student_email_val = trim(htmlspecialchars($_POST['student_email']));
    $parent_email_val = trim(htmlspecialchars($_POST['parent_email']));
    $username_val = trim(htmlspecialchars($_POST['username']));
    $nfc_id_val = trim(htmlspecialchars($_POST['nfc_id']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validations
    if (empty($full_name_val) || empty($contact_number_val) || empty($student_email_val) || empty($username_val) || empty($nfc_id_val) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($student_email_val, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid student email format.";
    } elseif (!empty($parent_email_val) && !filter_var($parent_email_val, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid parent email format (if provided).";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check for uniqueness: username, student_email, nfc_id (in both tables)
        $sql_check = "SELECT
                        (SELECT COUNT(*) FROM student WHERE username = ?) AS username_count,
                        (SELECT COUNT(*) FROM student WHERE student_email = ?) AS student_email_count,
                        (SELECT COUNT(*) FROM student WHERE nfc_id = ?) AS student_nfc_count,
                        (SELECT COUNT(*) FROM nfc_card WHERE nfc_id = ?) AS card_nfc_count";

        if ($stmt_check = mysqli_prepare($link, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ssss", $username_val, $student_email_val, $nfc_id_val, $nfc_id_val);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $counts = mysqli_fetch_assoc($result_check);
            mysqli_stmt_close($stmt_check);

            if ($counts['username_count'] > 0) {
                $error_message = "Username already taken. Please choose another.";
            } elseif ($counts['student_email_count'] > 0) {
                $error_message = "Student email already registered.";
            } elseif ($counts['student_nfc_count'] > 0 || $counts['card_nfc_count'] > 0) {
                $error_message = "NFC Card ID is already registered or invalid. Please use the unique ID provided to you.";
            } else {
                // All checks passed, proceed to insert
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                mysqli_begin_transaction($link);

                try {
                    // Insert into student table
                    $sql_insert_student = "INSERT INTO student (full_name, contact_number, student_email, parent_email, username, nfc_id) VALUES (?, ?, ?, ?, ?, ?)";
                    if ($stmt_insert_student = mysqli_prepare($link, $sql_insert_student)) {
                        mysqli_stmt_bind_param($stmt_insert_student, "ssssss", $full_name_val, $contact_number_val, $student_email_val, $parent_email_val, $username_val, $nfc_id_val);
                        mysqli_stmt_execute($stmt_insert_student);
                        $new_student_id = mysqli_insert_id($link);
                        mysqli_stmt_close($stmt_insert_student);

                        if ($new_student_id) {
                            // Insert into nfc_card table
                            // Assuming new cards start with 0 balance and Active status
                            $initial_balance = 0.00;
                            $status = 'Active';
                            $sql_insert_nfc = "INSERT INTO nfc_card (nfc_id, student_id, current_balance, password_hash, status, last_used) VALUES (?, ?, ?, ?, ?, NOW())";
                            if ($stmt_insert_nfc = mysqli_prepare($link, $sql_insert_nfc)) {
                                mysqli_stmt_bind_param($stmt_insert_nfc, "sidss", $nfc_id_val, $new_student_id, $initial_balance, $password_hash, $status);
                                mysqli_stmt_execute($stmt_insert_nfc);
                                mysqli_stmt_close($stmt_insert_nfc);

                                mysqli_commit($link);
                                $_SESSION['signup_success'] = "Registration successful! You can now log in.";
                                header("Location: login.php"); // Redirect to login page
                                exit();
                            } else { throw new Exception("Error preparing NFC card statement: " . mysqli_error($link)); }
                        } else { throw new Exception("Error creating student record."); }
                    } else { throw new Exception("Error preparing student statement: " . mysqli_error($link)); }
                } catch (Exception $e) {
                    mysqli_rollback($link);
                    $error_message = "Registration failed. Please try again. Error: " . $e->getMessage();
                    error_log("Signup Error: " . $e->getMessage());
                }
            }
        } else {
            $error_message = "Database error during uniqueness check. Please try again.";
            error_log("DB Prepare Error (signup.php - uniqueness check): " . mysqli_error($link));
        }
    }
}

// Define server-relative paths for consistency if needed elsewhere
$signup_url = '/scps/Final_Year_Project/signup.php'; // Or your actual path
$login_url = '/scps/Final_Year_Project/login.php';   // Or your actual path
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign Up</title>
    <?php include './includes/packages.php'; ?>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f3f4f6; /* bg-gray-100 */
            padding-top: 2rem; /* Add padding for scroll */
            padding-bottom: 2rem; /* Add padding for scroll */
        }
        .signup-container {
            width: 100%;
            max-width: 480px; /* Slightly wider for more fields */
        }
        /* Dark mode specific adjustments if needed via Tailwind */
        .dark body { background-color: #1f2937; /* dark:bg-gray-800 equivalent for body if not covered by Tailwind's html.dark */ }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="signup-container bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Create Student Account</h2>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['signup_error_flash'])): // For general errors not field specific ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['signup_error_flash']; ?></span>
            </div>
            <?php unset($_SESSION['signup_error_flash']); ?>
        <?php endif; ?>


        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-4">
                <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo $full_name_val; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number:</label>
                <input type="tel" id="contact_number" name="contact_number" value="<?php echo $contact_number_val; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="student_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student Email:</label>
                <input type="email" id="student_email" name="student_email" value="<?php echo $student_email_val; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="parent_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent Email (Optional):</label>
                <input type="email" id="parent_email" name="parent_email" value="<?php echo $parent_email_val; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo $username_val; ?>" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="nfc_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NFC Card ID:</label>
                <input type="text" id="nfc_id" name="nfc_id" value="<?php echo $nfc_id_val; ?>" required placeholder="Enter the ID on your physical card" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password:</label>
                <input type="password" id="password" name="password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Sign Up
            </button>
        </form>
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account? <a href="<?php echo $login_url; ?>" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>