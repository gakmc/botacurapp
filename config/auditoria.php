<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Formato de hora para mostrar
    |--------------------------------------------------------------------------
    | '24h' => 01-10-2026 a las 09:00
    | '12h' => 01-10-2026 a las 9:00 am
    | Solo afecta la presentación; en BD siempre se guarda el timestamp.
    */
    'formato_hora' => env('AUDITORIA_FORMATO_HORA', '24h'),

    /*
    |--------------------------------------------------------------------------
    | Retención por categoría
    |--------------------------------------------------------------------------
    | caliente_meses: tiempo en la tabla `auditorias` antes de pasar a archivo.
    | archivo_anios:  edad máxima del registro (contada desde su creación);
    |                 al superarla se elimina de `auditorias_archivo`.
    | Solo se eliminan registros de auditoría, nunca datos del negocio.
    */
    'retencion' => [
        'financiera'  => ['caliente_meses' => 12, 'archivo_anios' => 6],
        'seguridad'   => ['caliente_meses' => 6,  'archivo_anios' => 2],
        'operacional' => ['caliente_meses' => 12, 'archivo_anios' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Campos que nunca se guardan en `cambios`
    |--------------------------------------------------------------------------
    | Red de seguridad: aunque un modelo los incluya por error en su lista
    | blanca, el servicio los descarta.
    */
    'campos_sensibles' => [
        'password',
        'remember_token',
        'api_token',
        'token',
        'rut',
        'banco',
        'tipo_cuenta_bancaria',
        'numero_cuenta_bancaria',
        'correo_personal',
        'dob',
        'telefono',
        'whatsapp_cliente',
        'correo',
        'instagram_cliente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de actor que no se registran
    |--------------------------------------------------------------------------
    | Las acciones del bot no se auditan (solo lo realizado en la plataforma).
    */
    'actores_omitidos' => ['bot'],

];
