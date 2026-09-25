-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 02:49 PM
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
  `mac_address` varchar(17) DEFAULT NULL,
  `sector` varchar(120) DEFAULT NULL COMMENT 'Descripcion a gusto',
  `token_hash` varchar(64) DEFAULT NULL COMMENT 'Hash del token de autenticación',
  `token_secret` text DEFAULT NULL COMMENT 'Secreto cifrado para HMAC',
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'offline' COMMENT 'Ej: online, actualizando etc',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esp32_devices`
--

INSERT INTO `esp32_devices` (`id`, `industry_id`, `name`, `mac_address`, `sector`, `token_hash`, `token_secret`, `is_active`, `status`, `last_seen_at`, `created_at`) VALUES
(3, 2, 'ESP32 Extrusora', 'D4:E9:F4:8D:18:7C', 'descripcion campo sector', 'ac8051e714f7d24cfa4730c8a7ecefb2b374092942613a91172fc1b641a0dbe5', 'e3P4o7tOjVQE3WClK53L0GZ7lAJ99vYuBnoWCfM/8mibGmhgdMOvYi0pVW8Ie/efXjmj9OCwoILw58chniovv8EWaQlKXiTJ5OxMVWw2kUs2RLMF0a4tfsmBANgFo0fU', 1, 'online', '2026-07-31 15:52:36', '2026-07-31 15:40:31'),
(5, 2, 'ESP32 Extrusoras', NULL, NULL, '0d8e22e42644cd316e5965c8df43312e0ac4ae6543e1a0a5137a80bb44f680e8', 'SFRYnLt9GjZctWFN5ZZ/aQO0KyROQotj8CUKwzGkWQr8uUIVGzo/3Lt6ZouKENZKiJMfxsvWFXLV+QZ4hNG1j/sSx46SV0IvrKJyQhhb64QFc+oRwYc9VmBKOW244l5l', 1, 'offline', NULL, '2026-08-05 17:32:40'),
(6, 5, 'ESP32 B. Avellaneda', NULL, NULL, '95d3c1f9660f393f017624ddf6cf15c3c1f0693cbaa5b89a6a9c09bfd6b43200', 'XUldwyKP9LBZLQ7VxyMTSvGa/lylkA/e+3Jie0MWmaPZyOO25s9xDXbMPfpIp4WMFrfUD4/EjjRcG132yZ+ns5uIBIyMWkdXYcowS/40ScD9CdAZNG6kOVw4GxTjh3Rm', 1, 'offline', NULL, '2026-09-17 11:30:38'),
(7, 2, 'Hello', NULL, NULL, 'dd476a9eaa5d0f480e73646921fd3a34774a01ec8195f5e8822861fa503c3222', 'bQo3wGZNVKHAGYB04fDwxKkHVw03jB58iSljrCxdmXQSXgoGqUHTYXROkVMEvXkoxz6HSYA4ALOt7Xk8CTS0ZEEWmy7176nKnf4/RdKZQh9HUERFjNxrtAAcvsDaz3tp', 1, 'offline', NULL, '2026-09-17 11:44:26');

-- --------------------------------------------------------

--
-- Table structure for table `esp32_device_access`
--

CREATE TABLE `esp32_device_access` (
  `id` int(11) NOT NULL,
  `industry_user_id` int(11) NOT NULL,
  `esp32_device_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esp32_sensors`
--

CREATE TABLE `esp32_sensors` (
  `id` int(11) NOT NULL,
  `esp32_device_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL COMMENT 'Nombre de los Sensores',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esp32_sensors`
--

INSERT INTO `esp32_sensors` (`id`, `esp32_device_id`, `sensor_type_id`, `label`, `created_at`) VALUES
(7, 3, 4, 'RPM 1', '2026-07-31 15:40:31'),
(8, 3, 1, 'Corriente', '2026-07-31 15:40:31'),
(9, 3, 3, NULL, '2026-07-31 15:40:31'),
(15, 7, 4, NULL, '2026-09-17 11:44:26'),
(16, 7, 1, NULL, '2026-09-17 11:44:26'),
(17, 7, 3, NULL, '2026-09-17 11:44:26');

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

-- --------------------------------------------------------

--
-- Table structure for table `industries`
--

CREATE TABLE `industries` (
  `id` int(11) NOT NULL,
  `industry_uid` varchar(40) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `space_name` varchar(200) NOT NULL,
  `sector` varchar(120) DEFAULT NULL COMMENT 'Descripcion a gusto del Espacio',
  `space_password_hash` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industries`
--

