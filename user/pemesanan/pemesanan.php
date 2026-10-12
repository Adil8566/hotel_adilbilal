<?php
session_start();

// Pastikan yang login adalah user/pelanggan
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') === 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';

normalize_hotel_statuses($con);
sync_room_statuses($con);

// Pemesanan dihubungkan ke akun lewat kolom id_pengguna (bukan id_tamu, karena id_tamu adalah ID tabel tamu)
$id_user = (int)($_SESSION['id_pengguna'] ?? 0);

$q = trim($_GET['q'] ?? '');
$status_filter = strtolower(trim($_GET['status'] ?? ''));
$tanggal_dari = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';

// Filter wajib: Hanya tampilkan milik tamu yang sedang login
$where = ["b.id_pengguna = {$id_user}"]; 

if ($q !== '') {
    $q_safe = mysqli_real_escape_string($con, $q);
    $where[] = "(b.kode_booking LIKE '%{$q_safe}%' OR k.nomor_kamar LIKE '%{$q_safe}%')";
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
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Ambil data tipe kamar untuk kotak pemesanan
$query_tipe = mysqli_query($con, "SELECT id_tipe, nama_tipe, harga FROM tbl_tipe_kamar ORDER BY id_tipe ASC");

// [PERBAIKAN 2]: Mengubah INNER JOIN menjadi LEFT JOIN
// Agar jika admin belum menentukan nomor kamar, pesanan tetap muncul (tidak tersembunyi)
$query_booking = mysqli_query($con, "
    SELECT
        b.id_booking, b.kode_booking, b.check_in, b.check_out, b.jumlah_tamu, b.total,
        LOWER(TRIM(b.status)) AS status_booking,
        k.id_kamar, k.nomor_kamar,
        tk.nama_tipe,
        COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking = b.id_booking),0) AS terbayar
    FROM tbl_booking b
    LEFT JOIN tbl_kamar k ON b.id_kamar = k.id_kamar
    LEFT JOIN tbl_tipe_kamar tk ON k.id_tipe = tk.id_tipe
    {$where_sql}
    ORDER BY b.id_booking DESC
");

