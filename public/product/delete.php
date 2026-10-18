<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses ditolak!'); window.location='index.php';</script>";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        echo "<script>alert('Produk berhasil dihapus!'); window.location='index.php';</script>";
        exit;
    } catch (\PDOException $e) {
        echo "<script>alert('Gagal menghapus produk karena sudah memiliki riwayat transaksi!'); window.location='index.php';</script>";
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}