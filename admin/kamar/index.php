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

// Menyiapkan array penampung kondisi WHERE dinamis
$kondisi = [];

// 1. Cek apakah ada pengiriman ID Tipe dari filter
if (isset($_GET['id_tipe']) && $_GET['id_tipe'] != '') {
    $id_tipe_diklik = mysqli_real_escape_string($con, $_GET['id_tipe']);
    $kondisi[] = "tbl_kamar.id_tipe = '$id_tipe_diklik'";
}

// 2. Cek apakah ada input pencarian (berdasarkan nomor kamar, nama tipe, atau status)
if (isset($_GET['cari']) && !empty(trim($_GET['cari']))) {
    $cari = mysqli_real_escape_string($con, trim($_GET['cari']));
    $kondisi[] = "(tbl_kamar.nomor_kamar LIKE '%$cari%' OR tbl_tipe_kamar.nama_tipe LIKE '%$cari%' OR tbl_kamar.status LIKE '%$cari%')";
}

// Gabungkan kondisi WHERE jika ada
$sql_where = "";
if (count($kondisi) > 0) {
    $sql_where = " WHERE " . implode(" AND ", $kondisi);
}

// 3. QUERY DATABASE UTAMA
$query = mysqli_query($con, "
    SELECT 
        tbl_kamar.id_kamar,
        tbl_kamar.nomor_kamar,
        tbl_kamar.status,
        tbl_tipe_kamar.nama_tipe,
        tbl_tipe_kamar.harga
    FROM tbl_kamar
    INNER JOIN tbl_tipe_kamar
        ON tbl_kamar.id_tipe = tbl_tipe_kamar.id_tipe
    $sql_where
    ORDER BY tbl_kamar.nomor_kamar ASC
");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kamar - Hotel</title>

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
                <h3>Data Kamar</h3>
                
            </div>

            <div>
                <!-- Tombol Reset Filter Tipe -->
                <?php if (isset($_GET['id_tipe']) && $_GET['id_tipe'] != '') { ?>
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-sync"></i> Tampilkan Semua Kamar
                    </a>
                <?php } ?>

                <a href="tambah.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Kamar
                </a>
            </div>
        </div>

        <?php if (isset($_GET['pesan'])) { ?>
            <?php if ($_GET['pesan'] == 'tambah') { ?>
                <div class="alert alert-success">Data kamar berhasil ditambahkan.</div>
            <?php } elseif ($_GET['pesan'] == 'edit') { ?>
                <div class="alert alert-success">Data kamar berhasil diedit.</div>
            <?php } elseif ($_GET['pesan'] == 'hapus') { ?>
                <div class="alert alert-success">Data kamar berhasil dihapus.</div>
            <?php } elseif ($_GET['pesan'] == 'gagal_hapus') { ?>
                <div class="alert alert-danger">Kamar tidak dapat dihapus karena masih digunakan.</div>
            <?php } ?>
        <?php } ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                
                <!-- Form Pencarian -->
                <form action="" method="GET" class="row g-3 mb-4">
                    <!-- Jika sedang dalam mode filter id_tipe, pertahankan nilainya di form pencarian -->
                    <?php if (isset($_GET['id_tipe'])): ?>
                        <input type="hidden" name="id_tipe" value="<?= htmlspecialchars($_GET['id_tipe']); ?>">
                    <?php endif; ?>

                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="cari" placeholder="Cari nomor kamar, tipe, status..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($_GET['cari'])): ?>
                                <a href="index.php<?= isset($_GET['id_tipe']) ? '?id_tipe='.$_GET['id_tipe'] : ''; ?>" class="btn btn-outline-danger" title="Reset Pencarian">
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
                                <th>Nomor Kamar</th>
                                <th>Tipe Kamar</th>
                                <th>Harga</th>
                                <th>Status</th>
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
                                <td><?= htmlspecialchars($data['nomor_kamar']); ?></td>
                                <td><?= htmlspecialchars($data['nama_tipe']); ?></td>
                                <td>Rp <?= number_format($data['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($data['status'] == 'tersedia') { ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php } elseif ($data['status'] == 'terisi') { ?>
                                        <span class="badge bg-danger">Terisi</span>
                                    <?php } else { ?>
                                        <span class="badge bg-warning text-dark">Maintenance</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?= $data['id_kamar']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $data['id_kamar']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kamar ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Data kamar tidak ditemukan.</td>
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