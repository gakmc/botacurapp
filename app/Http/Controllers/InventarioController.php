<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\Insumo;
use App\InventarioMovimiento;
use App\InventarioMovimientoDetalle;
use App\Sector;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Inventario de insumos: stock vigente, toma física, recepción de compras,
 * merma, movimientos y kardex. La lógica vive en InventarioService.
 */
class InventarioController extends Controller
{
    const ORIGENES = [
        'toma_inicial'   => 'Toma inicial',
        'toma'           => 'Toma de inventario',
        'compra_factura' => 'Compra (factura)',
        'compra_manual'  => 'Compra (manual)',
        'consumo'        => 'Venta en consumo',
        'venta_directa'  => 'Venta directa',
        'merma'          => 'Merma',
        'ajuste_manual'  => 'Ajuste manual',
    ];

    /** @var InventarioService */
    private $inv;

    public function __construct(InventarioService $inv)
    {
        $this->inv = $inv;
        $this->middleware(function ($request, $next) {
            $u = auth()->user();
            $ok = $u && ($u->has_role(config('app.admin_role')) || $u->has_role(config('app.cocina_role'))
                || $u->has_role(config('app.jefe_local_role')) || $u->has_role(config('app.barman_role')));
            abort_unless($ok, 403);
            return $next($request);
        });
    }

    // ── Stock vigente ────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $sectorId = $request->input('sector_id');
        $provId   = $request->input('id_proveedor');
        $search   = trim((string) $request->input('search', ''));
        $inicios  = $this->inv->inicios();

        $insumos = Insumo::with(['unidadMedida', 'sector', 'proveedor'])
            ->when($sectorId, function ($q) use ($sectorId) { $q->where('id_sector', $sectorId); })
            ->when($provId === 'sin', function ($q) { $q->whereNull('id_proveedor'); })
            ->when($provId && $provId !== 'sin', function ($q) use ($provId) { $q->where('id_proveedor', $provId); })
            ->when($search !== '', function ($q) use ($search) { $q->where('nombre', 'like', "%{$search}%"); })
            ->orderBy('nombre')->get()
            // Secciones por proveedor habitual (Cencosud, Cocha, Lo Valledor…); sin proveedor al final
            ->sortBy(function ($i) { return ($i->proveedor ? '0' . $i->proveedor->nombre : '1') . '|' . $i->nombre; })->values();
        $proveedoresFiltro = \App\Proveedor::whereIn('id', Insumo::whereNotNull('id_proveedor')->distinct()->pluck('id_proveedor'))->orderBy('nombre')->get(['id', 'nombre']);

        $totalValor = 0;
        foreach ($insumos as $i) {
            $i->iniciado = isset($inicios[$i->id]);
            $i->valor_stock = $i->iniciado ? max(0, (float) $i->cantidad) * (float) $i->valor : 0;
            $totalValor += $i->valor_stock;
        }

        $sectores = Sector::orderBy('nombre')->get();
        $sinIniciar = $insumos->where('iniciado', false)->count();
        $criticos = $insumos->filter(function ($i) { return $i->iniciado && (float) $i->cantidad <= (float) $i->stock_critico; })->count();

