<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../database/koneksi.php';
require_once '../database/fungsi_hotel.php';
normalize_hotel_statuses($con);
sync_room_statuses($con);

function one(mysqli $con, string $sql): int {
    $q = mysqli_query($con, $sql);
    $d = $q ? mysqli_fetch_assoc($q) : null;
    return (int)($d['total'] ?? 0);
}

$total_tipe_kamar = one($con, "SELECT COUNT(*) AS total FROM tbl_tipe_kamar");
$total_kamar = one($con, "SELECT COUNT(*) AS total FROM tbl_kamar");
$total_tamu = one($con, "SELECT COUNT(*) AS total FROM tbl_tamu");
$total_booking = one($con, "SELECT COUNT(*) AS total FROM tbl_booking");
$kamar_tersedia = one($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE LOWER(TRIM(status))='tersedia'");
$kamar_terisi = one($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE LOWER(TRIM(status))='terisi'");
$kamar_maintenance = one($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE LOWER(TRIM(status))='maintenance'");
$pending = one($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status))='pending'");
$checkin_hari_ini = one($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status)) IN ('dikonfirmasi','checkin') AND check_in=CURDATE()");
$checkout_hari_ini = one($con, "SELECT COUNT(*) AS total FROM tbl_booking WHERE LOWER(TRIM(status))='checkin' AND check_out=CURDATE()");

$q_month = mysqli_query($con, "SELECT COALESCE(SUM(jumlah_bayar),0) AS total FROM tbl_pembayaran WHERE YEAR(tanggal_bayar)=YEAR(CURDATE()) AND MONTH(tanggal_bayar)=MONTH(CURDATE())");
$pendapatan_bulan = (float)(mysqli_fetch_assoc($q_month)['total'] ?? 0);

$q_out = mysqli_query($con, "SELECT COALESCE(SUM(s.total)-COALESCE(s.terbayar,0),0) AS total FROM (SELECT b.id_booking,b.total,(SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking) AS terbayar FROM tbl_booking b WHERE LOWER(TRIM(b.status)) <> 'dibatalkan') s");
$tagihan_belum_lunas = max(0, (float)(mysqli_fetch_assoc($q_out)['total'] ?? 0));

$upcoming = mysqli_query($con, "SELECT b.kode_booking,t.nama,k.nomor_kamar,b.check_in,b.check_out,LOWER(TRIM(b.status)) status FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar WHERE LOWER(TRIM(b.status)) IN ('pending','dikonfirmasi','checkin') AND b.check_out >= CURDATE() ORDER BY b.check_in ASC LIMIT 8");

$available_pct = $total_kamar > 0 ? round(($kamar_tersedia / $total_kamar) * 100) : 0;
$occupied_pct = $total_kamar > 0 ? round(($kamar_terisi / $total_kamar) * 100) : 0;
$maintenance_pct = $total_kamar > 0 ? round(($kamar_maintenance / $total_kamar) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Grand Dian Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets_mantis/css/mantis-hotel.css">
</head>
<body>
<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand"><i class=""></i>Grand Dian Hotel Bumiayu</a>
        <div>
            <span class="me-3 small"><i class="fas fa-user me-1"></i><?= e($_SESSION['nama'] ?? 'Admin'); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <?php include 'template/sidebar.php'; ?>
    <main class="flex-grow-1">
        <div class="page-heading-mantis">
            <div>
                <h1 class="page-title-mantis">Dashboard</h1>
                <p class="page-subtitle-mantis">Ringkasan operasional hotel dan aktivitas reservasi.</p>
            </div>
        </div>

        <section class="mantis-welcome">
            <div>
                <h2>Selamat datang, <?= e($_SESSION['nama'] ?? 'Admin'); ?></h2>
               
            </div>
            <div class="mantis-welcome-date">
                <i class="far fa-calendar me-1"></i><?= date('d/m/Y'); ?><br>
                <span><?= date('H:i'); ?> WIB</span>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="stat-top">
                        <div>
                            <div class="stat-label">Total Kamar</div>
                            <div class="stat-value"><?= $total_kamar; ?></div>
                            <div class="stat-note"><?= $total_tipe_kamar; ?> tipe kamar terdaftar</div>
                        </div>
                        <div class="stat-icon-mantis"><i class="fas fa-bed"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="stat-top">
                        <div>
                            <div class="stat-label">Total Tamu</div>
                            <div class="stat-value"><?= $total_tamu; ?></div>
                            <div class="stat-note">Data tamu tersimpan</div>
                        </div>
                        <div class="stat-icon-mantis success"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="stat-top">
                        <div>
                            <div class="stat-label">Total Booking</div>
                            <div class="stat-value"><?= $total_booking; ?></div>
                            <div class="stat-note"><?= $pending; ?> booking masih pending</div>
                        </div>
                        <div class="stat-icon-mantis info"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="stat-top">
                        <div>
                            <div class="stat-label">Pendapatan Bulan Ini</div>
                            <div class="stat-value" style="font-size:20px;"><?= rupiah($pendapatan_bulan); ?></div>
                            <div class="stat-note">Sisa tagihan: <?= rupiah($tagihan_belum_lunas); ?></div>
                        </div>
                        <div class="stat-icon-mantis warning"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="dashboard-panel-title">Booking Terdekat</h2>
                                <p class="dashboard-panel-subtitle">Reservasi aktif yang mendekati tanggal menginap.</p>
                            </div>
                            <!-- Search Bar & Button ditambahkan di sini -->
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" id="cariBooking" class="form-control form-control-sm" placeholder="Cari kode/tamu...">
                                <a href="pemesanan/pemesanan.php" class="btn btn-primary btn-sm text-nowrap">Lihat Semua</a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover dashboard-table align-middle">
                                <thead>
                                    <tr><th>Kode</th><th>Tamu</th><th>Kamar</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                <?php if ($upcoming && mysqli_num_rows($upcoming) > 0): while ($b = mysqli_fetch_assoc($upcoming)): ?>
                                    <tr>
                                        <td class="code"><?= e($b['kode_booking']); ?></td>
                                        <td><?= e($b['nama']); ?></td>
                                        <td><?= e($b['nomor_kamar']); ?></td>
                                        <td><?= tanggal_id($b['check_in']); ?></td>
                                        <td><?= tanggal_id($b['check_out']); ?></td>
                                        <td><span class="badge <?= status_booking_badge($b['status']); ?>"><?= status_booking_label($b['status']); ?></span></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada booking terdekat.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="dashboard-panel-title">Status Kamar</h2>
                        <p class="dashboard-panel-subtitle mb-4">Komposisi kondisi kamar saat ini.</p>

                        <div class="room-progress">
                            <div class="room-progress-head"><span>Tersedia</span><strong><?= $kamar_tersedia; ?> kamar</strong></div>
                            <div class="room-progress-bar"><div class="room-progress-fill success" style="width:<?= $available_pct; ?>%"></div></div>
                        </div>
                        <div class="room-progress">
                            <div class="room-progress-head"><span>Terisi</span><strong><?= $kamar_terisi; ?> kamar</strong></div>
                            <div class="room-progress-bar"><div class="room-progress-fill" style="width:<?= $occupied_pct; ?>%"></div></div>
                        </div>
                        <div class="room-progress">
                            <div class="room-progress-head"><span>Maintenance</span><strong><?= $kamar_maintenance; ?> kamar</strong></div>
                            <div class="room-progress-bar"><div class="room-progress-fill warning" style="width:<?= $maintenance_pct; ?>%"></div></div>
                        </div>

                        <hr class="my-4" style="border-color:#e6ebf1; opacity:1;">
                        <div class="d-flex justify-content-between small mb-2"><span class="text-muted">Check-in hari ini</span><strong><?= $checkin_hari_ini; ?></strong></div>
                        <div class="d-flex justify-content-between small"><span class="text-muted">Check-out hari ini</span><strong><?= $checkout_hari_ini; ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="dashboard-panel-title">Aksi Cepat</h2>
                        <p class="dashboard-panel-subtitle mb-3">Menu yang paling sering digunakan dalam operasional hotel.</p>
                        <div class="row g-2">
                            <div class="col-sm-6"><a class="quick-link" href="pemesanan/pemesanan.php"><i class="fas fa-calendar-plus"></i><div><strong>Tambah Pemesanan</strong><span>Buat reservasi baru</span></div></a></div>
                            <div class="col-sm-6"><a class="quick-link" href="kamar/tambah.php"><i class="fas fa-bed"></i><div><strong>Tambah Kamar</strong><span>Masukkan kamar baru</span></div></a></div>
                            <div class="col-sm-6"><a class="quick-link" href="tamu/tambah.php"><i class="fas fa-user-plus"></i><div><strong>Tambah Tamu</strong><span>Registrasi data tamu</span></div></a></div>
                            <div class="col-sm-6"><a class="quick-link" href="pembayaran/pembayaran.php"><i class="fas fa-money-bill-wave"></i><div><strong>Kelola Pembayaran</strong><span>Lihat transaksi dan tagihan</span></div></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="dashboard-panel-title">Ringkasan Operasional</h2>
                        <p class="dashboard-panel-subtitle mb-3">Angka penting untuk hari ini.</p>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-muted">Pending booking</span><span class="badge bg-warning"><?= $pending; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-muted">Kamar tersedia</span><span class="badge bg-success"><?= $kamar_tersedia; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-muted">Kamar terisi</span><span class="badge bg-primary"><?= $kamar_terisi; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="small text-muted">Maintenance</span><span class="badge bg-danger"><?= $kamar_maintenance; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Grand Dian Hotel Bumiayu &middot; Administrasi</div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script untuk fitur pencarian tabel -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('cariBooking');
    const tableRows = document.querySelectorAll('.dashboard-table tbody tr');

    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const keyword = this.value.toLowerCase();

            tableRows.forEach(function(row) {
                // Lewati baris jika isinya pesan kosong
                const firstCell = row.querySelector('td');
                if (firstCell && firstCell.colSpan == 6) return;

                // Ambil seluruh teks dalam satu baris
                const rowData = row.textContent.toLowerCase();

                // Sembunyikan baris jika tidak cocok dengan kata kunci
                if (rowData.includes(keyword)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
</body>
</html>