<?php
session_start();

// Validasi sesi admin
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';

// Wajib mengaktifkan mode Exception agar blok try-catch MySQLi bisa berfungsi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validasi parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// PERBAIKAN: Ubah variabel menjadi id_tipe
$id_tipe = (int) $_GET['id'];

try {
    // PERBAIKAN: Eksekusi penghapusan pada tabel tbl_tipe_kamar dan kolom id_tipe
    mysqli_query($con, "DELETE FROM tbl_tipe_kamar WHERE id_tipe = '$id_tipe'");
    
    // Cek apakah benar-benar ada data yang terhapus (lebih dari 0 baris)
    if (mysqli_affected_rows($con) > 0) {
        header('Location: index.php?pesan=hapus');
    } else {
        // Jika query jalan tapi data tidak ada/sudah terhapus sebelumnya
        header('Location: index.php?pesan=gagal'); 
    }
    exit;

} catch (mysqli_sql_exception $e) {
    // Jika gagal karena constraint (Error 1451 - tipe kamar masih dipakai di data kamar)
    if ($e->getCode() == 1451) {
        header('Location: index.php?pesan=gagal_terpakai');
    } else {
        header('Location: index.php?pesan=gagal');
    }
    exit;
}
?>