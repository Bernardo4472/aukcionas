<?php
/**
 * Test script to verify database connection and password hashes
 */

require_once 'includes/db.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Database connected successfully<br><br>";

// Check if users exist
$result = $conn->query("SELECT id, vardas, el_pastas, role FROM vartotojai ORDER BY id");

echo "<h3>Users in database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['vardas']}</td>";
    echo "<td>{$row['el_pastas']}</td>";
    echo "<td>{$row['role']}</td>";
    echo "</tr>";
}
echo "</table><br>";

// Test password verification for admin
echo "<h3>Password Hash Test:</h3>";

$stmt = $conn->prepare("SELECT slaptazodis FROM vartotojai WHERE el_pastas = ?");
$email = 'admin@ktu.lt';
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hash = $user['slaptazodis'];

    echo "Testing admin@ktu.lt with password 'admin123':<br>";
    echo "Hash in database: <code>" . htmlspecialchars($hash) . "</code><br>";

    if (password_verify('admin123', $hash)) {
        echo "✅ Password verification: <strong style='color: green;'>SUCCESS</strong><br>";
    } else {
        echo "❌ Password verification: <strong style='color: red;'>FAILED</strong><br>";
    }
} else {
    echo "❌ User admin@ktu.lt not found in database<br>";
}

echo "<br><h3>Expected Hashes:</h3>";
echo "admin123 should hash to: <code>\$2y\$12\$3HZY.Pt69poo3EgJiuH6cOC5lXpLUItrtrlQi7VMnW0eoNtgBsV8e</code><br>";
echo "acc123 should hash to: <code>\$2y\$12\$yAy1HeloS29BYAjeBwDmSO7R2wr4r/cCmXcYMymldpw8IsZaZ7O2S</code><br>";
echo "user123 should hash to: <code>\$2y\$12\$NStVAgYOOSDZ3R1xhH0j5e.U26fyVK42Dy9.k1rsehZeK2QlImxK6</code><br>";

echo "<br><h3>Instructions:</h3>";
echo "If the hash in database doesn't match the expected hash above, you need to:<br>";
echo "1. Go to phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
echo "2. Drop the 'aukcionas' database<br>";
echo "3. Create a new 'aukcionas' database<br>";
echo "4. Import the updated database.sql file<br>";
?>
