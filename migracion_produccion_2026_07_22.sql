-- =============================================================================
-- SCRIPT DE MIGRACIÓN PRODUCCIÓN — BotacurApp
-- Generado: 2026-07-22
-- Base de datos: cbo56863_botacurapp
--
-- INSTRUCCIONES:
--   Opción A (recomendada): SSH → cd /public_html → php artisan migrate
--   Opción B (manual):      phpMyAdmin → seleccionar BD → pestaña SQL → pegar esto
--
-- Todos los ALTER usan IF NOT EXISTS (sintaxis MariaDB) — idempotente.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. jobs (cola de trabajos) — 2025_11_27
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(255)    NOT NULL,
  `payload`      LONGTEXT        NOT NULL,
  `attempts`     TINYINT UNSIGNED NOT NULL,
  `reserved_at`  INT UNSIGNED    NULL,
  `available_at` INT UNSIGNED    NOT NULL,
  `created_at`   INT UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. sueldos_pagados — bono y motivo — 2025_11_13
-- ---------------------------------------------------------------------------
ALTER TABLE `sueldos_pagados`
  ADD COLUMN IF NOT EXISTS `bono`   INT          NULL AFTER `monto`,
  ADD COLUMN IF NOT EXISTS `motivo` VARCHAR(255) NULL AFTER `bono`;

-- ---------------------------------------------------------------------------
-- 3. precios_tipos_masajes — pago_masoterapeuta — 2025_10_22
-- ---------------------------------------------------------------------------
ALTER TABLE `precios_tipos_masajes`
  ADD COLUMN IF NOT EXISTS `pago_masoterapeuta` INT NULL
    COMMENT 'Pago al masoterapeuta por masaje' AFTER `precio_pareja`;

-- ---------------------------------------------------------------------------
-- 4. ventas — renombrar columnas (imagen_abono → folio_abono) — 2025_10_14
--    Solo se ejecuta si aún existen los nombres viejos.
-- ---------------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'ventas'
    AND COLUMN_NAME  = 'imagen_abono'
);
SET @sql_rename := IF(@col_exists > 0,
  'ALTER TABLE `ventas` CHANGE `imagen_abono` `folio_abono` VARCHAR(255) NULL',
  'SELECT "ventas.imagen_abono ya no existe (ok)"'
);
PREPARE stmt FROM @sql_rename;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists2 := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'ventas'
    AND COLUMN_NAME  = 'imagen_diferencia'
);
SET @sql_rename2 := IF(@col_exists2 > 0,
  'ALTER TABLE `ventas` CHANGE `imagen_diferencia` `folio_diferencia` VARCHAR(255) NULL',
  'SELECT "ventas.imagen_diferencia ya no existe (ok)"'
);
PREPARE stmt2 FROM @sql_rename2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ---------------------------------------------------------------------------
-- 5. pagos_egresos — 2025_09_17
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pagos_egresos` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `egreso_id`         INT UNSIGNED NOT NULL,
  `folio`             VARCHAR(255) NULL,
  `monto`             INT          NOT NULL,
  `neto`              INT          NULL,
  `iva`               INT          NULL,
  `impuesto_incluido` INT          NULL,
  `fecha_pago`        DATE         NOT NULL,
  `created_at`        TIMESTAMP    NULL,
  `updated_at`        TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `pagos_egresos_egreso_id_foreign`
    FOREIGN KEY (`egreso_id`) REFERENCES `egresos`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. egresos — limpiar columnas viejas — 2025_09_17
--    (folio/neto/iva/impuesto_incluido se movieron a pagos_egresos)
--    Primero re-añadimos las columnas SII (punto 17), luego no tocamos ivas.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 7. push_subscriptions — 2026_03_24
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED  NULL,
  `endpoint`         VARCHAR(512)  NOT NULL,
  `public_key`       TEXT          NOT NULL,
  `auth_token`       TEXT          NOT NULL,
  `content_encoding` VARCHAR(255)  NULL,
  `device_name`      VARCHAR(255)  NULL,
  `created_at`       TIMESTAMP     NULL,
  `updated_at`       TIMESTAMP     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`(191)),
  KEY `push_subscriptions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8. user_impuesto — 2026_04_08
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_impuesto` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED NOT NULL,
  `retiene_impuestos`  TINYINT(1)   NOT NULL,
  `retencion_desde`    TIMESTAMP    NOT NULL,
  `created_at`         TIMESTAMP    NULL,
  `updated_at`         TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_impuesto_user_id_unique` (`user_id`),
  CONSTRAINT `user_impuesto_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. programas — estado — 2026_04_21
