<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SII — Servicio de Impuestos Internos
    |--------------------------------------------------------------------------
    |
    | Credenciales para scraping BTE (SiiBteService) y para la API Gateway.
    |
    */

    // RUT de empresa sin dígito verificador, ej: "77848621"
    // y clave tributaria del portal SII
    'rut_empresa'      => env('SII_RUT_EMPRESA', ''),
    'clave_tributaria' => env('SII_CLAVE_TRIBUTARIA', ''),

    // API Gateway Chile (importación RCV)
    'api_url' => env('SII_API_URL', 'https://app.apigateway.cl/api/v2/sii'),
    'api_key' => env('SII_API_KEY', ''),

    // Remanente de credito fiscal IVA con que parte la cadena del F29: codigo
    // 077 del F29 del mes anterior a remanente_inicial_periodo (en pesos).
    // Vacio = no informado (se asume 0 y la pantalla F29 lo marca como supuesto).
    'remanente_inicial'         => env('SII_REMANENTE_INICIAL'),
    'remanente_inicial_periodo' => env('SII_REMANENTE_INICIAL_PERIODO', '202601'),

    // Tipos de documento DTE a importar desde RCV compras
    'tipos_importar' => [
        33 => 'Factura Electrónica',
        34 => 'Factura No Afecta Electrónica',
        46 => 'Liquidación Factura Electrónica',
        56 => 'Nota de Débito Electrónica',
        61 => 'Nota de Crédito Electrónica',
    ],

];
