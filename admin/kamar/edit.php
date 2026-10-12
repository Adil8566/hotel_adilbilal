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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kamar = (int) $_GET['id'];


$query_data = mysqli_query($con, "
    SELECT *
    FROM tbl_kamar
    WHERE id_kamar = '$id_kamar'
");

if (mysqli_num_rows($query_data) == 0) {

    header("Location: index.php");
    exit;
}

$data = mysqli_fetch_assoc($query_data);


$query_tipe = mysqli_query($con, "
    SELECT *
    FROM tbl_tipe_kamar
    ORDER BY nama_tipe ASC
");


if (isset($_POST['btn_edit'])) {

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

        // Cek nomor kamar agar tidak sama dengan kamar lain
        $cek = mysqli_query($con, "
            SELECT *
            FROM tbl_kamar
            WHERE nomor_kamar = '$nomor_kamar'
            AND id_kamar != '$id_kamar'
        ");

        if (mysqli_num_rows($cek) > 0) {

            echo "<script>
                    alert('Nomor kamar sudah digunakan oleh kamar lain!');
                  </script>";

        } else {

            $query_edit = mysqli_query($con, "
                UPDATE tbl_kamar SET
                    nomor_kamar = '$nomor_kamar',
                    id_tipe = '$id_tipe',
                    status = '$status'
                WHERE id_kamar = '$id_kamar'
            ");

            if ($query_edit) {

                header("Location: index.php?pesan=edit");
                exit;

            } else {

                echo "Data gagal diedit: " . mysqli_error($con);
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

    <title>Edit Kamar</title>

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

            <div class="card-header">

                <h5 class="mb-0">
                    Edit Kamar
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
                               value="<?= htmlspecialchars($data['nomor_kamar']); ?>"
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

                                <option value="<?= $tipe['id_tipe']; ?>"
                                    <?= ($tipe['id_tipe'] == $data['id_tipe']) ? 'selected' : ''; ?>>

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

                            <option value="tersedia"
                                <?= ($data['status'] == 'tersedia') ? 'selected' : ''; ?>>
                                Tersedia
                            </option>

                            <option value="terisi"
                                <?= ($data['status'] == 'terisi') ? 'selected' : ''; ?>>
                                Terisi
                            </option>

                            <option value="maintenance"
                                <?= ($data['status'] == 'maintenance') ? 'selected' : ''; ?>>
                                Maintenance
                            </option>

                        </select>

                    </div>


                    <div class="d-flex gap-2">

                        <button type="submit"
                                name="btn_edit"
                                class="btn btn-primary">

                            <i class="fas fa-save"></i>
                            Simpan Perubahan

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