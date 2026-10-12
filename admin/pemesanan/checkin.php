<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: pemesanan.php'); exit; }
$q = mysqli_query($con, "SELECT id_kamar, status, check_in, check_out FROM tbl_booking WHERE id_booking={$id} LIMIT 1");
$b = $q ? mysqli_fetch_assoc($q) : null;
if (!$b || strtolower(trim($b['status'])) !== 'dikonfirmasi') { header('Location: pemesanan.php'); exit; }
$today = date('Y-m-d');
if ($b['check_in'] > $today || $b['check_out'] <= $today) { header('Location: pemesanan.php?pesan=checkin_tidak_valid'); exit; }
$room = mysqli_query($con, "SELECT status FROM tbl_kamar WHERE id_kamar=".(int)$b['id_kamar']." LIMIT 1");
$rd = $room ? mysqli_fetch_assoc($room) : null;
if (!$rd || strtolower(trim($rd['status'])) === 'maintenance') { header('Location: pemesanan.php?pesan=kamar_tidak_tersedia'); exit; }
mysqli_query($con, "UPDATE tbl_booking SET status='checkin' WHERE id_booking={$id}");
sync_single_room_status($con, (int)$b['id_kamar']);
header('Location: pemesanan.php?pesan=checkin'); exit;
?>