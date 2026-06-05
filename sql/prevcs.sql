-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2026 at 05:14 AM
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
-- Database: `prevcs`
--

-- --------------------------------------------------------

--
-- Table structure for table `esp32_devices`
--

CREATE TABLE `esp32_devices` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `token_hash` varchar(64) DEFAULT NULL,
  `token_secret` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'offline',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esp32_devices`
--

INSERT INTO `esp32_devices` (`id`, `industry_id`, `name`, `mac_address`, `sector`, `token_hash`, `token_secret`, `is_active`, `status`, `last_seen_at`, `created_at`) VALUES
(6, 4, 'Motor 1', 'D4:E9:F4:8D:18:7C', 'Planta aceitera', '31e30111dcbeef1fe16694a563ebd47efcb2112539462e51e14a07009c46dd92', NULL, 1, 'online', '2026-06-03 02:57:25', '2026-06-03 00:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `esp32_sensors`
--

CREATE TABLE `esp32_sensors` (
  `id` int(11) NOT NULL,
  `esp32_device_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esp32_sensors`
--

INSERT INTO `esp32_sensors` (`id`, `esp32_device_id`, `sensor_type_id`, `label`, `created_at`) VALUES
(8, 6, 1, NULL, '2026-06-03 00:45:46'),
(9, 6, 4, NULL, '2026-06-03 00:45:53'),
(10, 6, 3, NULL, '2026-06-03 00:46:00');

-- --------------------------------------------------------

--
-- Table structure for table `form_answers`
--

CREATE TABLE `form_answers` (
  `id` int(11) NOT NULL,
  `raw_timestamp` varchar(64) NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `nombre` varchar(200) DEFAULT NULL,
  `rol_empresa` varchar(80) DEFAULT NULL,
  `conoce_similar` varchar(20) DEFAULT NULL,
  `funcion_util` varchar(200) DEFAULT NULL,
  `nivel_mejora` varchar(20) DEFAULT NULL,
  `es_ecologico` varchar(40) DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_answers`
--

