-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: prevcs
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `esp32_devices`
--

DROP TABLE IF EXISTS `esp32_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `esp32_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `token_hash` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'offline',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `mac_address` (`mac_address`),
  KEY `fk_esp32_devices_industry` (`industry_id`),
  CONSTRAINT `fk_esp32_devices_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `esp32_devices`
--

LOCK TABLES `esp32_devices` WRITE;
/*!40000 ALTER TABLE `esp32_devices` DISABLE KEYS */;
INSERT INTO `esp32_devices` VALUES (6,4,'Motor 1','D4:E9:F4:8D:18:7C','Planta aceitera','31e30111dcbeef1fe16694a563ebd47efcb2112539462e51e14a07009c46dd92',1,'online','2026-06-03 02:51:25','2026-06-03 00:30:28');
/*!40000 ALTER TABLE `esp32_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `esp32_sensors`
--

DROP TABLE IF EXISTS `esp32_sensors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `esp32_sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `esp32_device_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `esp32_device_id_sensor_type_id` (`esp32_device_id`,`sensor_type_id`),
  KEY `fk_esp32_sensors_device` (`esp32_device_id`),
  KEY `fk_esp32_sensors_type` (`sensor_type_id`),
  CONSTRAINT `fk_esp32_sensors_device` FOREIGN KEY (`esp32_device_id`) REFERENCES `esp32_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_esp32_sensors_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `esp32_sensors`
--

