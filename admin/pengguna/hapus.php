<?php
session_start(); if(!isset($_SESSION['id_pengguna'])||($_SESSION['peran']??'')!=='admin'){header('Location: ../../login.php');exit;} require_once '../../database/koneksi.php'; $id=(int)($_GET['id']??0);if($id>0&&(int)$id!==(int)$_SESSION['id_pengguna'])mysqli_query($con,"DELETE FROM tbl_pengguna WHERE id_pengguna={$id}");header('Location:index.php?pesan=hapus');exit;
?>