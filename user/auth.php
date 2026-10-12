<?php
session_start();

if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'user') {
    // Path dihitung dari URL agar redirect benar juga dari sub-folder (user/pemesanan, dst.)
    $script_auth = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos_auth = strpos($script_auth, '/user/');
    $root_auth = $pos_auth !== false ? substr($script_auth, 0, $pos_auth) : '';
    header('Location: ' . $root_auth . '/login.php?error=3');
    exit;
}

require_once(__DIR__ . '/../database/koneksi.php');
require_once(__DIR__ . '/../database/fungsi_hotel.php');
normalize_hotel_statuses($con);
sync_room_statuses($con); // status kamar mengikuti data admin (sama seperti halaman admin)

$id_user = (int)$_SESSION['id_pengguna'];
$nama_user = (string)($_SESSION['nama'] ?? 'User');

// Perhitungan total harga: sama persis dengan admin/pemesanan/tambah.php
// (Weekday Senin-Kamis = harga dasar, Weekend Jumat-Minggu = +20%, Day Rate = 50%,
//  Early Bird >= 30 hari = -15%, Last Minute <= 1 hari = -10%).
if (!function_exists('hitung_total_menginap')) {
    function hitung_total_menginap(float $harga_dasar, DateTime $in, DateTime $out): float
    {
        $harga_weekday = $harga_dasar;
        $harga_weekend = $harga_dasar * 1.20;
        $harga_day_rate = $harga_dasar * 0.50;

        $jumlah_malam = (int)$in->diff($out)->days;
        $total_harga = 0;

        if ($jumlah_malam === 0) {
            $total_harga = $harga_day_rate;
        } else {
            $curr_date = clone $in;
            for ($i = 0; $i < $jumlah_malam; $i++) {
                $hari = (int)$curr_date->format('N');
                $total_harga += ($hari >= 1 && $hari <= 4) ? $harga_weekday : $harga_weekend;
                $curr_date->modify('+1 day');
            }
        }

        $tgl_booking = new DateTime(date('Y-m-d'));
        $selisih_pesan = (int)$tgl_booking->diff(clone $in)->days;
        $diskon = 0;
        if ($selisih_pesan >= 30) {
            $diskon = 0.15;
        } elseif ($selisih_pesan <= 1) {
            $diskon = 0.10;
        }

        return $total_harga - ($total_harga * $diskon);
    }
}
