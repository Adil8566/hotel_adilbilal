<?php
session_start();
// Pastikan file koneksi Anda sudah benar dan variabel koneksinya adalah $con
require_once 'database/koneksi.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $konfirmasi_password = (string) ($_POST['konfirmasi_password'] ?? '');

    if ($nama === '' || $username === '' || $password === '') {
        $error = 'Semua data wajib diisi.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak sesuai.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $stmt = mysqli_prepare($con, 'SELECT id_pengguna FROM tbl_pengguna WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $username_ada = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($username_ada) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            // Menggunakan enkripsi sha1 sesuai permintaan Anda
            $password_hashed = sha1($password); 
            $peran = 'user';

            $insert = mysqli_prepare($con, 'INSERT INTO tbl_pengguna (username, password, nama, peran) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($insert, 'ssss', $username, $password_hashed, $nama, $peran);

            if (mysqli_stmt_execute($insert)) {
                $success = "Akun berhasil dibuat! Silakan <a href='login.php' class='alert-link'>Masuk di sini</a>.";
            } else {
                $error = 'Terjadi kesalahan sistem, gagal mendaftar.';
            }
            mysqli_stmt_close($insert);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - Manajemen Hotel</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons (Untuk Ikon Mata) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #ffffff;
        }
        .form-control {
            background-color: #f0f4fb;
            border: none;
            padding: 10px 15px;
        }
        .form-control:focus {
            background-color: #e2e8f0;
            box-shadow: none;
            border: 1px solid #c92a2a;
        }
        .btn-custom-red {
            background-color: #c92a2a;
            color: white;
            font-weight: bold;
        }
        .btn-custom-red:hover {
            background-color: #a52323;
            color: white;
        }
        /* Style tambahan untuk ikon mata */
        .toggle-password {
            cursor: pointer;
            color: #6c757d;
        }
        .toggle-password:hover {
            color: #333;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-none border-0 p-4" style="width: 100%; max-width: 450px;">
    
    <div class="text-center mb-4">
        <h6 class="fw-bold" style="letter-spacing: 1px;">MANAJEMEN GRAND DIAN HOTEL<br>BUMIAYU</h6>
        <p class="text-muted small" style="font-size: 13px; letter-spacing: 0.5px;">KABUPATEN BREBES</p>
        
        <hr class="mt-4 mb-3">
        <h5 class="fw-bold mb-0">BUAT AKUN BARU</h5>
    </div>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger py-2 small text-center"><?= $error; ?></div>
    <?php } ?>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success py-2 small text-center"><?= $success; ?></div>
    <?php } ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-muted mb-1">NAMA LENGKAP</label>
            <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold text-muted mb-1">USERNAME</label>
            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <!-- Field Password dengan Ikon Mata -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-muted mb-1">PASSWORD</label>
            <div class="position-relative">
                <input type="password" name="password" id="password" class="form-control" required style="padding-right: 40px;">
                <i class="bi bi-eye-slash position-absolute toggle-password" id="togglePassword" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
            </div>
        </div>

        <!-- Field Konfirmasi Password dengan Ikon Mata -->
        <div class="mb-4">
            <label class="form-label small fw-bold text-muted mb-1">KONFIRMASI PASSWORD</label>
            <div class="position-relative">
                <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" required style="padding-right: 40px;">
                <i class="bi bi-eye-slash position-absolute toggle-password" id="toggleKonfirmasi" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
            </div>
        </div>

        <button type="submit" name="register" class="btn btn-custom-red w-100 py-2">Daftar Sekarang</button>
    </form>

    <div class="text-center mt-4">
        <p class="text-muted small mb-3">Sudah punya akun? <a href="login.php" class="text-danger text-decoration-none fw-bold">Masuk</a></p>
        
        <h6 class="fw-bold text-danger mb-1" style="font-size: 16px;">GRAND DIAN HOTEL</h6>
        <p class="text-muted small" style="font-size: 11px;">Copyright &copy; Manajemen Hotel 2026</p>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script untuk logika Tampil/Sembunyi Password -->
<script>
    function toggleVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text'; // Tampilkan password
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = 'password'; // Sembunyikan password
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }

    // Event listener untuk klik ikon mata pada Password
    document.getElementById('togglePassword').addEventListener('click', function() {
        toggleVisibility('password', 'togglePassword');
    });

    // Event listener untuk klik ikon mata pada Konfirmasi Password
    document.getElementById('toggleKonfirmasi').addEventListener('click', function() {
        toggleVisibility('konfirmasi_password', 'toggleKonfirmasi');
    });
</script>

</body>
</html>