<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
$id_booking = (int)($_GET['id'] ?? 0);
if ($id_booking <= 0) { header('Location: pemesanan.php'); exit; }
$q = mysqli_query($con, "
 SELECT b.*, LOWER(TRIM(b.status)) AS status_normal,
        t.nama, t.no_identitas, t.no_hp, t.email, t.alamat,
        k.id_kamar, k.nomor_kamar,
        tk.nama_tipe, tk.harga
 FROM tbl_booking b
 INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu
 INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar
 INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe
 WHERE b.id_booking={$id_booking} LIMIT 1
");
$data = $q ? mysqli_fetch_assoc($q) : null;
if (!$data) { header('Location: pemesanan.php'); exit; }
$terbayar = total_terbayar($con, $id_booking);
$sisa = max(0, (float)$data['total'] - $terbayar);
$status_bayar = status_pembayaran((float)$data['total'], $terbayar);
$st = strtolower(trim($data['status_normal']));
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Detail Pemesanan</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light"><nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav>
<div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4">
<?php if (($_GET['pesan'] ?? '') === 'tambah'): ?><div class="alert alert-success">Pemesanan berhasil disimpan. Booking masih berstatus Pending sampai dikonfirmasi.</div><?php endif; ?>
<div class="row g-4"><div class="col-lg-8"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Detail Pemesanan</h5></div><div class="card-body"><table class="table table-bordered"><tr><th width="220">Kode Booking</th><td><?= e($data['kode_booking']); ?></td></tr><tr><th>Nama Tamu</th><td><?= e($data['nama']); ?></td></tr><tr><th>No. Identitas</th><td><?= e($data['no_identitas']); ?></td></tr><tr><th>No. HP</th><td><?= e($data['no_hp']); ?></td></tr><tr><th>Email</th><td><?= e($data['email']); ?></td></tr><tr><th>Kamar</th><td><?= e($data['nomor_kamar']); ?> - <?= e($data['nama_tipe']); ?></td></tr><tr><th>Harga / Malam</th><td><?= rupiah($data['harga']); ?></td></tr><tr><th>Check-in</th><td><?= tanggal_id($data['check_in']); ?></td></tr><tr><th>Check-out</th><td><?= tanggal_id($data['check_out']); ?></td></tr><tr><th>Jumlah Tamu</th><td><?= (int)$data['jumlah_tamu']; ?> orang</td></tr><tr><th>Total</th><td><strong><?= rupiah($data['total']); ?></strong></td></tr><tr><th>Status Booking</th><td><span class="badge <?= status_booking_badge($st); ?>"><?= status_booking_label($st); ?></span></td></tr></table>
<div class="d-flex flex-wrap gap-2"><a href="cetak.php?id=<?= $id_booking; ?>" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf me-1"></i>Cetak Bukti PDF</a><a href="pembayaran.php?id=<?= $id_booking; ?>" class="btn btn-warning"><i class="fas fa-money-bill-wave me-1"></i>Kelola Pembayaran</a><?php if ($st === 'pending'): ?><form action="verifikasi.php" method="POST" class="d-inline"><input type="hidden" name="id" value="<?= $id_booking; ?>"><button class="btn btn-success" onclick="return confirm('Konfirmasi booking ini?');"><i class="fas fa-check me-1"></i>Konfirmasi</button></form><?php endif; ?><?php if ($st === 'dikonfirmasi'): ?><form action="checkin.php" method="POST" class="d-inline"><input type="hidden" name="id" value="<?= $id_booking; ?>"><button class="btn btn-info" onclick="return confirm('Lakukan check-in?');"><i class="fas fa-right-to-bracket me-1"></i>Check-in</button></form><?php endif; ?><?php if ($st === 'checkin'): ?><form action="checkout.php" method="POST" class="d-inline"><input type="hidden" name="id" value="<?= $id_booking; ?>"><button class="btn btn-success" onclick="return confirm('Lakukan check-out?');"><i class="fas fa-right-from-bracket me-1"></i>Check-out</button></form><?php endif; ?><a href="pemesanan.php" class="btn btn-secondary">Kembali</a></div>
</div></div></div>
<div class="col-lg-4"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h6 class="mb-0">Ringkasan Pembayaran</h6></div><div class="card-body"><p class="mb-1 text-muted">Total</p><h5><?= rupiah($data['total']); ?></h5><p class="mb-1 text-muted">Sudah dibayar</p><h5 class="text-success"><?= rupiah($terbayar); ?></h5><p class="mb-1 text-muted">Sisa</p><h5 class="text-danger"><?= rupiah($sisa); ?></h5><span class="badge <?= status_pembayaran_badge($status_bayar); ?>">Status: <?= e($status_bayar); ?></span><hr><a href="pembayaran.php?id=<?= $id_booking; ?>" class="btn btn-outline-warning w-100">Buka Pembayaran</a></div></div></div></div>
</main></div></body></html>