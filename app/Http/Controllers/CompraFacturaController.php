<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\FacturaFoto;
use App\Insumo;
use App\Proveedor;
use App\Sector;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Facturas recibidas (XML del SII o foto) -> selección múltiple de productos
 * que pasan al inventario, cada uno a su insumo y sector, con la cantidad
 * comprada y el costo con IVA incluido. Lo que no es inventario no se marca.
 */
class CompraFacturaController extends Controller
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

    public function index(Request $request)
    {
        $desde = $request->input('desde', now()->subDays(45)->toDateString());
        $estado = $request->input('estado', 'pendientes');
        $buscar = trim((string) $request->input('buscar', ''));

        $facturas = DB::table('compra_items')
            ->where('fecha', '>=', $desde)->where('dte', '<>', 61)
            ->when($buscar !== '', function ($q) use ($buscar) { $q->where(function ($w) use ($buscar) { $w->where('razon_social', 'like', "%{$buscar}%")->orWhere('folio', $buscar); }); })
            ->groupBy('rut_emisor', 'dte', 'folio', 'razon_social', 'fecha')
            ->selectRaw("rut_emisor, dte, folio, razon_social, fecha, COUNT(*) lineas, SUM(monto) neto,
                SUM(inventario = 'agregado') agregadas, SUM(inventario = 'omitido') omitidas, SUM(inventario IS NULL) pendientes,
                MAX(origen) origen")
            ->when($estado === 'pendientes', function ($q) { $q->havingRaw('SUM(inventario IS NULL) > 0'); })
            ->orderByDesc('fecha')->limit(300)->get();

        return view('themes.backoffice.pages.inventario.recibidas.index', compact('facturas', 'desde', 'estado', 'buscar'));
    }

    public function show($rut, $dte, $folio)
    {
        $lineas = DB::table('compra_items')->where('rut_emisor', $rut)->where('dte', $dte)->where('folio', $folio)->orderBy('linea')->get();
        abort_if($lineas->isEmpty(), 404);
        $cab = $lineas->first();

        $insumos = Insumo::with('unidadMedida')->orderBy('nombre')->get();
        $sectores = Sector::orderBy('nombre')->get();
        $unidades = DB::table('unidades_medidas')->orderBy('id')->get();
        $mapa = DB::table('compra_producto_insumo')->where('rut_emisor', $rut)->get()->keyBy('clave');
        $abrevInsumo = $insumos->mapWithKeys(function ($i) { return [$i->id => strtolower(optional($i->unidadMedida)->abreviatura)]; });

        foreach ($lineas as $l) {
            $m = $mapa[self::clave($l)] ?? null;
            $l->sug_insumo = $m ? $m->id_insumo : self::sugerirInsumo($l->nombre, $insumos);
            $l->sug_factor = $m ? (float) $m->factor : ($l->sug_insumo ? self::sugerirFactor($l, $abrevInsumo[$l->sug_insumo] ?? '') : 1);
            $l->recordado = (bool) $m;
            $l->iva = in_array((int) $l->dte, [34], true) ? 1 : 1.19;
        }

        // Si esta factura ya entró por la recepción de un pedido, agregarla de nuevo duplicaría el stock
        $egresoId = $cab->egreso_id;
        $pedidoRecibido = $egresoId ? DB::table('pedidos_compra')->where('egreso_id', $egresoId)->whereNotNull('movimiento_id')->first() : null;
        $sectorProv = optional(Insumo::where('id_proveedor', optional(Proveedor::whereRaw("UPPER(REPLACE(rut, '.', '')) = ?", [$rut])->first())->id)->first())->id_sector;

        return view('themes.backoffice.pages.inventario.recibidas.show', compact('lineas', 'cab', 'insumos', 'sectores', 'unidades', 'pedidoRecibido', 'sectorProv'));
    }

    public function agregar(Request $request, $rut, $dte, $folio)
    {
        $request->validate([
            'sel'               => 'nullable|array',
            'l'                 => 'nullable|array',
            'l.*.id_insumo'     => 'nullable',
            'l.*.factor'        => 'nullable|numeric|gt:0',
            'l.*.id_sector'     => 'nullable|exists:sectores,id',
            'l.*.nuevo_nombre'  => 'nullable|string|max:255',
            'l.*.nuevo_unidad'  => 'nullable|exists:unidades_medidas,id',
        ]);
        $lineas = DB::table('compra_items')->where('rut_emisor', $rut)->where('dte', $dte)->where('folio', $folio)->get()->keyBy('id');
        abort_if($lineas->isEmpty(), 404);
        $cab = $lineas->first();
        $sel = array_map('intval', array_keys(array_filter((array) $request->input('sel', []))));
        $proveedorId = optional(Proveedor::whereRaw("UPPER(REPLACE(rut, '.', '')) = ?", [$rut])->first())->id;

        $res = DB::transaction(function () use ($request, $lineas, $sel, $cab, $rut, $proveedorId) {
            $movLineas = [];
            $marcar = [];
            foreach ($sel as $id) {
                $l = $lineas[$id] ?? null;
                if (! $l || $l->inventario === 'agregado' || (float) $l->monto <= 0 || (float) $l->cantidad <= 0) continue;
                $d = $request->input("l.$id", []);
                $sector = $d['id_sector'] ?? null;

                if (($d['id_insumo'] ?? '') === 'nuevo') {
                    $nombre = trim((string) ($d['nuevo_nombre'] ?? '')) ?: mb_convert_case(mb_strtolower($l->nombre), MB_CASE_TITLE);
                    $insumo = Insumo::create([
                        'nombre' => $nombre, 'id_unidad_medida' => $d['nuevo_unidad'] ?? 6, 'id_sector' => $sector ?: 1,
                        'id_proveedor' => $proveedorId, 'cantidad' => 0, 'valor' => 0, 'stock_critico' => 0,
                    ]);
                } else {
                    $insumo = Insumo::find((int) ($d['id_insumo'] ?? 0));
                    if (! $insumo) continue;
                    $cambios = [];
                    if ($sector && (int) $insumo->id_sector !== (int) $sector) $cambios['id_sector'] = $sector;
                    if (! $insumo->id_proveedor && $proveedorId) $cambios['id_proveedor'] = $proveedorId;
                    if ($cambios) $insumo->update($cambios);
                }

                $factor = (float) ($d['factor'] ?? 1) ?: 1;
                $cant = round((float) $l->cantidad * $factor, 4);
                $iva = (int) $l->dte === 34 ? 1 : 1.19;
                $movLineas[] = [
                    'id_insumo' => $insumo->id, 'cantidad' => $cant, 'id_unidad_medida' => $insumo->id_unidad_medida,
                    'costo_unitario' => (float) $l->monto / $cant, 'costo_unitario_iva' => (float) $l->monto * $iva / $cant,
                ];
                $marcar[$id] = $cant;

                DB::table('compra_producto_insumo')->updateOrInsert(
                    ['rut_emisor' => $rut, 'clave' => self::clave($l)],
                    ['nombre' => $l->nombre, 'id_insumo' => $insumo->id, 'factor' => $factor, 'updated_at' => now(), 'created_at' => now()]
                );
                // Asocia también las demás compras históricas de ese mismo producto
                DB::table('compra_items')->where('rut_emisor', $rut)->whereNull('id_insumo')->where(function ($q) use ($l) { $l->codigo ? $q->where('codigo', $l->codigo) : $q->where('nombre', $l->nombre); })
                    ->update(['id_insumo' => $insumo->id]);
            }

            $mov = null;
            if ($movLineas) {
                $mov = $this->inv->registrar('entrada', 'compra_factura', $movLineas, [
                    'fecha'           => $cab->fecha . ' 12:00:00',
                    'referencia_type' => $cab->egreso_id ? Egreso::class : ($cab->factura_foto_id ? FacturaFoto::class : null),
                    'referencia_id'   => $cab->egreso_id ?: $cab->factura_foto_id,
                    'observacion'     => 'Factura ' . $cab->razon_social . ' folio ' . $cab->folio,
                ]);
                foreach ($marcar as $id => $cant) {
                    DB::table('compra_items')->where('id', $id)->update(['inventario' => 'agregado', 'cantidad_inventario' => $cant, 'movimiento_id' => $mov->id, 'updated_at' => now()]);
                }
            }

            $omitidas = 0;
            if ($request->boolean('omitir_resto')) {
                $omitidas = DB::table('compra_items')->whereIn('id', $lineas->keys())->whereNull('inventario')->whereNotIn('id', array_keys($marcar))
                    ->update(['inventario' => 'omitido', 'updated_at' => now()]);
            }
            return [count($marcar), $omitidas];
        });

        return redirect()->route('backoffice.inventario.recibidas.show', [$rut, $dte, $folio])
            ->with('success', "{$res[0]} producto(s) pasaron al inventario" . ($res[1] ? ", {$res[1]} marcados como no inventario" : '') . '.');
    }

    // ── Apoyo ───────────────────────────────────────────────────────────────

    /** Identifica el producto de un proveedor: su código, o el nombre normalizado. */
    public static function clave($l)
    {
        return $l->codigo ? 'c:' . $l->codigo : 'n:' . mb_substr(self::normalizar($l->nombre), 0, 180);
    }

    private static function normalizar($s)
    {
        $s = strtoupper(strtr((string) $s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']));
        return trim(preg_replace('/[^A-Z0-9]+/', ' ', $s));
    }

    /** Insumo cuyo nombre aparece completo en la descripción de la factura (el más largo gana). */
    private static function sugerirInsumo($nombreLinea, $insumos)
    {
        $linea = ' ' . self::normalizar($nombreLinea) . ' ';
        $mejor = null; $largo = 0;
        foreach ($insumos as $i) {
            $palabras = array_filter(explode(' ', self::normalizar($i->nombre)), function ($p) { return strlen($p) > 2; });
            if (! $palabras) continue;
            $todas = true;
            foreach ($palabras as $p) {
                $raiz = rtrim($p, 'S');
                if (strpos($linea, ' ' . $raiz) === false) { $todas = false; break; }
            }
            if ($todas && strlen(implode('', $palabras)) > $largo) { $mejor = $i->id; $largo = strlen(implode('', $palabras)); }
        }
        return $mejor;
    }

    /** Conversión sugerida: por unidad de la factura o por el formato en el nombre ("MALLA 2KGS", "1.5LT"). */
    private static function sugerirFactor($l, $umInsumo)
    {
        $um = strtoupper((string) $l->unidad);
        $escala = ['kg' => ['peso', 1000], 'g' => ['peso', 1], 'lt' => ['vol', 1000], 'ml' => ['vol', 1]];
        $ins = $escala[$umInsumo] ?? null;
        if (in_array($um, ['KG', 'KGV', 'KGS', 'K'], true)) return $ins && $ins[0] === 'peso' ? 1000 / $ins[1] : 1;
        if ($ins && preg_match('/(\d+(?:\.\d+)?)\s*(KGS?|K|GRS?|G|LTS?|L|ML|CC)\b/i', str_replace(',', '.', $l->nombre), $m)) {
            $v = (float) $m[1];
            $u = strtoupper($m[2]);
            $tipo = in_array($u, ['KG', 'KGS', 'K', 'G', 'GR', 'GRS'], true) ? 'peso' : 'vol';
            $base = in_array($u, ['KG', 'KGS', 'K', 'L', 'LT', 'LTS'], true) ? 1000 : 1;
            if ($tipo === $ins[0]) return round($v * $base / $ins[1], 6);
        }
        return 1;
    }
}
