-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Nov 2025 pada 09.21
-- Versi server: 8.4.6
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `globetix`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_admin_approve_refund` (IN `p_refund_id` INT, IN `p_admin_user_id` INT)   BEGIN
    DECLARE v_booking_id INT;

    SELECT booking_id INTO v_booking_id FROM refund WHERE refund_id = p_refund_id;
    IF v_booking_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Refund not found';
    END IF;

    START TRANSACTION;

    UPDATE refund
    SET status_refund = 'disetujui', tanggal_selesai = NOW()
    WHERE refund_id = p_refund_id;

    UPDATE booking
    SET status_booking = 'cancelled', last_update = NOW()
    WHERE booking_id = v_booking_id;

    UPDATE kursi
    INNER JOIN booking_seat ON kursi.seat_id = booking_seat.seat_id
    SET kursi.status = 'tersedia', kursi.updated_at = NOW()
    WHERE booking_seat.booking_id = v_booking_id;

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_dashboard_summary` ()   BEGIN
    SELECT 'Tren Booking' AS section;
    SELECT * FROM v_tren_booking LIMIT 6;

    SELECT 'Pendapatan Mingguan' AS section;
    SELECT * FROM v_pendapatan_mingguan LIMIT 6;

    SELECT 'Status Booking' AS section;
    SELECT * FROM v_status_booking;

    SELECT 'Pengguna Baru' AS section;
    SELECT * FROM v_pengguna_baru LIMIT 6;

    SELECT 'Jadwal Populer' AS section;
    SELECT * FROM v_jadwal_populer;

    SELECT 'Refund Summary' AS section;
    SELECT * FROM v_refund_summary;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking`
--

CREATE TABLE `booking` (
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `jadwal_id` int NOT NULL,
  `tanggal_booking` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_harga` decimal(12,2) DEFAULT NULL,
  `status_booking` enum('pending','confirmed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `kode_booking` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seat_locked_until` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `jadwal_pulang_id` int DEFAULT NULL
) ;

--
-- Dumping data untuk tabel `booking`
--

INSERT INTO `booking` (`booking_id`, `user_id`, `jadwal_id`, `tanggal_booking`, `total_harga`, `status_booking`, `kode_booking`, `seat_locked_until`, `last_update`, `jadwal_pulang_id`) VALUES
(88, 41, 69, '2025-10-31 00:43:28', 145000.00, 'confirmed', 'TRAVEL-961319', '2025-10-31 00:53:28', '2025-10-31 00:52:00', 72),
(89, 41, 72, '2025-10-31 00:43:28', 245000.00, 'confirmed', 'TRAVEL-690726', '2025-10-31 00:53:28', '2025-10-31 00:52:03', NULL),
(90, 43, 71, '2025-10-31 00:50:38', 1100000.00, 'cancelled', 'TRAVEL-898791', '2025-10-31 01:00:38', '2025-10-31 01:23:16', 75),
(91, 43, 75, '2025-10-31 00:50:38', 1200000.00, 'confirmed', 'TRAVEL-879349', '2025-10-31 01:00:38', '2025-10-31 00:51:57', NULL),
(92, 43, 74, '2025-10-31 01:04:00', 450000.00, 'pending', 'TRAVEL-793531', '2025-10-31 01:14:00', '2025-10-31 01:04:00', 0),
(93, 43, 74, '2025-10-31 01:04:00', 450000.00, 'pending', 'TRAVEL-883281', '2025-10-31 01:14:00', '2025-10-31 01:04:00', 0),
(94, 44, 66, '2025-10-31 01:07:29', 250000.00, 'confirmed', 'TRAVEL-640028', '2025-10-31 01:17:29', '2025-10-31 01:15:17', 72),
(95, 44, 72, '2025-10-31 01:07:29', 245000.00, 'confirmed', 'TRAVEL-934928', '2025-10-31 01:17:29', '2025-11-05 07:35:26', NULL),
(96, 44, 66, '2025-10-31 01:07:29', 250000.00, 'confirmed', 'TRAVEL-393212', '2025-10-31 01:17:29', '2025-10-31 01:15:20', 72),
(97, 44, 72, '2025-10-31 01:07:29', 245000.00, 'confirmed', 'TRAVEL-987872', '2025-10-31 01:17:29', '2025-11-05 07:35:34', NULL),
(98, 39, 73, '2025-11-05 06:54:41', 200000.00, 'confirmed', 'TRAVEL-196522', '2025-11-05 07:04:41', '2025-11-05 06:55:02', 0),
(99, 42, 72, '2025-11-05 07:14:32', 245000.00, 'confirmed', 'TRAVEL-676719', '2025-11-05 07:24:32', '2025-11-05 07:22:18', 0),
(100, 44, 74, '2025-11-05 07:33:12', 450000.00, 'cancelled', 'TRAVEL-867759', '2025-11-05 07:43:12', '2025-11-05 07:34:31', 0),
(101, 42, 75, '2025-11-05 07:43:09', 600000.00, 'confirmed', 'TRAVEL-835439', '2025-11-05 07:53:09', '2025-11-05 07:43:55', 76),
(102, 42, 76, '2025-11-05 07:43:09', 1000000.00, 'confirmed', 'TRAVEL-985512', '2025-11-05 07:53:09', '2025-11-05 07:43:57', NULL),
(103, 42, 72, '2025-11-05 07:45:20', 245000.00, 'pending', 'TRAVEL-131562', '2025-11-05 07:55:20', '2025-11-05 07:45:20', 0),
(104, 42, 72, '2025-11-05 07:49:44', 245000.00, 'pending', 'TRAVEL-261222', '2025-11-05 07:59:44', '2025-11-05 07:49:44', 0),
(105, 44, 74, '2025-11-05 07:50:19', 450000.00, 'cancelled', 'TRAVEL-516813', '2025-11-05 08:00:19', '2025-11-05 07:50:29', 0),
(106, 44, 73, '2025-11-05 07:51:08', 200000.00, 'pending', 'TRAVEL-111738', '2025-11-05 08:01:08', '2025-11-05 07:51:08', 0),
(107, 42, 73, '2025-11-05 07:54:32', 200000.00, 'cancelled', 'TRAVEL-207569', '2025-11-05 08:04:32', '2025-11-05 07:54:36', 0),
(108, 44, 72, '2025-11-05 08:03:54', 245000.00, 'pending', 'TRAVEL-776179', '2025-11-05 08:13:54', '2025-11-05 08:03:54', 0),
(109, 39, 72, '2025-11-05 08:13:33', 245000.00, 'pending', 'TRAVEL-610735', '2025-11-05 08:23:33', '2025-11-05 08:13:33', 0),
(110, 39, 72, '2025-11-05 08:17:22', 245000.00, 'cancelled', 'TRAVEL-315563', '2025-11-05 08:27:22', '2025-11-05 08:18:47', 0);

--
-- Trigger `booking`
--
DELIMITER $$
CREATE TRIGGER `trg_booking_generate_uuid` BEFORE INSERT ON `booking` FOR EACH ROW BEGIN
    IF NEW.kode_booking IS NULL OR NEW.kode_booking = '' THEN
        SET NEW.kode_booking = UUID();
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_booking_insert` AFTER INSERT ON `booking` FOR EACH ROW BEGIN
    INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
    VALUES ('booking','INSERT',NEW.booking_id,
            CONCAT('Booking baru: kode=',NEW.kode_booking,
                   ', status=',NEW.status_booking,
                   ', total=',NEW.total_harga));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_booking_insert_timer` BEFORE INSERT ON `booking` FOR EACH ROW BEGIN
    IF NEW.seat_locked_until IS NULL THEN
        SET NEW.seat_locked_until = NOW() + INTERVAL 10 MINUTE;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_booking_update` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
    IF (OLD.status_booking <> NEW.status_booking) THEN
        INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
        VALUES ('booking','UPDATE',NEW.booking_id,
                CONCAT('Booking update: kode=',NEW.kode_booking,
                       ', status dari ',OLD.status_booking,' ke ',NEW.status_booking));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking_seat`
