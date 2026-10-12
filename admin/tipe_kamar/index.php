<?php

session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION['peran'] != 'admin') {
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
    <title>Tipe Kamar - Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">

</head>

<body class="bg-light">

<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-hotel"></i>
            Hotel Admin
        </a>
        <div class="text-white">
            <span class="me-3">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars($_SESSION['nama']); ?>
            </span>
            <a href="../../logout.php" class="btn btn-danger btn-sm">
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
                
            </div>
            <a href="tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Tipe Kamar
            </a>
        </div>

        <?php if (isset($_GET['pesan'])) { ?>
            <?php if ($_GET['pesan'] == 'tambah') { ?>
                <div class="alert alert-success">Data tipe kamar berhasil ditambahkan.</div>
            <?php } elseif ($_GET['pesan'] == 'edit') { ?>
                <div class="alert alert-success">Data tipe kamar berhasil diedit.</div>
            <?php } elseif ($_GET['pesan'] == 'hapus') { ?>
                <div class="alert alert-success">Data tipe kamar berhasil dihapus.</div>
            <?php } ?>
        <?php } ?>

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
                                <th width="160">Aksi</th>
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
                                <td><?= htmlspecialchars($data['nama_tipe']); ?></td>
                                <td>Rp <?= number_format($data['harga'], 0, ',', '.'); ?></td>
                                <td><?= nl2br(htmlspecialchars($data['fasilitas'])); ?></td>
                                <td><?= nl2br(htmlspecialchars($data['keterangan'])); ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $data['id_tipe']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $data['id_tipe']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <?= !empty($_GET['cari']) ? 'Data tipe kamar yang dicari tidak ditemukan.' : 'Belum ada data tipe kamar.'; ?>
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

</body>
</html>