<?php
session_start();

if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'user') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';

normalize_hotel_statuses($con);
sync_room_statuses($con);

$q = trim($_GET['q'] ?? '');
$status_filter = strtolower(trim($_GET['status'] ?? ''));
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';

$where = [];
if ($q !== '') {
    $q_safe = mysqli_real_escape_string($con, $q);
    $where[] = "(b.kode_booking LIKE '%{$q_safe}%' OR t.nama LIKE '%{$q_safe}%' OR k.nomor_kamar LIKE '%{$q_safe}%')";
}
if ($status_filter !== '' && valid_booking_status($status_filter)) {
    $where[] = "LOWER(TRIM(b.status)) = '" . mysqli_real_escape_string($con, $status_filter) . "'";
}
if ($tanggal_dari !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_dari)) {
    $where[] = "b.check_in >= '" . mysqli_real_escape_string($con, $tanggal_dari) . "'";
}
if ($tanggal_sampai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_sampai)) {
    $where[] = "b.check_out <= '" . mysqli_real_escape_string($con, $tanggal_sampai) . "'";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 1. QUERY TIPE KAMAR
// Kolom 'harga' di sini diposisikan sebagai Harga Dasar / Weekday Rate
$query_tipe = mysqli_query($con, "
    SELECT 
        tk.id_tipe, 
        tk.nama_tipe, 
        tk.harga,
        tk.foto, 
        COUNT(k.id_kamar) AS total_kamar,
        SUM(CASE WHEN LOWER(TRIM(k.status)) = 'terisi' THEN 1 ELSE 0 END) AS kamar_terisi,
        SUM(CASE WHEN LOWER(TRIM(k.status)) = 'tersedia' THEN 1 ELSE 0 END) AS kamar_tersedia
    FROM tbl_tipe_kamar tk
    LEFT JOIN tbl_kamar k ON tk.id_tipe = k.id_tipe
    GROUP BY tk.id_tipe, tk.nama_tipe, tk.harga, tk.foto
    ORDER BY tk.id_tipe ASC
");

$query_kamar = mysqli_query($con, "
    SELECT k.nomor_kamar, k.status, tk.nama_tipe
    FROM tbl_kamar k
    INNER JOIN tbl_tipe_kamar tk ON k.id_tipe = tk.id_tipe
    ORDER BY k.nomor_kamar ASC
");

$query_booking = mysqli_query($con, "
    SELECT
        b.id_booking, b.kode_booking, b.check_in, b.check_out, b.jumlah_tamu, b.total,
        LOWER(TRIM(b.status)) AS status_booking,
        t.nama AS nama_tamu,
        k.id_kamar, k.nomor_kamar,
        tk.nama_tipe,
        COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking = b.id_booking),0) AS terbayar
    FROM tbl_booking b
    INNER JOIN tbl_tamu t ON b.id_tamu = t.id_tamu
    INNER JOIN tbl_kamar k ON b.id_kamar = k.id_kamar
    INNER JOIN tbl_tipe_kamar tk ON k.id_tipe = tk.id_tipe
    {$where_sql}
    ORDER BY b.id_booking DESC
");

$count = [];
foreach (['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st) {
    $r = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status)) = '{$st}'");
    $count[$st] = (int) (mysqli_fetch_assoc($r)['total'] ?? 0);
}

$today_in = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status)) IN ('dikonfirmasi','checkin') AND check_in = CURDATE()");
$today_out = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status)) = 'checkin' AND check_out = CURDATE()");
$total_checkin_hari_ini = (int) (mysqli_fetch_assoc($today_in)['total'] ?? 0);
$total_checkout_hari_ini = (int) (mysqli_fetch_assoc($today_out)['total'] ?? 0);

// Menangkap ID tamu dari URL
$id_tamu_get = isset($_GET['id_tamu']) ? (int)$_GET['id_tamu'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - Grand Dian Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">

    <style>
        .kotak-tipe { border-radius: 12px; transition: .2s; }
        .kotak-tipe:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,.08); }
        .stat-card { border: 0; border-radius: 12px; }
        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="../index.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Admin Grand Dian Hotel Bumiayu</a>
        <div class="text-white"><span class="me-3"><i class="fas fa-user me-1"></i><?= e($_SESSION['nama'] ?? 'Admin'); ?></span><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div>
    </div>
