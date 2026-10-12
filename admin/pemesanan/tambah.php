<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php'); exit;
}
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);

// TANGKAP ID TAMU DARI URL JIKA ADA
$id_tamu_get_url = (int) ($_GET['id_tamu'] ?? 0);

$id_tipe_get = (int) ($_GET['id_tipe'] ?? $_POST['id_tipe'] ?? 0);
if ($id_tipe_get <= 0) { 
    header('Location: pemesanan.php' . ($id_tamu_get_url > 0 ? "?id_tamu=$id_tamu_get_url" : "")); 
    exit; 
}

$stmt_tipe = mysqli_prepare($con, 'SELECT nama_tipe, harga FROM tbl_tipe_kamar WHERE id_tipe = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_tipe, 'i', $id_tipe_get); mysqli_stmt_execute($stmt_tipe); mysqli_stmt_bind_result($stmt_tipe, $nama_tipe, $harga_tipe); $ada_tipe = mysqli_stmt_fetch($stmt_tipe); mysqli_stmt_close($stmt_tipe);
if (!$ada_tipe) { 
    header('Location: pemesanan.php' . ($id_tamu_get_url > 0 ? "?id_tamu=$id_tamu_get_url" : "")); 
    exit; 
}

$query_tamu = mysqli_query($con, 'SELECT id_tamu, nama, no_hp FROM tbl_tamu ORDER BY nama ASC');

$query_kamar_pilihan = mysqli_query($con, "
    SELECT id_kamar, nomor_kamar 
    FROM tbl_kamar 
    WHERE id_tipe = '$id_tipe_get' 
      AND LOWER(TRIM(status)) = 'tersedia' 
    ORDER BY nomor_kamar ASC
");

$error = '';
$old = ['id_tamu'=> $id_tamu_get_url > 0 ? $id_tamu_get_url : '', 'id_kamar'=>'', 'check_in'=>'', 'check_out'=>'', 'jumlah_tamu'=>1];

if (isset($_POST['btn-tambah'])) {
    $old['id_tamu'] = (int)($_POST['id_tamu'] ?? 0);
    $old['id_kamar'] = (int)($_POST['id_kamar'] ?? 0); 
    $old['check_in'] = trim($_POST['check_in'] ?? '');
    $old['check_out'] = trim($_POST['check_out'] ?? '');
    $old['jumlah_tamu'] = max(1, (int)($_POST['jumlah_tamu'] ?? 1));

    $in = DateTime::createFromFormat('Y-m-d', $old['check_in']);
    $out = DateTime::createFromFormat('Y-m-d', $old['check_out']);

    if ($old['id_tamu'] <= 0 || $old['id_kamar'] <= 0 || !$in || !$out) {
        $error = 'Data pemesanan belum lengkap. Pastikan tamu, kamar, dan tanggal telah diisi.';
    } elseif ($old['check_in'] < date('Y-m-d')) {
        $error = 'Tanggal check-in tidak boleh sebelum hari ini.';
    } elseif ($out < $in) { // DIUBAH: Mengizinkan check-in & check-out di hari yang sama (Day Rate)
        $error = 'Tanggal check-out tidak boleh sebelum check-in.';
    } else {
        $check_in = mysqli_real_escape_string($con, $old['check_in']);
        $check_out = mysqli_real_escape_string($con, $old['check_out']);
        $id_kamar_pilihan = $old['id_kamar'];
        
        $cari = mysqli_query($con, "
            SELECT k.id_kamar
            FROM tbl_kamar k
            WHERE k.id_kamar = {$id_kamar_pilihan}
              AND LOWER(TRIM(k.status)) = 'tersedia'
              AND NOT EXISTS (
                  SELECT 1 FROM tbl_booking b
                  WHERE b.id_kamar = k.id_kamar
                    AND LOWER(TRIM(b.status)) IN ('pending','dikonfirmasi','checkin')
                    AND b.check_in < '{$check_out}'
                    AND b.check_out > '{$check_in}'
              )
        ");

        if (!$cari || mysqli_num_rows($cari) === 0) {
            $error = 'Kamar yang Anda pilih sudah tidak tersedia pada rentang tanggal tersebut.';
        } else {
            $id_kamar = $id_kamar_pilihan;
            
            // =================================================================
            // LOGIKA HARGA DINAMIS (PENYESUAIAN BERDASARKAN TANGGAL)
            // =================================================================
            $harga_dasar = (float)$harga_tipe;
            
            // Atur nominal rate (Anda bisa ubah ini untuk mengambil dari database nanti)
            $harga_weekday = $harga_dasar;
            $harga_weekend = $harga_dasar * 1.20; // Tambah 20% untuk Weekend
            $harga_day_rate = $harga_dasar * 0.50; // Setengah harga untuk Day Rate
            
            $jumlah_malam = (int)$in->diff($out)->days;
            $total_harga = 0;

            // 1. Cek Day Rate (Menginap dan pulang di hari yang sama)
            if ($jumlah_malam === 0) {
                $total_harga = $harga_day_rate;
            } else {
                // 2. Kalkulasi per malam untuk Weekday vs Weekend
                $curr_date = clone $in;
                for ($i = 0; $i < $jumlah_malam; $i++) {
                    $hari = $curr_date->format('N'); // 1 (Senin) - 7 (Minggu)
                    if ($hari >= 1 && $hari <= 4) { // Senin - Kamis
                        $total_harga += $harga_weekday;
                    } else { // Jumat - Minggu
                        $total_harga += $harga_weekend;
                    }
                    $curr_date->modify('+1 day');
                }
            }

            // 3. Cek Early Bird & Last Minute Diskon
            $tgl_booking = new DateTime(date('Y-m-d'));
            $tgl_in_cek = clone $in;
            $selisih_pesan = (int)$tgl_booking->diff($tgl_in_cek)->days;
            $diskon = 0;
            
            if ($selisih_pesan >= 30) {
                $diskon = 0.15; // 15% Early Bird
            } elseif ($selisih_pesan <= 1) {
                $diskon = 0.10; // 10% Last Minute
            }

            // Hitung harga final
            $total = $total_harga - ($total_harga * $diskon);
            // =================================================================

            $kode_booking = 'HTL-' . date('YmdHis') . '-' . random_int(100, 999);
            $stmt = mysqli_prepare($con, 'INSERT INTO tbl_booking (kode_booking, id_tamu, id_kamar, check_in, check_out, jumlah_tamu, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $status = 'pending';
            mysqli_stmt_bind_param($stmt, 'siissids', $kode_booking, $old['id_tamu'], $id_kamar, $old['check_in'], $old['check_out'], $old['jumlah_tamu'], $total, $status);
            if (mysqli_stmt_execute($stmt)) {
                $id_booking = mysqli_insert_id($con);
                mysqli_stmt_close($stmt);
                header('Location: detail.php?id=' . $id_booking . '&pesan=tambah'); exit;
            }
            $error = 'Booking gagal disimpan: ' . mysqli_error($con);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Tambah Pemesanan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav>
<div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Tambah Pemesanan</h5></div><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation me-1"></i><?= e($error); ?></div><?php endif; ?>

<div class="alert alert-info"><strong><?= e($nama_tipe); ?></strong> - Harga Dasar: <?= rupiah($harga_tipe); ?> / malam. Sistem akan menyesuaikan harga secara otomatis berdasarkan tanggal.</div>

<form method="POST">
<input type="hidden" name="id_tipe" value="<?= $id_tipe_get; ?>">

<div class="mb-3">
    <label class="form-label">Nama Tamu</label>
    <?php if ($id_tamu_get_url > 0): ?>
        <?php
        $nama_tamu_readonly = 'Data Tamu Tidak Ditemukan';
        mysqli_data_seek($query_tamu, 0);
        while($tamu = mysqli_fetch_assoc($query_tamu)){
            if($tamu['id_tamu'] == $id_tamu_get_url) {
                $nama_tamu_readonly = e($tamu['nama']) . ' - ' . e($tamu['no_hp']);
                break;
            }
        }
        ?>
        <input type="hidden" name="id_tamu" value="<?= $id_tamu_get_url; ?>">
        <input type="text" class="form-control bg-light" value="<?= $nama_tamu_readonly; ?>" readonly>
        
    <?php else: ?>
        <select name="id_tamu" class="form-select" required>
            <option value="">-- Pilih Tamu --</option>
            <?php mysqli_data_seek($query_tamu, 0); while($tamu = mysqli_fetch_assoc($query_tamu)): ?>
                <option value="<?= (int)$tamu['id_tamu']; ?>" <?= (int)$old['id_tamu'] === (int)$tamu['id_tamu'] ? 'selected' : ''; ?>>
                    <?= e($tamu['nama']); ?> - <?= e($tamu['no_hp']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label">Pilih Nomor Kamar Tersedia</label>
    <select name="id_kamar" class="form-select" required>
        <option value="">-- Pilih Kamar Tersedia --</option>
        <?php 
        mysqli_data_seek($query_kamar_pilihan, 0);
        if (mysqli_num_rows($query_kamar_pilihan) > 0) {
            while($kmr = mysqli_fetch_assoc($query_kamar_pilihan)): ?>
                <option value="<?= $kmr['id_kamar']; ?>" <?= (int)$old['id_kamar'] === (int)$kmr['id_kamar'] ? 'selected' : ''; ?>>
                    Kamar <?= e($kmr['nomor_kamar']); ?>
                </option>
            <?php endwhile; 
        } else {
            echo '<option value="" disabled>Semua kamar tipe ini sedang terisi/maintenance</option>';
        }
        ?>
    </select>
</div>

<div class="row">
    <!-- DIUBAH: min date untuk check_out disamakan dengan hari ini agar Day Rate berfungsi -->
    <div class="col-md-6 mb-3"><label class="form-label">Check-in</label><input type="date" name="check_in" id="input_ci" class="form-control" value="<?= e($old['check_in']); ?>" min="<?= date('Y-m-d'); ?>" required></div>
    <div class="col-md-6 mb-3"><label class="form-label">Check-out</label><input type="date" name="check_out" id="input_co" class="form-control" value="<?= e($old['check_out']); ?>" min="<?= date('Y-m-d'); ?>" required></div>
</div>
<div class="mb-3"><label class="form-label">Jumlah Tamu</label><input type="number" name="jumlah_tamu" class="form-control" value="<?= (int)$old['jumlah_tamu']; ?>" min="1" required></div>

<!-- UI ESTIMASI HARGA -->
<div class="alert alert-warning mb-3" id="estimasi_harga" style="display:none;">
    <!-- Konten diisi oleh JavaScript -->
</div>

<div class="alert alert-secondary small"><i class="fas fa-circle-info me-1"></i>Status awal booking adalah <strong>Pending</strong>. Kamar baru menjadi <strong>Terisi</strong> saat proses Check-in.</div>
<button type="submit" name="btn-tambah" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Booking</button>

<a href="pemesanan.php<?= $id_tamu_get_url > 0 ? '?id_tamu=' . $id_tamu_get_url : '' ?>" class="btn btn-secondary ms-2">Kembali</a>
</form>
</div>
</div>
</main>
</div>

<!-- JAVASCRIPT UNTUK MENAMPILKAN HARGA LIVE -->
<script>
function hitungEstimasiHarga() {
    let ci = document.getElementById('input_ci').value;
    let co = document.getElementById('input_co').value;
    let kotakEstimasi = document.getElementById('estimasi_harga');
    
    if(!ci || !co) {
        kotakEstimasi.style.display = 'none';
        return;
    }

    let d1 = new Date(ci);
    let d2 = new Date(co);
    let today = new Date();
    today.setHours(0,0,0,0);
    d1.setHours(0,0,0,0);
    d2.setHours(0,0,0,0);

    if(d2 < d1) {
        kotakEstimasi.style.display = 'block';
        kotakEstimasi.innerHTML = "<span class='text-danger fw-bold'><i class='fas fa-times-circle'></i> Tanggal Check-out tidak valid</span>";
        return;
    }

    let hargaDasar = <?= (float)$harga_tipe; ?>;
    let hargaWeekday = hargaDasar;
    let hargaWeekend = hargaDasar * 1.20;
    let hargaDayRate = hargaDasar * 0.50;

    let diffTime = Math.abs(d2 - d1);
    let malam = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    let selisihPesan = Math.ceil((d1 - today) / (1000 * 60 * 60 * 24));
    let diskon = 0;
    let labelDiskon = "";
    
    if (selisihPesan >= 30) {
        diskon = 0.15;
        labelDiskon = "<span class='badge bg-success ms-2'>Diskon Early Bird (15%)</span>";
    } else if (selisihPesan <= 1 && selisihPesan >= 0) {
        diskon = 0.10;
        labelDiskon = "<span class='badge bg-danger ms-2'>Diskon Last Minute (10%)</span>";
    }

    let total = 0;
    let rincian = "";

    if (malam === 0) {
        total = hargaDayRate;
        rincian = "Day Rate / Transit (Tanpa Menginap)";
    } else {
        let curr = new Date(d1);
        let jmlWd = 0;
        let jmlWe = 0;
        for(let i = 0; i < malam; i++) {
            let day = curr.getDay(); // 0=Minggu, 1=Senin ... 6=Sabtu
            if(day >= 1 && day <= 4) {
                total += hargaWeekday;
                jmlWd++;
            } else {
                total += hargaWeekend;
                jmlWe++;
            }
            curr.setDate(curr.getDate() + 1);
        }
        rincian = jmlWd + " malam Weekday & " + jmlWe + " malam Weekend";
    }

    total = total - (total * diskon);
    
    let formatRupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total);
    
    kotakEstimasi.style.display = 'block';
    kotakEstimasi.innerHTML = "<div class='d-flex justify-content-between align-items-center'><span>Total Estimasi Harga: <strong class='fs-5'>" + formatRupiah + "</strong></span>" + labelDiskon + "</div><div class='small text-muted mt-1'><i class='fas fa-info-circle me-1'></i> " + rincian + "</div>";
}

document.getElementById('input_ci').addEventListener('change', hitungEstimasiHarga);
document.getElementById('input_co').addEventListener('change', hitungEstimasiHarga);

// Hitung saat halaman diload jika tanggal sudah terisi (misal saat form error lalu kembali)
window.onload = hitungEstimasiHarga;
</script>
</body>
</html>