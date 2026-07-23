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
            // Intentar endpoint resumen primero
            $resp = $this->postRcv("/rcv/ventas/resumen/{$rut}/{$periodo}");

            // Extraer datos crudos de la respuesta
            $d = $resp['data'] ?? $resp;

            // Si data es un array asociativo (resumen directo)
            if (isset($d['neto']) || isset($d['monto_neto']) || isset($d['total'])) {
                $resumen = [
                    'neto'     => (int) ($d['neto']     ?? $d['monto_neto']    ?? 0),
                    'iva'      => (int) ($d['iva']       ?? $d['monto_iva']     ?? 0),
                    'exento'   => (int) ($d['exento']    ?? $d['monto_exento']  ?? 0),
                    'total'    => (int) ($d['total']     ?? $d['monto_total']   ?? 0),
                    'cantidad' => (int) ($d['cantidad']  ?? $d['count']         ?? 0),
                ];
                return ['ok' => true, 'resumen' => $resumen, 'error' => null];
            }

            // Si data es una lista de documentos, sumarlos
            $docs = is_array($d) ? $d : [];
            $neto = 0; $iva = 0; $exento = 0; $total = 0; $cnt = 0;
            foreach ($docs as $doc) {
                $neto   += (int) ($doc['neto']    ?? $doc['monto_neto']   ?? 0);
                $iva    += (int) ($doc['iva']     ?? $doc['monto_iva']    ?? 0);
                $exento += (int) ($doc['exento']  ?? $doc['monto_exento'] ?? 0);
                $total  += (int) ($doc['total']   ?? $doc['monto_total']  ?? 0);
                $cnt++;
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
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $this->timeout,
            'headers'  => [
                'Authorization' => 'Token ' . $this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'verify' => false,
        ]);

        $response = $client->post($path, ['json' => $body]);
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
