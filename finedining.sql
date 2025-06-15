-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2025 at 03:56 PM
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
-- Database: `finedining`
--

-- --------------------------------------------------------

--
-- Table structure for table `area`
--

CREATE TABLE `area` (
  `id_area` int(11) NOT NULL,
  `nama_area` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kapasitas` int(11) NOT NULL,
  `tersedia` tinyint(1) DEFAULT 1,
  `id_admin` int(11) DEFAULT NULL,
  `gambar_area` varchar(255) DEFAULT NULL,
  `nomor_meja` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `area`
--

INSERT INTO `area` (`id_area`, `nama_area`, `deskripsi`, `kapasitas`, `tersedia`, `id_admin`, `gambar_area`, `nomor_meja`) VALUES
(7, 'Window Side', 'Perfect for couples with a scenic view', 3, 1, 4, 'img/683a311fd0f65_Perfect for couples with a scenic view.png', '1'),
(9, 'Window Side', 'Perfect for couples with a scenic view', 1, 1, 4, 'img/683a311fd0f65_Perfect for couples with a scenic view.png', '2'),
(10, 'Window Side', 'Perfect for couples with a scenic view', 5, 0, 4, NULL, '3'),
(11, 'Terrace Seating', 'Al fresco dining experience', 2, 0, 4, 'img/68488f099d7ff_image 5.png', '4'),
(12, 'Terrace Seating', 'Al fresco dining experience', 4, 0, 4, NULL, '5'),
(13, 'Terrace Seating', 'Al fresco dining experience', 6, 1, 4, NULL, '6'),
(14, 'VIP Room', 'Private room for special occasions', 8, 1, 4, 'img/6848954161bc7_image 4.png', '7'),
(15, 'VIP Room', 'Private room for special occasions', 10, 1, 4, 'img/684895e6c48ea_image 4.png', '8'),
(16, 'VIP Room', 'Private room for special occasions', 10, 1, 4, 'img/684895f519100_image 4.png', '9'),
(17, 'VIP Room', 'Private room for special occasions', 12, 1, 4, 'img/6848960227066_image 4.png', '10'),
(18, 'VIP Room', 'Private room for special occasions', 20, 1, 4, 'img/6848960f27f34_image 4.png', '11');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id_customer` int(11) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `tanggal_daftar` datetime DEFAULT current_timestamp(),
  `gambar_user` varchar(255) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id_customer`, `nama_lengkap`, `email`, `no_hp`, `password`, `tanggal_daftar`, `gambar_user`, `id_user`, `username`) VALUES
(9, 'Muhammad Raihan Hidayah', 'raihanhidayah87@gmail.com', '082310699436', NULL, '2025-06-02 23:51:15', 'pages/uploads/profile_683eb6fa4fcd5.png', 7, ''),
(10, 'Reza', 'reza@gmail.com', '082310699437', '$2y$10$OfgidJdQGt54EhqB8Jx1.OETXQUP41gXF08k1Yxs9kODneeg3xTgS', '2025-06-10 00:00:00', 'pages/uploads/profile_684987fb8309b.png', 10, 'Reza'),
(12, 'ajeng', 'a@gmail.com', '001', '$2y$10$d.Ntiz9Icc7LgH2aL7FFfelF6gfJ/DZC4q7gyKi1KqsimNAb5MpJ2', '2025-06-13 00:00:00', NULL, 13, 'ajeng');

-- --------------------------------------------------------

--
-- Table structure for table `interiorrequest`
--

CREATE TABLE `interiorrequest` (
  `id_request` int(11) UNSIGNED NOT NULL,
  `id_reservasi` int(11) NOT NULL,
  `decoration_theme` varchar(50) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int(11) NOT NULL,
  `nama_menu` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `gambar_menu` varchar(255) DEFAULT NULL,
  `tersedia` tinyint(100) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id_menu`, `nama_menu`, `deskripsi`, `harga`, `kategori`, `gambar_menu`, `tersedia`, `id_admin`, `stok`) VALUES