--

CREATE TABLE `booking_seat` (
  `booking_seat_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `seat_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `booking_seat`
--

INSERT INTO `booking_seat` (`booking_seat_id`, `booking_id`, `seat_id`) VALUES
(84, 88, 466),
(85, 89, 551),
(86, 90, 539),
(87, 90, 540),
(88, 91, 629),
(89, 91, 630),
(90, 92, 596),
(91, 93, 597),
(92, 94, 397),
(93, 95, 554),
(94, 96, 396),
(95, 97, 555),
(96, 98, 568),
(97, 99, 552),
(98, 100, 596),
(99, 101, 628),
(100, 102, 645),
(101, 103, 564),
(102, 104, 563),
(103, 105, 598),
(104, 106, 566),
(105, 107, 567),
(106, 108, 552),
(107, 109, 558),
(108, 110, 561);

-- --------------------------------------------------------

--
-- Struktur dari tabel `eticket`
--

CREATE TABLE `eticket` (
  `eticket_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `kode_booking` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_terbit` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `eticket`
--

INSERT INTO `eticket` (`eticket_id`, `booking_id`, `kode_booking`, `qr_code`, `tanggal_terbit`) VALUES
(23, 90, 'TRAVEL-898791', 'cb47abea-b5f3-11f0-86f8-088fc349f2f5', '2025-10-31 00:51:54'),
(24, 91, 'TRAVEL-879349', 'cd36184b-b5f3-11f0-86f8-088fc349f2f5', '2025-10-31 00:51:57'),
(25, 88, 'TRAVEL-961319', 'cf4268c6-b5f3-11f0-86f8-088fc349f2f5', '2025-10-31 00:52:00'),
(26, 89, 'TRAVEL-690726', 'd12209b5-b5f3-11f0-86f8-088fc349f2f5', '2025-10-31 00:52:03'),
(27, 94, 'TRAVEL-640028', '0fa6a197-b5f7-11f0-86f8-088fc349f2f5', '2025-10-31 01:15:17'),
(28, 96, 'TRAVEL-393212', '11ad15dc-b5f7-11f0-86f8-088fc349f2f5', '2025-10-31 01:15:20'),
(29, 98, 'TRAVEL-196522', '5a5b1686-ba14-11f0-86f8-088fc349f2f5', '2025-11-05 06:55:02'),
(30, 100, 'TRAVEL-867759', 'c9d6828d-ba19-11f0-86f8-088fc349f2f5', '2025-11-05 07:33:57'),
(31, 95, 'TRAVEL-934928', 'ff2784b0-ba19-11f0-86f8-088fc349f2f5', '2025-11-05 07:35:26'),
(32, 97, 'TRAVEL-987872', '039cc4b8-ba1a-11f0-86f8-088fc349f2f5', '2025-11-05 07:35:34'),
(33, 101, 'TRAVEL-835439', '2e961edc-ba1b-11f0-86f8-088fc349f2f5', '2025-11-05 07:43:55'),
(34, 102, 'TRAVEL-985512', '2fc08cbc-ba1b-11f0-86f8-088fc349f2f5', '2025-11-05 07:43:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `jadwal_id` int NOT NULL,
  `provider_id` int DEFAULT NULL,
  `asal` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tujuan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_keberangkatan` date DEFAULT NULL,
  `jam_keberangkatan` time DEFAULT NULL,
  `estimasi_durasi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_layanan` enum('reguler','premium','bisnis') COLLATE utf8mb4_general_ci DEFAULT 'reguler',
  `harga_perkursi` decimal(10,2) DEFAULT NULL,
  `status` enum('tersedia','batal','selesai') COLLATE utf8mb4_general_ci DEFAULT 'tersedia',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`jadwal_id`, `provider_id`, `asal`, `tujuan`, `tanggal_keberangkatan`, `jam_keberangkatan`, `estimasi_durasi`, `kelas_layanan`, `harga_perkursi`, `status`, `created_at`, `updated_at`) VALUES
(66, 4, 'Jakarta', 'Bandung', '2025-11-04', '19:30:00', '2 jam', 'bisnis', 250000.00, 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54'),
(67, 3, 'Jakarta', 'Bandung', '2025-11-04', '19:00:00', '2', 'bisnis', 175000.00, 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35'),
(68, 29, 'Jakarta', 'Bandung', '2025-11-04', '17:00:00', '2 Jam', 'reguler', 75000.00, 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08'),
(69, 22, 'Jakarta', 'Bandung', '2025-11-04', '18:30:00', '2 Jam', 'premium', 145000.00, 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44'),
(70, 25, 'Yogyakarta', 'Denpasar', '2025-11-01', '11:00:00', '18 Jam', 'reguler', 400000.00, 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33'),
(71, 33, 'Yogyakarta', 'Denpasar', '2025-11-01', '10:00:00', '18 Jam', 'bisnis', 550000.00, 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14'),
(72, 4, 'Bandung', 'Jakarta', '2025-11-08', '10:00:00', '2 Jam', 'bisnis', 245000.00, 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01'),
(73, 3, 'Bandung', 'Jakarta', '2025-11-08', '20:00:00', '2 Jam', 'premium', 200000.00, 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36'),
(74, 25, 'Denpasar', 'Yogyakarta', '2025-11-09', '07:00:00', '18 Jam', 'premium', 450000.00, 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22'),
(75, 33, 'Denpasar', 'Yogyakarta', '2025-11-09', '06:30:00', '18 Jam', 'bisnis', 600000.00, 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15'),
(76, 25, 'Yogyakarta', 'Denpasar', '2025-11-15', '20:00:00', '13 Jam', 'bisnis', 1000000.00, 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kursi`
--

CREATE TABLE `kursi` (
  `seat_id` int NOT NULL,
  `jadwal_id` int DEFAULT NULL,
  `nomor_kursi` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('tersedia','dipesan','terjual','dibatalkan','locked') COLLATE utf8mb4_general_ci DEFAULT 'tersedia',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kursi`
--

INSERT INTO `kursi` (`seat_id`, `jadwal_id`, `nomor_kursi`, `status`, `created_at`, `updated_at`, `locked_until`) VALUES
(396, 66, 'A1', 'terjual', '2025-10-31 00:14:54', '2025-10-31 01:15:20', '2025-10-31 01:17:29'),
(397, 66, 'B1', 'terjual', '2025-10-31 00:14:54', '2025-10-31 01:15:17', '2025-10-31 01:17:29'),
(398, 66, 'C1', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(399, 66, 'A2', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(400, 66, 'B2', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(401, 66, 'C2', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(402, 66, 'A3', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(403, 66, 'B3', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(404, 66, 'C3', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(405, 66, 'A4', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(406, 66, 'B4', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(407, 66, 'C4', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(408, 66, 'A5', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(409, 66, 'B5', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(410, 66, 'C5', 'tersedia', '2025-10-31 00:14:54', '2025-10-31 00:14:54', NULL),
(411, 67, 'A1', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(412, 67, 'B1', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(413, 67, 'C1', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(414, 67, 'A2', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(415, 67, 'B2', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(416, 67, 'C2', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(417, 67, 'A3', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(418, 67, 'B3', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(419, 67, 'C3', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(420, 67, 'A4', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(421, 67, 'B4', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(422, 67, 'C4', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(423, 67, 'A5', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(424, 67, 'B5', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(425, 67, 'C5', 'tersedia', '2025-10-31 00:15:35', '2025-10-31 00:15:35', NULL),
(426, 68, 'A1', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(427, 68, 'B1', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(428, 68, 'C1', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(429, 68, 'D1', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(430, 68, 'A2', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(431, 68, 'B2', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(432, 68, 'C2', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(433, 68, 'D2', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(434, 68, 'A3', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(435, 68, 'B3', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(436, 68, 'C3', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(437, 68, 'D3', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(438, 68, 'A4', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(439, 68, 'B4', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(440, 68, 'C4', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(441, 68, 'D4', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(442, 68, 'A5', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(443, 68, 'B5', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(444, 68, 'C5', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(445, 68, 'D5', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(446, 68, 'A6', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(447, 68, 'B6', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(448, 68, 'C6', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(449, 68, 'D6', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(450, 68, 'A7', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(451, 68, 'B7', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(452, 68, 'C7', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(453, 68, 'D7', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(454, 68, 'A8', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(455, 68, 'B8', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(456, 68, 'C8', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(457, 68, 'D8', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(458, 68, 'A9', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(459, 68, 'B9', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(460, 68, 'C9', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(461, 68, 'D9', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(462, 68, 'A10', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(463, 68, 'B10', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(464, 68, 'C10', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(465, 68, 'D10', 'tersedia', '2025-10-31 00:16:08', '2025-10-31 00:16:08', NULL),
(466, 69, 'A1', 'terjual', '2025-10-31 00:16:44', '2025-10-31 00:52:00', '2025-10-31 00:53:28'),
(467, 69, 'B1', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(468, 69, 'C1', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(469, 69, 'A2', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(470, 69, 'B2', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(471, 69, 'C2', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(472, 69, 'A3', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(473, 69, 'B3', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(474, 69, 'C3', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(475, 69, 'A4', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(476, 69, 'B4', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(477, 69, 'C4', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(478, 69, 'A5', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(479, 69, 'B5', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(480, 69, 'C5', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(481, 69, 'A6', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(482, 69, 'B6', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(483, 69, 'C6', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(484, 69, 'A7', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(485, 69, 'B7', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(486, 69, 'C7', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(487, 69, 'A8', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(488, 69, 'B8', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(489, 69, 'C8', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(490, 69, 'A9', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(491, 69, 'B9', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(492, 69, 'C9', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(493, 69, 'A10', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(494, 69, 'B10', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(495, 69, 'C10', 'tersedia', '2025-10-31 00:16:44', '2025-10-31 00:16:44', NULL),
(496, 70, 'A1', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(497, 70, 'B1', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(498, 70, 'C1', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(499, 70, 'D1', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(500, 70, 'A2', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(501, 70, 'B2', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(502, 70, 'C2', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(503, 70, 'D2', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(504, 70, 'A3', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(505, 70, 'B3', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(506, 70, 'C3', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(507, 70, 'D3', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(508, 70, 'A4', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(509, 70, 'B4', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(510, 70, 'C4', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(511, 70, 'D4', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(512, 70, 'A5', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(513, 70, 'B5', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(514, 70, 'C5', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(515, 70, 'D5', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(516, 70, 'A6', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(517, 70, 'B6', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(518, 70, 'C6', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(519, 70, 'D6', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(520, 70, 'A7', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(521, 70, 'B7', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(522, 70, 'C7', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(523, 70, 'D7', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(524, 70, 'A8', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(525, 70, 'B8', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(526, 70, 'C8', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(527, 70, 'D8', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(528, 70, 'A9', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(529, 70, 'B9', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(530, 70, 'C9', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(531, 70, 'D9', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(532, 70, 'A10', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(533, 70, 'B10', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(534, 70, 'C10', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(535, 70, 'D10', 'tersedia', '2025-10-31 00:17:33', '2025-10-31 00:17:33', NULL),
(536, 71, 'A1', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(537, 71, 'B1', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(538, 71, 'C1', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(539, 71, 'A2', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 01:23:16', NULL),
(540, 71, 'B2', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 01:23:16', NULL),
(541, 71, 'C2', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(542, 71, 'A3', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(543, 71, 'B3', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(544, 71, 'C3', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(545, 71, 'A4', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(546, 71, 'B4', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(547, 71, 'C4', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(548, 71, 'A5', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(549, 71, 'B5', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(550, 71, 'C5', 'tersedia', '2025-10-31 00:18:14', '2025-10-31 00:18:14', NULL),
(551, 72, 'A1', 'terjual', '2025-10-31 00:19:01', '2025-10-31 00:52:03', '2025-10-31 00:53:28'),
(552, 72, 'B1', 'tersedia', '2025-10-31 00:19:01', '2025-11-05 08:17:03', NULL),
(553, 72, 'C1', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(554, 72, 'A2', 'terjual', '2025-10-31 00:19:01', '2025-11-05 07:35:26', NULL),
(555, 72, 'B2', 'terjual', '2025-10-31 00:19:01', '2025-11-05 07:35:34', NULL),
(556, 72, 'C2', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(557, 72, 'A3', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(558, 72, 'B3', 'dipesan', '2025-10-31 00:19:01', '2025-11-05 08:13:33', '2025-11-05 08:23:33'),
(559, 72, 'C3', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(560, 72, 'A4', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(561, 72, 'B4', 'tersedia', '2025-10-31 00:19:01', '2025-11-05 08:18:47', NULL),
(562, 72, 'C4', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(563, 72, 'A5', 'tersedia', '2025-10-31 00:19:01', '2025-11-05 08:01:48', NULL),
(564, 72, 'B5', 'tersedia', '2025-10-31 00:19:01', '2025-11-05 07:55:44', NULL),
(565, 72, 'C5', 'tersedia', '2025-10-31 00:19:01', '2025-10-31 00:19:01', NULL),
(566, 73, 'A1', 'tersedia', '2025-10-31 00:19:36', '2025-11-05 08:01:48', NULL),
(567, 73, 'B1', 'tersedia', '2025-10-31 00:19:36', '2025-11-05 07:54:36', NULL),
(568, 73, 'C1', 'terjual', '2025-10-31 00:19:36', '2025-11-05 06:55:02', '2025-11-05 07:04:41'),
(569, 73, 'A2', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(570, 73, 'B2', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(571, 73, 'C2', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(572, 73, 'A3', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(573, 73, 'B3', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(574, 73, 'C3', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(575, 73, 'A4', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(576, 73, 'B4', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(577, 73, 'C4', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(578, 73, 'A5', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(579, 73, 'B5', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(580, 73, 'C5', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(581, 73, 'A6', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(582, 73, 'B6', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(583, 73, 'C6', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(584, 73, 'A7', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(585, 73, 'B7', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(586, 73, 'C7', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(587, 73, 'A8', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(588, 73, 'B8', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(589, 73, 'C8', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(590, 73, 'A9', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(591, 73, 'B9', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(592, 73, 'C9', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(593, 73, 'A10', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(594, 73, 'B10', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(595, 73, 'C10', 'tersedia', '2025-10-31 00:19:36', '2025-10-31 00:19:36', NULL),
(596, 74, 'A1', 'tersedia', '2025-10-31 00:20:22', '2025-11-05 07:34:31', NULL),
(597, 74, 'B1', 'tersedia', '2025-10-31 00:20:22', '2025-11-05 06:54:15', NULL),
(598, 74, 'C1', 'tersedia', '2025-10-31 00:20:22', '2025-11-05 07:50:29', NULL),
(599, 74, 'A2', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(600, 74, 'B2', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(601, 74, 'C2', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(602, 74, 'A3', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(603, 74, 'B3', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(604, 74, 'C3', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(605, 74, 'A4', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(606, 74, 'B4', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(607, 74, 'C4', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(608, 74, 'A5', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(609, 74, 'B5', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(610, 74, 'C5', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(611, 74, 'A6', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(612, 74, 'B6', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(613, 74, 'C6', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(614, 74, 'A7', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(615, 74, 'B7', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(616, 74, 'C7', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(617, 74, 'A8', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(618, 74, 'B8', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(619, 74, 'C8', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(620, 74, 'A9', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(621, 74, 'B9', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(622, 74, 'C9', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(623, 74, 'A10', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(624, 74, 'B10', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(625, 74, 'C10', 'tersedia', '2025-10-31 00:20:22', '2025-10-31 00:20:22', NULL),
(626, 75, 'A1', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(627, 75, 'B1', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(628, 75, 'C1', 'terjual', '2025-10-31 00:21:15', '2025-11-05 07:43:55', '2025-11-05 07:53:09'),
(629, 75, 'A2', 'terjual', '2025-10-31 00:21:15', '2025-10-31 00:51:57', '2025-10-31 01:00:38'),
(630, 75, 'B2', 'terjual', '2025-10-31 00:21:15', '2025-10-31 00:51:57', '2025-10-31 01:00:38'),
(631, 75, 'C2', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(632, 75, 'A3', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(633, 75, 'B3', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(634, 75, 'C3', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(635, 75, 'A4', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(636, 75, 'B4', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(637, 75, 'C4', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(638, 75, 'A5', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(639, 75, 'B5', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(640, 75, 'C5', 'tersedia', '2025-10-31 00:21:15', '2025-10-31 00:21:15', NULL),
(641, 76, 'A1', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(642, 76, 'B1', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(643, 76, 'C1', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(644, 76, 'A2', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(645, 76, 'B2', 'terjual', '2025-11-05 07:42:02', '2025-11-05 07:43:57', '2025-11-05 07:53:09'),
(646, 76, 'C2', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(647, 76, 'A3', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(648, 76, 'B3', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(649, 76, 'C3', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(650, 76, 'A4', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(651, 76, 'B4', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(652, 76, 'C4', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(653, 76, 'A5', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(654, 76, 'B5', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL),
(655, 76, 'C5', 'tersedia', '2025-11-05 07:42:02', '2025-11-05 07:42:02', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `expires_at`, `created_at`) VALUES
('ssyfnblc@gmail.com', 'ec6a5f64ec721627a36fdf0311cd2be20c2405de79ccdc5b277a159965c88fff', '2025-10-30 02:07:56', '2025-10-30 07:57:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `payment_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `metode` enum('transfer_bank','qris') COLLATE utf8mb4_general_ci NOT NULL,
  `no_identitas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_bayar` decimal(12,2) DEFAULT NULL,
  `status_pembayaran` enum('pending','sukses','gagal') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `tanggal_bayar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `batas_waktu` timestamp NULL DEFAULT NULL,
  `payment_gateway_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bukti_bayar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`payment_id`, `booking_id`, `metode`, `no_identitas`, `jumlah_bayar`, `status_pembayaran`, `tanggal_bayar`, `batas_waktu`, `payment_gateway_ref`, `bukti_bayar`) VALUES
(58, 88, 'transfer_bank', '-', 145000.00, 'sukses', '2025-10-31 00:52:00', NULL, NULL, 'bukti_88_89_1761871454.png'),
(59, 89, 'transfer_bank', '-', 245000.00, 'sukses', '2025-10-31 00:52:03', NULL, NULL, 'bukti_88_89_1761871454.png'),
(60, 90, 'transfer_bank', '-', 1100000.00, 'sukses', '2025-10-31 00:51:54', NULL, NULL, 'bukti_90_91_1761871845.pdf'),
(61, 91, 'transfer_bank', '-', 1200000.00, 'sukses', '2025-10-31 00:51:57', NULL, NULL, 'bukti_90_91_1761871845.pdf'),
(62, 92, 'transfer_bank', '-', 450000.00, 'pending', '2025-10-31 01:04:08', NULL, NULL, 'bukti_92_93_1761872648.pdf'),
(63, 93, 'transfer_bank', '-', 450000.00, 'pending', '2025-10-31 01:04:08', NULL, NULL, 'bukti_92_93_1761872648.pdf'),
(64, 94, 'transfer_bank', '-', 250000.00, 'sukses', '2025-10-31 01:15:17', NULL, NULL, 'bukti_94_95_96_97_1761872947.jpg'),
(65, 95, 'transfer_bank', '-', 245000.00, 'sukses', '2025-11-05 07:35:26', NULL, NULL, 'bukti_94_95_96_97_1761872947.jpg'),
(66, 96, 'transfer_bank', '-', 250000.00, 'sukses', '2025-10-31 01:15:20', NULL, NULL, 'bukti_94_95_96_97_1761872947.jpg'),
(67, 97, 'transfer_bank', '-', 245000.00, 'sukses', '2025-11-05 07:35:34', NULL, NULL, 'bukti_94_95_96_97_1761872947.jpg'),
(68, 98, 'transfer_bank', '-', 200000.00, 'sukses', '2025-11-05 06:55:02', NULL, NULL, 'bukti_98_1762325688.png'),
(69, 100, 'transfer_bank', '-', 450000.00, 'sukses', '2025-11-05 07:33:57', NULL, NULL, 'bukti_100_1762327999.png'),
(70, 101, 'transfer_bank', '-', 600000.00, 'sukses', '2025-11-05 07:43:55', NULL, NULL, 'bukti_101_102_1762328611.jpg'),
(71, 102, 'transfer_bank', '-', 1000000.00, 'sukses', '2025-11-05 07:43:57', NULL, NULL, 'bukti_101_102_1762328611.jpg'),
(72, 104, 'transfer_bank', '-', 245000.00, 'pending', '2025-11-05 07:49:50', NULL, NULL, 'bukti_104_1762328990.pdf');

--
-- Trigger `pembayaran`
--
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_insert` AFTER INSERT ON `pembayaran` FOR EACH ROW BEGIN
    INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
    VALUES ('pembayaran','INSERT',NEW.booking_id,
            CONCAT('Pembayaran baru: jumlah=',NEW.jumlah_bayar,
                   ', metode=',NEW.metode,
                   ', status=',NEW.status_pembayaran,
                   ', bukti=',IFNULL(NEW.bukti_bayar,'N/A')));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_update` AFTER UPDATE ON `pembayaran` FOR EACH ROW BEGIN
    IF (OLD.status_pembayaran <> NEW.status_pembayaran OR OLD.bukti_bayar <> NEW.bukti_bayar) THEN
        INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
        VALUES ('pembayaran','UPDATE',NEW.booking_id,
                CONCAT('Pembayaran update: status dari ',OLD.status_pembayaran,' ke ',NEW.status_pembayaran,
                       ', bukti=',IFNULL(NEW.bukti_bayar,'N/A')));
    END IF;

    IF (OLD.status_pembayaran <> 'sukses' AND NEW.status_pembayaran = 'sukses') THEN
        IF (SELECT COUNT(*) FROM eticket WHERE booking_id = NEW.booking_id) = 0 THEN
            INSERT INTO eticket (booking_id, kode_booking, qr_code)
            VALUES (
                NEW.booking_id,
                (SELECT kode_booking FROM booking WHERE booking_id = NEW.booking_id),
                UUID()
            );
        END IF;

        UPDATE booking
        SET status_booking = 'confirmed', last_update = NOW()
        WHERE booking_id = NEW.booking_id;

        UPDATE kursi
        INNER JOIN booking_seat ON kursi.seat_id = booking_seat.seat_id
        SET kursi.status = 'terjual', kursi.updated_at = NOW()
        WHERE booking_seat.booking_id = NEW.booking_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penumpang`
--

CREATE TABLE `penumpang` (
  `penumpang_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `nama_penumpang` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_identitas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penumpang`
--

INSERT INTO `penumpang` (`penumpang_id`, `booking_id`, `nama_penumpang`, `no_identitas`, `no_hp`, `email`) VALUES
(71, 88, 'Jihan Khansa', '330112345123', '0874234567', 'jihan@gmail.com'),
(72, 89, 'Jihan Khansa', '330112345123', '0874234567', 'jihan@gmail.com'),
(73, 90, 'Refi Yuni', '33012345', '2345', 'refi@gmail.com'),
(74, 91, 'Refi Yuni', '33012345', '2345', 'refi@gmail.com'),
(75, 90, 'Syafa Nabila', '3301345678', '0874234567', 'syafa@gmail.com'),
(76, 91, 'Syafa Nabila', '3301345678', '0874234567', 'syafa@gmail.com'),
(77, 92, 'Jihan Khansa', '330112345123', '0874234567', 'jihan@gmail.com'),
(78, 93, 'Refi Yuni', '330112345123', '2345', 'refi@gmail.com'),
(79, 94, 'Aku', '3330123', '0897654', 'aku@gmail.com'),
(80, 95, 'Aku', '3330123', '0897654', 'aku@gmail.com'),
(81, 96, 'Kamu', '330153267', '08568', 'kamu@gmai.com'),
(82, 97, 'Kamu', '330153267', '08568', 'kamu@gmai.com'),
(83, 98, 'SYAFA NABILA', '3301123412341234', '089516737121', 'syafanblca29@gmail.com'),
(84, 99, 'Graselin Adellla Simajuntak', '3330132378', '081324', 'cece@gmail.com'),
(85, 100, 'Ivan Noor', '12345678', '13245', 'ivan@gmail.com'),
(86, 101, 'Graselin Adellla Simajuntak', '3330132378', '081324', 'cece@gmail.com'),
(87, 102, 'Graselin Adellla Simajuntak', '3330132378', '081324', 'cece@gmail.com'),
(88, 103, 'asdf', 'sfd', 'f', 'asd'),
(89, 104, 'Graselin Adellla Simajuntak', '3330132378', '081324', 'cece@gmail.com'),
(90, 105, 'SYAFA NABILA', '3301123412341234', '089516737121', 'ADS@GMAIL.COM'),
(91, 106, 'SYAFA NABILA', '3301123412341234', '089516737121', 'refi@gmail.com'),
(92, 107, 'Graselin Adellla Simajuntak', '3330132378', '081324', 'cece@gmail.com'),
(93, 108, 'SYAFA NABILA', '3301123412341234', 'DAFSADFS', 'sadf@gmail.com'),
(94, 109, 'Saya', '1234', '234', 'syafanblca29@gmail.com'),
(95, 110, 'Saya', '1234', '234', 'syafanblca29@gmail.com');

--
-- Trigger `penumpang`
--
DELIMITER $$
CREATE TRIGGER `trg_penumpang_limit` BEFORE INSERT ON `penumpang` FOR EACH ROW BEGIN
    DECLARE jml INT;
    SELECT COUNT(*) INTO jml FROM penumpang WHERE booking_id = NEW.booking_id;
    IF jml >= 8 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Maksimal 8 penumpang per booking';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `providers`
--

CREATE TABLE `providers` (
  `provider_id` int NOT NULL,
  `nama_provider` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kontak` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `providers`
--

INSERT INTO `providers` (`provider_id`, `nama_provider`, `kontak`, `alamat`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Travel Makmur Jaya', '081234567890', 'Jl. Merdeka No. 10, Jakarta', 'Operator travel jurusan A-B', 'aktif', '2025-10-02 12:35:00', '2025-10-02 12:35:00'),
(2, 'BlueLine Travel', '021-555555', 'Jl. Merdeka No. 1 Jakarta', 'Penyedia bus pariwisata nyaman', 'aktif', '2025-10-02 16:19:52', '2025-10-02 16:19:52'),
(3, 'Fast Travel', '081234567892', 'Jl. Cepat No. 5, Jakarta', 'Provider bus cepat dan nyaman', 'aktif', '2025-10-03 06:13:53', '2025-10-03 06:13:53'),
(4, 'Eco Bus', '021-666666', 'Jl. Hijau No. 2, Bandung', 'Bus ramah lingkungan', 'aktif', '2025-10-03 06:13:53', '2025-10-03 06:13:53'),
(22, 'Golden Star Travel', '081311111111', 'Jl. Sudirman No. 11, Jakarta', 'Bus eksekutif dengan fasilitas premium', 'aktif', '2025-10-03 07:00:34', '2025-10-03 07:00:34'),
(24, 'Mega Trans', '021-777777', 'Jl. Diponegoro No. 15, Surabaya', 'Penyedia bus pariwisata besar', 'aktif', '2025-10-03 07:00:34', '2025-10-03 07:00:34'),
(25, 'Lintas Jawa', '081333333333', 'Jl. Malioboro No. 21, Yogyakarta', 'Spesialis rute antar kota di Pulau Jawa', 'aktif', '2025-10-03 07:00:34', '2025-10-03 07:00:34'),
(29, 'GoBus Indonesia', '082345678901', 'Jl. Soekarno Hatta No.22, Bandung', 'Operator bus antar kota dengan berbagai kelas layanan.', 'aktif', '2025-10-17 07:20:13', '2025-10-17 07:20:13'),
(30, 'SkyLine Travel', '083456789012', 'Jl. Malioboro No.5, Yogyakarta', 'Melayani perjalanan cepat dan aman antar provinsi.', 'aktif', '2025-10-17 07:20:13', '2025-10-17 07:20:13'),
(33, 'BaliGo', '086789012345', 'Jl. Raya Kuta No.7, Bali', 'Transportasi pariwisata rute Bali dan sekitarnya.', 'aktif', '2025-10-17 07:20:13', '2025-10-17 07:20:13'),
(37, 'JavaLine', '081234567891', 'Jl. Diponegoro No.88, Malang', 'Operator rute Jawa Timur dan sekitarnya.', 'aktif', '2025-10-17 07:20:13', '2025-10-17 07:20:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `refund`
--

CREATE TABLE `refund` (
  `refund_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `alasan` text COLLATE utf8mb4_general_ci,
  `rekening_refund` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah_refund` decimal(12,2) DEFAULT NULL,
  `status_refund` enum('diproses','disetujui','ditolak','selesai') COLLATE utf8mb4_general_ci DEFAULT 'diproses',
  `tanggal_request` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_selesai` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data untuk tabel `refund`
--

INSERT INTO `refund` (`refund_id`, `booking_id`, `payment_id`, `alasan`, `rekening_refund`, `jumlah_refund`, `status_refund`, `tanggal_request`, `tanggal_selesai`) VALUES
(21, 90, 60, 'Kesalahan', '=', 1100000.00, 'selesai', '2025-10-31 00:52:25', '2025-10-31 02:21:52'),
(22, 91, 61, 'kesalahan', '=', 1200000.00, 'selesai', '2025-10-31 00:52:43', '2025-10-31 01:17:37'),
(23, 98, 68, 'ASDFG', '1234R5', 200000.00, 'ditolak', '2025-11-05 07:23:31', '2025-11-05 07:30:18'),
(24, 100, 69, 'sdfg', 'asdf', 450000.00, 'selesai', '2025-11-05 07:34:07', '2025-11-05 07:34:33');

--
-- Trigger `refund`
--
DELIMITER $$
CREATE TRIGGER `trg_refund_insert` AFTER INSERT ON `refund` FOR EACH ROW BEGIN
    INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
    VALUES ('refund','INSERT',NEW.booking_id,
            CONCAT('Refund baru: alasan=',NEW.alasan,
                   ', jumlah=',NEW.jumlah_refund,
                   ', status=',NEW.status_refund));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_refund_update` AFTER UPDATE ON `refund` FOR EACH ROW BEGIN
    IF (OLD.status_refund <> NEW.status_refund) THEN
        INSERT INTO transaksi_log (tabel_asal, aksi, booking_id, detail)
        VALUES ('refund','UPDATE',NEW.booking_id,
                CONCAT('Refund update: status dari ',OLD.status_refund,' ke ',NEW.status_refund,
                       ', jumlah=',NEW.jumlah_refund));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_log`
--

CREATE TABLE `transaksi_log` (
  `log_id` int NOT NULL,
  `tabel_asal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aksi` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `booking_id` int DEFAULT NULL,
  `detail` text COLLATE utf8mb4_general_ci,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_log`
--

INSERT INTO `transaksi_log` (`log_id`, `tabel_asal`, `aksi`, `booking_id`, `detail`, `timestamp`) VALUES
(222, 'booking', 'INSERT', 88, 'Booking baru: kode=TRAVEL-961319, status=pending, total=145000.00', '2025-10-31 00:43:28'),
(223, 'booking', 'INSERT', 89, 'Booking baru: kode=TRAVEL-690726, status=pending, total=245000.00', '2025-10-31 00:43:28'),
(224, 'pembayaran', 'INSERT', 88, 'Pembayaran baru: jumlah=145000.00, metode=transfer_bank, status=pending, bukti=bukti_88_89_1761871454.png', '2025-10-31 00:44:14'),
(225, 'pembayaran', 'INSERT', 89, 'Pembayaran baru: jumlah=245000.00, metode=transfer_bank, status=pending, bukti=bukti_88_89_1761871454.png', '2025-10-31 00:44:14'),
(226, 'booking', 'INSERT', 90, 'Booking baru: kode=TRAVEL-898791, status=pending, total=1100000.00', '2025-10-31 00:50:38'),
(227, 'booking', 'INSERT', 91, 'Booking baru: kode=TRAVEL-879349, status=pending, total=1200000.00', '2025-10-31 00:50:38'),
(228, 'pembayaran', 'INSERT', 90, 'Pembayaran baru: jumlah=1100000.00, metode=transfer_bank, status=pending, bukti=bukti_90_91_1761871845.pdf', '2025-10-31 00:50:45'),
(229, 'pembayaran', 'INSERT', 91, 'Pembayaran baru: jumlah=1200000.00, metode=transfer_bank, status=pending, bukti=bukti_90_91_1761871845.pdf', '2025-10-31 00:50:45'),
(230, 'pembayaran', 'UPDATE', 90, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_90_91_1761871845.pdf', '2025-10-31 00:51:54'),
(231, 'booking', 'UPDATE', 90, 'Booking update: kode=TRAVEL-898791, status dari pending ke confirmed', '2025-10-31 00:51:54'),
(232, 'pembayaran', 'UPDATE', 91, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_90_91_1761871845.pdf', '2025-10-31 00:51:57'),
(233, 'booking', 'UPDATE', 91, 'Booking update: kode=TRAVEL-879349, status dari pending ke confirmed', '2025-10-31 00:51:57'),
(234, 'pembayaran', 'UPDATE', 88, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_88_89_1761871454.png', '2025-10-31 00:52:00'),
(235, 'booking', 'UPDATE', 88, 'Booking update: kode=TRAVEL-961319, status dari pending ke confirmed', '2025-10-31 00:52:00'),
(236, 'pembayaran', 'UPDATE', 89, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_88_89_1761871454.png', '2025-10-31 00:52:03'),
(237, 'booking', 'UPDATE', 89, 'Booking update: kode=TRAVEL-690726, status dari pending ke confirmed', '2025-10-31 00:52:03'),
(238, 'refund', 'INSERT', 90, 'Refund baru: alasan=Kesalahan, jumlah=1100000.00, status=diproses', '2025-10-31 00:52:25'),
(239, 'refund', 'INSERT', 91, 'Refund baru: alasan=kesalahan, jumlah=1200000.00, status=diproses', '2025-10-31 00:52:43'),
(240, 'booking', 'INSERT', 92, 'Booking baru: kode=TRAVEL-793531, status=pending, total=450000.00', '2025-10-31 01:04:00'),
(241, 'booking', 'INSERT', 93, 'Booking baru: kode=TRAVEL-883281, status=pending, total=450000.00', '2025-10-31 01:04:00'),
(242, 'pembayaran', 'INSERT', 92, 'Pembayaran baru: jumlah=450000.00, metode=transfer_bank, status=pending, bukti=bukti_92_93_1761872648.pdf', '2025-10-31 01:04:08'),
(243, 'pembayaran', 'INSERT', 93, 'Pembayaran baru: jumlah=450000.00, metode=transfer_bank, status=pending, bukti=bukti_92_93_1761872648.pdf', '2025-10-31 01:04:08'),
(244, 'booking', 'INSERT', 94, 'Booking baru: kode=TRAVEL-640028, status=pending, total=250000.00', '2025-10-31 01:07:29'),
(245, 'booking', 'INSERT', 95, 'Booking baru: kode=TRAVEL-934928, status=pending, total=245000.00', '2025-10-31 01:07:29'),
(246, 'booking', 'INSERT', 96, 'Booking baru: kode=TRAVEL-393212, status=pending, total=250000.00', '2025-10-31 01:07:29'),
(247, 'booking', 'INSERT', 97, 'Booking baru: kode=TRAVEL-987872, status=pending, total=245000.00', '2025-10-31 01:07:29'),
(248, 'pembayaran', 'INSERT', 94, 'Pembayaran baru: jumlah=250000.00, metode=transfer_bank, status=pending, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:09:07'),
(249, 'pembayaran', 'INSERT', 95, 'Pembayaran baru: jumlah=245000.00, metode=transfer_bank, status=pending, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:09:07'),
(250, 'pembayaran', 'INSERT', 96, 'Pembayaran baru: jumlah=250000.00, metode=transfer_bank, status=pending, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:09:07'),
(251, 'pembayaran', 'INSERT', 97, 'Pembayaran baru: jumlah=245000.00, metode=transfer_bank, status=pending, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:09:07'),
(252, 'pembayaran', 'UPDATE', 94, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:15:17'),
(253, 'booking', 'UPDATE', 94, 'Booking update: kode=TRAVEL-640028, status dari pending ke confirmed', '2025-10-31 01:15:17'),
(254, 'pembayaran', 'UPDATE', 96, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-10-31 01:15:20'),
(255, 'booking', 'UPDATE', 96, 'Booking update: kode=TRAVEL-393212, status dari pending ke confirmed', '2025-10-31 01:15:20'),
(256, 'refund', 'UPDATE', 91, 'Refund update: status dari diproses ke disetujui, jumlah=1200000.00', '2025-10-31 01:17:29'),
(257, 'refund', 'UPDATE', 91, 'Refund update: status dari disetujui ke selesai, jumlah=1200000.00', '2025-10-31 01:17:37'),
(258, 'refund', 'UPDATE', 90, 'Refund update: status dari diproses ke disetujui, jumlah=1100000.00', '2025-10-31 01:23:16'),
(259, 'booking', 'UPDATE', 90, 'Booking update: kode=TRAVEL-898791, status dari confirmed ke cancelled', '2025-10-31 01:23:16'),
(260, 'refund', 'UPDATE', 90, 'Refund update: status dari disetujui ke selesai, jumlah=1100000.00', '2025-10-31 02:21:52'),
(261, 'booking', 'INSERT', 98, 'Booking baru: kode=TRAVEL-196522, status=pending, total=200000.00', '2025-11-05 06:54:41'),
(262, 'pembayaran', 'INSERT', 98, 'Pembayaran baru: jumlah=200000.00, metode=transfer_bank, status=pending, bukti=bukti_98_1762325688.png', '2025-11-05 06:54:48'),
(263, 'pembayaran', 'UPDATE', 98, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_98_1762325688.png', '2025-11-05 06:55:02'),
(264, 'booking', 'UPDATE', 98, 'Booking update: kode=TRAVEL-196522, status dari pending ke confirmed', '2025-11-05 06:55:02'),
(265, 'booking', 'INSERT', 99, 'Booking baru: kode=TRAVEL-676719, status=pending, total=245000.00', '2025-11-05 07:14:32'),
(266, 'booking', 'UPDATE', 99, 'Booking update: kode=TRAVEL-676719, status dari pending ke confirmed', '2025-11-05 07:22:18'),
(267, 'refund', 'INSERT', 98, 'Refund baru: alasan=ASDFG, jumlah=200000.00, status=diproses', '2025-11-05 07:23:31'),
(268, 'refund', 'UPDATE', 98, 'Refund update: status dari diproses ke ditolak, jumlah=200000.00', '2025-11-05 07:30:18'),
(269, 'booking', 'INSERT', 100, 'Booking baru: kode=TRAVEL-867759, status=pending, total=450000.00', '2025-11-05 07:33:12'),
(270, 'pembayaran', 'INSERT', 100, 'Pembayaran baru: jumlah=450000.00, metode=transfer_bank, status=pending, bukti=bukti_100_1762327999.png', '2025-11-05 07:33:19'),
(271, 'pembayaran', 'UPDATE', 100, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_100_1762327999.png', '2025-11-05 07:33:57'),
(272, 'booking', 'UPDATE', 100, 'Booking update: kode=TRAVEL-867759, status dari pending ke confirmed', '2025-11-05 07:33:57'),
(273, 'refund', 'INSERT', 100, 'Refund baru: alasan=sdfg, jumlah=450000.00, status=diproses', '2025-11-05 07:34:07'),
(274, 'refund', 'UPDATE', 100, 'Refund update: status dari diproses ke disetujui, jumlah=450000.00', '2025-11-05 07:34:31'),
(275, 'booking', 'UPDATE', 100, 'Booking update: kode=TRAVEL-867759, status dari confirmed ke cancelled', '2025-11-05 07:34:31'),
(276, 'refund', 'UPDATE', 100, 'Refund update: status dari disetujui ke selesai, jumlah=450000.00', '2025-11-05 07:34:33'),
(277, 'pembayaran', 'UPDATE', 95, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-11-05 07:35:26'),
(278, 'booking', 'UPDATE', 95, 'Booking update: kode=TRAVEL-934928, status dari pending ke confirmed', '2025-11-05 07:35:26'),
(279, 'pembayaran', 'UPDATE', 97, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_94_95_96_97_1761872947.jpg', '2025-11-05 07:35:34'),
(280, 'booking', 'UPDATE', 97, 'Booking update: kode=TRAVEL-987872, status dari pending ke confirmed', '2025-11-05 07:35:34'),
(281, 'booking', 'INSERT', 101, 'Booking baru: kode=TRAVEL-835439, status=pending, total=600000.00', '2025-11-05 07:43:09'),
(282, 'booking', 'INSERT', 102, 'Booking baru: kode=TRAVEL-985512, status=pending, total=1000000.00', '2025-11-05 07:43:09'),
(283, 'pembayaran', 'INSERT', 101, 'Pembayaran baru: jumlah=600000.00, metode=transfer_bank, status=pending, bukti=bukti_101_102_1762328611.jpg', '2025-11-05 07:43:31'),
(284, 'pembayaran', 'INSERT', 102, 'Pembayaran baru: jumlah=1000000.00, metode=transfer_bank, status=pending, bukti=bukti_101_102_1762328611.jpg', '2025-11-05 07:43:31'),
(285, 'pembayaran', 'UPDATE', 101, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_101_102_1762328611.jpg', '2025-11-05 07:43:55'),
(286, 'booking', 'UPDATE', 101, 'Booking update: kode=TRAVEL-835439, status dari pending ke confirmed', '2025-11-05 07:43:55'),
(287, 'pembayaran', 'UPDATE', 102, 'Pembayaran update: status dari pending ke sukses, bukti=bukti_101_102_1762328611.jpg', '2025-11-05 07:43:57'),
(288, 'booking', 'UPDATE', 102, 'Booking update: kode=TRAVEL-985512, status dari pending ke confirmed', '2025-11-05 07:43:57'),
(289, 'booking', 'INSERT', 103, 'Booking baru: kode=TRAVEL-131562, status=pending, total=245000.00', '2025-11-05 07:45:20'),
(290, 'booking', 'INSERT', 104, 'Booking baru: kode=TRAVEL-261222, status=pending, total=245000.00', '2025-11-05 07:49:44'),
(291, 'pembayaran', 'INSERT', 104, 'Pembayaran baru: jumlah=245000.00, metode=transfer_bank, status=pending, bukti=bukti_104_1762328990.pdf', '2025-11-05 07:49:50'),
(292, 'booking', 'INSERT', 105, 'Booking baru: kode=TRAVEL-516813, status=pending, total=450000.00', '2025-11-05 07:50:19'),
(293, 'booking', 'UPDATE', 105, 'Booking update: kode=TRAVEL-516813, status dari pending ke cancelled', '2025-11-05 07:50:29'),
(294, 'booking', 'INSERT', 106, 'Booking baru: kode=TRAVEL-111738, status=pending, total=200000.00', '2025-11-05 07:51:08'),
(295, 'booking', 'INSERT', 107, 'Booking baru: kode=TRAVEL-207569, status=pending, total=200000.00', '2025-11-05 07:54:32'),
(296, 'booking', 'UPDATE', 107, 'Booking update: kode=TRAVEL-207569, status dari pending ke cancelled', '2025-11-05 07:54:36'),
(297, 'booking', 'INSERT', 108, 'Booking baru: kode=TRAVEL-776179, status=pending, total=245000.00', '2025-11-05 08:03:54'),
(298, 'booking', 'INSERT', 109, 'Booking baru: kode=TRAVEL-610735, status=pending, total=245000.00', '2025-11-05 08:13:33'),
(299, 'booking', 'INSERT', 110, 'Booking baru: kode=TRAVEL-315563, status=pending, total=245000.00', '2025-11-05 08:17:22'),
(300, 'booking', 'UPDATE', 110, 'Booking update: kode=TRAVEL-315563, status dari pending ke cancelled', '2025-11-05 08:18:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `username`, `nama_lengkap`, `email`, `password`, `role`, `tanggal_daftar`) VALUES
(18, 'admin', 'Administrator', 'admin@globetix.com', 'admin123', 'admin', '2025-10-03 05:52:16'),
(39, 'syafanbl', 'Syafa Nabila', 'syafanblca29@gmail.com', '$2y$10$nRyYood1S8QSKarf3vSIKOXm3ZqkFzxdqpcruwEf/bFNDKeIjljV6', 'user', '2025-10-24 06:42:01'),
(40, 'nabila', 'syafa nabila', 'ssyfnblc@gmail.com', '$2y$10$dSsNNfKU0rCTLrMfRNpdxu6nwGWxp6NQdEhygvOjiCg0jpZQ5XVcG', 'user', '2025-10-30 07:08:29'),
(41, 'JIHAN', 'Jihan Khasna', 'jihan@gmail.com', '$2y$10$UNJ4r4HXshiV5FfWK7rX9egiBfouBF1ixLXNzRMEb5Bf24RV/RYYy', 'user', '2025-10-31 00:38:33'),
(42, 'cecegres', 'Graselin Simajuntak', 'greselin@gmail.com', '$2y$10$ILd5dEZjueOZxWH/Xcf2ou/wfCjKPNnitMYQ8A2hJyzJrPyHtHNp.', 'user', '2025-10-31 00:39:29'),
(43, 'refiyuni', 'Refi Yuni Mariska', 'refi@gmail.com', '$2y$10$.YcO6mT7XYz6Zki40EdRTeyPe7H/s0FRePC8PUcDzrepeBpZ.mKBa', 'user', '2025-10-31 00:40:13'),
(44, 'inivan', 'Ivan Noor', 'ivan@gmail.com', '$2y$10$74TLizNQpq//XwRcnzoxZeWUEMejNql3/B2AMHR3yl/R6yqgJs246', 'user', '2025-10-31 00:41:54');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_jadwal_populer`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_jadwal_populer` (
`asal` varchar(100)
,`jadwal_id` int
,`tanggal_keberangkatan` date
,`total_booking` bigint
,`tujuan` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_pendapatan_mingguan`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_pendapatan_mingguan` (
`jumlah_transaksi` bigint
,`minggu_ke` int
,`total_pendapatan` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_pengguna_baru`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_pengguna_baru` (
`minggu_ke` int
,`pengguna_baru` bigint
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_refund_summary`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_refund_summary` (
`status_refund` enum('diproses','disetujui','ditolak','selesai')
,`total_refund` bigint
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_status_booking`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_status_booking` (
`status_booking` enum('pending','confirmed','cancelled')
,`total` bigint
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_tren_booking`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_tren_booking` (
`minggu_ke` int
,`total_booking` bigint
,`total_cancelled` decimal(23,0)
,`total_confirmed` decimal(23,0)
,`total_pending` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_jadwal_populer`
--
DROP TABLE IF EXISTS `v_jadwal_populer`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_jadwal_populer`  AS SELECT `j`.`jadwal_id` AS `jadwal_id`, `j`.`asal` AS `asal`, `j`.`tujuan` AS `tujuan`, `j`.`tanggal_keberangkatan` AS `tanggal_keberangkatan`, count(`b`.`booking_id`) AS `total_booking` FROM (`booking` `b` join `jadwal` `j` on((`b`.`jadwal_id` = `j`.`jadwal_id`))) WHERE (`b`.`status_booking` = 'confirmed') GROUP BY `j`.`jadwal_id`, `j`.`asal`, `j`.`tujuan`, `j`.`tanggal_keberangkatan` ORDER BY `total_booking` DESC LIMIT 0, 10 ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_pendapatan_mingguan`
--
DROP TABLE IF EXISTS `v_pendapatan_mingguan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pendapatan_mingguan`  AS SELECT yearweek(`p`.`tanggal_bayar`,1) AS `minggu_ke`, sum(`p`.`jumlah_bayar`) AS `total_pendapatan`, count(`p`.`payment_id`) AS `jumlah_transaksi` FROM `pembayaran` AS `p` WHERE (`p`.`status_pembayaran` = 'sukses') GROUP BY yearweek(`p`.`tanggal_bayar`,1) ORDER BY `minggu_ke` DESC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_pengguna_baru`
--
DROP TABLE IF EXISTS `v_pengguna_baru`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pengguna_baru`  AS SELECT yearweek(`t`.`first_booking`,1) AS `minggu_ke`, count(0) AS `pengguna_baru` FROM (select `booking`.`user_id` AS `user_id`,min(`booking`.`tanggal_booking`) AS `first_booking` from `booking` group by `booking`.`user_id`) AS `t` GROUP BY yearweek(`t`.`first_booking`,1) ORDER BY `minggu_ke` DESC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_refund_summary`
--
DROP TABLE IF EXISTS `v_refund_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_refund_summary`  AS SELECT `refund`.`status_refund` AS `status_refund`, count(0) AS `total_refund` FROM `refund` GROUP BY `refund`.`status_refund` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_status_booking`
--
DROP TABLE IF EXISTS `v_status_booking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_status_booking`  AS SELECT `booking`.`status_booking` AS `status_booking`, count(0) AS `total` FROM `booking` GROUP BY `booking`.`status_booking` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_tren_booking`
--
DROP TABLE IF EXISTS `v_tren_booking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tren_booking`  AS SELECT yearweek(`booking`.`tanggal_booking`,1) AS `minggu_ke`, count(0) AS `total_booking`, sum((case when (`booking`.`status_booking` = 'confirmed') then 1 else 0 end)) AS `total_confirmed`, sum((case when (`booking`.`status_booking` = 'pending') then 1 else 0 end)) AS `total_pending`, sum((case when (`booking`.`status_booking` = 'cancelled') then 1 else 0 end)) AS `total_cancelled` FROM `booking` GROUP BY yearweek(`booking`.`tanggal_booking`,1) ORDER BY `minggu_ke` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indeks untuk tabel `booking_seat`
--
ALTER TABLE `booking_seat`
  ADD PRIMARY KEY (`booking_seat_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`,`seat_id`),
  ADD KEY `seat_id` (`seat_id`);

--
-- Indeks untuk tabel `eticket`
--
ALTER TABLE `eticket`
  ADD PRIMARY KEY (`eticket_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_jadwal_asal_tujuan_tanggal` (`asal`,`tujuan`,`tanggal_keberangkatan`);

--
-- Indeks untuk tabel `kursi`
--
ALTER TABLE `kursi`
  ADD PRIMARY KEY (`seat_id`),
  ADD UNIQUE KEY `jadwal_id` (`jadwal_id`,`nomor_kursi`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`token`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_password_resets_email` (`email`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indeks untuk tabel `penumpang`
--
ALTER TABLE `penumpang`
  ADD PRIMARY KEY (`penumpang_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indeks untuk tabel `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`provider_id`);

--
-- Indeks untuk tabel `refund`
--
ALTER TABLE `refund`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indeks untuk tabel `transaksi_log`
--
ALTER TABLE `transaksi_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `booking_seat`
--
ALTER TABLE `booking_seat`
  MODIFY `booking_seat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT untuk tabel `eticket`
--
ALTER TABLE `eticket`
  MODIFY `eticket_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `jadwal_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kursi`
--
ALTER TABLE `kursi`
  MODIFY `seat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=656;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penumpang`
--
ALTER TABLE `penumpang`
  MODIFY `penumpang_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT untuk tabel `providers`
--
ALTER TABLE `providers`
  MODIFY `provider_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `refund`
--
ALTER TABLE `refund`
  MODIFY `refund_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `transaksi_log`
--
ALTER TABLE `transaksi_log`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`jadwal_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `booking_seat`
--
ALTER TABLE `booking_seat`
  ADD CONSTRAINT `booking_seat_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_seat_ibfk_2` FOREIGN KEY (`seat_id`) REFERENCES `kursi` (`seat_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eticket`
--
ALTER TABLE `eticket`
  ADD CONSTRAINT `eticket_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kursi`
--
ALTER TABLE `kursi`
  ADD CONSTRAINT `kursi_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`jadwal_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penumpang`
--
ALTER TABLE `penumpang`
  ADD CONSTRAINT `penumpang_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `refund`
--
ALTER TABLE `refund`
  ADD CONSTRAINT `refund_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `refund_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `pembayaran` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
