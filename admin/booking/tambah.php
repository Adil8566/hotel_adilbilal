<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
$id_tipe = (int)($_GET['id_tipe'] ?? $_POST['id_tipe'] ?? 0);
header('Location: ../pemesanan/tambah.php?id_tipe=' . $id_tipe);
exit;
?>
