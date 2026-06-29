-- ============================================================
-- MIGRACIÓN 004 — Tabla gas_instalaciones
-- Base de datos: cbo56863_botacura_iot (local: pruebas_botacura_iot)
-- Rama: feature/proveedores-egresos-mejoras
-- ============================================================
-- Historial operativo de instalación/cambio de cilindros de gas.
-- Registra cuándo se instaló un cilindro, dónde y cuánto duró el anterior.

CREATE TABLE IF NOT EXISTS `gas_instalaciones` (
  `id`                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Lugar donde se instaló el cilindro
  `lugar`                     ENUM('tinaja_1','tinaja_2','gas_casa','gas_cocina') NOT NULL,

  -- Fecha de la instalación actual
  `fecha_instalacion`         DATETIME NOT NULL,

  -- Fecha de la instalación anterior (para calcular duración)
  `fecha_instalacion_anterior` DATETIME NULL DEFAULT NULL,

  -- Días que duró el cilindro anterior (calculado automáticamente)
  `dias_duracion_anterior`    INT NULL DEFAULT NULL,

  -- Datos del cilindro instalado
  `valor_cilindro_clp`        INT NULL DEFAULT NULL       COMMENT 'Precio pagado por el cilindro en CLP',
  `kg_cilindro`               DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Tamaño del cilindro en kg (ej: 15, 45)',

  -- Proveedor y documento
  `proveedor_nombre`          VARCHAR(150) NULL DEFAULT NULL,
  `documento`                 VARCHAR(120) NULL DEFAULT NULL COMMENT 'N° de boleta o factura',
  `observacion`               TEXT NULL DEFAULT NULL,

  -- Referencias cruzadas con BD principal
  `gas_compra_id`             BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID en gas_compras de cbo56863_botacurapp',
  `egreso_id`                 BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID en egresos de cbo56863_botacurapp',

  -- Valor del contador al momento del cambio (para tinajas: horas; para casa/cocina: días)
  `contador_anterior_valor`   DECIMAL(12,2) NULL DEFAULT NULL,
  `contador_anterior_unidad`  VARCHAR(30) NULL DEFAULT NULL  COMMENT 'horas, dias, etc.',

  -- Metadatos
  `origen`                    VARCHAR(50) NOT NULL DEFAULT 'home_assistant' COMMENT 'home_assistant, manual, api',
  `estado`                    VARCHAR(50) NOT NULL DEFAULT 'instalado',

  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_lugar_fecha`   (`lugar`, `fecha_instalacion`),
  INDEX `idx_gas_compra_id` (`gas_compra_id`),
  INDEX `idx_egreso_id`     (`egreso_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Historial de instalaciones/cambios de cilindros de gas por lugar';

-- ============================================================
-- ROLLBACK
-- ============================================================
-- DROP TABLE IF EXISTS `gas_instalaciones`;