(17, ' Hors d’oeuvres (Appetizer)', 'Canapés,Bruschetta,Stuffed Mushrooms,Shrimp Cocktail,Mini Quiche,Caprese Skewers And Spring Rolls Mini', 45000.00, 'Makanan', 'img/6838f32696a9b_Hors d\'oeuvres.jpg', 1, 4, 100),
(18, 'Main Course Beef Wellington', 'Daging sapi tenderloin dibalut puff pastry & mushroom duxelles.', 185000.00, 'Makanan', 'img/6838f41e8b126_beef.jpeg', 1, 4, 96),
(19, 'Grilled Salmon Steak', 'Fillet salmon panggang dengan saus lemon butter.', 150000.00, 'Makanan', 'img/6838f50f1b0c0_Grilled Salmon Steak.jpg', 0, 4, 0),
(20, 'Chicken Cordon Bleu', 'Dada ayam isi ham & keju, disajikan dengan saus krim jamur.', 130000.00, 'Makanan', 'img/6838f5bb75325_Chicken Cordon Bleu.jpg', 1, 4, 82),
(21, 'Molten Lava Cake', 'Kue cokelat hangat dengan isi cokelat leleh & es krim vanilla.', 65000.00, 'Dessert', 'img/6838f662edaf2_Molten Lava Cake.jpg', 1, 4, 98),
(22, 'Panna Cotta', 'Puding susu Italia lembut dengan saus berry segar.', 60000.00, 'Dessert', 'img/6838f6f35d26d_Panna Cotta.jpg', 1, 4, 99),
(23, 'Tiramisu', 'Kue kopi & mascarpone khas Italia dengan taburan kakao.', 65000.00, 'Dessert', 'img/6838f754c1f3f_Tiramisu.jpg', 1, 4, 99),
(24, 'Crème Brûlée', 'Krim vanila panggang dengan permukaan gula karamel.\r\n', 70000.00, 'Dessert', 'img/6838f940617c1_ChatGPT Image May 30, 2025, 07_17_42 AM.png', 1, 4, 60),
(25, 'Mocktail Berry Bliss (Non-Alkohol)', 'Campuran stroberi, lemon, mint & soda.', 45000.00, 'Minuman', 'img/6838f9a6173f2_Mocktail Berry Bliss.jpg', 1, 4, 91),
(26, 'Lemon Mint Sparkle	(Non-Alkohol)', 'Lemon segar dengan daun mint dan soda ringan.', 42000.00, 'Minuman', 'img/6838faaf82bf8_ChatGPT Image May 30, 2025, 07_22_30 AM.png', 1, 4, 96),
(27, 'Espresso', 'Kopi hitam pekat khas Italia, satu shot.', 30000.00, 'Minuman', 'img/6838faa384815_Espresso.jpg', 1, 4, 96),
(28, 'Vanilla Latte', 'Espresso dicampur susu & sirup vanila.', 40000.00, 'Minuman', 'img/6838fb6e56613_ChatGPT Image May 30, 2025, 07_27_17 AM.png', 1, 4, 98),
(29, 'Sparkling Berry Elixir', 'Soda berry segar dengan raspberry, blueberry, lemon, dan daun mint.', 55000.00, 'Minuman', 'img/683f3164da3dd_Sparkling Berry Elixir.jpeg', 1, 4, 19);

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_reservasi` int(11) DEFAULT NULL,
  `total_tagihan` decimal(10,2) DEFAULT NULL,
  `metode_bayar` enum('tunai','transfer','qris') DEFAULT NULL,
  `tanggal_bayar` datetime DEFAULT NULL,
  `id_kasir` int(11) DEFAULT NULL,
  `jumlah_dibayar` decimal(10,2) DEFAULT 0.00,
  `status_payment` enum('Pending Payment','Deposit Paid','Fully Paid','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_reservasi`, `total_tagihan`, `metode_bayar`, `tanggal_bayar`, `id_kasir`, `jumlah_dibayar`, `status_payment`) VALUES
