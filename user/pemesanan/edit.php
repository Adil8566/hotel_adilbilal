<?php
require_once '../auth.php';
$id_booking = (int)($_GET['id'] ?? 0);
if ($id_booking <= 0) { header('Location: pemesanan.php'); exit; }

$q = mysqli_query($con, "SELECT b.*,t.nama,t.no_hp,t.no_identitas,t.email,t.alamat,k.id_tipe,tk.nama_tipe,tk.harga FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE b.id_booking={$id_booking} AND b.id_pengguna={$id_user} LIMIT 1");
$data = $q ? mysqli_fetch_assoc($q) : null;
if (!$data) { header('Location: pemesanan.php'); exit; }

// User hanya boleh mengubah pemesanan yang masih pending. Perubahan status (konfirmasi, check-in,
// check-out) adalah wewenang admin, jadi status tidak bisa diubah dari portal user.
if (strtolower(trim($data['status'])) !== 'pending') { header('Location: detail.php?id='.$id_booking); exit; }

$old = [
    'check_in' => $data['check_in'],
    'check_out' => $data['check_out'],
    'jumlah_tamu' => (int)$data['jumlah_tamu']
];
$error='';

if(isset($_POST['btn_edit'])){
    $old['check_in'] = trim($_POST['check_in'] ?? '');
    $old['check_out'] = trim($_POST['check_out'] ?? '');
    $old['jumlah_tamu'] = max(1,(int)($_POST['jumlah_tamu'] ?? 1));

    $in = DateTime::createFromFormat('Y-m-d', $old['check_in']);
    $out = DateTime::createFromFormat('Y-m-d', $old['check_out']);
    
    if(!$in || !$out) {
        $error = 'Tanggal belum lengkap.';
    } elseif($old['check_in'] < date('Y-m-d')) {
        $error = 'Tanggal check-in tidak boleh sebelum hari ini.';
    } elseif($out < $in) {
        $error = 'Tanggal check-out tidak boleh sebelum check-in.';
    } elseif(booking_overlaps($con, (int)$data['id_kamar'], $old['check_in'], $old['check_out'], $id_booking)) {
        $error = 'Kamar tidak tersedia pada rentang tanggal tersebut.';
    } else {
        // Harga dinamis sama dengan admin (weekday/weekend/day rate + diskon)
        $total = hitung_total_menginap((float)$data['harga'], $in, $out);

        $stmt = mysqli_prepare($con, "UPDATE tbl_booking SET check_in=?, check_out=?, jumlah_tamu=?, total=? WHERE id_booking=? AND id_pengguna=? AND LOWER(TRIM(status))='pending'");
        mysqli_stmt_bind_param($stmt, 'ssidii', $old['check_in'], $old['check_out'], $old['jumlah_tamu'], $total, $id_booking, $id_user);
        
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            header('Location: detail.php?id='.$id_booking.'&pesan=edit'); exit;
        }
        $error = 'Gagal memperbarui pemesanan: '.mysqli_error($con); 
        mysqli_stmt_close($stmt);
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
            <a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel</a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>
    
    <div class="d-flex">
        <?php include '../template/sidebar.php'; ?>
        
        <main class="flex-grow-1 p-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Edit Pemesanan</h5>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info d-flex align-items-center" style="background-color: #e0f7fa; color: #00796b; border: 1px solid #b2ebf2;">
                        <span class="me-2">Kode booking:</span> <strong><?= e($data['kode_booking']); ?></strong>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= e($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Check-in</label>
                                <input type="date" name="check_in" class="form-control" value="<?= e($old['check_in']); ?>" min="<?= date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Check-out</label>
                                <input type="date" name="check_out" class="form-control" value="<?= e($old['check_out']); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted small">Jumlah Tamu</label>
                                <input type="number" name="jumlah_tamu" class="form-control" value="<?= (int)$old['jumlah_tamu']; ?>" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-2">
                            <button type="submit" name="btn_edit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                            <a href="detail.php?id=<?= $id_booking; ?>" class="btn btn-light border ms-2">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>