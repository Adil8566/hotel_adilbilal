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

$id_tamu = (int) $_GET['id'];


$query_data = mysqli_query($con, "
    SELECT *
    FROM tbl_tamu
    WHERE id_tamu = '$id_tamu'
");

if (mysqli_num_rows($query_data) == 0) {

    header("Location: index.php");
    exit;
}

$data = mysqli_fetch_assoc($query_data);


if (isset($_POST['btn_edit'])) {

    $nama         = trim($_POST['nama']);
    $no_identitas = trim($_POST['no_identitas']);
    $no_hp        = trim($_POST['no_hp']);
    $email        = trim($_POST['email']);
    $alamat       = trim($_POST['alamat']);

    if ($nama == "") {

        echo "<script>
                alert('Nama tamu wajib diisi!');
              </script>";

    } else {

        $nama         = mysqli_real_escape_string($con, $nama);
        $no_identitas = mysqli_real_escape_string($con, $no_identitas);
        $no_hp        = mysqli_real_escape_string($con, $no_hp);
        $email        = mysqli_real_escape_string($con, $email);
        $alamat       = mysqli_real_escape_string($con, $alamat);

        $query_edit = mysqli_query($con, "
            UPDATE tbl_tamu SET
                nama = '$nama',
                no_identitas = '$no_identitas',
                no_hp = '$no_hp',
                email = '$email',
                alamat = '$alamat'
            WHERE id_tamu = '$id_tamu'
        ");

        if ($query_edit) {

            header("Location: index.php?pesan=edit");
            exit;

        } else {

            echo "Data gagal diedit: " . mysqli_error($con);
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

    <title>Edit Tamu</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">


</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">

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
                    Edit Data Tamu
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
                               value="<?= htmlspecialchars($data['nama']); ?>"
                               required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            No. Identitas
                        </label>

                        <input type="text"
                               name="no_identitas"
                               class="form-control"
                               value="<?= htmlspecialchars($data['no_identitas']); ?>">

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            No. HP
                        </label>

                        <input type="text"
                               name="no_hp"
                               class="form-control"
                               value="<?= htmlspecialchars($data['no_hp']); ?>">

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               value="<?= htmlspecialchars($data['email']); ?>">

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Alamat
                        </label>

                        <textarea name="alamat"
                                  class="form-control"
                                  rows="4"><?= htmlspecialchars($data['alamat']); ?></textarea>

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