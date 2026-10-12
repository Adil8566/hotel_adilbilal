<?php
require_once '../auth.php';
$id_booking = (int)($_GET['id'] ?? 0);
if ($id_booking <= 0) { header('Location: pemesanan.php'); exit; }

$q = mysqli_query($con, "
    SELECT b.*, t.nama AS nama_tamu,t.no_identitas,t.no_hp,t.email,t.alamat,
           k.nomor_kamar,tk.nama_tipe,tk.harga,
           COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) AS terbayar
    FROM tbl_booking b
    INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu
    INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar
    INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe
    WHERE b.id_booking={$id_booking} AND b.id_pengguna={$id_user}
    LIMIT 1
");
$data = $q ? mysqli_fetch_assoc($q) : null;
if (!$data) { header('Location: pemesanan.php'); exit; }

$terbayar = (float)$data['terbayar'];
$sisa = max(0,(float)$data['total']-$terbayar);
$status_bayar = status_pembayaran((float)$data['total'],$terbayar);
$pesan = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Detail Pemesanan</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css"></head>
<body class="bg-light"><nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav>
<div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4">
<?php if($pesan==='tambah'): ?><div class="alert alert-success">Pemesanan berhasil dibuat dan menunggu konfirmasi petugas.</div><?php elseif($pesan==='edit'): ?><div class="alert alert-success">Pemesanan berhasil diperbarui.</div><?php endif; ?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><div><h3>Detail Pemesanan</h3><p class="text-muted mb-0"><?= e($data['kode_booking']); ?> · <?= e($data['nama_tamu']); ?></p></div><div class="d-flex gap-2"><a href="cetak.php?id=<?= $id_booking; ?>" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf me-1"></i>Cetak Bukti</a><?php if(in_array(strtolower(trim($data['status'])),['pending','dikonfirmasi'],true)): ?><a href="batalkan.php?id=<?= $id_booking; ?>" class="btn btn-outline-danger" onclick="return confirm('Batalkan pemesanan ini?');">Batalkan</a><?php endif; ?></div></div>
<div class="row g-4">
<div class="col-lg-8"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Informasi Booking</h5></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><div class="text-muted small">Tipe Kamar</div><div class="fw-semibold"><?= e($data['nama_tipe']); ?></div></div>
<div class="col-md-6"><div class="text-muted small">Nomor Kamar</div><div class="fw-semibold"><?= e($data['nomor_kamar']); ?></div></div>
<div class="col-md-6"><div class="text-muted small">Check-in</div><div class="fw-semibold"><?= tanggal_id($data['check_in']); ?></div></div>
<div class="col-md-6"><div class="text-muted small">Check-out</div><div class="fw-semibold"><?= tanggal_id($data['check_out']); ?></div></div>
<div class="col-md-6"><div class="text-muted small">Jumlah Tamu</div><div class="fw-semibold"><?= (int)$data['jumlah_tamu']; ?> orang</div></div>
<div class="col-md-6"><div class="text-muted small">Status</div><div><span class="badge <?= status_booking_badge($data['status']); ?>"><?= status_booking_label($data['status']); ?></span></div></div>
<div class="col-12"><hr><div class="text-muted small mb-1">Data Tamu</div><div><?= e($data['nama_tamu']); ?> · <?= e($data['no_hp'] ?: '-'); ?> · <?= e($data['email'] ?: '-'); ?></div></div>
</div></div></div></div>
<div class="col-lg-4"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h6 class="mb-0">Ringkasan Pembayaran</h6></div><div class="card-body"><div class="text-muted small">Total</div><h5><?= rupiah($data['total']); ?></h5><div class="text-muted small">Sudah dibayar</div><h5 class="text-success"><?= rupiah($terbayar); ?></h5><div class="text-muted small">Sisa</div><h5 class="text-danger"><?= rupiah($sisa); ?></h5><span class="badge <?= status_pembayaran_badge($status_bayar); ?> mb-3"><?= e($status_bayar); ?></span><?php if($sisa>0 && !in_array(strtolower(trim($data['status'])),['dibatalkan','checkout'],true)): ?><a href="pembayaran.php?id=<?= $id_booking; ?>" class="btn btn-warning w-100"><i class="fas fa-wallet me-1"></i>Buka Pembayaran</a><?php endif; ?></div></div></div>
</div>
</main></div></body></html>
