<?php

namespace App\Console\Commands;

use App\Services\SiiService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lee del portal MIPYME del SII el detalle (productos) de las facturas
 * recibidas y lo guarda en compra_items. Guarda también el XML original en
 * storage/app/dte_recibidos/. Solo descarga documentos que aún no tiene, para
 * no gastar créditos de API Gateway dos veces.
 */
class SiiImportarDetalleCompras extends Command
{
    protected $signature = 'sii:importar-detalle-compras {desde : YYYY-MM-DD} {hasta : YYYY-MM-DD} {--simular}';

    protected $description = 'Importa el detalle de productos de las facturas recibidas (XML MIPYME) a compra_items';

    const TIPOS = [33, 34, 46, 56, 60, 61];

    public function handle(SiiService $sii)
    {
        if (! $sii->mipymeConfigurado()) {
            $this->error('Faltan SII_MIPYME_RUT / SII_MIPYME_CLAVE en .env');
            return 1;
        }

        $desde = Carbon::parse($this->argument('desde'))->startOfDay();
        $hasta = Carbon::parse($this->argument('hasta'))->startOfDay();
        $stats = ['documentos' => 0, 'ya_tenia' => 0, 'importados' => 0, 'lineas' => 0, 'errores' => 0];

        // El portal acepta rangos de máximo un mes
        for ($ini = $desde->copy(); $ini->lte($hasta); $ini->addMonthNoOverflow()->startOfMonth()) {
            $fin = $ini->copy()->endOfMonth()->min($hasta);
            $docs = $this->listar($ini->toDateString(), $fin->toDateString());
            $this->info("{$ini->format('Y-m')}: " . count($docs) . ' documentos en el portal');

            foreach ($docs as $d) {
                if (! in_array((int) $d['dte'], self::TIPOS, true)) continue;
                $stats['documentos']++;
                $rut = $d['rut'] . '-' . strtoupper($d['dv']);
                if (DB::table('compra_items')->where('rut_emisor', $rut)->where('dte', $d['dte'])->where('folio', (string) $d['folio'])->exists()) {
                    $stats['ya_tenia']++;
                    continue;
                }
                if ($this->option('simular')) continue;

                try {
                    $xml = $sii->xmlRecibido($rut, $d['dte'], $d['folio'], $d['fecha']);
                    Storage::put("dte_recibidos/{$rut}_{$d['dte']}_{$d['folio']}.xml", $xml);
                    $lineas = SiiService::detalleXml($xml);
                    $signo = in_array((int) $d['dte'], [60, 61], true) ? -1 : 1;
                    $egresoId = $this->egresoId($rut, $d['folio'], $d['dte']);
                    $filas = [];
                    foreach ($lineas as $n => $l) {
                        $filas[] = [
                            'egreso_id' => $egresoId, 'rut_emisor' => $rut, 'razon_social' => $d['razon_social'],
                            'dte' => $d['dte'], 'folio' => (string) $d['folio'], 'fecha' => $d['fecha'], 'linea' => $n + 1,
                            'codigo' => $l['codigo'] !== '' ? mb_substr($l['codigo'], 0, 60) : null,
                            'nombre' => mb_substr($l['nombre'] !== '' ? $l['nombre'] : '(sin nombre)', 0, 255),
                            'cantidad' => $l['cantidad'] ?: null, 'unidad' => $l['unidad'] !== '' ? mb_substr($l['unidad'], 0, 20) : null,
                            'precio' => $l['precio'] ?: null, 'monto' => $signo * abs($l['monto']),
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                    if (! $filas) {
                        // Documento sin detalle legible: se deja una línea con el total para no volver a pedirlo
                        $filas[] = ['egreso_id' => $egresoId, 'rut_emisor' => $rut, 'razon_social' => $d['razon_social'], 'dte' => $d['dte'],
                            'folio' => (string) $d['folio'], 'fecha' => $d['fecha'], 'linea' => 1, 'codigo' => null, 'nombre' => '(sin detalle en XML)',
                            'cantidad' => null, 'unidad' => null, 'precio' => null, 'monto' => $signo * abs((float) $d['total']),
                            'created_at' => now(), 'updated_at' => now()];
                    }
                    DB::table('compra_items')->insert($filas);
                    $stats['importados']++;
                    $stats['lineas'] += count($filas);
                } catch (\Throwable $e) {
                    $stats['errores']++;
                    $this->warn("  {$d['razon_social']} folio {$d['folio']}: " . substr($e->getMessage(), 0, 160));
                }
            }
        }

        $this->info(json_encode($stats));
        return $stats['errores'] ? 1 : 0;
    }

    private function listar($desde, $hasta)
    {
        $docs = [];
        $pagina = 1;
        do {
            $res = (new Client(['timeout' => 120, 'verify' => false, 'http_errors' => false, 'headers' => [
                'Authorization' => 'Token ' . config('sii.api_key'), 'Content-Type' => 'application/json', 'Accept' => 'application/json']]))
                ->post(rtrim(config('sii.api_url'), '/') . '/mipyme/recibidos/documentos/' . config('sii.rut_empresa') . '?formato=json', ['json' => [
                    'auth'    => ['pass' => ['rut' => config('sii.mipyme_rut'), 'clave' => config('sii.mipyme_clave')]],
                    'filtros' => ['NUM_PAG' => $pagina, 'FEC_DESDE' => $desde, 'FEC_HASTA' => $hasta],
                ]]);
            $j = json_decode((string) $res->getBody(), true);
            if ($res->getStatusCode() !== 200) {
                throw new \RuntimeException("Listado {$desde}..{$hasta} pág {$pagina}: HTTP " . $res->getStatusCode() . ' ' . ($j['detail'] ?? ''));
            }
            $docs = array_merge($docs, $j['data'] ?? []);
            $paginas = (int) ($j['metadata']['n_paginas'] ?? 1);
            $pagina++;
        } while ($pagina <= $paginas);
        return $docs;
    }

    private function egresoId($rut, $folio, $dte)
    {
        $rutN = strtoupper(str_replace(['.', ' '], '', $rut));
        $q = DB::table('egresos as e')->join('proveedores as p', 'p.id', '=', 'e.proveedor_id')
            ->where('e.fuente', 'sii')->where('e.numero_documento', (string) $folio)
            ->whereRaw("UPPER(REPLACE(REPLACE(p.rut, '.', ''), ' ', '')) = ?", [$rutN]);
        $q->where('e.total', in_array((int) $dte, [60, 61], true) ? '<' : '>=', 0);
        return $q->value('e.id');
    }
}
