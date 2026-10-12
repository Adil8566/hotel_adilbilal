<?php
require_once '../auth.php';
$dari=$_GET['dari']??date('Y-m-01');
$sampai=$_GET['sampai']??date('Y-m-t');
$status=strtolower(trim($_GET['status']??''));
$where=["b.id_pengguna={$id_user}","b.check_in <= '".mysqli_real_escape_string($con,$sampai)."'","b.check_out >= '".mysqli_real_escape_string($con,$dari)."'"];
if(valid_booking_status($status))$where[]="LOWER(TRIM(b.status))='".mysqli_real_escape_string($con,$status)."'";
$ws=implode(' AND ',$where);
$list=mysqli_query($con,"SELECT b.id_booking,b.kode_booking,t.nama,k.nomor_kamar,tk.nama_tipe,b.check_in,b.check_out,LOWER(TRIM(b.status)) status,b.total,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE {$ws} ORDER BY b.check_in ASC,b.id_booking ASC");
$rows=[];$total_booking=0;$nilai=0;$bayar=0;if($list){while($r=mysqli_fetch_assoc($list)){$rows[]=$r;$total_booking++;$nilai+=(float)$r['total'];$bayar+=(float)$r['terbayar'];}}$sisa=max(0,$nilai-$bayar);
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Laporan Pemesanan Saya</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
    </head>
<body class="bg-light">
    <nav class="navbar navbar-light mantis-topbar">
        <div class="container-fluid">
            <a href="../index.php" class="navbar-brand">
                <i class="fas fa-hotel me-2">
                </i>Grand Dian Hotel Bumiayu</a>
                <div class="text-white">
                    <span class="me-3">
                        <i class="fas fa-user me-1">
                        </i>
                        <?= e($nama_user); ?>
                    </span>
                    <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </nav>
        <div class="d-flex">
            <?php include '../template/sidebar.php'; ?>
            <main class="flex-grow-1 p-4">
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h3>Laporan Pemesanan</h3>
        <p class="text-muted mb-0">Laporan ini hanya berisi transaksi milik akun Anda.</p>
    </div>
    <a href="export.php?dari=<?= urlencode($dari); ?>&sampai=<?= urlencode($sampai); ?>&status=<?= urlencode($status); ?>" class="btn btn-outline-success">
        <i class="fas fa-file-csv me-1"></i>Export CSV</a>
    </div>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">Dari</label>
                <input type="date" name="dari" class="form-control" value="<?= e($dari); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai</label>
                <input type="date" name="sampai" class="form-control" value="<?= e($sampai); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <?php 
                    foreach(['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st): 
                    ?>
                    <option value="<?= $st; ?>" <?= $status===$st?'selected':''; ?>><?= status_booking_label($st); 
                    ?>
                    </option>
                    <?php 
                    endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="dashboard-stat">
            <div class="stat-label">Jumlah Booking</div>
            <div class="stat-value"><?= $total_booking; ?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="dashboard-stat">
        <div class="stat-label">Nilai Booking</div>
        <div class="stat-value" style="font-size:22px;"><?= rupiah($nilai); ?>
    </div>
</div>
</div>
<div class="col-md-4">
    <div class="dashboard-stat">
        <div class="stat-label">Sisa Tagihan</div>
        <div class="stat-value" style="font-size:22px;"><?= rupiah($sisa); ?>
    </div>
</div>
</div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Tipe Kamar</th>
                        <th>Kamar</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($rows):$no=1;
                    foreach($rows as $r):$rs=max(0,(float)$r['total']-(float)$r['terbayar']);?>
                    <tr>
                        <td><?= $no++; ?>
                    </td>
                    <td>
                        <?= e($r['kode_booking']); ?>
                    </td>
                    <td>
                        <?= e($r['nama_tipe']); ?>
                    </td>
                    <td>
                        <?= e($r['nomor_kamar']); ?>
                    </td>
                    <td>
                        <?= tanggal_id($r['check_in']); ?> - <?= tanggal_id($r['check_out']); ?>
                    </td>
                    <td>
                        <span class="badge <?= status_booking_badge($r['status']); ?>"><?= status_booking_label($r['status']); ?>
                    </span>
                </td>
                <td>
                    <?= rupiah($r['total']); ?>
                </td>
                <td class="text-success"><?= rupiah($r['terbayar']); ?>
            </td>
            <td class="text-danger"><?= rupiah($rs); ?>
        </td>
    </tr>
    <?php 
    endforeach;
    else:
    ?>
    <tr>
        <td colspan="9" class="text-center text-muted py-5">Tidak ada transaksi pada periode tersebut.</td>
    </tr>
    <?php 
    endif; ?>
    </tbody>
</table>
</div>
</div>
</div>
</main>
</div>
</body>
</html>
