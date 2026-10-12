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

    $nama         = trim($_POST['nama']);
    $no_identitas = trim($_POST['no_identitas']);
    $no_hp        = trim($_POST['no_hp']);
    $email        = trim($_POST['email']);
    $alamat       = trim($_POST['alamat']);

    if ($nama == "") {

        echo "<script>
                alert('Nama tamu wajib diisi!');
              </script>";

    } elseif ($no_identitas != "" && strlen($no_identitas) > 16) {

        echo "<script>
                alert('No. Identitas maksimal 16 digit!');
              </script>";

    } elseif ($no_hp != "" && strlen($no_hp) > 13) {

        echo "<script>
                alert('No. HP maksimal 13 digit!');
              </script>";

    } else {

        $nama         = mysqli_real_escape_string($con, $nama);
        $no_identitas = mysqli_real_escape_string($con, $no_identitas);
        $no_hp        = mysqli_real_escape_string($con, $no_hp);
        $email        = mysqli_real_escape_string($con, $email);
        $alamat       = mysqli_real_escape_string($con, $alamat);

        $query = mysqli_query($con, "
            INSERT INTO tbl_tamu
            (nama, no_identitas, no_hp, email, alamat)
            VALUES
            ('$nama', '$no_identitas', '$no_hp', '$email', '$alamat')
        ");

        if ($query) {

            header("Location: index.php?pesan=tambah");
            exit;

        } else {

            echo "Data gagal disimpan: " . mysqli_error($con);
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

    <title>Tambah Tamu</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">


</head>

<body class="bg-light">

<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand">
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

            <div class="card-header bg-white py-3">

                <h5 class="mb-0">
                    Tambah Data Tamu
                </h5>

            </div>


            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Nama Tamu
                        </label>

                        <input type="text"
                               name="nama"
                               class="form-control"
                               placeholder=""
                               required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            No. Identitas <small class="text-muted">(Maksimal 16 karakter)</small>
                        </label>

                        <input type="text"
                               name="no_identitas"
                               class="form-control"
                               placeholder=""
                               maxlength="16"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <div class="form-text">Hanya boleh berisi angka, maksimal 16 digit.</div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            No. HP <small class="text-muted">(Maksimal 13 karakter)</small>
                        </label>

                        <input type="text"
                               name="no_hp"
                               class="form-control"
                               placeholder=""
                               maxlength="13"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <div class="form-text">Hanya boleh berisi angka, maksimal 13 digit.</div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="">

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Alamat
                        </label>

                        <textarea name="alamat"
                                  class="form-control"
                                  rows="4"
                                  placeholder=""></textarea>

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