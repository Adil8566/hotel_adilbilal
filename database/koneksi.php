<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "hotel";

$con = mysqli_connect($host, $user, $password, $database);

if (!$con) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8mb4");

// Menghubungkan booking yang dibuat dari portal user dengan akun pemesan.
// Kolom dibuat otomatis agar database lama tetap bisa dipakai tanpa impor ulang.
$cek_kolom_user_booking = @mysqli_query($con, "
    SELECT COUNT(*) AS total
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_booking'
      AND COLUMN_NAME = 'id_pengguna'
");
$kolom_user_booking_ada = false;
if ($cek_kolom_user_booking) {
    $row_kolom = mysqli_fetch_assoc($cek_kolom_user_booking);
    $kolom_user_booking_ada = (int)($row_kolom['total'] ?? 0) > 0;
}
if (!$kolom_user_booking_ada) {
    @mysqli_query($con, "ALTER TABLE tbl_booking ADD COLUMN id_pengguna INT(11) NULL AFTER id_tamu");
    @mysqli_query($con, "ALTER TABLE tbl_booking ADD KEY id_pengguna (id_pengguna)");
}

/*
 * Fallback ringan untuk instalasi baru.
 * Struktur mengikuti database hotel yang diberikan pengguna,
 * khususnya tabel tbl_pembayaran: tanggal_bayar, metode, status.
 */
@mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS tbl_pembayaran (
        id_pembayaran INT(11) NOT NULL AUTO_INCREMENT,
        id_booking INT(11) NOT NULL,
        tanggal_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
        jumlah_bayar DECIMAL(12,2) NOT NULL,
        metode VARCHAR(50) DEFAULT NULL,
        status ENUM('belum_lunas','lunas') DEFAULT 'belum_lunas',
        PRIMARY KEY (id_pembayaran),
        KEY id_booking (id_booking)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Kolom password dibuat longgar agar mendukung password_hash() maupun hash lama SHA1.
@mysqli_query($con, "ALTER TABLE tbl_pengguna MODIFY password VARCHAR(255) NOT NULL");
?>
