<?php
// admin/includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login dan role-nya adalah admin
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}
