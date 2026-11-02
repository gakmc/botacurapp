<?php

/*
 * Planificacion de turnos y reporte del dia siguiente (17/10/2026).
 * Dias: 1 = lunes ... 7 = domingo. "hasta" = ultimo dia que trabaja (AAAA-MM-DD) o null.
 * El salario se lee del usuario en la app (por nombre); "salario" aqui solo si no se encuentra.
 */
return [
    'hora_reporte' => env('TURNOS_HORA_REPORTE', '15:00'),
    'horario'      => env('TURNOS_HORARIO', '09:30 a 19:30'),
    'margen_meta'  => 40,          // %
    'iva'          => 0.19,
    'insumos_pct'  => 15,          // % del ingreso neto (estimado hasta tener inventario)
    'consumo_por_persona' => 12500, // promedio historico abr-oct 2026
    'pago_masaje_defecto' => 10000,

    // Atencion al cliente: quienes pueden y que dias
    'atencion' => [
        ['nombre' => 'Juan Guzmán',          'dias' => [1, 2, 3, 4, 5, 6, 7]],
        ['nombre' => 'Catalina Maureira',    'dias' => [1, 2, 3, 4, 5, 6, 7]],
        ['nombre' => 'Fernando Morales',     'dias' => [1, 2, 3, 4, 5, 6, 7]],
        ['nombre' => 'Pamela Tapia',         'dias' => [4, 5, 6, 7]],
        ['nombre' => 'David Tapia',          'dias' => [4, 5, 6, 7]],
        ['nombre' => 'Francisca Bahamondes', 'dias' => [1, 2, 3, 4, 5]],
    ],

    // Cuantas personas de atencion y de cocina segun asistentes (hasta N personas => cantidad)
    'atencion_por_personas' => [10 => 2, 20 => 3, 35 => 4, 50 => 5, 9999 => 6],
    'cocina_por_personas'   => [10 => 1, 20 => 2, 35 => 3, 50 => 4, 9999 => 5],
    'salario_cocina'        => 45000,

    // Turnos fijos (no dependen de los clientes). Confirmado por Sebastian el 17/10/2026:
    // Elohim lun-vie, Camila mar-sab, Fabiola lun y jue, Juan Carlos sab y dom.
    // Gabriel y Octavio hasta el 31/10/2026.
    'fijos' => [
        1 => [['Elohim Azocar', 50000], ['Fabiola Geneus Sylvain', 45000], ['Gabriel Villar', 50000, '2026-10-31'], ['Octavio Peñaloza', 40000, '2026-10-31']],
        2 => [['Elohim Azocar', 50000], ['Camila Wirlok', 100000], ['Gabriel Villar', 50000, '2026-10-31'], ['Octavio Peñaloza', 40000, '2026-10-31']],
        3 => [['Elohim Azocar', 50000], ['Camila Wirlok', 100000], ['Gabriel Villar', 50000, '2026-10-31'], ['Octavio Peñaloza', 40000, '2026-10-31']],
        4 => [['Elohim Azocar', 50000], ['Camila Wirlok', 100000], ['Fabiola Geneus Sylvain', 45000], ['Gabriel Villar', 50000, '2026-10-31'], ['Octavio Peñaloza', 40000, '2026-10-31']],
        5 => [['Elohim Azocar', 50000], ['Camila Wirlok', 100000], ['Gabriel Villar', 50000, '2026-10-31'], ['Octavio Peñaloza', 40000, '2026-10-31']],
        6 => [['Camila Wirlok', 100000], ['Juan Carlos Moncada', 40000]],
        7 => [['Juan Carlos Moncada', 40000]],
    ],
];
