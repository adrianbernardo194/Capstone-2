<?php
$servername = "localhost";
$username = "u894474334_usr_wEiUvio2";
$password = "!C4PSTONe_";
$dbname = "u894474334_barangay_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>