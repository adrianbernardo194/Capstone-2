<?php
/**
 * send_otp.php
 * Handles four actions:
 *   action=send_otp         — registration: generates OTP, sends via email (email must NOT already exist)
 *   action=verify_otp       — registration: checks entered OTP vs session
 *   action=send_reset_otp   — forgot password: generates OTP, sends via email.
 *                              Returns a specific error if the email isn't registered
 *                              (chosen deliberately for easier support/testing —
 *                              this does mean the endpoint reveals which emails exist)
 *   action=verify_reset_otp — forgot password: checks entered OTP vs session
 */

session_start();
include 'db.php';
header('Content-Type: application/json');

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── SMTP config (Hostinger email account) ──────────────────────────────────────
define('SMTP_HOST',     'smtp.hostinger.com');                    // confirmed correct — universal Hostinger SMTP host
define('SMTP_PORT',     465);                                     // 465 = SSL (try this first)
define('SMTP_SECURE',   PHPMailer::ENCRYPTION_SMTPS);              // if 465 fails, switch to 587 + ENCRYPTION_STARTTLS
define('SMTP_USERNAME', 'thesiscapstone@sanroque-marikina.click'); // your mailbox
define('SMTP_PASSWORD', 'PASTE_YOUR_MAILBOX_PASSWORD_HERE');       // ← fill this in directly on the server, don't share it in chat
define('SMTP_FROM_NAME','Barangay San Roque');
// ─────────────────────────────────────────────────────────────────────────────

$action = $_POST['action'] ?? '';

/**
 * Shared helper — sends a 6-digit code to an email via SMTP.
 * Returns true/false for send success; throws nothing (catches internally).
 */
function sendOtpEmail(string $email, string $otp, string $subjectLine, string $bodyIntro): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subjectLine;
        $mail->Body    = "$bodyIntro <b>$otp</b><br>Valid for 5 minutes.";
        $mail->AltBody = "$bodyIntro $otp. Valid for 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// SEND OTP (registration — email must NOT already exist)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'send_otp') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']); exit;
    }

    $email_esc = $conn->real_escape_string($email);
    $existing  = $conn->query("SELECT id FROM residents WHERE email='$email_esc'")->fetch_assoc();
    if ($existing) {
        echo json_encode(['error' => 'This email address is already registered.']); exit;
    }

    $otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $_SESSION['otp_code']    = $otp;
    $_SESSION['otp_expires'] = $expires;
    $_SESSION['otp_email']   = $email;

    $sent = sendOtpEmail(
        $email, $otp,
        'Your verification code',
        'Your Barangay San Roque verification code is:'
    );

    if ($sent) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Could not send verification email. Please check the address and try again.']);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// VERIFY OTP (registration)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'verify_otp') {

    $entered = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    if (empty($_SESSION['otp_code']) || empty($_SESSION['otp_email'])) {
        echo json_encode(['error' => 'No OTP found. Please request a new code.']); exit;
    }
    if (strcasecmp($_SESSION['otp_email'], $email) !== 0) {
        echo json_encode(['error' => 'Email address mismatch.']); exit;
    }
    if (date('Y-m-d H:i:s') > $_SESSION['otp_expires']) {
        unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_email']);
        echo json_encode(['error' => 'OTP has expired. Please request a new code.']); exit;
    }
    if ($entered !== $_SESSION['otp_code']) {
        echo json_encode(['error' => 'Incorrect OTP. Please check and try again.']); exit;
    }

    $_SESSION['otp_verified'] = true;
    unset($_SESSION['otp_code'], $_SESSION['otp_expires']);

    echo json_encode(['success' => true]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// SEND RESET OTP (forgot password — shows a specific error if the email isn't registered)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'send_reset_otp') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']); exit;
    }

    $email_esc = $conn->real_escape_string($email);
    $existing  = $conn->query("SELECT id FROM residents WHERE email='$email_esc'")->fetch_assoc();

    if (!$existing) {
        echo json_encode(['error' => 'No account found with that email address.']); exit;
    }

    $otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $_SESSION['reset_otp_code']    = $otp;
    $_SESSION['reset_otp_expires'] = $expires;
    $_SESSION['reset_otp_email']   = $email;

    $sent = sendOtpEmail(
        $email, $otp,
        'Password reset code',
        "Your Barangay San Roque password reset code is:"
    );

    if ($sent) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Could not send reset email. Please try again.']);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// VERIFY RESET OTP (forgot password)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'verify_reset_otp') {

    $entered = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    if (empty($_SESSION['reset_otp_code']) || empty($_SESSION['reset_otp_email'])) {
        echo json_encode(['error' => 'No reset code found. Please request a new one.']); exit;
    }
    if (strcasecmp($_SESSION['reset_otp_email'], $email) !== 0) {
        echo json_encode(['error' => 'Email address mismatch.']); exit;
    }
    if (date('Y-m-d H:i:s') > $_SESSION['reset_otp_expires']) {
        unset($_SESSION['reset_otp_code'], $_SESSION['reset_otp_expires'], $_SESSION['reset_otp_email']);
        echo json_encode(['error' => 'Code has expired. Please request a new one.']); exit;
    }
    if ($entered !== $_SESSION['reset_otp_code']) {
        echo json_encode(['error' => 'Incorrect code. Please check and try again.']); exit;
    }

    $_SESSION['reset_otp_verified'] = true;
    unset($_SESSION['reset_otp_code'], $_SESSION['reset_otp_expires']);

    echo json_encode(['success' => true]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// No matching action
// ══════════════════════════════════════════════════════════════════════════════
echo json_encode(['error' => 'Invalid action.']);
