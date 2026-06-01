<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$role     = $_POST['role']     ?? '';   // 'admin' or 'resident'
$email    = trim($_POST['email']    ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($role === 'admin') {
    if (!$username || !$password) {
        echo json_encode(['error' => 'Username and password are required.']); exit;
    }
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if (!$admin || !password_verify($password, $admin['password'])) {
        echo json_encode(['error' => 'Invalid username or password.']); exit;
    }

    $_SESSION['user_id']   = $admin['id'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_name'] = $admin['username'];

    // Audit log — login
    include_once 'audit.php';
    log_audit($conn, 'LOGIN', 'Authentication',
        "Admin '{$admin['username']}' logged in successfully.",
        $admin['id'], $admin['username'], null, null);

    echo json_encode(['success' => true, 'redirect' => 'admin-dashboard.php']);

} elseif ($role === 'resident') {
    if (!$email || !$password) {
        echo json_encode(['error' => 'Email and password are required.']); exit;
    }
    $stmt = $conn->prepare("SELECT * FROM residents WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resident = $stmt->get_result()->fetch_assoc();

    if (!$resident || !password_verify($password, $resident['password'])) {
        echo json_encode(['error' => 'Invalid email or password.']); exit;
    }

    $_SESSION['user_id']      = $resident['id'];
    $_SESSION['user_role']    = 'resident';
    $_SESSION['user_name']    = $resident['full_name'];
    $_SESSION['user_email']   = $resident['email'];
    echo json_encode(['success' => true, 'redirect' => 'resident-portal.php']);

} else {
    echo json_encode(['error' => 'Invalid role.']);
}
?>