        return view('themes.backoffice.pages.inventario.index', compact('insumos', 'sectores', 'sectorId', 'search', 'totalValor', 'sinIniciar', 'criticos', 'provId', 'proveedoresFiltro'));
    }

    // ── Toma de inventario ──────────────────────────────────────────────────
    public function tomaCreate(Request $request)
    {
        $sectorId  = $request->input('sector_id');
        $ubicacion = $request->input('ubicacion');
        $sectores = Sector::orderBy('nombre')->get();
        $ubicaciones = Insumo::whereNotNull('ubicacion')->distinct()->orderBy('ubicacion')->pluck('ubicacion');
        // Ordenado por ubicación: así se recorre la bodega al contar (Equipo 1, Equipo 3, Despensa…)
        $insumos = Insumo::with('unidadMedida')
            ->when($sectorId, function ($q) use ($sectorId) { $q->where('id_sector', $sectorId); })
            ->when($ubicacion, function ($q) use ($ubicacion) { $q->where('ubicacion', $ubicacion); })
            ->orderByRaw('ubicacion IS NULL, ubicacion')->orderBy('nombre')->get();
        $inicios = $this->inv->inicios();
        return view('themes.backoffice.pages.inventario.toma', compact('insumos', 'sectores', 'sectorId', 'inicios', 'ubicacion', 'ubicaciones'));
    }

    public function tomaStore(Request $request)
    {
        $request->validate([
            'conteo'          => 'required|array',
            'conteo.*'        => 'nullable|numeric|min:0',
            'costo'           => 'nullable|array',
            'costo.*'         => 'nullable|numeric|min:0',
            'fecha'           => 'nullable|date',
        ]);

        $conteos = [];
        foreach ($request->input('conteo', []) as $id => $contado) {
            if ($contado === null || $contado === '') continue;
            $conteos[(int) $id] = ['contado' => $contado, 'costo_unitario' => $request->input("costo.$id")];
        }
        if (! $conteos) {
            return back()->withInput()->with('error', 'No ingresaste ningún conteo.');
        }

        $movs = $this->inv->registrarToma($conteos, [
            'fecha'       => $request->input('fecha') ?: now(),
            'id_sector'   => $request->input('sector_id') ?: null,
            'observacion' => $request->input('observacion'),
        ]);

        return redirect()->route('backoffice.inventario.index', ['sector_id' => $request->input('sector_id')])
            ->with('success', 'Toma registrada: ' . count($conteos) . ' insumo(s) contados en ' . count($movs) . ' movimiento(s).');
    }

    // ── Recepción de mercadería (compras) ───────────────────────────────────
    public function recepcionCreate(Request $request)
    {
        $insumos = Insumo::with('unidadMedida')->orderBy('nombre')->get();
        $unidades = DB::table('unidades_medidas')->orderBy('id')->get();
        $egresos = Egreso::with('proveedor')
            ->where('fecha_egreso', '>=', now()->subDays(60)->toDateString())
            ->where('total', '>', 0)
            ->orderByDesc('fecha_egreso')->limit(300)->get();
        $egresoId = $request->input('egreso_id');
        return view('themes.backoffice.pages.inventario.recepcion', compact('insumos', 'unidades', 'egresos', 'egresoId'));
    }

    public function recepcionStore(Request $request)
    {
        $request->validate([
            'fecha'                      => 'required|date',
            'egreso_id'                  => 'nullable|exists:egresos,id',
            'lineas'                     => 'required|array|min:1',
            'lineas.*.id_insumo'         => 'required|exists:insumos,id',
            'lineas.*.cantidad'          => 'required|numeric|gt:0',
            'lineas.*.id_unidad_medida'  => 'required|exists:unidades_medidas,id',
            'lineas.*.costo_total'       => 'required|numeric|min:0',
        ], [
            'lineas.required' => 'Agrega al menos un insumo.',
        ]);

        $lineas = [];
        foreach ($request->input('lineas') as $l) {
            $f = $this->inv->factor($l['id_unidad_medida'], Insumo::find($l['id_insumo'])->id_unidad_medida);
            if ($f === null) {
                return back()->withInput()->with('error', 'Una línea usa una unidad que no se puede convertir a la unidad del insumo (ej. kg a unidades).');
            }
            $lineas[] = [
                'id_insumo'        => $l['id_insumo'],
                'cantidad'         => $l['cantidad'],
                'id_unidad_medida' => $l['id_unidad_medida'],
                'costo_unitario'   => $l['costo_total'] / $l['cantidad'],
            ];
        }

        $egreso = $request->input('egreso_id') ? Egreso::find($request->input('egreso_id')) : null;
        $this->inv->registrar('entrada', $egreso ? 'compra_factura' : 'compra_manual', $lineas, [
            'fecha'       => $request->input('fecha'),
            'referencia'  => $egreso,
            'observacion' => $request->input('observacion'),
        ]);

        return redirect()->route('backoffice.inventario.index')->with('success', 'Recepción registrada: ' . count($lineas) . ' insumo(s).');
    }

    // ── Merma ───────────────────────────────────────────────────────────────
    public function mermaStore(Request $request)
    {
        $request->validate([
            'id_insumo'        => 'required|exists:insumos,id',
            'cantidad'         => 'required|numeric|gt:0',
            'id_unidad_medida' => 'required|exists:unidades_medidas,id',
            'observacion'      => 'required|string|max:255',
        ], ['observacion.required' => 'Indica el motivo de la merma.']);

        $f = $this->inv->factor($request->id_unidad_medida, Insumo::find($request->id_insumo)->id_unidad_medida);
        if ($f === null) {
            return back()->with('error', 'La unidad no se puede convertir a la unidad del insumo.');
        }
        $this->inv->registrar('salida', 'merma', [[
            'id_insumo' => $request->id_insumo, 'cantidad' => $request->cantidad, 'id_unidad_medida' => $request->id_unidad_medida,
        ]], ['observacion' => $request->observacion]);

        return back()->with('success', 'Merma registrada.');
    }

    // ── Movimientos y kardex ────────────────────────────────────────────────
    public function movimientos(Request $request)
    {
        $origen = $request->input('origen');
        $desde  = $request->input('desde', now()->subDays(30)->toDateString());
        $hasta  = $request->input('hasta', now()->toDateString());

        $movs = InventarioMovimiento::with(['detalles.insumo.unidadMedida', 'usuario'])
            ->whereBetween('fecha', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->when($origen, function ($q) use ($origen) { $q->where('origen', $origen); })
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(50)->appends($request->query());

        $origenes = self::ORIGENES;
        return view('themes.backoffice.pages.inventario.movimientos', compact('movs', 'origen', 'desde', 'hasta', 'origenes'));
    }

    public function kardex(Insumo $insumo)
    {
        $detalles = InventarioMovimientoDetalle::with('movimiento')
            ->where('id_insumo', $insumo->id)
            ->join('inventario_movimientos as m', 'm.id', '=', 'inventario_movimiento_detalles.movimiento_id')
            ->orderBy('m.fecha')->orderBy('inventario_movimiento_detalles.id')
            ->select('inventario_movimiento_detalles.*')->get();

        $saldo = 0;
        foreach ($detalles as $d) { $saldo += (float) $d->cantidad_base; $d->saldo = $saldo; }
        $origenes = self::ORIGENES;
        return view('themes.backoffice.pages.inventario.kardex', compact('insumo', 'detalles', 'origenes'));
    }

    // ── Recetas con problemas y conciliación ────────────────────────────────
    public function recetas()
    {
        $p = $this->inv->problemasRecetas();
        return view('themes.backoffice.pages.inventario.recetas', $p);
    }

    public function conciliar()
    {
        $r = $this->inv->conciliarVentas();
        return back()->with($r['errores'] ? 'error' : 'success',
            "Ventas revisadas: {$r['detalles']} · Correcciones: {$r['movimientos']} · Errores: {$r['errores']}");
    }
}
