<?php
// Generate password hash for "1"
$password = "1";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "\n";
echo "SQL to update all users:\n";
echo "UPDATE vartotojai SET slaptazodis = '" . $hash . "';\n";
?>
