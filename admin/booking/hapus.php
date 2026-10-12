<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
header('Location: ../pemesanan/hapus.php?id=' . $id);
exit;
?>
