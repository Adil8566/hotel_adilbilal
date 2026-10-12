<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: pemesanan.php'); exit; }
$q = mysqli_query($con, "SELECT id_kamar, status FROM tbl_booking WHERE id_booking={$id} LIMIT 1");
$b = $q ? mysqli_fetch_assoc($q) : null;
if (!$b || strtolower(trim($b['status'])) !== 'checkin') { header('Location: pemesanan.php'); exit; }
mysqli_query($con, "UPDATE tbl_booking SET status='checkout' WHERE id_booking={$id}");
sync_single_room_status($con, (int)$b['id_kamar']);
header('Location: pemesanan.php?pesan=checkout'); exit;
?>