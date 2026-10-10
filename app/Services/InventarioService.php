<?php

namespace App\Services;

use App\DetalleConsumo;
use App\DetalleVentaDirecta;
use App\Insumo;
use App\InventarioMovimiento;
use App\InventarioMovimientoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InventarioService
 *
 * Kardex de insumos. Reglas:
 *  - El stock de un insumo = suma de inventario_movimiento_detalles.cantidad_base
 *    (en la unidad del insumo; positivo entra, negativo sale). insumos.cantidad
 *    guarda ese stock vigente para las pantallas existentes.
 *  - Costo: promedio ponderado, neto de IVA, por unidad del insumo (insumos.valor).
 *  - Cada insumo empieza a moverse desde su primera toma de inventario
 *    (origen toma_inicial). Las ventas anteriores a esa fecha no lo descuentan.
 *  - Las ventas (detalles de consumo y de venta directa) descuentan según la
 *    receta (insumo_producto). sincronizarVenta es idempotente: calcula lo que
 *    debería estar descontado para ese detalle y registra solo la diferencia,
 *    así sirve para altas, cambios de cantidad y eliminaciones.
 *
 * Compatible Laravel 6 / PHP 7.2.
 */
class InventarioService
{
    // abreviatura => [magnitud, factor a la unidad mínima]
    const ESCALAS = [
        'lt' => ['volumen', 1000], 'l' => ['volumen', 1000], 'ml' => ['volumen', 1],
        'kg' => ['peso', 1000], 'g' => ['peso', 1], 'gr' => ['peso', 1],
    ];

    /** @var array|null id => abreviatura */
    private $unidades;

    /** @var array|null id_insumo => fecha de su toma inicial */
    private $inicios;

    // ─────────────────────────────────────────────────────────────────────────
    // Unidades
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cuántas unidades $hacia hay en 1 unidad $desde. Null si no son convertibles
     * (ej. ml -> kg, o ud -> kg).
     */
    public function factor($desde, $hacia)
    {
        if (! $desde || ! $hacia || (int) $desde === (int) $hacia) {
            return 1.0;
        }
        $u = $this->unidades();
        $a = self::ESCALAS[strtolower(trim($u[$desde] ?? ''))] ?? null;
        $b = self::ESCALAS[strtolower(trim($u[$hacia] ?? ''))] ?? null;
        if (! $a || ! $b || $a[0] !== $b[0]) {
            return null;
        }
        return $a[1] / $b[1];
    }

