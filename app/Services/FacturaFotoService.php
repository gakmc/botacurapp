<?php

namespace App\Services;

use App\Egreso;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Lee facturas impresas desde fotos (o PDF) con Claude y las corrobora contra
 * el registro de compras del SII (egresos fuente=sii, por RUT emisor + folio).
 *
 * PHP 7.3 no admite el SDK oficial de Anthropic: se usa la API HTTP directa
 * con Guzzle, igual que BotAprendizajeService.
 */
class FacturaFotoService
{
    const MODELO = 'claude-opus-5-5';
    const LADO_MAX = 2000; // px; una factura se lee bien y la imagen queda liviana

    const PROMPT = <<<TXT
Estas imágenes son una factura (u otro documento tributario chileno) recibida por Botacura, un centro recreativo. Puede venir en varias fotos.
Transcribe exactamente lo que dice el documento:
- RUT del emisor con dígito verificador (formato 12345678-9, sin puntos), razón social, folio y fecha de emisión (YYYY-MM-DD).
- Montos en pesos chilenos como enteros: neto, exento, IVA y total.
- Cada línea de detalle: código si existe, descripción tal como está impresa, cantidad, unidad, precio unitario, descuento y monto de la línea.
Si un dato no se ve o no se lee con seguridad, déjalo en null; no lo inventes ni lo calcules. Anota en observaciones lo que esté borroso, cortado o dudoso.
TXT;

