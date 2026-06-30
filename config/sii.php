<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Gateway Chile – Integración SII (V2)
    |--------------------------------------------------------------------------
    |
    | Proveedor: API Gateway Chile V2 (https://apigateway.cl)
    | Modalidad: Pay-per-use (créditos no vencen)
    | Panel:     https://app.apigateway.cl
    |
    | Arquitectura V2:
    |   - Se hace POST a  https://apigateway.cl/api/v2/sii/...
    |   - Auth:  Authorization: Token {SII_API_KEY}
    |   - Body:  {"auth": {"pass": {"rut": "...", "clave": "..."}}}
    |   - El Token es el "Token de Conexión" que aparece en app.apigateway.cl
    |
    | Endpoints utilizados:
    |   - RCV Compras:   POST /rcv/compras/detalle/{rut}/{periodo}/{tipo}/REGISTRO
    |   - Contribuyente: POST /contribuyentes/{rut}
    |
    | Variables .env requeridas:
    |   SII_API_URL         → URL base V2 (default correcto abajo)
    |   SII_API_KEY         → Token de Conexión de app.apigateway.cl
    |   SII_RUT_EMPRESA     → RUT completo con DV (ej: 77848621-0)
    |   SII_DV_EMPRESA      → Dejar vacío (DV ya incluido en SII_RUT_EMPRESA)
    |   SII_CLAVE_TRIBUTARIA→ Clave tributaria SII del contribuyente
    |
    */

    'api_url'         => env('SII_API_URL', 'https://app.apigateway.cl/api/v2/sii'),
    'api_key'         => env('SII_API_KEY', ''),
    'rut_empresa'     => env('SII_RUT_EMPRESA', ''),
    'dv_empresa'      => env('SII_DV_EMPRESA', ''),
    'clave_tributaria'=> env('SII_CLAVE_TRIBUTARIA', ''),
    'ambiente'        => env('SII_AMBIENTE', 'produccion'),

    /*
    |--------------------------------------------------------------------------
    | Timeout y reintentos
    |--------------------------------------------------------------------------
    */
    '