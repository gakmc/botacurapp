-- ============================================================
-- MIGRACIÓN 001 — Categorías por Proveedor
-- Rama: feature/proveedores-egresos-mejoras
-- Base de datos: cbo56863_botacurapp
-- ============================================================

-- 1. Agregar campo de categoría principal al proveedor
--    (relación simple: un proveedor tiene una categoría principal)
ALTER TABLE `proveedores`
  ADD COLUMN `categoria_compra_id` INT(10) UNSIGNED NULL DEFAULT NULL
    COMMENT 'Categoría principal de compra de este proveedor'
    AFTER `correo`,
  ADD COLUMN `direccion` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'Dirección física del proveedor'
    AFTER `categoria_compra_id`,
  ADD COLUMN `contacto_nombre` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Nombre del contacto en el proveedor'
    AFTER `direccion`,
  ADD COLUMN `notas` TEXT NULL DEFAULT NULL
    COMMENT 'Observaciones internas del proveedor'
    AFTER `contacto_nombre`,
  ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=activo, 0=inactivo'
    AFTER `notas`,
  ADD CONSTRAINT `fk_proveedores_categoria`
    FOREIGN KEY (`categoria_compra_id`)
    REFERENCES `categorias_compras` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Índice para búsqueda por categoría
CREATE INDEX `idx_proveedores_categoria`
  ON `proveedores` (`categoria_compra_id`);

-- 3. Tabla pivot para proveedores con MÚLTIPLES categorías
CREATE TABLE IF NOT EXISTS `proveedor_categorias` (
  `id`                  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `proveedor_id`        INT(10) UNSIGNED NOT NULL,
  `categoria_compra_id` INT(10) UNSIGNED NOT NULL,
  `created_at`          TIMESTAMP NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proveedor_categoria` (`proveedor_id`, `categoria_compra_id`),
  CONSTRAINT `fk_pc_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_pc_categoria`
    FOREIGN KEY (`categoria_compra_id`) REFERENCES `categorias_compras` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Relación muchos-a-muchos entre proveedores y categorías de compra';

-- ============================================================
-- ROLLBACK
-- ============================================================
-- ALTER TABLE `proveedores`
--   DROP FOREIGN KEY `fk_proveedores_categoria`,
--   DROP INDEX `idx_proveedores_categoria`,
--   DROP COLUMN `categoria_compra_id`,
--   DROP COLUMN `direccion`,
--   DROP COLUMN `contacto_nombre`,
--   DROP COLUMN `notas`,
--   DROP COLUMN `activo`;
-- DROP TABLE IF EXISTS `proveedor_categorias`;
