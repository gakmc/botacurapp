<?php
namespace App\Http\Controllers;

use App\DetalleConsumo;
use App\DetalleVentaDirecta;
use App\Events\Consumos\EstadoConsumoActualizado;
use App\Producto;
use App\Services\WebPushService;
use App\User;
use App\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarmanController extends Controller
{
    public function index()
    {
        $fechaActual = Carbon::now()->startOfDay();

        $productos = DB::table('reservas')
            ->join('ventas', 'reservas.id', '=', 'ventas.id_reserva')
            ->join('consumos', 'ventas.id', '=', 'consumos.id_venta')
            ->join('visitas', 'reservas.id', '=', 'visitas.id_reserva')
            ->join('ubicaciones', 'ubicaciones.id', '=', 'visitas.id_ubicacion')
            ->join('detalles_consumos', 'consumos.id', '=', 'detalles_consumos.id_consumo')
            ->join('productos', 'detalles_consumos.id_producto', '=', 'productos.id')
            ->join('tipos_productos', 'productos.id_tipo_producto', '=', 'tipos_productos.id')
            ->join('sectores', 'tipos_productos.id_sector', '=', 'sectores.id')
            ->join('clientes', 'reservas.cliente_id', '=', 'clientes.id')
            ->where('reservas.fecha_visita', '>=', $fechaActual)
        // ->whereIn('tipos_productos.nombre', ['bebestibles', 'cocteles'])
            ->where(function ($query) {
                $query->where('sectores.nombre', 'barra');
            })
            ->select(
                'detalles_consumos.id_consumo',
                'clientes.nombre_cliente',
                'detalles_consumos.cantidad_producto',
                'detalles_consumos.estado as estado',
                'detalles_consumos.id as id',
                'detalles_consumos.subtotal',
                'detalles_consumos.created_at as creado',
                'productos.nombre as producto',
                'reservas.fecha_visita',
                'tipos_productos.nombre as categoria',
                'ubicaciones.nombre as ubicacion'
            )
            ->orderBy('reservas.fecha_visita', 'asc')
            ->get();

        // dd($productos);

        // $pedidos = $productos
        //     ->sortBy('creado')
        //     ->groupBy(['estado', 'id_consumo']);

        $productos = $productos->map(function($row){
            $row->origen = 'consumo';
            $row->pedido_key = $row->id_consumo.'|'.Carbon::parse($row->creado)->format('Y-m-d H:i:s');
            return $row;
        });

        $productos = $productos->concat($this->productosVentaDirectaBarra());

        $pedidos = $productos
            ->groupBy('estado')
            ->map(function ($porEstado) {
                return $porEstado->groupBy('pedido_key');
            });

        return view('themes.backoffice.pages.barman.index', [
            'productos' => $productos,
            'pedidos' => $pedidos,
        ]);

    }

    /**
     * Productos de barra vendidos vía Venta Directa (sin reserva asociada),
     * normalizados con la misma forma que usan las filas de Consumo.
     */
    private function productosVentaDirectaBarra()
    {
        $fechaActual = Carbon::now()->startOfDay();

        return DB::table('detalles_ventas_directas')
            ->join('ventas_directas', 'ventas_directas.id', '=', 'detalles_ventas_directas.venta_directa_id')
            ->join('productos', 'detalles_ventas_directas.producto_id', '=', 'productos.id')
            ->join('tipos_productos', 'productos.id_tipo_producto', '=', 'tipos_productos.id')
            ->join('sectores', 'tipos_productos.id_sector', '=', 'sectores.id')
            ->where('ventas_directas.fecha', '>=', $fechaActual->toDateString())
            ->where('sectores.nombre', 'barra')
            ->select(
                'detalles_ventas_directas.id as id',
                'detalles_ventas_directas.cantidad as cantidad_producto',
                'detalles_ventas_directas.estado as estado',
                'detalles_ventas_directas.subtotal',
                'detalles_ventas_directas.created_at as creado',
                'productos.nombre as producto',
                'tipos_productos.nombre as categoria',
                'ventas_directas.id as venta_directa_id'
            )
            ->orderBy('ventas_directas.created_at', 'asc')
            ->get()
            ->map(function ($row) {
                $row->origen        = 'venta_directa';
                $row->id_consumo    = null;
                $row->nombre_cliente = 'Venta Directa';
                $row->ubicacion     = 'Venta Directa';
                $row->pedido_key    = 'vd-' . $row->venta_directa_id;
                return $row;
            });
    }

    public function OLDactualizarEstado(Request $request, $id)
    {
        // // Buscar el detalle de consumo por ID
        // $detalleConsumo = DB::table('detalles_consumos')->where('id', $id)->first();

        // if (!$detalleConsumo) {
        //     return response()->json(['error' => 'Detalle de consumo no encontrado.'], 404);
        // }

        // // Actualizar el estado del detalle
        // DB::table('detalles_consumos')
        //     ->where('id', $id)
        //     ->update(['estado' => $request->input('estado')]);

        // return response()->json(['success' => true, 'estado' => $request->input('estado')]);

        $detalle         = DetalleConsumo::findOrFail($id);
        $detalle->estado = $request->estado;
        $detalle->save();

        $visita = Visita::where('id_reserva', $detalle->consumo->venta->reserva->id)->first();

        $producto = [
            'nombre'    => $detalle->producto->nombre,
            'cantidad'  => $detalle->cantidad_producto,
            'cliente'   => $detalle->consumo->venta->reserva->cliente->nombre_cliente,
            'ubicacion' => $visita->ubicacion->nombre,
        ];

        // broadcast(new EstadoConsumoActualizado($detalle->id, $detalle->estado, $producto));
        event(new EstadoConsumoActualizado($detalle->id, $detalle->estado, $producto));

        return response()->json(['success' => true, 'estado' => $request->input('estado')]);
    }

    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:por-procesar,en-preparacion,completado,entregado',
            'origen' => 'nullable|in:consumo,venta_directa',
        ]);

        if ($request->input('origen') === 'venta_directa') {
            return $this->actualizarEstadoVentaDirecta($request, $id);
        }

        // $id siempre corresponde a un id_consumo (pedido completo), nunca a un detalle individual.
        $idConsumo = $id;

        if ($request->filled('pedido_creado')) {
            $pedidoCreado = Carbon::parse($request->pedido_creado)->format('Y-m-d H:i:s');

            DetalleConsumo::where('id_consumo', $idConsumo)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?", [$pedidoCreado])
                ->update(['estado' => $request->estado]);
        } else {
            DetalleConsumo::where('id_consumo', $idConsumo)
                ->update(['estado' => $request->estado]);
        }

        // Tomamos un detalle para obtener datos del cliente
        $detalleBase = DetalleConsumo::where('id_consumo', $idConsumo)
            ->with(['consumo.venta.reserva.cliente', 'producto'])
            ->first();

        if (!$detalleBase) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $reservaId = $detalleBase->consumo->venta->reserva->id;

        $visita = Visita::where('id_reserva', $reservaId)
            ->with('ubicacion')
            ->first();

        $pedidoCreado = $request->filled('pedido_creado')
            ? Carbon::parse($request->pedido_creado)->format('Y-m-d H:i:s')
            : null;

        $producto = [
            'origen'    => 'consumo',
            'pedido_id' => $idConsumo,
            'cliente'   => $detalleBase->consumo->venta->reserva->cliente->nombre_cliente,
            'ubicacion' => $visita ? $visita->ubicacion->nombre : '',
            'pedido_creado'  => $pedidoCreado,
            'pedido_key'     => $pedidoCreado ? ($idConsumo.'|'.$pedidoCreado) : (string)$idConsumo,
        ];

        $items = DetalleConsumo::where('id_consumo', $idConsumo)
            ->when($pedidoCreado, function($q) use ($pedidoCreado) {
                $q->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?", [$pedidoCreado]);
            })
            ->with('producto:id,nombre')
            ->get()
            ->map(function($d){
                return [
                    'id_detalle' => $d->id,
                    'nombre'     => $d->producto->nombre,
                    'cantidad'   => $d->cantidad_producto,
                ];
            })
            ->values()
            ->all();

        $producto['items'] = $items;

        if ($request->estado === 'completado') {
            $this->notificarPedidoListo($producto['cliente'], $producto['ubicacion']);
        }

        // Reutilizamos tu evento actual
        event(new EstadoConsumoActualizado(
            $producto['pedido_key'],                  // ahora enviamos pedido completo
            $request->estado,
            $producto
        ));

        return response()->json([
            'success' => true,
            'estado'  => $request->estado
        ]);
    }

    private function actualizarEstadoVentaDirecta(Request $request, $ventaDirectaId)
    {
        DetalleVentaDirecta::where('venta_directa_id', $ventaDirectaId)
            ->update(['estado' => $request->estado]);

        $tieneDetalles = DetalleVentaDirecta::where('venta_directa_id', $ventaDirectaId)->exists();

        if (!$tieneDetalles) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $items = DetalleVentaDirecta::where('venta_directa_id', $ventaDirectaId)
            ->with('producto:id,nombre')
            ->get()
            ->map(function ($d) {
                return [
                    'id_detalle' => $d->id,
                    'nombre'     => $d->producto->nombre,
                    'cantidad'   => $d->cantidad,
                ];
            })
            ->values()
            ->all();

        $producto = [
            'origen'     => 'venta_directa',
            'pedido_id'  => $ventaDirectaId,
            'cliente'    => 'Venta Directa',
            'ubicacion'  => 'Venta Directa',
            'pedido_key' => 'vd-' . $ventaDirectaId,
            'items'      => $items,
        ];

        if ($request->estado === 'completado') {
            $this->notificarPedidoListo($producto['cliente'], $producto['ubicacion']);
        }

        event(new EstadoConsumoActualizado(
            $producto['pedido_key'],
            $request->estado,
            $producto
        ));

        return response()->json([
            'success' => true,
            'estado'  => $request->estado
        ]);
    }

    private function notificarPedidoListo(string $cliente, string $ubicacion)
    {
        $fechaHoy = Carbon::now()->toDateString();

        $usuarios = User::query()
            ->join('asignacion_user', 'users.id', '=', 'asignacion_user.user_id')
            ->join('asignaciones', 'asignacion_user.asignacion_id', '=', 'asignaciones.id')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereDate('asignaciones.fecha', $fechaHoy)
            ->whereIn('roles.name', ['garzon', 'anfitrion'])
            ->select('users.*')
            ->distinct()
            ->get();

        if ($usuarios->isNotEmpty()) {
            app(WebPushService::class)->sendToUsers($usuarios, [
                'title' => 'Pedido listo',
                'body'  => 'Cliente: '.$cliente.' - Ubicación: '.$ubicacion,
                'url'   => url('/barman/bebidas'),
            ]);
        }
    }

    public function bebidas()
    {
        $fechaActual = Carbon::now()->startOfDay();

        $productos = DB::table('reservas')
            ->join('ventas', 'reservas.id', '=', 'ventas.id_reserva')
            ->join('consumos', 'ventas.id', '=', 'consumos.id_venta')
            ->join('visitas', 'reservas.id', '=', 'visitas.id_reserva')
            ->join('ubicaciones', 'ubicaciones.id', '=', 'visitas.id_ubicacion')
            ->join('detalles_consumos', 'consumos.id', '=', 'detalles_consumos.id_consumo')
            ->join('productos', 'detalles_consumos.id_producto', '=', 'productos.id')
            ->join('tipos_productos', 'productos.id_tipo_producto', '=', 'tipos_productos.id')
            ->join('sectores', 'tipos_productos.id_sector', '=', 'sectores.id')
            ->join('clientes', 'reservas.cliente_id', '=', 'clientes.id')
            ->where('reservas.fecha_visita', '>=', $fechaActual)
        // ->whereIn('tipos_productos.nombre', ['bebestibles', 'cocteles'])
            ->where(function ($query) {
                $query->where('sectores.nombre', 'barra');
            })
            ->select(
                'clientes.nombre_cliente',
                'detalles_consumos.cantidad_producto',
                'detalles_consumos.estado as estado',
                'detalles_consumos.id as id',
                'detalles_consumos.subtotal',
                'detalles_consumos.created_at as creado',
                'productos.nombre as producto',
                'reservas.fecha_visita',
                'tipos_productos.nombre as categoria',
                'ubicaciones.nombre as ubicacion',
                'detalles_consumos.id_consumo'
            )
            ->orderBy('reservas.fecha_visita', 'asc')
            ->get();

        // dd($productos);

        // $pedidos = $productos
        //     ->groupBy('estado') // completado / entregado
        //     ->map(function ($porEstado) {
        //         return $porEstado->groupBy('id_consumo'); // agrupar por pedido
        //     });

        $productos = $productos->map(function($row){
            $row->origen = 'consumo';
            $row->pedido_key = $row->id_consumo.'|'.Carbon::parse($row->creado)->format('Y-m-d H:i:s');
            return $row;
        });

        $productos = $productos->concat($this->productosVentaDirectaBarra());

        $pedidos = $productos
            ->groupBy('estado')
            ->map(function ($porEstado) {
                return $porEstado->groupBy('pedido_key');
            });

        return view('themes.backoffice.pages.barman.bebida', [
            'productos' => $productos,
            'pedidos' => $pedidos,
        ]);
    }

}