-- ---------------------------------------------------------------------------
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `estado` VARCHAR(255) NULL AFTER `descuento`;

-- ---------------------------------------------------------------------------
-- 10. woocommerce_orders — 2026_04_27
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `woocommerce_orders` (
  `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `wc_order_id`          INT UNSIGNED    NOT NULL,
  `wc_order_key`         VARCHAR(255)    NULL,
  `wc_product_id`        INT UNSIGNED    NULL,
  `billing_email`        VARCHAR(255)    NOT NULL,
  `billing_first_name`   VARCHAR(255)    NULL,
  `billing_last_name`    VARCHAR(255)    NULL,
  `billing_phone`        VARCHAR(255)    NULL,
  `fecha_visita_wc`      DATE            NULL,
  `fecha_reservacion_wc` DATE            NULL,
  `status`               VARCHAR(255)    NOT NULL,
  `total`                INT UNSIGNED    NULL,
  `currency`             VARCHAR(10)     NULL,
  `payment_method`       VARCHAR(255)    NULL,
  `authorization_code`   VARCHAR(255)    NULL,
  `card_number`          VARCHAR(10)     NULL,
  `payment_type`         VARCHAR(255)    NULL,
  `transaction_status`   VARCHAR(255)    NULL,
  `buy_order`            VARCHAR(255)    NULL,
  `installments_number`  TINYINT         NOT NULL DEFAULT 0,
  `procesado`            ENUM('pendiente','ok','error') NOT NULL DEFAULT 'pendiente',
  `reserva_id`           INT UNSIGNED    NULL,
  `cliente_id`           INT UNSIGNED    NULL,
  `error_detalle`        TEXT            NULL,
  `payload_raw`          JSON            NULL,
  `cantidad_personas`    SMALLINT UNSIGNED NULL COMMENT 'Suma de quantities en line_items',
  `created_at`           TIMESTAMP       NULL,
  `updated_at`           TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `woocommerce_orders_wc_order_id_unique` (`wc_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 11. programas — wc_product_id — 2026_04_27
-- ---------------------------------------------------------------------------
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `wc_product_id` INT UNSIGNED NULL
    COMMENT 'ID del producto en WooCommerce asociado a este programa' AFTER `id`;

-- ---------------------------------------------------------------------------
-- 12. programas — permite_giftcard, min_personas — 2026_05_14
-- ---------------------------------------------------------------------------
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `permite_giftcard` TINYINT(1) NOT NULL DEFAULT 0 AFTER `descuento`,
  ADD COLUMN IF NOT EXISTS `min_personas`     TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `permite_giftcard`;

-- ---------------------------------------------------------------------------
-- 13. programas — espacio_tipo — 2026_05_14 / 2026_05_27
-- ---------------------------------------------------------------------------
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `espacio_tipo`
    ENUM('estacion_economico','estacion_intermedio','estacion_full','terraza','reposera','wellness')
    NULL COMMENT 'Tipo de espacio físico que ocupa el programa' AFTER `min_personas`;

-- ---------------------------------------------------------------------------
-- 14. programas — imagen_url — 2026_05_28
-- ---------------------------------------------------------------------------
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `imagen_url` VARCHAR(255) NULL
    COMMENT 'URL pública de la infografía del programa (WhatsApp media)' AFTER `espacio_tipo`;

-- ---------------------------------------------------------------------------
-- 15. bot_conversaciones — crear tabla si no existe — 2026_05_27
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bot_conversaciones` (
  `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`            VARCHAR(255)  NOT NULL,
  `canal`                 ENUM('whatsapp','instagram') NOT NULL DEFAULT 'whatsapp',
  `paso`                  TINYINT       NOT NULL DEFAULT 0,
  `nombre_cliente`        VARCHAR(255)  NULL,
  `telefono`              VARCHAR(255)  NULL,
  `correo`                VARCHAR(255)  NULL,
  `instagram`             VARCHAR(255)  NULL,
  `genero`                VARCHAR(255)  NULL,
  `id_programa`           INT UNSIGNED  NULL,
  `cantidad_personas`     TINYINT       NULL,
  `fecha_visita`          DATE          NULL,
  `celebracion_especial`  VARCHAR(255)  NULL,
  `tipo_pago`             VARCHAR(255)  NULL,
  `incluye_masajes`       TINYINT(1)    NOT NULL DEFAULT 0,
  `incluye_menu`          TINYINT(1)    NOT NULL DEFAULT 0,
  `politicas_aceptadas`   TINYINT(1)    NOT NULL DEFAULT 0,
  `id_cliente`            INT UNSIGNED  NULL,
  `id_reserva`            INT UNSIGNED  NULL,
  `activo`                TINYINT(1)    NOT NULL DEFAULT 1,
  `motivo_cierre`         VARCHAR(255)  NULL,
  `ultimo_mensaje`        TEXT          NULL,
  `historial_json`        TEXT          NULL,
  `created_at`            TIMESTAMP     NULL,
  `updated_at`            TIMESTAMP     NULL,
  PRIMARY KEY (`id`),
  KEY `bot_conversaciones_usuario_id_index` (`usuario_id`),
  KEY `bot_conversaciones_activo_index` (`activo`),
  KEY `bot_conversaciones_usuario_id_activo_index` (`usuario_id`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existía con estructura vieja, añadir columnas que falten:
ALTER TABLE `bot_conversaciones`
  ADD COLUMN IF NOT EXISTS `correo`               VARCHAR(255) NULL AFTER `nombre_cliente`,
  ADD COLUMN IF NOT EXISTS `instagram`            VARCHAR(255) NULL AFTER `correo`,
  ADD COLUMN IF NOT EXISTS `genero`               VARCHAR(255) NULL AFTER `instagram`,
  ADD COLUMN IF NOT EXISTS `celebracion_especial` VARCHAR(255) NULL AFTER `fecha_visita`,
  ADD COLUMN IF NOT EXISTS `tipo_pago`            VARCHAR(255) NULL AFTER `celebracion_especial`,
  ADD COLUMN IF NOT EXISTS `incluye_masajes`      TINYINT(1)   NOT NULL DEFAULT 0 AFTER `tipo_pago`,
  ADD COLUMN IF NOT EXISTS `incluye_menu`         TINYINT(1)   NOT NULL DEFAULT 0 AFTER `incluye_masajes`,
  ADD COLUMN IF NOT EXISTS `politicas_aceptadas`  TINYINT(1)   NOT NULL DEFAULT 0 AFTER `incluye_menu`,
  ADD COLUMN IF NOT EXISTS `historial_json`       TEXT         NULL AFTER `ultimo_mensaje`;

-- ---------------------------------------------------------------------------
-- 16. fecha_disponibles — 2026_06_09
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fecha_disponibles` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`     DATE         NOT NULL,
  `tipo`      ENUM('regular','festivo','especial') NOT NULL DEFAULT 'regular',
  `habilitada` TINYINT(1) NOT NULL DEFAULT 1,
  `nota`      VARCHAR(255) NULL,
  `created_at` TIMESTAMP  NULL,
  `updated_at` TIMESTAMP  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha_disponibles_fecha_unique` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 17. sii_resumen_mensual — 2026_06_29
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sii_resumen_mensual` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `periodo`               CHAR(6)      NOT NULL COMMENT 'Formato YYYYMM',
  `compras_neto`          BIGINT       NOT NULL DEFAULT 0,
  `compras_iva`           BIGINT       NOT NULL DEFAULT 0,
  `compras_exento`        BIGINT       NOT NULL DEFAULT 0,
  `compras_total`         BIGINT       NOT NULL DEFAULT 0,
  `compras_cantidad`      INT          NOT NULL DEFAULT 0,
  `ventas_neto`           BIGINT       NOT NULL DEFAULT 0,
  `ventas_iva`            BIGINT       NOT NULL DEFAULT 0,
  `ventas_exento`         BIGINT       NOT NULL DEFAULT 0,
  `ventas_total`          BIGINT       NOT NULL DEFAULT 0,
  `ventas_cantidad`       INT          NOT NULL DEFAULT 0,
  `honorarios_bruto`      BIGINT       NOT NULL DEFAULT 0,
  `honorarios_retencion`  BIGINT       NOT NULL DEFAULT 0,
  `honorarios_neto`       BIGINT       NOT NULL DEFAULT 0,
  `iva_debito`            BIGINT       NOT NULL DEFAULT 0,
  `iva_credito`           BIGINT       NOT NULL DEFAULT 0,
  `iva_diferencia`        BIGINT       NOT NULL DEFAULT 0,
  `ultima_sincronizacion` TIMESTAMP    NULL,
  `created_at`            TIMESTAMP    NULL,
  `updated_at`            TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sii_resumen_mensual_periodo_unique` (`periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 18. woocommerce_orders — cantidad_personas — 2026_07_09
-- ---------------------------------------------------------------------------
ALTER TABLE `woocommerce_orders`
  ADD COLUMN IF NOT EXISTS `cantidad_personas` SMALLINT UNSIGNED NULL
    COMMENT 'Suma de quantities en line_items del pedido WC' AFTER `wc_product_id`;

-- ---------------------------------------------------------------------------
-- 19. detalles_ventas_directas — estado — 2026_07_14
-- ---------------------------------------------------------------------------
ALTER TABLE `detalles_ventas_directas`
  ADD COLUMN IF NOT EXISTS `estado` VARCHAR(255) NOT NULL DEFAULT 'por-procesar' AFTER `producto_id`;

-- ---------------------------------------------------------------------------
-- 20. egresos — periodo_sii — 2026_07_16
-- ---------------------------------------------------------------------------
ALTER TABLE `egresos`
  ADD COLUMN IF NOT EXISTS `periodo_sii` VARCHAR(7) NULL
    COMMENT 'Período SII de importación en formato YYYY-MM (ej: 2026-01)';

-- ---------------------------------------------------------------------------
-- 21. users — rut y boletea — 2026_07_17
-- ---------------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `rut`     VARCHAR(15) NULL
    COMMENT 'RUT del trabajador (ej: 21073497-K) para vincular con BTE' AFTER `name`,
  ADD COLUMN IF NOT EXISTS `boletea` TINYINT(1)  NOT NULL DEFAULT 0
    COMMENT 'True si emite Boleta de Honorarios Electrónica' AFTER `rut`;

-- ---------------------------------------------------------------------------
-- 22. reservas — estado y menu_recibido — 2026_07_17
-- ---------------------------------------------------------------------------
ALTER TABLE `reservas`
  ADD COLUMN IF NOT EXISTS `estado`        VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `menu_recibido` TINYINT(1)   NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------------
-- 23. clientes — sexo nullable — 2026_07_18
-- ---------------------------------------------------------------------------
ALTER TABLE `clientes`
  MODIFY COLUMN `sexo` VARCHAR(255) NULL;

-- ---------------------------------------------------------------------------
-- 24. reservas — fuente — 2026_07_18
-- ---------------------------------------------------------------------------
ALTER TABLE `reservas`
  ADD COLUMN IF NOT EXISTS `fuente` VARCHAR(255) NULL DEFAULT 'backoffice'
    COMMENT 'backoffice | bot_whatsapp | woocommerce';

-- ---------------------------------------------------------------------------
-- 25. *** COLUMNAS SII EN EGRESOS *** — 2026_07_22 (NUEVA MIGRACIÓN)
--     Estas columnas faltaban en el repo. Son necesarias para importación SII.
-- ---------------------------------------------------------------------------
ALTER TABLE `egresos`
  ADD COLUMN IF NOT EXISTS `descripcion`      VARCHAR(255) NULL AFTER `proveedor_id`,
  ADD COLUMN IF NOT EXISTS `fecha_egreso`     DATE         NULL AFTER `descripcion`,
  ADD COLUMN IF NOT EXISTS `numero_documento` VARCHAR(50)  NULL AFTER `fecha_egreso`,
  ADD COLUMN IF NOT EXISTS `neto`             INT          NULL AFTER `total`,
  ADD COLUMN IF NOT EXISTS `iva`              INT          NULL AFTER `neto`,
  ADD COLUMN IF NOT EXISTS `fuente`           VARCHAR(30)  NULL DEFAULT 'manual'
    COMMENT 'sii | manual | gas_iot' AFTER `iva`,
  ADD COLUMN IF NOT EXISTS `estado`           VARCHAR(20)  NULL DEFAULT 'pendiente'
    COMMENT 'pendiente | pagado | anulado' AFTER `fuente`,
  ADD COLUMN IF NOT EXISTS `observaciones`    TEXT         NULL AFTER `estado`;

-- ---------------------------------------------------------------------------
-- 26. menus — tipo_servicio — 2026_07_22 (NUEVA MIGRACIÓN)
--     Permite saber si el menú es desayuno u once (preguntado por el bot).
-- ---------------------------------------------------------------------------
ALTER TABLE `menus`
  ADD COLUMN IF NOT EXISTS `tipo_servicio` VARCHAR(20) NULL
    COMMENT 'desayuno | once | null' AFTER `alergias`;

-- ---------------------------------------------------------------------------
-- 27. reservas — cantidad_masajes_extra — 2026_02_17
-- ---------------------------------------------------------------------------
ALTER TABLE `reservas`
  ADD COLUMN IF NOT EXISTS `cantidad_masajes_extra` INT NULL;

-- ---------------------------------------------------------------------------
-- Registrar estas migraciones en la tabla migrations (para que artisan no las vuelva a correr)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2025_11_27_094644_create_jobs_table',                             99),
  ('2025_11_13_093347_add_fields_to_sueldo_pagados_table',           99),
  ('2025_10_22_095437_add_pago_masoterapeuta_on_precios_tipos_masajes_table', 99),
  ('2025_10_14_091223_rename_columns_in_ventas_table',               99),
  ('2025_09_17_100103_create_pagos_egresos_table',                   99),
  ('2025_09_17_095642_alter_egresos_drop_columns',                   99),
  ('2026_02_17_094656_add_cantidad_masaje_extra_to_reservas_table',  99),
  ('2026_03_24_091619_create_push_subscriptions_table',              99),
  ('2026_04_08_103353_create_user_impuesto_table',                   99),
  ('2026_04_21_094338_add_estado_to_programas_table',                99),
  ('2026_04_27_163034_create_woocommerce_orders_table',              99),
  ('2026_04_27_165547_add_wc_product_id_to_programas_table',        99),
  ('2026_05_14_000000_add_min_personas_to_programas_table',          99),
  ('2026_05_27_141054_create_bot_conversaciones_table',              99),
  ('2026_05_27_200000_add_espacio_tipo_to_programas_table',          99),
  ('2026_05_28_100000_add_imagen_url_to_programas_table',            99),
  ('2026_05_28_200000_add_extended_fields_to_bot_conversaciones_table', 99),
  ('2026_06_09_090138_create_fecha_disponibles_table',               99),
  ('2026_06_29_200000_create_sii_resumen_mensual_table',             99),
  ('2026_07_09_000001_add_cantidad_personas_to_woocommerce_orders',  99),
  ('2026_07_14_000000_add_estado_to_detalles_ventas_directas_table', 99),
  ('2026_07_16_100000_add_otros_subcategoria_gastos_variables',      99),
  ('2026_07_16_110000_add_periodo_sii_to_egresos_table',             99),
  ('2026_07_17_100000_add_boletea_rut_to_users_table',               99),
  ('2026_07_17_200000_add_missing_fields_to_reservas_table',         99),
  ('2026_07_18_002000_add_historial_json_to_bot_conversaciones',     99),
  ('2026_07_18_100000_bot_fields_clientes_reservas',                 99),
  ('2026_07_22_000001_add_sii_columns_to_egresos_table',            99),
  ('2026_07_22_000002_add_tipo_servicio_to_menus_table',            99);

SET FOREIGN_KEY_CHECKS = 1;

-- FIN DEL SCRIPT
