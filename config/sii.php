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

    // Tipos de documento DTE a importar desde RCV compras
    'tipos_importar' => [
        33 => 'Factura Electrónica',
        34 => 'Factura No Afecta Electrónica',
        46 => 'Liquidación Factura Electrónica',
        56 => 'Nota de Débito Electrónica',
        61 => 'Nota de Crédito Electrónica',
    ],

];
