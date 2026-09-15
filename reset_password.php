<?php
/**
 * reset_password.php
 * Final step of the forgot-password flow: updates the resident's password.
 * Only allowed if send_otp.php's verify_reset_otp action already set
 * $_SESSION['reset_otp_verified'] = true for this email.
 */

session_start();
include 'db.php';
header('Content-Type: application/json');

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// ── Security: must have verified the reset OTP in this session ──
if (empty($_SESSION['reset_otp_verified']) || $_SESSION['reset_otp_verified'] !== true) {
    echo json_encode(['error' => 'Please verify your email with the code first.']); exit;
}
if (empty($_SESSION['reset_otp_email']) || strcasecmp($_SESSION['reset_otp_email'], $email) !== 0) {
    echo json_encode(['error' => 'Email mismatch. Please restart the reset process.']); exit;
}
if (strlen($password) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters.']); exit;
}

try {
    $hashed    = password_hash($password, PASSWORD_BCRYPT);
    $email_esc = $conn->real_escape_string($email);
    $hash_esc  = $conn->real_escape_string($hashed);

    $conn->query("UPDATE residents SET password='$hash_esc' WHERE email='$email_esc'");

    if ($conn->affected_rows === 0) {
        // Either the email doesn't exist (shouldn't happen at this point, since
        // send_reset_otp only emails a code to existing accounts) or the password
        // was already identical to the new one — treat both as success either way
        // so we don't leak account-existence info this late in the flow either.
    }

    // Clear reset session state
    unset($_SESSION['reset_otp_verified'], $_SESSION['reset_otp_email']);

    echo json_encode(['success' => true]);
} catch (\Throwable $e) {
    echo json_encode(['error' => 'Could not reset password. Please try again.']);
}
