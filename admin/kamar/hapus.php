<?php

session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION['peran'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../database/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kamar = (int) $_GET['id'];

$query = mysqli_query($con, "
    DELETE FROM tbl_kamar
    WHERE id_kamar = '$id_kamar'
");

if ($query) {

    header("Location: index.php?pesan=hapus");
    exit;

} else {

    echo "Data gagal dihapus: " . mysqli_error($con);
}

?>