</nav>
<div class="d-flex">
    <?php include '../template/sidebar.php'; ?>
    <main class="flex-grow-1 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
            <div><h3 class="mb-1">Pemesanan Hotel</h3><p class="text-muted mb-0">Pengelolaan booking, verifikasi, check-in, check-out, dan pembayaran.</p></div>
            <a href="../laporan/index.php" class="btn btn-outline-primary"><i class="fas fa-chart-column me-1"></i>Laporan</a>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <?php $pesan = $_GET['pesan']; ?>
            <?php if ($pesan === 'tambah'): ?><div class="alert alert-success">Pemesanan berhasil ditambahkan.
                
            </div>
            <?php elseif ($pesan === 'edit'): ?><div class="alert alert-success">Pemesanan berhasil diubah.

            </div>
            <?php elseif ($pesan === 'hapus'): ?><div class="alert alert-success">Pemesanan berhasil dihapus.

            </div>
            <?php elseif ($pesan === 'verifikasi'): ?><div class="alert alert-success">Pemesanan berhasil dikonfirmasi.

            </div>
            <?php elseif ($pesan === 'checkin'): ?><div class="alert alert-success">Tamu berhasil melakukan check-in.

            </div>
            <?php elseif ($pesan === 'checkout'): ?><div class="alert alert-success">Tamu berhasil melakukan check-out dan kamar kembali tersedia.</div>
            <?php elseif ($pesan === 'batal'): ?><div class="alert alert-success">Pemesanan berhasil dibatalkan.

            </div>
            <?php elseif ($pesan === 'kamar_tidak_tersedia'): ?><div class="alert alert-danger">Tidak ada kamar yang tersedia pada rentang tanggal tersebut.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($id_tamu_get > 0): ?>
            <div class="alert alert-info border-info shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i> Anda sedang memproses pemesanan untuk tamu. Silakan <strong>Pilih Tipe Kamar</strong> di bawah ini untuk melanjutkan.
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4 no-print">
            <div class="col-lg col-md-4"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Pending</small><h3 class="mb-0 text-warning"><?= $count['pending']; ?></h3>
        </div>
    </div>
</div>
            <div class="col-lg col-md-4"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Dikonfirmasi</small><h3 class="mb-0 text-info"><?= $count['dikonfirmasi']; ?></h3>
        </div>
    </div>
        </div>
            <div class="col-lg col-md-4"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Check-in</small><h3 class="mb-0 text-primary"><?= $count['checkin']; ?></h3>
        </div>
    </div>
</div>
            <div class="col-lg col-md-4"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Check-out</small><h3 class="mb-0 text-success"><?= $count['checkout']; ?></h3>
        </div>
        </div>
        </div>
            <div class="col-lg col-md-4"><div class="card stat-card shadow-sm"><div class="card-body"><small class="text-muted">Check-in / Check-out hari ini</small><h6 class="mb-0"><?= $total_checkin_hari_ini; ?> / <?= $total_checkout_hari_ini; ?></h6>
        </div>
    </div>
