-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 05:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_booking`
--

CREATE TABLE `tbl_booking` (
  `id_booking` int(11) NOT NULL,
  `kode_booking` varchar(30) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `id_tamu` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `jumlah_tamu` int(11) DEFAULT 1,
  `total` decimal(12,2) NOT NULL,
  `status` enum('pending','dikonfirmasi','checkin','checkout','dibatalkan') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_booking`
--

INSERT INTO `tbl_booking` (`id_booking`, `kode_booking`, `id_tamu`, `id_kamar`, `check_in`, `check_out`, `jumlah_tamu`, `total`, `status`) VALUES
(11, 'HTL-20260922060619', 3, 13, '2026-09-22', '2026-09-30', 10, 4000000.00, 'checkout');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kamar`
--

CREATE TABLE `tbl_kamar` (
  `id_kamar` int(11) NOT NULL,
  `nomor_kamar` varchar(20) NOT NULL,
  `id_tipe` int(11) NOT NULL,
  `status` enum('tersedia','terisi','maintenance') DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_kamar`
--

INSERT INTO `tbl_kamar` (`id_kamar`, `nomor_kamar`, `id_tipe`, `status`) VALUES
(8, '101', 7, 'tersedia'),
(9, '102', 7, 'tersedia'),
(10, '103', 7, 'tersedia'),
(11, '104', 7, 'tersedia'),
(12, '105', 7, 'tersedia'),
(13, '106', 6, 'tersedia'),
(14, '107', 6, 'tersedia'),
(15, '108', 6, 'tersedia'),
(16, '109', 6, 'tersedia'),
(17, '110', 6, 'tersedia'),
(18, '111', 4, 'maintenance'),
(19, '112', 4, 'tersedia'),
(20, '113', 4, 'tersedia'),
(21, '114', 4, 'tersedia'),
(22, '115', 4, 'tersedia'),
(23, '116', 3, 'tersedia'),
(27, '120', 5, 'tersedia'),
(28, '121', 5, 'tersedia'),
(29, '122', 5, 'tersedia'),
(30, '123', 5, 'tersedia'),
(31, '124', 5, 'tersedia'),
(32, '125', 5, 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pembayaran`
--

CREATE TABLE `tbl_pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `tanggal_bayar` datetime DEFAULT current_timestamp(),
  `jumlah_bayar` decimal(12,2) NOT NULL,
  `metode` varchar(50) DEFAULT NULL,
  `status` enum('belum_lunas','lunas') DEFAULT 'belum_lunas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pembayaran`
--

INSERT INTO `tbl_pembayaran` (`id_pembayaran`, `id_booking`, `tanggal_bayar`, `jumlah_bayar`, `metode`, `status`) VALUES
(1, 11, '2026-09-22 14:51:07', 4000000.00, 'Transfer', '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pengguna`
--

CREATE TABLE `tbl_pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `peran` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pengguna`
--

INSERT INTO `tbl_pengguna` (`id_pengguna`, `username`, `password`, `nama`, `peran`) VALUES
(1, 'admin', '$2y$10$q5Sij3zNgozqzAf9p2QVX.4lTKGd.DhVj14nEuJeFvSrgRg80shAe', 'Adil Kusuma', 'admin'),
(2, 'admin1', '6c7ca345f63f835cb353ff15bd6c5e052ec08e7a', 'Nur Ramadhan', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tamu`
--

CREATE TABLE `tbl_tamu` (
  `id_tamu` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_identitas` varchar(50) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_tamu`
--

INSERT INTO `tbl_tamu` (`id_tamu`, `nama`, `no_identitas`, `no_hp`, `email`, `alamat`) VALUES
(1, 'Adil Kusuma', '3329060912040001', '085225136016', 'adilkusuma042@gmail.com', 'Desa Karang Jongkeng'),
(2, 'Ian Gerald, S.Kom', '53030725004040005', '085314867993', 'gerald@gmail.com', 'Pagojengan Kec.Paguyangan Kab.Brebes'),
(3, 'Muhammad Isa Irawanto', '332906092345', '087867568767', 'isa@gmail.com', 'Paguyangan-Brebes'),
(4, 'Lukman', '33019293', '085227500050', 'adilkusuma042@gmail.com', 'Desa Karang Jongkeng'),
(5, 'Tarno Kece', '3329060912040001', '083834990754', 'Wafdabkc74@gmail.com', 'CIPUTIH RT02/RW02, KEC. SALEM, KAB. BREBES');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tipe_kamar`
--

CREATE TABLE `tbl_tipe_kamar` (
  `id_tipe` int(11) NOT NULL,
  `nama_tipe` varchar(100) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `fasilitas` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_tipe_kamar`
--

INSERT INTO `tbl_tipe_kamar` (`id_tipe`, `nama_tipe`, `harga`, `fasilitas`, `keterangan`) VALUES
(3, 'Moderate Room', 250000.00, 'AC, TV, Air Panas, Wifi, Kolam Renang, Taman Buah, Taman Bunga', 'View kota Bumiayu dan View Gunung Slamet'),
(4, 'Superior Room', 300000.00, 'AC, TV, Air Panas, Wifi, Kolam Renang, Taman Buah, Taman Bunga', 'View kota Bumiayu dan View Gunung Slamet'),
(5, 'Deluxe Room', 400000.00, 'AC, TV, Air Panas, Hair Dryer, Wifi, Kolam Renang, Taman Buah, Taman Bunga', 'View kota bumiayu'),
(6, 'Villa Superior', 500000.00, 'AC, TV, Air Panas, Hair Dryer, Wifi, Kolam Renang, Taman Buah, Taman Bunga', 'View Kota Bumiayu'),
(7, 'Villa Deluxe', 450000.00, 'AC, TV, Air Panas, Hair Dryer, Wifi, Kolam Renang, Taman Bunga, Taman Buah', 'View Kota Bumiayu');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_booking`
--
ALTER TABLE `tbl_booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `id_pengguna` (`id_pengguna`),
  ADD KEY `id_tamu` (`id_tamu`),
  ADD KEY `id_kamar` (`id_kamar`);

--
-- Indexes for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  ADD PRIMARY KEY (`id_kamar`),
  ADD UNIQUE KEY `nomor_kamar` (`nomor_kamar`),
  ADD KEY `id_tipe` (`id_tipe`);

--
-- Indexes for table `tbl_pembayaran`
--
ALTER TABLE `tbl_pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_booking` (`id_booking`);

--
-- Indexes for table `tbl_pengguna`
--
ALTER TABLE `tbl_pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tbl_tamu`
--
ALTER TABLE `tbl_tamu`
  ADD PRIMARY KEY (`id_tamu`);

--
-- Indexes for table `tbl_tipe_kamar`
--
ALTER TABLE `tbl_tipe_kamar`
  ADD PRIMARY KEY (`id_tipe`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_booking`
--
ALTER TABLE `tbl_booking`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  MODIFY `id_kamar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_pembayaran`
--
ALTER TABLE `tbl_pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_pengguna`
--
ALTER TABLE `tbl_pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_tamu`
--
ALTER TABLE `tbl_tamu`
  MODIFY `id_tamu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_tipe_kamar`
--
ALTER TABLE `tbl_tipe_kamar`
  MODIFY `id_tipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_booking`
--
ALTER TABLE `tbl_booking`
  ADD CONSTRAINT `tbl_booking_ibfk_1` FOREIGN KEY (`id_tamu`) REFERENCES `tbl_tamu` (`id_tamu`),
  ADD CONSTRAINT `tbl_booking_ibfk_2` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`);

--
-- Constraints for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  ADD CONSTRAINT `tbl_kamar_ibfk_1` FOREIGN KEY (`id_tipe`) REFERENCES `tbl_tipe_kamar` (`id_tipe`);

--
-- Constraints for table `tbl_pembayaran`
--
ALTER TABLE `tbl_pembayaran`
  ADD CONSTRAINT `tbl_pembayaran_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `tbl_booking` (`id_booking`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
