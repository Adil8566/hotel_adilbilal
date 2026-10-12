<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
header('Location: ../pemesanan/edit.php?id=' . $id);
exit;
?>
