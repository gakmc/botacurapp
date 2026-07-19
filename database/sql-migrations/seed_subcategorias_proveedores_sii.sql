-- ============================================================
-- Seed: subcategorias_compras → Proveedores reales SII (Mayo 2026)
-- Borrar todo y reemplazar con proveedores del RCV SII
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE subcategorias_compras;
SET FOREIGN_KEY_CHECKS = 1;

-- ── GASTOS FIJOS ─────────────────────────────────────────────
INSERT INTO subcategorias_compras (nombre, categoria_id, created_at, updated_at)
SELECT p.nombre, c.id, NOW(), NOW()
FROM (
    SELECT 'Transbank S.A'                   AS nombre UNION ALL
    SELECT 'Telefónica Móviles Chile S.A.'
) AS p
CROSS JOIN categorias_compras c WHERE c.nombre = 'Gastos Fijos';

-- ── GASTOS VARIABLES ─────────────────────────────────────────
INSERT INTO subcategorias_compras (nombre, categoria_id, created_at, updated_at)
SELECT p.nombre, c.id, NOW(), NOW()
FROM (
    SELECT 'AGRICOLA INDUSTRIAL LO VALLEDOR AASA S.A.'                             AS nombre UNION ALL
    SELECT 'DISTRIBUIDORA Y COMERCIALIZADORA DE PRODUCTOS ALIMENTICIOS COCHA LTDA' UNION ALL
    SELECT 'CENCOSUD RETAIL S.A.'                                                  UNION ALL
    SELECT 'DISTRIBUIDORA LA ESTRELLA LIMITADA'                                    UNION ALL
    SELECT 'SOCIEDAD AVICOLA RIO MAIPO LIMITADA.'                                  UNION ALL
    SELECT 'RENDIC HERMANOS S.A.'                                                  UNION ALL
    SELECT 'ELABORACIÓN DE CERVEZAS ARTESANALES ECATERINA MUÑOZ EMPRESA INDIVIDU'  UNION ALL
    SELECT 'DULCE ESPIGA SPA'                                                      UNION ALL
    SELECT 'COMERCIAL HIELO FLORIDA SPA'                                           UNION ALL
    SELECT 'Comercializadora El Mirador SA'                                        UNION ALL
    SELECT 'FERRETERIA Y MATERIALES DE CONSTRUCCION COMERCIO LTDA'                 UNION ALL
    SELECT 'MATERIALES DE CONSTRUCCION CR 29:12 LTDA'                              UNION ALL
    SELECT 'SODIMAC S.A.'                                                          UNION ALL
    SELECT 'ACTIVIDADES DE DISEÑO Y DECORACION DE INTERIORES CAMILA PAZ FERNANDA W' UNION ALL
    SELECT 'CLODOMIRA ELIZABETH WIRLOK CHANDIA'                                    UNION ALL
    SELECT 'JORGE ANTONIO PIERATTINI SANDOVAL'
) AS p
CROSS JOIN categorias_compras c WHERE c.nombre = 'Gastos Variables';

-- Verificar
SELECT sc.id, c.nombre AS categoria, sc.nombre AS subcategoria
FROM subcategorias_compras sc
JOIN categorias_compras c ON c.id = sc.categoria_id
ORDER BY c.nombre, sc.nombre;