INSERT INTO `form_answers` (`id`, `raw_timestamp`, `submitted_at`, `nombre`, `rol_empresa`, `conoce_similar`, `funcion_util`, `nivel_mejora`, `es_ecologico`, `imported_at`) VALUES
(1, '6/5/2026 12:57:59', '2026-05-06 12:57:59', 'Emanuel', 'Administración', 'Sí', 'Gestión Mantenimiento', 'Alto', 'Si', '2026-05-21 23:15:30'),
(2, '6/5/2026 17:18:32', '2026-05-06 17:18:32', 'GENARO', 'Gerente', 'Sí', 'Gráficos', 'Alto', 'Si', '2026-05-21 23:15:30'),
(3, '6/5/2026 17:32:47', '2026-05-06 17:32:47', 'Gaston meotto', 'Operador', 'Sí', 'Gestión Mantenimiento', 'Alto', 'Si', '2026-05-21 23:15:30'),
(4, '6/5/2026 17:32:55', '2026-05-06 17:32:55', 'Esteban angel sebastian', 'Operador', 'Sí', 'Gestión Mantenimiento', 'Alto', 'Si', '2026-05-21 23:15:30'),
(5, '6/5/2026 18:18:14', '2026-05-06 18:18:14', 'jeremias brusasca', 'Operador', 'Sí', 'Gestión Mantenimiento', 'Medio', 'Si', '2026-05-21 23:15:30'),
(6, '7/5/2026 9:24:48', '2026-07-05 09:24:48', 'Emanuel Martin', 'Operador', 'No', 'Sensor que monitoree un motor y presente datos en tiempo real', 'Alto', 'Si', '2026-05-21 23:15:30'),
(7, '11/5/2026 21:01:17', '2026-05-11 21:01:17', 'Mayra', 'Administración', 'No', 'Sensor que monitoree un motor y presente datos en tiempo real', 'Alto', 'Si', '2026-05-21 23:15:30'),
(8, '12/5/2026 16:42:29', '2026-05-12 16:42:29', 'Rosana', 'Administración', 'No', 'Stock', 'Medio', 'Si', '2026-05-21 23:15:30'),
(9, '12/5/2026 16:43:45', '2026-05-12 16:43:45', 'Jorge Daniel', 'Operador', 'Sí', 'Gestión Mantenimiento', 'Alto', 'Me es indiferente', '2026-05-21 23:15:30'),
(10, '12/5/2026 16:45:43', '2026-05-12 16:45:43', 'Licia', 'Administración', 'Sí', 'Gráficos', 'Alto', 'Si', '2026-05-21 23:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `industries`
--

CREATE TABLE `industries` (
  `id` int(11) NOT NULL,
  `industry_uid` varchar(40) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `space_name` varchar(200) NOT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `space_password_hash` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industries`
--

INSERT INTO `industries` (`id`, `industry_uid`, `company_name`, `space_name`, `sector`, `space_password_hash`, `created_by`, `created_at`) VALUES
(4, 'SJS-IND-2FBAF6', 'San Jose SRL', 'Extrusoras', 'Planta aceitera', '$2y$10$Co26Lv8T1CwzWKV3n3SyG.0IzbMan4c2vIaJAhKc5by2Y7Dx1MAtq', 1, '2026-06-02 22:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `industry_metrics`
--

CREATE TABLE `industry_metrics` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `suggested_chart` varchar(20) NOT NULL DEFAULT 'line'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industry_metrics`
--

INSERT INTO `industry_metrics` (`id`, `industry_id`, `sensor_type_id`, `suggested_chart`) VALUES
(8, 4, 6, 'timeline'),
(9, 4, 4, 'gauge'),
(10, 4, 1, 'line'),
(11, 4, 3, 'line');

-- --------------------------------------------------------

--
-- Table structure for table `industry_users`
--

CREATE TABLE `industry_users` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industry_users`
--

INSERT INTO `industry_users` (`id`, `industry_id`, `user_id`, `role`, `joined_at`) VALUES
(5, 4, 1, 'admin_principal', '2026-06-02 22:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2026-04-21-000001', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1779405258, 1),
(2, '2026-05-08-000001', 'App\\Database\\Migrations\\CreatePasswordResetsTable', 'default', 'App', 1779405258, 1),
(3, '2026-05-21-000001', 'App\\Database\\Migrations\\CreateFormAnswersTable', 'default', 'App', 1779405258, 1),
(4, '2026-05-29-000001', 'App\\Database\\Migrations\\CreateIndustriesTable', 'default', 'App', 1780089087, 2),
(5, '2026-05-29-000002', 'App\\Database\\Migrations\\CreateIndustryUsersTable', 'default', 'App', 1780089087, 2),
(6, '2026-05-29-000003', 'App\\Database\\Migrations\\CreateSensorTypesTable', 'default', 'App', 1780089087, 2),
(7, '2026-05-29-000004', 'App\\Database\\Migrations\\CreateEsp32DevicesTable', 'default', 'App', 1780089087, 2),
(8, '2026-05-29-000005', 'App\\Database\\Migrations\\CreateEsp32SensorsTable', 'default', 'App', 1780089087, 2),
(9, '2026-05-29-000006', 'App\\Database\\Migrations\\CreateSensorReadingsTable', 'default', 'App', 1780089087, 2),
(10, '2026-05-29-000007', 'App\\Database\\Migrations\\CreateIndustryMetricsTable', 'default', 'App', 1780089087, 2),
(11, '2026-06-02-000001', 'App\\Database\\Migrations\\AlterEsp32DevicesAddSecurity', 'default', 'App', 1780434868, 3),
(12, '2026-06-02-000002', 'App\\Database\\Migrations\\SeedRpmAnalogSensorType', 'default', 'App', 1780434868, 3),
(13, '2026-06-02-000003', 'App\\Database\\Migrations\\RemoveRpmAnalogSensorType', 'default', 'App', 1780439660, 4);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` bigint(20) NOT NULL,
  `esp32_sensor_id` int(11) NOT NULL,
  `value` decimal(12,3) NOT NULL,
  `measured_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `esp32_sensor_id`, `value`, `measured_at`, `created_at`) VALUES
(32, 10, 0.000, '2026-06-03 01:07:16', '2026-06-03 01:07:16'),
(33, 9, 0.000, '2026-06-03 01:07:16', '2026-06-03 01:07:16'),
(34, 10, 0.000, '2026-06-03 01:08:16', '2026-06-03 01:08:16'),
(35, 9, 0.000, '2026-06-03 01:08:16', '2026-06-03 01:08:16'),
(36, 10, 0.000, '2026-06-03 01:09:16', '2026-06-03 01:09:16'),
(37, 9, 0.000, '2026-06-03 01:09:16', '2026-06-03 01:09:16'),
(38, 10, 17919.000, '2026-06-03 01:41:23', '2026-06-03 01:41:23'),
(39, 9, 0.000, '2026-06-03 01:41:23', '2026-06-03 01:41:23'),
(40, 8, 20.690, '2026-06-03 01:41:23', '2026-06-03 01:41:23'),
(41, 10, 19377.000, '2026-06-03 01:42:23', '2026-06-03 01:42:23'),
(42, 9, 0.000, '2026-06-03 01:42:23', '2026-06-03 01:42:23'),
(43, 8, 22.190, '2026-06-03 01:42:23', '2026-06-03 01:42:23'),
(44, 10, 961.000, '2026-06-03 01:43:23', '2026-06-03 01:43:23'),
(45, 9, 0.000, '2026-06-03 01:43:23', '2026-06-03 01:43:23'),
(46, 8, 24.750, '2026-06-03 01:43:23', '2026-06-03 01:43:23'),
(47, 10, 0.000, '2026-06-03 01:44:23', '2026-06-03 01:44:23'),
(48, 9, 0.000, '2026-06-03 01:44:23', '2026-06-03 01:44:23'),
(49, 8, 23.500, '2026-06-03 01:44:23', '2026-06-03 01:44:23'),
(50, 10, 0.000, '2026-06-03 02:12:25', '2026-06-03 02:12:25'),
(51, 9, 0.000, '2026-06-03 02:12:25', '2026-06-03 02:12:25'),
(52, 8, 19.190, '2026-06-03 02:12:25', '2026-06-03 02:12:25'),
(53, 10, 0.000, '2026-06-03 02:13:24', '2026-06-03 02:13:24'),
(54, 9, 0.000, '2026-06-03 02:13:24', '2026-06-03 02:13:24'),
(55, 8, 19.620, '2026-06-03 02:13:24', '2026-06-03 02:13:24'),
(56, 10, 0.000, '2026-06-03 02:14:24', '2026-06-03 02:14:24'),
(57, 9, 0.000, '2026-06-03 02:14:24', '2026-06-03 02:14:24'),
(58, 8, 19.880, '2026-06-03 02:14:24', '2026-06-03 02:14:24'),
(59, 10, 0.000, '2026-06-03 02:15:24', '2026-06-03 02:15:24'),
(60, 9, 0.000, '2026-06-03 02:15:24', '2026-06-03 02:15:24'),
(61, 8, 20.310, '2026-06-03 02:15:24', '2026-06-03 02:15:24'),
(62, 10, 0.000, '2026-06-03 02:16:24', '2026-06-03 02:16:24'),
(63, 9, 0.000, '2026-06-03 02:16:24', '2026-06-03 02:16:24'),
(64, 8, 20.690, '2026-06-03 02:16:24', '2026-06-03 02:16:24'),
(65, 10, 0.000, '2026-06-03 02:17:24', '2026-06-03 02:17:24'),
(66, 9, 0.000, '2026-06-03 02:17:24', '2026-06-03 02:17:24'),
(67, 8, 20.690, '2026-06-03 02:17:24', '2026-06-03 02:17:24'),
(68, 10, 0.000, '2026-06-03 02:18:25', '2026-06-03 02:18:25'),
(69, 9, 0.000, '2026-06-03 02:18:25', '2026-06-03 02:18:25'),
(70, 8, 20.560, '2026-06-03 02:18:25', '2026-06-03 02:18:25'),
(71, 10, 0.000, '2026-06-03 02:19:24', '2026-06-03 02:19:24'),
(72, 9, 0.000, '2026-06-03 02:19:24', '2026-06-03 02:19:24'),
(73, 8, 20.560, '2026-06-03 02:19:24', '2026-06-03 02:19:24'),
(74, 10, 0.000, '2026-06-03 02:20:24', '2026-06-03 02:20:24'),
(75, 9, 0.000, '2026-06-03 02:20:24', '2026-06-03 02:20:24'),
(76, 8, 20.500, '2026-06-03 02:20:24', '2026-06-03 02:20:24'),
(77, 10, 0.000, '2026-06-03 02:21:25', '2026-06-03 02:21:25'),
(78, 9, 0.000, '2026-06-03 02:21:25', '2026-06-03 02:21:25'),
(79, 8, 20.440, '2026-06-03 02:21:25', '2026-06-03 02:21:25'),
(80, 10, 0.000, '2026-06-03 02:22:25', '2026-06-03 02:22:25'),
(81, 9, 0.000, '2026-06-03 02:22:25', '2026-06-03 02:22:25'),
(82, 8, 20.310, '2026-06-03 02:22:25', '2026-06-03 02:22:25'),
(83, 10, 0.000, '2026-06-03 02:23:24', '2026-06-03 02:23:24'),
(84, 9, 0.000, '2026-06-03 02:23:24', '2026-06-03 02:23:24'),
(85, 8, 20.310, '2026-06-03 02:23:24', '2026-06-03 02:23:24'),
(86, 10, 0.000, '2026-06-03 02:24:24', '2026-06-03 02:24:24'),
(87, 9, 0.000, '2026-06-03 02:24:24', '2026-06-03 02:24:24'),
(88, 8, 20.250, '2026-06-03 02:24:24', '2026-06-03 02:24:24'),
(89, 10, 0.000, '2026-06-03 02:25:25', '2026-06-03 02:25:25'),
(90, 9, 0.000, '2026-06-03 02:25:25', '2026-06-03 02:25:25'),
(91, 8, 20.190, '2026-06-03 02:25:25', '2026-06-03 02:25:25'),
(92, 10, 0.000, '2026-06-03 02:26:24', '2026-06-03 02:26:24'),
(93, 9, 0.000, '2026-06-03 02:26:24', '2026-06-03 02:26:24'),
(94, 8, 20.190, '2026-06-03 02:26:24', '2026-06-03 02:26:24'),
(95, 10, 0.000, '2026-06-03 02:27:25', '2026-06-03 02:27:25'),
(96, 9, 0.000, '2026-06-03 02:27:25', '2026-06-03 02:27:25'),
(97, 8, 20.190, '2026-06-03 02:27:25', '2026-06-03 02:27:25'),
(98, 10, 0.000, '2026-06-03 02:28:25', '2026-06-03 02:28:25'),
(99, 9, 0.000, '2026-06-03 02:28:25', '2026-06-03 02:28:25'),
(100, 8, 20.120, '2026-06-03 02:28:25', '2026-06-03 02:28:25'),
(101, 10, 0.000, '2026-06-03 02:29:25', '2026-06-03 02:29:25'),
(102, 9, 0.000, '2026-06-03 02:29:25', '2026-06-03 02:29:25'),
(103, 8, 20.120, '2026-06-03 02:29:25', '2026-06-03 02:29:25'),
(104, 10, 0.000, '2026-06-03 02:30:25', '2026-06-03 02:30:25'),
(105, 9, 0.000, '2026-06-03 02:30:25', '2026-06-03 02:30:25'),
(106, 8, 20.250, '2026-06-03 02:30:25', '2026-06-03 02:30:25'),
(107, 10, 0.000, '2026-06-03 02:31:25', '2026-06-03 02:31:25'),
(108, 9, 0.000, '2026-06-03 02:31:25', '2026-06-03 02:31:25'),
(109, 8, 20.440, '2026-06-03 02:31:25', '2026-06-03 02:31:25'),
(110, 10, 0.000, '2026-06-03 02:32:25', '2026-06-03 02:32:25'),
(111, 9, 0.000, '2026-06-03 02:32:25', '2026-06-03 02:32:25'),
(112, 8, 20.310, '2026-06-03 02:32:25', '2026-06-03 02:32:25'),
(113, 10, 0.000, '2026-06-03 02:33:25', '2026-06-03 02:33:25'),
(114, 9, 0.000, '2026-06-03 02:33:25', '2026-06-03 02:33:25'),
(115, 8, 20.250, '2026-06-03 02:33:25', '2026-06-03 02:33:25'),
(116, 10, 0.000, '2026-06-03 02:34:25', '2026-06-03 02:34:25'),
(117, 9, 0.000, '2026-06-03 02:34:25', '2026-06-03 02:34:25'),
(118, 8, 20.190, '2026-06-03 02:34:25', '2026-06-03 02:34:25'),
(119, 10, 0.000, '2026-06-03 02:35:25', '2026-06-03 02:35:25'),
(120, 9, 0.000, '2026-06-03 02:35:25', '2026-06-03 02:35:25'),
(121, 8, 20.120, '2026-06-03 02:35:25', '2026-06-03 02:35:25'),
(122, 10, 0.000, '2026-06-03 02:36:25', '2026-06-03 02:36:25'),
(123, 9, 0.000, '2026-06-03 02:36:25', '2026-06-03 02:36:25'),
(124, 8, 20.120, '2026-06-03 02:36:25', '2026-06-03 02:36:25'),
(125, 10, 0.000, '2026-06-03 02:37:25', '2026-06-03 02:37:25'),
(126, 9, 0.000, '2026-06-03 02:37:25', '2026-06-03 02:37:25'),
(127, 8, 20.120, '2026-06-03 02:37:25', '2026-06-03 02:37:25'),
(128, 10, 0.000, '2026-06-03 02:38:25', '2026-06-03 02:38:25'),
(129, 9, 0.000, '2026-06-03 02:38:25', '2026-06-03 02:38:25'),
(130, 8, 20.120, '2026-06-03 02:38:25', '2026-06-03 02:38:25'),
(131, 10, 0.000, '2026-06-03 02:39:25', '2026-06-03 02:39:25'),
(132, 9, 0.000, '2026-06-03 02:39:25', '2026-06-03 02:39:25'),
(133, 8, 20.060, '2026-06-03 02:39:25', '2026-06-03 02:39:25'),
(134, 10, 0.000, '2026-06-03 02:40:25', '2026-06-03 02:40:25'),
(135, 9, 0.000, '2026-06-03 02:40:25', '2026-06-03 02:40:25'),
(136, 8, 20.000, '2026-06-03 02:40:25', '2026-06-03 02:40:25'),
(137, 10, 0.000, '2026-06-03 02:41:25', '2026-06-03 02:41:25'),
(138, 9, 0.000, '2026-06-03 02:41:25', '2026-06-03 02:41:25'),
(139, 8, 20.000, '2026-06-03 02:41:25', '2026-06-03 02:41:25'),
(140, 10, 0.000, '2026-06-03 02:42:25', '2026-06-03 02:42:25'),
(141, 9, 0.000, '2026-06-03 02:42:25', '2026-06-03 02:42:25'),
(142, 8, 20.000, '2026-06-03 02:42:25', '2026-06-03 02:42:25'),
(143, 10, 0.000, '2026-06-03 02:43:25', '2026-06-03 02:43:25'),
(144, 9, 0.000, '2026-06-03 02:43:25', '2026-06-03 02:43:25'),
(145, 8, 19.940, '2026-06-03 02:43:25', '2026-06-03 02:43:25'),
(146, 10, 48.000, '2026-06-03 02:44:25', '2026-06-03 02:44:25'),
(147, 9, 0.000, '2026-06-03 02:44:25', '2026-06-03 02:44:25'),
(148, 8, 20.000, '2026-06-03 02:44:25', '2026-06-03 02:44:25'),
(149, 10, 0.000, '2026-06-03 02:45:25', '2026-06-03 02:45:25'),
(150, 9, 0.000, '2026-06-03 02:45:25', '2026-06-03 02:45:25'),
(151, 8, 20.000, '2026-06-03 02:45:25', '2026-06-03 02:45:25'),
(152, 10, 0.000, '2026-06-03 02:46:25', '2026-06-03 02:46:25'),
(153, 9, 0.000, '2026-06-03 02:46:25', '2026-06-03 02:46:25'),
(154, 8, 19.940, '2026-06-03 02:46:25', '2026-06-03 02:46:25'),
(155, 10, 0.000, '2026-06-03 02:47:25', '2026-06-03 02:47:25'),
(156, 9, 0.000, '2026-06-03 02:47:25', '2026-06-03 02:47:25'),
(157, 8, 19.880, '2026-06-03 02:47:25', '2026-06-03 02:47:25'),
(158, 10, 0.000, '2026-06-03 02:48:25', '2026-06-03 02:48:25'),
(159, 9, 0.000, '2026-06-03 02:48:25', '2026-06-03 02:48:25'),
(160, 8, 19.880, '2026-06-03 02:48:25', '2026-06-03 02:48:25'),
(161, 10, 0.000, '2026-06-03 02:49:25', '2026-06-03 02:49:25'),
(162, 9, 0.000, '2026-06-03 02:49:25', '2026-06-03 02:49:25'),
(163, 8, 19.880, '2026-06-03 02:49:25', '2026-06-03 02:49:25'),
(164, 10, 0.000, '2026-06-03 02:50:25', '2026-06-03 02:50:25'),
(165, 9, 0.000, '2026-06-03 02:50:25', '2026-06-03 02:50:25'),
(166, 8, 19.810, '2026-06-03 02:50:25', '2026-06-03 02:50:25'),
(167, 10, 0.000, '2026-06-03 02:51:25', '2026-06-03 02:51:25'),
(168, 9, 0.000, '2026-06-03 02:51:25', '2026-06-03 02:51:25'),
(169, 8, 19.880, '2026-06-03 02:51:25', '2026-06-03 02:51:25'),
(170, 10, 0.000, '2026-06-03 02:52:25', '2026-06-03 02:52:25'),
(171, 9, 0.000, '2026-06-03 02:52:25', '2026-06-03 02:52:25'),
(172, 8, 19.880, '2026-06-03 02:52:25', '2026-06-03 02:52:25'),
(173, 10, 0.000, '2026-06-03 02:53:25', '2026-06-03 02:53:25'),
(174, 9, 0.000, '2026-06-03 02:53:25', '2026-06-03 02:53:25'),
(175, 8, 19.810, '2026-06-03 02:53:25', '2026-06-03 02:53:25'),
(176, 10, 0.000, '2026-06-03 02:54:25', '2026-06-03 02:54:25'),
(177, 9, 0.000, '2026-06-03 02:54:25', '2026-06-03 02:54:25'),
(178, 8, 19.880, '2026-06-03 02:54:25', '2026-06-03 02:54:25'),
(179, 10, 0.000, '2026-06-03 02:55:25', '2026-06-03 02:55:25'),
(180, 9, 0.000, '2026-06-03 02:55:25', '2026-06-03 02:55:25'),
(181, 8, 19.810, '2026-06-03 02:55:25', '2026-06-03 02:55:25'),
(182, 10, 0.000, '2026-06-03 02:56:25', '2026-06-03 02:56:25'),
(183, 9, 0.000, '2026-06-03 02:56:25', '2026-06-03 02:56:25'),
(184, 8, 19.810, '2026-06-03 02:56:25', '2026-06-03 02:56:25'),
(185, 10, 0.000, '2026-06-03 02:57:25', '2026-06-03 02:57:25'),
(186, 9, 0.000, '2026-06-03 02:57:25', '2026-06-03 02:57:25'),
(187, 8, 19.810, '2026-06-03 02:57:25', '2026-06-03 02:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_types`
--

CREATE TABLE `sensor_types` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `default_chart` varchar(20) NOT NULL DEFAULT 'line',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_types`
--

INSERT INTO `sensor_types` (`id`, `code`, `name`, `unit`, `default_chart`, `created_at`) VALUES
(1, 'temperature', 'Temperatura', '°C', 'line', '2026-05-29 21:11:27'),
(2, 'humidity', 'Humedad', '%', 'area', '2026-05-29 21:11:27'),
(3, 'vibration', 'Vibración', 'mm/s', 'line', '2026-05-29 21:11:27'),
(4, 'rpm', 'RPM', 'rev/min', 'gauge', '2026-05-29 21:11:27'),
(5, 'current', 'Corriente', 'A', 'bars', '2026-05-29 21:11:27'),
(6, 'on_off', 'Estado ON/OFF', NULL, 'timeline', '2026-05-29 21:11:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nombre`, `apellido`, `email`, `password_hash`, `foto_perfil`, `created_at`) VALUES
(1, 'Jose', 'Mangold', 'josemangold08@gmail.com', '$2y$10$VfXfh6pQ9JWPOQvUchd2dOyAiWw9C3COQTNL0CDgkiDQ1e5XzmvGG', NULL, '2026-04-27 19:38:47'),
(2, 'Emaaa', '', 'emarissopatron@gmail.com', '$2y$10$mrq7CrKrWsQpOf3cZhLauuBKzJap936E4FnjklWjkkfmXZ71Im3QG', NULL, '2026-05-08 14:53:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `esp32_devices`
--
ALTER TABLE `esp32_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mac_address` (`mac_address`),
  ADD KEY `fk_esp32_devices_industry` (`industry_id`);

--
-- Indexes for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esp32_device_id_sensor_type_id` (`esp32_device_id`,`sensor_type_id`),
  ADD KEY `fk_esp32_sensors_device` (`esp32_device_id`),
  ADD KEY `fk_esp32_sensors_type` (`sensor_type_id`);

--
-- Indexes for table `form_answers`
--
ALTER TABLE `form_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `raw_timestamp` (`raw_timestamp`),
  ADD KEY `submitted_at` (`submitted_at`);

--
-- Indexes for table `industries`
--
ALTER TABLE `industries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_uid` (`industry_uid`),
  ADD KEY `fk_industries_creator` (`created_by`);

--
-- Indexes for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_id_sensor_type_id` (`industry_id`,`sensor_type_id`),
  ADD KEY `fk_industry_metrics_industry` (`industry_id`),
  ADD KEY `fk_industry_metrics_type` (`sensor_type_id`);

--
-- Indexes for table `industry_users`
--
ALTER TABLE `industry_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_id_user_id` (`industry_id`,`user_id`),
  ADD KEY `fk_industry_users_industry` (`industry_id`),
  ADD KEY `fk_industry_users_user` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_password_resets_user` (`user_id`);

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esp32_sensor_id_measured_at` (`esp32_sensor_id`,`measured_at`);

--
-- Indexes for table `sensor_types`
--
ALTER TABLE `sensor_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `esp32_devices`
--
ALTER TABLE `esp32_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `industry_users`
--
ALTER TABLE `industry_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `sensor_types`
--
ALTER TABLE `sensor_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `esp32_devices`
--
ALTER TABLE `esp32_devices`
  ADD CONSTRAINT `fk_esp32_devices_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  ADD CONSTRAINT `fk_esp32_sensors_device` FOREIGN KEY (`esp32_device_id`) REFERENCES `esp32_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_esp32_sensors_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `industries`
--
ALTER TABLE `industries`
  ADD CONSTRAINT `fk_industries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  ADD CONSTRAINT `fk_industry_metrics_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_industry_metrics_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `industry_users`
--
ALTER TABLE `industry_users`
  ADD CONSTRAINT `fk_industry_users_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_industry_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD CONSTRAINT `fk_sensor_readings_sensor` FOREIGN KEY (`esp32_sensor_id`) REFERENCES `esp32_sensors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
