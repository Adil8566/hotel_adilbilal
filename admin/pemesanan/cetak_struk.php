<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
// PENTING: Sesuaikan letak path file fpdf.php Anda di bawah ini!
require_once '../../assets_volt/fpdf/fpdf.php'; 

// Ambil ID Pembayaran dari URL
$id_pembayaran = isset($_GET['id_pembayaran']) ? (int)$_GET['id_pembayaran'] : 0;

if ($id_pembayaran === 0) {
    die("ID Pembayaran tidak valid.");
}

// Query untuk mengambil data pembayaran, booking, dan tamu
$query = mysqli_query($con, "
    SELECT 
        p.*, 
        b.kode_booking, b.check_in, b.check_out,
        t.nama AS nama_tamu,
        k.nomor_kamar
    FROM tbl_pembayaran p
    JOIN tbl_booking b ON p.id_booking = b.id_booking
    JOIN tbl_tamu t ON b.id_tamu = t.id_tamu
    JOIN tbl_kamar k ON b.id_kamar = k.id_kamar
    WHERE p.id_pembayaran = $id_pembayaran
");

if (mysqli_num_rows($query) == 0) {
    die("Data pembayaran tidak ditemukan.");
}

$data = mysqli_fetch_assoc($query);

// ==========================================
// MULAI GENERATE PDF DENGAN FPDF
// ==========================================

// Membuat instance FPDF (P = Portrait, mm = milimeter, A5 = Ukuran Kertas agar pas untuk struk)
$pdf = new FPDF('P', 'mm', 'A5');
$pdf->AddPage();

// Header Hotel
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 7, 'GRAND DIAN HOTEL BUMIAYU', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Jl. Raya Bumiayu - Tegal, Brebes, Jawa Tengah', 0, 1, 'C');
$pdf->Cell(0, 5, 'Telp: (0289) 1234567 | Email: info@granddianbumiayu.com', 0, 1, 'C');

// Garis Pemisah
$pdf->SetLineWidth(0.5);
$pdf->Line(10, 30, 138, 30);
$pdf->Ln(8); // Enter spacing

// Judul Struk
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'BUKTI PEMBAYARAN', 0, 1, 'C');
$pdf->Ln(5);

// Info Data Transaksi
$pdf->SetFont('Arial', '', 10);

// Set lebar kolom
$col1 = 40; 
$col2 = 5;

// Baris 1: No Transaksi
$pdf->Cell($col1, 6, 'No. Transaksi', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, 'PAY-'.str_pad($data['id_pembayaran'], 5, '0', STR_PAD_LEFT), 0, 1);

// Baris 2: Tanggal
$pdf->Cell($col1, 6, 'Tanggal Bayar', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, date('d M Y H:i', strtotime($data['tanggal_bayar'])), 0, 1);

// Baris 3: Kode Booking
$pdf->Cell($col1, 6, 'Kode Booking', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, $data['kode_booking'], 0, 1);

// Baris 4: Nama Tamu
$pdf->Cell($col1, 6, 'Nama Tamu', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, $data['nama_tamu'], 0, 1);

// Baris 5: Kamar
$pdf->Cell($col1, 6, 'No. Kamar', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, $data['nomor_kamar'], 0, 1);

$pdf->Ln(5);
$pdf->SetLineWidth(0.2);
$pdf->Line(10, $pdf->GetY(), 138, $pdf->GetY());
$pdf->Ln(5);

// Detail Pembayaran
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($col1, 6, 'Metode Pembayaran', 0, 0);
$pdf->Cell($col2, 6, ':', 0, 0);
$pdf->Cell(0, 6, strtoupper($data['metode']), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($col1, 8, 'JUMLAH BAYAR', 0, 0);
$pdf->Cell($col2, 8, ':', 0, 0);
$pdf->Cell(0, 8, 'Rp ' . number_format($data['jumlah_bayar'], 0, ',', '.'), 0, 1);

$pdf->Ln(5);
$pdf->SetLineWidth(0.2);
$pdf->Line(10, $pdf->GetY(), 138, $pdf->GetY());
$pdf->Ln(10);

// Tanda Tangan & Footer
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 6, '', 0, 0); // Spacer kosong untuk posisi ke kanan
$pdf->Cell(0, 6, 'Bumiayu, ' . date('d M Y'), 0, 1, 'C');
$pdf->Cell(70, 6, '', 0, 0);
$pdf->Cell(0, 6, 'Resepsionis,', 0, 1, 'C');

$pdf->Ln(15); // Jarak tanda tangan

$pdf->Cell(70, 6, '', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, '(' . $_SESSION['nama'] . ')', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Terima kasih atas kunjungan Anda.', 0, 1, 'C');

// Output file PDF ke Browser (huruf 'I' berarti Inline browser, bukan langsung download)
$pdf->Output('I', 'Struk_Pembayaran_'.$data['kode_booking'].'.pdf');
?>