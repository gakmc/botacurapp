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

        foreach ($tiposImportar as $tipo) {
            try {
                $resp = $this->postRcv(
                    "/rcv/compras/detalle/{$rutPath}/{$periodo}/{$tipo}/REGISTRO"
                );
                $data = $resp['data'] ?? [];
                foreach ($data as $doc) {
                    $documentos[] = $this->normalizarDocumento($doc, $tipo);
                }
            } catch (\Throwable $e) {
                // Si un tipo no tiene registros, la API puede devolver 404 o array vacío.
                // Continuamos con los demás tipos.
                $errores[] = "tipo {$tipo}: " . $e->getMessage();
            }
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

    private function normalizarDocumento($doc, $tipoNum)
    {
        $tipoNombre = config('sii.tipos_importar')[$tipoNum] ?? 'Desconocido';

        return [
            'tipo_documento'  => $tipoNum,
            'tipo_nombre'     => $tipoNombre,
            'folio'           => (string) ($doc['folio']         ?? ''),
            'fecha_documento' => $doc['fecha']                   ?? null,
            'rut_emisor'      => $doc['rut']                     ?? null,
            'razon_social'    => $doc['razon_social']            ?? null,
            'monto_neto'      => (int) ($doc['neto']             ?? 0),
            'monto_iva'       => (int) ($doc['iva']              ?? 0),
            'monto_total'     => (int) ($doc['total']            ?? 0),
            'estado_acuse'    => $doc['fecha_acuse']             ?? null,
        ];
    }
}
