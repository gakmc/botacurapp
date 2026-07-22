<?php

namespace App\Console\Commands;

use App\Egreso;
use App\Proveedor;
use App\Services\SiiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * SiiImportarSemana
 *
 * Importa automáticamente los documentos SII del mes en curso
 * y almacena un log del resultado en storage/logs/sii_importacion.log
 *
 * Uso manual:   php artisan sii:importar-semana
 * Programado:   domingos 21:10 desde Kernel.php
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class SiiImportarSemana extends Command
{
    protected $signature   = 'sii:importar-semana
                                {--anio= : Año a importar (default: año actual)}
                                {--mes=  : Mes a importar (default: mes actual)}';

    protected $description = 'Importa automáticamente los documentos SII del mes en curso sin interacción manual';

    private $sii;

    public function __construct(SiiService $sii)
    {
        parent::__construct();
        $this->sii = $sii;
    }

    public function handle()
    {
        // Evitar que PHP mate el proceso por max_execution_time (XAMPP default = 30s)
        set_time_limit(0);

        $anio = (int) ($this->option('anio') ?: now()->year);
        $mes  = (int) ($this->option('mes')  ?: now()->month);

        $this->info('=== SII Auto-importación ' . $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . ' ===');

        if (!$this->sii->credencialesConfiguradas()) {
            $this->error('Credenciales SII no configuradas. Revisa SII_API_KEY y SII_RUT_EMPRESA en .env');
            return 1;
        }

        // ── Categoría y subcategoría por defecto ──────────────────────────────
        $catDefault = DB::table('categorias_compras')->where('nombre', 'Gastos Variables')->first();
        if ($catDefault) {
            $subCatDefault = DB::table('subcategorias_compras')
                ->where('categoria_id', $catDefault->id)
                ->where('nombre', 'Otros / Varios')
                ->first();
            if (!$subCatDefault) {
                $subCatDefault = DB::table('subcategorias_compras')
                    ->where('categoria_id', $catDefault->id)
                    ->first();
            }
        } else {
            $subCatDefault = null;
        }

        if (!$catDefault || !$subCatDefault) {
            $this->error('No existe "Gastos Variables" en subcategorias_compras. Corre el seeder primero.');
            return 1;
        }

        $catIdDef    = $catDefault->id;
        $subCatIdDef = $subCatDefault->id;

        // ── Mapa auto-match proveedor → subcategoría ──────────────────────────
        $mapaSub = DB::table('subcategorias_compras as sc')
            ->join('categorias_compras as c', 'c.id', '=', 'sc.categoria_id')
            ->select('sc.id as subcategoria_id', 'sc.categoria_id')
            ->addSelect(DB::raw('LOWER(TRIM(sc.nombre)) AS nombre_key'))
            ->get()
            ->keyBy('nombre_key');

        // ── Consulta RCV SII ──────────────────────────────────────────────────
        $resultado = $this->sii->listarCompras($anio, $mes);

        if (!$resultado['ok'] && empty($resultado['data'])) {
            $this->error('Error al consultar SII: ' . ($resultado['error'] ?? 'sin datos'));
            return 1;
        }

        $importados = 0;
        $omitidos   = 0;
        $autoMatch  = 0;
        $sinMatch   = 0;

        DB::beginTransaction();
        try {
            $periodoKey = sprintf('%04d-%02d', $anio, $mes);
            foreach ($resultado['data'] as $doc) {
                $existe = Egreso::where('fuente', 'sii')
                    ->where('periodo_sii', $periodoKey)
                    ->where('numero_documento', $doc['folio'])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

                $razonSocial = trim($doc['razon_social'] ?? '');
                $key         = mb_strtolower($razonSocial);
                $match       = isset($mapaSub[$key]) ? $mapaSub[$key] : null;

                if ($match) {
                    $catId    = $match->categoria_id;
                    $subCatId = $match->subcategoria_id;
                    $autoMatch++;
                } elseif ($razonSocial) {
                    // Auto-crear subcategoría con nombre real del proveedor
                    $nuevoSubId = DB::table('subcategorias_compras')->insertGetId([
                        'nombre'       => $razonSocial,
                        'categoria_id' => $catIdDef,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    $catId    = $catIdDef;
                    $subCatId = $nuevoSubId;
                    $mapaSub[$key] = (object)['subcategoria_id' => $nuevoSubId, 'categoria_id' => $catIdDef];
                    $sinMatch++;
                } else {
                    $catId    = $catIdDef;
                    $subCatId = $subCatIdDef;
                    $sinMatch++;
                }

                // Auto-crear proveedor si no existe
                $rutEmis     = $doc['rut_emisor'] ?? null;
                $razonSocial = trim($doc['razon_social'] ?? '');
                $proveedor   = $rutEmis ? Proveedor::where('rut', $rutEmis)->first() : null;
                if (!$proveedor && $rutEmis && $razonSocial) {
                    $proveedor = Proveedor::create([
                        'nombre' => $razonSocial,
                        'rut'    => $rutEmis,
                    ]);
                }
                $tipoDoc   = DB::table('tipo_documentos')->where('codigo', $doc['tipo_documento'])->first();

                $fechaNorm = null;
                if (!empty($doc['fecha_documento'])) {
                    try {
                        $fechaNorm = \Carbon\Carbon::parse($doc['fecha_documento'])->format('Y-m-d');
                    } catch (\Throwable $ex) {
                        $fechaNorm = null;
                    }
                }

                Egreso::create([
                    'tipo_documento_id' => $tipoDoc ? $tipoDoc->id : null,
                    'categoria_id'      => $catId,
                    'subcategoria_id'   => $subCatId,
                    'proveedor_id'      => $proveedor ? $proveedor->id : null,
                    'descripcion'       => trim(($doc['razon_social'] ?? '') . ' - Folio ' . $doc['folio']),
                    'fecha_egreso'      => $fechaNorm,
                    'numero_documento'  => $doc['folio'],
                    'neto'              => $doc['monto_neto']  ?: null,
                    'iva'               => $doc['monto_iva']   ?: null,
                    'total'             => $doc['monto_total'],
                    'fuente'            => 'sii',
                    'periodo_sii'       => $periodoKey,
                    'estado'            => 'pendiente',
                    'observaciones'     => 'Auto-importado SII RCV - RUT: ' . $doc['rut_emisor'],
                ]);

                $importados++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error al importar: ' . $e->getMessage());
            return 1;
        }

        $this->info('Importados : ' . $importados);
        $this->info('Omitidos   : ' . $omitidos . ' (ya existían)');
        $this->info('Auto-match : ' . $autoMatch);
        $this->info('Sin match  : ' . $sinMatch . ' (categoría por defecto)');

        return 0;
    }
}
