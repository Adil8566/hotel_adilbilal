<?php
require_once '../auth.php';
$pid=(int)($_GET['id']??0);$booking=(int)($_GET['booking']??0);
if($pid<=0||$booking<=0){header('Location: ../pemesanan/pemesanan.php');exit;}
$q=mysqli_query($con,"SELECT b.status,p.jumlah_bayar FROM tbl_pembayaran p INNER JOIN tbl_booking b ON p.id_booking=b.id_booking WHERE p.id_pembayaran={$pid} AND p.id_booking={$booking} AND b.id_pengguna={$id_user} LIMIT 1");
$row=$q?mysqli_fetch_assoc($q):null;
if($row && in_array(strtolower(trim($row['status'])),['pending','dikonfirmasi'],true)) mysqli_query($con,"DELETE FROM tbl_pembayaran WHERE id_pembayaran={$pid} AND id_booking={$booking}");
header('Location: pembayaran.php?id='.$booking);exit;
