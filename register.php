<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$name     = trim($_POST['full_name']   ?? '');
$address  = trim($_POST['address']     ?? '');
$email    = trim($_POST['email']       ?? '');
$birthday = trim($_POST['birthday']    ?? '');
$pass     = $_POST['password']         ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Validate
if (!$name || !$address || !$email || !$birthday || !$pass || !$confirm) {
    echo json_encode(['error' => 'All fields are required.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email address.']); exit;
}
if (strlen($pass) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
}
if ($pass !== $confirm) {
    echo json_encode(['error' => 'Passwords do not match.']); exit;
}

// Check duplicate email
$stmt = $conn->prepare("SELECT id FROM residents WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'An account with this email already exists.']); exit;
}

// Insert
$hash = password_hash($pass, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO residents (full_name, address, email, birthday, password) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $name, $address, $email, $birthday, $hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
} else {
    echo json_encode(['error' => 'Registration failed. Please try again.']);
}
?>
