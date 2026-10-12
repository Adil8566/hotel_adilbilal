<?php
session_start();

// Jika pengguna sudah login, arahkan ke halaman sesuai peran.
if (isset($_SESSION['id_pengguna'], $_SESSION['peran'])) {
    if ($_SESSION['peran'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    }

    if ($_SESSION['peran'] === 'user') {
        header('Location: user/index.php');
        exit;
    }

    // Session tidak valid.
    session_unset();
    session_destroy();
    session_start();
}

$error = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case '1':
            $error = 'Username atau password salah!';
            break;
        case '2':
            $error = 'Username dan password wajib diisi!';
            break;
        case '3':
            $error = 'Silakan login terlebih dahulu.';
            break;
        default:
            $error = 'Terjadi kesalahan saat login.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manajemen Hotel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,600,700,800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        html, body, button, input, textarea, select {
            font-family: "Nunito Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        }
        body { color: #31344a; }
        .carousel-item img {
            object-fit: cover;
            height: 100vh;
        }
        .btn-masuk {
            background-color: #b91c1c;
            color: white;
            border-radius: 4px;
        }
        .btn-masuk:hover {
            background-color: #991b1b;
            color: white;
        }
        .form-label-custom {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555b70;
            letter-spacing: .04em;
        }
        .login-panel { max-width: 450px; }
        .form-control {
            border-color: #e5e7eb;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #aab0bf;
            box-shadow: 0 0 0 .2rem rgba(38,43,64,.08);
        }
        .btn-masuk {
            border: 0;
            font-size: .9rem;
            letter-spacing: .01em;
        }
    </style>
</head>
<body>

<div class="container-fluid vh-100 p-0">
    <div class="row g-0 h-100">

        <!-- BAGIAN KIRI: SLIDER HOTEL -->
        <div class="col-lg-7 p-0 d-none d-lg-block bg-dark">
            <div id="sliderHotel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner h-100">
                    <div class="carousel-item active h-100">
                        <img src="assets_volt/img/hotel1.jpg" class="d-block w-100" alt="Hotel 1">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="assets_volt/img/hotel2.jpg" class="d-block w-100" alt="Hotel Kamar">
                    </div>
                    <div class="carousel-item h-100">
                        <img src="assets_volt/img/hotel3.jpg" class="d-block w-100" alt="Hotel Kolam Renang">
                    </div>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#sliderHotel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Sebelumnya</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#sliderHotel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Selanjutnya</span>
                </button>
            </div>
        </div>

        <!-- BAGIAN KANAN: FORM LOGIN -->
        <div class="col-lg-5 col-12 d-flex align-items-center justify-content-center bg-white">
            <div class="w-100 px-4 px-md-5 login-panel">

                <div class="text-center mb-5">
                    <img src="assets_volt/img/logo hotel 2.jpg" alt="Logo Hotel" style="width: 80px; margin-bottom: 15px;">
                    <h5 class="fw-bold mb-1">MANAJEMEN GRAND DIAN HOTEL BUMIAYU</h5>
                    <p class="text-muted small">KABUPATEN BREBES</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                <?php endif; ?>

                <form action="proses_login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label form-label-custom">Username</label>
                        <input
                            type="text"
                            class="form-control py-2"
                            id="username"
                            name="username"
                            placeholder="Username"
                            autocomplete="username"
                            required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label form-label-custom">Password</label>
                        <input
                            type="password"
                            class="form-control py-2"
                            id="password"
                            name="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            required>
                    </div>

                    <button type="submit" name="btn_login" class="btn btn-masuk w-100 py-2 fw-bold mb-3">
                        Masuk
                    </button>
                    <div class="text-center mt-3">
                        <p class="text-muted small">Belum punya akun? <a href="register.php" class="text-danger text-decoration-none fw-bold">Buat Akun</a></p>
                    </div>
                </form>

                <div class="text-start">
                    <p class="small text-muted mb-0">
                        Silakan gunakan akun yang sudah terdaftar pada sistem.
                    </p>
                </div>

                <div class="text-center mt-5">
                    <h3 class="text-danger" style="font-family: inherit; margin-bottom: 0; font-weight: 800;">GRAND DIAN HOTEL</h3>
                    <p class="small text-muted mt-1">Copyright © Manajemen Hotel 2026</p>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
