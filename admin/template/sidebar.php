<?php
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$admin_pos = strpos($script_path, '/admin/');
$admin_url = $admin_pos !== false ? substr($script_path, 0, $admin_pos) . '/admin' : '/admin';
$admin_url = rtrim($admin_url, '/');

$nama_pengguna = (string)($_SESSION['nama'] ?? 'Admin');
$inisial = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nama_pengguna), 0, 1));
if ($inisial === '') { $inisial = 'A'; }
$current = str_replace('\\', '/', basename($_SERVER['SCRIPT_NAME'] ?? ''));
$is = static function (string $needle) use ($script_path): bool {
    return strpos($script_path, $needle) !== false;
};
?>
<aside class="mantis-sidebar">
    <div class="mantis-sidebar-inner">
        <a class="mantis-brand" href="<?= htmlspecialchars($admin_url . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
            <img src="<?= htmlspecialchars($admin_url ? dirname($admin_url) . '/assets_volt/img/logo hotel 2.jpg' : '../assets_volt/img/logo hotel 2.jpg', ENT_QUOTES, 'UTF-8'); ?>" alt="Grand Dian Hotel">
            <div class="mantis-brand-title">
                <strong>Grand Dian Hotel</strong>
                <span>Panel Administrasi</span>
            </div>
        </a>

        <div class="mantis-section-title">Navigation</div>
        <ul class="mantis-nav">
            <li>
                <a class="<?= $current === 'index.php' && !$is('/tipe_kamar/') && !$is('/kamar/') && !$is('/tamu/') && !$is('/pemesanan/') && !$is('/pembayaran/') && !$is('/laporan/') && !$is('/pengguna/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-gauge-high"></i><span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="mantis-section-title">Hotel Management</div>
        <ul class="mantis-nav">
            <li><a class="<?= $is('/tipe_kamar/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/tipe_kamar/index.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-layer-group"></i><span>Tipe Kamar</span></a></li>
            <li><a class="<?= $is('/kamar/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/kamar/index.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-bed"></i><span>Data Kamar</span></a></li>
            <li><a class="<?= $is('/tamu/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/tamu/index.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-users"></i><span>Data Tamu</span></a></li>
            <li><a class="<?= $is('/pemesanan/') || $is('/booking/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/pemesanan/pemesanan.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-calendar-check"></i><span>Pemesanan</span></a></li>
        </ul>

        <div class="mantis-section-title">Transactions</div>
        <ul class="mantis-nav">
            <li><a class="<?= $is('/pembayaran/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/pembayaran/pembayaran.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-wallet"></i><span>Pembayaran</span></a></li>
            <li><a class="<?= $is('/laporan/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/laporan/index.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-chart-line"></i><span>Laporan</span></a></li>
        </ul>

        <div class="mantis-section-title">System</div>
        <ul class="mantis-nav">
            <li><a class="<?= $is('/pengguna/') ? 'active' : ''; ?>" href="<?= htmlspecialchars($admin_url . '/pengguna/index.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-user-gear"></i><span>Pengguna</span></a></li>
        </ul>

        <div class="mantis-sidebar-footer">
            <div class="mantis-user-mini">
                <div class="mantis-avatar"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <strong><?= htmlspecialchars($nama_pengguna, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?= htmlspecialchars($_SESSION['peran'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <a class="mantis-logout" href="<?= htmlspecialchars(dirname($admin_url) . '/logout.php', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-right-from-bracket"></i><span>Logout</span>
            </a>
        </div>
    </div>
</aside>
