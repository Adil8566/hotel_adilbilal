<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: pemesanan.php'); exit; }
$q = mysqli_query($con, "SELECT id_kamar FROM tbl_booking WHERE id_booking={$id} LIMIT 1");
$b = $q ? mysqli_fetch_assoc($q) : null;
if (!$b) { header('Location: pemesanan.php'); exit; }
$id_kamar = (int)$b['id_kamar'];
@mysqli_query($con, "DELETE FROM tbl_pembayaran WHERE id_booking={$id}");
$ok = mysqli_query($con, "DELETE FROM tbl_booking WHERE id_booking={$id}");
if ($ok) { sync_single_room_status($con, $id_kamar); header('Location: pemesanan.php?pesan=hapus'); exit; }
echo 'Booking gagal dihapus: ' . e(mysqli_error($con));
?>