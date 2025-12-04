<?php
session_start();

if (isset($_SESSION['role'])) {

    // Jika admin
    if ($_SESSION['role'] === 'admin') {
        header("Location: backend/index.php");
        exit;
    }

    // Jika customer
    if ($_SESSION['role'] === 'customer') {
        header("Location: frontend/list_villas.php");
        exit;
    }
}

// Jika belum login → redirect ke login
header("Location: frontend/login.php");
exit;
?>