(1, 1, 415000.00, 'tunai', '2025-06-11 14:37:35', NULL, 415000.00, 'Fully Paid'),
(2, 2, 390000.00, 'tunai', '2025-06-11 14:50:14', NULL, 390000.00, 'Fully Paid'),
(3, 3, 200000.00, 'transfer', '2025-06-11 16:19:15', NULL, 200000.00, 'Fully Paid'),
(4, 4, 130000.00, 'tunai', '2025-06-11 16:40:43', NULL, 130000.00, 'Fully Paid'),
(5, 5, 70000.00, 'tunai', '2025-06-11 18:12:25', NULL, 70000.00, 'Fully Paid'),
(6, 6, 200000.00, 'qris', '2025-06-11 20:29:06', NULL, 200000.00, 'Fully Paid'),
(7, 7, 305000.00, 'tunai', '2025-06-11 20:35:21', NULL, 305000.00, 'Fully Paid'),
(8, 8, 830000.00, 'transfer', '2025-06-11 21:04:55', NULL, 830000.00, 'Fully Paid'),
(9, 9, 454000.00, 'tunai', '2025-06-14 00:54:03', NULL, 454000.00, 'Fully Paid');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_reservasi` int(11) DEFAULT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status_pesanan` enum('dipesan','dimasak','diantar','selesai') DEFAULT 'dipesan',
  `id_waiter` int(11) DEFAULT NULL,
  `tanggal_pesanan` datetime DEFAULT current_timestamp(),
  `id_area` int(11) DEFAULT NULL,
  `tanggal_pesan` datetime DEFAULT current_timestamp(),
  `tanggal_selesai` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_reservasi`, `id_menu`, `jumlah`, `catatan`, `status_pesanan`, `id_waiter`, `tanggal_pesanan`, `id_area`, `tanggal_pesan`, `tanggal_selesai`) VALUES
(1, 1, 20, 2, NULL, 'selesai', NULL, '2025-06-11 14:23:37', 9, '2025-06-11 14:23:37', NULL),
(2, 1, 25, 2, NULL, 'selesai', NULL, '2025-06-11 14:23:37', 9, '2025-06-11 14:23:37', NULL),
(3, 1, 23, 1, NULL, 'selesai', NULL, '2025-06-11 14:23:37', 9, '2025-06-11 14:23:37', NULL),
(4, 2, 20, 3, NULL, 'selesai', NULL, '2025-06-11 14:47:43', 13, '2025-06-11 14:47:43', '2025-06-14 03:29:18'),
(5, 3, 20, 1, NULL, 'selesai', NULL, '2025-06-11 15:43:15', 16, '2025-06-11 15:43:15', '2025-06-11 16:37:52'),
(6, 3, 24, 1, NULL, 'selesai', NULL, '2025-06-11 15:43:15', 16, '2025-06-11 15:43:15', '2025-06-11 16:37:52'),
(7, 4, 20, 1, NULL, 'selesai', NULL, '2025-06-11 16:22:02', 12, '2025-06-11 16:22:02', '2025-06-11 17:13:44'),
(8, 5, 24, 1, NULL, 'selesai', NULL, '2025-06-11 18:08:35', 11, '2025-06-11 18:08:35', '2025-06-11 21:10:23'),
(9, 6, 20, 1, NULL, 'selesai', NULL, '2025-06-11 19:22:27', 18, '2025-06-11 19:22:27', '2025-06-11 20:34:04'),
(10, 6, 24, 1, NULL, 'selesai', NULL, '2025-06-11 19:22:27', 18, '2025-06-11 19:22:27', '2025-06-11 20:34:04'),
(11, 7, 20, 2, NULL, 'dimasak', NULL, '2025-06-11 20:28:01', 13, '2025-06-11 20:28:01', '2025-06-11 20:40:13'),
(12, 7, 25, 1, NULL, 'selesai', NULL, '2025-06-11 20:28:01', 13, '2025-06-11 20:28:01', '2025-06-11 20:39:51'),
(13, 8, 20, 5, NULL, 'selesai', NULL, '2025-06-11 20:48:11', 17, '2025-06-11 20:48:11', '2025-06-14 03:26:26'),
(14, 8, 25, 4, NULL, 'selesai', NULL, '2025-06-11 20:48:11', 17, '2025-06-11 20:48:11', '2025-06-14 03:26:26'),
(15, 9, 18, 2, NULL, 'dimasak', NULL, '2025-06-11 21:04:26', 10, '2025-06-11 21:04:26', '2025-06-14 05:07:20'),
(16, 9, 26, 2, NULL, 'dimasak', NULL, '2025-06-11 21:04:26', 10, '2025-06-11 21:04:26', '2025-06-14 05:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id_reservasi` int(11) NOT NULL,
  `id_customer` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `waktu` time NOT NULL DEFAULT '00:00:00',
  `jumlah_orang` int(11) DEFAULT NULL,
  `lokasi_meja` varchar(50) DEFAULT NULL,
  `payment_status` enum('Pending Payment','Deposit Paid','Fully Paid','Cancelled') DEFAULT 'Pending Payment',
  `catatan_tambahan` text DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_bayar` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservasi`
--

