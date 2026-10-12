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
require_once 'SimplePDF.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID booking tidak valid.');
}

$id_booking = (int) $_GET['id'];

$query = mysqli_query($con, "
    SELECT
        b.id_booking,
        b.kode_booking,
        b.check_in,
        b.check_out,
        b.jumlah_tamu,
        b.total,
        b.status,
        t.nama,
        t.no_identitas,
        t.no_hp,
        t.email,
        t.alamat,
        k.nomor_kamar,
        tk.nama_tipe,
        tk.harga
    FROM tbl_booking b
    INNER JOIN tbl_tamu t ON b.id_tamu = t.id_tamu
    INNER JOIN tbl_kamar k ON b.id_kamar = k.id_kamar
    INNER JOIN tbl_tipe_kamar tk ON k.id_tipe = tk.id_tipe
    WHERE b.id_booking = '$id_booking'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) === 0) {
    die('Data booking tidak ditemukan.');
}

$data = mysqli_fetch_assoc($query);

$paymentQuery = mysqli_query($con, "SELECT COALESCE(SUM(jumlah_bayar), 0) AS terbayar FROM tbl_pembayaran WHERE id_booking = $id_booking");
$paymentData = $paymentQuery ? mysqli_fetch_assoc($paymentQuery) : null;
$terbayar = (float) ($paymentData['terbayar'] ?? 0);
$sisaPembayaran = max(0, (float) $data['total'] - $terbayar);
$statusPembayaran = $terbayar <= 0 ? 'Belum Bayar' : (($terbayar + 0.009 >= (float) $data['total']) ? 'Lunas' : 'DP');

$checkIn = new DateTime($data['check_in']);
$checkOut = new DateTime($data['check_out']);
$jumlahMalam = max(1, (int) $checkIn->diff($checkOut)->days);

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function tanggalIndonesia($tanggal): string
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = new DateTime($tanggal);
    return (int) $d->format('d') . ' ' . $bulan[(int) $d->format('m')] . ' ' . $d->format('Y');
}

$pdf = new SimplePDF();

// Header PDF dengan logo Grand Dian Hotel di samping judul.
$logoPath = __DIR__ . '/../../assets_volt/img/logo hotel 2.jpg';
$pdf->image($logoPath, 70, 18, 58, 58);
$pdf->text(180, 42, 'GRAND DIAN HOTEL', 20, true);
$pdf->text(195, 63, 'BUKTI PEMESANAN KAMAR', 12, true);
$pdf->line(42, 90, 553, 90, 1.5);

$pdf->labelValue(120, 'Kode Booking', $data['kode_booking']);
$pdf->labelValue(140, 'Tanggal Cetak', date('d-m-Y H:i'));
$pdf->labelValue(160, 'Status', ucfirst(strtolower(trim($data['status']))));

$pdf->text(55, 200, 'DATA TAMU', 12, true);
$pdf->rect(50, 211, 495, 138, 1);
$pdf->labelValue(235, 'Nama Lengkap', $data['nama']);
$pdf->labelValue(257, 'No. Identitas', $data['no_identitas']);
$pdf->labelValue(279, 'No. HP', $data['no_hp']);
$pdf->labelValue(301, 'Email', $data['email']);
$pdf->labelValue(323, 'Alamat', $data['alamat']);

$pdf->text(55, 385, 'DETAIL PEMESANAN', 12, true);
$pdf->rect(50, 396, 495, 270, 1); // Tinggi kotak ditambah menjadi 270

$pdf->labelValue(420, 'Nomor Kamar', $data['nomor_kamar']);
$pdf->labelValue(442, 'Tipe Kamar', $data['nama_tipe']);
$pdf->labelValue(464, 'Harga / Malam', rupiah($data['harga']));
$pdf->labelValue(486, 'Check-in', tanggalIndonesia($data['check_in']));
$pdf->labelValue(508, 'Check-out', tanggalIndonesia($data['check_out']));
$pdf->labelValue(530, 'Jumlah Malam', $jumlahMalam . ' malam');
$pdf->labelValue(552, 'Jumlah Tamu', $data['jumlah_tamu'] . ' orang');
$pdf->labelValue(574, 'Total Pembayaran', rupiah($data['total']));
$pdf->labelValue(596, 'Sudah Dibayar', rupiah($terbayar));
$pdf->labelValue(618, 'Sisa Pembayaran', rupiah($sisaPembayaran));
$pdf->labelValue(640, 'Status Pembayaran', $statusPembayaran);

$pdf->line(50, 690, 545, 690, 1.2); // Posisi Y diturunkan menyesuaikan kotak
$pdf->centered(720, 'Terima kasih telah memilih Grand Dian Hotel.', 10, false); // Posisi Y diturunkan menyesuaikan kotak
$pdf->centered(739, 'Simpan bukti ini sebagai bukti pemesanan.', 10, false); // Posisi Y diturunkan menyesuaikan kotak

$pdf->output('Bukti-Pemesanan-' . $data['kode_booking'] . '.pdf');