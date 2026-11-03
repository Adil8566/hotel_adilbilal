<?php
session_start();

// Cek akses admin
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';

if (isset($_GET['id'])) {
    $id_booking = (int)$_GET['id'];
    
    // Update status menjadi 'pending'
    $query = "UPDATE tbl_booking SET status = 'pending' WHERE id_booking = $id_booking";
    
    if (mysqli_query($con, $query)) {
        // Redirect kembali ke halaman pemesanan dengan pesan sukses
        // (Menggunakan pesan=edit karena alert "Pemesanan berhasil diubah" sudah tersedia di pemesanan.php)
        header('Location: pemesanan.php?pesan=edit');
        exit;
    } else {
        echo "<script>
                alert('Gagal mengubah status pemesanan: " . mysqli_error($con) . "');
                window.location.href = 'pemesanan.php';
              </script>";
        exit;
    }
} else {
    header('Location: pemesanan.php');
    exit;
}
?>