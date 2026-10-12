<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';

$id_booking = (int)($_GET['id'] ?? 0);
if ($id_booking <= 0) { header('Location: pemesanan.php'); exit; }

$q = mysqli_query($con, "SELECT b.*, t.nama AS nama_tamu, k.nomor_kamar, tk.nama_tipe FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE b.id_booking={$id_booking} LIMIT 1");

$data = $q ? mysqli_fetch_assoc($q) : null;
if (!$data) { header('Location: pemesanan.php'); exit; }
$error='';
if (isset($_POST['simpan_pembayaran'])) {
    $jumlah = (float)($_POST['jumlah_bayar'] ?? 0);
    $metode = trim($_POST['metode'] ?? 'Cash');
    $sudah = total_terbayar($con, $id_booking);
    
    // Perhitungan sisa tagihan dibulatkan
    $sisa = max(0, round((float)$data['total'] - $sudah));
    
    if ($jumlah <= 0) $error = 'Jumlah pembayaran harus lebih dari 0.';
    elseif ($jumlah > $sisa) $error = 'Jumlah pembayaran melebihi sisa tagihan.';
    else {
        $status_bayar = ($sudah + $jumlah >= round((float)$data['total'])) ? 'lunas' : 'belum_lunas';
        $stmt = mysqli_prepare($con, 'INSERT INTO tbl_pembayaran (id_booking, jumlah_bayar, metode, status) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'idss', $id_booking, $jumlah, $metode, $status_bayar);
        if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header('Location: pembayaran.php?id='.$id_booking.'&pesan=bayar'); exit; }
        $error = 'Pembayaran gagal disimpan: ' . mysqli_error($con); mysqli_stmt_close($stmt);
    }
}

$terbayar = total_terbayar($con, $id_booking); 
$sisa = max(0, round((float)$data['total'] - $terbayar)); 
$sp = status_pembayaran((float)$data['total'], $terbayar);

// Sinkronkan status pada data pembayaran
$sync_status = $sp === 'Lunas' ? 'lunas' : 'belum_lunas';
@mysqli_query($con, "UPDATE tbl_pembayaran SET status='{$sync_status}' WHERE id_booking={$id_booking}");
$riwayat=mysqli_query($con,"SELECT id_pembayaran,id_booking,tanggal_bayar,jumlah_bayar,metode,status FROM tbl_pembayaran WHERE id_booking={$id_booking} ORDER BY id_pembayaran DESC");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Pembayaran Booking</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light"><nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav><div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4">
<div class="mb-4"><h3>Pembayaran Booking</h3><p class="text-muted mb-0"><?= e($data['kode_booking']); ?> - <?= e($data['nama_tamu']); ?> - Kamar <?= e($data['nomor_kamar']); ?></p></div>
<?php if (($_GET['pesan'] ?? '') === 'bayar'): ?><div class="alert alert-success">Pembayaran berhasil dicatat.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Tambah Pembayaran</h5></div><div class="card-body"><div class="row g-3 mb-3"><div class="col-4"><small class="text-muted">Total</small><div class="fw-bold"><?= rupiah($data['total']); ?></div></div><div class="col-4"><small class="text-muted">Terbayar</small><div class="fw-bold text-success"><?= rupiah($terbayar); ?></div></div><div class="col-4"><small class="text-muted">Sisa</small><div class="fw-bold text-danger"><?= rupiah($sisa); ?></div></div></div><span class="badge <?= status_pembayaran_badge($sp); ?> mb-3"><?= e($sp); ?></span>
<?php if ($sisa > 0): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Jumlah Bayar</label>
            <input type="number" name="jumlah_bayar" class="form-control" min="1" max="<?= $sisa; ?>" value="<?= $sisa; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Metode Pembayaran</label>
            <select name="metode" class="form-select">
                <option>Cash</option>
                <option>Transfer</option>
                <option>QRIS</option>
                <option>Debit</option>
                <option>Kartu Kredit</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <input class="form-control" value="Otomatis berdasarkan total pembayaran" readonly>
        </div>
        <button name="simpan_pembayaran" class="btn btn-warning w-100"><i class="fas fa-save me-1"></i>Simpan Pembayaran</button>
    </form>
<?php else: ?>
    <div class="alert alert-success mb-0">Tagihan sudah lunas.</div>
<?php endif; ?>
<a href="detail.php?id=<?= $id_booking; ?>" class="btn btn-secondary w-100 mt-2">Kembali ke Detail</a></div></div></div>
<div class="col-lg-7"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Riwayat Pembayaran</h5></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle"><thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php $no=1; if($riwayat && mysqli_num_rows($riwayat)>0): while($p=mysqli_fetch_assoc($riwayat)): ?>
<tr>
    <td><?= $no++; ?></td>
    <td><?= tanggal_id($p['tanggal_bayar']); ?><br><small><?= date('H:i', strtotime($p['tanggal_bayar'])); ?></small></td>
    <td><?= e($p['metode']); ?></td>
    <td><?= rupiah($p['jumlah_bayar']); ?></td>
    <td><span class="badge <?= $p['status'] === 'lunas' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?= e($p['status'] ?: 'belum_lunas'); ?></span></td>
    <td>
        <!-- PENAMBAHAN TOMBOL CETAK BUKTI -->
        <div class="d-flex gap-1">
            <a href="cetak_struk.php?id_pembayaran=<?= (int)$p['id_pembayaran']; ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Cetak Bukti">
                <i class="fas fa-print"></i>
            </a>
            <a href="pembayaran_hapus.php?id=<?= (int)$p['id_pembayaran']; ?>&booking=<?= $id_booking; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus catatan pembayaran ini?');" title="Hapus">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="6" class="text-center text-muted">Belum ada pembayaran.</td></tr>
<?php endif; ?>
</tbody></table></div></div></div></div></div>
</main></div></body></html>