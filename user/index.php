<?php
require_once 'auth.php';

function user_total(mysqli $con, string $sql): int {
    $q = mysqli_query($con, $sql);
    $d = $q ? mysqli_fetch_assoc($q) : null;
    return (int)($d['total'] ?? 0);
}

$total_tipe = user_total($con, "SELECT COUNT(*) AS total FROM tbl_tipe_kamar");
$total_kamar = user_total($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE LOWER(TRIM(status)) <> 'maintenance'");
$kamar_tersedia = user_total($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE LOWER(TRIM(status))='tersedia'");
$total_booking = user_total($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE id_pengguna={$id_user}");
$pending = user_total($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE id_pengguna={$id_user} AND LOWER(TRIM(status))='pending'");
$aktif = user_total($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE id_pengguna={$id_user} AND LOWER(TRIM(status)) IN ('dikonfirmasi','checkin')");
$q_tagihan = mysqli_query($con, "
    SELECT COALESCE(SUM(b.total),0) AS total,
           COALESCE(SUM((SELECT COALESCE(SUM(p.jumlah_bayar),0) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking)),0) AS terbayar
    FROM tbl_booking b
    WHERE b.id_pengguna={$id_user}
      AND LOWER(TRIM(b.status)) <> 'dibatalkan'
");
$tagihan = $q_tagihan ? mysqli_fetch_assoc($q_tagihan) : [];
$total_tagihan = max(0, (float)($tagihan['total'] ?? 0) - (float)($tagihan['terbayar'] ?? 0));

$upcoming = mysqli_query($con, "
    SELECT b.id_booking,b.kode_booking,b.check_in,b.check_out,b.total,b.status,
           k.nomor_kamar,tk.nama_tipe,
           COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) AS terbayar
    FROM tbl_booking b
    INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar
    INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe
    WHERE b.id_pengguna={$id_user}
      AND LOWER(TRIM(b.status)) IN ('pending','dikonfirmasi','checkin')
      AND b.check_out >= CURDATE()
    ORDER BY b.check_in ASC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard User - Grand Dian Hotel</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets_mantis/css/mantis-hotel.css">
<style>
.user-hero{background:linear-gradient(135deg,#ffffff 0%,#f4f8ff 100%);border:1px solid #e5ebf3;border-radius:18px;padding:28px;box-shadow:0 10px 30px rgba(20,40,80,.05)}
.user-action{display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;border:1px solid #e7ebf2;border-radius:14px;padding:16px;background:#fff;transition:.2s;height:100%}
.user-action:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,0,0,.07)}
.user-action .ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#eef4ff;color:#2563eb}
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel Bumiayu</a>
        <div><span class="me-3 small text-white"><i class="fas fa-user me-1"></i><?= e($nama_user); ?></span><a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a></div>
    </div>
</nav>

<div class="d-flex">
<?php include 'template/sidebar.php'; ?>
<main class="flex-grow-1 p-4">
    <div class="page-heading-mantis">
        <div>
            <h1 class="page-title-mantis">Dashboard User</h1>
            <p class="page-subtitle-mantis">Kelola pemesanan kamar Anda tanpa masuk ke panel administrasi.</p>
        </div>
    </div>

    <section class="user-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="text-muted small mb-1">Selamat datang</div>
                <h2 class="mb-2"><?= e($nama_user); ?></h2>
                <p class="mb-0 text-muted">Lihat kamar, buat reservasi, dan pantau status pembayaran dari satu tempat.</p>
            </div>
            <a href="tipe_kamar/index.php" class="btn btn-primary px-4"><i class="fas fa-bed me-2"></i>Lihat Kamar</a>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3"><div class="dashboard-stat"><div class="stat-top"><div><div class="stat-label">Kamar Tersedia</div><div class="stat-value"><?= $kamar_tersedia; ?></div><div class="stat-note">dari <?= $total_kamar; ?> unit aktif</div></div><div class="stat-icon-mantis success"><i class="fas fa-bed"></i></div></div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="dashboard-stat"><div class="stat-top"><div><div class="stat-label">Pemesanan Saya</div><div class="stat-value"><?= $total_booking; ?></div><div class="stat-note"><?= $aktif; ?> pemesanan aktif</div></div><div class="stat-icon-mantis info"><i class="fas fa-calendar-check"></i></div></div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="dashboard-stat"><div class="stat-top"><div><div class="stat-label">Menunggu Konfirmasi</div><div class="stat-value"><?= $pending; ?></div><div class="stat-note">status pending</div></div><div class="stat-icon-mantis warning"><i class="fas fa-clock"></i></div></div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="dashboard-stat"><div class="stat-top"><div><div class="stat-label">Sisa Tagihan</div><div class="stat-value" style="font-size:20px;"><?= rupiah($total_tagihan); ?></div><div class="stat-note">belum terbayar</div></div><div class="stat-icon-mantis"><i class="fas fa-wallet"></i></div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><a class="user-action" href="tipe_kamar/index.php"><span class="ic"><i class="fas fa-bed"></i></span><div><strong>Pesan Kamar</strong><div class="text-muted small">Pilih tipe kamar dan tanggal menginap.</div></div></a></div>
        <div class="col-md-4"><a class="user-action" href="pemesanan/pemesanan.php"><span class="ic"><i class="fas fa-calendar-alt"></i></span><div><strong>Pemesanan Saya</strong><div class="text-muted small">Lihat detail dan cetak bukti booking.</div></div></a></div>
        <div class="col-md-4"><a class="user-action" href="pembayaran/pembayaran.php"><span class="ic"><i class="fas fa-receipt"></i></span><div><strong>Pembayaran</strong><div class="text-muted small">Pantau pembayaran dan sisa tagihan.</div></div></a></div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><h2 class="dashboard-panel-title">Pemesanan Terdekat</h2><p class="dashboard-panel-subtitle mb-0">Reservasi Anda yang masih aktif.</p></div>
                <a href="pemesanan/pemesanan.php" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Kode</th><th>Tipe Kamar</th><th>Kamar</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Pembayaran</th></tr></thead>
                    <tbody>
                    <?php if ($upcoming && mysqli_num_rows($upcoming) > 0): while($b=mysqli_fetch_assoc($upcoming)):
                        $ps=status_pembayaran((float)$b['total'],(float)$b['terbayar']); ?>
                        <tr>
                            <td class="fw-bold"><?= e($b['kode_booking']); ?></td>
                            <td><?= e($b['nama_tipe']); ?></td>
                            <td><?= e($b['nomor_kamar']); ?></td>
                            <td><?= tanggal_id($b['check_in']); ?></td>
                            <td><?= tanggal_id($b['check_out']); ?></td>
                            <td><span class="badge <?= status_booking_badge($b['status']); ?>"><?= status_booking_label($b['status']); ?></span></td>
                            <td><span class="badge <?= status_pembayaran_badge($ps); ?>"><?= e($ps); ?></span></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pemesanan aktif. Silakan lihat kamar yang tersedia.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dashboard-footer">Grand Dian Hotel Bumiayu · Portal Tamu</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
