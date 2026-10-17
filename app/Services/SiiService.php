<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * SiiService (V2)
 *
 * Cliente HTTP hacia API Gateway Chile — API V2.
 * Documentación: https://www.apigateway.cl/products/sii/rcv
 * Base URL:  https://apigateway.cl/api/v2/sii
 * Auth:      Authorization: Token {TOKEN_CONEXION}
 * Método:    POST con body {"auth": {"pass": {"rut": "...", "clave": "..."}}}
 *
 * Compatible Laravel 6 / PHP 7.2 (sin arrow fn, sin typed props, sin nullsafe).
 */
class SiiService
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $token;

    /** @var string */
    private $rut;

    /** @var string */
    private $clave;

    /** @var int */
    private $timeout;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('sii.api_url', 'https://apigateway.cl/api/v2/sii'), '/');
        $this->token    = config('sii.api_key', '');
        $this->rut      = config('sii.rut_empresa', '');      // ej: "77848621-0"
        $this->clave    = config('sii.clave_tributaria', ''); // clave SII
        $this->timeout  = (int) config('sii.timeout', 30);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PÚBLICO: RCV Compras — devuelve documentos del período
    // ─────────────────────────────────────────────────────────────────────────

    public function listarCompras($anio, $mes)
    {
        $periodo  = sprintf('%04d%02d', $anio, $mes);
        $rutPath  = $this->rut; // ej: "77848621-0"

        // Tipos de DTE que importamos (ver config/sii.php)
        $tiposImportar = array_keys(config('sii.tipos_importar', [33 => null]));

        $documentos = [];
        $errores    = [];

        // La API devuelve TODOS los documentos del periodo sin importar el tipo
        // pedido en la ruta (comprobado 2026-10-10: 33/34/46/56/61 traen los mismos
        // documentos). Se consulta una sola vez y el tipo real se toma del campo
        // 'dte' de cada documento. Antes las notas de credito entraban como
        // facturas positivas y se pagaban 5 consultas por periodo.
        try {
            $tipoRuta = $tiposImportar[0] ?? 33;
            $resp = $this->postRcv(
                "/rcv/compras/detalle/{$rutPath}/{$periodo}/{$tipoRuta}/REGISTRO"
            );
            foreach ($resp['data'] ?? [] as $doc) {
                $dte = (int) ($doc['dte'] ?? 0);
                if (! in_array($dte, $tiposImportar)) {
                    continue;
                }
                $documentos[] = $this->normalizarDocumento($doc, $dte);
            }
        } catch (\Throwable $e) {
            $errores[] = $e->getMessage();
        }

        $ok = empty($errores) || count($documentos) > 0;

        return [
            'ok'      => $ok,
            'data'    => $documentos,
            'total'   => count($documentos),
            'periodo' => $periodo,
            'error'   => $ok ? null : implode('; ', $errores),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PÚBLICO: RCV Ventas — devuelve resumen del período para F29/PPM
    // ─────────────────────────────────────────────────────────────────────────

    public function listarVentas($anio, $mes)
    {
        $periodo = sprintf('%04d%02d', $anio, $mes);
        $rut     = $this->rut;

        try {
            $resp = $this->postRcv("/rcv/ventas/resumen/{$rut}/{$periodo}");

            // La respuesta real viene como:
            //   { "data": { "respEstado": {...}, "data": [ {fila por tipo de documento}, ... ] } }
            // Cada fila trae, por tipo de documento (Factura, Boleta, Nota de
            // Crédito, etc.), sus propios rsmnMntNeto/rsmnMntIVA/rsmnMntExe/
            // rsmnMntTotal/rsmnTotDoc. Las Notas de Crédito (tipo 60 y 61)
            // restan, porque anulan/rebajan ventas ya emitidas.
            $filas = $resp['data']['data'] ?? [];

            // Fallback por si alguna vez la API devuelve un resumen plano
            // en vez de la lista de filas por tipo de documento.
            if (!is_array($filas) || empty($filas)) {
                $plano = $resp['data'] ?? $resp;
                if (is_array($plano) && (isset($plano['neto']) || isset($plano['rsmnMntNeto']))) {
                    $resumen = [
                        'neto'     => (int) ($plano['neto']     ?? $plano['rsmnMntNeto']  ?? 0),
                        'iva'      => (int) ($plano['iva']      ?? $plano['rsmnMntIVA']   ?? 0),
                        'exento'   => (int) ($plano['exento']   ?? $plano['rsmnMntExe']   ?? 0),
                        'total'    => (int) ($plano['total']    ?? $plano['rsmnMntTotal'] ?? 0),
                        'cantidad' => (int) ($plano['cantidad'] ?? $plano['rsmnTotDoc']   ?? 0),
                    ];
                    return ['ok' => true, 'resumen' => $resumen, 'error' => null];
                }

                $resumenVacio = ['neto' => 0, 'iva' => 0, 'exento' => 0, 'total' => 0, 'cantidad' => 0];
                return ['ok' => true, 'resumen' => $resumenVacio, 'error' => null];
            }

            // Tipos de documento que RESTAN de las ventas (notas de crédito).
            $tiposQueRestan = [60, 61];

            $neto = 0; $iva = 0; $exento = 0; $total = 0; $cnt = 0;

            foreach ($filas as $fila) {
                $tipo  = (int) ($fila['rsmnTipoDocInteger'] ?? 0);
                $signo = in_array($tipo, $tiposQueRestan, true) ? -1 : 1;

                $neto   += $signo * (int) ($fila['rsmnMntNeto']  ?? 0);
                $iva    += $signo * (int) ($fila['rsmnMntIVA']   ?? 0);
                $exento += $signo * (int) ($fila['rsmnMntExe']   ?? 0);
                $total  += $signo * (int) ($fila['rsmnMntTotal'] ?? 0);
                $cnt    += (int) ($fila['rsmnTotDoc'] ?? 0);
            }

            return [
                'ok'      => true,
                'resumen' => ['neto' => $neto, 'iva' => $iva, 'exento' => $exento,
                              'total' => $total, 'cantidad' => $cnt],
                'error'   => null,
            ];

        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $resumenVacio = ['neto' => 0, 'iva' => 0, 'exento' => 0, 'total' => 0, 'cantidad' => 0];
            // 404 o sin registros = 0 ventas (no es error)
            if (strpos($msg, '404') !== false || strpos($msg, 'sin registro') !== false) {
                return ['ok' => true, 'resumen' => $resumenVacio, 'error' => null];
            }
            return ['ok' => false, 'resumen' => $resumenVacio, 'error' => $msg];
        }
    }

    /**
     * Devuelve la respuesta CRUDA (sin normalizar) del resumen de ventas,
     * solo para diagnóstico: así se puede ver el nombre real de los campos
     * que manda la API cuando el parseo normal da $0 con documentos > 0.
     */
    public function debugVentasResumenCrudo($anio, $mes)
    {
        $periodo = sprintf('%04d%02d', $anio, $mes);
        $rut     = $this->rut;

        try {
            $resp = $this->postRcv("/rcv/ventas/resumen/{$rut}/{$periodo}");
            return ['ok' => true, 'raw' => $resp, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'raw' => null, 'error' => $e->getMessage()];
        }
    }

    public function buscarContribuyente($rut)
    {
        try {
            $resp = $this->postJson("/contribuyentes/{$rut}", []);
            return ['ok' => true, 'data' => $resp, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function credencialesConfiguradas()
    {
        return !empty($this->token) && !empty($this->rut) && !empty($this->clave);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVADO: HTTP con Guzzle
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST a un endpoint del RCV.
     * El body incluye siempre las credenciales SII (auth.pass).
     */
    private function postRcv($path, $extra = [])
    {
        $body = array_merge([
            'auth' => [
                'pass' => [
                    'rut'   => $this->rut,
                    'clave' => $this->clave,
                ],
            ],
        ], $extra);

        return $this->postJson($path, $body);
    }

    private function postJson($path, $body)
    {
        // Construir URL absoluta manualmente para evitar que Guzzle descarte
        // el segmento /api/v2/sii cuando el path empieza con '/'.
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $client = new Client([
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Token ' . $this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'verify' => false,
        ]);

        \Illuminate\Support\Facades\Log::info('SII API request', ['url' => $url]);

        $response = $client->post($url, ['json' => $body]);
        $body_str = (string) $response->getBody();
        $decoded  = json_decode($body_str, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Respuesta SII no es JSON válido: ' . substr($body_str, 0, 200));
        }

        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVADO: normalizar documento V2 → formato interno
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // PÚBLICO: Portal MIPYME — documentos recibidos (con detalle de productos)
    // Autentica con la persona autorizada (config sii.mipyme_rut / mipyme_clave).
    // ─────────────────────────────────────────────────────────────────────────

    public function mipymeConfigurado()
    {
        return config('sii.mipyme_rut') && config('sii.mipyme_clave') && $this->token && $this->rut;
    }

    /**
     * XML del DTE recibido (string) o excepción. Cuesta créditos de API Gateway:
     * llamar solo para documentos que aún no se han leído.
     */
    public function xmlRecibido($rutEmisor, $dte, $folio, $fechaEmision = null)
    {
        $url = rtrim($this->baseUrl, '/') . "/mipyme/recibidos/xml/{$this->rut}/{$rutEmisor}/{$dte}/{$folio}"
            . ($fechaEmision ? '?fecha_emision=' . $fechaEmision : '');
        $client = new Client([
            'timeout' => max($this->timeout, 60), 'verify' => false, 'http_errors' => false,
            'headers' => ['Authorization' => 'Token ' . $this->token, 'Content-Type' => 'application/json'],
        ]);
        \Illuminate\Support\Facades\Log::info('SII API request', ['url' => $url]);
        $res = $client->post($url, ['json' => ['auth' => ['pass' => [
            'rut' => config('sii.mipyme_rut'), 'clave' => config('sii.mipyme_clave'),
        ]]]]);
        $body = (string) $res->getBody();
        if ($res->getStatusCode() !== 200) {
            $j = json_decode($body, true);
            throw new \Exception('MIPYME ' . $res->getStatusCode() . ': ' . ($j['detail'] ?? substr($body, 0, 200)));
        }
        return $body;
    }

    /**
     * Líneas de detalle de un XML de DTE: [['nombre', 'cantidad', 'unidad', 'precio', 'monto', 'codigo'], ...]
     */
    public static function detalleXml($xmlString)
    {
        $xml = @simplexml_load_string($xmlString);
        if (! $xml) return [];
        $xml->registerXPathNamespace('s', 'http://www.sii.cl/SiiDte');
        $det = $xml->xpath('//s:Documento/s:Detalle') ?: ($xml->xpath('//Documento/Detalle') ?: []);
        $out = [];
        foreach ($det as $d) {
            $out[] = [
                'nombre'   => trim((string) $d->NmbItem),
                'cantidad' => (float) $d->QtyItem,
                'unidad'   => trim((string) $d->UnmdItem),
                'precio'   => (float) $d->PrcItem,
                'monto'    => (float) $d->MontoItem,
                'codigo'   => trim((string) ($d->CdgItem->VlrCodigo ?? '')),
            ];
        }
        return $out;
    }

    /**
     * ¿Ya existe en egresos este documento del RCV? Compara folio + RUT del
     * emisor + tipo (nota de credito = total negativo). Distintos proveedores
     * pueden repetir numero de folio; antes se comparaba solo el folio y el
     * segundo documento se perdia.
     */
    public static function egresoExiste($folio, $rut = null, $tipo = 33)
    {
        $q = \App\Egreso::where('fuente', 'sii')->where('numero_documento', (string) $folio);
        $q->where('total', in_array((int) $tipo, [60, 61], true) ? '<' : '>=', 0);
        if ($rut) {
            $rut = strtoupper(str_replace(['.', ' '], '', $rut));
            $q->where(function ($w) use ($rut) {
                $w->whereHas('proveedor', function ($p) use ($rut) {
                    $p->whereRaw("UPPER(REPLACE(REPLACE(rut, '.', ''), ' ', '')) = ?", [$rut]);
                })->orWhere('observaciones', 'like', '%RUT: ' . $rut . '%');
            });
        }
        return $q->exists();
    }

    private function normalizarDocumento($doc, $tipoNum)
    {
        $tipoNombre = config('sii.tipos_importar')[$tipoNum] ?? 'Desconocido';

        // Notas de credito (60/61) restan: se guardan con monto negativo.
        $signoDoc = in_array((int) $tipoNum, [60, 61], true) ? -1 : 1;

        return [
            'tipo_documento'  => $tipoNum,
            'tipo_nombre'     => $tipoNombre,
            'folio'           => (string) ($doc['folio']         ?? ''),
            'fecha_documento' => $doc['fecha']                   ?? null,
            'rut_emisor'      => $doc['rut']                     ?? null,
            'razon_social'    => $doc['razon_social']            ?? null,
            'monto_neto'      => $signoDoc * abs((int) ($doc['neto'] ?? 0)),
            'monto_iva'       => $signoDoc * abs((int) ($doc['iva'] ?? 0)),
            'monto_total'     => $signoDoc * abs((int) ($doc['total'] ?? 0)),
            'estado_acuse'    => $doc['fecha_acuse']             ?? null,
        ];
    }
}
