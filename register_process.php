<?php
/**
 * register.php (POST handler)
 *
 * Handles the final account creation after OTP verification.
 * Called by the register.php frontend via fetch() on Phase 3 submission.
 *
 * Rename this file to register.php OR add a check at the top of your
 * register.php to handle POST requests like this:
 *
 *   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *       // run this code
 *   }
 */

session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET request — show the registration form (handled by register.php frontend)
    exit;
}

// ── Security: must have verified OTP in session ──
if (empty($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    echo json_encode(['error' => 'Email not verified. Please complete OTP verification.']); exit;
}

// ── Collect and sanitize fields ──
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$birthday    = $_POST['birthday'] ?? '';
$address     = trim($_POST['address'] ?? '');
$email       = trim($_POST['email'] ?? '');
$password    = $_POST['password'] ?? '';
$id_type     = trim($_POST['id_type']     ?? '');
$id_skipped  = ($_POST['id_skipped'] ?? '0') === '1';

// ── Validate required fields (residents table: full_name, address, email, birthday, password) ──
if (!$first_name || !$last_name) {
    echo json_encode(['error' => 'First and last name are required.']); exit;
}
if (!$birthday) {
    echo json_encode(['error' => 'Birthday is required.']); exit;
}
if (!$address) {
    echo json_encode(['error' => 'Address is required.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email address.']); exit;
}
if (strlen($password) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters.']); exit;
}

// ── Check email uniqueness (residents.email is UNIQUE, but check first for a clean error message) ──
try {
    $email_esc = $conn->real_escape_string($email);
    $echeck = $conn->query("SELECT id FROM residents WHERE email='$email_esc'")->fetch_assoc();
    if ($echeck) {
        echo json_encode(['error' => 'This email address is already registered.']); exit;
    }

    // ── Hash password ──
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // ── Handle ID photo upload ──
    $id_photo_path = null;
    $id_verification_status = 'unverified'; // matches residents.id_verification_status enum default

    if (!$id_skipped && !empty($_FILES['id_photo']) && $_FILES['id_photo']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        $mime    = mime_content_type($_FILES['id_photo']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['error' => 'Invalid ID file type. Only JPG and PNG are accepted.']); exit;
        }
        if ($_FILES['id_photo']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['error' => 'ID photo is too large. Maximum 5MB.']); exit;
        }

        $upload_dir = 'uploads/ids/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext           = strtolower(pathinfo($_FILES['id_photo']['name'], PATHINFO_EXTENSION));
        $filename      = 'id_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $id_photo_path = $upload_dir . $filename;

        if (!move_uploaded_file($_FILES['id_photo']['tmp_name'], $id_photo_path)) {
            echo json_encode(['error' => 'Failed to upload ID photo. Please try again.']); exit;
        }

        $id_verification_status = 'pending'; // submitted, awaiting admin review
    }

    // ── Full name (for display) ──
    $full_name = trim("$first_name $middle_name $last_name");

    $full_name_esc     = $conn->real_escape_string($full_name);
    $address_esc       = $conn->real_escape_string($address);
    $birthday_esc      = $conn->real_escape_string($birthday);
    $id_type_esc       = $id_type ? $conn->real_escape_string($id_type) : null;
    $id_photo_esc      = $id_photo_path ? $conn->real_escape_string($id_photo_path) : null;
    $status_esc        = $conn->real_escape_string($id_verification_status);

    // ── Insert into residents table ──
    $sql = "INSERT INTO residents
        (full_name, address, email, birthday, password, id_type, id_photo, id_verification_status, created_at)
        VALUES
        ('$full_name_esc', '$address_esc', '$email_esc', '$birthday_esc', '$hashed',
         " . ($id_type_esc ? "'$id_type_esc'" : "NULL") . ",
         " . ($id_photo_esc ? "'$id_photo_esc'" : "NULL") . ",
         '$status_esc', NOW()
        )";

    $conn->query($sql);
    $new_resident_id = $conn->insert_id;

    // ── Notify admin if ID was submitted ──
    // NOTE: this assumes a 'notifications' table shaped like your original draft
    // (complaint_id, complainant_name, subject, message, type, is_read). If your
    // actual notifications table has different columns, this insert will fail —
    // it's wrapped so a failure here won't block account creation.
    if ($id_verification_status === 'pending') {
        try {
            $msg = $conn->real_escape_string(
                "$full_name has registered and submitted a $id_type for identity verification. Please review."
            );
            $fn  = $conn->real_escape_string($full_name);
            $conn->query("INSERT INTO notifications
                (complaint_id, complainant_name, subject, message, type, is_read)
                VALUES (0, '$fn', 'New ID Verification Request', '$msg', 'id_verification', 0)"
            );
        } catch (\Throwable $e) {
            // Silently skip — account was already created successfully above.
            // error_log('Notification insert failed: ' . $e->getMessage());
        }
    }

    // ── Clear OTP session data ──
    unset($_SESSION['otp_verified'], $_SESSION['otp_email']);

    echo json_encode([
        'success'     => true,
        'id_uploaded' => $id_verification_status === 'pending',
    ]);

} catch (\Throwable $e) {
    // TEMPORARY: surfacing the real error message while we debug.
    // Once this is confirmed working, change this back to a generic message.
    echo json_encode(['error' => 'Account creation failed: ' . $e->getMessage()]);
    exit;
}
