<?php
require_once '../auth.php';
$id_booking=(int)($_GET['id']??0);
if($id_booking<=0){header('Location: pemesanan.php');exit;}
$q=mysqli_query($con,"SELECT b.*,t.nama AS nama_tamu,k.nomor_kamar,tk.nama_tipe FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE b.id_booking={$id_booking} AND b.id_pengguna={$id_user} LIMIT 1");
$data=$q?mysqli_fetch_assoc($q):null;if(!$data){header('Location: pemesanan.php');exit;}
$state=strtolower(trim($data['status']));
$error='';
if(isset($_POST['simpan_pembayaran']) && !in_array($state,['dibatalkan','checkout'],true)){
 $jumlah=(float)($_POST['jumlah_bayar']??0);$metode=trim($_POST['metode']??'Transfer');$sudah=total_terbayar($con,$id_booking);$sisa=max(0,(float)$data['total']-$sudah);
 if($jumlah<=0)$error='Jumlah pembayaran harus lebih dari 0.';elseif($jumlah>$sisa)$error='Jumlah pembayaran melebihi sisa tagihan.';else{
  $status_bayar=($sudah+$jumlah+0.009 >= (float)$data['total'])?'lunas':'belum_lunas';
  $stmt=mysqli_prepare($con,'INSERT INTO tbl_pembayaran(id_booking,jumlah_bayar,metode,status) VALUES(?,?,?,?)');mysqli_stmt_bind_param($stmt,'idss',$id_booking,$jumlah,$metode,$status_bayar);
  if(mysqli_stmt_execute($stmt)){mysqli_stmt_close($stmt);header('Location: pembayaran.php?id='.$id_booking.'&pesan=bayar');exit;}
  $error='Pembayaran gagal disimpan: '.mysqli_error($con);mysqli_stmt_close($stmt);
 }
}
$terbayar=total_terbayar($con,$id_booking);$sisa=max(0,(float)$data['total']-$terbayar);$sp=status_pembayaran((float)$data['total'],$terbayar);
// Sinkronkan status pada data pembayaran (sama seperti halaman pembayaran admin)
$sync_status=$sp==='Lunas'?'lunas':'belum_lunas';
@mysqli_query($con,"UPDATE tbl_pembayaran SET status='{$sync_status}' WHERE id_booking={$id_booking}");
$riwayat=mysqli_query($con,"SELECT tanggal_bayar,jumlah_bayar,metode,status FROM tbl_pembayaran WHERE id_booking={$id_booking} ORDER BY id_pembayaran DESC");
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Pembayaran - Grand Dian Hotel</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
    </head>
<body class="bg-light">
    <nav class="navbar navbar-light mantis-topbar">
        <div class="container-fluid">
            <a href="pemesanan.php" class="navbar-brand">
                <i class="fas fa-hotel me-2"></i>Grand Dian Hotel
            </a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <?php include '../template/sidebar.php'; ?>
        <main class="flex-grow-1 p-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">Riwayat Pembayaran</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1;if($riwayat&&mysqli_num_rows($riwayat)>0):while($p=mysqli_fetch_assoc($riwayat)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= tanggal_id($p['tanggal_bayar']); ?><br><small><?= date('H:i',strtotime($p['tanggal_bayar'])); ?></small></td>
                                <td><?= e($p['metode']); ?></td>
                                <td><?= rupiah($p['jumlah_bayar']); ?></td>
                                <td><span class="badge <?= $p['status']==='lunas'?'bg-success':'bg-warning text-dark'; ?>"><?= e($p['status']?:'belum_lunas'); ?></span></td>
                            </tr>
                            <?php endwhile;else:?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada pembayaran.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
</body>
</html>
