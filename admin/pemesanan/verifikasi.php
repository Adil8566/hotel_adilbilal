<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: pemesanan.php'); exit; }
$stmt = mysqli_prepare($con, "UPDATE tbl_booking SET status='dikonfirmasi' WHERE id_booking=? AND LOWER(TRIM(status))='pending'");
mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
header('Location: pemesanan.php?pesan=verifikasi'); exit;
?>