<?php
// config/functions.php
session_start();
require_once __DIR__ . '/koneksi.php';

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /frontend/login.php');
        exit;
    }
}

function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        header('Location: /frontend/login.php');
        exit;
    }
}
?>
