-- Interruptor "Verificacion con numero de telefono" de la vista Administrar cuenta.
ALTER TABLE `users`
    ADD COLUMN `phone_verification_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_enabled`;
