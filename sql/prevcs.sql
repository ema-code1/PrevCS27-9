-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 02:20 AM
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
  `device_token` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'offline',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'SJS-IND-599DFF', 'San Jose SRL', 'Extrusoras', 'Planta aceitera', '$2y$10$C5QsoMLFsOPrObQ60WbJkeiXLrrwjqBdqReOWmNoeNB12r6ImCBeW', 1, '2026-06-01 00:18:36');

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
(4, 3, 6, 'timeline'),
(5, 3, 4, 'gauge'),
(6, 3, 1, 'line'),
(7, 3, 3, 'line');

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
(4, 3, 1, 'admin_principal', '2026-06-01 00:18:36');

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
(10, '2026-05-29-000007', 'App\\Database\\Migrations\\CreateIndustryMetricsTable', 'default', 'App', 1780089087, 2);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `industry_users`
--
ALTER TABLE `industry_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sensor_types`
--
ALTER TABLE `sensor_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
