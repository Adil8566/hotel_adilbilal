<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
$dari=$_GET['dari']??date('Y-m-01');
$sampai=$_GET['sampai']??date('Y-m-t');
$status=$_GET['status']??'';
$where=["b.check_in <= '{$sampai}'","b.check_out >= '{$dari}'"];
if(valid_booking_status($status)) $where[]="LOWER(TRIM(b.status))='".mysqli_real_escape_string($con,$status)."'"; $ws=implode(' AND ',$where);
$list=mysqli_query($con,"SELECT b.id_booking,b.kode_booking,t.nama,k.nomor_kamar,tk.nama_tipe,b.check_in,b.check_out,LOWER(TRIM(b.status)) status,b.total,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE {$ws} ORDER BY b.check_in ASC,b.id_booking ASC");
$total_booking=0;$nilai_booking=0;$pembayaran=0;$sisa=0;$rows=[]; if($list){while($r=mysqli_fetch_assoc($list)){ $rows[]=$r;$total_booking++;$nilai_booking+=(float)$r['total'];$pembayaran+=(float)$r['terbayar'];$sisa+=max(0,(float)$r['total']-(float)$r['terbayar']); }}
?>
<!DOCTYPE html><html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Laporan Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
    <style>@media print{.no-print,.sidebar{display:none!important}.content-print{width:100%!important}}</style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-light mantis-topbar no-print">
        <div class="container-fluid">
            <a href="../index.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar"><?php include '../template/sidebar.php'; ?></div>
        <main class="flex-grow-1 p-4 content-print">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3>Laporan Pemesanan dan Pembayaran</h3>
                    <p class="text-muted mb-0">Periode <?= tanggal_id($dari); ?> sampai <?= tanggal_id($sampai); ?></p>
                </div>
                
            </div>
            
            <form class="card card-body shadow-sm border-0 mb-4 no-print row g-2" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Dari</label>
                    <input type="date" name="dari" class="form-control" value="<?= e($dari); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="<?= e($sampai); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach(['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st): ?>
                            <option value="<?= $st; ?>" <?= $status===$st?'selected':''; ?>><?= status_booking_label($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Tampilkan</button>
            </div>
                
            </form>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <small class="text-muted">Jumlah Booking</small>
                            <h4><?= $total_booking; ?>
                        </h4>
                    </div>
                </div>
            </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <small class="text-muted">Nilai Booking</small>
                            <h5><?= rupiah($nilai_booking); ?>
                        </h5>
                    </div>
                </div>
            </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <small class="text-muted">Sudah Dibayar</small>
                            <h5 class="text-success"><?= rupiah($pembayaran); ?>
                        </h5>
                    </div>
                </div>
            </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <small class="text-muted">Sisa Tagihan</small>
                            <h5 class="text-danger"><?= rupiah($sisa); ?>
                        </h5>
                    </div>
                </div>
            </div>
            </div>
            <div class="no-print">
                    <!-- Perubahan: Tombol PDF diubah menjadi link <a> yang mengarah ke cetak_pdf.php -->
                    <a href="cetak_pdf.php?dari=<?= urlencode($dari); ?>&sampai=<?= urlencode($sampai); ?>&status=<?= urlencode($status); ?>" target="_blank" class="btn btn-danger"><i class="fas fa-print me-1"></i>Cetak / Simpan PDF</a>
                </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Tamu</th>
                                    <th>Kamar</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Terbayar</th>
                                    <th>Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($rows):$no=1;foreach($rows as $r):$s=max(0,(float)$r['total']-(float)$r['terbayar']); 
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= e($r['kode_booking']); ?></td>
                                    <td><?= e($r['nama']); ?>
                                </td>
                                <td><?= e($r['nomor_kamar']); ?>
                                <br>
                                <small><?= e($r['nama_tipe']); ?>
                            </small>
                        </td>
                        <td><?= tanggal_id($r['check_in']); ?>
                    </td>
                    <td><?= tanggal_id($r['check_out']); ?></td>
                    <td><?= status_booking_label($r['status']); ?></td>
                    <td><?= rupiah($r['total']); ?></td>
                    <td><?= rupiah($r['terbayar']); ?></td>
                    <td><?= rupiah($s); ?></td>
                </tr>
                    <?php
                            endforeach;
                            else:?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Tidak ada data pada periode ini.</td>
                                </tr>
                                <?php
                            endif;
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>