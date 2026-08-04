-- =====================================================================
-- kanban_test.sql
-- Archivo para importar en dbdiagram.io (Import > From SQL > MySQL)
-- Contiene: tus tablas actuales (tal como están en prevcs_v3_23-7.sql,
-- sin los INSERT de datos, solo estructura) + las tablas nuevas del
-- tablero Kanban al final.
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================================
-- TABLAS ACTUALES (sin modificar)
-- =====================================================================

-- --------------------------------------------------------
-- Table structure for table `esp32_devices`
-- --------------------------------------------------------

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
-- Table structure for table `esp32_sensors`
-- --------------------------------------------------------

CREATE TABLE `esp32_sensors` (
  `id` int(11) NOT NULL,
  `esp32_device_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `form_answers`
-- --------------------------------------------------------

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
-- Table structure for table `industries`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `industry_metrics`
-- --------------------------------------------------------

CREATE TABLE `industry_metrics` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `sensor_type_id` int(11) NOT NULL,
  `suggested_chart` varchar(20) NOT NULL DEFAULT 'line'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `industry_users`
-- --------------------------------------------------------

CREATE TABLE `industry_users` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `migrations`
-- --------------------------------------------------------

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `password_resets`
-- --------------------------------------------------------

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `sensor_readings`
-- --------------------------------------------------------

CREATE TABLE `sensor_readings` (
  `id` bigint(20) NOT NULL,
  `esp32_sensor_id` int(11) NOT NULL,
  `value` decimal(12,3) NOT NULL,
  `measured_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `sensor_types`
-- --------------------------------------------------------

CREATE TABLE `sensor_types` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `default_chart` varchar(20) NOT NULL DEFAULT 'line',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `verification_codes`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Indexes for existing tables
-- --------------------------------------------------------

ALTER TABLE `esp32_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mac_address` (`mac_address`),
  ADD KEY `fk_esp32_devices_industry` (`industry_id`);

ALTER TABLE `esp32_sensors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esp32_device_id_sensor_type_id` (`esp32_device_id`,`sensor_type_id`),
  ADD KEY `fk_esp32_sensors_device` (`esp32_device_id`),
  ADD KEY `fk_esp32_sensors_type` (`sensor_type_id`);

ALTER TABLE `form_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `raw_timestamp` (`raw_timestamp`),
  ADD KEY `submitted_at` (`submitted_at`);

ALTER TABLE `industries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_uid` (`industry_uid`),
  ADD KEY `fk_industries_creator` (`created_by`);

ALTER TABLE `industry_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_id_sensor_type_id` (`industry_id`,`sensor_type_id`),
  ADD KEY `fk_industry_metrics_industry` (`industry_id`),
  ADD KEY `fk_industry_metrics_type` (`sensor_type_id`);

ALTER TABLE `industry_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industry_id_user_id` (`industry_id`,`user_id`),
  ADD KEY `fk_industry_users_industry` (`industry_id`),
  ADD KEY `fk_industry_users_user` (`user_id`);

ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_password_resets_user` (`user_id`);

ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esp32_sensor_id_measured_at` (`esp32_sensor_id`,`measured_at`);

ALTER TABLE `sensor_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_verification_user_purpose` (`user_id`,`purpose`),
  ADD KEY `expires_at` (`expires_at`);

-- --------------------------------------------------------
-- AUTO_INCREMENT for existing tables
-- --------------------------------------------------------

ALTER TABLE `esp32_devices` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `esp32_sensors` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `form_answers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `industries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `industry_metrics` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `industry_users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `migrations` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `password_resets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sensor_readings` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sensor_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `verification_codes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Constraints for existing tables
-- --------------------------------------------------------

ALTER TABLE `esp32_devices`
  ADD CONSTRAINT `fk_esp32_devices_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `esp32_sensors`
  ADD CONSTRAINT `fk_esp32_sensors_device` FOREIGN KEY (`esp32_device_id`) REFERENCES `esp32_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_esp32_sensors_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `industries`
  ADD CONSTRAINT `fk_industries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `industry_metrics`
  ADD CONSTRAINT `fk_industry_metrics_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_industry_metrics_type` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `industry_users`
  ADD CONSTRAINT `fk_industry_users_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_industry_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sensor_readings`
  ADD CONSTRAINT `fk_sensor_readings_sensor` FOREIGN KEY (`esp32_sensor_id`) REFERENCES `esp32_sensors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `verification_codes`
  ADD CONSTRAINT `fk_verification_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


-- =====================================================================
-- TABLAS NUEVAS: Tablero Kanban
-- =====================================================================

-- --------------------------------------------------------
-- Tableros
-- --------------------------------------------------------

CREATE TABLE `kanban_boards` (
  `id` int(11) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `background_color` varchar(20) NOT NULL DEFAULT 'blue',
  `created_by` int(11) NOT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Miembros del tablero (arquitectura de roles/permisos preparada,
-- aunque hoy solo se use "admin")
-- --------------------------------------------------------

CREATE TABLE `kanban_board_members` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','editor','colaborador','lector') NOT NULL DEFAULT 'colaborador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Columnas
-- --------------------------------------------------------

CREATE TABLE `kanban_columns` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'gray',
  `position` int(11) NOT NULL DEFAULT 0,
  `status_key` varchar(40) DEFAULT NULL,
  `task_limit` int(11) DEFAULT NULL,
  `is_collapsed` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tareas
-- --------------------------------------------------------

CREATE TABLE `kanban_tasks` (
  `id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `parent_task_id` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
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
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Responsables asignados a una tarea (N:M)
-- --------------------------------------------------------

CREATE TABLE `kanban_task_assignees` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Etiquetas (paleta por tablero) y su relación N:M con tareas
-- --------------------------------------------------------

CREATE TABLE `kanban_labels` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'blue',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kanban_task_labels` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Checklists e ítems
-- --------------------------------------------------------

CREATE TABLE `kanban_checklists` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT 'Checklist',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kanban_checklist_items` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Comentarios (con respuestas mediante parent_comment_id)
-- --------------------------------------------------------

CREATE TABLE `kanban_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Adjuntos y enlaces
-- --------------------------------------------------------

CREATE TABLE `kanban_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kanban_links` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Historial de actividad
-- --------------------------------------------------------

CREATE TABLE `kanban_activity_log` (
  `id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Indexes for kanban tables
-- --------------------------------------------------------

ALTER TABLE `kanban_boards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_boards_industry` (`industry_id`),
  ADD KEY `fk_kanban_boards_creator` (`created_by`);

ALTER TABLE `kanban_board_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `board_id_user_id` (`board_id`,`user_id`),
  ADD KEY `fk_kanban_board_members_board` (`board_id`),
  ADD KEY `fk_kanban_board_members_user` (`user_id`);

ALTER TABLE `kanban_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_columns_board` (`board_id`);

ALTER TABLE `kanban_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_tasks_column` (`column_id`),
  ADD KEY `fk_kanban_tasks_board` (`board_id`),
  ADD KEY `fk_kanban_tasks_parent` (`parent_task_id`),
  ADD KEY `fk_kanban_tasks_creator` (`created_by`);

ALTER TABLE `kanban_task_assignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id_user_id` (`task_id`,`user_id`),
  ADD KEY `fk_kanban_task_assignees_task` (`task_id`),
  ADD KEY `fk_kanban_task_assignees_user` (`user_id`);

ALTER TABLE `kanban_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `board_id_name` (`board_id`,`name`),
  ADD KEY `fk_kanban_labels_board` (`board_id`);

ALTER TABLE `kanban_task_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id_label_id` (`task_id`,`label_id`),
  ADD KEY `fk_kanban_task_labels_task` (`task_id`),
  ADD KEY `fk_kanban_task_labels_label` (`label_id`);

ALTER TABLE `kanban_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_checklists_task` (`task_id`);

ALTER TABLE `kanban_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_checklist_items_checklist` (`checklist_id`);

ALTER TABLE `kanban_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_comments_task` (`task_id`),
  ADD KEY `fk_kanban_comments_user` (`user_id`),
  ADD KEY `fk_kanban_comments_parent` (`parent_comment_id`);

ALTER TABLE `kanban_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_attachments_task` (`task_id`),
  ADD KEY `fk_kanban_attachments_user` (`user_id`);

ALTER TABLE `kanban_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_links_task` (`task_id`);

ALTER TABLE `kanban_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kanban_activity_board` (`board_id`),
  ADD KEY `fk_kanban_activity_task` (`task_id`),
  ADD KEY `fk_kanban_activity_user` (`user_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT for kanban tables
-- --------------------------------------------------------

ALTER TABLE `kanban_boards` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_board_members` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_columns` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_tasks` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_task_assignees` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_labels` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_task_labels` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_checklists` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_checklist_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_comments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_attachments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_links` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kanban_activity_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Constraints for kanban tables
-- --------------------------------------------------------

ALTER TABLE `kanban_boards`
  ADD CONSTRAINT `fk_kanban_boards_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_boards_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_board_members`
  ADD CONSTRAINT `fk_kanban_board_members_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_board_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_columns`
  ADD CONSTRAINT `fk_kanban_columns_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_tasks`
  ADD CONSTRAINT `fk_kanban_tasks_column` FOREIGN KEY (`column_id`) REFERENCES `kanban_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_tasks_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_tasks_parent` FOREIGN KEY (`parent_task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_task_assignees`
  ADD CONSTRAINT `fk_kanban_task_assignees_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_task_assignees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_labels`
  ADD CONSTRAINT `fk_kanban_labels_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_task_labels`
  ADD CONSTRAINT `fk_kanban_task_labels_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_task_labels_label` FOREIGN KEY (`label_id`) REFERENCES `kanban_labels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_checklists`
  ADD CONSTRAINT `fk_kanban_checklists_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_checklist_items`
  ADD CONSTRAINT `fk_kanban_checklist_items_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `kanban_checklists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_comments`
  ADD CONSTRAINT `fk_kanban_comments_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_comments_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `kanban_comments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `kanban_attachments`
  ADD CONSTRAINT `fk_kanban_attachments_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_attachments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_links`
  ADD CONSTRAINT `fk_kanban_links_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `kanban_activity_log`
  ADD CONSTRAINT `fk_kanban_activity_board` FOREIGN KEY (`board_id`) REFERENCES `kanban_boards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_activity_task` FOREIGN KEY (`task_id`) REFERENCES `kanban_tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kanban_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;
