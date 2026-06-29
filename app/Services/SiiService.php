<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

/**
 * SiiService
 *
 * Cliente HTTP hacia API Gateway Chile para consultas al SII.
 * Documentación: https://apigateway.cl/docs
 *
 * Uso:
 *   $sii = new SiiService();
 *   $compras = $sii->listarCompras(2026, 6);
 *   $contribuyente = $sii->buscarContribuyente('12345678-9');
 */
class SiiService
{
    private string $baseUrl;
    private string $apiKey;
    private string $rut;
    private string $dv;
    private int    $timeout;
    private int    $retry;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('sii.api_url'), '/');
        $this->apiKey   = config('sii.api_key');
        $this->rut      = config('sii.rut_empresa');
        $this->dv       = config('sii.dv_empresa');
        $this->timeout  = config('sii.timeout', 30);
        $this->retry    = config('sii.retry', 2);
    }

    // -------------------------------------------------------------------------
    // PÚBLICO: RCV Compras
    // -------------------------------------------------------------------------

    /**
     * Lista los documentos de compra del RCV para un período dado.
     *
     * @param  int  $anio   Año (ej: 2026)
     * @param  int  $mes    Mes 1-12
     * @return array{ok: bool, data: array, error: string|null}
     */
    public function listarCompras(int $anio, int $mes): array
    {
        $periodo = sprintf('%04d%02d', $anio, $mes);

        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/rcv/listado-compras-periodo", [
                    'rut'     => $this->rutCompleto(),
                    'periodo' => $periodo,
                    'tipo'    => 'COMPRAS',
                ]);

            if ($response->failed()) {
                return $this->error(
                    "SII respondió con estado {$response->status()}",
                    $response->status()
                );
            }

            $data = $response->json();

            // Normalizar la respuesta según estructura API Gateway
            $documentos = $data['data'] ?? $data['documentos'] ?? $data ?? [];

            return [
                'ok'         => true,
                'data'       => $this->normalizarDocumentos($documentos),
                'total'      => count($documentos),
                'periodo'    => $periodo,
                'error'      => null,
            ];

        } catch (RequestException $e) {
            return $this->error('Error de conexión con SII: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return $this->error('Error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el detalle de un DTE específico.
     *
     * @param  int  $tipoDocumento  Código SII (33=factura, etc.)
     * @param  string  $folio
     * @param  string  $rutEmisor   RUT del proveedor (con DV, ej: "12345678-9")
     */
    public function detalleDte(int $tipoDocumento, string $folio, string $rutEmisor): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/dte/documentos", [
                    'rut_receptor'   => $this->rutCompleto(),
                    'rut_emisor'     => $rutEmisor,
                    'tipo_documento' => $tipoDocumento,
                    'folio'          => $folio,
                ]);

            if ($response->failed()) {
                return $this->error("Error al obtener DTE: {$response->status()}");
            }

            return [
                'ok'    => true,
                'data'  => $response->json(),
                'error' => null,
            ];

        } catch (\Throwable $e) {
            return $this->error('Error al obtener DTE: ' . $e->getMessage());
        }
    }

    /**
     * Busca datos de un contribuyente por RUT en SII.
     * Útil para verificar o autocompletar datos del proveedor.
     *
     * @param  string  $rut  Con o sin DV (ej: "12345678-9" o "12345678")
     */
    public function buscarContribuyente(string $rut): array
    {
        $rutLimpio = $this->limpiarRut($rut);

        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/contribuyentes/{$rutLimpio}");

            if ($response->status() === 404) {
                return ['ok' => false, 'data' => null, 'error' => 'Contribuyente no encontrado'];
            }

            if ($response->failed()) {
                return $this->error("Error al buscar contribuyente: {$response->status()}");
            }

            return [
                'ok'    => true,
                'data'  => $response->json(),
                'error' => null,
            ];

        } catch (\Throwable $e) {
            return $this->error('Error al buscar contribuyente: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // PRIVADO: helpers
    // -------------------------------------------------------------------------

    private function client()
    {
        return Http::withHeaders([
                'x-api-key'    => $this->apiKey,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry($this->retry, 500);
    }

    private function rutCompleto(): string
    {
        return "{$this->rut}-{$this->dv}";
    }

    private function limpiarRut(string $rut): string
    {
        // Acepta "12345678-9", "12.345.678-9", "123456789" → devuelve "12345678-9"
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($rut) > 1) {
            $dv   = strtoupper(substr($rut, -1));
            $body = substr($rut, 0, -1);
            return "{$body}-{$dv}";
        }
        return $rut;
    }

    /**
     * Normaliza los documentos al formato que usa BotacurApp internamente,
     * independiente de cómo responda API Gateway.
     */
    private function normalizarDocumentos(array $docs): array
    {
        return collect($docs)->map(function ($doc) {
            // API Gateway puede usar camelCase o snake_case según versión
            return [
                'tipo_documento'    => $doc['tipoDocumento']    ?? $doc['tipo_documento']    ?? null,
                'tipo_nombre'       => config('sii.tipos_importar')[$doc['tipoDocumento'] ?? $doc['tipo_documento'] ?? 0] ?? 'Desconocido',
                'folio'             => (string) ($doc['folio'] ?? ''),
                'fecha_documento'   => $doc['fechaDocumento']   ?? $doc['fecha_documento']   ?? null,
                'rut_emisor'        => $doc['rutEmisor']        ?? $doc['rut_emisor']        ?? null,
                'razon_social'      => $doc['razonSocial']      ?? $doc['razon_social']      ?? null,
                'monto_neto'        => (int) ($doc['montoNeto']   ?? $doc['monto_neto']   ?? 0),
                'monto_iva'         => (int) ($doc['montoIva']    ?? $doc['monto_iva']    ?? 0),
                'monto_total'       => (int) ($doc['montoTotal']  ?? $doc['monto_total']  ?? 0),
                'estado_acuse'      => $doc['estadoAcuse']      ?? $doc['estado_acuse']      ?? null,
            ];
        })->values()->all();
    }

    private function error(string $mensaje, int $status = 0): array
    {
        return [
            'ok'    => false,
            'data'  => [],
            'total' => 0,
            'error' => $mensaje,
        ];
    }

    // -------------------------------------------------------------------------
    // UTIL: validación de credenciales antes de llamar
    // -------------------------------------------------------------------------

    public function credencialesConfiguradas(): bool
    {
        return !empty($this->apiKey) && !empty($this->rut);
    }
}