// Menghitung statistik pemesanan
$count = [];
foreach (['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st) {
    $r = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status)) = '{$st}' AND id_pengguna = {$id_user}");
    $count[$st] = (int) (mysqli_fetch_assoc($r)['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Saya - Grand Dian Hotel</title>
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
<nav class="navbar navbar-light mantis-topbar bg-white shadow-sm">
    <div class="container-fluid">
        <a href="../index.php" class="navbar-brand text-primary fw-bold"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel Bumiayu</a>
        <div class="text-dark">
            <span class="me-3"><i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['nama'] ?? 'Tamu'); ?></span>
            <a href="../../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <?php include '../template/sidebar.php'; ?>
    
    <main class="flex-grow-1 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
            <div>
                <h3 class="mb-1">Pemesanan Saya</h3>
                <p class="text-muted mb-0">Lihat, pantau, dan kelola reservasi kamar Anda.</p>
            </div>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <?php $pesan = $_GET['pesan']; ?>
            <?php if ($pesan === 'tambah'): ?><div class="alert alert-success">Pemesanan berhasil dibuat, silakan lakukan pembayaran.</div>
            <?php elseif ($pesan === 'bayar'): ?><div class="alert alert-success">Bukti pembayaran berhasil diunggah dan sedang menunggu konfirmasi admin.</div>
            <?php elseif ($pesan === 'batal'): ?><div class="alert alert-success">Pemesanan berhasil dibatalkan.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="row g-3 mb-4 no-print">
            <div class="col-lg col-md-6">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Pending</small>
                        <h3 class="mb-0 text-warning"><?= $count['pending']; 
                        ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Dikonfirmasi</small>
                        <h3 class="mb-0 text-info">
                            <?= $count['dikonfirmasi']; 
                            ?>
                            </h3>
                        </div>
                    </div>
                </div>
            <div class="col-lg col-md-6">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Check-in</small>
                        <h3 class="mb-0 text-primary">
                            <?= $count['checkin']; 
                            ?>
                            </h3>
                        </div>
                    </div>
                </div>
            <div class="col-lg col-md-6">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Check-out</small>
                        <h3 class="mb-0 text-success">
                            <?= $count['checkout']; ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tambah Pemesanan -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white">
                <h5 class="mb-0">Tambah Pemesanan Berdasarkan Tipe Kamar</h5>
            </div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3">
                <?php
                if ($query_tipe && mysqli_num_rows($query_tipe) > 0): while ($tipe = mysqli_fetch_assoc($query_tipe)): 
                ?>
                    <div class="col">
                        <div class="card h-100 kotak-tipe border-primary">
                            <div class="card-body text-center">
                                <h6 class="fw-bold text-primary"><?= htmlspecialchars($tipe['nama_tipe']); ?></h6>
                                <div class="text-success fw-bold mb-3"><?= rupiah($tipe['harga']); ?>/malam</div>
                                <a href="tambah.php?id_tipe=<?= (int)$tipe['id_tipe']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i> Pesan</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="col-12 text-muted">Belum ada tipe kamar yang tersedia.</div>
                <?php 
                endif; 
                ?>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-header bg-white">
                <h5 class="mb-0">Filter Pemesanan</h5>
            </div>
            <div class="card-body">
                <form class="row g-2" method="GET">
                    <div class="col-lg-4">
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q); ?>" placeholder="Kode booking / nomor kamar">
                    </div>
                    <div class="col-lg-3">
                        <select name="status" class="form-select">
                            <option value="">Semua status</option>
                            <?php 
                            foreach (['pending','dikonfirmasi','checkin','checkout','dibatalkan'] as $st): ?>
                                <option value="<?= $st; ?>" <?= $status_filter === $st ? 'selected' : ''; ?>><?= status_booking_label($st); ?></option>
                            <?php 
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <input type="date" name="tanggal_dari" class="form-control" value="<?= htmlspecialchars($tanggal_dari); ?>">
                    </div>
                    <div class="col-lg-2">
                        <input type="date" name="tanggal_sampai" class="form-control" value="<?= htmlspecialchars($tanggal_sampai); ?>">
                    </div>
                    <div class="col-lg-1 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" title="Cari">
                            <i class="fas fa-search"></i>
                    </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data Pemesanan -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">Daftar Pemesanan Saya</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Tipe Kamar</th>
                            <th>Kamar</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th 
                            class="no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($query_booking && mysqli_num_rows($query_booking) > 0): 
                        $no = 1; 
                        while ($b = mysqli_fetch_assoc($query_booking)): 
                            $bayar = (float)$b['terbayar']; 
                            $sisa = max(0, (float)$b['total'] - $bayar); 
                            $ps = status_pembayaran((float)$b['total'], $bayar); 
                            $st = strtolower(trim($b['status_booking'])); 
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($b['kode_booking']); ?></td>
                            <td><?= htmlspecialchars($b['nama_tipe'] ?? 'Menunggu...'); ?></td>
                            <td><strong><?= htmlspecialchars($b['nomor_kamar'] ?: 'Belum Ditentukan'); ?></strong></td>
                            <td><?= tanggal_id($b['check_in']); ?></td>
                            <td><?= tanggal_id($b['check_out']); ?></td>
                            <td><?= rupiah($b['total']); ?></td>
                            <td>
                                <span class="badge <?= status_pembayaran_badge($ps); ?>"><?= $ps; ?></span>
                                <?php if($sisa > 0): ?><br><small class="text-danger"><?= rupiah($sisa); ?> sisa</small>
                                    <?php endif;
                                     ?>
                            </td>
                            <td>
                                <span class="badge <?= status_booking_badge($st); ?>"><?= status_booking_label($st); ?></span>
                            </td>
                            <td class="text-center no-print" style="min-width:140px">
                                
                                <a href="detail.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-info text-white mb-1" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php
                                if ($st === 'pending' || $sisa > 0): 
                                ?>
                                    <a href="pembayaran.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-success mb-1" title="Upload Pembayaran">
                                        <i class="fas fa-wallet"></i>
                                    </a>
                                <?php 
                                endif; 
                                ?>

                                <?php 
                                if ($ps === 'Lunas' || $st === 'dikonfirmasi' || $st === 'checkin' || $st === 'checkout'): 
                                ?>
                                    <a href="cetak.php?id=<?= (int)$b['id_booking']; ?>" target="_blank" class="btn btn-sm btn-secondary mb-1" title="Cetak E-Tiket">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php 
                                endif; 
                                ?>

                                <?php 
                                if ($st === 'pending'): 
                                ?>
                                    <a href="batalkan.php?id=<?= (int)$b['id_booking']; ?>
                                    " class="btn btn-sm btn-danger mb-1" title="Batalkan Pemesanan" onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php 
                                endif; 
                                ?>

                            </td>
                        </tr>
                    <?php 
                    endwhile; 
                    else:
                    ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">Anda belum memiliki riwayat pemesanan yang sesuai.</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>