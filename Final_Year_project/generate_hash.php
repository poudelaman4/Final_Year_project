<?php
// --- TEMPORARY PASSWORD HASHER ---
// Use this file *once* to get a hash for a test password.
// DELETE THIS FILE IMMEDIATELY AFTER USE FOR SECURITY.

$test_password = "alice"; // <-- *** CHANGE THIS to a NEW, SIMPLE password for testing! ***

$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);

echo "The password hash for '" . htmlspecialchars($test_password) . "' is:<br>";
echo "<strong>" . htmlspecialchars($hashed_password) . "</strong><br><br>";
echo "Copy the hash (the long string) and paste it into the 'password_hash' field in phpMyAdmin.";

// REMEMBER TO DELETE THIS FILE AFTER YOU HAVE THE HASH!
?>