<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }

require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);

// Memanggil library FPDF
require_once '../../assets_volt/fpdf/fpdf.php'; 

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

$where = ["b.check_in <= '{$sampai}'", "b.check_out >= '{$dari}'"];
if(valid_booking_status($status)) {
    $where[] = "LOWER(TRIM(b.status))='" . mysqli_real_escape_string($con, $status) . "'";
}
$ws = implode(' AND ', $where);

$sql = "SELECT b.id_booking, b.kode_booking, t.nama, k.nomor_kamar, tk.nama_tipe, b.check_in, b.check_out, LOWER(TRIM(b.status)) status, b.total, COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar 
        FROM tbl_booking b 
        INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu 
        INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar 
        INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe 
        WHERE {$ws} 
        ORDER BY b.check_in ASC, b.id_booking ASC";
$list = mysqli_query($con, $sql);

// Set up FPDF - L (Landscape), mm (milimeter), A4 (Kertas)
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// Judul Laporan
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN PEMESANAN DAN PEMBAYARAN HOTEL', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Periode: ' . tanggal_id($dari) . ' sampai ' . tanggal_id($sampai), 0, 1, 'C');
$st_label = valid_booking_status($status) ? status_booking_label($status) : 'Semua Status';
$pdf->Cell(0, 6, 'Status: ' . $st_label, 0, 1, 'C');
$pdf->Ln(5);

// Pengaturan Lebar Kolom Tabel (Total Max Lebar = 277mm)
$w = [8, 35, 40, 35, 25, 25, 25, 28, 28, 28];

// Header Tabel FPDF
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell($w[0], 8, 'NO', 1, 0, 'C', true);
$pdf->Cell($w[1], 8, 'KODE', 1, 0, 'C', true);
$pdf->Cell($w[2], 8, 'TAMU', 1, 0, 'C', true);
$pdf->Cell($w[3], 8, 'KAMAR', 1, 0, 'C', true);
$pdf->Cell($w[4], 8, 'CHECK-IN', 1, 0, 'C', true);
$pdf->Cell($w[5], 8, 'CHECK-OUT', 1, 0, 'C', true);
$pdf->Cell($w[6], 8, 'STATUS', 1, 0, 'C', true);
$pdf->Cell($w[7], 8, 'TOTAL', 1, 0, 'C', true);
$pdf->Cell($w[8], 8, 'TERBAYAR', 1, 0, 'C', true);
$pdf->Cell($w[9], 8, 'SISA', 1, 1, 'C', true);

// Looping Data Tabel
$pdf->SetFont('Arial', '', 8);
$no = 1;
$nilai_booking = 0;
$pembayaran = 0;
$sisa_tagihan = 0;

if (mysqli_num_rows($list) > 0) {
    while ($r = mysqli_fetch_assoc($list)) {
        $sisa = max(0, (float)$r['total'] - (float)$r['terbayar']);
        $nilai_booking += (float)$r['total'];
        $pembayaran += (float)$r['terbayar'];
        $sisa_tagihan += $sisa;

        $tamu = strlen($r['nama']) > 23 ? substr($r['nama'], 0, 21) . '...' : $r['nama'];
        $kamar_raw = $r['nomor_kamar'] . ' (' . $r['nama_tipe'] . ')';
        $kamar = strlen($kamar_raw) > 20 ? substr($kamar_raw, 0, 18) . '...' : $kamar_raw;

        $pdf->Cell($w[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 7, $r['kode_booking'], 1, 0, 'C');
        $pdf->Cell($w[2], 7, $tamu, 1, 0, 'L');
        $pdf->Cell($w[3], 7, $kamar, 1, 0, 'L');
        $pdf->Cell($w[4], 7, date('d-m-Y', strtotime($r['check_in'])), 1, 0, 'C');
        $pdf->Cell($w[5], 7, date('d-m-Y', strtotime($r['check_out'])), 1, 0, 'C');
        $pdf->Cell($w[6], 7, status_booking_label($r['status']), 1, 0, 'C');
        $pdf->Cell($w[7], 7, rupiah($r['total']), 1, 0, 'R');
        $pdf->Cell($w[8], 7, rupiah($r['terbayar']), 1, 0, 'R');
        $pdf->Cell($w[9], 7, rupiah($sisa), 1, 1, 'R');
    }
    
    // Baris Total Bawah
    $pdf->SetFont('Arial', 'B', 8);
    $lebar_gabung = $w[0]+$w[1]+$w[2]+$w[3]+$w[4]+$w[5]+$w[6];
    $pdf->Cell($lebar_gabung, 8, 'TOTAL KESELURUHAN', 1, 0, 'R', true);
    $pdf->Cell($w[7], 8, rupiah($nilai_booking), 1, 0, 'R', true);
    $pdf->Cell($w[8], 8, rupiah($pembayaran), 1, 0, 'R', true);
    $pdf->Cell($w[9], 8, rupiah($sisa_tagihan), 1, 1, 'R', true);
    
} else {
    $pdf->Cell(array_sum($w), 10, 'Tidak ada data pada periode ini.', 1, 1, 'C');
}

// Menampilkan / Mengunduh PDF
$nama_file = 'Laporan_Hotel_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $nama_file); // 'I' membuka di browser, ganti ke 'D' untuk otomatis download
?>