<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * EgresoScanController
 *
 * POST /api/egresos/scan
 *   - Recibe PDF o imagen de factura/boleta
 *   - Extrae datos con IA (Claude API)
 *   - Retorna JSON estructurado para confirmar antes de guardar
 *
 * POST /api/egresos/scan/confirm
 *   - Recibe los datos confirmados por el usuario
 *   - Guarda egreso + egreso_items + egreso_documentos
 */
class EgresoScanController extends Controller
{
    // -------------------------------------------------------------------------
    // PASO 1 — Subir documento y extraer datos con IA
    // -------------------------------------------------------------------------

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'archivo.required' => 'Debes subir un archivo.',
            'archivo.mimes'    => 'Solo se aceptan PDF, JPG, PNG o WebP.',
            'archivo.max'      => 'El archivo no puede superar 10 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('archivo');
        $mime = $file->getMimeType();
        $ext  = strtolower($file->getClientOriginalExtension());

        // Guardar temporalmente con UUID
        $uuid     = \Str::uuid()->toString();
        $filename = $uuid . '.' . $ext;
        $ruta     = 'egresos_documentos/' . date('Y/m') . '/' . $filename;

        Storage::disk('local')->put($ruta, file_get_contents($file->getRealPath()));

        // Guardar registro en egreso_documentos (sin egreso_id aún)
        $docId = \DB::table('egreso_documentos')->insertGetId([
            'egreso_id'       => null,
            'tipo'            => ($ext === 'pdf') ? 'pdf' : 'imagen',
            'ruta_archivo'    => $ruta,
            'nombre_original' => $file->getClientOriginalName(),
            'procesado'       => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Extraer datos con IA
        try {
            $datos = $this->extraerConIA($file->getRealPath(), $mime);

            // Calcular confianza basada en campos encontrados
            $confianza = $this->calcularConfianza($datos);

            // Actualizar registro con datos extraídos
            \DB::table('egreso_documentos')->where('id', $docId)->update([
                'datos_extraidos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                'confianza'       => $confianza,
                'procesado'       => 1,
                'updated_at'      => now(),
            ]);

            return response()->json([
                'ok'         => true,
                'doc_id'     => $docId,
                'confianza'  => $confianza,
                'datos'      => $datos,
                'mensaje'    => 'Documento procesado correctamente. Revisa y confirma los datos.',
            ]);

        } catch (\Throwable $e) {
            \DB::table('egreso_documentos')->where('id', $docId)->update([
                'error_procesado' => $e->getMessage(),
                'procesado'       => 1,
                'updated_at'      => now(),
            ]);

            return response()->json([
                'ok'     => false,
                'doc_id' => $docId,
                'error'  => 'Error al procesar con IA: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // PASO 2 — Confirmar y guardar en base de datos
    // -------------------------------------------------------------------------

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doc_id'             => 'required|integer|exists:egreso_documentos,id',
            'tipo_documento_id'  => 'required|integer|exists:tipos_documentos,id',
            'categoria_id'       => 'required|integer|exists:categorias_compras,id',
            'subcategoria_id'    => 'required|integer|exists:subcategorias_compras,id',
            'proveedor_id'       => 'nullable|integer|exists:proveedores,id',
            'proveedor_nombre'   => 'nullable|string|max:150',
            'fecha'              => 'required|date',
            'numero_documento'   => 'nullable|string|max:100',
            'neto'               => 'required|integer|min:0',
            'iva'                => 'nullable|integer|min:0',
            'total'              => 'required|integer|min:1',
            'metodo_pago'        => 'nullable|string|in:efectivo,transferencia,tarjeta_debito,tarjeta_credito,cheque,credito_proveedor',
            'observaciones'      => 'nullable|string',
            'items'              => 'nullable|array',
            'items.*.descripcion'    => 'required_with:items|string|max:500',
            'items.*.cantidad'       => 'nullable|numeric|min:0',
            'items.*.unidad'         => 'nullable|string|max:50',
            'items.*.precio_unitario'=> 'nullable|integer|min:0',
            'items.*.subtotal'       => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        \DB::beginTransaction();
        try {
            // Crear egreso principal
            $egresoId = \DB::table('egresos')->insertGetId([
                'tipo_documento_id' => $request->tipo_documento_id,
                'categoria_id'      => $request->categoria_id,
                'subcategoria_id'   => $request->subcategoria_id,
                'proveedor_id'      => $request->proveedor_id,
                'neto'              => $request->neto,
                'iva'               => $request->iva ?? 0,
                'impuesto_incluido' => $request->impuesto_incluido ?? 0,
                'total'             => $request->total,
                'fecha'             => $request->fecha,
                // Columnas de migration_002
                'descripcion'       => $request->descripcion ?? $this->generarDescripcion($request),
                'fecha_egreso'      => $request->fecha,
                'numero_documento'  => $request->numero_documento,
                'metodo_pago'       => $request->metodo_pago,
                'estado'            => 'pendiente',
                'fuente'            => 'ai_scan',
                'observaciones'     => $request->observaciones,
                'user_id'           => auth()->id(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // Guardar ítems si vienen
            if ($request->items && count($request->items) > 0) {
                $items = array_map(function ($item) use ($egresoId) {
                    return [
                        'egreso_id'      => $egresoId,
                        'descripcion'    => $item['descripcion'],
                        'unidad'         => $item['unidad'] ?? null,
                        'cantidad'       => $item['cantidad'] ?? 1,
                        'precio_unitario'=> (int) ($item['precio_unitario'] ?? 0),
                        'descuento'      => (int) ($item['descuento'] ?? 0),
                        'subtotal'       => (int) ($item['subtotal'] ?? 0),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }, $request->items);
                \DB::table('egreso_items')->insert($items);
            }

            // Vincular documento al egreso
            \DB::table('egreso_documentos')->where('id', $request->doc_id)->update([
                'egreso_id'  => $egresoId,
                'procesado'  => 2, // Confirmado por usuario
                'updated_at' => now(),
            ]);

            \DB::commit();

            return response()->json([
                'ok'        => true,
                'egreso_id' => $egresoId,
                'mensaje'   => 'Egreso registrado correctamente.',
            ], 201);

        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json([
                'ok'    => false,
                'error' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // IA — Extracción de datos de la factura/boleta
    // -------------------------------------------------------------------------

    private function extraerConIA(string $rutaArchivo, string $mime): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY no configurado en .env');
        }

        // Leer archivo y convertir a base64
        $contenido = file_get_contents($rutaArchivo);
        $base64    = base64_encode($contenido);

        // Determinar media_type para Claude
        $mediaType = match(true) {
            str_contains($mime, 'pdf')  => 'application/pdf',
            str_contains($mime, 'png')  => 'image/png',
            str_contains($mime, 'webp') => 'image/webp',
            default                     => 'image/jpeg',
        };

        $prompt = <<<'PROMPT'
Analiza esta factura o boleta chilena y extrae los datos en formato JSON estricto.

Responde ÚNICAMENTE con el JSON, sin texto adicional ni markdown.

Formato exacto:
{
  "tipo_documento": "factura" | "boleta" | "guia_despacho" | "otro",
  "numero_documento": "string o null",
  "fecha_emision": "YYYY-MM-DD o null",
  "fecha_vencimiento": "YYYY-MM-DD o null",
  "proveedor": {
    "nombre": "string",
    "rut": "string o null",
    "direccion": "string o null",
    "giro": "string o null"
  },
  "cliente": {
    "nombre": "string o null",
    "rut": "string o null"
  },
  "items": [
    {
      "codigo": "string o null",
      "descripcion": "string",
      "unidad": "string o null (ej: kg, un, lt)",
      "cantidad": number,
      "precio_unitario": number (entero en pesos CLP, sin puntos ni $),
      "descuento": number (0 si no hay),
      "subtotal": number (entero en pesos CLP)
    }
  ],
  "totales": {
    "neto": number (entero, monto neto sin IVA),
    "iva_porcentaje": 19,
    "iva_monto": number (entero),
    "descuento_total": number (0 si no hay),
    "otros_impuestos": [
      { "descripcion": "string", "porcentaje": number, "monto": number }
    ],
    "total": number (entero, monto total a pagar)
  },
  "metodo_pago": "transferencia" | "cheque" | "credito" | "efectivo" | null,
  "observaciones": "string o null"
}

Notas importantes para facturas chilenas:
- Los montos son en pesos CLP (enteros, sin decimales)
- El IVA es 19% del monto neto
- Puede haber impuestos adicionales (ej: 5% retención carne, impuesto adicional bebidas)
- El número de documento es el folio de la factura/boleta
- El RUT tiene formato XX.XXX.XXX-X
- Las fechas están en español (ej: "11 de Junio de 2026" → "2026-06-11")
PROMPT;

        // Llamar a Claude API via Guzzle
        $client = new \GuzzleHttp\Client(['timeout' => 60]);

        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-opus-4-5',
                'max_tokens' => 2048,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'   => 'text',
                                'text'   => $prompt,
                            ],
                            [
                                'type'      => 'document',
                                'source'    => [
                                    'type'       => 'base64',
                                    'media_type' => $mediaType,
                                    'data'       => $base64,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $texto = $body['content'][0]['text'] ?? '';

        // Parsear el JSON extraído
        // Limpiar posibles marcadores markdown
        $texto = preg_replace('/^```json\s*/m', '', $texto);
        $texto = preg_replace('/^```\s*$/m', '', $texto);
        $texto = trim($texto);

        $datos = json_decode($texto, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('La IA no retornó JSON válido: ' . $texto);
        }

        return $datos;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function calcularConfianza(array $datos): float
    {
        $score = 0;
        $max   = 10;

        if (!empty($datos['numero_documento']))       $score++;
        if (!empty($datos['fecha_emision']))          $score++;
        if (!empty($datos['proveedor']['nombre']))    $score++;
        if (!empty($datos['proveedor']['rut']))       $score++;
        if (!empty($datos['items']) && count($datos['items']) > 0) $score += 2;
        if (!empty($datos['totales']['neto']))        $score++;
        if (!empty($datos['totales']['iva_monto']))   $score++;
        if (!empty($datos['totales']['total']))       $score += 2;

        return round(($score / $max) * 100, 2);
    }

    private function generarDescripcion(Request $request): string
    {
        $parts = [];
        if ($request->proveedor_nombre) $parts[] = $request->proveedor_nombre;
        if ($request->numero_documento) $parts[] = 'Doc.' . $request->numero_documento;
        return implode(' – ', $parts) ?: 'Egreso via escaneo';
    }
}
