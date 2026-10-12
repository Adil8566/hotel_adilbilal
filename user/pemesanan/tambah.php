<?php
require_once '../auth.php';

$id_tipe_get = (int)($_GET['id_tipe'] ?? $_POST['id_tipe'] ?? 0);
if ($id_tipe_get <= 0) { header('Location: ../tipe_kamar/index.php'); exit; }

$stmt_tipe = mysqli_prepare($con, 'SELECT nama_tipe, harga FROM tbl_tipe_kamar WHERE id_tipe=? LIMIT 1');
mysqli_stmt_bind_param($stmt_tipe, 'i', $id_tipe_get);
mysqli_stmt_execute($stmt_tipe);
mysqli_stmt_bind_result($stmt_tipe, $nama_tipe, $harga_tipe);
$ada_tipe = mysqli_stmt_fetch($stmt_tipe);
mysqli_stmt_close($stmt_tipe);
if (!$ada_tipe) { header('Location: ../tipe_kamar/index.php'); exit; }

$old = [
    'nama' => $nama_user,
    'no_identitas' => '', 'no_hp' => '', 'email' => '', 'alamat' => '',
    'check_in' => '', 'check_out' => '', 'jumlah_tamu' => 1
];
$error = '';

$existing = mysqli_query($con, "SELECT t.nama,t.no_identitas,t.no_hp,t.email,t.alamat FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu WHERE b.id_pengguna={$id_user} ORDER BY b.id_booking DESC LIMIT 1");
if ($existing && ($last = mysqli_fetch_assoc($existing))) {
    foreach (['nama','no_identitas','no_hp','email','alamat'] as $k) { if ($last[$k] !== null && $last[$k] !== '') $old[$k] = $last[$k]; }
}

