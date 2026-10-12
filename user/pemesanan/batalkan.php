<?php
require_once '../auth.php';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header('Location: pemesanan.php'); exit; }
$q = mysqli_query($con, "SELECT id_kamar,status FROM tbl_booking WHERE id_booking={$id} AND id_pengguna={$id_user} LIMIT 1");
$b = $q ? mysqli_fetch_assoc($q) : null;
if (!$b) { header('Location: pemesanan.php'); exit; }
if (!in_array(strtolower(trim($b['status'])), ['pending','dikonfirmasi'], true)) { header('Location: detail.php?id='.$id); exit; }
$stmt=mysqli_prepare($con,"UPDATE tbl_booking SET status='dibatalkan' WHERE id_booking=? AND id_pengguna=? AND LOWER(TRIM(status)) IN ('pending','dikonfirmasi')");
mysqli_stmt_bind_param($stmt,'ii',$id,$id_user); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
sync_single_room_status($con,(int)$b['id_kamar']);
header('Location: pemesanan.php?pesan=batal'); exit;
