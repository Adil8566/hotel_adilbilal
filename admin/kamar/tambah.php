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

$query_tipe = mysqli_query($con, "
    SELECT *
    FROM tbl_tipe_kamar
    ORDER BY nama_tipe ASC
");


if (isset($_POST['btn-tambah'])) {

    $nomor_kamar = trim($_POST['nomor_kamar']);
    $id_tipe     = (int) $_POST['id_tipe'];
    $status      = $_POST['status'];

    if ($nomor_kamar == "" || $id_tipe == 0) {

        echo "<script>
                alert('Nomor kamar dan tipe kamar wajib diisi!');
              </script>";

    } else {

        $nomor_kamar = mysqli_real_escape_string($con, $nomor_kamar);
        $status      = mysqli_real_escape_string($con, $status);

        // Cek nomor kamar
        $cek = mysqli_query($con, "
            SELECT *
            FROM tbl_kamar
            WHERE nomor_kamar = '$nomor_kamar'
        ");

        if (mysqli_num_rows($cek) > 0) {

            echo "<script>
                    alert('Nomor kamar sudah digunakan!');
                  </script>";

        } else {

            $query = mysqli_query($con, "
                INSERT INTO tbl_kamar
                (nomor_kamar, id_tipe, status)
                VALUES
                ('$nomor_kamar', '$id_tipe', '$status')
            ");

            if ($query) {

                header("Location: index.php?pesan=tambah");
                exit;

            } else {

                echo "Data gagal disimpan: " . mysqli_error($con);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Kamar</title>

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
                    Tambah Kamar
                </h5>

            </div>


            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Nomor Kamar
                        </label>

                        <input type="text"
                               name="nomor_kamar"
                               class="form-control"
                               placeholder="Contoh: 101"
                               required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Tipe Kamar
                        </label>

                        <select name="id_tipe"
                                class="form-select"
                                required>

                            <option value="">
                                -- Pilih Tipe Kamar --
                            </option>

                            <?php while ($tipe = mysqli_fetch_assoc($query_tipe)) { ?>

                                <option value="<?= $tipe['id_tipe']; ?>">

                                    <?= htmlspecialchars($tipe['nama_tipe']); ?>

                                    - Rp
                                    <?= number_format($tipe['harga'], 0, ',', '.'); ?>

                                </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select"
                                required>

                            <option value="tersedia">
                                Tersedia
                            </option>

                            <option value="terisi">
                                Terisi
                            </option>

                            <option value="maintenance">
                                Maintenance
                            </option>

                        </select>

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