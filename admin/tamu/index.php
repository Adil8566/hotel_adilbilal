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
    $query = mysqli_query($con, "
        SELECT *
        FROM tbl_tamu
        WHERE nama LIKE '%$pencarian%' 
           OR no_identitas LIKE '%$pencarian%' 
           OR no_hp LIKE '%$pencarian%' 
           OR email LIKE '%$pencarian%' 
           OR alamat LIKE '%$pencarian%'
        ORDER BY id_tamu DESC
    ");
} else {
    $query = mysqli_query($con, "
        SELECT *
        FROM tbl_tamu
        ORDER BY id_tamu DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tamu - Hotel</title>
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
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="d-flex">

    <?php include '../template/sidebar.php'; ?>

    <div class="flex-grow-1 p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Data Tamu</h3>
                
            </div>

            <a href="tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Tamu
            </a>
        </div>

        <?php if (isset($_GET['pesan'])) { ?>
            <?php if ($_GET['pesan'] == 'tambah') { ?>
                <div class="alert alert-success">Data tamu berhasil ditambahkan.</div>
            <?php } elseif ($_GET['pesan'] == 'edit') { ?>
                <div class="alert alert-success">Data tamu berhasil diedit.</div>
            <?php } elseif ($_GET['pesan'] == 'hapus') { ?>
                <div class="alert alert-success">Data tamu berhasil dihapus.</div>
            <?php } ?>
        <?php } ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                
                <!-- Form Pencarian -->
                <form action="" method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="cari" placeholder="Cari nama, no identitas, HP, dll..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
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
                                <th>Nama Tamu</th>
                                <th>No. Identitas</th>
                                <th>No. HP</th>
                                <th>Email</th>
                                <th>Alamat</th>
                                <!-- Lebar kolom aksi diperbesar agar muat 3 tombol -->
                                <th width="280">Aksi</th>
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
                                <td><?= htmlspecialchars($data['nama']); ?></td>
                                <td><?= htmlspecialchars($data['no_identitas']); ?></td>
                                <td><?= htmlspecialchars($data['no_hp']); ?></td>
                                <td><?= htmlspecialchars($data['email']); ?></td>
                                <td><?= nl2br(htmlspecialchars($data['alamat'])); ?></td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <a href="edit.php?id=<?= $data['id_tamu']; ?>" class="btn btn-warning btn-sm mb-1">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <!-- Tombol Hapus -->
                                    <a href="hapus.php?id=<?= $data['id_tamu']; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Yakin ingin menghapus data tamu ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                    <!-- TAMBAHAN: Tombol Pesan Kamar yang mengarah ke folder pemesanan -->
                                    <!-- TAMBAHAN: Tombol Pesan Kamar -->
                                    <a href="../pemesanan/pemesanan.php?id_tamu=<?= $data['id_tamu']; ?>" class="btn btn-info btn-sm text-white mb-1">
                                        <i class="fas fa-bed"></i> Pesan Kamar
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <?= !empty($_GET['cari']) ? 'Data tamu yang dicari tidak ditemukan.' : 'Belum ada data tamu.'; ?>
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