<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || 
($_SESSION['peran'] ?? '') !== 'admin') { 
header('Location: ../../login.php'); 
exit; 
}
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
$keyword=trim($_GET['q']??'');
$where="LOWER(TRIM(b.status)) <> 'dibatalkan'";
if($keyword!==''){ 
$q=mysqli_real_escape_string($con,$keyword);
$where.=" AND (b.kode_booking LIKE '%{$q}%' OR t.nama LIKE '%{$q}%' OR k.nomor_kamar LIKE '%{$q}%')"; 
}
$list=mysqli_query($con,"SELECT b.id_booking,b.kode_booking,b.total,t.nama,k.nomor_kamar,b.check_in,b.check_out,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar WHERE {$where} ORDER BY b.id_booking DESC");
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Pembayaran Hotel</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-light mantis-topbar">
        <div class="container-fluid">
            <a href="../index.php" class="navbar-brand">
                <i class="fas fa-hotel me-2">

                </i>Hotel Admin</a>
                <a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </nav>
        <div class="d-flex">
            <?php 
            include '../template/sidebar.php';
            ?>
            <main class="flex-grow-1 p-4">
                <div class="mb-4">
                    <h3>Pembayaran</h3>
                    <p class="text-muted">Pilih booking untuk mencatat pembayaran atau melihat riwayat transaksi.</p>
                </div>
<form class="row g-2 mb-4">
    <div class="col-md-10">
        <input name="q" class="form-control" value="<?= e($keyword);
        ?>" placeholder="Cari kode booking, nama tamu, nomor kamar">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Cari</button>
        </div>
    </form>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Tamu</th>
                        <th>Kamar</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no=1; 
                    if($list && mysqli_num_rows($list)>0): while($b=mysqli_fetch_assoc($list)): $sisa=max(0,(float)
                    $b['total']-(float)$b['terbayar']); $ps=status_pembayaran((float)$b['total'],(float)$b['terbayar']); 
                ?>
                <tr>
                    <td>
                        <?= 
                        $no++;
                        ?>
                        </td>
                        <td>
                            <?= 
                        e($b['kode_booking']); 
                        ?>
                        </td>
                        <td>
                            <?= 
                        e($b['nama']);
                        ?>
                        </td>
                        <td>
                            <?= e($b['nomor_kamar']);
                             ?>
                             </td>
                             <td>
                                <?= tanggal_id($b['check_in']);
                                 ?>
                                 </td>
                                 <td>
                                    <?= tanggal_id($b['check_out']); 
                                    ?>
                                    </td>
                                    <td>
                                        <?= rupiah($b['total']);
                                        ?>
                                        </td>
                                        <td>
                                            <?= rupiah($b['terbayar']); 
                                            ?>
                                            </td>
                                            <td>
                                                <?= rupiah($sisa);
                                                ?>
                                                </td>
                                                <td>
                                                <span class="badge 
                                                <?= status_pembayaran_badge($ps); 
                                                ?>">
                                                <?= $ps; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../pemesanan/pembayaran.php?id=<?= (int)$b['id_booking']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-money-bill-wave">

                                                </i> Kelola</a>
                                            </td></tr><?php endwhile; else: ?><tr><td colspan="11" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div></div></main></div></body></html>