    public function unidades()
    {
        if ($this->unidades === null) {
            $this->unidades = DB::table('unidades_medidas')->pluck('abreviatura', 'id')->toArray();
        }
        return $this->unidades;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stock
    // ─────────────────────────────────────────────────────────────────────────

    public function stock($idInsumo)
    {
        return (float) InventarioMovimientoDetalle::where('id_insumo', $idInsumo)->sum('cantidad_base');
    }

    /** Fecha de la primera toma de cada insumo (desde ahí se descuentan ventas). */
    public function inicios()
    {
        if ($this->inicios === null) {
            $this->inicios = DB::table('inventario_movimiento_detalles as d')
                ->join('inventario_movimientos as m', 'm.id', '=', 'd.movimiento_id')
                ->where('m.origen', 'toma_inicial')
                ->groupBy('d.id_insumo')
                ->selectRaw('d.id_insumo, MIN(m.fecha) as inicio')
                ->pluck('inicio', 'id_insumo')
                ->toArray();
        }
        return $this->inicios;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Registrar movimientos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param string $tipo    entrada | salida | ajuste
     * @param string $origen  toma_inicial | toma | compra_factura | compra_manual | consumo | venta_directa | merma | ajuste_manual
     * @param array  $lineas  [['id_insumo', 'cantidad', 'id_unidad_medida'?, 'costo_unitario'?], ...]
     *                        cantidad: positiva para entrada/salida; con signo para ajuste.
     *                        costo_unitario: neto, por unidad de la línea (opcional).
     * @param array  $opts    fecha, referencia (Model), referencia_type, referencia_id,
     *                        id_user, id_sector, observacion, fijar_costo (bool)
     */
    public function registrar($tipo, $origen, array $lineas, array $opts = [])
    {
        if (! in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            throw new \InvalidArgumentException("Tipo de movimiento no válido: {$tipo}");
        }

        return DB::transaction(function () use ($tipo, $origen, $lineas, $opts) {
            $ref = $opts['referencia'] ?? null;
            $mov = InventarioMovimiento::create([
                'fecha'           => $opts['fecha'] ?? now(),
                'tipo'            => $tipo,
                'origen'          => $origen,
                'referencia_type' => $ref ? get_class($ref) : ($opts['referencia_type'] ?? null),
                'referencia_id'   => $ref ? $ref->getKey() : ($opts['referencia_id'] ?? null),
                'id_user'         => $opts['id_user'] ?? optional(auth()->user())->id,
                'id_sector'       => $opts['id_sector'] ?? null,
                'observacion'     => $opts['observacion'] ?? null,
            ]);

            foreach ($lineas as $l) {
                $insumo = Insumo::lockForUpdate()->findOrFail($l['id_insumo']);
                $unidadLinea = $l['id_unidad_medida'] ?? $insumo->id_unidad_medida;
                $f = $this->factor($unidadLinea, $insumo->id_unidad_medida);
                if ($f === null) {
                    throw new \RuntimeException("No se puede convertir la unidad de la línea a la unidad de \"{$insumo->nombre}\".");
                }

                $cantidad = (float) $l['cantidad'];
                $base = $cantidad * $f;
                if ($tipo === 'entrada') $base = abs($base);
                if ($tipo === 'salida')  $base = -abs($base);

                $stockPrev = $this->stock($insumo->id);
                $tieneCosto = isset($l['costo_unitario']) && $l['costo_unitario'] !== '' && $l['costo_unitario'] !== null;
                $costo = $tieneCosto ? ((float) $l['costo_unitario']) / $f : (float) $insumo->valor;

                if ($tieneCosto && ! empty($opts['fijar_costo'])) {
                    $insumo->valor = (int) round($costo);
                } elseif ($tieneCosto && $base > 0) {
                    // Promedio ponderado (si el stock previo es negativo o cero, manda el costo nuevo)
                    $insumo->valor = (int) round($stockPrev > 0
                        ? ($stockPrev * (float) $insumo->valor + $base * $costo) / ($stockPrev + $base)
                        : $costo);
                }

                InventarioMovimientoDetalle::create([
                    'movimiento_id'    => $mov->id,
                    'id_insumo'        => $insumo->id,
                    'cantidad'         => $cantidad,
                    'id_unidad_medida' => $unidadLinea,
                    'cantidad_base'    => $base,
                    'costo_unitario'   => $costo,
                    'costo_total'      => round($base * $costo, 2),
                ]);

                $insumo->cantidad = $stockPrev + $base;
                $insumo->save();
            }

            $this->inicios = null; // una toma puede haber creado inicios nuevos
            return $mov;
        });
    }

    /**
     * Toma de inventario: deja el stock del sistema igual a lo contado.
     * $conteos = [id_insumo => ['contado' => x, 'costo_unitario' => y|null]]
     * El primer conteo de cada insumo queda como toma_inicial (stock de partida,
     * no merma), aunque antes haya recibido alguna compra.
     */
    public function registrarToma(array $conteos, array $opts = [])
    {
        $inicial = [];
        $ajuste  = [];
        $inicios = $this->inicios();

        foreach ($conteos as $idInsumo => $c) {
            if (! isset($c['contado']) || $c['contado'] === '' || $c['contado'] === null) continue;
            $diff = (float) $c['contado'] - $this->stock($idInsumo);
            $linea = ['id_insumo' => $idInsumo, 'cantidad' => $diff, 'costo_unitario' => $c['costo_unitario'] ?? null];
            if (isset($inicios[$idInsumo])) $ajuste[] = $linea; else $inicial[] = $linea;
        }

        $movs = [];
        if ($inicial) $movs[] = $this->registrar('ajuste', 'toma_inicial', $inicial, $opts + ['fijar_costo' => true]);
        if ($ajuste)  $movs[] = $this->registrar('ajuste', 'toma', $ajuste, $opts + ['fijar_costo' => true]);
        return $movs;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ventas -> salidas por receta
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Deja descontado, para un detalle de venta, exactamente lo que indica la
     * receta x cantidad. Registra solo la diferencia con lo ya descontado.
     * $cantidad = 0 revierte (detalle eliminado).
     */
    public function sincronizarVenta($refType, $refId, $idProducto, $cantidad, $fecha, $origen)
    {
        $fecha = $fecha ? Carbon::parse($fecha) : now();
        $inicios = $this->inicios();
        if (! $inicios) return null; // aún no hay toma de inventario

        $deseado = [];
        if ($cantidad > 0 && $idProducto) {
            $receta = DB::table('insumo_producto as ip')
                ->join('insumos as i', 'i.id', '=', 'ip.id_insumo')
                ->where('ip.id_producto', $idProducto)
                ->get(['ip.id_insumo', 'ip.cantidad_insumo_usar', 'ip.id_unidad_medida', 'i.id_unidad_medida as unidad_insumo']);
            foreach ($receta as $r) {
                $inicio = $inicios[$r->id_insumo] ?? null;
                if (! $inicio || $fecha->lt(Carbon::parse($inicio))) continue;
                $f = $this->factor($r->id_unidad_medida, $r->unidad_insumo);
                if ($f === null || $r->cantidad_insumo_usar <= 0) continue; // receta con problema: ver problemasRecetas()
                $deseado[$r->id_insumo] = ($deseado[$r->id_insumo] ?? 0) - $r->cantidad_insumo_usar * $cantidad * $f;
            }
        }

        $actual = DB::table('inventario_movimiento_detalles as d')
            ->join('inventario_movimientos as m', 'm.id', '=', 'd.movimiento_id')
            ->where('m.referencia_type', $refType)->where('m.referencia_id', $refId)
            ->groupBy('d.id_insumo')
            ->selectRaw('d.id_insumo, SUM(d.cantidad_base) as total')
            ->pluck('total', 'id_insumo')->toArray();

        $lineas = [];
        $todasNegativas = true;
        foreach (array_unique(array_merge(array_keys($deseado), array_keys($actual))) as $idInsumo) {
            $delta = round(($deseado[$idInsumo] ?? 0) - (float) ($actual[$idInsumo] ?? 0), 4);
            if (abs($delta) < 0.0001) continue;
            if ($delta > 0) $todasNegativas = false;
            $lineas[] = ['id_insumo' => $idInsumo, 'cantidad' => $delta];
        }
        if (! $lineas) return null;

        return $this->registrar($todasNegativas ? 'salida' : 'ajuste', $origen, $lineas, [
            'fecha'           => $fecha,
            'referencia_type' => $refType,
            'referencia_id'   => $refId,
            'id_user'         => null,
        ]);
    }

    /**
     * Recorre todas las ventas desde la primera toma y corrige lo que falte o
     * sobre (cubre eliminaciones masivas que no disparan observers).
     */
    public function conciliarVentas()
    {
        $inicios = $this->inicios();
        if (! $inicios) return ['detalles' => 0, 'movimientos' => 0, 'errores' => 0];
        $desde = min($inicios);
        $stats = ['detalles' => 0, 'movimientos' => 0, 'errores' => 0];

        $sync = function ($type, $id, $prod, $cant, $fecha, $origen) use (&$stats) {
            $stats['detalles']++;
            try {
                if ($this->sincronizarVenta($type, $id, $prod, $cant, $fecha, $origen)) $stats['movimientos']++;
            } catch (\Throwable $e) {
                $stats['errores']++;
                Log::error('Inventario conciliar', ['ref' => "$type#$id", 'error' => $e->getMessage()]);
            }
        };

        DetalleConsumo::where('created_at', '>=', $desde)->orderBy('id')->chunk(500, function ($rows) use ($sync) {
            foreach ($rows as $d) $sync(DetalleConsumo::class, $d->id, $d->id_producto, (int) $d->cantidad_producto, $d->created_at, 'consumo');
        });
        DetalleVentaDirecta::where('created_at', '>=', $desde)->orderBy('id')->chunk(500, function ($rows) use ($sync) {
            foreach ($rows as $d) $sync(DetalleVentaDirecta::class, $d->id, $d->producto_id, (int) $d->cantidad, $d->created_at, 'venta_directa');
        });

        // Detalles eliminados sin evento (p. ej. VentaDirecta borra sus detalles en bloque)
        foreach ([[DetalleConsumo::class, 'detalles_consumos', 'consumo'], [DetalleVentaDirecta::class, 'detalles_ventas_directas', 'venta_directa']] as list($cls, $tabla, $origen)) {
            $huerfanos = DB::table('inventario_movimientos as m')
                ->leftJoin($tabla . ' as t', 't.id', '=', 'm.referencia_id')
                ->where('m.referencia_type', $cls)->whereNull('t.id')
                ->distinct()->pluck('m.referencia_id');
            foreach ($huerfanos as $id) $sync($cls, $id, null, 0, null, $origen);
        }

        return $stats;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Diagnóstico de recetas
    // ─────────────────────────────────────────────────────────────────────────

    /** Líneas de receta que no se pueden descontar, y productos vendidos sin receta. */
    public function problemasRecetas($desde = null)
    {
        $u = $this->unidades();
        $lineas = DB::table('insumo_producto as ip')
            ->join('productos as p', 'p.id', '=', 'ip.id_producto')
            ->leftJoin('insumos as i', 'i.id', '=', 'ip.id_insumo')
            ->get(['ip.id', 'p.id as id_producto', 'p.nombre as producto', 'i.id as id_insumo', 'i.nombre as insumo',
                   'ip.cantidad_insumo_usar', 'ip.id_unidad_medida', 'i.id_unidad_medida as unidad_insumo']);

        $problemas = [];
        foreach ($lineas as $l) {
            $motivo = null;
            if (! $l->id_insumo) $motivo = 'El insumo ya no existe';
            elseif ($l->cantidad_insumo_usar <= 0) $motivo = 'Cantidad en cero';
            elseif ($this->factor($l->id_unidad_medida, $l->unidad_insumo) === null)
                $motivo = 'La receta usa ' . ($u[$l->id_unidad_medida] ?? '?') . ' y el insumo se mide en ' . ($u[$l->unidad_insumo] ?? '?');
            if ($motivo) $problemas[] = (array) $l + ['motivo' => $motivo];
        }

        $desde = $desde ?: now()->subMonths(3)->toDateString();
        $sinReceta = DB::table('detalles_consumos as d')
            ->join('productos as p', 'p.id', '=', 'd.id_producto')
            ->leftJoin('insumo_producto as ip', 'ip.id_producto', '=', 'p.id')
            ->whereNull('ip.id')->where('d.created_at', '>=', $desde)
            ->groupBy('p.id', 'p.nombre')
            ->selectRaw('p.id, p.nombre, SUM(d.cantidad_producto) as vendidos')
            ->orderByDesc('vendidos')->get();

        return ['lineas' => $problemas, 'sin_receta' => $sinReceta, 'desde' => $desde];
    }
}
