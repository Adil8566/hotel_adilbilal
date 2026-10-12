<?php
require_once '../auth.php';

$keyword=trim($_GET['q']??'');
$where=["b.id_pengguna={$id_user}","LOWER(TRIM(b.status)) <> 'dibatalkan'"];
if($keyword!==''){
 $q=mysqli_real_escape_string($con,$keyword);
 $where[]="(b.kode_booking LIKE '%{$q}%' OR t.nama LIKE '%{$q}%' OR k.nomor_kamar LIKE '%{$q}%')";
}
$where_sql='WHERE '.implode(' AND ',$where);
$list=mysqli_query($con,"SELECT b.id_booking,b.kode_booking,b.total,t.nama,k.nomor_kamar,b.check_in,b.check_out,b.status,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar {$where_sql} ORDER BY b.id_booking DESC");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Pembayaran - Grand Dian Hotel</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css"></head>
<body class="bg-light"><nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="../index.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel Bumiayu</a><div class="text-white"><span class="me-3"><i class="fas fa-user me-1"></i><?= e($nama_user); ?></span><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></div></nav><div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4">
<div class="page-heading-mantis"><div><h1 class="page-title-mantis">Pembayaran</h1><p class="page-subtitle-mantis">Pantau tagihan dan riwayat pembayaran pemesanan Anda.</p></div></div>
<div class="card shadow-sm border-0 mb-4"><div class="card-body"><form class="row g-2"><div class="col-md-6"><input name="q" class="form-control" value="<?= e($keyword); ?>" placeholder="Cari kode booking atau nomor kamar..."></div><div class="col-md-auto"><button class="btn btn-primary"><i class="fas fa-search me-1"></i>Cari</button></div></form></div></div>
<div class="card shadow-sm border-0"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>No</th><th>Kode</th><th>Kamar</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Terbayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if($list&&mysqli_num_rows($list)>0):$no=1;while($r=mysqli_fetch_assoc($list)): $sp=status_pembayaran((float)$r['total'],(float)$r['terbayar']);$sisa=max(0,(float)$r['total']-(float)$r['terbayar']); ?><tr><td><?= $no++; ?></td><td class="fw-semibold"><?= e($r['kode_booking']); ?></td><td><?= e($r['nomor_kamar']); ?></td><td><?= tanggal_id($r['check_in']); ?></td><td><?= tanggal_id($r['check_out']); ?></td><td><?= rupiah($r['total']); ?></td><td class="text-success"><?= rupiah($r['terbayar']); ?></td><td class="text-danger"><?= rupiah($sisa); ?></td><td><span class="badge <?= status_pembayaran_badge($sp); ?>"><?= e($sp); ?></span></td><td><a href="../pemesanan/pembayaran.php?id=<?= (int)$r['id_booking']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-wallet me-1"></i>Bayar</a></td></tr><?php endwhile;else:?><tr><td colspan="10" class="text-center text-muted py-5">Belum ada data pembayaran.</td></tr><?php endif; ?></tbody></table></div></div></div>
</main></div></body></html>
