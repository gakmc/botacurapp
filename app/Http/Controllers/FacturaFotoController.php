<?php

namespace App\Http\Controllers;

use App\FacturaFoto;
use App\PedidoCompra;
use App\Services\FacturaFotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Foto de factura impresa -> lectura con IA -> corroboración con el SII ->
 * revisión humana -> líneas al catálogo de compras (compra_items).
 */
class FacturaFotoController extends Controller
{
    /** @var FacturaFotoService */
    private $svc;

    public function __construct(FacturaFotoService $svc)
    {
        $this->svc = $svc;
        $this->middleware(function ($request, $next) {
            $u = auth()->user();
            $ok = $u && ($u->has_role(config('app.admin_role')) || $u->has_role(config('app.cocina_role'))
                || $u->has_role(config('app.jefe_local_role')) || $u->has_role(config('app.barman_role')));
            abort_unless($ok, 403);
            return $next($request);
        });
    }

    public function index()
    {
        $fotos = FacturaFoto::with(['egreso.proveedor', 'pedido', 'usuario'])->orderByDesc('id')->limit(100)->get();
        return view('themes.backoffice.pages.inventario.facturas.index', compact('fotos'));
    }

    public function create(Request $request)
    {
        $pedido = $request->input('pedido_id') ? PedidoCompra::with('proveedor')->find($request->input('pedido_id')) : null;
        return view('themes.backoffice.pages.inventario.facturas.create', compact('pedido'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivos'   => 'required|array|min:1|max:8',
            'archivos.*' => 'file|mimes:jpeg,jpg,png,webp,pdf|max:12288',
            'pedido_id'  => 'nullable|exists:pedidos_compra,id',
        ], ['archivos.required' => 'Sube al menos una foto de la factura.']);

        $rutas = [];
        foreach ($request->file('archivos') as $f) {
            $rutas[] = $f->store('facturas_fotos/' . now()->format('Y/m'));
        }
        $foto = FacturaFoto::create([
            'archivos' => $rutas, 'pedido_id' => $request->input('pedido_id'), 'subida_por' => auth()->id(), 'estado' => 'pendiente',
        ]);
        $this->leer($foto);
        return redirect()->route('backoffice.inventario.facturas.show', $foto);
    }

    public function show(FacturaFoto $foto)
    {
        $foto->load(['egreso.proveedor', 'pedido.proveedor', 'pedido.items.insumo.unidadMedida']);
        return view('themes.backoffice.pages.inventario.facturas.show', compact('foto'));
    }

    /** Sirve una de las fotos subidas (solo usuarios con acceso). */
    public function archivo(FacturaFoto $foto, $n)
    {
        $ruta = $foto->archivos[(int) $n] ?? null;
        abort_unless($ruta && Storage::exists($ruta), 404);
        return response()->file(Storage::path($ruta));
    }

    /** Vuelve a leer las fotos con la IA (por ejemplo, tras un error). */
    public function releer(FacturaFoto $foto)
    {
        abort_if($foto->estado === 'confirmada', 422);
        $this->leer($foto);
        return redirect()->route('backoffice.inventario.facturas.show', $foto);
    }

    /** Guarda las correcciones hechas a mano y vuelve a verificar contra el SII. */
    public function actualizar(Request $request, FacturaFoto $foto)
    {
        abort_if($foto->estado === 'confirmada', 422, 'La factura ya fue confirmada.');
        $request->validate([
            'cab.rut_emisor' => 'nullable|string|max:15', 'cab.folio' => 'nullable|string|max:20', 'cab.fecha_emision' => 'nullable|date',
            'cab.neto' => 'nullable|integer', 'cab.exento' => 'nullable|integer', 'cab.iva' => 'nullable|integer', 'cab.total' => 'nullable|integer',
            'lin' => 'nullable|array', 'lin.*.descripcion' => 'nullable|string|max:255',
            'lin.*.cantidad' => 'nullable|numeric', 'lin.*.precio_unitario' => 'nullable|numeric', 'lin.*.monto' => 'nullable|numeric',
        ]);
        $d = $foto->extraccion ?: [];
        foreach (['rut_emisor', 'razon_social', 'folio', 'fecha_emision', 'tipo_documento'] as $k) {
            if ($request->has("cab.$k")) $d[$k] = trim((string) $request->input("cab.$k")) ?: null;
        }
        foreach (['neto', 'exento', 'iva', 'total'] as $k) {
            $val = $request->input("cab.$k");
            $d[$k] = ($val === null || $val === '') ? null : (int) $val;
        }
        $lineas = [];
        foreach ($request->input('lin', []) as $l) {
            if (trim((string) ($l['descripcion'] ?? '')) === '') continue;
            $num = function ($k) use ($l) { return ($l[$k] ?? '') === '' ? null : (float) $l[$k]; };
            $lineas[] = ['codigo' => trim((string) ($l['codigo'] ?? '')) ?: null, 'descripcion' => trim($l['descripcion']),
                'cantidad' => $num('cantidad'), 'unidad' => trim((string) ($l['unidad'] ?? '')) ?: null,
                'precio_unitario' => $num('precio_unitario'), 'descuento' => $num('descuento'), 'monto' => $num('monto')];
        }
        $d['lineas'] = $lineas;
        $v = $this->svc->verificar($d);
        $foto->update(['extraccion' => $d, 'verificacion' => $v, 'egreso_id' => $v['egreso_id']]);
        return redirect()->route('backoffice.inventario.facturas.show', $foto)->with('success', 'Cambios guardados y verificados contra el SII.');
    }

    public function confirmar(FacturaFoto $foto)
    {
        abort_if($foto->estado === 'confirmada', 422);
        $d = $foto->extraccion ?: [];
        if (empty($d['rut_emisor']) || empty($d['folio'])) {
            return back()->with('error', 'Falta el RUT del emisor o el folio. Corrígelos antes de confirmar.');
        }
        $n = $this->svc->guardarLineas($foto->id, $d, $foto->egreso_id);
        $foto->update(['estado' => 'confirmada', 'confirmada_por' => auth()->id()]);
        if ($foto->pedido && ! $foto->pedido->egreso_id && $foto->egreso_id) {
            $foto->pedido->update(['egreso_id' => $foto->egreso_id]);
        }
        return redirect()->route('backoffice.inventario.facturas.show', $foto)
            ->with('success', $n ? "Factura confirmada: {$n} producto(s) agregados al catálogo de compras." : 'Factura confirmada. Sus productos ya estaban registrados (XML o foto anterior).');
    }

    public function descartar(FacturaFoto $foto)
    {
        abort_if($foto->estado === 'confirmada', 422);
        $foto->update(['estado' => 'descartada']);
        return redirect()->route('backoffice.inventario.facturas.index')->with('success', 'Foto descartada.');
    }

    private function leer(FacturaFoto $foto)
    {
        try {
            $d = $this->svc->extraer($foto->archivos);
            $v = $this->svc->verificar($d);
            $foto->update(['extraccion' => $d, 'verificacion' => $v, 'egreso_id' => $v['egreso_id'], 'estado' => 'pendiente', 'error' => null]);
        } catch (\Throwable $e) {
            $foto->update(['estado' => 'error', 'error' => substr($e->getMessage(), 0, 1000)]);
        }
    }
}
