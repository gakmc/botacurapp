-- ============================================================
-- MIGRACIÓN 005 — Tabla gas_compras
-- Base de datos: cbo56863_botacurapp (local: pruebas_botacura)
-- Rama: feature/proveedores-egresos-mejoras
-- ============================================================
-- Registra cada compra/pago de cilindros de gas al proveedor.
-- Se complementa con un registro en `egresos`.

CREATE TABLE IF NOT EXISTS `gas_compras` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Proveedor
  `proveedor_id`        BIGINT UNSIGNED NULL DEFAULT NULL  COMMENT 'FK a tabla proveedores (opcional)',
  `proveedor_nombre`    VARCHAR(150) NULL DEFAULT NULL     COMMENT 'Nombre del proveedor (desnormalizado para rapidez)',

  -- Compra
  `fecha_compra`        DATE NOT NULL,
  `valor_unitario_clp`  INT NOT NULL DEFAULT 0             COMMENT 'Precio por cilindro en CLP',
  `cantidad_cilindros`  INT NOT NULL DEFAULT 1,
  `kg_cilindro`         DECIMAL(8,2) NOT NULL DEFAULT 0    COMMENT 'Kg del cilindro (15, 45, etc.)',
  `total_clp`           INT NOT NULL DEFAULT 0             COMMENT 'valor_unitario_clp × cantidad_cilindros',

  -- Documento y notas
  `documento`           VARCHAR(120) NULL DEFAULT NULL     COMMENT 'N° de boleta o factura',
  `observacion`         TEXT NULL DEFAULT NULL,

  -- Referencia cruzada con egresos
  `egreso_id`           BIGINT UNSIGNED NULL DEFAULT NULL  COMMENT 'ID en tabla egresos',

  -- Metadatos
  `origen`              VARCHAR(50) NOT NULL DEFAULT 'home_assistant' COMMENT 'home_assistant, manual, api',
  `estado`              VARCHAR(50) NOT NULL DEFAULT 'comprado',

  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_fecha_compra`  (`fecha_compra`),
  INDEX `idx_proveedor_id`  (`proveedor_id`),
  INDEX `idx_egreso_id`     (`egreso_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Compras de cilindros de gas al proveedor, vinculadas a egresos';

-- ============================================================
-- ROLLBACK
-- ============================================================
-- DROP TABLE IF EXISTS `gas_compras`;