INSERT INTO `industries` (`id`, `industry_uid`, `company_name`, `space_name`, `sector`, `space_password_hash`, `created_by`, `created_at`) VALUES
(2, 'SJS-IND-4B1DCC', 'San Jose SRL', 'Extrusoras', 'Planta aceitera', '$2y$10$gxb7ZbvtD376nC/WWjvOO.fBLs.lZ7H8wVlCMpTY6DBgCjjDASXY2', 1, '2026-07-31 15:39:54'),
(4, 'TI-IND-805D32', 'Tacho ITR3', 'Extrusoras', 'Extrusora', '$2y$10$Ejy/Cr57axm0xqs1fuOIquPGEHyZdQMIS5Fsj.shz/gMVqPMBu3EG', 1, '2026-08-21 14:38:26'),
(5, 'YX-IND-A767E2', 'YPF', 'B. Avellaneda', 'Servicoios', '$2y$10$kd2t80JcsBa4p/US/RTnc.sxuUqdssgxEedydEX6QH9w33RE833u2', 3, '2026-09-17 11:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `industry_metrics`
--

CREATE TABLE `industry_metrics` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `suggested_chart` varchar(20) NOT NULL DEFAULT 'line' COMMENT 'Tipo de gráfico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industry_metrics`
--

INSERT INTO `industry_metrics` (`id`, `industry_id`, `sensor_type_id`, `suggested_chart`) VALUES
(4, 2, 4, 'gauge'),
(5, 2, 1, 'line'),
(6, 2, 3, 'line'),
(7, 4, 5, 'bars'),
(8, 5, 5, 'bars'),
(9, 5, 6, 'timeline');

-- --------------------------------------------------------

--
-- Table structure for table `industry_users`
--

CREATE TABLE `industry_users` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `permission` enum('read','edit') DEFAULT 'read',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `industry_users`
--

INSERT INTO `industry_users` (`id`, `industry_id`, `user_id`, `role`, `permission`, `joined_at`) VALUES
(3, 2, 1, 'admin_principal', 'read', '2026-07-31 15:39:54'),
(4, 2, 3, 'admin_principal', 'read', '2026-07-31 16:06:35'),
(5, 4, 1, 'admin_principal', 'read', '2026-08-21 14:38:26'),
(6, 5, 3, 'admin_principal', 'read', '2026-09-17 11:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_activity_log`
--

CREATE TABLE `kanban_activity_log` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_activity_log`
--

INSERT INTO `kanban_activity_log` (`id`, `board_id`, `task_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(92, 3, NULL, 1, 'column_created', 'creó la columna «Pendiente»', '2026-08-05 14:00:12'),
(95, 3, NULL, 1, 'column_created', 'creó la columna «Terminado»', '2026-08-05 14:01:45'),
(105, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 15:10:35'),
(107, 3, NULL, 1, 'column_deleted', 'eliminó la columna «Pendiente»', '2026-08-05 16:10:35'),
(108, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:10:38'),
(109, 3, NULL, 1, 'column_updated', 'editó la columna «Terminado»', '2026-08-05 16:10:41'),
(112, 3, NULL, 1, 'column_created', 'creó la columna «hola»', '2026-08-05 16:23:16'),
(113, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:27:37'),
(114, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:27:38'),
(115, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:27:38'),
(116, 3, NULL, 1, 'column_duplicated', 'duplicó la columna «hola»', '2026-08-05 16:36:00'),
(117, 3, NULL, 1, 'column_updated', 'editó la columna «hola»', '2026-08-05 16:36:10'),
(118, 3, NULL, 1, 'column_updated', 'editó la columna «hola»', '2026-08-05 16:36:12'),
(119, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:36:16'),
(186, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:46:37'),
(187, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:46:37'),
(188, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:46:38'),
(189, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:46:40'),
(191, 3, NULL, 1, 'task_deleted', 'eliminó la tarea «hola»', '2026-08-05 16:48:27'),
(192, 3, NULL, 1, 'task_deleted', 'eliminó la tarea «tonto»', '2026-08-05 16:48:30'),
(194, 3, NULL, 1, 'task_deleted', 'eliminó la tarea «hola»', '2026-08-05 16:48:38'),
(195, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:51:01'),
(196, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 16:51:03'),
(198, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-05 17:02:27'),
(199, 3, NULL, 1, 'column_created', 'creó la columna «Pendiente»', '2026-08-05 17:02:27'),
(200, 3, NULL, 1, 'column_updated', 'editó la columna «Pendiente»', '2026-08-05 17:02:27'),
(201, 3, NULL, 1, 'column_created', 'creó la columna «En progreso»', '2026-08-05 17:02:27'),
(202, 3, NULL, 1, 'column_updated', 'editó la columna «En progreso»', '2026-08-05 17:02:27'),
(203, 3, NULL, 1, 'column_created', 'creó la columna «En revisión»', '2026-08-05 17:02:27'),
(204, 3, NULL, 1, 'column_updated', 'editó la columna «En revisión»', '2026-08-05 17:02:27'),
(205, 3, NULL, 1, 'column_created', 'creó la columna «Completado»', '2026-08-05 17:02:27'),
(206, 3, NULL, 1, 'column_updated', 'editó la columna «Completado»', '2026-08-05 17:02:27'),
(234, 3, NULL, 1, 'task_deleted', 'eliminó la tarea «Tarea de prueba de estilos»', '2026-08-05 17:06:52'),
(245, 3, NULL, 1, 'column_created', 'creó la columna «Pendiente»', '2026-08-05 17:12:54'),
(246, 3, NULL, 1, 'column_created', 'creó la columna «En progreso»', '2026-08-05 17:12:54'),
(247, 3, NULL, 1, 'column_created', 'creó la columna «Completado»', '2026-08-05 17:12:54'),
(248, 3, NULL, 1, 'column_updated', 'editó la columna «hola»', '2026-08-05 17:12:54'),
(249, 3, NULL, 1, 'column_updated', 'editó la columna «ohal»', '2026-08-05 17:12:54'),
(266, 3, NULL, 1, 'column_duplicated', 'duplicó la columna «ohal»', '2026-08-05 17:12:57'),
(267, 3, NULL, 1, 'column_deleted', 'eliminó la columna «hola»', '2026-08-05 17:12:58'),
(268, 3, NULL, 1, 'column_deleted', 'eliminó la columna «ohal»', '2026-08-05 17:12:58'),
(269, 3, NULL, 1, 'column_deleted', 'eliminó la columna «Terminado»', '2026-08-05 17:12:58'),
(270, 3, NULL, 1, 'column_deleted', 'eliminó la columna «hola (copia)»', '2026-08-05 17:12:58'),
(271, 3, NULL, 1, 'column_deleted', 'eliminó la columna «Pendiente»', '2026-08-05 17:12:58'),
(272, 3, NULL, 1, 'column_deleted', 'eliminó la columna «En progreso»', '2026-08-05 17:12:58'),
(273, 3, NULL, 1, 'column_deleted', 'eliminó la columna «Completado»', '2026-08-05 17:12:58'),
(274, 3, NULL, 1, 'column_deleted', 'eliminó la columna «ohal (copia)»', '2026-08-05 17:12:58'),
(301, 3, 23, 1, 'task_moved', 'movió la tarea de «Terminado» a «ohal»', '2026-08-05 17:36:26'),
(302, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola»', '2026-08-05 17:36:29'),
(303, 3, NULL, 1, 'column_deleted', 'eliminó la columna «Terminado»', '2026-08-06 11:20:34'),
(304, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «ohal»', '2026-08-06 11:20:44'),
(305, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola (copia)»', '2026-08-06 11:20:49'),
(306, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (copia)» a «ohal»', '2026-08-06 11:20:50'),
(307, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola (copia)»', '2026-08-06 11:20:51'),
(308, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (copia)» a «hola»', '2026-08-06 11:22:31'),
(309, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «ohal»', '2026-08-06 11:35:33'),
(310, 3, NULL, 1, 'column_created', 'creó la columna «Holaa»', '2026-08-06 11:39:08'),
(311, 3, NULL, 1, 'column_updated', 'editó la columna «Hola»', '2026-08-06 11:39:14'),
(312, 3, 23, 1, 'task_updated', 'editó la tarea', '2026-08-06 11:46:04'),
(313, 3, 23, 1, 'task_updated', 'editó la tarea', '2026-08-06 11:49:02'),
(314, 3, 23, 1, 'task_updated', 'cambió la prioridad a alta', '2026-08-06 11:49:38'),
(315, 3, 23, 1, 'task_updated', 'cambió la prioridad a urgente', '2026-08-06 11:49:45'),
(316, 3, 23, 1, 'task_updated', 'cambió la prioridad a baja', '2026-08-06 11:49:49'),
(317, 3, 23, 1, 'task_updated', 'cambió la prioridad a media', '2026-08-06 11:49:52'),
(318, 3, NULL, 1, 'column_created', 'creó la columna «tonto»', '2026-08-06 11:55:32'),
(319, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola (copia)»', '2026-08-06 12:27:21'),
(320, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (copia)» a «ohal»', '2026-08-06 12:27:23'),
(321, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola (copia)»', '2026-08-06 12:27:25'),
(322, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (copia)» a «ohal»', '2026-08-06 12:27:29'),
(323, 3, 23, 1, 'task_moved', 'movió la tarea de «ohal» a «hola»', '2026-08-06 12:27:31'),
(324, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-06 12:27:40'),
(325, 3, NULL, 1, 'column_updated', 'editó la columna «hola (tonto)»', '2026-08-06 12:28:19'),
(326, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-06 12:28:29'),
(327, 3, NULL, 1, 'column_updated', 'editó la columna «olga»', '2026-08-06 12:28:39'),
(328, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «olga»', '2026-08-06 12:30:07'),
(329, 3, 23, 1, 'task_moved', 'movió la tarea de «olga» a «hola»', '2026-08-06 12:30:09'),
(330, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «olga»', '2026-08-06 12:30:09'),
(331, 3, 23, 1, 'task_moved', 'movió la tarea de «olga» a «hola (tonto)»', '2026-08-06 12:30:10'),
(332, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (tonto)» a «olga»', '2026-08-06 12:43:58'),
(333, 3, 23, 1, 'task_moved', 'movió la tarea de «olga» a «hola»', '2026-08-06 12:44:00'),
(334, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «hola (tonto)»', '2026-08-06 12:44:01'),
(335, 3, 23, 1, 'task_moved', 'movió la tarea de «hola (tonto)» a «olga»', '2026-08-06 12:51:58'),
(336, 3, 23, 1, 'task_moved', 'movió la tarea de «olga» a «hola»', '2026-08-06 12:51:59'),
(337, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «Hola»', '2026-08-06 12:52:00'),
(338, 3, 23, 1, 'task_moved', 'movió la tarea de «Hola» a «hola»', '2026-08-06 12:52:01'),
(340, 3, 45, 1, 'task_created', 'creó la tarea «dfgdfg»', '2026-08-06 12:52:06'),
(341, 3, 46, 1, 'task_created', 'creó la tarea «dfgd»', '2026-08-06 12:52:08'),
(342, 3, 47, 1, 'task_created', 'creó la tarea «fdgdfgdg»', '2026-08-06 12:52:11'),
(343, 3, 48, 1, 'task_created', 'creó la tarea «fdg»', '2026-08-06 12:52:11'),
(344, 3, 49, 1, 'task_created', 'creó la tarea «d»', '2026-08-06 12:52:12'),
(345, 3, 50, 1, 'task_created', 'creó la tarea «f»', '2026-08-06 12:52:12'),
(346, 3, 51, 1, 'task_created', 'creó la tarea «dfgd»', '2026-08-06 12:52:14'),
(347, 3, 48, 1, 'task_moved', 'movió la tarea de «Hola» a «hola»', '2026-08-07 13:03:00'),
(348, 3, 23, 1, 'task_moved', 'movió la tarea de «hola» a «Hola»', '2026-08-07 13:03:01'),
(349, 3, 46, 1, 'task_moved', 'movió la tarea de «hola (tonto)» a «hola»', '2026-08-19 17:20:55'),
(350, 3, 48, 1, 'task_moved', 'movió la tarea de «hola» a «olga»', '2026-08-19 17:23:56'),
(351, 3, 48, 1, 'task_moved', 'movió la tarea de «olga» a «hola»', '2026-08-19 17:23:58'),
(354, 3, 23, 1, 'task_moved', 'movió la tarea de «Hola» a «olga»', '2026-08-19 17:24:15'),
(355, 3, 47, 1, 'task_moved', 'reordenó la tarea en «Hola»', '2026-08-19 17:24:20'),
(356, 3, 50, 1, 'task_moved', 'movió la tarea de «Hola» a «olga»', '2026-08-19 17:25:47'),
(357, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-19 17:27:25'),
(358, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-19 17:28:33'),
(359, 3, NULL, 1, 'column_updated', 'editó la columna «dfdfdf»', '2026-08-19 17:28:38'),
(360, 3, NULL, 1, 'board_updated', 'actualizó el tablero', '2026-08-19 17:29:20'),
(361, 3, NULL, 1, 'column_updated', 'editó la columna «cgdfgd»', '2026-08-19 17:29:23'),
(362, 3, 47, 1, 'task_moved', 'movió la tarea de «Hola» a «hola»', '2026-08-19 17:34:04'),
(363, 3, 47, 1, 'task_moved', 'movió la tarea de «hola» a «Hola»', '2026-08-19 17:34:07'),
(364, 3, 51, 1, 'task_moved', 'movió la tarea de «cgdfgd» a «hola»', '2026-08-19 17:40:02'),
(365, 3, 48, 1, 'task_moved', 'movió la tarea de «hola» a «cgdfgd»', '2026-08-19 17:40:03'),
(366, 3, 23, 1, 'task_moved', 'movió la tarea de «dfdfdf» a «Hola»', '2026-08-19 17:40:12'),
(367, 3, 49, 1, 'task_moved', 'movió la tarea de «Hola» a «dfdfdf»', '2026-08-19 17:40:13'),
(369, 3, 50, 1, 'task_moved', 'movió la tarea de «dfdfdf» a «Hola»', '2026-08-19 17:40:15'),
(370, 3, 50, 1, 'task_moved', 'movió la tarea de «Hola» a «dfdfdf»', '2026-08-19 17:40:18'),
(371, 3, 47, 1, 'task_moved', 'movió la tarea de «Hola» a «cgdfgd»', '2026-08-19 17:40:20'),
(372, 3, 46, 1, 'task_moved', 'movió la tarea de «hola» a «dfdfdf»', '2026-08-19 17:40:26'),
(373, 3, 49, 1, 'task_moved', 'movió la tarea de «dfdfdf» a «hola»', '2026-08-19 18:10:33'),
(374, 3, 49, 1, 'task_moved', 'movió la tarea de «hola» a «dfdfdf»', '2026-08-19 18:10:33'),
(375, 3, 49, 1, 'task_moved', 'movió la tarea de «dfdfdf» a «hola»', '2026-08-19 18:10:34'),
(376, 3, 49, 1, 'task_moved', 'movió la tarea de «hola» a «dfdfdf»', '2026-08-19 18:10:37'),
(377, 3, NULL, 1, 'column_archived', 'archivó la columna «hola»', '2026-08-20 10:58:47'),
(389, 3, 52, 1, 'task_created', 'convirtió un ítem de checklist en tarea', '2026-08-20 11:37:24'),
(390, 3, 53, 1, 'task_created', 'convirtió un ítem de checklist en tarea', '2026-08-20 11:37:27'),
(391, 3, 54, 1, 'task_created', 'convirtió un ítem de checklist en tarea', '2026-08-20 11:37:31'),
(393, 3, 48, 1, 'checklist_created', 'agregó el checklist «Checklist»', '2026-08-20 11:46:15'),
(394, 3, 48, 1, 'attachment_added', 'adjuntó un archivo', '2026-08-20 11:46:35'),
(395, 3, 47, 1, 'checklist_created', 'agregó el checklist «Checklist»', '2026-08-20 11:49:55'),
(396, 3, 52, 1, 'task_moved', 'movió la tarea de «Hola» a «dfdfdf»', '2026-08-20 13:08:55'),
(397, 3, 52, 1, 'task_moved', 'movió la tarea de «dfdfdf» a «hola (tonto)»', '2026-08-20 13:08:57'),
(398, 3, 53, 1, 'task_moved', 'movió la tarea de «Hola» a «hola (tonto)»', '2026-08-20 13:09:28'),
(399, 3, 54, 1, 'task_updated', 'cambió la prioridad a baja', '2026-08-20 13:10:24'),
(401, 3, 23, 1, 'task_moved', 'movió la tarea de «Hola» a «dfdfdf»', '2026-08-20 13:22:02'),
(402, 3, 23, 1, 'checklist_created', 'agregó el checklist «Checklist»', '2026-08-20 14:03:07'),
(403, 3, 23, 1, 'checklist_updated', 'agregó un ítem al checklist', '2026-08-20 14:03:17'),
(404, 3, 55, 1, 'task_created', 'creó la tarea «ghhfg»', '2026-08-20 14:07:14'),
(405, 3, 49, 1, 'task_updated', 'editó la tarea', '2026-08-20 14:09:18'),
(406, 3, 49, 1, 'checklist_created', 'agregó el checklist «Checklist»', '2026-08-20 14:11:01'),
(407, 3, 49, 1, 'checklist_updated', 'agregó un ítem al checklist', '2026-08-20 14:16:11'),
(408, 3, 49, 1, 'checklist_updated', 'agregó un ítem al checklist', '2026-08-20 14:16:12'),
(409, 3, 49, 1, 'checklist_updated', 'agregó un ítem al checklist', '2026-08-20 14:16:14'),
(410, 3, 49, 1, 'comment_created', 'comentó la tarea', '2026-08-20 14:19:42'),
(411, 3, 54, 3, 'checklist_created', 'agregó el checklist «Checklist»', '2026-09-14 22:43:51'),
(412, 3, 54, 3, 'checklist_updated', 'agregó un ítem al checklist', '2026-09-14 22:44:05'),
(413, 3, NULL, 3, 'board_updated', 'actualizó el tablero', '2026-09-14 22:51:46'),
(414, 3, NULL, 3, 'board_updated', 'actualizó el tablero', '2026-09-14 22:52:04'),
(415, 3, NULL, 3, 'board_updated', 'actualizó el tablero', '2026-09-14 22:52:10'),
(418, 3, NULL, 3, 'column_updated', 'editó la columna «dfdfdf»', '2026-09-14 23:57:32'),
(419, 3, NULL, 3, 'column_updated', 'editó la columna «dfdfdf»', '2026-09-14 23:57:54'),
(420, 3, NULL, 3, 'column_updated', 'editó la columna «dfdfdf»', '2026-09-15 00:13:52'),
(421, 3, NULL, 3, 'column_updated', 'editó la columna «dfdfdf»', '2026-09-15 00:14:04'),
(423, 3, 57, 3, 'task_duplicated', 'duplicó la tarea «fdg»', '2026-09-15 00:49:55'),
(424, 3, 54, 3, 'comment_created', 'comentó la tarea', '2026-09-15 00:52:24'),
(425, 3, 54, 3, 'comment_created', 'comentó la tarea', '2026-09-15 00:52:55'),
(426, 3, 50, 3, 'task_updated', 'cambió la prioridad a urgente', '2026-09-15 01:45:48'),
(427, 3, 50, 3, 'checklist_created', 'agregó el checklist «Checklist»', '2026-09-15 01:49:18'),
(430, 3, NULL, 3, 'tasks_purged', 'borró 2 tarea(s) completada(s) hace más de 0 días', '2026-09-17 11:16:21'),
(433, 3, NULL, 3, 'tasks_purged', 'borró 1 tarea(s) completada(s) hace más de 0 días', '2026-09-17 11:16:34'),
(434, 3, 59, 3, 'task_created', 'creó la tarea «task complted»', '2026-09-17 11:16:56'),
(435, 3, 59, 3, 'task_completed', 'marcó la tarea como completada', '2026-09-17 11:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_attachments`
--

CREATE TABLE `kanban_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_image` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kanban_boards`
--

CREATE TABLE `kanban_boards` (
  `id` int(11) NOT NULL,
  `esp32_device_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL DEFAULT 'Tareas de mantenimiento',
  `description` text DEFAULT NULL,
  `background_color` varchar(20) NOT NULL DEFAULT 'azul',
  `task_seq` int(11) NOT NULL DEFAULT 0 COMMENT 'ej:#33 el n° q se muestra p/c/tarea',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_boards`
--

INSERT INTO `kanban_boards` (`id`, `esp32_device_id`, `name`, `description`, `background_color`, `task_seq`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 3, 'Hola', 'primera tabla', 'rojo', 36, 1, '2026-08-05 13:59:34', '2026-09-17 11:16:55'),
(5, 5, 'Tareas de mantenimiento', NULL, 'azul', 0, 1, '2026-08-05 17:32:40', '2026-08-05 17:32:40'),
(6, 6, 'Tareas de mantenimiento', NULL, 'azul', 0, 3, '2026-09-17 11:30:38', '2026-09-17 11:30:38'),
(7, 7, 'Tareas de mantenimiento', NULL, 'azul', 0, 3, '2026-09-17 11:44:27', '2026-09-17 11:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_checklists`
--

CREATE TABLE `kanban_checklists` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT 'Checklist',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_checklists`
--

INSERT INTO `kanban_checklists` (`id`, `task_id`, `title`, `position`, `created_at`) VALUES
(8, 48, 'Checklist', 0, '2026-08-20 11:46:15'),
(9, 47, 'Checklist', 0, '2026-08-20 11:49:55'),
(10, 23, 'Checklist', 0, '2026-08-20 14:03:07'),
(11, 49, 'Checklist', 0, '2026-08-20 14:11:01'),
(12, 54, 'Checklist', 0, '2026-09-14 22:43:51'),
(13, 57, 'Checklist', 0, '2026-09-15 00:49:55'),
(14, 50, 'Checklist', 0, '2026-09-15 01:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_checklist_items`
--

CREATE TABLE `kanban_checklist_items` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_checklist_items`
--

INSERT INTO `kanban_checklist_items` (`id`, `checklist_id`, `content`, `is_completed`, `position`, `created_at`, `updated_at`) VALUES
(22, 10, 'Hola', 1, 0, '2026-08-20 14:03:17', '2026-08-20 14:03:33'),
(23, 11, 'hola', 1, 0, '2026-08-20 14:16:11', '2026-08-20 14:19:27'),
(24, 11, 'como', 1, 1, '2026-08-20 14:16:12', '2026-08-20 14:19:28'),
(25, 11, 'estaas', 1, 2, '2026-08-20 14:16:14', '2026-08-20 14:19:29'),
(26, 12, 'cumplir', 0, 0, '2026-09-14 22:44:05', '2026-09-15 01:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_columns`
--

CREATE TABLE `kanban_columns` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL COMMENT 'ver ajustes en la pag',
  `color` varchar(20) NOT NULL DEFAULT 'gris',
  `position` int(11) NOT NULL DEFAULT 0,
  `status_key` varchar(40) DEFAULT NULL COMMENT 'ver ajustes en la pag',
  `is_final` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ver ajustes en la pag',
  `task_limit` int(11) DEFAULT NULL COMMENT 'ver ajustes en la pag',
  `is_collapsed` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_columns`
--

INSERT INTO `kanban_columns` (`id`, `board_id`, `name`, `description`, `color`, `position`, `status_key`, `is_final`, `task_limit`, `is_collapsed`, `is_archived`, `created_at`, `updated_at`) VALUES
(15, 3, 'dfdfdf', 'Descripcion 1', 'rosa', 2, 'Estado del flujo asociado', 0, 4, 0, 0, '2026-08-05 17:13:29', '2026-09-15 00:14:04'),
(16, 3, 'hola', NULL, 'gris', 1, NULL, 0, NULL, 0, 1, '2026-08-05 17:13:29', '2026-08-20 10:58:47'),
(17, 3, 'hola (tonto)', NULL, 'gris', 3, NULL, 0, NULL, 0, 0, '2026-08-05 17:13:29', '2026-08-20 13:22:01'),
(30, 3, 'Hola', NULL, 'gris', 1, NULL, 0, NULL, 0, 0, '2026-08-06 11:39:08', '2026-09-14 23:09:23'),
(31, 3, 'cgdfgd', NULL, 'gris', 0, NULL, 0, NULL, 0, 0, '2026-08-06 11:55:32', '2026-08-20 13:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_comments`
--

CREATE TABLE `kanban_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_comment_id` int(11) DEFAULT NULL COMMENT 'Un comentario puede ser respuesta a otro',
  `content` text NOT NULL,
  `edited_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_comments`
--

INSERT INTO `kanban_comments` (`id`, `task_id`, `user_id`, `parent_comment_id`, `content`, `edited_at`, `created_at`, `updated_at`) VALUES
(7, 49, 1, NULL, 'hola', NULL, '2026-08-20 14:19:42', '2026-08-20 14:19:42'),
(9, 54, 3, NULL, 'mal echo', '2026-09-15 00:52:48', '2026-09-15 00:52:24', '2026-09-15 00:52:48'),
(10, 54, 3, NULL, '@Ema no es cierto', NULL, '2026-09-15 00:52:55', '2026-09-15 00:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_labels`
--

CREATE TABLE `kanban_labels` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'azul',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_labels`
--

INSERT INTO `kanban_labels` (`id`, `board_id`, `name`, `color`, `created_at`) VALUES
(13, 3, 'el que lo lee', 'verde', '2026-08-06 11:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_links`
--

CREATE TABLE `kanban_links` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kanban_tasks`
--

CREATE TABLE `kanban_tasks` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `parent_task_id` int(11) DEFAULT NULL COMMENT 'Funcionalidad futura: auto-referencia: una tarea puede apuntar a otra tarea de la misma tabla.',
  `code` varchar(20) DEFAULT NULL COMMENT 'ej:#33 el n° q se muestra p/c/tarea',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `priority` enum('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
  `status` varchar(40) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `estimated_minutes` int(11) DEFAULT NULL,
  `logged_minutes` int(11) NOT NULL DEFAULT 0,
  `internal_notes` text DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kanban_tasks`
--

INSERT INTO `kanban_tasks` (`id`, `board_id`, `column_id`, `parent_task_id`, `code`, `title`, `description`, `color`, `priority`, `status`, `position`, `start_date`, `due_date`, `completed_at`, `estimated_minutes`, `logged_minutes`, `internal_notes`, `is_completed`, `is_archived`, `created_by`, `created_at`, `updated_at`) VALUES
(23, 3, 15, NULL, '#5', 'tonto', NULL, 'naranja', 'media', NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-05 17:13:29', '2026-08-20 13:22:02'),
(45, 3, 17, NULL, '#22', 'dfgdfg', NULL, NULL, 'media', NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-06 12:52:06', '2026-08-20 13:09:28'),
(46, 3, 15, NULL, '#23', 'dfgd', NULL, NULL, 'media', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-06 12:52:08', '2026-08-19 18:10:33'),
(47, 3, 31, NULL, '#24', 'fdgdfgdg', NULL, NULL, 'media', NULL, 2, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-06 12:52:11', '2026-09-15 00:49:55'),
(48, 3, 31, NULL, '#25', 'fdg', NULL, NULL, 'media', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-06 12:52:11', '2026-08-19 17:40:03'),
(49, 3, 15, NULL, '#26', 'd', NULL, NULL, 'media', NULL, 2, NULL, NULL, NULL, NULL, 0, 'hjhjhjjhjh', 0, 0, 1, '2026-08-06 12:52:12', '2026-08-20 14:09:18'),
(50, 3, 15, NULL, '#27', 'f', NULL, NULL, 'urgente', NULL, 3, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-06 12:52:12', '2026-09-15 01:45:48'),
(51, 3, 16, NULL, '#28', 'dfgd', NULL, NULL, 'media', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, 1, 1, '2026-08-06 12:52:14', '2026-08-20 10:58:47'),
(52, 3, 17, NULL, '#29', 'hola', NULL, NULL, 'media', NULL, 2, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-20 11:37:24', '2026-08-20 13:09:28'),
(53, 3, 17, NULL, '#30', 'comer', NULL, NULL, 'media', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-20 11:37:27', '2026-08-20 13:09:28'),
(54, 3, 30, NULL, '#31', 'oler el pasto', NULL, NULL, 'baja', NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-20 11:37:31', '2026-08-20 13:22:02'),
(55, 3, 31, NULL, '#32', 'ghhfg', NULL, NULL, 'media', NULL, 3, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 1, '2026-08-20 14:07:14', '2026-09-15 00:49:55'),
(57, 3, 31, NULL, '#34', 'fdg', NULL, NULL, 'media', NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 3, '2026-09-15 00:49:55', '2026-09-15 00:49:55'),
(59, 3, 31, NULL, '#36', 'task complted', NULL, NULL, 'media', NULL, 4, NULL, NULL, '2026-09-17 11:16:58', NULL, 0, NULL, 1, 0, 3, '2026-09-17 11:16:56', '2026-09-17 11:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `kanban_task_assignees`
--

CREATE TABLE `kanban_task_assignees` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kanban_task_labels`
--

CREATE TABLE `kanban_task_labels` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, '2026-04-21-000001', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1784806920, 1),
(2, '2026-05-08-000001', 'App\\Database\\Migrations\\CreatePasswordResetsTable', 'default', 'App', 1784806920, 1),
(3, '2026-05-21-000001', 'App\\Database\\Migrations\\CreateFormAnswersTable', 'default', 'App', 1784806920, 1),
(4, '2026-05-29-000001', 'App\\Database\\Migrations\\CreateIndustriesTable', 'default', 'App', 1784806920, 1),
(5, '2026-05-29-000002', 'App\\Database\\Migrations\\CreateIndustryUsersTable', 'default', 'App', 1784806920, 1),
(6, '2026-05-29-000003', 'App\\Database\\Migrations\\CreateSensorTypesTable', 'default', 'App', 1784806920, 1),
(7, '2026-05-29-000004', 'App\\Database\\Migrations\\CreateEsp32DevicesTable', 'default', 'App', 1784806920, 1),
(8, '2026-05-29-000005', 'App\\Database\\Migrations\\CreateEsp32SensorsTable', 'default', 'App', 1784806920, 1),
(9, '2026-05-29-000006', 'App\\Database\\Migrations\\CreateSensorReadingsTable', 'default', 'App', 1784806920, 1),
(10, '2026-05-29-000007', 'App\\Database\\Migrations\\CreateIndustryMetricsTable', 'default', 'App', 1784806920, 1),
(11, '2026-06-02-000001', 'App\\Database\\Migrations\\AlterEsp32DevicesAddSecurity', 'default', 'App', 1784806920, 1),
(12, '2026-06-02-000002', 'App\\Database\\Migrations\\SeedRpmAnalogSensorType', 'default', 'App', 1784806920, 1),
(13, '2026-06-02-000003', 'App\\Database\\Migrations\\RemoveRpmAnalogSensorType', 'default', 'App', 1784806920, 1),
(14, '2026-06-05-000001', 'App\\Database\\Migrations\\AddTokenSecretToEsp32Devices', 'default', 'App', 1784806920, 1),
(15, '2026-06-09-000001', 'App\\Database\\Migrations\\AddEmailVerificationAndTwoFactor', 'default', 'App', 1784806920, 1),
(16, '2026-06-09-000002', 'App\\Database\\Migrations\\MakeEsp32MacNullable', 'default', 'App', 1784806920, 1),
(17, '2026-07-30-000001', 'App\\Database\\Migrations\\AddPermissionToIndustryUsers', 'default', 'App', 1785409777, 2),
(18, '2026-08-05-000001', 'App\\Database\\Migrations\\CreateKanbanTables', 'default', 'App', 1785935186, 3),
(19, '2026-08-21-000001', 'App\\Database\\Migrations\\CreateStockTables', 'default', 'App', 1787318899, 4),
(20, '2026-08-21-000002', 'App\\Database\\Migrations\\AddPhoneVerificationToUsers', 'default', 'App', 1787328740, 5),
(21, '2026-09-08-000001', 'App\\Database\\Migrations\\CreateSpaceInvitationsTable', 'default', 'App', 1789423080, 6),
(22, '2026-09-10-000001', 'App\\Database\\Migrations\\CreateSpaceActionTokensTable', 'default', 'App', 1789423080, 6),
(23, '2026-09-10-000002', 'App\\Database\\Migrations\\CreateEsp32DeviceAccessTable', 'default', 'App', 1789423080, 6),
(24, '2026-09-15-000001', 'App\\Database\\Migrations\\CreateSubscriptionsTable', 'default', 'App', 1789735343, 7),
(25, '2026-09-16-000001', 'App\\Database\\Migrations\\CreateNotificationsTable', 'default', 'App', 1789735343, 7),
(26, '2026-09-17-000001', 'App\\Database\\Migrations\\CreateSpaceEventsTable', 'default', 'App', 1789735343, 7),
(27, '2026-09-17-000002', 'App\\Database\\Migrations\\AddCalendarAssigneesTagsAndReminders', 'default', 'App', 1789735343, 7);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Destinatario: una fila por miembro del espacio',
  `level` tinyint(1) NOT NULL COMMENT '1 = agotado, 2 = bajo el minimo, 3 = aviso propio',
  `title` varchar(160) NOT NULL,
  `text` varchar(500) NOT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'stock' COMMENT 'de donde viene ej: stok, sensores, mantenimiento etc',
  `source_key` varchar(120) NOT NULL COMMENT 'identifica el origen p/no repetirla ej:fila 8 de stock está bajo el mínimo 2 veces, solo avisa 1',
  `link` varchar(255) DEFAULT NULL COMMENT 'funcionalidad futura: campo q guarda link p/redirigir al problema',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `industry_id`, `user_id`, `level`, `title`, `text`, `source`, `source_key`, `link`, `read_at`, `created_at`) VALUES
(1, 2, 3, 3, 'Reponer pronto', 'boludo quedó en 4 unidades, en o por debajo de 4 en San Jose SRL.', 'stock', 'stock:1', 'espacios/2/stock', '2026-09-17 11:19:43', '2026-09-14 23:27:30'),
(2, 2, 1, 3, 'Reponer pronto', 'boludo quedó en 4 unidades, en o por debajo de 4 en San Jose SRL.', 'stock', 'stock:1', 'espacios/2/stock', '2026-09-17 11:22:08', '2026-09-14 23:27:30'),
(3, 2, 3, 3, 'Reponer pronto', 'Ítem sin nombre quedó en 2 unidades, en o por debajo de 2 en San Jose SRL.', 'stock', 'stock:2', 'espacios/2/stock', '2026-09-17 11:19:43', '2026-09-14 23:31:32'),
(4, 2, 1, 3, 'Reponer pronto', 'Ítem sin nombre quedó en 2 unidades, en o por debajo de 2 en San Jose SRL.', 'stock', 'stock:2', 'espacios/2/stock', '2026-09-17 11:22:08', '2026-09-14 23:31:32');

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
(4, 9, 0.000, '2026-07-31 15:52:36', '2026-07-31 15:52:36'),
(5, 7, 56.000, '2026-07-31 15:52:36', '2026-07-31 15:52:36'),
(6, 8, 23.060, '2026-07-31 15:52:36', '2026-07-31 15:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_types`
--

CREATE TABLE `sensor_types` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL COMMENT 'Tipo de Sensor',
  `name` varchar(80) NOT NULL COMMENT 'Nombre q aparece en grafico',
  `unit` varchar(20) DEFAULT NULL,
  `default_chart` varchar(20) NOT NULL DEFAULT 'line' COMMENT 'Grafico q Recomienda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_types`
--

INSERT INTO `sensor_types` (`id`, `code`, `name`, `unit`, `default_chart`, `created_at`) VALUES
(1, 'temperature', 'Temperatura', '°C', 'line', '2026-07-23 11:42:00'),
(2, 'humidity', 'Humedad', '%', 'area', '2026-07-23 11:42:00'),
(3, 'vibration', 'Vibración', 'mm/s', 'line', '2026-07-23 11:42:00'),
(4, 'rpm', 'RPM grafico', 'rev/min', 'gauge', '2026-07-23 11:42:00'),
(5, 'current', 'Corriente', 'A', 'bars', '2026-07-23 11:42:00'),
(6, 'on_off', 'Estado ON/OFF', NULL, 'timeline', '2026-07-23 11:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `space_action_tokens`
--

CREATE TABLE `space_action_tokens` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` varchar(40) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `space_events`
--

CREATE TABLE `space_events` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'blue',
  `category` varchar(60) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `notify` varchar(20) DEFAULT 'none',
  `notify_at` datetime DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `space_event_assignees`
--

CREATE TABLE `space_event_assignees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `space_event_tags`
--

CREATE TABLE `space_event_tags` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `name` varchar(40) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `space_invitations`
--

CREATE TABLE `space_invitations` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `email` varchar(254) NOT NULL,
  `role` enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `permission` enum('read','edit') NOT NULL DEFAULT 'read',
  `token_hash` varchar(64) NOT NULL,
  `invited_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `accepted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_cells`
--

CREATE TABLE `stock_cells` (
  `id` int(11) NOT NULL,
  `row_id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `value_text` text DEFAULT NULL,
  `value_number` decimal(14,3) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_cells`
--

INSERT INTO `stock_cells` (`id`, `row_id`, `column_id`, `value_text`, `value_number`, `updated_at`) VALUES
(5, 1, 1, 'Chata', NULL, '2026-09-17 16:37:45'),
(6, 8, 13, 'Primer año', NULL, '2026-08-21 14:38:44'),
(7, 8, 14, 'Primer año', NULL, '2026-08-21 14:38:44'),
(8, 8, 15, 'Primer año', NULL, '2026-08-21 14:38:44'),
(9, 10, 13, 'Introducción a la Economía', NULL, '2026-08-21 14:38:44'),
(10, 10, 14, 'Análisis matemático i', NULL, '2026-08-21 14:38:44'),
(11, 10, 15, 'Análisis matemático i', NULL, '2026-08-21 14:38:44'),
(12, 11, 13, 'Análisis matemático I', NULL, '2026-08-21 14:38:44'),
(13, 11, 14, 'Introducción a la contabilidad', NULL, '2026-08-21 14:38:44'),
(14, 11, 15, 'Introducción a la contabilidad', NULL, '2026-08-21 14:38:44'),
(15, 12, 13, 'Introducción a la Contabilidad', NULL, '2026-08-21 14:38:44'),
(16, 12, 14, 'Metodología de la investigación', NULL, '2026-08-21 14:38:44'),
(17, 12, 15, 'Metodología de la investigación', NULL, '2026-08-21 14:38:44'),
(18, 13, 13, 'Tecnologías de la Información I', NULL, '2026-08-21 14:38:44'),
(19, 13, 14, 'Tecnologías de la información i', NULL, '2026-08-21 14:38:44'),
(20, 13, 15, 'Tecnologías de la información i', NULL, '2026-08-21 14:38:44'),
(21, 14, 13, 'Introducción a la Administración', NULL, '2026-08-21 14:38:44'),
(22, 14, 14, 'Introducción a la administración', NULL, '2026-08-21 14:38:44'),
(23, 14, 15, 'Introducción a la administración', NULL, '2026-08-21 14:38:44'),
(24, 15, 13, 'Psicología y sociología de las organizaciones', NULL, '2026-08-21 14:38:44'),
(25, 15, 14, 'Comercialización y marketing i', NULL, '2026-08-21 14:38:44'),
(26, 15, 15, 'Comercialización y marketing i', NULL, '2026-08-21 14:38:44'),
(27, 16, 13, 'Introducción a los negocios digitales', NULL, '2026-08-21 14:38:44'),
(28, 16, 14, 'Introducción a las finanzas', NULL, '2026-08-21 14:38:44'),
(29, 16, 15, 'Introducción a las finanzas', NULL, '2026-08-21 14:38:44'),
(30, 17, 13, 'Análisis matemático II', NULL, '2026-08-21 14:38:44'),
(31, 17, 14, 'Introducción a la economía', NULL, '2026-08-21 14:38:44'),
(32, 17, 15, 'Introducción a la economía', NULL, '2026-08-21 14:38:44'),
(33, 18, 13, 'Álgebra', NULL, '2026-08-21 14:38:44'),
(34, 18, 14, 'Análisis matemático ii', NULL, '2026-08-21 14:38:44'),
(35, 18, 15, 'Análisis matemático ii', NULL, '2026-08-21 14:38:44'),
(36, 19, 13, 'Tecnologías de la información II', NULL, '2026-08-21 14:38:44'),
(37, 19, 14, 'Álgebra', NULL, '2026-08-21 14:38:44'),
(38, 19, 15, 'Álgebra', NULL, '2026-08-21 14:38:44'),
(39, 20, 13, 'Derecho empresarial I', NULL, '2026-08-21 14:38:44'),
(40, 20, 14, 'Tecnologías de la información ii', NULL, '2026-08-21 14:38:44'),
(41, 20, 15, 'Tecnologías de la información ii', NULL, '2026-08-21 14:38:44'),
(42, 21, 13, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(43, 21, 14, 'Derecho empresarial i', NULL, '2026-08-21 14:38:44'),
(44, 21, 15, 'Derecho empresarial i', NULL, '2026-08-21 14:38:44'),
(45, 22, 13, 'Pensamiento crítico filosófico', NULL, '2026-08-21 14:38:44'),
(46, 22, 14, 'Seminario de integración y vinculación i', NULL, '2026-08-21 14:38:44'),
(47, 22, 15, 'Seminario de integración y vinculación i', NULL, '2026-08-21 14:38:44'),
(48, 23, 14, 'Pensamiento crítico filosófico', NULL, '2026-08-21 14:38:44'),
(49, 23, 15, 'Pensamiento crítico filosófico', NULL, '2026-08-21 14:38:44'),
(50, 25, 13, 'Segundo año', NULL, '2026-08-21 14:38:44'),
(51, 25, 14, 'Segundo año', NULL, '2026-08-21 14:38:44'),
(52, 25, 15, 'Segundo año', NULL, '2026-08-21 14:38:44'),
(53, 27, 13, 'Comercio electrónico y marketplace', NULL, '2026-08-21 14:38:44'),
(54, 27, 14, 'Estadística i', NULL, '2026-08-21 14:38:44'),
(55, 27, 15, 'Teoría de las inversiones', NULL, '2026-08-21 14:38:44'),
(56, 28, 13, 'Herramientas de la Industria 4.0', NULL, '2026-08-21 14:38:44'),
(57, 28, 14, 'Macroeconomía', NULL, '2026-08-21 14:38:44'),
(58, 28, 15, 'Historia económica de Argentina y el mundo', NULL, '2026-08-21 14:38:44'),
(59, 29, 13, 'Estadística I', NULL, '2026-08-21 14:38:44'),
(60, 29, 14, 'Contabilidad de gestión i', NULL, '2026-08-21 14:38:44'),
(61, 29, 15, 'Estadística i', NULL, '2026-08-21 14:38:44'),
(62, 30, 13, 'Comercialización y Marketing I', NULL, '2026-08-21 14:38:44'),
(63, 30, 14, 'Administración de recursos humanos i', NULL, '2026-08-21 14:38:44'),
(64, 30, 15, 'Macroeconomía i', NULL, '2026-08-21 14:38:44'),
(65, 31, 13, 'Contabilidad de gestión I', NULL, '2026-08-21 14:38:44'),
(66, 31, 14, 'Derecho empresarial ii - (derecho del trabajo y la seguridad social)', NULL, '2026-08-21 14:38:44'),
(67, 31, 15, 'Contabilidad de gestión i', NULL, '2026-08-21 14:38:44'),
(68, 32, 13, 'Administración de RRHH I', NULL, '2026-08-21 14:38:44'),
(69, 32, 14, 'Taller de negociación', NULL, '2026-08-21 14:38:44'),
(70, 32, 15, 'Econometría', NULL, '2026-08-21 14:38:44'),
(71, 33, 13, 'Electiva', NULL, '2026-08-21 14:38:44'),
(72, 33, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(73, 33, 15, 'Materia electiva i', NULL, '2026-08-21 14:38:44'),
(74, 34, 13, 'Tecnología y herramientas digitales', NULL, '2026-08-21 14:38:44'),
(75, 34, 14, 'Estadística ii', NULL, '2026-08-21 14:38:44'),
(76, 34, 15, 'Estadística ii', NULL, '2026-08-21 14:38:44'),
(77, 35, 13, 'Métodos Cuantitativos para la toma de decisiones', NULL, '2026-08-21 14:38:44'),
(78, 35, 14, 'Microeconomía', NULL, '2026-08-21 14:38:44'),
(79, 35, 15, 'Microeconomía i', NULL, '2026-08-21 14:38:44'),
(80, 36, 13, 'Estadística II', NULL, '2026-08-21 14:38:44'),
(81, 36, 14, 'Comercialización y marketing ii (mkt digital)', NULL, '2026-08-21 14:38:44'),
(82, 36, 15, 'Economía del consumo', NULL, '2026-08-21 14:38:44'),
(83, 37, 13, 'Comercialización y Marketing II', NULL, '2026-08-21 14:38:44'),
(84, 37, 14, 'Dirección y estrategia de empresas i', NULL, '2026-08-21 14:38:44'),
(85, 37, 15, 'Seminario de integración y vinculación ii', NULL, '2026-08-21 14:38:44'),
(86, 38, 13, 'Dirección y estrategia de empresas I', NULL, '2026-08-21 14:38:44'),
(87, 38, 14, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(88, 38, 15, 'Problemáticas antropológicas contemporáneas', NULL, '2026-08-21 14:38:44'),
(89, 39, 13, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(90, 39, 14, 'Problemas antropológicos contemporáneos', NULL, '2026-08-21 14:38:44'),
(91, 39, 15, 'Materia electiva ii', NULL, '2026-08-21 14:38:44'),
(92, 40, 13, 'Problemas antropológicos contemporáneos', NULL, '2026-08-21 14:38:44'),
(93, 40, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(94, 42, 13, 'Tercer año', NULL, '2026-08-21 14:38:44'),
(95, 42, 14, 'Tercer año', NULL, '2026-08-21 14:38:44'),
(96, 42, 15, 'Tercer año', NULL, '2026-08-21 14:38:44'),
(97, 44, 13, 'Inteligencia Artificial en modelos de negocios', NULL, '2026-08-21 14:38:44'),
(98, 44, 14, 'Matemática financiera', NULL, '2026-08-21 14:38:44'),
(99, 44, 15, 'Dirección y estrategia de empresas i', NULL, '2026-08-21 14:38:44'),
(100, 45, 13, 'Matemática financiera', NULL, '2026-08-21 14:38:44'),
(101, 45, 14, 'Contabilidad de gestión ii - (presupuestos y control presupuestario)', NULL, '2026-08-21 14:38:44'),
(102, 45, 15, 'Matemática financiera', NULL, '2026-08-21 14:38:44'),
(103, 46, 13, 'Impuestos I', NULL, '2026-08-21 14:38:44'),
(104, 46, 14, 'Derecho empresarial iii - (sociedades comerciales)', NULL, '2026-08-21 14:38:44'),
(105, 46, 15, 'Teoría de juegos y decisiones', NULL, '2026-08-21 14:38:44'),
(106, 47, 13, 'Contabilidad de gestión II', NULL, '2026-08-21 14:38:44'),
(107, 47, 14, 'Impuestos i', NULL, '2026-08-21 14:38:44'),
(108, 47, 15, 'Impuestos i', NULL, '2026-08-21 14:38:44'),
(109, 48, 13, 'Administración de operaciones', NULL, '2026-08-21 14:38:44'),
(110, 48, 14, 'Dirección y estrategia de empresas ii', NULL, '2026-08-21 14:38:44'),
(111, 48, 15, 'Mesoeconomía y desarrollo regional', NULL, '2026-08-21 14:38:44'),
(112, 49, 13, 'Seminario de fe, vida y contextos culturales', NULL, '2026-08-21 14:38:44'),
(113, 49, 14, 'Seminario de fe, vida y contextos culturales', NULL, '2026-08-21 14:38:44'),
(114, 49, 15, 'Seminario de fe, vida y contextos culturales', NULL, '2026-08-21 14:38:44'),
(115, 50, 13, 'Electiva', NULL, '2026-08-21 14:38:44'),
(116, 50, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(117, 50, 15, 'Materia electiva iii', NULL, '2026-08-21 14:38:44'),
(118, 51, 13, 'Ciberseguridad y Protección de Datos', NULL, '2026-08-21 14:38:44'),
(119, 51, 14, 'Administración económica financiera i', NULL, '2026-08-21 14:38:44'),
(120, 51, 15, 'Economía del comportamiento', NULL, '2026-08-21 14:38:44'),
(121, 52, 13, 'Administración económica financiera I', NULL, '2026-08-21 14:38:44'),
(122, 52, 14, 'Derecho empresarial iv - (concursos y quiebras)', NULL, '2026-08-21 14:38:44'),
(123, 52, 15, 'Econometría ii', NULL, '2026-08-21 14:38:44'),
(124, 53, 13, 'ERP - Criterios de selección', NULL, '2026-08-21 14:38:44'),
(125, 53, 14, 'Administración de operaciones', NULL, '2026-08-21 14:38:44'),
(126, 53, 15, 'Investigación de mercados en base digital', NULL, '2026-08-21 14:38:44'),
(127, 54, 13, 'Investigación de mercados en base digital', NULL, '2026-08-21 14:38:44'),
(128, 54, 14, 'Investigación de mercados en base digital', NULL, '2026-08-21 14:38:44'),
(129, 54, 15, 'Mercado de capitales', NULL, '2026-08-21 14:38:44'),
(130, 55, 13, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(131, 55, 14, 'Erp - criterios de selección', NULL, '2026-08-21 14:38:44'),
(132, 55, 15, 'Seminario de integración y vinculación iii', NULL, '2026-08-21 14:38:44'),
(133, 56, 13, 'Seminario de fe, vida y contextos sociales', NULL, '2026-08-21 14:38:44'),
(134, 56, 14, 'Seminario de integración y vinculación - (simulador de negocios)', NULL, '2026-08-21 14:38:44'),
(135, 56, 15, 'Seminario de fe, vida y contextos sociales', NULL, '2026-08-21 14:38:44'),
(136, 57, 13, 'Electiva', NULL, '2026-08-21 14:38:44'),
(137, 57, 14, 'Seminario de fe, vida y contextos sociales', NULL, '2026-08-21 14:38:44'),
(138, 57, 15, 'Materia electiva iv', NULL, '2026-08-21 14:38:44'),
(139, 58, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(140, 58, 15, 'Administración económica financiera ii', NULL, '2026-08-21 14:38:44'),
(141, 60, 13, 'Cuarto año', NULL, '2026-08-21 14:38:44'),
(142, 60, 14, 'Cuarto año', NULL, '2026-08-21 14:38:44'),
(143, 60, 15, 'Cuarto año', NULL, '2026-08-21 14:38:44'),
(144, 62, 13, 'Gestión Financiera en Negocios Digitales', NULL, '2026-08-21 14:38:44'),
(145, 62, 14, 'Administración económica financiera ii', NULL, '2026-08-21 14:38:44'),
(146, 62, 15, 'Clusterización y competitividad', NULL, '2026-08-21 14:38:44'),
(147, 63, 13, 'Gerenciamiento de Proyectos Digitales', NULL, '2026-08-21 14:38:44'),
(148, 63, 14, 'Impuestos ii', NULL, '2026-08-21 14:38:44'),
(149, 63, 15, 'Finanzas públicas', NULL, '2026-08-21 14:38:44'),
(150, 64, 13, 'Administración económica financiera II', NULL, '2026-08-21 14:38:44'),
(151, 64, 14, 'Historia y política económica', NULL, '2026-08-21 14:38:44'),
(152, 64, 15, 'Política monetaria y sistema financiero', NULL, '2026-08-21 14:38:44'),
(153, 65, 13, 'Administración de cadena de suministros', NULL, '2026-08-21 14:38:44'),
(154, 65, 14, 'Administración de recursos humanos ii', NULL, '2026-08-21 14:38:44'),
(155, 65, 15, 'Desafíos éticos contemporáneos', NULL, '2026-08-21 14:38:44'),
(156, 66, 13, 'Impuestos II', NULL, '2026-08-21 14:38:44'),
(157, 66, 14, 'Administración de cadena de suministros', NULL, '2026-08-21 14:38:44'),
(158, 66, 15, 'Materia electiva v', NULL, '2026-08-21 14:38:44'),
(159, 67, 13, 'Desafíos éticos contemporáneos', NULL, '2026-08-21 14:38:44'),
(160, 67, 14, 'Comercio internacional', NULL, '2026-08-21 14:38:44'),
(161, 67, 15, 'Finanzas corporativas internacionales', NULL, '2026-08-21 14:38:44'),
(162, 68, 13, 'Electiva', NULL, '2026-08-21 14:38:44'),
(163, 68, 14, 'Ética profesional', NULL, '2026-08-21 14:38:44'),
(164, 68, 15, 'Economía internacional', NULL, '2026-08-21 14:38:44'),
(165, 69, 13, 'Emprendedurismo Digital', NULL, '2026-08-21 14:38:44'),
(166, 69, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(167, 69, 15, 'Economía ambiental y desarrollo sostenible', NULL, '2026-08-21 14:38:44'),
(168, 70, 13, 'Legislación en Negocios Digitales', NULL, '2026-08-21 14:38:44'),
(169, 70, 14, 'Finanzas corporativas', NULL, '2026-08-21 14:38:44'),
(170, 70, 15, 'Seminario de integración y vinculación iv', NULL, '2026-08-21 14:38:44'),
(171, 71, 13, 'Comercialización y marketing II', NULL, '2026-08-21 14:38:44'),
(172, 71, 14, 'Logística', NULL, '2026-08-21 14:38:44'),
(173, 71, 15, 'Derecho bancario y bursátil', NULL, '2026-08-21 14:38:44'),
(174, 72, 13, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(175, 72, 14, 'Comercialización y marketing iii (mkt estratégico)', NULL, '2026-08-21 14:38:44'),
(176, 72, 15, 'Nuevas economías e innovación', NULL, '2026-08-21 14:38:44'),
(177, 73, 13, 'Prácticas profesionales', NULL, '2026-08-21 14:38:44'),
(178, 73, 14, 'Seminario de integración y vinculación', NULL, '2026-08-21 14:38:44'),
(179, 73, 15, 'Prácticas preprofesionales', NULL, '2026-08-21 14:38:44'),
(180, 74, 13, 'Electiva', NULL, '2026-08-21 14:38:44'),
(181, 74, 14, '(Evaluación de proyectos de inversión + plan de negocios)', NULL, '2026-08-21 14:38:44'),
(182, 74, 15, 'Pricing', NULL, '2026-08-21 14:38:44'),
(183, 75, 14, 'Liderazgo y toma de decisiones', NULL, '2026-08-21 14:38:44'),
(184, 75, 15, 'Materia electiva vi', NULL, '2026-08-21 14:38:44'),
(185, 76, 14, 'Prácticas profesionales', NULL, '2026-08-21 14:38:44'),
(186, 77, 14, 'Electiva', NULL, '2026-08-21 14:38:44'),
(191, 86, 1, 'Camion', NULL, '2026-09-17 16:37:52');

-- --------------------------------------------------------

--
-- Table structure for table `stock_columns`
--

CREATE TABLE `stock_columns` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'texto',
  `unit` varchar(40) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_item_column` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si es la columna principal no se puede elimnar',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_columns`
--

INSERT INTO `stock_columns` (`id`, `table_id`, `name`, `type`, `unit`, `settings`, `is_item_column`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 'Item', 'texto', NULL, NULL, 1, 0, '2026-08-21 13:35:31', '2026-08-21 13:35:31'),
(10, 4, 'Item', 'texto', NULL, NULL, 1, 0, '2026-08-21 14:38:26', '2026-08-21 14:38:26'),
(11, 4, 'Cantidad', 'contador', 'unidades', NULL, 0, 1, '2026-08-21 14:38:26', '2026-08-21 14:38:26'),
(12, 5, 'Columna 1', 'texto', NULL, NULL, 1, 0, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(13, 5, 'ADMIN. NEGOCIOS DIGITALES', 'texto', NULL, NULL, 0, 1, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(14, 5, 'ADMINISTRACION DE EMPRESAS', 'texto', NULL, NULL, 0, 2, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(15, 5, 'LIC. EN ECONOMÍA Y NEGOCIOS', 'texto', NULL, NULL, 0, 3, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(16, 1, 'Columna 2', 'aviso', NULL, '{\"label\":\"Reponer pronto\",\"comparator\":\"lte\",\"zero\":true,\"minimo\":true}', 0, 1, '2026-09-14 23:53:15', '2026-09-15 00:28:39'),
(17, 6, 'Item', 'texto', NULL, NULL, 1, 0, '2026-09-14 23:53:21', '2026-09-14 23:53:21'),
(18, 6, 'Cantidad', 'contador', 'unidades', NULL, 0, 1, '2026-09-14 23:53:21', '2026-09-14 23:53:21'),
(19, 6, 'Mínimo tolerable', 'minimo', 'unidades', NULL, 0, 2, '2026-09-14 23:53:21', '2026-09-14 23:53:21'),
(20, 7, 'Item', 'texto', NULL, NULL, 1, 0, '2026-09-17 11:30:26', '2026-09-17 11:30:26'),
(21, 7, 'Cantidad', 'contador', 'unidades', NULL, 0, 1, '2026-09-17 11:30:26', '2026-09-17 11:30:26'),
(22, 7, 'Mínimo tolerable', 'minimo', 'unidades', NULL, 0, 2, '2026-09-17 11:30:26', '2026-09-17 11:30:26'),
(23, 1, 'Columna 3', 'texto', NULL, NULL, 0, 2, '2026-09-17 16:36:51', '2026-09-17 16:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `stock_rows`
--

CREATE TABLE `stock_rows` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_rows`
--

INSERT INTO `stock_rows` (`id`, `table_id`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2026-08-21 13:35:31', '2026-08-21 13:35:31'),
(2, 1, 1, '2026-08-21 13:35:38', '2026-08-21 13:35:38'),
(6, 4, 0, '2026-08-21 14:38:26', '2026-08-21 14:38:26'),
(7, 5, 0, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(8, 5, 1, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(9, 5, 2, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(10, 5, 3, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(11, 5, 4, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(12, 5, 5, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(13, 5, 6, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(14, 5, 7, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(15, 5, 8, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(16, 5, 9, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(17, 5, 10, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(18, 5, 11, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(19, 5, 12, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(20, 5, 13, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(21, 5, 14, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(22, 5, 15, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(23, 5, 16, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(24, 5, 17, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(25, 5, 18, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(26, 5, 19, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(27, 5, 20, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(28, 5, 21, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(29, 5, 22, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(30, 5, 23, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(31, 5, 24, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(32, 5, 25, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(33, 5, 26, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(34, 5, 27, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(35, 5, 28, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(36, 5, 29, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(37, 5, 30, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(38, 5, 31, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(39, 5, 32, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(40, 5, 33, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(41, 5, 34, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(42, 5, 35, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(43, 5, 36, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(44, 5, 37, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(45, 5, 38, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(46, 5, 39, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(47, 5, 40, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(48, 5, 41, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(49, 5, 42, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(50, 5, 43, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(51, 5, 44, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(52, 5, 45, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(53, 5, 46, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(54, 5, 47, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(55, 5, 48, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(56, 5, 49, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(57, 5, 50, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(58, 5, 51, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(59, 5, 52, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(60, 5, 53, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(61, 5, 54, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(62, 5, 55, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(63, 5, 56, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(64, 5, 57, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(65, 5, 58, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(66, 5, 59, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(67, 5, 60, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(68, 5, 61, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(69, 5, 62, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(70, 5, 63, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(71, 5, 64, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(72, 5, 65, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(73, 5, 66, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(74, 5, 67, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(75, 5, 68, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(76, 5, 69, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(77, 5, 70, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(78, 5, 71, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(79, 5, 72, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(80, 5, 73, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(81, 5, 74, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(82, 6, 0, '2026-09-14 23:53:21', '2026-09-14 23:53:21'),
(83, 7, 0, '2026-09-17 11:30:26', '2026-09-17 11:30:26'),
(84, 1, 2, '2026-09-17 16:37:45', '2026-09-17 16:37:45'),
(85, 1, 3, '2026-09-17 16:37:46', '2026-09-17 16:37:46'),
(86, 1, 4, '2026-09-17 16:37:47', '2026-09-17 16:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `stock_tables`
--

CREATE TABLE `stock_tables` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT 'Tabla de stock',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_tables`
--

INSERT INTO `stock_tables` (`id`, `industry_id`, `title`, `position`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'Activos', 0, 1, '2026-08-21 13:35:31', '2026-09-17 16:37:42'),
(4, 4, 'Tacho ITR3', 0, 1, '2026-08-21 14:38:26', '2026-08-21 14:38:26'),
(5, 4, 'Comp-MateriasUCC', 1, 1, '2026-08-21 14:38:44', '2026-08-21 14:38:44'),
(6, 2, 'Tabla Interactiva 2', 1, 3, '2026-09-14 23:53:21', '2026-09-14 23:53:21'),
(7, 5, 'YPF', 0, 3, '2026-09-17 11:30:26', '2026-09-17 11:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `external_reference` varchar(64) NOT NULL,
  `mp_preapproval_id` varchar(64) DEFAULT NULL,
  `mp_plan_id` varchar(64) NOT NULL,
  `payer_email` varchar(254) NOT NULL,
  `payer_name` varchar(150) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'creating',
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL COMMENT 'fecha de verificacnion e-mail',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'si es q esta activado',
  `phone_verification_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si es q está activado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nombre`, `apellido`, `email`, `password_hash`, `foto_perfil`, `created_at`, `email_verified_at`, `two_factor_enabled`, `phone_verification_enabled`) VALUES
(1, 'Jose', 'Mangold', 'josemangold08@gmail.com', '$2y$10$Smhm8zviXo1XBxeTPGTlB.u921tgThjP6YonSEZ38wfqv/8gp9h/i', '1787321208_f833e5eb08baa9dc01cd.png', '2026-07-23 11:44:14', '2026-07-23 11:44:35', 0, 0),
(3, 'Ema', 'risso', 'emarissopatron@gmail.com', '$2y$10$.q3DNPk1qU3h4F1GG167n.xvyBTN/pqJaBvnGRevvCBOsAcVcrduS', NULL, '2026-07-31 16:05:49', '2026-07-31 16:06:09', 0, 0),
(5, 'Ema', 'Risso', 'emanuelrisopatron@gmail.com', '$2y$10$VRdc5wbV6Zb32x7YXFZiLeZp074V2SVF0J3dARXPEKZVESlsZkkUC', NULL, '2026-08-04 15:56:11', NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` varchar(40) NOT NULL COMMENT 'Tipo: email_verification, two_factor_login etc',
  `code_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Intentos fallidos',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_codes`
--

INSERT INTO `verification_codes` (`id`, `user_id`, `purpose`, `code_hash`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'email_verification', '$2y$10$9lPtFt.MuZZtyv7ldK4P/.dXFvaF5SKQwEnn.Vmkf15UJ4O06mzQ2', 0, '2026-07-23 11:49:14', '2026-07-23 11:44:35', '2026-07-23 11:44:14'),
(3, 1, 'two_factor_login', '$2y$10$zlzDrQ/IESAoX.vZYZfLP.sSppPtFRGf1NrQQyk9YSKnyzjnK0rJK', 0, '2026-08-21 12:33:48', '2026-08-21 12:29:37', '2026-08-21 12:28:48'),
(4, 3, 'email_verification', '$2y$10$LBTqYv806Y72kshofbSyPuR5GRRxygRimOYnT3HmmpYTeOUEX4Dki', 0, '2026-07-31 16:10:49', '2026-07-31 16:06:09', '2026-07-31 16:05:49');

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
-- Indexes for table `esp32_device_access`
--
ALTER TABLE `esp32_device_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_device_access_member_device` (`industry_user_id`,`esp32_device_id`),
  ADD KEY `esp32_device_access_esp32_device_id_foreign` (`esp32_device_id`);

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
-- Indexes for table `kanban_activity_log`
--
ALTER TABLE `kanban_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `board_id_created_at` (`board_id`,`created_at`),
  ADD KEY `fk_kanban_activity_task` (`task_id`),
  ADD KEY `fk_kanban_activity_user` (`user_id`);

--
-- Indexes for table `kanban_attachments`
--
ALTER TABLE `kanban_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_attachments_task` (`task_id`),
  ADD KEY `fk_kanban_attachments_user` (`user_id`);

--
-- Indexes for table `kanban_boards`
--
ALTER TABLE `kanban_boards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esp32_device_id` (`esp32_device_id`),
  ADD KEY `fk_kanban_boards_creator` (`created_by`);

--
-- Indexes for table `kanban_checklists`
--
ALTER TABLE `kanban_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id_position` (`task_id`,`position`);

--
-- Indexes for table `kanban_checklist_items`
--
ALTER TABLE `kanban_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id_position` (`checklist_id`,`position`);

--
-- Indexes for table `kanban_columns`
--
ALTER TABLE `kanban_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `board_id_is_archived_position` (`board_id`,`is_archived`,`position`);

--
-- Indexes for table `kanban_comments`
--
ALTER TABLE `kanban_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id_created_at` (`task_id`,`created_at`),
  ADD KEY `fk_kanban_comments_user` (`user_id`),
  ADD KEY `fk_kanban_comments_parent` (`parent_comment_id`);

--
-- Indexes for table `kanban_labels`
--
ALTER TABLE `kanban_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `board_id_name` (`board_id`,`name`);

--
-- Indexes for table `kanban_links`
--
ALTER TABLE `kanban_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_links_task` (`task_id`);

--
-- Indexes for table `kanban_tasks`
--
ALTER TABLE `kanban_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `column_id_is_archived_position` (`column_id`,`is_archived`,`position`),
  ADD KEY `board_id_is_completed` (`board_id`,`is_completed`),
  ADD KEY `fk_kanban_tasks_parent` (`parent_task_id`),
  ADD KEY `fk_kanban_tasks_creator` (`created_by`);

--
-- Indexes for table `kanban_task_assignees`
--
ALTER TABLE `kanban_task_assignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id_user_id` (`task_id`,`user_id`),
  ADD KEY `fk_kanban_assignees_user` (`user_id`);

--
-- Indexes for table `kanban_task_labels`
--
ALTER TABLE `kanban_task_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id_label_id` (`task_id`,`label_id`),
  ADD KEY `fk_kanban_task_labels_label` (`label_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_bandeja` (`user_id`,`id`),
  ADD KEY `idx_notifications_sin_leer` (`user_id`,`read_at`),
  ADD KEY `idx_notifications_origen` (`source_key`,`user_id`,`id`),
  ADD KEY `idx_notifications_industry` (`industry_id`);

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
-- Indexes for table `space_action_tokens`
--
ALTER TABLE `space_action_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `industry_id_purpose` (`industry_id`,`purpose`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `space_events`
--
ALTER TABLE `space_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_space_events_agenda` (`industry_id`,`start_at`),
  ADD KEY `fk_space_events_creator` (`created_by`),
  ADD KEY `idx_space_events_recordatorio` (`notified_at`,`notify_at`);

--
-- Indexes for table `space_event_assignees`
--
ALTER TABLE `space_event_assignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_space_event_assignees` (`event_id`,`user_id`),
  ADD KEY `idx_space_event_assignees_user` (`user_id`);

--
-- Indexes for table `space_event_tags`
--
ALTER TABLE `space_event_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_space_event_tags` (`industry_id`,`name`),
  ADD KEY `fk_space_event_tags_creator` (`created_by`);

--
-- Indexes for table `space_invitations`
--
ALTER TABLE `space_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `industry_id_email` (`industry_id`,`email`);

--
-- Indexes for table `stock_cells`
--
ALTER TABLE `stock_cells`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stock_cells_celda` (`row_id`,`column_id`),
  ADD KEY `fk_stock_cells_column` (`column_id`);

--
-- Indexes for table `stock_columns`
--
ALTER TABLE `stock_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_columns_orden` (`table_id`,`position`);

--
-- Indexes for table `stock_rows`
--
ALTER TABLE `stock_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_rows_orden` (`table_id`,`position`);

--
-- Indexes for table `stock_tables`
--
ALTER TABLE `stock_tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_tables_orden` (`industry_id`,`position`),
  ADD KEY `fk_stock_tables_creator` (`created_by`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_reference` (`external_reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mp_preapproval_id` (`mp_preapproval_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_verification_user_purpose` (`user_id`,`purpose`),
  ADD KEY `expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `esp32_devices`
--
ALTER TABLE `esp32_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `esp32_device_access`
--
ALTER TABLE `esp32_device_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `industry_users`
--
ALTER TABLE `industry_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kanban_activity_log`
--
ALTER TABLE `kanban_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=436;

--
-- AUTO_INCREMENT for table `kanban_attachments`
--
ALTER TABLE `kanban_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kanban_boards`
--
ALTER TABLE `kanban_boards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kanban_checklists`
--
ALTER TABLE `kanban_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kanban_checklist_items`
--
ALTER TABLE `kanban_checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `kanban_columns`
--
ALTER TABLE `kanban_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `kanban_comments`
--
ALTER TABLE `kanban_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kanban_labels`
--
ALTER TABLE `kanban_labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kanban_links`
--
ALTER TABLE `kanban_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kanban_tasks`
--
ALTER TABLE `kanban_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `kanban_task_assignees`
--
ALTER TABLE `kanban_task_assignees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `kanban_task_labels`
--
ALTER TABLE `kanban_task_labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sensor_types`
--
ALTER TABLE `sensor_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `space_action_tokens`
--
ALTER TABLE `space_action_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `space_events`
--
ALTER TABLE `space_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `space_event_assignees`
--
ALTER TABLE `space_event_assignees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `space_event_tags`
--
ALTER TABLE `space_event_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `space_invitations`
--
ALTER TABLE `space_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_cells`
--
ALTER TABLE `stock_cells`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `stock_columns`
--
ALTER TABLE `stock_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `stock_rows`
--
ALTER TABLE `stock_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `stock_tables`
--
ALTER TABLE `stock_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `esp32_devices`
--
ALTER TABLE `esp32_devices`
  ADD CONSTRAINT `fk_esp32_devices_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `esp32_device_access`
--
ALTER TABLE `esp32_device_access`
  ADD CONSTRAINT `esp32_device_access_esp32_device_id_foreign` FOREIGN KEY (`esp32_device_id`) REFERENCES `esp32_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `esp32_device_access_industry_user_id_foreign` FOREIGN KEY (`industry_user_id`) REFERENCES `industry_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `kanban_activity_log`
--
ALTER TABLE `kanban_activity_log`
  ADD CONSTRAINT `fk_kanban_activity_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_activity_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Constraints for table `kanban_attachments`
--
ALTER TABLE `kanban_attachments`
  ADD CONSTRAINT `fk_kanban_attachments_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_attachments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Constraints for table `kanban_boards`
--
ALTER TABLE `kanban_boards`
  ADD CONSTRAINT `fk_kanban_boards_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `fk_kanban_boards_device` FOREIGN KEY (`esp32_device_id`) REFERENCES `esp32_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_checklists`
--
ALTER TABLE `kanban_checklists`
  ADD CONSTRAINT `fk_kanban_checklists_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_checklist_items`
--
ALTER TABLE `kanban_checklist_items`
  ADD CONSTRAINT `fk_kanban_items_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `kanban_checklists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_columns`
--
ALTER TABLE `kanban_columns`
  ADD CONSTRAINT `fk_kanban_columns_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_comments`
--
ALTER TABLE `kanban_comments`
  ADD CONSTRAINT `fk_kanban_comments_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `kanban_comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_comments_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Constraints for table `kanban_labels`
--
ALTER TABLE `kanban_labels`
  ADD CONSTRAINT `fk_kanban_labels_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_links`
--
ALTER TABLE `kanban_links`
  ADD CONSTRAINT `fk_kanban_links_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_tasks`
--
ALTER TABLE `kanban_tasks`
  ADD CONSTRAINT `fk_kanban_tasks_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_tasks_column` FOREIGN KEY (`column_id`) REFERENCES `kanban_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `fk_kanban_tasks_parent` FOREIGN KEY (`parent_task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;

--
-- Constraints for table `kanban_task_assignees`
--
ALTER TABLE `kanban_task_assignees`
  ADD CONSTRAINT `fk_kanban_assignees_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_assignees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kanban_task_labels`
--
ALTER TABLE `kanban_task_labels`
  ADD CONSTRAINT `fk_kanban_task_labels_label` FOREIGN KEY (`label_id`) REFERENCES `kanban_labels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_task_labels_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD CONSTRAINT `fk_sensor_readings_sensor` FOREIGN KEY (`esp32_sensor_id`) REFERENCES `esp32_sensors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `space_action_tokens`
--
ALTER TABLE `space_action_tokens`
  ADD CONSTRAINT `space_action_tokens_industry_id_foreign` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `space_action_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `space_events`
--
ALTER TABLE `space_events`
  ADD CONSTRAINT `fk_space_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `fk_space_events_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `space_event_assignees`
--
ALTER TABLE `space_event_assignees`
  ADD CONSTRAINT `fk_space_event_assignees_event` FOREIGN KEY (`event_id`) REFERENCES `space_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_space_event_assignees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `space_event_tags`
--
ALTER TABLE `space_event_tags`
  ADD CONSTRAINT `fk_space_event_tags_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_space_event_tags_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `space_invitations`
--
ALTER TABLE `space_invitations`
  ADD CONSTRAINT `space_invitations_industry_id_foreign` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_cells`
--
ALTER TABLE `stock_cells`
  ADD CONSTRAINT `fk_stock_cells_column` FOREIGN KEY (`column_id`) REFERENCES `stock_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_cells_row` FOREIGN KEY (`row_id`) REFERENCES `stock_rows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_columns`
--
ALTER TABLE `stock_columns`
  ADD CONSTRAINT `fk_stock_columns_table` FOREIGN KEY (`table_id`) REFERENCES `stock_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_rows`
--
ALTER TABLE `stock_rows`
  ADD CONSTRAINT `fk_stock_rows_table` FOREIGN KEY (`table_id`) REFERENCES `stock_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_tables`
--
ALTER TABLE `stock_tables`
  ADD CONSTRAINT `fk_stock_tables_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `fk_stock_tables_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD CONSTRAINT `fk_verification_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
