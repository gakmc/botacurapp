-- ============================================================
-- MIGRACIÓN 002 — Mejoras tabla egresos
-- Rama: feature/proveedores-egresos-mejoras
-- Base de datos: cbo56863_botacurapp
-- ============================================================

-- 1. Mejorar tabla egresos existente
ALTER TABLE `egresos`
  ADD COLUMN `descripcion` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'Descripción breve del egreso'
    AFTER `total`,
  ADD COLUMN `fecha_egreso` DATE NULL DEFAULT NULL
    COMMENT 'Fecha real del gasto (puede diferir de created_at)'
    AFTER `descripcion`,
  ADD COLUMN `numero_documento` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Número de factura, boleta o guía de despacho'
    AFTER `fecha_egreso`,
  ADD COLUMN `metodo_pago` ENUM('efectivo','transferencia','tarjeta_debito','tarjeta_credito','cheque','credito_proveedor') NULL DEFAULT NULL
    COMMENT 'Forma de pago del egreso'
    AFTER `numero_documento`,
  ADD COLUMN `estado` ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente'
    COMMENT 'Estado de pago del egreso'
    AFTER `metodo_pago`,
  ADD COLUMN `fuente` ENUM('manual','home_assistant','ai_scan','importacion') NOT NULL DEFAULT 'manual'
    COMMENT 'Origen del ingreso del egreso al sistema'
    AFTER `estado`,
  ADD COLUMN `observaciones` TEXT NULL DEFAULT NULL
    COMMENT 'Notas internas adicionales'
    AFTER `fuente`,
  ADD COLUMN `user_id` INT(10) UNSIGNED NULL DEFAULT NULL
    COMMENT 'Usuario que registró el egreso'
    AFTER `observaciones`;

-- 2. Índices útiles para filtros comunes
CREATE INDEX `idx_egresos_fecha`     ON `egresos` (`fecha_egreso`);
CREATE INDEX `idx_egresos_estado`    ON `egresos` (`estado`);
CREATE INDEX `idx_egresos_fuente`    ON `egresos` (`fuente`);
CREATE INDEX `idx_egresos_proveedor` ON `egresos` (`proveedor_id`);

-- 3. Mejorar tabla pagos_egresos
ALTER TABLE `pagos_egresos`
  ADD COLUMN `metodo_pago` ENUM('efectivo','transferencia','tarjeta_debito','tarjeta_credito','cheque') NULL DEFAULT NULL
    COMMENT 'Método de pago específico de esta cuota'
    AFTER `fecha_pago`,
  ADD COLUMN `comprobante_ruta` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'Ruta al comprobante de transferencia u otro documento'
    AFTER `metodo_pago`,
  ADD COLUMN `notas` VARCHAR(500) NULL DEFAULT NULL
    AFTER `comprobante_ruta`;

-- ============================================================
-- ROLLBACK
-- ============================================================
-- ALTER TABLE `egresos`
--   DROP INDEX `idx_egresos_fecha`,
--   DROP INDEX `idx_egresos_estado`,
--   DROP INDEX `idx_egresos_fuente`,
--   DROP INDEX `idx_egresos_proveedor`,
--   DROP COLUMN `descripcion`,
--   DROP COLUMN `fecha_egreso`,
--   DROP COLUMN `numero_documento`,
--   DROP COLUMN `metodo_pago`,
--   DROP COLUMN `estado`,
--   DROP COLUMN `fuente`,
--   DROP COLUMN `observaciones`,
--   DROP COLUMN `user_id`;
-- ALTER TABLE `pagos_egresos`
--   DROP COLUMN `metodo_pago`,
--   DROP COLUMN `comprobante_ruta`,
--   DROP COLUMN `notas`;