</div>
        </div>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white"><h5 class="mb-0">Tambah Pemesanan Berdasarkan Tipe Kamar</h5></div>
            <div class="card-body"><div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3">
                
                <?php 
                if ($query_tipe && mysqli_num_rows($query_tipe) > 0): 
                    while ($tipe = mysqli_fetch_assoc($query_tipe)): 
                        $total = (int)$tipe['total_kamar'];
                        $terisi = (int)$tipe['kamar_terisi'];
                        $tersedia = (int)$tipe['kamar_tersedia'];
                        
                        // Dianggap penuh jika tidak ada kamar yang statusnya 'tersedia'
                        $is_full = ($tersedia == 0);
                ?>
                    <div class="col">
                        <div class="card h-100 kotak-tipe <?= $is_full ? 'border-secondary opacity-75' : 'border-primary' ?>">
                            
                            <!-- BAGIAN YANG DITAMBAHKAN: Foto Kamar -->
                            <?php 
                                // Menyesuaikan gambar secara otomatis berdasarkan nama kamar
                                $nama_tipe = strtolower($tipe['nama_tipe']);
                                $file_foto = 'hotel1.jpg'; // Gambar default jika tidak ada yang cocok
                                
                                if (strpos($nama_tipe, 'moderate') !== false) {
                                    $file_foto = 'hotel1.jpg';
                                } elseif (strpos($nama_tipe, 'superior') !== false) {
                                    $file_foto = 'hotel2.jpg';
                                } elseif (strpos($nama_tipe, 'deluxe') !== false) {
                                    $file_foto = 'hotel3.jpg';
                                }
                            ?>
                            <img src="../../assets_volt/img/<?= $file_foto; ?>" 
                                 class="card-img-top" 
                                 alt="<?= e($tipe['nama_tipe']); ?>" 
                                 style="height: 160px; object-fit: cover; border-top-left-radius: 11px; border-top-right-radius: 11px;"
                                 onerror="this.src='../../assets_volt/img/hotel1.jpg';">
                            <!-- AKHIR PENAMBAHAN -->

                            <div class="card-body text-center">
                                <h6 class="fw-bold <?= $is_full ? 'text-secondary' : 'text-primary' ?>"><?= e($tipe['nama_tipe']); ?></h6>
                                
                                <!-- BAGIAN YANG DIUBAH: Penambahan Teks "Mulai dari" -->
                                <div class="<?= $is_full ? 'text-secondary' : 'text-success' ?> fw-bold mb-2">
                                    <small class="text-muted fw-normal">Mulai dari</small><br>
                                    <?= rupiah($tipe['harga']); ?>/malam
                                </div>
                                
                                <!-- Indikator Kamar -->
                                <div class="small mb-3 <?= $is_full ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    Terisi: <?= $terisi; ?>/<?= $total; ?> Kamar
                                    <?php if($is_full): ?><br>(Penuh)<?php endif; ?>
                                </div>
                                
                                <!-- Tombol Pesan Dinamis -->
                                <?php if ($is_full): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-ban me-1"></i>Penuh
                                    </button>
                                <?php else: ?>
                                    <a href="tambah.php?id_tipe=<?= (int)$tipe['id_tipe']; ?><?= $id_tamu_get > 0 ? '&id_tamu='.$id_tamu_get : ''; ?>" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-1"></i>Pesan
                                    </a>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?><div class="col-12 text-muted">Belum ada tipe kamar.</div><?php endif; ?>

            </div></div>
        </div>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white"><h5 class="mb-0">Filter Pemesanan</h5></div>
            <div class="card-body"><form class="row g-2" method="GET">
                <div class="col-lg-4"><input type="text" name="q" class="form-control" value="<?= e($q); ?>" placeholder="Kode booking / nama tamu / nomor kamar"></div>
                <div class="col-lg-2"><select name="status" class="form-select"><option value="">Semua status</option><?php foreach (['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st): ?><option value="<?= $st; ?>" <?= $status_filter === $st ? 'selected' : ''; ?>><?= status_booking_label($st); ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2"><input type="date" name="tanggal_dari" class="form-control" value="<?= e($tanggal_dari); ?>"></div>
                <div class="col-lg-2"><input type="date" name="tanggal_sampai" class="form-control" value="<?= e($tanggal_sampai); ?>"></div>
                <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="fas fa-search"></i></button><a href="pemesanan.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i></a></div>
            </form></div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h5 class="mb-0">Daftar Pemesanan</h5></div>
            <div class="card-body"><div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Kode</th><th>Tamu</th><th>Kamar</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Pembayaran</th><th>Status</th><th class="no-print">Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($query_booking && mysqli_num_rows($query_booking) > 0): $no = 1; while ($b = mysqli_fetch_assoc($query_booking)): $bayar = (float)$b['terbayar']; $sisa = max(0, (float)$b['total'] - $bayar); $ps = status_pembayaran((float)$b['total'], $bayar); $st = strtolower(trim($b['status_booking'])); ?>
                        <tr>
                            <td><?= $no++; ?></td><td class="fw-bold"><?= e($b['kode_booking']); ?></td><td><?= e($b['nama_tamu']); ?></td>
                            <td><strong><?= e($b['nomor_kamar']); ?></strong><br><small class="text-muted"><?= e($b['nama_tipe']); ?></small></td>
                            <td><?= tanggal_id($b['check_in']); ?></td><td><?= tanggal_id($b['check_out']); ?></td>
                            <td><?= rupiah($b['total']); ?></td>
                            <td><span class="badge <?= status_pembayaran_badge($ps); ?>"><?= $ps; ?></span><br><small><?= rupiah($sisa); ?> sisa</small></td>
                            <td><span class="badge <?= status_booking_badge($st); ?>"><?= status_booking_label($st); ?></span></td>
                            <td class="text-center no-print" style="min-width:180px">
                                <!-- 1. Tombol Detail -->
                                <a href="detail.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-primary mb-1" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <!-- 2. Tombol Konfirmasi Pembayaran -->
                                <?php if ($ps !== 'Lunas' || $st === 'pending'): ?>
                                    <form action="konfirmasi_pembayaran.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= (int)$b['id_booking']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success mb-1" title="Konfirmasi Pembayaran" onclick="return confirm('Konfirmasi pembayaran dari user? Status otomatis menjadi Pending.');">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- 3. Tombol Edit -->
                                <?php if (!in_array($st, ['checkout','dibatalkan'], true)): ?>
                                    <a href="edit.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-warning mb-1" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- 4. Tombol Hapus -->
                                <a href="hapus.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-danger mb-1" title="Hapus" onclick="return confirm('Yakin menghapus data pemesanan ini? Data pembayaran terkait juga akan dihapus.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?><tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data pemesanan yang sesuai filter.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>