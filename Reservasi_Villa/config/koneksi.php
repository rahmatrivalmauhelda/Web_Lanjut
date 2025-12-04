<?php
// config/koneksi.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'reservasi_villa';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_errno) {
    die('Failed to connect to MySQL: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>