LOCK TABLES `esp32_sensors` WRITE;
/*!40000 ALTER TABLE `esp32_sensors` DISABLE KEYS */;
INSERT INTO `esp32_sensors` VALUES (8,6,1,NULL,'2026-06-03 00:45:46'),(9,6,4,NULL,'2026-06-03 00:45:53'),(10,6,3,NULL,'2026-06-03 00:46:00');
/*!40000 ALTER TABLE `esp32_sensors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_answers`
--

DROP TABLE IF EXISTS `form_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raw_timestamp` varchar(64) NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `nombre` varchar(200) DEFAULT NULL,
  `rol_empresa` varchar(80) DEFAULT NULL,
  `conoce_similar` varchar(20) DEFAULT NULL,
  `funcion_util` varchar(200) DEFAULT NULL,
  `nivel_mejora` varchar(20) DEFAULT NULL,
  `es_ecologico` varchar(40) DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `raw_timestamp` (`raw_timestamp`),
  KEY `submitted_at` (`submitted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_answers`
--

LOCK TABLES `form_answers` WRITE;
/*!40000 ALTER TABLE `form_answers` DISABLE KEYS */;
INSERT INTO `form_answers` VALUES (1,'6/5/2026 12:57:59','2026-05-06 12:57:59','Emanuel','Administración','Sí','Gestión Mantenimiento','Alto','Si','2026-05-21 23:15:30'),(2,'6/5/2026 17:18:32','2026-05-06 17:18:32','GENARO','Gerente','Sí','Gráficos','Alto','Si','2026-05-21 23:15:30'),(3,'6/5/2026 17:32:47','2026-05-06 17:32:47','Gaston meotto','Operador','Sí','Gestión Mantenimiento','Alto','Si','2026-05-21 23:15:30'),(4,'6/5/2026 17:32:55','2026-05-06 17:32:55','Esteban angel sebastian','Operador','Sí','Gestión Mantenimiento','Alto','Si','2026-05-21 23:15:30'),(5,'6/5/2026 18:18:14','2026-05-06 18:18:14','jeremias brusasca','Operador','Sí','Gestión Mantenimiento','Medio','Si','2026-05-21 23:15:30'),(6,'7/5/2026 9:24:48','2026-07-05 09:24:48','Emanuel Martin','Operador','No','Sensor que monitoree un motor y presente datos en tiempo real','Alto','Si','2026-05-21 23:15:30'),(7,'11/5/2026 21:01:17','2026-05-11 21:01:17','Mayra','Administración','No','Sensor que monitoree un motor y presente datos en tiempo real','Alto','Si','2026-05-21 23:15:30'),(8,'12/5/2026 16:42:29','2026-05-12 16:42:29','Rosana','Administración','No','Stock','Medio','Si','2026-05-21 23:15:30'),(9,'12/5/2026 16:43:45','2026-05-12 16:43:45','Jorge Daniel','Operador','Sí','Gestión Mantenimiento','Alto','Me es indiferente','2026-05-21 23:15:30'),(10,'12/5/2026 16:45:43','2026-05-12 16:45:43','Licia','Administración','Sí','Gráficos','Alto','Si','2026-05-21 23:15:30');
/*!40000 ALTER TABLE `form_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `industries`
--

DROP TABLE IF EXISTS `industries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `industries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_uid` varchar(40) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `space_name` varchar(200) NOT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `space_password_hash` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `industry_uid` (`industry_uid`),
  KEY `fk_industries_creator` (`created_by`),
  CONSTRAINT `fk_industries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `industries`
--

LOCK TABLES `industries` WRITE;
/*!40000 ALTER TABLE `industries` DISABLE KEYS */;
INSERT INTO `industries` VALUES (4,'SJS-IND-2FBAF6','San Jose SRL','Extrusoras','Planta aceitera','$2y$10$Co26Lv8T1CwzWKV3n3SyG.0IzbMan4c2vIaJAhKc5by2Y7Dx1MAtq',1,'2026-06-02 22:43:10');
/*!40000 ALTER TABLE `industries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `industry_metrics`
--

DROP TABLE IF EXISTS `industry_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `industry_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `suggested_chart` varchar(20) NOT NULL DEFAULT 'line',
  PRIMARY KEY (`id`),
  UNIQUE KEY `industry_id_sensor_type_id` (`industry_id`,`sensor_type_id`),
  KEY `fk_industry_metrics_industry` (`industry_id`),
  KEY `fk_industry_metrics_type` (`sensor_type_id`),
  CONSTRAINT `fk_industry_metrics_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_industry_metrics_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `industry_metrics`
--

LOCK TABLES `industry_metrics` WRITE;
/*!40000 ALTER TABLE `industry_metrics` DISABLE KEYS */;
INSERT INTO `industry_metrics` VALUES (8,4,6,'timeline'),(9,4,4,'gauge'),(10,4,1,'line'),(11,4,3,'line');
/*!40000 ALTER TABLE `industry_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `industry_users`
--

DROP TABLE IF EXISTS `industry_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `industry_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `industry_id_user_id` (`industry_id`,`user_id`),
  KEY `fk_industry_users_industry` (`industry_id`),
  KEY `fk_industry_users_user` (`user_id`),
  CONSTRAINT `fk_industry_users_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_industry_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `industry_users`
--

LOCK TABLES `industry_users` WRITE;
/*!40000 ALTER TABLE `industry_users` DISABLE KEYS */;
INSERT INTO `industry_users` VALUES (5,4,1,'admin_principal','2026-06-02 22:43:10');
/*!40000 ALTER TABLE `industry_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-04-21-000001','App\\Database\\Migrations\\CreateUsersTable','default','App',1779405258,1),(2,'2026-05-08-000001','App\\Database\\Migrations\\CreatePasswordResetsTable','default','App',1779405258,1),(3,'2026-05-21-000001','App\\Database\\Migrations\\CreateFormAnswersTable','default','App',1779405258,1),(4,'2026-05-29-000001','App\\Database\\Migrations\\CreateIndustriesTable','default','App',1780089087,2),(5,'2026-05-29-000002','App\\Database\\Migrations\\CreateIndustryUsersTable','default','App',1780089087,2),(6,'2026-05-29-000003','App\\Database\\Migrations\\CreateSensorTypesTable','default','App',1780089087,2),(7,'2026-05-29-000004','App\\Database\\Migrations\\CreateEsp32DevicesTable','default','App',1780089087,2),(8,'2026-05-29-000005','App\\Database\\Migrations\\CreateEsp32SensorsTable','default','App',1780089087,2),(9,'2026-05-29-000006','App\\Database\\Migrations\\CreateSensorReadingsTable','default','App',1780089087,2),(10,'2026-05-29-000007','App\\Database\\Migrations\\CreateIndustryMetricsTable','default','App',1780089087,2),(11,'2026-06-02-000001','App\\Database\\Migrations\\AlterEsp32DevicesAddSecurity','default','App',1780434868,3),(12,'2026-06-02-000002','App\\Database\\Migrations\\SeedRpmAnalogSensorType','default','App',1780434868,3),(13,'2026-06-02-000003','App\\Database\\Migrations\\RemoveRpmAnalogSensorType','default','App',1780439660,4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_password_resets_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_readings`
--

DROP TABLE IF EXISTS `sensor_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor_readings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `esp32_sensor_id` int(11) NOT NULL,
  `value` decimal(12,3) NOT NULL,
  `measured_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `esp32_sensor_id_measured_at` (`esp32_sensor_id`,`measured_at`),
  CONSTRAINT `fk_sensor_readings_sensor` FOREIGN KEY (`esp32_sensor_id`) REFERENCES `esp32_sensors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_readings`
--

LOCK TABLES `sensor_readings` WRITE;
/*!40000 ALTER TABLE `sensor_readings` DISABLE KEYS */;
INSERT INTO `sensor_readings` VALUES (32,10,0.000,'2026-06-03 01:07:16','2026-06-03 01:07:16'),(33,9,0.000,'2026-06-03 01:07:16','2026-06-03 01:07:16'),(34,10,0.000,'2026-06-03 01:08:16','2026-06-03 01:08:16'),(35,9,0.000,'2026-06-03 01:08:16','2026-06-03 01:08:16'),(36,10,0.000,'2026-06-03 01:09:16','2026-06-03 01:09:16'),(37,9,0.000,'2026-06-03 01:09:16','2026-06-03 01:09:16'),(38,10,17919.000,'2026-06-03 01:41:23','2026-06-03 01:41:23'),(39,9,0.000,'2026-06-03 01:41:23','2026-06-03 01:41:23'),(40,8,20.690,'2026-06-03 01:41:23','2026-06-03 01:41:23'),(41,10,19377.000,'2026-06-03 01:42:23','2026-06-03 01:42:23'),(42,9,0.000,'2026-06-03 01:42:23','2026-06-03 01:42:23'),(43,8,22.190,'2026-06-03 01:42:23','2026-06-03 01:42:23'),(44,10,961.000,'2026-06-03 01:43:23','2026-06-03 01:43:23'),(45,9,0.000,'2026-06-03 01:43:23','2026-06-03 01:43:23'),(46,8,24.750,'2026-06-03 01:43:23','2026-06-03 01:43:23'),(47,10,0.000,'2026-06-03 01:44:23','2026-06-03 01:44:23'),(48,9,0.000,'2026-06-03 01:44:23','2026-06-03 01:44:23'),(49,8,23.500,'2026-06-03 01:44:23','2026-06-03 01:44:23'),(50,10,0.000,'2026-06-03 02:12:25','2026-06-03 02:12:25'),(51,9,0.000,'2026-06-03 02:12:25','2026-06-03 02:12:25'),(52,8,19.190,'2026-06-03 02:12:25','2026-06-03 02:12:25'),(53,10,0.000,'2026-06-03 02:13:24','2026-06-03 02:13:24'),(54,9,0.000,'2026-06-03 02:13:24','2026-06-03 02:13:24'),(55,8,19.620,'2026-06-03 02:13:24','2026-06-03 02:13:24'),(56,10,0.000,'2026-06-03 02:14:24','2026-06-03 02:14:24'),(57,9,0.000,'2026-06-03 02:14:24','2026-06-03 02:14:24'),(58,8,19.880,'2026-06-03 02:14:24','2026-06-03 02:14:24'),(59,10,0.000,'2026-06-03 02:15:24','2026-06-03 02:15:24'),(60,9,0.000,'2026-06-03 02:15:24','2026-06-03 02:15:24'),(61,8,20.310,'2026-06-03 02:15:24','2026-06-03 02:15:24'),(62,10,0.000,'2026-06-03 02:16:24','2026-06-03 02:16:24'),(63,9,0.000,'2026-06-03 02:16:24','2026-06-03 02:16:24'),(64,8,20.690,'2026-06-03 02:16:24','2026-06-03 02:16:24'),(65,10,0.000,'2026-06-03 02:17:24','2026-06-03 02:17:24'),(66,9,0.000,'2026-06-03 02:17:24','2026-06-03 02:17:24'),(67,8,20.690,'2026-06-03 02:17:24','2026-06-03 02:17:24'),(68,10,0.000,'2026-06-03 02:18:25','2026-06-03 02:18:25'),(69,9,0.000,'2026-06-03 02:18:25','2026-06-03 02:18:25'),(70,8,20.560,'2026-06-03 02:18:25','2026-06-03 02:18:25'),(71,10,0.000,'2026-06-03 02:19:24','2026-06-03 02:19:24'),(72,9,0.000,'2026-06-03 02:19:24','2026-06-03 02:19:24'),(73,8,20.560,'2026-06-03 02:19:24','2026-06-03 02:19:24'),(74,10,0.000,'2026-06-03 02:20:24','2026-06-03 02:20:24'),(75,9,0.000,'2026-06-03 02:20:24','2026-06-03 02:20:24'),(76,8,20.500,'2026-06-03 02:20:24','2026-06-03 02:20:24'),(77,10,0.000,'2026-06-03 02:21:25','2026-06-03 02:21:25'),(78,9,0.000,'2026-06-03 02:21:25','2026-06-03 02:21:25'),(79,8,20.440,'2026-06-03 02:21:25','2026-06-03 02:21:25'),(80,10,0.000,'2026-06-03 02:22:25','2026-06-03 02:22:25'),(81,9,0.000,'2026-06-03 02:22:25','2026-06-03 02:22:25'),(82,8,20.310,'2026-06-03 02:22:25','2026-06-03 02:22:25'),(83,10,0.000,'2026-06-03 02:23:24','2026-06-03 02:23:24'),(84,9,0.000,'2026-06-03 02:23:24','2026-06-03 02:23:24'),(85,8,20.310,'2026-06-03 02:23:24','2026-06-03 02:23:24'),(86,10,0.000,'2026-06-03 02:24:24','2026-06-03 02:24:24'),(87,9,0.000,'2026-06-03 02:24:24','2026-06-03 02:24:24'),(88,8,20.250,'2026-06-03 02:24:24','2026-06-03 02:24:24'),(89,10,0.000,'2026-06-03 02:25:25','2026-06-03 02:25:25'),(90,9,0.000,'2026-06-03 02:25:25','2026-06-03 02:25:25'),(91,8,20.190,'2026-06-03 02:25:25','2026-06-03 02:25:25'),(92,10,0.000,'2026-06-03 02:26:24','2026-06-03 02:26:24'),(93,9,0.000,'2026-06-03 02:26:24','2026-06-03 02:26:24'),(94,8,20.190,'2026-06-03 02:26:24','2026-06-03 02:26:24'),(95,10,0.000,'2026-06-03 02:27:25','2026-06-03 02:27:25'),(96,9,0.000,'2026-06-03 02:27:25','2026-06-03 02:27:25'),(97,8,20.190,'2026-06-03 02:27:25','2026-06-03 02:27:25'),(98,10,0.000,'2026-06-03 02:28:25','2026-06-03 02:28:25'),(99,9,0.000,'2026-06-03 02:28:25','2026-06-03 02:28:25'),(100,8,20.120,'2026-06-03 02:28:25','2026-06-03 02:28:25'),(101,10,0.000,'2026-06-03 02:29:25','2026-06-03 02:29:25'),(102,9,0.000,'2026-06-03 02:29:25','2026-06-03 02:29:25'),(103,8,20.120,'2026-06-03 02:29:25','2026-06-03 02:29:25'),(104,10,0.000,'2026-06-03 02:30:25','2026-06-03 02:30:25'),(105,9,0.000,'2026-06-03 02:30:25','2026-06-03 02:30:25'),(106,8,20.250,'2026-06-03 02:30:25','2026-06-03 02:30:25'),(107,10,0.000,'2026-06-03 02:31:25','2026-06-03 02:31:25'),(108,9,0.000,'2026-06-03 02:31:25','2026-06-03 02:31:25'),(109,8,20.440,'2026-06-03 02:31:25','2026-06-03 02:31:25'),(110,10,0.000,'2026-06-03 02:32:25','2026-06-03 02:32:25'),(111,9,0.000,'2026-06-03 02:32:25','2026-06-03 02:32:25'),(112,8,20.310,'2026-06-03 02:32:25','2026-06-03 02:32:25'),(113,10,0.000,'2026-06-03 02:33:25','2026-06-03 02:33:25'),(114,9,0.000,'2026-06-03 02:33:25','2026-06-03 02:33:25'),(115,8,20.250,'2026-06-03 02:33:25','2026-06-03 02:33:25'),(116,10,0.000,'2026-06-03 02:34:25','2026-06-03 02:34:25'),(117,9,0.000,'2026-06-03 02:34:25','2026-06-03 02:34:25'),(118,8,20.190,'2026-06-03 02:34:25','2026-06-03 02:34:25'),(119,10,0.000,'2026-06-03 02:35:25','2026-06-03 02:35:25'),(120,9,0.000,'2026-06-03 02:35:25','2026-06-03 02:35:25'),(121,8,20.120,'2026-06-03 02:35:25','2026-06-03 02:35:25'),(122,10,0.000,'2026-06-03 02:36:25','2026-06-03 02:36:25'),(123,9,0.000,'2026-06-03 02:36:25','2026-06-03 02:36:25'),(124,8,20.120,'2026-06-03 02:36:25','2026-06-03 02:36:25'),(125,10,0.000,'2026-06-03 02:37:25','2026-06-03 02:37:25'),(126,9,0.000,'2026-06-03 02:37:25','2026-06-03 02:37:25'),(127,8,20.120,'2026-06-03 02:37:25','2026-06-03 02:37:25'),(128,10,0.000,'2026-06-03 02:38:25','2026-06-03 02:38:25'),(129,9,0.000,'2026-06-03 02:38:25','2026-06-03 02:38:25'),(130,8,20.120,'2026-06-03 02:38:25','2026-06-03 02:38:25'),(131,10,0.000,'2026-06-03 02:39:25','2026-06-03 02:39:25'),(132,9,0.000,'2026-06-03 02:39:25','2026-06-03 02:39:25'),(133,8,20.060,'2026-06-03 02:39:25','2026-06-03 02:39:25'),(134,10,0.000,'2026-06-03 02:40:25','2026-06-03 02:40:25'),(135,9,0.000,'2026-06-03 02:40:25','2026-06-03 02:40:25'),(136,8,20.000,'2026-06-03 02:40:25','2026-06-03 02:40:25'),(137,10,0.000,'2026-06-03 02:41:25','2026-06-03 02:41:25'),(138,9,0.000,'2026-06-03 02:41:25','2026-06-03 02:41:25'),(139,8,20.000,'2026-06-03 02:41:25','2026-06-03 02:41:25'),(140,10,0.000,'2026-06-03 02:42:25','2026-06-03 02:42:25'),(141,9,0.000,'2026-06-03 02:42:25','2026-06-03 02:42:25'),(142,8,20.000,'2026-06-03 02:42:25','2026-06-03 02:42:25'),(143,10,0.000,'2026-06-03 02:43:25','2026-06-03 02:43:25'),(144,9,0.000,'2026-06-03 02:43:25','2026-06-03 02:43:25'),(145,8,19.940,'2026-06-03 02:43:25','2026-06-03 02:43:25'),(146,10,48.000,'2026-06-03 02:44:25','2026-06-03 02:44:25'),(147,9,0.000,'2026-06-03 02:44:25','2026-06-03 02:44:25'),(148,8,20.000,'2026-06-03 02:44:25','2026-06-03 02:44:25'),(149,10,0.000,'2026-06-03 02:45:25','2026-06-03 02:45:25'),(150,9,0.000,'2026-06-03 02:45:25','2026-06-03 02:45:25'),(151,8,20.000,'2026-06-03 02:45:25','2026-06-03 02:45:25'),(152,10,0.000,'2026-06-03 02:46:25','2026-06-03 02:46:25'),(153,9,0.000,'2026-06-03 02:46:25','2026-06-03 02:46:25'),(154,8,19.940,'2026-06-03 02:46:25','2026-06-03 02:46:25'),(155,10,0.000,'2026-06-03 02:47:25','2026-06-03 02:47:25'),(156,9,0.000,'2026-06-03 02:47:25','2026-06-03 02:47:25'),(157,8,19.880,'2026-06-03 02:47:25','2026-06-03 02:47:25'),(158,10,0.000,'2026-06-03 02:48:25','2026-06-03 02:48:25'),(159,9,0.000,'2026-06-03 02:48:25','2026-06-03 02:48:25'),(160,8,19.880,'2026-06-03 02:48:25','2026-06-03 02:48:25'),(161,10,0.000,'2026-06-03 02:49:25','2026-06-03 02:49:25'),(162,9,0.000,'2026-06-03 02:49:25','2026-06-03 02:49:25'),(163,8,19.880,'2026-06-03 02:49:25','2026-06-03 02:49:25'),(164,10,0.000,'2026-06-03 02:50:25','2026-06-03 02:50:25'),(165,9,0.000,'2026-06-03 02:50:25','2026-06-03 02:50:25'),(166,8,19.810,'2026-06-03 02:50:25','2026-06-03 02:50:25'),(167,10,0.000,'2026-06-03 02:51:25','2026-06-03 02:51:25'),(168,9,0.000,'2026-06-03 02:51:25','2026-06-03 02:51:25'),(169,8,19.880,'2026-06-03 02:51:25','2026-06-03 02:51:25');
/*!40000 ALTER TABLE `sensor_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_types`
--

DROP TABLE IF EXISTS `sensor_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `default_chart` varchar(20) NOT NULL DEFAULT 'line',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_types`
--

LOCK TABLES `sensor_types` WRITE;
/*!40000 ALTER TABLE `sensor_types` DISABLE KEYS */;
INSERT INTO `sensor_types` VALUES (1,'temperature','Temperatura','°C','line','2026-05-29 21:11:27'),(2,'humidity','Humedad','%','area','2026-05-29 21:11:27'),(3,'vibration','Vibración','mm/s','line','2026-05-29 21:11:27'),(4,'rpm','RPM','rev/min','gauge','2026-05-29 21:11:27'),(5,'current','Corriente','A','bars','2026-05-29 21:11:27'),(6,'on_off','Estado ON/OFF',NULL,'timeline','2026-05-29 21:11:27');
/*!40000 ALTER TABLE `sensor_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Jose','Mangold','josemangold08@gmail.com','$2y$10$VfXfh6pQ9JWPOQvUchd2dOyAiWw9C3COQTNL0CDgkiDQ1e5XzmvGG',NULL,'2026-04-27 19:38:47'),(2,'Emaaa','','emarissopatron@gmail.com','$2y$10$mrq7CrKrWsQpOf3cZhLauuBKzJap936E4FnjklWjkkfmXZ71Im3QG',NULL,'2026-05-08 14:53:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-02 23:51:50