if (isset($_POST['btn-tambah'])) {
    foreach (array_keys($old) as $k) {
        if (isset($_POST[$k])) $old[$k] = trim((string)$_POST[$k]);
    }
    $old['jumlah_tamu'] = max(1, (int)($_POST['jumlah_tamu'] ?? 1));
    $in = DateTime::createFromFormat('Y-m-d', $old['check_in']);
    $out = DateTime::createFromFormat('Y-m-d', $old['check_out']);

    if ($old['nama']==='' || $old['no_hp']==='' || !$in || !$out) {
        $error = 'Nama, nomor HP, check-in, dan check-out wajib diisi.';
    } elseif ($old['check_in'] < date('Y-m-d')) {
        $error = 'Tanggal check-in tidak boleh sebelum hari ini.';
    } elseif ($out < $in) { // sama seperti admin: check-in & check-out di hari yang sama = Day Rate
        $error = 'Tanggal check-out tidak boleh sebelum check-in.';
    } else {
        $check_in = mysqli_real_escape_string($con, $old['check_in']);
        $check_out = mysqli_real_escape_string($con, $old['check_out']);
        $cari = mysqli_query($con, "
            SELECT k.id_kamar FROM tbl_kamar k
            WHERE k.id_tipe={$id_tipe_get}
              AND LOWER(TRIM(k.status)) <> 'maintenance'
              AND NOT EXISTS (
                  SELECT 1 FROM tbl_booking b
                  WHERE b.id_kamar=k.id_kamar
                    AND LOWER(TRIM(b.status)) IN ('pending','dikonfirmasi','checkin')
                    AND b.check_in < '{$check_out}'
                    AND b.check_out > '{$check_in}'
              )
            ORDER BY k.nomor_kamar ASC LIMIT 1
        ");
        if (!$cari || mysqli_num_rows($cari)===0) {
            $error = 'Tidak ada kamar yang tersedia pada tanggal tersebut.';
        } else {
            $kamar = mysqli_fetch_assoc($cari);
            mysqli_begin_transaction($con);
            try {
                $s_tamu = mysqli_prepare($con, 'SELECT id_tamu FROM tbl_tamu WHERE nama=? AND no_hp=? LIMIT 1');
                mysqli_stmt_bind_param($s_tamu, 'ss', $old['nama'], $old['no_hp']);
                mysqli_stmt_execute($s_tamu);
                mysqli_stmt_bind_result($s_tamu, $id_tamu);
                $found_tamu = mysqli_stmt_fetch($s_tamu);
                mysqli_stmt_close($s_tamu);

                if (!$found_tamu) {
                    $s_new = mysqli_prepare($con, 'INSERT INTO tbl_tamu (nama,no_identitas,no_hp,email,alamat) VALUES (?,?,?,?,?)');
                    mysqli_stmt_bind_param($s_new, 'sssss', $old['nama'],$old['no_identitas'],$old['no_hp'],$old['email'],$old['alamat']);
                    if (!mysqli_stmt_execute($s_new)) throw new RuntimeException('Gagal menyimpan data tamu.');
                    $id_tamu = mysqli_insert_id($con);
                    mysqli_stmt_close($s_new);
                } else {
                    $s_update = mysqli_prepare($con, 'UPDATE tbl_tamu SET no_identitas=?,email=?,alamat=? WHERE id_tamu=?');
                    mysqli_stmt_bind_param($s_update, 'sssi', $old['no_identitas'],$old['email'],$old['alamat'],$id_tamu);
                    mysqli_stmt_execute($s_update);
                    mysqli_stmt_close($s_update);
                }

                // Harga dinamis sama dengan admin (weekday/weekend/day rate + diskon)
                $total = hitung_total_menginap((float)$harga_tipe, $in, $out);
                $kode = 'HTL-' . date('YmdHis') . '-' . random_int(100,999);
                $status = 'pending';
                $stmt = mysqli_prepare($con, 'INSERT INTO tbl_booking (kode_booking,id_pengguna,id_tamu,id_kamar,check_in,check_out,jumlah_tamu,total,status) VALUES (?,?,?,?,?,?,?,?,?)');
                $id_kamar = (int)$kamar['id_kamar'];
                mysqli_stmt_bind_param($stmt, 'siiissids', $kode,$id_user,$id_tamu,$id_kamar,$old['check_in'],$old['check_out'],$old['jumlah_tamu'],$total,$status);
                if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Booking gagal disimpan.');
                $id_booking = mysqli_insert_id($con);
                mysqli_stmt_close($stmt);

                mysqli_commit($con);
                header('Location: detail.php?id=' . $id_booking . '&pesan=tambah');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Pesan Kamar - Grand Dian Hotel</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css"></head>
<body class="bg-light">
<nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Grand Dian Hotel</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav>
<div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4">
<div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Pesan <?= e($nama_tipe); ?></h5></div><div class="card-body">
<?php if($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation me-1"></i><?= e($error); ?></div><?php endif; ?>
<div class="alert alert-info"><strong><?= e($nama_tipe); ?></strong> · <?= rupiah($harga_tipe); ?> / malam. Sistem akan menyesuaikan harga secara otomatis berdasarkan tanggal dan memilih kamar yang tidak bentrok dengan booking aktif.</div>
<form method="POST"><input type="hidden" name="id_tipe" value="<?= $id_tipe_get; ?>">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Nama Tamu</label><input name="nama" class="form-control" value="<?= e($old['nama']); ?>" required></div>
<div class="col-md-6"><label class="form-label">No. HP</label><input name="no_hp" class="form-control" value="<?= e($old['no_hp']); ?>" required></div>
<div class="col-md-6"><label class="form-label">No. Identitas</label><input name="no_identitas" class="form-control" value="<?= e($old['no_identitas']); ?>"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($old['email']); ?>"></div>
<div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= e($old['alamat']); ?></textarea></div>
<div class="col-md-4"><label class="form-label">Check-in</label><input type="date" name="check_in" id="input_ci" class="form-control" value="<?= e($old['check_in']); ?>" min="<?= date('Y-m-d'); ?>" required></div>
<div class="col-md-4"><label class="form-label">Check-out</label><input type="date" name="check_out" id="input_co" class="form-control" value="<?= e($old['check_out']); ?>" min="<?= date('Y-m-d'); ?>" required></div>
<div class="col-md-4"><label class="form-label">Jumlah Tamu</label><input type="number" name="jumlah_tamu" class="form-control" value="<?= (int)$old['jumlah_tamu']; ?>" min="1" required></div>
</div>
<div class="alert alert-warning mt-3 mb-0" id="estimasi_harga" style="display:none;"></div>
<div class="alert alert-secondary small mt-3 mb-0">Status awal pemesanan adalah <strong>Pending</strong>. Setelah diverifikasi petugas, status akan berubah menjadi Dikonfirmasi.</div>
<div class="mt-4"><button type="submit" name="btn-tambah" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Booking</button><a href="pemesanan.php" class="btn btn-secondary ms-2">Kembali</a></div>
</form></div></div>
</main></div>
<script>
function hitungEstimasiHarga() {
    let ci = document.getElementById('input_ci').value;
    let co = document.getElementById('input_co').value;
    let kotak = document.getElementById('estimasi_harga');
    if (!ci || !co) { kotak.style.display = 'none'; return; }
    let d1 = new Date(ci), d2 = new Date(co), today = new Date();
    today.setHours(0,0,0,0); d1.setHours(0,0,0,0); d2.setHours(0,0,0,0);
    if (d2 < d1) {
        kotak.style.display = 'block';
        kotak.innerHTML = "<span class='text-danger fw-bold'><i class='fas fa-times-circle'></i> Tanggal Check-out tidak valid</span>";
        return;
    }
    let hargaDasar = <?= (float)$harga_tipe; ?>;
    let malam = Math.round((d2 - d1) / 86400000);
    let selisihPesan = Math.round((d1 - today) / 86400000);
    let diskon = 0, labelDiskon = "";
    if (selisihPesan >= 30) { diskon = 0.15; labelDiskon = "<span class='badge bg-success ms-2'>Diskon Early Bird (15%)</span>"; }
    else if (selisihPesan <= 1 && selisihPesan >= 0) { diskon = 0.10; labelDiskon = "<span class='badge bg-danger ms-2'>Diskon Last Minute (10%)</span>"; }
    let total = 0, rincian = "";
    if (malam === 0) {
        total = hargaDasar * 0.50;
        rincian = "Day Rate / Transit (Tanpa Menginap)";
    } else {
        let curr = new Date(d1), wd = 0, we = 0;
        for (let i = 0; i < malam; i++) {
            let day = curr.getDay();
            if (day >= 1 && day <= 4) { total += hargaDasar; wd++; } else { total += hargaDasar * 1.20; we++; }
            curr.setDate(curr.getDate() + 1);
        }
        rincian = wd + " malam Weekday & " + we + " malam Weekend";
    }
    total = total - (total * diskon);
    let fmt = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total);
    kotak.style.display = 'block';
    kotak.innerHTML = "<div class='d-flex justify-content-between align-items-center'><span>Total Estimasi Harga: <strong class='fs-5'>" + fmt + "</strong></span>" + labelDiskon + "</div><div class='small text-muted mt-1'><i class='fas fa-info-circle me-1'></i> " + rincian + "</div>";
}
document.getElementById('input_ci').addEventListener('change', hitungEstimasiHarga);
document.getElementById('input_co').addEventListener('change', hitungEstimasiHarga);
window.onload = hitungEstimasiHarga;
</script>
</body></html>
