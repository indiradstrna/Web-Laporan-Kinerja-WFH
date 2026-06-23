-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Apr 2026 pada 10.47
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `biokpi_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assessment_date` date NOT NULL,
  `period` enum('1','2') NOT NULL DEFAULT '1',
  `extra_score` int(11) DEFAULT 0,
  `total_score` decimal(5,2) DEFAULT 0.00,
  `data_json` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_type` enum('WFO','WFH') DEFAULT 'WFO',
  `date` date NOT NULL,
  `clock_in_time` datetime DEFAULT NULL,
  `clock_out_time` datetime DEFAULT NULL,
  `clock_in_lat` decimal(11,8) DEFAULT NULL,
  `clock_in_lng` decimal(11,8) DEFAULT NULL,
  `clock_out_lat` decimal(11,8) DEFAULT NULL,
  `clock_out_lng` decimal(11,8) DEFAULT NULL,
  `status` enum('ontime','late') NOT NULL DEFAULT 'ontime',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nik` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `education` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tenure` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificates` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'outsourcing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `nik`, `department`, `role_title`, `position`, `education`, `tenure`, `certificates`, `created_at`, `type`) VALUES
(63, 'Indira Destriana Anjani', '123456', 'FMD', 'Staff', 'Programmer', NULL, NULL, NULL, '2026-01-26 02:08:34', 'outsourcing'),
(64, 'Sheni Olvianda', '111111', 'FMD', 'Internship', 'Internship', NULL, NULL, NULL, '2026-02-03 05:34:19', 'outsourcing'),
(66, 'Slamet Widodo Sugiarto', '197707072025211067', 'FMD', 'Manager', 'Manager', NULL, NULL, NULL, '2026-02-19 02:20:21', 'outsourcing'),
(111, 'AGUS', '16267400035', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(112, 'AHMAD SUDRAJAT', '16269400052', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(114, 'ANDI SUHANDI', '16268800019', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(115, 'ANGGI PURNAMA', '16260400054', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(116, 'AULIA RIZKY ANANDA', '16260400053', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(117, 'DADANG SETIAWAN', '16267900036', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(118, 'DEDE MAULANA', '16268000018', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(119, 'DIDIT TRISNADI', '16267800056', 'FMD', 'Staff', 'DRIVER', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(120, 'ENDANG SETIAWAN', '16267900038', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(121, 'ENDIH', '16266700037', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(122, 'ERWAN PRAYOGI', '16269200058', 'FMD', 'Staff', 'DRIVER', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(123, 'HASAN HUSEN', '16267600032', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(124, 'HERDI FERDIANSYAH MAULANA', '16268900039', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(125, 'HERMAN', '16268600040', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(126, 'HERU', '16268000041', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(127, 'ICHWAN PIDIN', '16267600022', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(128, 'IDA HERMAWAN', '16268000042', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(129, 'IDIM DIMYATI', '16267000050', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(130, 'INDRA ARDIANSYAH', '16268200024', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(131, 'ISNANDAR', '16268700030', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(132, 'KHAMELANDI ASTRIAN', '16268600017', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(133, 'KOMARUDIN', '16268500020', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(134, 'KURNIAWAN', '16268200025', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(135, 'KUSNADI', '16267600023', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(136, 'LINGGAR', '16269000031', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(137, 'M. ABUDIN', '16267800043', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(138, 'MAULANA HASANUDIN', '16268300026', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(139, 'MOH.WAHYU NOORRAMDHANY Y.', '16268300055', 'FMD', 'Staff', 'SUPERVISOR PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(140, 'MUHAMAD ZAENUDDIN', '16267100051', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(141, 'MUHAMMAD NAZRIL ILHAM', '16260500034', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(142, 'MULYADI', '16267800044', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(143, 'MULYANA', '16266800045', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(144, 'RIDWAN', '16268500046', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(145, 'ROHMAN', '16267200047', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(146, 'RUDI HARTONO', '16268300028', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(147, 'SARIPUDIN', '16268000027', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(148, 'SUGIH MAULANA', '16260400016', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(149, 'TAOFIK ROKAYAT', '16267200057', 'FMD', 'Staff', 'DRIVER', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(150, 'TASORI HERIYANTO', '16267500021', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(151, 'WAHYUDI', '16267700048', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(152, 'WALDI HIDAYAT', '16269000049', 'FMD', 'Staff', 'PRAMUBAKTI', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(153, 'YAYA TARYANA', '16267800033', 'FMD', 'Staff', 'CHIEFSECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(154, 'ZULPA HILMAWAN', '16269000029', 'FMD', 'Staff', 'SECURITY', NULL, NULL, NULL, '2026-02-20 01:44:42', 'outsourcing'),
(155, 'Administrator', 'admin', 'IT', 'Super Admin', 'System Administrator', NULL, NULL, NULL, '2026-02-20 02:11:16', 'outsourcing'),
(156, 'Agus Sujadi', '197212162014091003', 'FMD', 'Staff', 'Staff', NULL, NULL, NULL, '2026-02-20 02:34:39', 'outsourcing'),
(157, 'Alfi Dwi Nugroho, A.Md', '198605082025211053', 'FMD', 'Staff', 'Staff', NULL, NULL, NULL, '2026-02-20 02:34:39', 'outsourcing'),
(158, 'Bahrudin', '197009192025211011', 'FMD', 'Staff', 'Staff', NULL, NULL, NULL, '2026-02-20 02:34:39', 'outsourcing'),
(159, 'Indra Septian, A.Md', '198902222025211044', 'FMD', 'Staff', 'Staff', NULL, NULL, NULL, '2026-02-20 02:34:39', 'outsourcing'),
(160, 'Lastiah, SE', '199008092025212052', 'FMD', 'Staff', 'Staff', NULL, NULL, NULL, '2026-02-20 02:34:39', 'outsourcing'),
(163, 'Prof. Dr. Edi Santosa, S.P., M.Si', '197005201996011000', 'BoD', 'Direktur', 'Direktur', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(164, 'Dr. Elis Rosdiawati, M.Pd', '196905151992032009', 'BOD', 'Deputi Direktur bidang Administrasi', 'Deputi Direktur bidang Administrasi', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(165, 'Dr.rer.nat. Doni Yusri, SP., MM', '', 'BoD', 'Deputi Direktur bidang Program', 'Deputi Direktur bidang Program', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(166, 'Peri Siantuni, SE', '198212062008101002', 'FAD', 'BPP', 'BPP', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(167, 'Mulyadiana Prayoga', '198108072009101001', 'SITD', 'Staf SITD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(168, 'Supriyatno, A.Md', '196902262014091001', 'FAD', 'Staf FAD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(169, 'Sunardi Ikay', '197007202007011002', 'SITD', 'Staf SITD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(170, 'Herni Widhiastuti, S.Si', '197708072014092001', 'Pengadaan', 'Pejabat Pengadaan', 'Pejabat Pengadaan', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(171, 'Dewi Rahmawati, M.Si', '197810122014092003', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(172, 'Nopi Ramli', '198011052014092003', 'HRAD', 'Staf HRAD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(173, 'Haritz Cahya Nugraha, M.T', '198912142015041002', 'KMD', 'Manajer KMD', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(174, 'Aan Darwati, S.Ak', '198407032025212025', 'FAD', 'Staf FAD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(175, 'Aris Purnajaya', '198212142025211037', 'SITD', 'Staf SITD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(176, 'Asep Saepudin', '198212182025211036', 'KMD', 'Staf KMD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(177, 'Asep Syaefudin, SE', '197212232025211010', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(178, 'Dani Yudi Trisna, A.Md', '198110072025211038', 'KMD', 'Staf KMD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(179, 'Dewi Suryani Oktavia Basuki, SP., MM', '198210282025212041', 'HCID', 'Manajer HCID', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(180, 'Didi Junaedi, A.Md', '198305182025211032', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(181, 'Fitri Junaedy, SEI', '198506302025211042', 'HRAD', 'Staf HRAD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(182, 'Hery Yanto', '198312212025211029', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(183, 'Iman', '197007272025211013', 'SITD', 'Staf SITD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(184, 'Irawan', '198206272025211034', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(185, 'Lidia Defita, S.Kom', '197712122025212024', 'HRAD', 'Staf HRAD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(186, 'Lillys Betty Yuliawati, S.Si', '198007062025212028', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(187, 'Risa Rosita, M.Si', '198507172025212047', 'SITD', 'Manajer  SITD', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(188, 'Risya Ayu Astari, A.Md', '198703282025212040', 'SITD', 'Staf SITD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(189, 'Rizkia Tirtani', '198912112025212050', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(190, 'Rosadi, S.Pd.I', '197910152025211032', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(191, 'Saiful Bachri, M.Si', '198811072025211049', 'HCID', 'Supervisor HCID', 'Supervisor HCID', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(193, 'Tenni Wahyuni, S.I.Kom', '198003182025212017', 'HRAD', 'Manajer HRAD', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(194, 'Trijanti A. Widinni Asnan, M.Si.', '199201142025212043', 'FAD', 'Manajer FAD', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(195, 'Woro Kanti Dharmastuti, M.Si', '198109012025212021', 'KMD', 'Staf KMD', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(196, 'Yana', '198605132025211031', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(197, 'Zaenal Abidin', '197201042025211008', 'HCID', 'Staf HCID', 'Staff', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(198, 'Zulkarnaen Noor Syarif, M.Kom', '198212042025211036', 'SITD', 'Supervisor SITD', 'Supervisor SITD', NULL, NULL, NULL, '2026-04-09 06:49:25', 'outsourcing'),
(199, 'Slamet Widodo Sugiarto, M.Sc', '197707072025211067', 'FMD', 'Manajer FMD', 'Manajer', NULL, NULL, NULL, '2026-04-09 06:59:37', 'outsourcing'),
(200, 'Alya Shafira, S.T', '16269900059', 'SITD', 'Staf SITD', 'staff', NULL, NULL, NULL, '2026-04-10 00:18:34', 'outsourcing'),
(201, 'Angga Octora', '16258200044', 'KMD', 'Staf KMD', 'staff', NULL, NULL, NULL, '2026-04-10 00:18:34', 'outsourcing'),
(202, 'Novianti Mutiara Ramadhan', '16250300051', 'FAD', 'Staf FAD', 'staff', NULL, NULL, NULL, '2026-04-10 00:18:34', 'outsourcing'),
(203, 'Putri Dina Rahayu, SE', '16259000052', 'HRAD', 'Staf HRAD', 'staff', NULL, NULL, NULL, '2026-04-10 00:18:34', 'outsourcing'),
(204, 'Rifa Nur Ishlah, S.P', '16250100054', 'HCID', 'Staf HCID', 'staff', NULL, NULL, NULL, '2026-04-10 00:18:34', 'outsourcing'),
(205, 'Alvi Dwi Aryani', '68012026', 'SITD', 'tenaga ahli SITD', 'staff', NULL, NULL, NULL, '2026-04-10 00:43:32', 'outsourcing'),
(206, 'M. Raushan Fiqr', '68022026', 'HCID', 'tenaga ahli HCID', 'staff', NULL, NULL, NULL, '2026-04-10 00:43:32', 'outsourcing');

-- --------------------------------------------------------

--
-- Struktur dari tabel `employee_targets`
--

CREATE TABLE `employee_targets` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `target_3_months` text DEFAULT NULL,
  `target_6_months` text DEFAULT NULL,
  `target_1_year` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `evidence`
--

CREATE TABLE `evidence` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gps_logs`
--

CREATE TABLE `gps_logs` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `trigger_type` enum('start','stop','tracking') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nik` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `status` enum('success','failed_password','blocked_device','blocked_duplicate','not_found') NOT NULL,
  `device_id` varchar(250) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_tokens`
--

CREATE TABLE `qr_tokens` (
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('Harian','Mingguan','Bulanan') DEFAULT 'Harian',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('user','admin','super admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `daily_workload` int DEFAULT '8',
  `work_radius_meters` int DEFAULT '100',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mac_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wfh_lat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wfh_lng` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `employee_id`, `password`, `role`, `daily_workload`, `work_radius_meters`, `created_at`, `mac_address`, `wfh_lat`, `wfh_lng`) VALUES
(1, 155, '11223344', 'super admin', 8, 100, '2026-01-26 01:52:31', 'HW-337AA017-Admin,HW-E55D355-Admin,HW-DBCB986-199008092025212052,HW-DBCB986-admin', NULL, NULL),
(90, 63, 'seabiotrop68', 'user', 8, 100, '2026-01-26 02:08:34', NULL, '-6.6363444025761', '106.82645865891'),
(91, 64, 'XG41SFOF', 'user', 8, 100, '2026-02-03 05:34:19', NULL, '-6.6363918062278', '106.82642881154'),
(92, 65, 'seabiotrop68', 'user', 8, 100, '2026-02-19 01:49:16', NULL, NULL, NULL),
(93, 66, 'seabiotrop68', 'super admin', 8, 100, '2026-02-19 02:20:21', 'HW-2FDCAB1B-197707072025211067,HW-67FE04E2-197707072025211067,HW-3C0F0F62-197707072025211067,HW-6269187A-197707072025211067,HW-140E852E-197707072025211067,HW-56BB9362-197707072025211067,HW-3A8A83E2-197707072025211067,HW-DBCB986-197707072025211067,HW-31E38', '-6.4424393', '107.0394653'),
(138, 111, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(139, 112, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(140, 113, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(141, 114, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(142, 115, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(143, 116, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(144, 117, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(145, 118, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(146, 119, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', 'FP-2880B319A690A852845C', NULL, NULL),
(147, 120, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(148, 121, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(149, 122, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(150, 123, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(151, 124, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(152, 125, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(153, 126, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(154, 127, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(155, 128, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(156, 129, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(157, 130, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(158, 131, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(159, 132, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(160, 133, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(161, 134, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(162, 135, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(163, 136, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(164, 137, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(165, 138, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(166, 139, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(167, 140, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(168, 141, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(169, 142, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(170, 143, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(171, 144, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(172, 145, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(173, 146, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(174, 147, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(175, 148, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(176, 149, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(177, 150, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(178, 151, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(179, 152, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(180, 153, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(181, 154, 'seabiotrop68', 'user', 8, 100, '2026-02-20 01:44:42', NULL, NULL, NULL),
(182, 156, 'seabiotrop68', 'user', 8, 100, '2026-02-20 02:34:39', 'HW-683624C3-197212162014091003', NULL, NULL),
(183, 157, 'seabiotrop68', 'admin', 8, 100, '2026-02-20 02:34:39', 'HW-6786041C-198605082025211053,HW-4121DBEE-198605082025211053,HW-40D6AE1D-198605082025211053,HW-40CA6620-198605082025211053', '-6.593719', '106.8168015'),
(184, 158, 'seabiotrop68', 'user', 8, 100, '2026-02-20 02:34:39', NULL, NULL, NULL),
(185, 159, 'seabiotrop68', 'admin', 8, 100, '2026-02-20 02:34:39', 'HW-63ED13F9-198902222025211044,HW-33D2417D-198902222025211044,HW-23953E5A-198902222025211044', NULL, NULL),
(186, 160, 'seabiotrop68', 'user', 8, 100, '2026-02-20 02:34:39', 'HW-6BC846C0-199008092025212052,HW-5992D4B2-199008092025212052', '-6.6463840894488', '106.82237881384'),
(187, 161, 'seabiotrop68', 'user', 8, 100, '2026-04-09 02:37:11', NULL, NULL, NULL),
(188, 162, 'seabiotrop68', 'user', 8, 100, '2026-04-09 02:37:11', NULL, NULL, NULL),
(189, 163, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(190, 164, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(191, 165, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(192, 166, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-6786041C-198212062008101002,HW-40CA6620-198212062008101002', '-6.9612962', '106.9120627'),
(193, 167, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(194, 168, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-420A2400-196902262014091001,HW-6942CBBC-196902262014091001', '-6.7394238', '106.8074175'),
(195, 169, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(196, 170, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-554A9673-197708072014092001,HW-48DFD4BA-197708072014092001', '-6.549139', '106.8036192'),
(197, 171, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(198, 172, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-D35E560-198011052014092003,HW-37279F36-198011052014092003', '-6.5835923', '106.8128511'),
(199, 173, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-6D782D8E-198912142015041002,HW-5A4FF983-198912142015041002,HW-607F492D-198912142015041002', '-6.603294', '106.767143'),
(200, 174, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-66AF8A1B-198407032025212025', '-6.584494', '106.824005'),
(201, 175, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(202, 176, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(203, 177, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(204, 178, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-218E7B3B-198110072025211038', '-6.609569', '106.7830051'),
(205, 179, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(206, 180, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(207, 181, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-3DA54F15-198506302025211042,HW-7CC4F38F-198506302025211042,HW-7CC4F38F-198003182025212017,HW-46053D2F-198506302025211042', '-6.6159403', '106.7551808'),
(208, 182, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(209, 183, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(210, 184, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-7C1AE406-198206272025211034', NULL, NULL),
(211, 185, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-296C937A-197712122025212024,HW-3B60F997-197712122025212024,HW-40EA4C4B-197712122025212024', '-6.6064635', '106.7794029'),
(212, 186, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-6BCE3A4D-198007062025212028', '-6.407018', '106.965316'),
(213, 187, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-3FA50DA8-198507172025212047,HW-5A80F673-198507172025212047', '-6.456185258368', '106.84606451285'),
(214, 188, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-2CF73CF0-198703282025212040,HW-39C04332-198703282025212040', '-6.538510329151', '106.8344792208'),
(215, 189, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-554A9673-198912112025212050', NULL, NULL),
(216, 190, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-F6AE608-197910152025211032,HW-7D95A482-197910152025211032', NULL, NULL),
(217, 191, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(218, 192, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(219, 193, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-17B35BE3-198003182025212017,HW-659EF634-198003182025212017,HW-7CC4F38F-198506302025211042,HW-7CC4F38F-198003182025212017', '-6.5847222', '106.773689'),
(220, 194, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-7A0F7A3-199201142025212043,HW-4C27A417-199201142025212043,HW-738A9840-199201142025212043', '-6.6102441', '106.7576682'),
(221, 195, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-293190C7-198109012025212021', '-6.5878268', '106.8169898'),
(222, 196, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', 'HW-420A2400-198605132025211031', NULL, NULL),
(223, 197, 'seabiotrop68', 'user', 8, 100, '2026-04-09 06:49:25', NULL, NULL, NULL),
(224, 198, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:49:25', 'HW-2669F2F3-198212042025211036,HW-16445480-198212042025211036', '-6.576629', '106.8042141'),
(225, 199, 'seabiotrop68', 'admin', 8, 100, '2026-04-09 06:59:37', NULL, NULL, NULL),
(226, 200, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:22:44', 'HW-72FED5D4-16269900059', '-6.5822307888622', '106.83307267855'),
(227, 201, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:22:44', NULL, NULL, NULL),
(228, 202, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:22:44', 'HW-44668335-16250300051,HW-1F23E269-16250300051', '-6.6650828763618', '106.86561684615'),
(229, 203, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:22:44', 'HW-130C8DD5-16259000052', '-6.6417241878768', '106.82758135098'),
(230, 204, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:22:44', 'HW-479B2C75-16250100054,HW-7D3EEDF8-16250100054', '-6.6289419', '106.7687274'),
(238, 205, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:44:11', 'HW-44668335-68012026', '-6.630247051029', '106.83092744002'),
(239, 206, 'seabiotrop68', 'user', 8, 100, '2026-04-10 00:44:11', 'HW-11D4AFE1-68022026,HW-130C8DD5-68022026', '-6.5551095132078', '106.73525380451');

-- --------------------------------------------------------

--
-- Struktur dari tabel `work_sessions`
--

CREATE TABLE `work_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('active','completed','pending_approval','revision') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `task_name` varchar(150) DEFAULT NULL,
  `manager_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance_daily` (`user_id`,`date`);

--
-- Indeks untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_emp` (`full_name`,`nik`);

--
-- Indeks untuk tabel `employee_targets`
--
ALTER TABLE `employee_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_target` (`employee_id`,`year`);

--
-- Indeks untuk tabel `evidence`
--
ALTER TABLE `evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indeks untuk tabel `gps_logs`
--
ALTER TABLE `gps_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indeks untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD PRIMARY KEY (`token`);

--
-- Indeks untuk tabel `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indeks untuk tabel `work_sessions`
--
ALTER TABLE `work_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT untuk tabel `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT untuk tabel `employee_targets`
--
ALTER TABLE `employee_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT untuk tabel `evidence`
--
ALTER TABLE `evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `gps_logs`
--
ALTER TABLE `gps_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=504;

--
-- AUTO_INCREMENT untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT untuk tabel `work_sessions`
--
ALTER TABLE `work_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `employee_targets`
--
ALTER TABLE `employee_targets`
  ADD CONSTRAINT `employee_targets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `evidence`
--
ALTER TABLE `evidence`
  ADD CONSTRAINT `evidence_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `work_sessions` (`id`);

--
-- Ketidakleluasaan untuk tabel `gps_logs`
--
ALTER TABLE `gps_logs`
  ADD CONSTRAINT `gps_logs_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `work_sessions` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `work_sessions`
--
ALTER TABLE `work_sessions`
  ADD CONSTRAINT `work_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