    /** Lee el documento. $archivos = rutas relativas a storage/app. */
    public function extraer(array $archivos)
    {
        $contenido = [];
        foreach ($archivos as $ruta) {
            $abs = Storage::path($ruta);
            $mime = mime_content_type($abs) ?: 'image/jpeg';
            if ($mime === 'application/pdf') {
                $contenido[] = ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode(file_get_contents($abs))]];
            } else {
                list($data, $mime) = $this->imagenLiviana($abs, $mime);
                $contenido[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($data)]];
            }
        }
        $contenido[] = ['type' => 'text', 'text' => self::PROMPT];

        $client = new Client(['timeout' => 180]);
        $res = $client->post('https://api.anthropic.com/v1/messages', [
            'http_errors' => false,
            'headers' => [
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                // Si el modelo rechaza la solicitud, el servidor la reintenta en otro modelo
                'anthropic-beta'    => 'server-side-fallback-2026-07-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'         => self::MODELO,
                'max_tokens'    => 16000,
                'fallbacks'     => 'default',
                'output_config' => ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => self::esquema()]],
                'messages'      => [['role' => 'user', 'content' => $contenido]],
            ],
        ]);

        $j = json_decode((string) $res->getBody(), true);
        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException('Claude ' . $res->getStatusCode() . ': ' . ($j['error']['message'] ?? substr((string) $res->getBody(), 0, 300)));
        }
        if (($j['stop_reason'] ?? null) === 'refusal') {
            throw new \RuntimeException('El modelo no procesó la imagen (' . ($j['stop_details']['category'] ?? 'sin categoría') . ').');
        }
        if (($j['stop_reason'] ?? null) === 'max_tokens') {
            throw new \RuntimeException('La factura es demasiado larga para leerla de una vez; súbela en menos fotos por vez.');
        }
        $texto = '';
        foreach ($j['content'] ?? [] as $b) if (($b['type'] ?? '') === 'text') $texto .= $b['text'];
        $datos = json_decode($texto, true);
        if (! is_array($datos)) {
            throw new \RuntimeException('Respuesta no legible del modelo.');
        }
        Log::info('FacturaFoto leída', ['modelo' => $j['model'] ?? null, 'tokens' => $j['usage'] ?? null]);
        return $datos;
    }

    /** Compara lo leído con el registro de compras del SII y con la suma de las líneas. */
    public function verificar(array $d)
    {
        $v = ['egreso_id' => null, 'estado' => 'no_encontrada', 'diferencias' => [], 'suma_lineas' => null];

        $suma = 0;
        foreach ($d['lineas'] ?? [] as $l) $suma += (float) ($l['monto'] ?? 0);
        $v['suma_lineas'] = (int) round($suma);
        // El exento a veces viene como línea y a veces solo en los totales: vale cuadrar con cualquiera de los dos
        $neto = (int) ($d['neto'] ?? 0);
        $base = $neto + (int) ($d['exento'] ?? 0);
        $cuadra = function ($ref) use ($suma) { return abs($suma - $ref) <= max(2, $ref * 0.005); };
        if ($base > 0 && ! $cuadra($neto) && ! $cuadra($base)) {
            $v['diferencias'][] = 'La suma de las líneas ($' . number_format($suma, 0, ',', '.') . ') no cuadra con el neto ($' . number_format($neto, 0, ',', '.') . ($base !== $neto ? ' o neto + exento $' . number_format($base, 0, ',', '.') : '') . '): puede faltar una línea o estar mal leída.';
        }

        $rut = strtoupper(str_replace(['.', ' '], '', (string) ($d['rut_emisor'] ?? '')));
        $folio = preg_replace('/\D/', '', (string) ($d['folio'] ?? ''));
        if (! $rut || ! $folio) {
            $v['estado'] = 'sin_datos';
            $v['diferencias'][] = 'No se pudo leer el RUT del emisor o el folio: no se puede buscar en el SII.';
            return $v;
        }

        $eg = Egreso::where('fuente', 'sii')->where('numero_documento', $folio)
            ->whereHas('proveedor', function ($p) use ($rut) { $p->whereRaw("UPPER(REPLACE(REPLACE(rut, '.', ''), ' ', '')) = ?", [$rut]); })
            ->orderByDesc('total')->first();
        if (! $eg) {
            $v['diferencias'][] = 'Esta factura aún no aparece en el registro de compras del SII (se sincroniza los lunes). Vuelve a verificarla después.';
            return $v;
        }

        $v['egreso_id'] = $eg->id;
        $sii = ['total' => (int) $eg->total, 'iva' => (int) $eg->iva, 'neto' => (int) $eg->total - (int) $eg->iva];
        $v['sii'] = $sii;
        foreach (['total' => 'Total', 'iva' => 'IVA'] as $k => $nombre) {
            if (isset($d[$k]) && $d[$k] !== null && abs((int) $d[$k] - $sii[$k]) > 1) {
                $v['diferencias'][] = "{$nombre} de la foto (\$" . number_format($d[$k], 0, ',', '.') . ") distinto al del SII (\$" . number_format($sii[$k], 0, ',', '.') . ').';
            }
        }
        // El modelo Egreso formatea la fecha (d-m-Y); se compara el valor guardado en la base
        $fechaSii = substr((string) ($eg->getAttributes()['fecha_egreso'] ?? ''), 0, 10);
        if (! empty($d['fecha_emision']) && $fechaSii && $d['fecha_emision'] !== $fechaSii) {
            $v['diferencias'][] = 'Fecha de la foto (' . $d['fecha_emision'] . ') distinta a la del SII (' . $fechaSii . ').';
        }
        $v['estado'] = $v['diferencias'] ? 'con_diferencias' : 'coincide';
        return $v;
    }

    /** Guarda las líneas confirmadas en compra_items (no pisa las que ya vinieron por XML). */
    /** [rut, dte, folio] con que el documento queda en compra_items. */
    public static function claveDocumento(array $d)
    {
        return [
            strtoupper(str_replace(['.', ' '], '', (string) ($d['rut_emisor'] ?? ''))),
            ['factura' => 33, 'factura_exenta' => 34, 'nota_credito' => 61, 'boleta' => 39, 'guia_despacho' => 52][$d['tipo_documento'] ?? 'factura'] ?? 33,
            preg_replace('/\D/', '', (string) ($d['folio'] ?? '')),
        ];
    }

    public function guardarLineas($facturaFotoId, array $d, $egresoId)
    {
        list($rut, $dte, $folio) = self::claveDocumento($d);
        if (DB::table('compra_items')->where('rut_emisor', $rut)->where('dte', $dte)->where('folio', $folio)->exists()) {
            return 0; // ya está (por XML o por una foto anterior)
        }
        $signo = $dte === 61 ? -1 : 1;
        $filas = [];
        foreach ($d['lineas'] ?? [] as $n => $l) {
            if (trim((string) ($l['descripcion'] ?? '')) === '') continue;
            $filas[] = [
                'egreso_id' => $egresoId, 'rut_emisor' => $rut, 'razon_social' => $d['razon_social'] ?? null,
                'dte' => $dte, 'folio' => $folio, 'fecha' => $d['fecha_emision'] ?: now()->toDateString(), 'linea' => $n + 1,
                'codigo' => isset($l['codigo']) && $l['codigo'] !== '' ? mb_substr($l['codigo'], 0, 60) : null,
                'nombre' => mb_substr($l['descripcion'], 0, 255),
                'cantidad' => $l['cantidad'] ?? null, 'unidad' => isset($l['unidad']) ? mb_substr($l['unidad'], 0, 20) : null,
                'precio' => $l['precio_unitario'] ?? null, 'monto' => $signo * abs((float) ($l['monto'] ?? 0)),
                'origen' => 'foto', 'factura_foto_id' => $facturaFotoId,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        if ($filas) DB::table('compra_items')->insert($filas);
        return count($filas);
    }

    /** Reduce la foto a LADO_MAX px en JPEG (las fotos de celular pesan varios MB). */
    private function imagenLiviana($abs, $mime)
    {
        $info = @getimagesize($abs);
        if (! $info || ! function_exists('imagecreatefromstring')) return [file_get_contents($abs), $mime];
        list($w, $h) = $info;
        $img = @imagecreatefromstring(file_get_contents($abs));
        if (! $img) return [file_get_contents($abs), $mime];
        if (function_exists('exif_read_data') && $mime === 'image/jpeg') {
            $o = (@exif_read_data($abs)['Orientation'] ?? 1);
            if ($o == 3) $img = imagerotate($img, 180, 0);
            if ($o == 6) { $img = imagerotate($img, -90, 0); list($w, $h) = [$h, $w]; }
            if ($o == 8) { $img = imagerotate($img, 90, 0); list($w, $h) = [$h, $w]; }
        }
        $f = min(1, self::LADO_MAX / max($w, $h));
        if ($f < 1) {
            $nueva = imagecreatetruecolor((int) ($w * $f), (int) ($h * $f));
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, (int) ($w * $f), (int) ($h * $f), $w, $h);
            imagedestroy($img);
            $img = $nueva;
        }
        ob_start();
        imagejpeg($img, null, 85);
        imagedestroy($img);
        return [ob_get_clean(), 'image/jpeg'];
    }

    private static function esquema()
    {
        $n = function ($t) { return ['type' => [$t, 'null']]; };
        return [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['es_documento_tributario', 'tipo_documento', 'rut_emisor', 'razon_social', 'folio', 'fecha_emision', 'neto', 'exento', 'iva', 'total', 'lineas', 'observaciones'],
            'properties' => [
                'es_documento_tributario' => ['type' => 'boolean'],
                'tipo_documento' => ['type' => 'string', 'enum' => ['factura', 'factura_exenta', 'boleta', 'guia_despacho', 'nota_credito', 'otro']],
                'rut_emisor' => $n('string'), 'razon_social' => $n('string'), 'folio' => $n('string'), 'fecha_emision' => $n('string'),
                'neto' => $n('integer'), 'exento' => $n('integer'), 'iva' => $n('integer'), 'total' => $n('integer'),
                'lineas' => ['type' => 'array', 'items' => [
                    'type' => 'object', 'additionalProperties' => false,
                    'required' => ['codigo', 'descripcion', 'cantidad', 'unidad', 'precio_unitario', 'descuento', 'monto'],
                    'properties' => [
                        'codigo' => $n('string'), 'descripcion' => ['type' => 'string'], 'cantidad' => $n('number'), 'unidad' => $n('string'),
                        'precio_unitario' => $n('number'), 'descuento' => $n('number'), 'monto' => $n('number'),
                    ],
                ]],
                'observaciones' => ['type' => 'string'],
            ],
        ];
    }
}
