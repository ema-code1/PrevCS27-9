-- Notificaciones persistentes por usuario.
--
-- Hasta ahora las alertas de stock vivían en el localStorage del navegador, así
-- que solo las veía quien estaba mirando la tabla en ese momento. Esta tabla las
-- lleva al servidor: se escribe una fila por cada miembro del espacio (fan-out
-- en escritura), lo que hace triviales el "leído" por usuario y el contador de
-- la campana, y sobreviven al refresh y al cambio de dispositivo.
--
-- El proyecto no usa migraciones de CodeIgniter: correr este archivo a mano y
-- volcarlo al dump principal (sql/prevcs 25-8-26.sql).
--
-- REEMPLAZADO por la migración 2026-09-16-000001_CreateNotificationsTable:
-- ya no hace falta correrlo, setup.cmd / php spark migrate crean la tabla.

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `industry_id` int(11)      NOT NULL,
  `user_id`     int(11)      NOT NULL COMMENT 'Destinatario: una fila por miembro del espacio',
  `level`       tinyint(1)   NOT NULL COMMENT '1 = agotado, 2 = bajo el minimo, 3 = aviso propio',
  `title`       varchar(160) NOT NULL,
  `text`        varchar(500) NOT NULL,
  `source`      varchar(40)  NOT NULL DEFAULT 'stock',
  `source_key`  varchar(120) NOT NULL COMMENT 'stock:{rowId} - identifica el origen para no repetir el aviso',
  `link`        varchar(255)          DEFAULT NULL,
  `read_at`     datetime              DEFAULT NULL,
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_bandeja` (`user_id`, `id`),
  KEY `idx_notifications_sin_leer` (`user_id`, `read_at`),
  KEY `idx_notifications_origen` (`source_key`, `user_id`, `id`),
  KEY `idx_notifications_industry` (`industry_id`),
  CONSTRAINT `fk_notifications_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
