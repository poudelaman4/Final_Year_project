<?php
// admin/profile.php - Admin Profile Display Page (View Only)

date_default_timezone_set('Asia/Kathmandu');
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$current_admin_id = $_SESSION['admin_id'];

// Include database connection & packages
require_once '../includes/db_connection.php';
include '../includes/packages.php';

// Initialize variables
$full_name_db = "N/A";
$username_db = "N/A";
$role_db = "N/A";
$last_login_db = "N/A";
$created_at_db = "N/A";
$error_message = "";

// --- FETCH CURRENT ADMIN'S DATA ---
$sqlFetchAdmin = "SELECT full_name, username, role, last_login, created_at FROM staff WHERE staff_id = ?";
if ($stmtFetch = mysqli_prepare($link, $sqlFetchAdmin)) {
    mysqli_stmt_bind_param($stmtFetch, "i", $current_admin_id);
    if (mysqli_stmt_execute($stmtFetch)) {
        $result = mysqli_stmt_get_result($stmtFetch);
        if ($admin_data = mysqli_fetch_assoc($result)) {
            $full_name_db = htmlspecialchars($admin_data['full_name']);
            $username_db = htmlspecialchars($admin_data['username']);
            $role_db = htmlspecialchars(ucfirst(str_replace('_', ' ', $admin_data['role']))); // Format role nicely
            // Format dates (optional, adjust format as needed)
            $last_login_db = $admin_data['last_login'] ? date("d M Y, h:i A", strtotime($admin_data['last_login'])) : 'Never';
            $created_at_db = $admin_data['created_at'] ? date("d M Y", strtotime($admin_data['created_at'])) : 'N/A';
        } else {
            $error_message = "Could not retrieve your profile information.";
        }
    } else {
        $error_message = "Error fetching profile: " . mysqli_stmt_error($stmtFetch);
        error_log("DB Execute Error (profile.php - view): " . mysqli_stmt_error($stmtFetch));
    }
    mysqli_stmt_close($stmtFetch);
} else {
    $error_message = "Database error preparing to fetch profile: " . mysqli_error($link);
    error_log("DB Prepare Error (profile.php - view): " . mysqli_error($link));
}

// Include Header
include '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en" class="<?php // TODO: Add theme class based on user preference if header doesn't handle it ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <style>
        /* Replace with Tailwind */
        .profile-view-container { max-width: 600px; margin: 2rem auto; padding: 2rem; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .profile-detail { margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .profile-detail label { font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem; }
        .profile-detail span { color: #1f2937; font-size: 1.1rem; }
        .dark .profile-view-container { background-color: #1f2937; }
        .dark .profile-detail { border-bottom-color: #4b5563; }
        .dark .profile-detail label { color: #d1d5db; }
        .dark .profile-detail span { color: #e5e7eb; }
        .edit-link a { color: #4f46e5; text-decoration: none; }
        .edit-link a:hover { text-decoration: underline; }
        .dark .edit-link a { color: #818cf8; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans">

    <div class="profile-view-container">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">My Profile</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php else: ?>
            <div class="profile-detail">
                <label>Full Name:</label>
                <span><?php echo $full_name_db; ?></span>
            </div>
            <div class="profile-detail">
                <label>Username:</label>
                <span><?php echo $username_db; ?></span>
            </div>
            <div class="profile-detail">
                <label>Role:</label>
                <span><?php echo $role_db; ?></span>
            </div>
             <div class="profile-detail">
                <label>Account Created:</label>
                <span><?php echo $created_at_db; ?></span>
            </div>
             <div class="profile-detail">
                <label>Last Login:</label>
                <span><?php echo $last_login_db; ?></span>
            </div>
            <div class="mt-6 text-center edit-link">
                <a href="settings.php">Edit Profile & Settings</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <?php if (isset($link)) mysqli_close($link); ?>
</body>
</html>