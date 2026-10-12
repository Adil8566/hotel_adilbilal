<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
$id = (int)($_GET['id'] ?? 0);
$booking = (int)($_GET['booking'] ?? 0);
if ($id > 0) mysqli_query($con, "DELETE FROM tbl_pembayaran WHERE id_pembayaran={$id}");
header('Location: pembayaran.php?id='.$booking); exit;
?>