<?php
session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../../login.php');
    exit;
}

if (($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id_tipe = (int) $_GET['id'];

// Hapus data tipe kamar yang benar.
$query = mysqli_query($con, "
    DELETE FROM tbl_tipe_kamar
    WHERE id_tipe = '$id_tipe'
");

if ($query) {
    header('Location: index.php?pesan=hapus');
    exit;
}

// Biasanya gagal jika tipe kamar masih dipakai oleh data kamar.
echo 'Data gagal dihapus: ' . htmlspecialchars(mysqli_error($con), ENT_QUOTES, 'UTF-8');
?>
