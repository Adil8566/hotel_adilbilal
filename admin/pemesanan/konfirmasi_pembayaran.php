<?php
session_start();

// Validasi sesi admin
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';

// Pastikan data dikirim melalui method POST
if (isset($_POST['id'])) {
    $id_booking = (int)$_POST['id'];

    // Update status di tabel booking menjadi 'pending'
    $query = "UPDATE tbl_booking SET status = 'pending' WHERE id_booking = $id_booking";
    $update = mysqli_query($con, $query);

    if ($update) {
        // Redirect kembali ke halaman pemesanan dengan pesan alert sukses
        // (Menggunakan parameter ?pesan=verifikasi yang sudah ada di pemesanan.php)
        header('Location: pemesanan.php?pesan=verifikasi');
        exit;
    } else {
        echo "<script>alert('Gagal mengonfirmasi pembayaran: " . mysqli_error($con) . "'); window.location='pemesanan.php';</script>";
    }
} else {
    // Jika file diakses langsung tanpa menekan tombol
    header('Location: pemesanan.php');
    exit;
}