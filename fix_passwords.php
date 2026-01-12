<?php
/**
 * Fix passwords script - run this on the server
 * Usage: php fix_passwords.php
 */

require_once 'includes/db.php';

$password = "1";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Generated hash for password '1': \n";
echo $hash . "\n\n";

// Update all users
$stmt = $conn->prepare("UPDATE vartotojai SET slaptazodis = ?");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "SUCCESS! All passwords updated to '1'\n";
    echo "Affected rows: " . $stmt->affected_rows . "\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

$conn->close();
?>
