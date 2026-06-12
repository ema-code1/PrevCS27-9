-- Permiso por miembro dentro del espacio:
--   read  -> solo puede ver registros
--   edit  -> puede editar el espacio y sus placas (p.ej. eliminar ESP32)
-- El admin_principal siempre tiene control total (se setea edit por consistencia).

ALTER TABLE industry_users
    ADD COLUMN permission ENUM('read', 'edit') NOT NULL DEFAULT 'read' AFTER role;

UPDATE industry_users SET permission = 'edit' WHERE role = 'admin_principal';
