<?php
session_start();

// 1. Pastikan yang login adalah user (bukan admin)
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') === 'admin') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../database/koneksi.php';

// Cek apakah ada parameter pencarian
$pencarian = "";
if (isset($_GET['cari']) && !empty(trim($_GET['cari']))) {
    $pencarian = mysqli_real_escape_string($con, trim($_GET['cari']));
    $query = mysqli_query($con, "SELECT * FROM tbl_tipe_kamar WHERE nama_tipe LIKE '%$pencarian%' OR fasilitas LIKE '%$pencarian%' ORDER BY id_tipe DESC");
} else {
    $query = mysqli_query($con, "SELECT * FROM tbl_tipe_kamar ORDER BY id_tipe DESC");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipe Kamar - Grand Dian Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-light mantis-topbar bg-white shadow-sm">
    <div class="container-fluid">
        <a href="../index.php" class="navbar-brand text-primary fw-bold">
            <i class="fas fa-hotel"></i>
            Grand Dian Hotel Bumiayu
        </a>
        <div class="text-dark">
            <span class="me-3">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars($_SESSION['nama'] ?? 'Tamu'); ?>
            </span>
            <a href="../../logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <?php include '../template/sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Tipe Kamar</h3>
                <p class="text-muted mb-0">Lihat dan pilih tipe kamar yang tersedia untuk Anda</p>
            </div>
            <!-- Tombol Tambah dihilangkan karena ini halaman user -->
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                
                <!-- Form Pencarian -->
                <form action="" method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="cari" placeholder="Cari nama tipe atau fasilitas..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($_GET['cari'])): ?>
                                <a href="index.php" class="btn btn-outline-danger" title="Reset Pencarian">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th width="60">No</th>
                                <th>Nama Tipe</th>
                                <th>Harga</th>
                                <th>Fasilitas</th>
                                <th>Keterangan</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($query) > 0) {
                            while ($data = mysqli_fetch_assoc($query)) {
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($data['nama_tipe']); ?></td>
                                <td class="text-success fw-bold">Rp <?= number_format($data['harga'], 0, ',', '.'); ?></td>
                                <td><?= nl2br(htmlspecialchars($data['fasilitas'])); ?></td>
                                <td><?= nl2br(htmlspecialchars($data['keterangan'])); ?></td>
                                <td class="text-center">
                                    <!-- Tombol Edit & Hapus diganti menjadi tombol Pesan -->
                                    <a href="../pemesanan/tambah.php?id_tipe=<?= $data['id_tipe']; ?>" class="btn btn-primary btn-sm w-100 shadow-sm">
                                        <i class="fas fa-calendar-plus"></i> Pesan
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <?= !empty($_GET['cari']) ? 'Data tipe kamar yang dicari tidak ditemukan.' : 'Belum ada data tipe kamar yang tersedia.'; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>