<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\Insumo;
use App\PedidoCompra;
use App\PedidoCompraItem;
use App\Proveedor;
use App\Sector;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de compras de insumos:
 *   1. Lista de compras (sugerida por stock objetivo, agrupada por proveedor)
 *   2. Pedido por proveedor
 *   3. Compra: quien compra marca qué consiguió (no borra nada)
 *   4. Recepción a ciegas: quien ordena cuenta lo que llegó; la app compara con
 *      lo comprado, marca diferencias, liga la factura SII y suma al inventario.
 */
class CompraInsumoController extends Controller
{
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

    // ── Configurar insumos (proveedor habitual, ubicación, objetivo) ─────────
    public function configurar(Request $request)
    {
        $provId = $request->input('id_proveedor');
        $insumos = Insumo::with('unidadMedida')
            ->when($provId === 'sin', function ($q) { $q->whereNull('id_proveedor'); })
            ->when($provId && $provId !== 'sin', function ($q) use ($provId) { $q->where('id_proveedor', $provId); })
            ->orderBy('nombre')->get();
        $proveedores = $this->proveedoresInsumos();
        $sectores = Sector::orderBy('nombre')->get();
        $unidades = DB::table('unidades_medidas')->orderBy('id')->get();
        return view('themes.backoffice.pages.inventario.compras.configurar', compact('insumos', 'proveedores', 'sectores', 'unidades', 'provId'));
    }

    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'ins'                    => 'array',
            'ins.*.nombre'           => 'required|string|max:255',
            'ins.*.id_proveedor'     => 'nullable|exists:proveedores,id',
            'ins.*.id_sector'        => 'required|exists:sectores,id',
            'ins.*.stock_critico'    => 'nullable|numeric|min:0',
            'ins.*.stock_objetivo'   => 'nullable|numeric|min:0',
            'nuevo.nombre'           => 'nullable|string|max:255',
            'nuevo.id_unidad_medida' => 'required_with:nuevo.nombre|nullable|exists:unidades_medidas,id',
            'nuevo.id_sector'        => 'required_with:nuevo.nombre|nullable|exists:sectores,id',
        ]);

        $n = 0;
        foreach ($request->input('ins', []) as $id => $d) {
            $i = Insumo::find($id);
            if (! $i) continue;
            $i->fill([
                'nombre'         => trim($d['nombre']),
                'id_proveedor'   => $d['id_proveedor'] ?: null,
                'id_sector'      => $d['id_sector'],
                'ubicacion'      => trim($d['ubicacion'] ?? '') ?: null,
                'stock_critico'  => $d['stock_critico'] !== '' ? $d['stock_critico'] : 0,
                'stock_objetivo' => ($d['stock_objetivo'] ?? '') !== '' ? $d['stock_objetivo'] : null,
                'presentacion'   => trim($d['presentacion'] ?? '') ?: null,
            ]);
            if ($i->isDirty()) { $i->save(); $n++; }
        }

        $nuevo = $request->input('nuevo', []);
        if (! empty($nuevo['nombre'])) {
            Insumo::create([
                'nombre'           => trim($nuevo['nombre']),
                'id_unidad_medida' => $nuevo['id_unidad_medida'],
                'id_sector'        => $nuevo['id_sector'],
                'id_proveedor'     => ! empty($nuevo['id_proveedor']) ? $nuevo['id_proveedor'] : null,
                'ubicacion'        => trim($nuevo['ubicacion'] ?? '') ?: null,
                'stock_objetivo'   => ($nuevo['stock_objetivo'] ?? '') !== '' ? $nuevo['stock_objetivo'] : null,
                'stock_critico'    => ($nuevo['stock_critico'] ?? '') !== '' ? $nuevo['stock_critico'] : 0,
                'presentacion'     => trim($nuevo['presentacion'] ?? '') ?: null,
                'cantidad'         => 0,
                'valor'            => 0,
            ]);
            $n++;
        }

        return back()->with('success', "Guardado: {$n} insumo(s) actualizados.");
    }

    // ── 1. Lista de compras ──────────────────────────────────────────────────
    public function lista()
    {
        $inicios = $this->inv->inicios();
        $enCurso = $this->cantidadesEnCurso();
        $insumos = Insumo::with(['unidadMedida', 'proveedor'])->orderBy('nombre')->get();

        $grupos = [];
        foreach ($insumos as $i) {
            $stock = isset($inicios[$i->id]) ? (float) $i->cantidad : null;
            $pedido = $enCurso[$i->id] ?? 0;
            $sugerido = 0;
            if ($i->stock_objetivo !== null && $stock !== null) {
                $sugerido = max(0, (float) $i->stock_objetivo - $stock - $pedido);
            }
            $i->stock_actual = $stock;
            $i->en_curso = $pedido;
            $i->sugerido = round($sugerido, 3);
            $i->critico = $stock !== null && $stock <= (float) $i->stock_critico;
            $grupos[$i->id_proveedor ?: 0][] = $i;
        }

        // Proveedores con algo sugerido primero
        uksort($grupos, function ($a, $b) use ($grupos) {
            $sa = collect($grupos[$a])->where('sugerido', '>', 0)->count();
            $sb = collect($grupos[$b])->where('sugerido', '>', 0)->count();
            return $sb <=> $sa ?: $a <=> $b;
        });
        $proveedores = Proveedor::whereIn('id', array_filter(array_keys($grupos)))->pluck('nombre', 'id');

        return view('themes.backoffice.pages.inventario.compras.lista', compact('grupos', 'proveedores'));
    }

    public function crearPedidos(Request $request)
    {
        $request->validate(['cant' => 'array', 'cant.*' => 'nullable|numeric|min:0']);

        $porProveedor = [];
        foreach ($request->input('cant', []) as $idInsumo => $cant) {
            if ($cant === null || $cant === '' || (float) $cant <= 0) continue;
            $ins = Insumo::find($idInsumo);
            if (! $ins) continue;
            $porProveedor[$ins->id_proveedor ?: 0][] = ['id_insumo' => $ins->id, 'cantidad_pedida' => $cant];
        }
        if (! $porProveedor) return back()->with('error', 'No hay cantidades para pedir.');

        DB::transaction(function () use ($porProveedor, $request) {
            foreach ($porProveedor as $prov => $items) {
                $p = PedidoCompra::create([
                    'id_proveedor' => $prov ?: null,
                    'estado'       => 'por_comprar',
                    'creado_por'   => auth()->id(),
                    'observacion'  => $request->input('observacion'),
                ]);
                foreach ($items as $it) $p->items()->create($it + ['estado' => 'pendiente']);
            }
        });

        return redirect()->route('backoffice.inventario.compras.index')
            ->with('success', 'Se crearon ' . count($porProveedor) . ' pedido(s), uno por proveedor.');
    }

    // ── 2. Pedidos ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $estado = $request->input('estado');
        $pedidos = PedidoCompra::with(['proveedor', 'items', 'creador', 'comprador', 'receptor'])
            ->when($estado, function ($q) use ($estado) { $q->where('estado', $estado); },
                   function ($q) { $q->where(function ($w) {
                       $w->whereIn('estado', ['por_comprar', 'comprado', 'con_diferencias'])
                         ->orWhere('updated_at', '>=', now()->subDays(14));
                   }); })
            ->orderByRaw("FIELD(estado, 'por_comprar', 'comprado', 'con_diferencias', 'recibido', 'cancelado')")
            ->orderByDesc('id')->get();

        $conteo = PedidoCompra::groupBy('estado')->selectRaw('estado, COUNT(*) n')->pluck('n', 'estado');
        $facturasSinRecepcion = $this->facturasSinRecepcion();
        $estados = PedidoCompra::ESTADOS;
        return view('themes.backoffice.pages.inventario.compras.index', compact('pedidos', 'conteo', 'estado', 'estados', 'facturasSinRecepcion'));
    }

    public function show(PedidoCompra $pedido)
    {
        $pedido->load(['items.insumo.unidadMedida', 'proveedor', 'egreso', 'creador', 'comprador', 'receptor']);
        $estadosItem = PedidoCompraItem::ESTADOS;
        $estados = PedidoCompra::ESTADOS;
        return view('themes.backoffice.pages.inventario.compras.show', compact('pedido', 'estadosItem', 'estados'));
    }

    public function cancelar(PedidoCompra $pedido)
    {
        abort_unless(in_array($pedido->estado, ['por_comprar', 'comprado'], true), 422, 'Este pedido ya fue recibido.');
        $pedido->update(['estado' => 'cancelado']);
        return redirect()->route('backoffice.inventario.compras.index')->with('success', 'Pedido cancelado.');
    }

    // ── 3. Compra ───────────────────────────────────────────────────────────
    public function comprarForm(PedidoCompra $pedido)
    {
        abort_unless(in_array($pedido->estado, ['por_comprar', 'comprado'], true), 422, 'Este pedido ya fue recibido.');
        $pedido->load(['items.insumo.unidadMedida', 'proveedor']);
        return view('themes.backoffice.pages.inventario.compras.comprar', compact('pedido'));
    }

    public function comprarStore(Request $request, PedidoCompra $pedido)
    {
        abort_unless(in_array($pedido->estado, ['por_comprar', 'comprado'], true), 422);
        $request->validate([
            'item'                  => 'required|array',
            'item.*.comprada'       => 'nullable|numeric|min:0',
            'item.*.costo'          => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $pedido) {
            foreach ($pedido->items as $it) {
                $d = $request->input("item.{$it->id}", []);
                $noHabia = ! empty($d['no_habia']);
                $comprada = $noHabia ? 0 : (($d['comprada'] ?? '') === '' ? $it->cantidad_pedida : $d['comprada']);
                $it->update([
                    'cantidad_comprada' => $comprada,
                    'costo_total_neto'  => ($d['costo'] ?? '') === '' ? null : $d['costo'],
                    'estado'            => (float) $comprada > 0 ? 'comprado' : 'no_habia',
                    'nota'              => trim($d['nota'] ?? '') ?: null,
                ]);
            }
            $pedido->update(['estado' => 'comprado', 'comprado_por' => auth()->id(), 'comprado_at' => now()]);
        });

        return redirect()->route('backoffice.inventario.compras.index')
            ->with('success', 'Compra registrada. Queda pendiente que alguien reciba y cuente lo que llegó.');
    }

    // ── 4. Recepción a ciegas ───────────────────────────────────────────────
    public function recibirForm(PedidoCompra $pedido)
    {
        abort_unless($pedido->estado === 'comprado', 422, 'Solo se reciben pedidos ya comprados.');
        $pedido->load(['items.insumo.unidadMedida', 'proveedor']);
        $facturas = Egreso::where('fuente', 'sii')->where('total', '>', 0)
            ->when($pedido->id_proveedor, function ($q) use ($pedido) { $q->where('proveedor_id', $pedido->id_proveedor); })
            ->where('fecha_egreso', '>=', now()->subDays(45)->toDateString())
            ->whereNotIn('id', PedidoCompra::whereNotNull('egreso_id')->where('id', '!=', $pedido->id)->pluck('egreso_id'))
            ->orderByDesc('fecha_egreso')->limit(50)->get();
        $insumos = Insumo::with('unidadMedida')->orderBy('nombre')->get();
        return view('themes.backoffice.pages.inventario.compras.recibir', compact('pedido', 'facturas', 'insumos'));
    }

    public function recibirStore(Request $request, PedidoCompra $pedido)
    {
        abort_unless($pedido->estado === 'comprado', 422);
        $request->validate([
            'recibida'          => 'required|array',
            'recibida.*'        => 'required|numeric|min:0',
            'egreso_id'         => 'nullable|exists:egresos,id',
            'extra'             => 'nullable|array',
            'extra.*.id_insumo' => 'nullable|exists:insumos,id',
            'extra.*.cantidad'  => 'nullable|numeric|gt:0',
        ], ['recibida.*.required' => 'Falta contar uno o más productos: si no llegó, anota 0.']);

        $lineasInv = [];
        $hayDiferencias = false;

        DB::transaction(function () use ($request, $pedido, &$lineasInv, &$hayDiferencias) {
            foreach ($pedido->items as $it) {
                $rec = (float) $request->input("recibida.{$it->id}");
                $esperada = $it->cantidad_comprada !== null ? (float) $it->cantidad_comprada : (float) $it->cantidad_pedida;
                if ($it->estado === 'no_habia' && $rec == 0) {
                    $estado = 'no_habia';
                } elseif ($rec == 0) {
                    $estado = 'no_llego';
                } elseif (abs($rec - $esperada) < 0.0001) {
                    $estado = 'ok';
                } else {
                    $estado = $rec < $esperada ? 'faltante' : 'sobrante';
                }
                if (! in_array($estado, ['ok', 'no_habia'], true)) $hayDiferencias = true;

                $notaRecepcion = trim((string) $request->input("nota.{$it->id}"));
                $nota = implode(' | ', array_filter([$it->nota, $notaRecepcion ? 'Recepción: ' . $notaRecepcion : null]));
                $it->update(['cantidad_recibida' => $rec, 'estado' => $estado, 'nota' => $nota ?: null]);

                if ($rec > 0) {
                    $unit = ($it->costo_total_neto !== null && (float) $it->cantidad_comprada > 0)
                        ? (float) $it->costo_total_neto / (float) $it->cantidad_comprada : null;
                    $lineasInv[] = ['id_insumo' => $it->id_insumo, 'cantidad' => $rec, 'costo_unitario' => $unit];
                }
            }

            foreach ($request->input('extra', []) as $ex) {
                if (empty($ex['id_insumo']) || empty($ex['cantidad'])) continue;
                $pedido->items()->create(['id_insumo' => $ex['id_insumo'], 'cantidad_pedida' => 0,
                    'cantidad_recibida' => $ex['cantidad'], 'estado' => 'extra', 'nota' => $ex['nota'] ?? null]);
                $lineasInv[] = ['id_insumo' => $ex['id_insumo'], 'cantidad' => $ex['cantidad']];
                $hayDiferencias = true;
            }

            $egreso = $request->input('egreso_id') ? Egreso::find($request->input('egreso_id')) : null;
            $mov = $lineasInv ? $this->inv->registrar('entrada', $egreso ? 'compra_factura' : 'compra_manual', $lineasInv, [
                'referencia'  => $pedido,
                'observacion' => 'Recepción pedido #' . $pedido->id . ' · ' . $pedido->nombreProveedor(),
            ]) : null;

            $pedido->update([
                'estado'        => $hayDiferencias ? 'con_diferencias' : 'recibido',
                'recibido_por'  => auth()->id(),
                'recibido_at'   => now(),
                'egreso_id'     => $egreso ? $egreso->id : null,
                'movimiento_id' => $mov ? $mov->id : null,
            ]);

            // Aprender proveedor habitual de los insumos que no lo tenían
            if ($pedido->id_proveedor) {
                Insumo::whereIn('id', collect($lineasInv)->pluck('id_insumo'))->whereNull('id_proveedor')
                    ->update(['id_proveedor' => $pedido->id_proveedor]);
            }
        });

        return redirect()->route('backoffice.inventario.compras.show', $pedido)
            ->with($hayDiferencias ? 'error' : 'success', $hayDiferencias
                ? 'Recepción registrada CON DIFERENCIAS. Revisa el detalle y avisa al proveedor si corresponde.'
                : 'Recepción registrada: llegó todo lo comprado.');
    }

    // ── Apoyo ───────────────────────────────────────────────────────────────

    /** Cantidad ya pedida y aún no recibida, por insumo. */
    private function cantidadesEnCurso()
    {
        return DB::table('pedido_compra_items as i')
            ->join('pedidos_compra as p', 'p.id', '=', 'i.pedido_id')
            ->whereIn('p.estado', ['por_comprar', 'comprado'])
            ->groupBy('i.id_insumo')
            ->selectRaw("i.id_insumo, SUM(CASE WHEN p.estado = 'comprado' THEN COALESCE(i.cantidad_comprada, 0) ELSE i.cantidad_pedida END) as total")
            ->pluck('total', 'id_insumo')->toArray();
    }

    /** Proveedores que aparecen en facturas o ya asignados a insumos (para elegir). */
    private function proveedoresInsumos()
    {
        $ids = DB::table('egresos')->where('fuente', 'sii')->where('fecha_egreso', '>=', now()->subYear()->toDateString())
            ->whereNotNull('proveedor_id')->distinct()->pluck('proveedor_id')
            ->merge(Insumo::whereNotNull('id_proveedor')->distinct()->pluck('id_proveedor'))->unique();
        return Proveedor::whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre']);
    }

    /** Facturas recientes de proveedores de insumos sin recepción registrada. */
    private function facturasSinRecepcion()
    {
        $provs = Insumo::whereNotNull('id_proveedor')->distinct()->pluck('id_proveedor');
        if ($provs->isEmpty()) return collect();
        $ligadas = PedidoCompra::whereNotNull('egreso_id')->pluck('egreso_id')
            ->merge(DB::table('inventario_movimientos')->where('referencia_type', Egreso::class)->pluck('referencia_id'));
        return Egreso::with('proveedor')->where('fuente', 'sii')->where('total', '>', 0)
            ->whereIn('proveedor_id', $provs)
            ->where('fecha_egreso', '>=', now()->subDays(21)->toDateString())
            ->whereNotIn('id', $ligadas)
            ->orderByDesc('fecha_egreso')->get();
    }
}
