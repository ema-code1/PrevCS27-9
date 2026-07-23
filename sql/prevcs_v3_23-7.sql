-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 02:32 PM
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
  `sector` varchar(120) DEFAULT NULL,
  `token_hash` varchar(64) DEFAULT NULL,
  `token_secret` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
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
(1, 'SJS-IND-055F02', 'San Jose SRL', 'Extrusoras', 'Planta aceitera', '$2y$10$UoFloDiEnGu0DRowC9nNFOm45p6n0UUIAZjvB7Ym99hOmVNj2mrb.', 1, '2026-07-23 11:45:35');

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
(1, 1, 4, 'gauge'),
(2, 1, 1, 'line'),
(3, 1, 3, 'line');

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
(1, 1, 1, 'admin_principal', '2026-07-23 11:45:35');

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
(16, '2026-06-09-000002', 'App\\Database\\Migrations\\MakeEsp32MacNullable', 'default', 'App', 1784806920, 1);

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
(1, 'temperature', 'Temperatura', '°C', 'line', '2026-07-23 11:42:00'),
(2, 'humidity', 'Humedad', '%', 'area', '2026-07-23 11:42:00'),
(3, 'vibration', 'Vibración', 'mm/s', 'line', '2026-07-23 11:42:00'),
(4, 'rpm', 'RPM', 'rev/min', 'gauge', '2026-07-23 11:42:00'),
(5, 'current', 'Corriente', 'A', 'bars', '2026-07-23 11:42:00'),
(6, 'on_off', 'Estado ON/OFF', NULL, 'timeline', '2026-07-23 11:42:00');

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
  `email_verified_at` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nombre`, `apellido`, `email`, `password_hash`, `foto_perfil`, `created_at`, `email_verified_at`, `two_factor_enabled`) VALUES
(1, 'Jose', 'Mangold', 'josemangold08@gmail.com', '$2y$10$bLgYMEOz0ideRpRVojsF1eBks6agKd6Eh8/3VhP8wnOy.MIrM3ahC', NULL, '2026-07-23 11:44:14', '2026-07-23 11:44:35', 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` varchar(40) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_codes`
--

INSERT INTO `verification_codes` (`id`, `user_id`, `purpose`, `code_hash`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'email_verification', '$2y$10$9lPtFt.MuZZtyv7ldK4P/.dXFvaF5SKQwEnn.Vmkf15UJ4O06mzQ2', 0, '2026-07-23 11:49:14', '2026-07-23 11:44:35', '2026-07-23 11:44:14');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esp32_sensors`
--
ALTER TABLE `esp32_sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `industry_metrics`
--
ALTER TABLE `industry_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `industry_users`
--
ALTER TABLE `industry_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_types`
--
ALTER TABLE `sensor_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD CONSTRAINT `fk_verification_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
