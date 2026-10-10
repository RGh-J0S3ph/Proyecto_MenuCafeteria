-- Ejecutar una sola vez después de 001_comercio.sql.
-- Los productos existentes reciben el tipo bebida; reclasificar refrigerios desde administración.
ALTER TABLE productos
    ADD COLUMN tipo ENUM('bebida', 'refrigerio') NOT NULL DEFAULT 'bebida' AFTER categoria_id;