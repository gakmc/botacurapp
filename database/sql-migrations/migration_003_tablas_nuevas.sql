-- ============================================================
-- MIGRACIÓN 003 — Nuevas tablas: egreso_items, egreso_documentos, ha_webhooks
-- Rama: feature/proveedores-egresos-mejoras
-- Base de datos: cbo56863_botacurapp
-- ============================================================

-- 1. egreso_items — Líneas de detalle de una factura/boleta
CREATE TABLE IF NOT EXISTS `egreso_items` (
  `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `egreso_id`       INT(10) UNSIGNED NOT NULL,
  `descripcion`     VARCHAR(500) NOT NULL         COMMENT 'Nombre o descripción del ítem',
  `unidad`          VARCHAR(50) NULL DEFAULT NULL  COMMENT 'kg, litros, unidades, etc.',
  `cantidad`        DECIMAL(10,3) NOT NULL DEFAULT 1,
  `precio_unitario` INT(11) NOT NULL DEFAULT 0    COMMENT 'Precio sin IVA por unidad (en pesos)',
  `descuento`       INT(11) NOT NULL DEFAULT 0    COMMENT 'Descuento aplicado al ítem',
  `subtotal`        INT(11) NOT NULL DEFAULT 0    COMMENT 'cantidad * precio_unitario - descuento',
  `created_at`      TIMESTAMP NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ei_egreso`
    FOREIGN KEY (`egreso_id`) REFERENCES `egresos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_ei_egreso` (`egreso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Líneas de detalle de cada egreso (ítems de la factura)';

-- 2. egreso_documentos — Archivos adjuntos + datos extraídos por IA
CREATE TABLE IF NOT EXISTS `egreso_documentos` (
  `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `egreso_id`        INT(10) UNSIGNED NULL DEFAULT NULL
    COMMENT 'NULL mientras el egreso aún no se confirma (pre-registro)',
  `tipo`             ENUM('pdf','imagen','otro') NOT NULL DEFAULT 'imagen',
  `ruta_archivo`     VARCHAR(500) NOT NULL         COMMENT 'Ruta relativa en el servidor',
  `nombre_original`  VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nombre original del archivo subido',
  `datos_extraidos`  JSON NULL DEFAULT NULL
    COMMENT 'JSON con lo que la IA extrajo: {proveedor, fecha, numero, total, iva, items:[]}',
  `confianza`        DECIMAL(5,2) NULL DEFAULT NULL
    COMMENT 'Score de confianza de la extracción IA (0-100)',
  `procesado`        TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0=pendiente, 1=procesado por IA, 2=confirmado por usuario',
  `error_procesado`  TEXT NULL DEFAULT NULL,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ed_egreso`
    FOREIGN KEY (`egreso_id`) REFERENCES `egresos` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_ed_egreso`    (`egreso_id`),
  INDEX `idx_ed_procesado` (`procesado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Documentos adjuntos (facturas/boletas) con datos extraídos por IA';

-- 3. ha_webhooks — Dispositivos/automatizaciones de Home Assistant
CREATE TABLE IF NOT EXISTS `ha_webhooks` (
  `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100) NOT NULL
    COMMENT 'Nombre amigable del dispositivo o automatización HA',
  `token`            VARCHAR(64) NOT NULL
    COMMENT 'Token secreto único para este webhook',
  `categoria_id`     INT(10) UNSIGNED NULL DEFAULT NULL,
  `subcategoria_id`  INT(10) UNSIGNED NULL DEFAULT NULL,
  `proveedor_id`     INT(10) UNSIGNED NULL DEFAULT NULL,
  `metodo_pago`      ENUM('efectivo','transferencia','tarjeta_debito','tarjeta_credito','cheque','credito_proveedor') NULL DEFAULT NULL,
  `descripcion_auto` VARCHAR(255) NULL DEFAULT NULL,
  `activo`           TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_uso`       TIMESTAMP NULL DEFAULT NULL,
  `total_registros`  INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ha_token` (`token`),
  INDEX `idx_ha_activo` (`activo`),
  CONSTRAINT `fk_ha_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias_compras` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_ha_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Webhooks de Home Assistant para registro automático de egresos';

-- 4. ha_webhook_logs — Historial de llamadas desde HA
CREATE TABLE IF NOT EXISTS `ha_webhook_logs` (
  `id`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `webhook_id`    INT(10) UNSIGNED NOT NULL,
  `egreso_id`     INT(10) UNSIGNED NULL DEFAULT NULL,
  `payload`       JSON NULL DEFAULT NULL,
  `ip_origen`     VARCHAR(45) NULL DEFAULT NULL,
  `resultado`     ENUM('ok','error') NOT NULL DEFAULT 'ok',
  `mensaje_error` TEXT NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_hwl_webhook`
    FOREIGN KEY (`webhook_id`) REFERENCES `ha_webhooks` (`id`)
    ON DELETE CASCADE,
  INDEX `idx_hwl_webhook`   (`webhook_id`),
  INDEX `idx_hwl_resultado` (`resultado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Log de llamadas entrantes desde Home Assistant';

-- ============================================================
-- ROLLBACK
-- ============================================================
-- DROP TABLE IF EXISTS `ha_webhook_logs`;
-- DROP TABLE IF EXISTS `ha_webhooks`;
-- DROP TABLE IF EXISTS `egreso_documentos`;
-- DROP TABLE IF EXISTS `egreso_items`;