INSERT INTO `reservasi` (`id_reservasi`, `id_customer`, `tanggal`, `waktu`, `jumlah_orang`, `lokasi_meja`, `payment_status`, `catatan_tambahan`, `id_area`, `id_user`, `tanggal_bayar`) VALUES
(1, 9, '2025-06-11', '22:00:00', 1, '2', 'Pending Payment', NULL, 9, 7, '2025-06-11 09:23:47'),
(2, 10, '2025-06-11', '15:00:00', 6, '6', 'Pending Payment', NULL, 13, 10, '2025-06-11 09:48:57'),
(3, 10, '2025-06-11', '21:00:00', 10, '9', 'Fully Paid', NULL, 16, 10, '2025-06-11 11:19:03'),
(4, 10, '2025-06-11', '20:00:00', 4, '5', 'Pending Payment', NULL, 12, 10, '2025-06-11 11:22:13'),
(5, 10, '2025-06-11', '19:00:00', 2, '4', 'Pending Payment', NULL, 11, 10, '2025-06-11 13:09:47'),
(6, 10, '2025-06-18', '20:00:00', 20, '11', 'Fully Paid', NULL, 18, 10, '2025-06-11 14:27:36'),
(7, 10, '2025-06-11', '18:00:00', 6, '6', 'Pending Payment', NULL, 13, 10, '2025-06-11 15:32:56'),
(8, 10, '2025-06-26', '19:00:00', 12, '10', 'Fully Paid', NULL, 17, 10, '2025-06-11 15:48:44'),
(9, 10, '2025-06-23', '19:00:00', 5, '3', 'Pending Payment', NULL, 10, 10, '2025-06-11 16:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `id_review` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` enum('waiter','chef','kasir','admin') DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `username`, `email`, `password`, `role`, `aktif`) VALUES
(4, NULL, 'admin', 'raihanhidayah@gmail.com', '$2y$10$HP1YhwxWhw9UwttanpQpJe1zzx2lUzF3.ry6fHgd86qpTcPWBjFtW', 'admin', 1),
(7, NULL, 'rai', 'raihanhidayah87@gmail.com', '$2y$10$QBTBJg6W7fdy2KZjnMkD9O9Fn3V6oQs9tbd3CRLRRLoYph1dnJFj.', '', 1),
(8, NULL, 'kasir', 'raihanhidayah1@gmail.com', '$2y$10$mdEMCNtThEYnRCUdp40LWOW9JT7tD0Te5pJNNFJl/TgBcgsl4Y4/m', 'kasir', 1),
(10, NULL, 'Reza', 'reza@gmail.com', '$2y$10$OfgidJdQGt54EhqB8Jx1.OETXQUP41gXF08k1Yxs9kODneeg3xTgS', '', 1),
(12, NULL, 'dapur', 'dapur@gmail.com', '$2y$10$KS05.M1HlfVampt/AJIGxuLv0AgKrQjXnx3.JrJiMUT6a3.5v1Q7W', 'chef', 1),
(13, NULL, 'ajeng', 'a@gmail.com', '$2y$10$d.Ntiz9Icc7LgH2aL7FFfelF6gfJ/DZC4q7gyKi1KqsimNAb5MpJ2', '', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`id_area`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id_customer`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `interiorrequest`
--
ALTER TABLE `interiorrequest`
  ADD PRIMARY KEY (`id_request`),
  ADD KEY `id_reservasi` (`id_reservasi`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `id_reservasi` (`id_reservasi`),
  ADD UNIQUE KEY `id_reservasi_2` (`id_reservasi`),
  ADD KEY `id_kasir` (`id_kasir`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_waiter` (`id_waiter`),
  ADD KEY `id_menu` (`id_menu`),
  ADD KEY `id_reservasi` (`id_reservasi`);

--
-- Indexes for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD PRIMARY KEY (`id_reservasi`),
  ADD KEY `id_customer` (`id_customer`),
  ADD KEY `id_area` (`id_area`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `area`
--
ALTER TABLE `area`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id_customer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `interiorrequest`
--
ALTER TABLE `interiorrequest`
  MODIFY `id_request` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id_reservasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `area`
--
ALTER TABLE `area`
  ADD CONSTRAINT `area_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_user`);

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `interiorrequest`
--
ALTER TABLE `interiorrequest`
  ADD CONSTRAINT `interiorrequest_ibfk_1` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`) ON DELETE CASCADE,
  ADD CONSTRAINT `interiorrequest_ibfk_2` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`);

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_user`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`),
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`id_kasir`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `pembayaran_ibfk_3` FOREIGN KEY (`id_kasir`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `pembayaran_ibfk_4` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`),
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`),
  ADD CONSTRAINT `pesanan_ibfk_3` FOREIGN KEY (`id_waiter`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `pesanan_ibfk_5` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`),
  ADD CONSTRAINT `pesanan_ibfk_6` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`),
  ADD CONSTRAINT `pesanan_ibfk_7` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`),
  ADD CONSTRAINT `pesanan_ibfk_8` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`),
  ADD CONSTRAINT `pesanan_ibfk_9` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`);

--
-- Constraints for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD CONSTRAINT `reservasi_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `customer` (`id_customer`),
  ADD CONSTRAINT `reservasi_ibfk_2` FOREIGN KEY (`id_area`) REFERENCES `area` (`id_area`),
  ADD CONSTRAINT `reservasi_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
