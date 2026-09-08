-- Invitaciones por correo a un espacio.
--
-- Antes la única forma de sumar a alguien era pasarle el UID y la clave del
-- espacio por afuera del sistema. Esta tabla habilita el flujo tipo Google
-- Docs: el admin escribe un mail desde "Configurar espacio", se guarda una
-- invitación de un solo uso y sale un correo con el link de aceptación.
--
-- El token viaja en la URL y acá queda solo su SHA-256, así que leer la tabla
-- no alcanza para reconstruir el link. Al aceptar se crea la fila en
-- industry_users con el rol y el permiso que definió el admin.
--
-- El proyecto no usa migraciones de CodeIgniter: correr este archivo a mano y
-- volcarlo al dump principal (sql/prevcs 25-8-26.sql).

CREATE TABLE IF NOT EXISTS `space_invitations` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `industry_id` int(11)      NOT NULL,
  `email`       varchar(254) NOT NULL COMMENT 'Destinatario de la invitacion, en minusculas',
  `role`        enum('admin_principal','supervisor') NOT NULL DEFAULT 'supervisor',
  `permission`  enum('read','edit')                  NOT NULL DEFAULT 'read',
  `token_hash`  char(64)     NOT NULL COMMENT 'SHA-256 hex del token que viaja en el link',
  `invited_by`  int(11)          NULL COMMENT 'users.id del admin que invito',
  `created_at`  datetime         NULL,
  `expires_at`  datetime         NULL COMMENT 'Vence a los 7 dias',
  `accepted_at` datetime         NULL,
  `accepted_by` int(11)          NULL COMMENT 'users.id que acepto',
  PRIMARY KEY (`id`),
  UNIQUE KEY `space_invitations_token` (`token_hash`),
  KEY `space_invitations_space_email` (`industry_id`, `email`),
  CONSTRAINT `space_invitations_industry`
    FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
