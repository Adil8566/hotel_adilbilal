<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
header('Location: ../pemesanan/cetak.php?id=' . $id);
exit;
?>
