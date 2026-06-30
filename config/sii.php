<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Gateway Chile – Integración SII
    |--------------------------------------------------------------------------
    |
    | Proveedor: API Gateway Chile (https://apigateway.cl)
    | Modalidad: Pay-per-use (sin cargo mensual fijo, créditos no vencen)
    |
    | Endpoints utilizados:
    |   - RCV Compras:       GET /rcv/listado-compras-periodo
    |   - Detalle DTE:       GET /dte/documentos/{tipo}/{folio}
    |   - Contribuyente:     GET /contribuyentes/{rut}
    |
    | Variables .env requeridas:
    |   SII_API_URL        → URL base del proveedor (sin slash final)
    |   SII_API_KEY        → Token/key entregado por API Gateway
    |   SII_RUT_EMPRESA    → RUT de Botacura SIN dígito verificador (ej: 12345678)
    |   SII_DV_EMPRESA     → Dígito verificador del RUT (ej: 9)
    |   SII_AMBIENTE       → 'produccion' | 'certificacion' (default: certificacion)
    |
    */

    'api_url'         => env('SII_API_URL', 'https://apigateway.cl/api/v1/cl-sii'),
    'api_key'         => env('SII_API_KEY', ''),
    'rut_empresa'     => env('SII_RUT_EMPRESA', ''),
    'dv_empresa'      => env('SII_DV_EMPRESA', ''),
    'clave_tributaria'=> env('SII_CLAVE_TRIBUTARIA', ''),
    'ambiente'        => env('SII_AMBIENTE', 'certificacion'),

    /*
    |--------------------------------------------------------------------------
    | Timeout y reintentos
    |--------------------------------------------------------------------------
    */
    'timeout'       => 30,   // segundos
    'retry'         => 2,    // reintentos ante fallo de red

    /*
    |--------------------------------------------------------------------------
    | Tipos de documento SII que importamos
    |-------------------