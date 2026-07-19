-- ============================================================
-- Seed: Programas de prueba para validar /api/disponibilidad
-- Ejecutar en phpMyAdmin → botacurapp → SQL
-- ============================================================

-- Limpiar si ya hay datos de prueba previos
DELETE FROM programas WHERE nombre_programa IN (
    'Wellness Day',
    'Wellness Plus',
    'Full Day'
);

-- Insertar los 3 programas reales con espacio_tipo configurado
INSERT INTO programas (nombre_programa, slug, valor_programa, descuento, estado, espacio_tipo, wc_product_id, created_at, updated_at)
VALUES
    ('Wellness Day',
     'wellness-day',
     75000, NULL, 'activo',
     'terraza',          -- pool flexible terraza+reposera
     NULL,
     NOW(), NOW()),

    ('Wellness Plus',
     'wellness-plus',
     95000, NULL, 'activo',
     'terraza',          -- pool flexible terraza+reposera
     NULL,
     NOW(), NOW()),

    ('Full Day',
     'full-day',
     120000, NULL, 'activo',
     'estacion_full',    -- pool fijo 5 cupos
     NULL,
     NOW(), NOW());

-- Verificar
SELECT id, nombre_programa, espacio_tipo, valor_programa FROM programas ORDER BY id;
