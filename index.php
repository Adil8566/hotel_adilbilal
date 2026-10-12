<?php
require_once __DIR__ . '/database/koneksi.php';

function landing_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function landing_rupiah(float $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}

//  landing page

$stats = [
    'tipe_kamar' => 0,
    'kamar' => 0,
    'tersedia' => 0,
    'tamu' => 0,
];

$q = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_tipe_kamar");
if ($q) {
    $stats['tipe_kamar'] = (int) (mysqli_fetch_assoc($q)['total'] ?? 0);
}

$q = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_kamar");
if ($q) {
    $stats['kamar'] = (int) (mysqli_fetch_assoc($q)['total'] ?? 0);
}

$q = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_kamar WHERE status = 'tersedia'");
if ($q) {
    $stats['tersedia'] = (int) (mysqli_fetch_assoc($q)['total'] ?? 0);
}

$q = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_tamu");
if ($q) {
    $stats['tamu'] = (int) (mysqli_fetch_assoc($q)['total'] ?? 0);
}

$rooms = [];
$qRooms = mysqli_query($con, "
    SELECT
        t.id_tipe,
        t.nama_tipe,
        t.harga,
        t.fasilitas,
        t.keterangan,
        COUNT(k.id_kamar) AS jumlah_kamar,
        COALESCE(SUM(CASE WHEN k.status = 'tersedia' THEN 1 ELSE 0 END), 0) AS tersedia
    FROM tbl_tipe_kamar t
    LEFT JOIN tbl_kamar k ON k.id_tipe = t.id_tipe
    GROUP BY t.id_tipe, t.nama_tipe, t.harga, t.fasilitas, t.keterangan
    ORDER BY t.harga ASC, t.nama_tipe ASC
");
if ($qRooms) {
    while ($row = mysqli_fetch_assoc($qRooms)) {
        $rooms[] = $row;
    }
}

$roomImages = [
    'Moderate Room' => 'hotel1.jpg',
    'Superior Room' => 'hotel2.jpg',
    'Deluxe Room' => 'hotel3.jpg',
    'Villa Superior' => 'hotel1.jpg',
    'Villa Deluxe' => 'hotel2.jpg',
];

$defaultImages = ['hotel1.jpg', 'hotel2.jpg', 'hotel3.jpg'];

$heroImage = 'assets_volt/img/hotel1.jpg';
$logoImage = 'assets_volt/img/logo hotel 2.jpg';
$loginUrl = 'login.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Dian Hotel Bumiayu | Informasi & Reservasi</title>
    <meta name="description" content="Landing page Grand Dian Hotel Bumiayu dengan informasi tipe kamar, fasilitas, akses login, dan pemesanan.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets_landing/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="assets_landing/landing.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="<?= landing_e($logoImage); ?>">
    <style>
        .hotel-brand-logo { width: 42px; height: 42px; object-fit: cover; border-radius: 10px; border: 1px solid rgba(11,18,32,.08); }
        .hotel-brand-text { color: #101828; font-weight: 800; }
        .hotel-brand-text span { color: var(--clr-primary); }
        .hero-title { max-width: 720px; }
        .room-features { display:flex; flex-wrap:wrap; gap:7px; margin-top:10px; }
        .room-feature { background: #f3f8f3; color: #3b4a43; padding: 4px 9px; border-radius: 999px; font-size: .72rem; }
        .room-card .cause-body { min-height: 330px; }
        .room-card .cause-img { height: 230px; }
        .room-card .cause-img img { height:100%; object-fit:cover; }
        .hero-booking-note { font-size: .82rem; color: #667085; }
        .login-nav-link { color:#475467 !important; font-weight:600 !important; }
        .login-nav-link:hover { color: var(--clr-primary) !important; }
        .hero-image-label { font-size: .72rem; color:#667085; margin-top:10px; }
        .hotel-info-band { margin-top: 18px; display:flex; flex-wrap:wrap; gap:10px; }
        .hotel-info-pill { display:inline-flex; align-items:center; gap:7px; background: rgba(22,163,74,.08); color:#137333; border-radius:999px; padding:7px 11px; font-size:.78rem; font-weight:700; }
        .contact-card .info-note { color:#667085; font-size:.86rem; }
        @media (max-width: 991.98px) {
            .navbar-hw .navbar-nav { padding-top:10px; }
            .navbar-hw .nav-link { padding-top:9px; padding-bottom:9px; }
            .desktop-login-actions { margin-top:12px; }
        }
    </style>
</head>
<body>

<div id="preloader" aria-hidden="true">
    <div class="preload-ring"><i class="bi bi-building preload-heart"></i></div>
    <div class="preload-text">MEMUAT GRAND DIAN HOTEL&hellip;</div>
</div>

<nav class="navbar navbar-expand-lg navbar-hw" id="mainNav">
    <div class="container">
        <a class="hw-logo d-flex align-items-center gap-2" href="#home" aria-label="Grand Dian Hotel">
            <img src="<?= landing_e($logoImage); ?>" alt="Logo Grand Dian Hotel" class="hotel-brand-logo">
            <span class="hotel-brand-text">Grand Dian <span>Hotel</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Buka navigasi">
            <i class="bi bi-list fs-2"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav nav-hw mx-auto mt-3 mt-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#kamar">Kamar</a></li>
                <li class="nav-item"><a class="nav-link" href="#fasilitas">Fasilitas</a></li>
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#galeri">Galeri</a></li>
                <li class="nav-item"><a class="nav-link" href="#cara">Cara Akses</a></li>
                <li class="nav-item"><a class="nav-link login-nav-link" href="<?= landing_e($loginUrl); ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
            </ul>
            <div class="desktop-login-actions d-flex align-items-center gap-2 justify-content-center">
                <a href="<?= landing_e($loginUrl); ?>" class="btn-hw-primary">Pesan Sekarang <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</nav>

<header class="hero" id="home">
    <div class="hero-glow"></div>
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <span class="blob blob-3"></span>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="eyebrow"><i class="bi bi-stars"></i> Grand Dian Hotel Bumiayu</span>
                <h1 class="hero-title mt-4">Nikmati pengalaman <span class="accent-underline">menginap<svg viewBox="0 0 200 14" preserveAspectRatio="none"><path d="M2 10 Q 50 2 100 8 T 198 6" stroke="#F59E0B" stroke-width="5" fill="none" stroke-linecap="round"/></svg></span> dengan nyaman.</h1>
                <p class="hero-lead mt-4">Di Prakata Grand Dian Hotel Bumiayu, kami bangga menyajikan sebuah destinasi penginapan yang menyatukan kenyamanan modern dengan keindahan estetika. Terletak di jantung kota Bumiayu, kami menyediakan pilihan akomodasi yang memikat bagi tamu-tamu kami.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="<?= landing_e($loginUrl); ?>" class="btn-hw-primary">Pesan Sekarang <i class="bi bi-arrow-right"></i></a>
                    <a href="#kamar" class="btn-hw-outline"><i class="bi bi-door-open"></i> Lihat Kamar</a>
                </div>
                <div class="hotel-info-band">
                    <span class="hotel-info-pill"><i class="bi bi-building"></i> <?= (int)$stats['kamar']; ?> unit kamar</span>
                    <span class="hotel-info-pill"><i class="bi bi-check2-circle"></i> <?= (int)$stats['tersedia']; ?> kamar tersedia</span>
                </div>
                <div class="d-flex align-items-center gap-3 mt-4 hero-booking-note">
                    <i class="bi bi-shield-check fs-4 text-success"></i>
                    <span>Login tersedia untuk akun dengan peran admin dan user.</span>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="120">
                <div class="hero-media">
                    <div class="hero-media-frame">
                        <img src="<?= landing_e($heroImage); ?>" alt="Grand Dian Hotel Bumiayu" loading="eager">
                    </div>
                    <div class="float-card float-card-1">
                        <span class="fc-icon"><i class="bi bi-door-open-fill"></i></span>
                        <div><div class="fc-val"><?= (int)$stats['tersedia']; ?></div><div class="fc-label">Kamar tersedia</div></div>
                    </div>
                    <div class="float-card float-card-2">
                        <span class="fc-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                        <div><div class="fc-val"><?= (int)$stats['tipe_kamar']; ?></div><div class="fc-label">Tipe kamar</div></div>
                    </div>
                </div>
                <div class="hero-image-label">Foto dari aset project: Grand Dian Hotel.</div>
            </div>
        </div>
    </div>
</header>

<section class="section-pad pt-0" id="impact">
    <div class="container">
        <div class="stats-band" data-aos="zoom-in">
            <div class="row g-0">
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-num"><span class="counter" data-target="<?= (int)$stats['tipe_kamar']; ?>">0</span></div>
                    <div class="stat-label">Tipe Kamar</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-num"><span class="counter" data-target="<?= (int)$stats['kamar']; ?>">0</span></div>
                    <div class="stat-label">Unit Kamar</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-num"><span class="counter" data-target="<?= (int)$stats['tersedia']; ?>">0</span></div>
                    <div class="stat-label">Kamar Tersedia</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-num"><span class="counter" data-target="<?= (int)$stats['tamu']; ?>">0</span></div>
                    <div class="stat-label">Data Tamu</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-pad bg-white" id="kamar">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:680px;" data-aos="fade-up">
            <span class="eyebrow"><i class="bi bi-house-door-fill"></i> Pilihan Kamar</span>
            <h2 class="section-title mt-3" style="font-size:clamp(1.8rem,3.2vw,2.5rem);">Tipe kamar </h2>
            <p class="section-sub mx-auto mt-2"></p>
        </div>
        <div class="row g-4">
            <?php if (!$rooms): ?>
                <div class="col-12">
                    <div class="contact-card text-center">
                        <i class="bi bi-info-circle fs-1 text-success"></i>
                        <h5 class="mt-3 fw-bold">Data tipe kamar belum tersedia</h5>
                        <p class="text-muted mb-0">Silakan import database hotel terlebih dahulu.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $index => $room): ?>
                    <?php
                        $roomName = (string)$room['nama_tipe'];
                        $imgName = $roomImages[$roomName] ?? $defaultImages[$index % count($defaultImages)];
                        $imgPath = 'assets_volt/img/' . $imgName;
                        $jumlahKamar = (int)$room['jumlah_kamar'];
                        $tersedia = (int)$room['tersedia'];
                        $availability = $jumlahKamar > 0 ? (int)round(($tersedia / $jumlahKamar) * 100) : 0;
                        $features = preg_split('/\s*,\s*/', (string)$room['fasilitas'], -1, PREG_SPLIT_NO_EMPTY);
                        $features = array_slice($features, 0, 6);
                    ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 80; ?>">
                        <div class="cause-card room-card h-100">
                            <div class="cause-img">
                                <img src="<?= landing_e($imgPath); ?>" alt="<?= landing_e($roomName); ?>" loading="lazy">
                                <span class="cause-tag"><?= $tersedia > 0 ? 'Tersedia' : 'Penuh'; ?></span>
                            </div>
                            <div class="cause-body d-flex flex-column">
                                <h5 class="font-display fw-bold"><?= landing_e($roomName); ?></h5>
                                <p class="text-muted small mb-1">
                                    <?= landing_e((string)$room['keterangan']); ?>
                                </p>
                                <div class="room-features">
                                    <?php foreach ($features as $feature): ?>
                                        <span class="room-feature"><i class="bi bi-check2"></i> <?= landing_e($feature); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="cause-progress-track mt-3"><div class="cause-progress-fill" data-progress="<?= $availability; ?>"></div></div>
                                <div class="cause-meta mt-2">
                                    <span class="raised"><?= landing_rupiah((float)$room['harga']); ?> / malam</span>
                                    <span class="goal"><?= $tersedia; ?>/<?= $jumlahKamar; ?> tersedia</span>
                                </div>
                                <a href="<?= landing_e($loginUrl); ?>" class="cause-donate mt-auto">Pesan kamar <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-pad" id="fasilitas">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:680px;" data-aos="fade-up">
            <span class="eyebrow"><i class="bi bi-stars"></i> Fasilitas</span>
            <h2 class="section-title mt-3" style="font-size:clamp(1.8rem,3.2vw,2.5rem);">Fasilitas kamar</h2>
            <p class="section-sub mx-auto mt-2"></p>
        </div>
        <?php
        $facilities = [];
        foreach ($rooms as $room) {
            $items = preg_split('/\s*,\s*/', (string)$room['fasilitas'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($items as $item) {
                $key = strtolower(trim($item));
                if ($key !== '') {
                    $facilities[$key] = trim($item);
                }
            }
        }
        $facilityIcons = [
            'ac' => 'bi-snow2',
            'tv' => 'bi-tv-fill',
            'air panas' => 'bi-droplet-half',
            'wifi' => 'bi-wifi',
            'kolam renang' => 'bi-water',
            'taman buah' => 'bi-tree-fill',
            'taman bunga' => 'bi-flower1',
            'hair dryer' => 'bi-wind',
        ];
        ?>
        <div class="row g-4">
            <?php foreach ($facilities as $key => $label): ?>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up">
                    <div class="why-card h-100">
                        <div class="why-ic"><i class="bi <?= landing_e($facilityIcons[$key] ?? 'bi-check-circle-fill'); ?>"></i></div>
                        <h5 class="font-display fw-bold mb-1"><?= landing_e($label); ?></h5>
                        <p class="text-muted mb-0 small">Tercantum pada informasi fasilitas tipe kamar.</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-pad bg-white" id="tentang">
    <div class="container contact-card about-card">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-collage">
                    <div class="about-badge">
                        <div class="ab-ring"><span><?= (int)$stats['tipe_kamar']; ?> tipe</span></div>
                        <div>
                            <div class="fw-bold font-display" style="font-size:.9rem;">Pilihan Kamar</div>
                            <div class="text-muted" style="font-size:.78rem;">Data dari sistem hotel</div>
                        </div>
                    </div>
                    <div class="img-a"><img src="assets_volt/img/hotel2.jpg" alt="Interior hotel" loading="lazy"></div>
                    <div class="img-b"><img src="assets_volt/img/hotel3.jpg" alt="Fasilitas hotel" loading="lazy"></div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="eyebrow"><i class="bi bi-building-fill"></i> Tentang Sistem</span>
                <h2 class="section-title mt-3 mb-3" style="font-size:clamp(1.7rem,3vw,2.4rem);">Satu halaman untuk memperkenalkan Grand Dian Hotel Bumiayu.</h2>
                <p class="section-sub">Landing page ini menjadi halaman publik sebelum pengunjung masuk ke sistem. Informasi tipe kamar ditampilkan dari database dan tombol Login mengarah ke sistem autentikasi yang sudah tersedia.</p>
                <ul class="about-list mt-3">
                    <li><span class="ic"><i class="bi bi-database-check"></i></span><div><strong>Data terhubung database</strong><div class="text-muted small">Nama tipe kamar, harga, fasilitas, dan ketersediaan dibaca dari tabel hotel.</div></div></li>
                    <li><span class="ic"><i class="bi bi-person-check-fill"></i></span><div><strong>Login satu pintu</strong><div class="text-muted small">Menu Login digunakan untuk masuk sesuai peran akun pada sistem.</div></div></li>
                    <li><span class="ic"><i class="bi bi-phone-fill"></i></span><div><strong>Responsif</strong><div class="text-muted small">Tampilan menyesuaikan desktop, tablet, dan perangkat mobile.</div></div></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section-pad" id="cara">
    <div class="container contact-card">
        <div class="text-center mx-auto mb-5" style="max-width:680px;" data-aos="fade-up">
            <span class="eyebrow"><i class="bi bi-signpost-split-fill"></i> Cara Akses</span>
            <h2 class="section-title mt-3" style="font-size:clamp(1.8rem,3.2vw,2.5rem);"></h2>
        </div>
        <div class="hiw-wrap">
            <div class="hiw-line"></div>
            <div class="row g-4">
                <div class="col-md-4 hiw-step" data-aos="fade-up">
                    <div class="hiw-num">1</div>
                    <h5 class="font-display fw-bold">Lihat Kamar</h5>
                    <p class="text-muted">Pilih tipe kamar berdasarkan harga, fasilitas, dan jumlah unit yang tersedia.</p>
                </div>
                <div class="col-md-4 hiw-step" data-aos="fade-up" data-aos-delay="120">
                    <div class="hiw-num">2</div>
                    <h5 class="font-display fw-bold">Login</h5>
                    <p class="text-muted">Masuk menggunakan akun yang sudah terdaftar atau buat akun melalui halaman registrasi.</p>
                </div>
                <div class="col-md-4 hiw-step" data-aos="fade-up" data-aos-delay="240">
                    <div class="hiw-num">3</div>
                    <h5 class="font-display fw-bold">Gunakan Sistem</h5>
                    <p class="text-muted">Setelah masuk, sistem mengarahkan pengguna berdasarkan peran akun yang dimiliki.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-pad bg-white" id="galeri">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:680px;" data-aos="fade-up">
            <span class="eyebrow"><i class="bi bi-images"></i> Galeri</span>
            <h2 class="section-title mt-3" style="font-size:clamp(1.8rem,3.2vw,2.5rem);"></h2>
            <p class="section-sub mx-auto mt-2"></p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up"><div class="cause-card"><div class="cause-img" style="height:260px;"><img src="assets_volt/img/hotel1.jpg" alt="Grand Dian Hotel" loading="lazy"></div><div class="cause-body"><h5 class="font-display fw-bold mb-1">Grand Dian Hotel</h5><p class="text-muted small mb-0">Tampilan hotel dari aset project.</p></div></div></div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="80"><div class="cause-card"><div class="cause-img" style="height:260px;"><img src="assets_volt/img/hotel2.jpg" alt="Interior kamar hotel" loading="lazy"></div><div class="cause-body"><h5 class="font-display fw-bold mb-1">Interior Kamar</h5><p class="text-muted small mb-0">Contoh visual kamar hotel.</p></div></div></div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="160"><div class="cause-card"><div class="cause-img" style="height:260px;"><img src="assets_volt/img/hotel3.jpg" alt="Fasilitas hotel" loading="lazy"></div><div class="cause-body"><h5 class="font-display fw-bold mb-1">Fasilitas Hotel</h5><p class="text-muted small mb-0">Contoh visual fasilitas hotel.</p></div></div></div>
        </div>
    </div>
</section>

<section class="section-pad" id="contact">
    <div class="container">
        <div class="cta-banner" data-aos="zoom-in">
            <i class="bi bi-building" style="font-size:2.4rem;"></i>
            <h2 class="mt-3">Siap masuk ke sistem Grand Dian Hotel?</h2>
            <p class="mb-4" style="opacity:.92;">Gunakan menu Login untuk masuk ke sistem yang sudah terhubung dengan database hotel.</p>
            <a href="<?= landing_e($loginUrl); ?>" class="btn-cta"><i class="bi bi-box-arrow-in-right me-2"></i>Login ke Sistem</a>
        </div>
    </div>
</section>

<footer class="footer-hw">
    <svg class="footer-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="#0B1220" d="M0,32 C240,70 480,0 720,18 C960,36 1200,70 1440,26 L1440,70 L0,70 Z"></path>
    </svg>
    <span class="footer-glow"></span>
    <span class="footer-glow-2"></span>
    <div class="container footer-top">
        <div class="row g-4">
            <div class="col-lg-5 col-md-6">
                <a class="hw-logo text-white d-inline-flex align-items-center gap-2" href="#home">
                    <img src="<?= landing_e($logoImage); ?>" alt="Logo Grand Dian Hotel" class="hotel-brand-logo">
                    <span>Grand Dian <span style="color:var(--clr-primary);">Hotel</span></span>
                </a>
                <p class="mt-3">Halaman publik Grand Dian Hotel Bumiayu yang menampilkan informasi kamar, fasilitas, galeri, serta akses menuju sistem hotel.</p>
                <div class="footer-badges">
                    <span class="footer-badge"><i class="bi bi-database-check"></i> Terhubung Database</span>
                    <span class="footer-badge"><i class="bi bi-shield-check"></i> Login Tersedia</span>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-3">
                <h6>Navigasi</h6>
                <ul>
                    <li><a class="footer-link" href="#kamar"><i class="bi bi-caret-right-fill"></i>Kamar</a></li>
                    <li><a class="footer-link" href="#fasilitas"><i class="bi bi-caret-right-fill"></i>Fasilitas</a></li>
                    <li><a class="footer-link" href="#tentang"><i class="bi bi-caret-right-fill"></i>Tentang</a></li>
                    <li><a class="footer-link" href="#galeri"><i class="bi bi-caret-right-fill"></i>Galeri</a></li>
                    <li><a class="footer-link" href="#cara"><i class="bi bi-caret-right-fill"></i>Cara Akses</a></li>
                    <li><a class="footer-link" href="<?= landing_e($loginUrl); ?>"><i class="bi bi-caret-right-fill"></i>Login</a></li>
                </ul>
            </div>
            <div class="col-md-6 col-lg-4">
                <h6>Informasi Sistem</h6>
                <div class="footer-contact-item">
                    <span class="ic"><i class="bi bi-geo-alt-fill"></i></span>
                    <span>Grand Dian Hotel Bumiayu, Kabupaten Brebes</span>
                </div>
                <div class="footer-contact-item">
                    <span class="ic"><i class="bi bi-door-open-fill"></i></span>
                    <span><?= (int)$stats['kamar']; ?> unit kamar dan <?= (int)$stats['tipe_kamar']; ?> tipe kamar</span>
                </div>
                <div class="footer-contact-item">
                    <span class="ic"><i class="bi bi-person-circle"></i></span>
                    <span>Akses akun admin dan user melalui halaman Login</span>
                </div>
                <a href="<?= landing_e($loginUrl); ?>" class="btn-hw-primary mt-3">Masuk ke Sistem <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>&copy; <?= date('Y'); ?> Grand Dian Hotel Bumiayu. All rights reserved.</div>
            <div>Manajemen Hotel berbasis PHP Native &amp; MariaDB</div>
        </div>
    </div>
</footer>

<div class="mobile-donate-bar">
    <a href="<?= landing_e($loginUrl); ?>" class="btn-hw-primary">Login / Pesan <i class="bi bi-arrow-right"></i></a>
</div>

<div id="scrollTop" role="button" aria-label="Kembali ke atas" tabindex="0">
    <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="23"></circle><circle class="progress" cx="26" cy="26" r="23"></circle></svg>
    <i class="bi bi-arrow-up"></i>
</div>

<script src="assets_landing/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets_landing/landing.js"></script>
</body>
</html>
