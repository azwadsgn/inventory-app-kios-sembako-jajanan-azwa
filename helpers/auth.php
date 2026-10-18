<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /inventory-app/public/login.php");
        exit;
    }
}

function check_admin() {
    check_auth();
    if ($_SESSION['role'] !== 'admin') {
        echo "<script>alert('Akses ditolak! Halaman ini khusus admin.'); window.location='/inventory-app/public/dashboard.php';</script>";
        exit;
    }
}
?>