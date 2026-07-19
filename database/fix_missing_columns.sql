-- Columnas que existen en producción pero faltan en migraciones locales
-- Usar: mysql -u root pruebas_botacura < database/fix_missing_columns.sql

SET FOREIGN_KEY_CHECKS=0;

-- programas: permite_giftcard, min_personas, solo_plataforma, wc_main_image_ids
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `permite_giftcard` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `min_personas` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  ADD COLUMN IF NOT EXISTS `solo_plataforma` tinyint(4) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `wc_main_image_ids` text NULL;

SET FOREIGN_KEY_CHECKS=1;
