<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    header('Location: login.php'); exit;
}
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resident_id = (int)$_SESSION['user_id'];
    $subject     = mysqli_real_escape_string($conn, $_POST['subject']);
    $date        = mysqli_real_escape_string($conn, $_POST['complaint_date']);
    $c_name      = mysqli_real_escape_string($conn, $_POST['complainant_name']);
    $c_addr      = mysqli_real_escape_string($conn, $_POST['complainant_address']);
    $c_cont      = mysqli_real_escape_string($conn, $_POST['complainant_contact']);
    $r_name      = mysqli_real_escape_string($conn, $_POST['respondent_name']);
    $r_addr      = mysqli_real_escape_string($conn, $_POST['respondent_address']);
    $narrative   = mysqli_real_escape_string($conn, $_POST['narrative']);

    // Multi-file upload
    $uploaded_names  = [];
    $max_total_bytes = 25 * 1024 * 1024;
    $total_size      = 0;
    $target_dir      = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['evidence']['name'][0])) {
        $file_count = count($_FILES['evidence']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $total_size += $_FILES['evidence']['size'][$i];
            if ($total_size > $max_total_bytes) break;
            $ext         = strtolower(pathinfo($_FILES['evidence']['name'][$i], PATHINFO_EXTENSION));
            $unique_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'][$i], $target_dir . $unique_name)) {
                $uploaded_names[] = $unique_name;
            }
        }
    }

    $evidence_pic = mysqli_real_escape_string($conn, implode(',', $uploaded_names));

    $sql = "INSERT INTO complaints
            (resident_id, subject, date_filed, complainant_name, complainant_address,
             complainant_contact, respondent_name, respondent_address, narrative, evidence_pic, status)
            VALUES
            ($resident_id, '$subject','$date','$c_name','$c_addr','$c_cont',
             '$r_name','$r_addr','$narrative','$evidence_pic','Pending')";

    if ($conn->query($sql) === TRUE) {
        $new_id = $conn->insert_id;

        // Admin notification
        $notif_msg = mysqli_real_escape_string($conn,
            "A new complaint has been filed regarding \"$subject\" by $c_name.");
        $conn->query("INSERT INTO notifications
                      (complaint_id, complainant_name, subject, message, type, is_read)
                      VALUES ($new_id,'$c_name','$subject','$notif_msg','new_complaint',0)");

        header("Location: complaint-form.php?success=1&id=" . $new_id);
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
