<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);

$id_booking = (int)($_GET['id'] ?? 0);
if ($id_booking <= 0) { header('Location: pemesanan.php'); exit; }

$q = mysqli_query($con, "SELECT * FROM tbl_booking WHERE id_booking={$id_booking} LIMIT 1");
$data = $q ? mysqli_fetch_assoc($q) : null;
if (!$data) { header('Location: pemesanan.php'); exit; }

$current = strtolower(trim($data['status']));
$error = '';

// Daftar status yang diizinkan sesuai permintaan Anda
$allowed_statuses = ['pending', 'checkin', 'checkout', 'dibatalkan'];

if (isset($_POST['btn_edit'])) {
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $jumlah_tamu = max(1, (int)($_POST['jumlah_tamu'] ?? 1));
    $status = strtolower(trim($_POST['status'] ?? $current));
    
    $in = DateTime::createFromFormat('Y-m-d', $check_in);
    $out = DateTime::createFromFormat('Y-m-d', $check_out);

    if (!$in || !$out || $out <= $in) {
        $error = 'Tanggal check-out harus setelah check-in.';
    } elseif (!in_array($status, $allowed_statuses, true)) {
        // Validasi disederhanakan: pastikan status yang diinput ada di 4 pilihan tersebut
        $error = 'Status booking tidak valid.';
    } elseif (booking_overlaps($con, (int)$data['id_kamar'], $check_in, $check_out, $id_booking)) {
        $error = 'Kamar sudah memiliki booking aktif pada tanggal tersebut.';
    } elseif ($status === 'checkin' && !($check_in <= date('Y-m-d') && $check_out > date('Y-m-d'))) {
        $error = 'Status Check-in hanya dapat digunakan ketika tanggal hari ini berada di antara check-in dan check-out.';
    } else {
        $harga_q = mysqli_query($con, "SELECT tk.harga FROM tbl_kamar k INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE k.id_kamar=".(int)$data['id_kamar']." LIMIT 1");
        $harga_d = $harga_q ? mysqli_fetch_assoc($harga_q) : null;
        $harga = (float)($harga_d['harga'] ?? 0);
        $jumlah_malam = (int)$in->diff($out)->days;
        $total = $harga * $jumlah_malam;

        $stmt = mysqli_prepare($con, 'UPDATE tbl_booking SET check_in=?, check_out=?, jumlah_tamu=?, total=?, status=? WHERE id_booking=?');
        mysqli_stmt_bind_param($stmt, 'ssidsi', $check_in, $check_out, $jumlah_tamu, $total, $status, $id_booking);
        
        if (!@mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($con, 'UPDATE tbl_booking SET check_in=?, check_out=?, jumlah_tamu=?, total=?, status=? WHERE id_booking=?');
            mysqli_stmt_bind_param($stmt, 'ssidsi', $check_in, $check_out, $jumlah_tamu, $total, $status, $id_booking);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        
        sync_single_room_status($con, (int)$data['id_kamar']);
        header('Location: pemesanan.php?pesan=edit'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Edit Pemesanan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-light mantis-topbar">
        <div class="container-fluid">
            <a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <?php include '../template/sidebar.php'; ?>
        <main class="flex-grow-1 p-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Pemesanan</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">Kode booking: <strong><?= e($data['kode_booking']); ?></strong></div>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-in</label>
                                <input type="date" name="check_in" class="form-control" value="<?= e($data['check_in']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-out</label>
                                <input type="date" name="check_out" class="form-control" value="<?= e($data['check_out']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Tamu</label>
                            <input type="number" name="jumlah_tamu" class="form-control" value="<?= (int)$data['jumlah_tamu']; ?>" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?= $current === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="checkin" <?= $current === 'checkin' ? 'selected' : ''; ?>>Cek-in</option>
                                <option value="checkout" <?= $current === 'checkout' ? 'selected' : ''; ?>>Cek-out</option>
                                <option value="dibatalkan" <?= $current === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                            <div class="form-text">Status kamar akan otomatis diperbarui mengikuti pilihan di atas.</div>
                        </div>
                        
                        <button class="btn btn-primary" name="btn_edit"><i class="fas fa-save me-1"></i>Simpan Perubahan</button>
                        <a href="pemesanan.php" class="btn btn-secondary ms-2">Kembali</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>