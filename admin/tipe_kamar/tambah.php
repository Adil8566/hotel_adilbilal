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

if (isset($_POST['btn-tambah'])) {

    $nama_tipe  = trim($_POST['nama_tipe']);
    $harga      = trim($_POST['harga']);
    $fasilitas  = trim($_POST['fasilitas']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama_tipe == "" || $harga == "") {

        echo "<script>
                alert('Nama tipe dan harga wajib diisi!');
                window.location='tambah.php';
              </script>";

        exit;
    }

    $nama_tipe  = mysqli_real_escape_string($con, $nama_tipe);
    $harga      = mysqli_real_escape_string($con, $harga);
    $fasilitas  = mysqli_real_escape_string($con, $fasilitas);
    $keterangan = mysqli_real_escape_string($con, $keterangan);

    $query = mysqli_query($con, "
        INSERT INTO tbl_tipe_kamar
        (nama_tipe, harga, fasilitas, keterangan)
        VALUES
        ('$nama_tipe', '$harga', '$fasilitas', '$keterangan')
    ");

    if ($query) {

        header("Location: index.php?pesan=tambah");
        exit;

    } else {

        echo "Data gagal disimpan: " . mysqli_error($con);
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Tipe Kamar</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">


</head>

<body class="bg-light">

<nav class="navbar navbar-light mantis-topbar">

    <div class="container-fluid">

        <a href="../index.php"
           class="navbar-brand">

            <i class="fas fa-hotel"></i>
            Hotel Admin

        </a>

        <a href="../../logout.php"
           class="btn btn-danger btn-sm">

            Logout

        </a>

    </div>

</nav>


<div class="d-flex">

    <?php include '../template/sidebar.php'; ?>


    <div class="flex-grow-1 p-4">

        <div class="card shadow-sm border-0">

            <div class="card-header">

                <h5 class="mb-0">
                    Tambah Tipe Kamar
                </h5>

            </div>


            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Nama Tipe Kamar
                        </label>

                        <input type="text"
                               name="nama_tipe"
                               class="form-control"
                               placeholder="Contoh: Deluxe"
                               required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Harga per Malam
                        </label>

                        <input type="number"
                               name="harga"
                               class="form-control"
                               placeholder="Contoh: 350000"
                               min="0"
                               required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Fasilitas
                        </label>

                        <textarea name="fasilitas"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Contoh: AC, TV, WiFi, Kamar Mandi"></textarea>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Keterangan
                        </label>

                        <textarea name="keterangan"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Keterangan tipe kamar"></textarea>

                    </div>


                    <div class="d-flex gap-2">

                        <button type="submit"
                                name="btn-tambah"
                                class="btn btn-primary">

                            <i class="fas fa-save"></i>
                            Simpan

                        </button>

                        <a href="index.php"
                           class="btn btn-secondary">

                            Kembali

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

</body>
</html>