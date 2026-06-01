<?php
// seed_admin.php
// Open http://localhost/thesis/seed_admin.php ONCE, then DELETE this file.

include 'db.php';

$username = 'admin';
$password = '123';
$hash = password_hash($password, PASSWORD_BCRYPT);

// Remove old admin if any, then insert fresh
$conn->query("DELETE FROM admins WHERE username='admin'");
$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "<h2 style='color:green;font-family:monospace;padding:20px;'>
        ✅ Admin account seeded successfully!<br>
        Username: <b>admin</b><br>
        Password: <b>123</b><br><br>
        <span style='color:red;'>⚠️ Delete this file now: seed_admin.php</span>
    </h2>";
} else {
    echo "<h2 style='color:red;font-family:monospace;padding:20px;'>❌ Error: " . $stmt->error . "</h2>";
}
?>
